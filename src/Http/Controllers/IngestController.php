<?php

namespace Kazispace\Bridge\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Kazispace\Bridge\Support\ResultApply;
use Kazispace\Bridge\Support\SnapshotFreshness;

class IngestController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $payload = $request->json()->all();
        if (($payload['contract_version'] ?? null) !== '1') {
            return response()->json(['error' => 'contract_version must be 1'], 422);
        }
        $organizationId = $payload['organization_id'] ?? null;
        $vehicleId = $payload['fleetbase_vehicle_id'] ?? null;
        $observedAt = $payload['observed_at'] ?? null;

        if (! is_string($organizationId) || $organizationId === '' || ! is_string($vehicleId) || $vehicleId === '' || ! SnapshotFreshness::isUtc($observedAt)) {
            return response()->json(['error' => 'organization_id, fleetbase_vehicle_id, and observed_at (UTC ISO-8601) are required'], 422);
        }

        $snapshot = is_array($payload['snapshot'] ?? null) ? $payload['snapshot'] : [];
        $samples = is_array($payload['samples'] ?? null) ? $payload['samples'] : [];
        $alert = is_array($payload['alert'] ?? null) ? $payload['alert'] : null;
        $observedAtSql = SnapshotFreshness::toSqlUtc($observedAt);

        $snapshotUpdated = DB::transaction(function () use ($organizationId, $vehicleId, $observedAt, $observedAtSql, $snapshot, $samples, $alert): bool {
            $now = now();
            $key = [
                'organization_id' => $organizationId,
                'fleetbase_vehicle_id' => $vehicleId,
            ];
            $existing = DB::table('kz_vehicle_battery_snapshot')->where($key)->lockForUpdate()->first();
            if (! ResultApply::accepts($existing?->observed_at, $observedAt)) {
                return false;
            }

            $snapshotValues = [
                'soc' => $snapshot['soc'] ?? null,
                'soh' => $snapshot['soh'] ?? null,
                'pack_voltage' => $snapshot['pack_voltage'] ?? null,
                'cell_delta_v' => $snapshot['cell_delta_v'] ?? null,
                'temp_max' => $snapshot['temp_max'] ?? null,
                'charge_state' => $snapshot['charge_state'] ?? null,
                'health_score' => $snapshot['health_score'] ?? null,
                'health_risk' => $snapshot['health_risk'] ?? null,
                'range_estimate_km' => $snapshot['range_estimate_km'] ?? null,
                'observed_at' => $observedAtSql,
                'updated_at' => $now,
            ];
            if ($existing === null) {
                DB::table('kz_vehicle_battery_snapshot')->insert($key + $snapshotValues + ['created_at' => $now]);
            } else {
                $updated = DB::table('kz_vehicle_battery_snapshot')
                    ->where($key)
                    ->where('observed_at', '<', $observedAtSql)
                    ->update($snapshotValues);
                if ($updated !== 1) {
                    return false;
                }
            }

            foreach ($samples as $sample) {
                if (! is_array($sample) || ! SnapshotFreshness::isUtc($sample['observed_at'] ?? null)) {
                    continue;
                }
                DB::table('kz_vehicle_battery_samples')->insertOrIgnore([
                    'organization_id' => $organizationId,
                    'fleetbase_vehicle_id' => $vehicleId,
                    'observed_at' => SnapshotFreshness::toSqlUtc($sample['observed_at']),
                    'soc' => $sample['soc'] ?? null,
                    'soh' => $sample['soh'] ?? null,
                    'pack_voltage' => $sample['pack_voltage'] ?? null,
                    'temp_max' => $sample['temp_max'] ?? null,
                    'speed' => $sample['speed'] ?? null,
                    'created_at' => $now,
                ]);
            }

            if ($alert !== null && ! empty($alert['alert_text'])) {
                $faultCode = $alert['fault_code'] ?? null;
                $open = null;
                if (is_string($faultCode) && $faultCode !== '') {
                    $open = DB::table('kz_vehicle_alerts')
                        ->where('organization_id', $organizationId)
                        ->where('fleetbase_vehicle_id', $vehicleId)
                        ->where('fault_code', $faultCode)
                        ->where('status', 'open')
                        ->first();
                }
                if ($open !== null) {
                    DB::table('kz_vehicle_alerts')->where('id', $open->id)->update([
                        'alert_level' => $alert['alert_level'] ?? 'info',
                        'alert_text' => $alert['alert_text'],
                        'fault_summary' => $alert['fault_summary'] ?? null,
                        'repair_advice' => $alert['repair_advice'] ?? null,
                        'updated_at' => $now,
                    ]);
                } else {
                    DB::table('kz_vehicle_alerts')->insert([
                        'organization_id' => $organizationId,
                        'fleetbase_vehicle_id' => $vehicleId,
                        'alert_level' => $alert['alert_level'] ?? 'info',
                        'alert_text' => $alert['alert_text'],
                        'fault_code' => $faultCode,
                        'fault_summary' => $alert['fault_summary'] ?? null,
                        'repair_advice' => $alert['repair_advice'] ?? null,
                        'status' => 'open',
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);
                }
            }

            return true;
        });

        return response()->json(ResultApply::response($snapshotUpdated));
    }
}

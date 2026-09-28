<?php

namespace Kazispace\Bridge\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;

class IngestController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $payload = $request->json()->all();
        $organizationId = $payload['organization_id'] ?? null;
        $vehicleId = $payload['fleetbase_vehicle_id'] ?? null;
        $observedAt = $payload['observed_at'] ?? null;

        if (! is_string($organizationId) || $organizationId === '' || ! is_string($vehicleId) || $vehicleId === '' || ! is_string($observedAt) || ! $this->isUtcTimestamp($observedAt)) {
            return response()->json(['error' => 'organization_id, fleetbase_vehicle_id, and observed_at (UTC ISO-8601) are required'], 422);
        }

        $snapshot = is_array($payload['snapshot'] ?? null) ? $payload['snapshot'] : [];
        $now = now();
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
            'observed_at' => $observedAt,
            'updated_at' => $now,
        ];
        $key = [
            'organization_id' => $organizationId,
            'fleetbase_vehicle_id' => $vehicleId,
        ];
        $existing = DB::table('kz_vehicle_battery_snapshot')->where($key)->exists();
        if ($existing) {
            DB::table('kz_vehicle_battery_snapshot')->where($key)->update($snapshotValues);
        } else {
            DB::table('kz_vehicle_battery_snapshot')->insert($key + $snapshotValues + ['created_at' => $now]);
        }

        foreach ($payload['samples'] ?? [] as $sample) {
            if (! is_array($sample) || ! $this->isUtcTimestamp($sample['observed_at'] ?? null)) {
                continue;
            }
            DB::table('kz_vehicle_battery_samples')->insertOrIgnore([
                'organization_id' => $organizationId,
                'fleetbase_vehicle_id' => $vehicleId,
                'observed_at' => $sample['observed_at'],
                'soc' => $sample['soc'] ?? null,
                'soh' => $sample['soh'] ?? null,
                'pack_voltage' => $sample['pack_voltage'] ?? null,
                'temp_max' => $sample['temp_max'] ?? null,
                'speed' => $sample['speed'] ?? null,
                'created_at' => $now,
            ]);
        }

        $alert = is_array($payload['alert'] ?? null) ? $payload['alert'] : null;
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

        return response()->json(['stored' => true]);
    }

    private function isUtcTimestamp(mixed $value): bool
    {
        return is_string($value) && preg_match('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}Z$/', $value) === 1;
    }
}

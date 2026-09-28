<?php

namespace Kazispace\Bridge\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;

class ResultController extends Controller
{
    public function snapshot(Request $request, string $vehicleId): JsonResponse
    {
        $organizationId = $this->organizationId();
        $row = DB::table('kz_vehicle_battery_snapshot')
            ->where('organization_id', $organizationId)
            ->where('fleetbase_vehicle_id', $vehicleId)
            ->first();

        return response()->json(['snapshot' => $row]);
    }

    public function samples(Request $request, string $vehicleId): JsonResponse
    {
        $organizationId = $this->organizationId();
        $rows = DB::table('kz_vehicle_battery_samples')
            ->where('organization_id', $organizationId)
            ->where('fleetbase_vehicle_id', $vehicleId)
            ->orderByDesc('observed_at')
            ->limit(500)
            ->get();

        return response()->json(['samples' => $rows]);
    }

    public function alerts(Request $request): JsonResponse
    {
        $organizationId = $this->organizationId();
        $rows = DB::table('kz_vehicle_alerts')
            ->where('organization_id', $organizationId)
            ->orderByDesc('created_at')
            ->limit(200)
            ->get();

        return response()->json(['alerts' => $rows]);
    }

    public function updateAlert(Request $request, int $alertId): JsonResponse
    {
        $organizationId = $this->organizationId();
        $status = (string) $request->input('status', '');
        if (! in_array($status, ['open', 'ack', 'closed'], true)) {
            return response()->json(['error' => 'status must be open, ack, or closed'], 422);
        }

        $updated = DB::table('kz_vehicle_alerts')
            ->where('organization_id', $organizationId)
            ->where('id', $alertId)
            ->update(['status' => $status, 'updated_at' => now()]);

        return response()->json(['updated' => $updated === 1]);
    }

    private function organizationId(): string
    {
        $user = auth()->user();
        $organizationId = $user->company_uuid ?? $user->company_id ?? null;
        if (! is_string($organizationId) && ! is_numeric($organizationId)) {
            abort(403, 'organization unresolved');
        }

        return (string) $organizationId;
    }
}

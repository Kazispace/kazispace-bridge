<?php

namespace Kazispace\Bridge\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Kazispace\Bridge\Support\CurveWindow;
use Kazispace\Bridge\Support\InvalidCurveWindow;
use Kazispace\Bridge\Support\OrganizationScope;

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

    public function snapshots(Request $request): JsonResponse
    {
        $organizationId = $this->organizationId();
        $rows = DB::table('kz_vehicle_battery_snapshot')
            ->where('organization_id', $organizationId)
            ->orderByDesc('observed_at')
            ->get();

        return response()->json(['snapshots' => $rows]);
    }

    public function samples(Request $request, string $vehicleId): JsonResponse
    {
        $organizationId = $this->organizationId();
        try {
            $window = CurveWindow::resolve($request->query('from'), $request->query('to'), gmdate('Y-m-d\TH:i:s\Z'));
        } catch (InvalidCurveWindow $error) {
            return response()->json(['error' => $error->getMessage()], 422);
        }

        $rows = DB::table('kz_vehicle_battery_samples')
            ->where('organization_id', $organizationId)
            ->where('fleetbase_vehicle_id', $vehicleId)
            ->where('observed_at', '>=', $window->sqlFrom)
            ->where('observed_at', '<=', $window->sqlTo)
            ->orderBy('observed_at')
            ->get(CurveWindow::COLUMNS);

        return response()->json([
            'from' => $window->from,
            'to' => $window->to,
            'samples' => $rows,
        ]);
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
        $organizationId = OrganizationScope::fromUser(auth()->user());
        if ($organizationId === null) {
            abort(403, 'organization unresolved');
        }

        return $organizationId;
    }
}

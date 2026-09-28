<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kz_vehicle_battery_snapshot', function (Blueprint $table): void {
            $table->id();
            $table->string('organization_id');
            $table->string('fleetbase_vehicle_id');
            $table->decimal('soc', 6, 2)->nullable();
            $table->decimal('soh', 6, 2)->nullable();
            $table->decimal('pack_voltage', 8, 2)->nullable();
            $table->decimal('cell_delta_v', 8, 3)->nullable();
            $table->decimal('temp_max', 6, 2)->nullable();
            $table->string('charge_state')->nullable();
            $table->decimal('health_score', 6, 2)->nullable();
            $table->string('health_risk')->nullable();
            $table->decimal('range_estimate_km', 8, 1)->nullable();
            $table->timestamp('observed_at');
            $table->timestamps();
            $table->unique(['organization_id', 'fleetbase_vehicle_id'], 'kz_snapshot_org_vehicle');
        });

        Schema::create('kz_vehicle_battery_samples', function (Blueprint $table): void {
            $table->id();
            $table->string('organization_id');
            $table->string('fleetbase_vehicle_id');
            $table->timestamp('observed_at');
            $table->decimal('soc', 6, 2)->nullable();
            $table->decimal('soh', 6, 2)->nullable();
            $table->decimal('pack_voltage', 8, 2)->nullable();
            $table->decimal('temp_max', 6, 2)->nullable();
            $table->decimal('speed', 8, 2)->nullable();
            $table->timestamp('created_at')->nullable();
            $table->index(['organization_id', 'fleetbase_vehicle_id', 'observed_at'], 'kz_samples_lookup');
        });

        Schema::create('kz_vehicle_alerts', function (Blueprint $table): void {
            $table->id();
            $table->string('organization_id');
            $table->string('fleetbase_vehicle_id');
            $table->string('alert_level');
            $table->text('alert_text');
            $table->string('fault_code')->nullable();
            $table->text('fault_summary')->nullable();
            $table->text('repair_advice')->nullable();
            $table->string('status')->default('open');
            $table->timestamps();
            $table->index(['organization_id', 'status'], 'kz_alerts_org_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kz_vehicle_alerts');
        Schema::dropIfExists('kz_vehicle_battery_samples');
        Schema::dropIfExists('kz_vehicle_battery_snapshot');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('kz_vehicle_battery_samples', function (Blueprint $table): void {
            $table->unique(
                ['organization_id', 'fleetbase_vehicle_id', 'observed_at'],
                'kz_samples_unique_point'
            );
        });
    }

    public function down(): void
    {
        Schema::table('kz_vehicle_battery_samples', function (Blueprint $table): void {
            $table->dropUnique('kz_samples_unique_point');
        });
    }
};

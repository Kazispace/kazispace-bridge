<?php

namespace Kazispace\Bridge;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class BridgeServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/kazispace.php', 'kazispace');
    }

    public function boot(): void
    {
        $this->publishes([
            __DIR__.'/../config/kazispace.php' => config_path('kazispace.php'),
        ], 'kazispace-config');

        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');

        Route::middleware(['api', \Kazispace\Bridge\Http\Middleware\VerifyIngestSignature::class])
            ->prefix('kazispace/v1')
            ->group(function (): void {
                Route::post('ingest', [\Kazispace\Bridge\Http\Controllers\IngestController::class, 'store']);
            });

        Route::middleware(['web', 'auth'])
            ->prefix('int/v1/kazispace')
            ->group(function (): void {
                Route::get('vehicles/{vehicleId}/snapshot', [\Kazispace\Bridge\Http\Controllers\ResultController::class, 'snapshot']);
                Route::get('vehicles/{vehicleId}/samples', [\Kazispace\Bridge\Http\Controllers\ResultController::class, 'samples']);
                Route::get('alerts', [\Kazispace\Bridge\Http\Controllers\ResultController::class, 'alerts']);
                Route::patch('alerts/{alertId}', [\Kazispace\Bridge\Http\Controllers\ResultController::class, 'updateAlert']);
            });
    }
}

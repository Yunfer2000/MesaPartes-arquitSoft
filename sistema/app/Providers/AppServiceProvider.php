<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // API REST v1: límites por minuto y por IP (config/api.php). Cada uno lleva su propio
        // contador para que el límite general y el de cada endpoint no se sumen.
        foreach (['general', 'consultas', 'verificaciones', 'tokens'] as $nombre) {
            RateLimiter::for("api-{$nombre}", fn (Request $request) => Limit::perMinute(config("api.limite_{$nombre}"))
                ->by("api-{$nombre}|".$request->ip())
                ->response(fn () => response()->json(['message' => 'Demasiadas peticiones. Espera un minuto e inténtalo de nuevo.'], 429)));
        }
    }
}

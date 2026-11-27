<?php

use App\Http\Controllers\Api\V1\ConsultaController;
use App\Http\Controllers\Api\V1\TipoDocumentoController;
use App\Http\Controllers\Api\V1\TokenController;
use App\Http\Controllers\Api\V1\VerificacionController;
use App\Models\Rol;
use Illuminate\Support\Facades\Route;

/*
| API REST v1 (Sprint 2 del curso de Arquitectura de Software).
| Responde siempre en JSON. Contrato: public/docs/openapi.yaml, visible en /api-docs.
| Usa la misma capa de servicios que las pantallas Blade: la API es otra "puerta" al mismo monolito.
*/
Route::prefix('v1')->name('api.v1.')->middleware('throttle:api-general')->group(function () {
    // Públicas (sin cuenta)
    Route::get('/tipos-documento', [TipoDocumentoController::class, 'index'])->name('tipos.index');
    Route::get('/tipos-documento/{tipo}', [TipoDocumentoController::class, 'show'])->whereNumber('tipo')->name('tipos.show');
    Route::post('/consultas', [ConsultaController::class, 'store'])->middleware('throttle:api-consultas')->name('consultas.store');
    Route::get('/verificaciones/{codigo}', [VerificacionController::class, 'show'])->middleware('throttle:api-verificaciones')->name('verificaciones.show');

    // Token de acceso del personal (Laravel Sanctum)
    Route::post('/tokens', [TokenController::class, 'store'])->middleware('throttle:api-tokens')->name('tokens.store');

    // Solo el Administrador, con token Bearer
    Route::middleware(['auth:sanctum', 'rol:'.Rol::ADMINISTRADOR])->group(function () {
        Route::delete('/tokens/actual', [TokenController::class, 'destroy'])->name('tokens.destroy');
        Route::post('/tipos-documento', [TipoDocumentoController::class, 'store'])->name('tipos.store');
        Route::put('/tipos-documento/{tipo}', [TipoDocumentoController::class, 'update'])->whereNumber('tipo')->name('tipos.update');
        Route::delete('/tipos-documento/{tipo}', [TipoDocumentoController::class, 'destroy'])->whereNumber('tipo')->name('tipos.destroy');
    });
});

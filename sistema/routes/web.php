<?php

use App\Http\Controllers\Admin\AreaController as AdminAreaController;
use App\Http\Controllers\Admin\AuditoriaController;
use App\Http\Controllers\Admin\TipoDocumentoController;
use App\Http\Controllers\Admin\UsuarioController;
use App\Http\Controllers\AreaController;
use App\Http\Controllers\BusquedaController;
use App\Http\Controllers\CopiaController;
use App\Http\Controllers\ReporteController;
use App\Http\Controllers\DerivacionController;
use App\Http\Controllers\DocumentoController;
use App\Http\Controllers\MesaPartesController;
use App\Http\Controllers\PdfController;
use App\Http\Controllers\PendientesController;
use App\Http\Controllers\TramiteController;
use App\Http\Controllers\TramiteExternoController;
use App\Http\Controllers\VerificacionController;
use App\Http\Controllers\Auth\SesionController;
use App\Models\Rol;
use App\Support\Menu;
use Illuminate\Support\Facades\Route;

/*
| RNF17: las pantallas del personal (login incluido) solo responden en un subdominio aparte,
| por ejemplo personal.mesadepartes.tecnologicohierropozo.edu.pe. Así el login no queda a la
| vista en la página pública. Si APP_STAFF_DOMAIN está vacío (en tu PC), todo funciona en un
| solo dominio, como antes.
*/
$dominioPersonal = config('app.staff_domain');

$rutasPersonal = function () use ($dominioPersonal) {
    if ($dominioPersonal) {
        // En el subdominio, la raíz lleva al login o a la bandeja del usuario
        Route::get('/', fn () => auth()->check() ? redirect(Menu::inicio(auth()->user())) : redirect()->route('login'))
            ->name('personal.inicio');
    }

    // Acceso del personal (CU02). CU01 Registrar cuenta se eliminó: el público presenta sin cuenta.
    Route::middleware('guest')->group(function () {
        Route::get('/ingresar', [SesionController::class, 'create'])->name('login');
        Route::post('/ingresar', [SesionController::class, 'store'])->middleware('throttle:20,1');
    });
    Route::post('/salir', [SesionController::class, 'destroy'])->middleware('auth')->name('logout');

    // Compartidas: el permiso lo decide ExpedientePolicy
    Route::middleware('auth')->group(function () {
        Route::get('/expedientes/{expediente}/constancia', [TramiteController::class, 'constancia'])->name('tramites.constancia');
        // RF26: constancia y respuestas en PDF con QR
        Route::get('/expedientes/{expediente}/constancia/pdf', [PdfController::class, 'constancia'])->name('tramites.constancia.pdf');
        Route::get('/respuestas/{respuesta}/pdf', [PdfController::class, 'respuesta'])->name('respuestas.pdf');
        Route::get('/documentos/{documento}', [DocumentoController::class, 'show'])->name('documentos.show');
    });
    // Mesa de Partes (CU08, CU09, CU17)
    Route::middleware(['auth', 'rol:'.Rol::MESA_DE_PARTES])->prefix('mesa-de-partes')->name('mesa.')->group(function () {
        Route::get('/', [MesaPartesController::class, 'bandeja'])->name('bandeja');
        Route::get('/presencial', [MesaPartesController::class, 'presencial'])->name('presencial');
        Route::get('/presencial/solicitante', [MesaPartesController::class, 'buscarSolicitante'])->middleware('throttle:60,1')->name('presencial.buscar');
        Route::post('/presencial', [MesaPartesController::class, 'registrarPresencial'])->name('presencial.store');
        Route::get('/expedientes/{expediente}', [MesaPartesController::class, 'show'])->name('show');
        Route::post('/expedientes/{expediente}/recepcionar', [MesaPartesController::class, 'recepcionar'])->name('recepcionar');
        Route::post('/expedientes/{expediente}/observar', [MesaPartesController::class, 'observar'])->name('observar');
    });

    // Áreas (CU11, CU12, CU13)
    Route::middleware(['auth', 'rol:'.Rol::AREA])->prefix('area')->name('area.')->group(function () {
        Route::get('/', [AreaController::class, 'bandeja'])->name('bandeja');
        Route::get('/expedientes/{expediente}', [AreaController::class, 'show'])->name('show');
        Route::post('/expedientes/{expediente}/confirmar', [AreaController::class, 'confirmar'])->name('confirmar');
        Route::post('/expedientes/{expediente}/avance', [AreaController::class, 'avance'])->name('avance');
        Route::post('/expedientes/{expediente}/responder', [AreaController::class, 'responder'])->name('responder');
        // Derivación de uno a muchos: marcar como leída una copia para conocimiento
        Route::post('/copias/{copia}/leido', [CopiaController::class, 'leer'])->name('copias.leer');
    });

    // CU10 Derivar (Mesa de Partes y áreas)
    Route::post('/expedientes/{expediente}/derivar', [DerivacionController::class, 'store'])
        ->middleware(['auth', 'rol:'.Rol::MESA_DE_PARTES.','.Rol::AREA])->name('expedientes.derivar');
    // Administración (CU03, CU04)
    Route::middleware(['auth', 'rol:'.Rol::ADMINISTRADOR])->prefix('admin')->name('admin.')->group(function () {
        Route::get('/usuarios', [UsuarioController::class, 'index'])->name('usuarios.index');
        Route::post('/usuarios', [UsuarioController::class, 'store'])->name('usuarios.store');
        Route::put('/usuarios/{usuario}', [UsuarioController::class, 'update'])->name('usuarios.update');
        Route::patch('/usuarios/{usuario}/estado', [UsuarioController::class, 'estado'])->name('usuarios.estado');
        Route::get('/areas', [AdminAreaController::class, 'index'])->name('areas.index');
        Route::post('/areas', [AdminAreaController::class, 'store'])->name('areas.store');
        Route::put('/areas/{area}', [AdminAreaController::class, 'update'])->name('areas.update');
        Route::patch('/areas/{area}/estado', [AdminAreaController::class, 'estado'])->name('areas.estado');
        Route::get('/tipos-documento', [TipoDocumentoController::class, 'index'])->name('tipos.index');
        Route::post('/tipos-documento', [TipoDocumentoController::class, 'store'])->name('tipos.store');
        Route::put('/tipos-documento/{tipo}', [TipoDocumentoController::class, 'update'])->name('tipos.update');
        Route::patch('/tipos-documento/{tipo}/estado', [TipoDocumentoController::class, 'estado'])->name('tipos.estado');
        Route::get('/auditoria', [AuditoriaController::class, 'index'])->name('auditoria');
    });

    // Búsqueda y reportes (CU14, CU15): todo el personal
    Route::middleware(['auth', 'rol:'.implode(',', [Rol::MESA_DE_PARTES, Rol::AREA, Rol::ADMINISTRADOR])])->group(function () {
        Route::get('/busqueda', [BusquedaController::class, 'index'])->name('busqueda');
        Route::get('/expedientes/{expediente}', [BusquedaController::class, 'show'])->name('expedientes.show');
        Route::get('/reportes', [ReporteController::class, 'index'])->name('reportes');
    });

    // CU18 Monitorear plazos y pendientes: Administrador y Dirección General
    Route::get('/pendientes', [PendientesController::class, 'index'])
        ->middleware(['auth', 'rol:'.Rol::AREA.','.Rol::ADMINISTRADOR])->name('pendientes');
};

// Las rutas del personal van primero para que, en el subdominio, "/" sea la del personal.
if ($dominioPersonal) {
    Route::domain($dominioPersonal)->group($rutasPersonal);
} else {
    $rutasPersonal();
}

// Página pública: portal y Mesa de Partes sin cuenta (CU05, CU06, CU07)
Route::get('/', fn () => auth()->check() ? redirect(Menu::inicio(auth()->user())) : app(TramiteExternoController::class)->portal())
    ->name('inicio');

// Mesa de Partes sin cuenta (CU05, CU06, CU07): cualquier persona con su documento de identidad
Route::name('externo.')->group(function () {
    Route::get('/tramite/nuevo', [TramiteExternoController::class, 'create'])->name('create');
    Route::post('/tramite', [TramiteExternoController::class, 'store'])->middleware('throttle:10,1')->name('store');
    Route::get('/tramite/remitente', [TramiteExternoController::class, 'buscar'])->middleware('throttle:20,1')->name('buscar');
    Route::get('/consulta', [TramiteExternoController::class, 'consulta'])->name('consulta');
    Route::post('/consulta', [TramiteExternoController::class, 'buscarExpediente'])->middleware('throttle:10,1')->name('consultar');
    Route::get('/consulta/{numero}', [TramiteExternoController::class, 'show'])->name('show');
    Route::post('/consulta/{numero}/salir', [TramiteExternoController::class, 'salir'])->name('salir');
    Route::get('/consulta/{numero}/constancia', [TramiteExternoController::class, 'constancia'])->name('constancia');
    Route::get('/consulta/{numero}/constancia/pdf', [PdfController::class, 'constanciaExterna'])->name('constancia.pdf');
    Route::get('/consulta/{numero}/respuestas/{respuesta}/pdf', [PdfController::class, 'respuestaExterna'])->name('respuesta.pdf');
    Route::post('/consulta/{numero}/subsanar', [TramiteExternoController::class, 'subsanar'])->middleware('throttle:10,1')->name('subsanar');
    Route::get('/consulta/{numero}/documentos/{documento}', [TramiteExternoController::class, 'documento'])->name('documento');
});

// CU19 Verificar documento (firma electrónica del sistema, RF26): público y sin cuenta
Route::get('/verificar', [VerificacionController::class, 'index'])->name('verificar');
Route::get('/verificar/{codigo}', [VerificacionController::class, 'show'])->middleware('throttle:30,1')->name('verificar.show');
Route::post('/verificar/{codigo}', [VerificacionController::class, 'comprobar'])->middleware('throttle:10,1')->name('verificar.comprobar');

// Sprint 2: documentación de la API REST v1 (OpenAPI 3 en public/docs/openapi.yaml) con Swagger UI
Route::view('/api-docs', 'api-docs')->name('api.docs');

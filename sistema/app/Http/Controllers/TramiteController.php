<?php

namespace App\Http\Controllers;

use App\Models\Expediente;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

/** Constancia del expediente para el personal. Las pantallas del administrado con cuenta se quitaron (CU01 eliminado). */
class TramiteController extends Controller
{
    /** CU17 paso 9: cargo del registro presencial (Figura 13). */
    public function constancia(Expediente $expediente): View
    {
        Gate::authorize('view', $expediente);

        return view('tramites.constancia', ['expediente' => $expediente->load('administrado', 'tipoDocumento', 'areaDestino', 'documentos')]);
    }
}

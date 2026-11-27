<?php

namespace App\Http\Controllers;

use App\Models\Copia;
use App\Services\FlujoExpediente;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/** Derivación de uno a muchos: el área marca como leída la copia que recibió. */
class CopiaController extends Controller
{
    public function leer(Copia $copia, Request $request, FlujoExpediente $flujo): RedirectResponse
    {
        // Solo el área a la que se envió la copia puede marcarla
        abort_unless($copia->area_id === $request->user()->area_id, 403);
        $flujo->tomarConocimiento($copia, $request->user());

        return back()->with('exito', "Copia del expediente {$copia->expediente->numero} marcada como leída.");
    }
}

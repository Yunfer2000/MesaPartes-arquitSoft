<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Error de una regla del trámite (flujos alternos).
 * En las pantallas vuelve a la página anterior con el aviso; en la API responde 409 Conflict en JSON.
 */
class ReglaNegocio extends Exception
{
    public function render(Request $request): RedirectResponse|JsonResponse
    {
        if ($request->is('api/*') || $request->expectsJson()) {
            return response()->json(['message' => $this->getMessage()], 409);
        }

        return back()->withInput()->with('aviso', $this->getMessage());
    }
}

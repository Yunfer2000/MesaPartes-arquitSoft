<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\ConsultaRequest;
use App\Http\Resources\SeguimientoResource;
use App\Services\ConsultaExpediente;
use Illuminate\Http\JsonResponse;

/** CU07 Consultar seguimiento por API. Se usa POST para no dejar el DNI en la URL ni en los registros del servidor. */
class ConsultaController extends Controller
{
    public function store(ConsultaRequest $request, ConsultaExpediente $consulta): JsonResponse|SeguimientoResource
    {
        $expediente = $consulta->buscar($request->validated('numero'), $request->validated('numero_documento'));

        if (! $expediente) {
            // 3a. No se dice cuál de los dos datos falló
            return response()->json(['message' => 'No encontramos un expediente con ese número o código y ese documento de identidad.'], 404);
        }

        $expediente->load(['tipoDocumento', 'administrado', 'movimientos.areaDestino']);

        return new SeguimientoResource($expediente);
    }
}

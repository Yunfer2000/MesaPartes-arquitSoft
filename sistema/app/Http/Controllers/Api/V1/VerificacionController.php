<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\VerificacionResource;
use App\Services\Verificacion;
use Illuminate\Http\JsonResponse;

/** CU19 Verificar documento por API: lo puede usar otro sistema o una app que lea el QR. */
class VerificacionController extends Controller
{
    public function show(string $codigo): JsonResponse|VerificacionResource
    {
        $codigo = Verificacion::normalizar($codigo);
        $resultado = Verificacion::buscar($codigo);

        if (! $resultado) {
            return response()->json(['autentico' => false, 'message' => 'No existe un documento con ese código de verificación.'], 404);
        }

        return new VerificacionResource($resultado + ['codigo' => $codigo]);
    }
}

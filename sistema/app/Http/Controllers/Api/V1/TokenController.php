<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\TokenRequest;
use App\Models\Usuario;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Hash;

/** Token Bearer para el personal (Laravel Sanctum). Solo usuarios activos. */
class TokenController extends Controller
{
    /** POST /api/v1/tokens → 201 con el token, 401 si las credenciales no sirven */
    public function store(TokenRequest $request): JsonResponse
    {
        $usuario = Usuario::with('rol')->where('correo', $request->validated('correo'))->first();

        if (! $usuario || ! $usuario->activo || ! Hash::check($request->validated('contrasena'), $usuario->contrasena)) {
            return response()->json(['message' => 'Correo o contraseña incorrectos.'], 401);
        }

        $token = $usuario->createToken($request->validated('dispositivo') ?? 'api', ['*'], now()->addHours(8));

        return response()->json([
            'token' => $token->plainTextToken,
            'tipo' => 'Bearer',
            'expira' => $token->accessToken->expires_at?->toIso8601String(),
            'usuario' => ['nombre' => $usuario->nombre_completo, 'rol' => $usuario->rol->nombre],
        ], 201);
    }

    /** DELETE /api/v1/tokens/actual → 204: cierra la sesión de la API */
    public function destroy(Request $request): Response
    {
        $request->user()->currentAccessToken()->delete();

        return response()->noContent();
    }
}

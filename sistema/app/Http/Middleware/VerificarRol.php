<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** Uso en rutas: ->middleware('rol:Mesa de Partes,Administrador') */
class VerificarRol
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $usuario = $request->user();

        abort_unless($usuario && $usuario->tieneRol(...$roles), 403, 'No tienes permiso para ver esta página.');

        return $next($request);
    }
}

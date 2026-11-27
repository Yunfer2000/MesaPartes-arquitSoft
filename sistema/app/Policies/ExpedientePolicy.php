<?php

namespace App\Policies;

use App\Models\Expediente;
use App\Models\Rol;
use App\Models\Usuario;

/** RNF15: cada usuario ve solo los expedientes que le corresponden. */
class ExpedientePolicy
{
    public function view(Usuario $usuario, Expediente $expediente): bool
    {
        return match ($usuario->rol->nombre) {
            Rol::MESA_DE_PARTES, Rol::ADMINISTRADOR => true,
            // El área lo ve si pasó por ella o si le enviaron copia para conocimiento
            Rol::AREA => $expediente->movimientos()->where('area_destino_id', $usuario->area_id)->exists()
                || $expediente->copias()->where('area_id', $usuario->area_id)->exists(),
            default => false,
        };
    }

    /** Puede recepcionar, observar, derivar o atender: el expediente debe estar ahora en su área. */
    public function operar(Usuario $usuario, Expediente $expediente): bool
    {
        if (! $usuario->tieneRol(Rol::MESA_DE_PARTES, Rol::AREA)) {
            return false;
        }

        return $usuario->area_id !== null && $expediente->areaActual()?->id === $usuario->area_id;
    }
}

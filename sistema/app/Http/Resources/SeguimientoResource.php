<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * CU07: seguimiento del expediente para la API. Solo datos que el administrado ya ve en la
 * pantalla pública; correo y celular no se devuelven (Ley 29733).
 */
class SeguimientoResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'numero' => $this->numero,
            'asunto' => $this->asunto,
            'modalidad' => $this->modalidad,
            'estado' => $this->estado,
            'tipo_documento' => $this->tipoDocumento?->nombre,
            // Si el controlador ya cargó los movimientos (eager loading), se usa el último de la
            // colección y se evitan 2 consultas extra; si no, se pregunta a la base de datos.
            'area_actual' => ($this->relationLoaded('movimientos')
                ? $this->movimientos->last()?->areaDestino
                : $this->areaActual())?->nombre,
            'fecha_presentacion' => $this->fecha_presentacion?->toIso8601String(),
            'fecha_recepcion' => $this->fecha_recepcion?->toIso8601String(),
            'remitente' => $this->administrado->nombre_completo,
            'codigo_verificacion' => $this->codigo_verificacion,
            'recorrido' => $this->burbujas()->map(fn ($b) => [
                'paso' => $b['numero'],
                'titulo' => $b['titulo'],
                'area' => $b['area'],
                'fecha' => $b['fecha']?->toIso8601String(),
                'estado' => $b['estado'],
            ])->values(),
        ];
    }
}

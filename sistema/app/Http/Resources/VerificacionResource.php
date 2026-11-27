<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** CU19 por API: resultado de Verificacion::buscar(). */
class VerificacionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'autentico' => true,
            'codigo' => $this->resource['codigo'],
            'tipo' => $this->resource['tipo'],
            'expediente' => $this->resource['expediente']->numero,
            'fecha' => $this->resource['fecha']?->toIso8601String(),
            'firmante' => $this->resource['firmante'],
            'documentos' => collect($this->resource['documentos'])->map(fn ($d) => [
                'nombre' => $d->nombre_archivo,
                'tamano_kb' => $d->tamano_kb,
                'hash_sha256' => $d->hash_sha256,
            ])->values(),
        ];
    }
}

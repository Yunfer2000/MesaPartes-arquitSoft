<?php

namespace App\Services;

use App\Models\Expediente;

/**
 * CU07 Consultar seguimiento: busca el expediente por N.° (o código propio) y documento de identidad.
 * Lo usan la pantalla pública (Blade) y la API REST v1, así la regla vive en un solo lugar.
 */
class ConsultaExpediente
{
    public function buscar(string $numero, string $documento): ?Expediente
    {
        // N.° de expediente o código propio; ambos se guardan en mayúsculas
        $codigo = mb_strtoupper(trim($numero));

        return Expediente::where(fn ($q) => $q->where('numero', $codigo)->orWhere('codigo_propio', $codigo))
            ->whereHas('administrado', fn ($q) => $q->where('numero_documento', trim($documento)))
            ->first();
    }
}

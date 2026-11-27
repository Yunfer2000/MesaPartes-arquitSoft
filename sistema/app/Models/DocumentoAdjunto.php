<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DocumentoAdjunto extends Model
{
    protected $table = 'documentos_adjuntos';

    protected $fillable = ['expediente_id', 'subsanacion_id', 'respuesta_id', 'nombre_archivo', 'ruta',
        'tipo_mime', 'tamano_kb', 'hash_sha256', 'es_principal', 'fecha_carga'];

    protected $casts = [
        'es_principal' => 'boolean',
        'fecha_carga' => 'datetime',
    ];

    public function expediente(): BelongsTo
    {
        return $this->belongsTo(Expediente::class);
    }
}

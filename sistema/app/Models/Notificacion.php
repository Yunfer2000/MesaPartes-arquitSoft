<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Notificacion extends Model
{
    protected $table = 'notificaciones';

    protected $fillable = ['administrado_id', 'expediente_id', 'evento', 'mensaje', 'fecha', 'enviada', 'leida'];

    protected $casts = [
        'fecha' => 'datetime',
        'enviada' => 'boolean',
        'leida' => 'boolean',
    ];

    public function administrado(): BelongsTo
    {
        return $this->belongsTo(Administrado::class);
    }

    public function expediente(): BelongsTo
    {
        return $this->belongsTo(Expediente::class);
    }
}

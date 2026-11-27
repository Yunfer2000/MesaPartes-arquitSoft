<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Copia "para conocimiento" de un expediente derivado (derivación de uno a muchos). */
class Copia extends Model
{
    protected $table = 'copias';

    protected $fillable = ['expediente_id', 'movimiento_id', 'area_id', 'usuario_id', 'proveido', 'fecha_hora',
        'leido', 'fecha_lectura', 'leido_por_id'];

    protected $casts = [
        'fecha_hora' => 'datetime',
        'fecha_lectura' => 'datetime',
        'leido' => 'boolean',
    ];

    public function expediente(): BelongsTo
    {
        return $this->belongsTo(Expediente::class);
    }

    public function movimiento(): BelongsTo
    {
        return $this->belongsTo(Movimiento::class);
    }

    public function area(): BelongsTo
    {
        return $this->belongsTo(Area::class);
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(Usuario::class);
    }

    public function leidoPor(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'leido_por_id');
    }
}

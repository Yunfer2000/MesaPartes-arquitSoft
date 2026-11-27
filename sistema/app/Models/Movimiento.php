<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Movimiento extends Model
{
    protected $table = 'movimientos';

    protected $fillable = ['expediente_id', 'area_origen_id', 'area_destino_id', 'usuario_id', 'usuario_destino_id',
        'accion', 'proveido', 'fecha_hora', 'confirmado', 'fecha_confirmacion'];

    protected $casts = [
        'fecha_hora' => 'datetime',
        'fecha_confirmacion' => 'datetime',
        'confirmado' => 'boolean',
    ];

    public function expediente(): BelongsTo
    {
        return $this->belongsTo(Expediente::class);
    }

    public function areaOrigen(): BelongsTo
    {
        return $this->belongsTo(Area::class, 'area_origen_id');
    }

    public function areaDestino(): BelongsTo
    {
        return $this->belongsTo(Area::class, 'area_destino_id');
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(Usuario::class);
    }

    public function usuarioDestino(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'usuario_destino_id');
    }
}

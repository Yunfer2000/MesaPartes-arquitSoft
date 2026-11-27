<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RegistroAuditoria extends Model
{
    protected $table = 'registros_auditoria';

    protected $fillable = ['usuario_id', 'accion', 'tabla', 'registro_id', 'detalle', 'ip', 'fecha_hora'];

    protected $casts = ['fecha_hora' => 'datetime'];

    public static function registrar(string $accion, Model $registro, ?string $detalle = null): self
    {
        return static::create([
            'usuario_id' => auth()->id(),
            'accion' => $accion,
            'tabla' => $registro->getTable(),
            'registro_id' => $registro->getKey(),
            'detalle' => $detalle,
            'ip' => request()->ip(),
            'fecha_hora' => now(),
        ]);
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(Usuario::class);
    }
}

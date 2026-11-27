<?php

namespace App\Models;

use App\Services\Verificacion;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Respuesta extends Model
{
    /** RF26: cada respuesta nace con su código de verificación (firma electrónica del sistema). */
    protected static function booted(): void
    {
        static::creating(fn (Respuesta $r) => $r->codigo_verificacion ??= Verificacion::nuevoCodigo());
    }

    protected $table = 'respuestas';

    protected $fillable = ['expediente_id', 'usuario_id', 'contenido', 'fecha'];

    protected $casts = ['fecha' => 'datetime'];

    public function expediente(): BelongsTo
    {
        return $this->belongsTo(Expediente::class);
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(Usuario::class);
    }

    public function documentos(): HasMany
    {
        return $this->hasMany(DocumentoAdjunto::class);
    }
}

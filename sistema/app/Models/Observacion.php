<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Observacion extends Model
{
    protected $table = 'observaciones';

    protected $fillable = ['expediente_id', 'usuario_id', 'motivo', 'detalle', 'fecha'];

    protected $casts = ['fecha' => 'datetime'];

    public function expediente(): BelongsTo
    {
        return $this->belongsTo(Expediente::class);
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(Usuario::class);
    }

    public function subsanacion(): HasOne
    {
        return $this->hasOne(Subsanacion::class);
    }

    /** CU07 A3: días desde la observación, con la misma regla que el semáforo (config/plazos.php). */
    public function diasTranscurridos(): int
    {
        return (int) (config('plazos.habiles') ? $this->fecha->diffInWeekdays(now()) : $this->fecha->diffInDays(now()));
    }
}

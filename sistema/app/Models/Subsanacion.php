<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Subsanacion extends Model
{
    protected $table = 'subsanaciones';

    protected $fillable = ['observacion_id', 'descripcion', 'fecha'];

    protected $casts = ['fecha' => 'datetime'];

    public function observacion(): BelongsTo
    {
        return $this->belongsTo(Observacion::class);
    }

    public function documentos(): HasMany
    {
        return $this->hasMany(DocumentoAdjunto::class);
    }
}

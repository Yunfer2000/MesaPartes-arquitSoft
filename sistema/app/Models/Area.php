<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Area extends Model
{
    public const MESA_DE_PARTES = 'MP';

    protected $table = 'areas';

    protected $fillable = ['nombre', 'sigla', 'area_padre_id', 'activa', 'responsable_id'];

    protected $casts = ['activa' => 'boolean'];

    public static function mesaDePartes(): self
    {
        return static::where('sigla', self::MESA_DE_PARTES)->firstOrFail();
    }

    public function responsable(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'responsable_id');
    }

    /** Unidad de la que depende según el organigrama (null = Dirección General o sin asignar). */
    public function padre(): BelongsTo
    {
        return $this->belongsTo(self::class, 'area_padre_id');
    }

    public function hijas(): HasMany
    {
        return $this->hasMany(self::class, 'area_padre_id');
    }

    public function usuarios(): HasMany
    {
        return $this->hasMany(Usuario::class);
    }

    public function expedientes(): HasMany
    {
        return $this->hasMany(Expediente::class, 'area_destino_id');
    }
}

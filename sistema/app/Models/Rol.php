<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Rol extends Model
{
    public const MESA_DE_PARTES = 'Mesa de Partes';
    public const AREA = 'Área';
    public const ADMINISTRADOR = 'Administrador';

    protected $table = 'roles';

    protected $fillable = ['nombre', 'descripcion'];

    public function usuarios(): HasMany
    {
        return $this->hasMany(Usuario::class);
    }
}

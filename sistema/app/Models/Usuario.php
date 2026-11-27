<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class Usuario extends Authenticatable
{
    use HasApiTokens, Notifiable;

    protected $table = 'usuarios';

    protected $fillable = ['rol_id', 'area_id', 'nombres', 'apellidos', 'correo', 'contrasena', 'activo', 'fecha_registro'];

    protected $hidden = ['contrasena', 'remember_token'];

    protected $casts = [
        'contrasena' => 'hashed',
        'activo' => 'boolean',
        'fecha_registro' => 'datetime',
    ];

    public function getAuthPasswordName(): string
    {
        return 'contrasena';
    }

    public function routeNotificationForMail(): string
    {
        return $this->correo;
    }

    public function rol(): BelongsTo
    {
        return $this->belongsTo(Rol::class);
    }

    public function area(): BelongsTo
    {
        return $this->belongsTo(Area::class);
    }

    public function administrado(): HasOne
    {
        return $this->hasOne(Administrado::class);
    }

    public function tieneRol(string ...$roles): bool
    {
        return in_array($this->rol->nombre, $roles, true);
    }

    public function getNombreCompletoAttribute(): string
    {
        return "{$this->nombres} {$this->apellidos}";
    }

    /** RF25: el Administrador y la Dirección General ven el panel de pendientes de todas las áreas. */
    public function vePendientes(): bool
    {
        return $this->tieneRol(Rol::ADMINISTRADOR) || ($this->tieneRol(Rol::AREA) && $this->area?->sigla === 'DG');
    }
}

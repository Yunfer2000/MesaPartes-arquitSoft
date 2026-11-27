<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Administrado extends Model
{
    public const TIPOS = ['Estudiante', 'Egresado', 'Docente', 'Personal', 'Ciudadano'];
    public const DOCUMENTOS_IDENTIDAD = ['DNI', 'Carné de extranjería', 'Pasaporte', 'RUC'];

    /** Formulario público: tipo de persona y documentos que puede usar una persona natural. */
    public const TIPOS_PERSONA = ['natural' => 'Natural', 'juridica' => 'Jurídica'];
    public const DOCUMENTOS_PERSONA_NATURAL = ['DNI', 'Carné de extranjería'];
    /** Registro presencial (CU17): el personal ve el documento físico, así que también acepta pasaporte. */
    public const DOCUMENTOS_PRESENCIAL = ['DNI', 'Carné de extranjería', 'Pasaporte'];

    /** Formato de cada documento de identidad (DNI de 8 dígitos, RUC de 11). */
    public const FORMATOS_DOCUMENTO = [
        'DNI' => '/^\d{8}$/',
        'Carné de extranjería' => '/^[A-Za-z0-9]{6,12}$/',
        'Pasaporte' => '/^[A-Za-z0-9]{6,12}$/',
        'RUC' => '/^(10|15|17|20)\d{9}$/',
    ];

    protected $table = 'administrados';

    protected $fillable = ['usuario_id', 'tipo_doc_identidad', 'numero_documento', 'nombres', 'apellido_paterno', 'apellido_materno',
        'razon_social', 'tipo_administrado', 'correo', 'telefono', 'direccion'];

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(Usuario::class);
    }

    public function expedientes(): HasMany
    {
        return $this->hasMany(Expediente::class);
    }

    public function notificaciones(): HasMany
    {
        return $this->hasMany(Notificacion::class);
    }

    /** Apellidos para mostrar ("Quispe Huamán"). No se guarda: se arma con paterno y materno. */
    public function getApellidosAttribute(): string
    {
        return trim("{$this->apellido_paterno} {$this->apellido_materno}");
    }

    /** Persona jurídica: se identifica con RUC y razón social. */
    public function esJuridica(): bool
    {
        return $this->tipo_doc_identidad === 'RUC';
    }

    public function getNombreCompletoAttribute(): string
    {
        $nombre = trim("{$this->nombres} {$this->apellidos}");

        // Las entidades del formulario público no tienen nombres propios (el representante va en el expediente)
        return $this->razon_social ? ($nombre !== '' ? "{$this->razon_social} ({$nombre})" : $this->razon_social) : $nombre;
    }

    /** Muestra solo el inicio y el final de un dato de contacto: jo***@gmail.com, 96*****56. */
    public static function ocultar(?string $dato): ?string
    {
        if (! $dato) {
            return null;
        }
        if (str_contains($dato, '@')) {
            [$usuario, $dominio] = explode('@', $dato, 2);

            return mb_substr($usuario, 0, 2).'***@'.$dominio;
        }

        return mb_substr($dato, 0, 2).str_repeat('*', max(0, mb_strlen($dato) - 4)).mb_substr($dato, -2);
    }
}

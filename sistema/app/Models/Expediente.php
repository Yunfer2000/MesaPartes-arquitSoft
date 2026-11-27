<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use App\Services\Verificacion;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class Expediente extends Model
{
    public const PRESENTADO = 'Presentado';
    public const RECIBIDO = 'Recibido';
    public const OBSERVADO = 'Observado';
    public const SUBSANADO = 'Subsanado';
    public const DERIVADO = 'Derivado';
    public const EN_ATENCION = 'En atención';
    public const FINALIZADO = 'Finalizado';

    public const ESTADOS = [self::PRESENTADO, self::RECIBIDO, self::OBSERVADO, self::SUBSANADO,
        self::DERIVADO, self::EN_ATENCION, self::FINALIZADO];

    /** RF26: cada constancia nace con su código de verificación (firma electrónica del sistema). */
    protected static function booted(): void
    {
        static::creating(fn (Expediente $e) => $e->codigo_verificacion ??= Verificacion::nuevoCodigo());
    }

    protected $table = 'expedientes';

    protected $fillable = ['administrado_id', 'tipo_documento_id', 'area_destino_id', 'numero', 'codigo_propio', 'asunto', 'detalle',
        'folios', 'modalidad', 'fecha_presentacion', 'fecha_recepcion', 'estado',
        'representante_dni', 'representante_nombres', 'representante_apellido_paterno',
        'representante_apellido_materno', 'representante_celular', 'representante_correo'];

    protected $casts = [
        'fecha_presentacion' => 'datetime',
        'fecha_recepcion' => 'datetime',
    ];

    /** Número correlativo por año: EXP-2026-000001. */
    public static function generarNumero(): string
    {
        $anio = now()->year;
        $ultimo = DB::table('expedientes')->where('numero', 'like', "EXP-{$anio}-%")->lockForUpdate()->max('numero');
        $siguiente = $ultimo ? ((int) substr($ultimo, -6)) + 1 : 1;

        return sprintf('EXP-%d-%06d', $anio, $siguiente);
    }

    /** Expedientes cuyo último movimiento los dejó en el área indicada (ubicación actual). */
    public function scopeEnArea(Builder $query, int $areaId): Builder
    {
        return $query->whereIn('expedientes.id', Movimiento::query()
            ->select('expediente_id')
            ->where('area_destino_id', $areaId)
            ->whereIn('id', Movimiento::query()->selectRaw('max(id)')->groupBy('expediente_id')));
    }

    /** Área donde se encuentra ahora el expediente. */
    public function areaActual(): ?Area
    {
        return $this->ultimoMovimiento()?->areaDestino;
    }

    /** Persona jurídica: el expediente guarda los datos del representante que lo presentó. */
    public function tieneRepresentante(): bool
    {
        return $this->representante_dni !== null;
    }

    /** Nombre del representante para mostrar: "Rosa Huamaní Cárdenas". */
    public function getRepresentanteNombreAttribute(): ?string
    {
        if (! $this->tieneRepresentante()) {
            return null;
        }

        return trim("{$this->representante_nombres} {$this->representante_apellido_paterno} {$this->representante_apellido_materno}");
    }

    /** Correo al que se avisa: el del representante en persona jurídica, si no el del administrado. */
    public function correoDeAviso(): ?string
    {
        return $this->tieneRepresentante() ? $this->representante_correo : $this->administrado->correo;
    }

    public function administrado(): BelongsTo
    {
        return $this->belongsTo(Administrado::class);
    }

    public function tipoDocumento(): BelongsTo
    {
        return $this->belongsTo(TipoDocumento::class);
    }

    public function areaDestino(): BelongsTo
    {
        return $this->belongsTo(Area::class, 'area_destino_id');
    }

    public function documentos(): HasMany
    {
        return $this->hasMany(DocumentoAdjunto::class);
    }

    public function movimientos(): HasMany
    {
        return $this->hasMany(Movimiento::class)->orderBy('fecha_hora')->orderBy('id');
    }

    public function observaciones(): HasMany
    {
        return $this->hasMany(Observacion::class)->latest('fecha');
    }

    public function respuestas(): HasMany
    {
        return $this->hasMany(Respuesta::class);
    }

    public function notificaciones(): HasMany
    {
        return $this->hasMany(Notificacion::class);
    }

    /** Copias para conocimiento enviadas al derivar (derivación de uno a muchos). */
    public function copias(): HasMany
    {
        return $this->hasMany(Copia::class)->orderBy('fecha_hora')->orderBy('id');
    }

    public function ultimoMovimiento(): ?Movimiento
    {
        return $this->movimientos()->reorder()->latest('fecha_hora')->latest('id')->first();
    }

    /**
     * CU07: burbujas del seguimiento público, una por cada movimiento real del expediente, en orden.
     * La confirmación del área (CU11) no crea movimiento: se muestra como la burbuja "En atención".
     * Mientras no esté finalizado, se agrega al final la burbuja "Respuesta pendiente".
     * estado: completado (verde), actual (azul con borde dorado), observado (rojo) o pendiente (gris punteado).
     *
     * @return Collection<int, array{numero: int, accion: string, titulo: string, area: ?string, fecha: ?Carbon, actual: bool, estado: string}>
     */
    public function burbujas(): Collection
    {
        $titulos = ['presentado' => 'Presentado', 'recibido' => 'Recibido', 'observado' => 'Observado',
            'subsanado' => 'Subsanado', 'derivado' => 'Derivado', 'confirmado' => 'En atención',
            'avance' => 'Avance', 'finalizado' => 'Finalizado'];

        $burbujas = collect();
        foreach ($this->movimientos as $m) {
            $burbujas->push(['accion' => $m->accion, 'area' => $m->areaDestino?->nombre, 'fecha' => $m->fecha_hora]);
            if ($m->accion === 'derivado' && $m->confirmado) {
                $burbujas->push(['accion' => 'confirmado', 'area' => $m->areaDestino?->nombre, 'fecha' => $m->fecha_confirmacion]);
            }
        }

        $finalizado = $this->estado === self::FINALIZADO;
        $ultima = $burbujas->count() - 1;

        $burbujas = $burbujas->values()->map(function ($b, $i) use ($titulos, $finalizado, $ultima) {
            // Un expediente finalizado ya no tiene etapa en curso
            $actual = $i === $ultima && ! $finalizado;

            return $b + [
                'numero' => $i + 1,
                'titulo' => $titulos[$b['accion']] ?? ucfirst($b['accion']),
                'actual' => $actual,
                'estado' => match (true) {
                    $b['accion'] === 'observado' => 'observado',
                    $actual => 'actual',
                    default => 'completado',
                },
            ];
        });

        if (! $finalizado) {
            $burbujas->push(['accion' => 'pendiente', 'titulo' => 'Respuesta pendiente', 'area' => null, 'fecha' => null,
                'numero' => $burbujas->count() + 1, 'actual' => false, 'estado' => 'pendiente']);
        }

        return $burbujas;
    }

    /** RF24: días que lleva el expediente en el área donde está ahora. */
    public function diasEnArea(): int
    {
        $ultimo = $this->relationLoaded('movimientos') ? $this->movimientos->last() : $this->ultimoMovimiento();
        $desde = $ultimo?->fecha_hora ?? $this->fecha_presentacion;

        return (int) (config('plazos.habiles') ? $desde->diffInWeekdays(now()) : $desde->diffInDays(now()));
    }

    /** RF24: color del semáforo (verde, ambar o rojo). Sin color si ya terminó o espera al administrado. */
    public function semaforo(): ?string
    {
        if (in_array($this->estado, [self::FINALIZADO, self::OBSERVADO], true)) {
            return null;
        }

        $dias = $this->diasEnArea();

        return match (true) {
            $dias >= config('plazos.rojo') => 'rojo',
            $dias >= config('plazos.ambar') => 'ambar',
            default => 'verde',
        };
    }
}

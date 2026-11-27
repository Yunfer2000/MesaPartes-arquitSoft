<?php

namespace App\Services;

use App\Exceptions\ReglaNegocio;
use App\Models\Administrado;
use App\Models\Area;
use App\Models\Copia;
use App\Models\Expediente;
use App\Models\Movimiento;
use App\Models\Observacion;
use App\Models\Respuesta;
use App\Models\Usuario;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

/**
 * Operaciones sobre un expediente. Cada método sigue el diagrama de
 * secuencia del caso de uso indicado (Figuras 44 a 50 y 53).
 */
class FlujoExpediente
{
    public function __construct(private Archivos $archivos, private Notificador $notificador)
    {
    }

    /**
     * CU05 Presentar trámite virtual: con cuenta ($usuario) o desde el formulario público sin cuenta.
     *
     * @param  UploadedFile[]  $anexos
     */
    public function presentar(Administrado $administrado, ?Usuario $usuario, array $datos, UploadedFile $principal, array $anexos = []): Expediente
    {
        $expediente = DB::transaction(function () use ($administrado, $usuario, $datos, $principal, $anexos) {
            // 6. Genera el número y registra la fecha y hora de presentación
            $expediente = Expediente::create([
                'administrado_id' => $administrado->id,
                'tipo_documento_id' => $datos['tipo_documento_id'] ?? null,
                'area_destino_id' => $datos['area_destino_id'],
                'numero' => Expediente::generarNumero(),
                'codigo_propio' => $datos['codigo_propio'] ?? null,
                'asunto' => $datos['asunto'],
                'detalle' => $datos['detalle'] ?? null,
                'folios' => $datos['folios'] ?? null,
                'modalidad' => 'Virtual',
                'fecha_presentacion' => now(),
                'estado' => Expediente::PRESENTADO,
                // Persona jurídica: quien presenta en nombre de la entidad (puede cambiar en cada trámite)
                'representante_dni' => $datos['representante_dni'] ?? null,
                'representante_nombres' => $datos['representante_nombres'] ?? null,
                'representante_apellido_paterno' => $datos['representante_apellido_paterno'] ?? null,
                'representante_apellido_materno' => $datos['representante_apellido_materno'] ?? null,
                'representante_celular' => $datos['representante_celular'] ?? null,
                'representante_correo' => $datos['representante_correo'] ?? null,
            ]);

            $this->archivos->guardar($expediente, $principal, true);
            foreach ($anexos as $anexo) {
                $this->archivos->guardar($expediente, $anexo);
            }

            // 7. Primer movimiento: el expediente llega a Mesa de Partes
            Movimiento::create([
                'expediente_id' => $expediente->id,
                'area_destino_id' => Area::mesaDePartes()->id,
                'usuario_id' => $usuario?->id,
                'accion' => 'presentado',
                'fecha_hora' => $expediente->fecha_presentacion,
            ]);

            return $expediente;
        });

        // CU16: aviso con el código del expediente al correo del remitente
        $this->notificador->notificar($expediente, 'presentación');

        return $expediente;
    }

    /**
     * CU06 Subsanar observación.
     *
     * @param  UploadedFile[]  $archivos
     */
    public function subsanar(Expediente $expediente, ?Usuario $usuario, string $descripcion, array $archivos = []): void
    {
        $observacion = $this->observacionPendiente($expediente);
        if (! $observacion) {
            throw new ReglaNegocio('El expediente no tiene observaciones pendientes.');
        }

        DB::transaction(function () use ($expediente, $usuario, $descripcion, $archivos, $observacion) {
            $subsanacion = $observacion->subsanacion()->create(['descripcion' => $descripcion, 'fecha' => now()]);
            foreach ($archivos as $archivo) {
                $this->archivos->guardar($expediente, $archivo, false, ['subsanacion_id' => $subsanacion->id]);
            }

            Movimiento::create([
                'expediente_id' => $expediente->id,
                'area_destino_id' => Area::mesaDePartes()->id,
                'usuario_id' => $usuario?->id,
                'accion' => 'subsanado',
                'fecha_hora' => now(),
            ]);
            $expediente->update(['estado' => Expediente::SUBSANADO]);
        });
    }

    public function observacionPendiente(Expediente $expediente): ?Observacion
    {
        if ($expediente->estado !== Expediente::OBSERVADO) {
            return null;
        }

        return $expediente->observaciones()->whereDoesntHave('subsanacion')->first();
    }

    /** CU08 Recepcionar expediente: Mesa de Partes completa tipo, folios y N.° de documento. */
    public function recepcionar(Expediente $expediente, Usuario $operador, array $datos = []): void
    {
        DB::transaction(function () use (&$expediente, $operador, $datos) {
            // Se vuelve a leer el expediente y se bloquea su fila hasta terminar: si dos operadores
            // pulsan a la vez, el segundo espera y ya ve el estado nuevo.
            $expediente = Expediente::lockForUpdate()->findOrFail($expediente->id);
            $this->exigirEstado($expediente, [Expediente::PRESENTADO, Expediente::SUBSANADO]);

            // forceFill: nro_documento no está en $fillable del modelo (así no chocamos con el Bloque 1)
            $expediente->forceFill([
                'estado' => Expediente::RECIBIDO,
                'fecha_recepcion' => $expediente->fecha_recepcion ?? now(),
                'tipo_documento_id' => $datos['tipo_documento_id'] ?? $expediente->tipo_documento_id,
                'folios' => $datos['folios'] ?? $expediente->folios,
                'nro_documento' => $datos['nro_documento'] ?? $expediente->nro_documento,
            ])->save();
            $this->mover($expediente, $operador, 'recibido', Area::mesaDePartes());
        });

        $this->notificador->notificar($expediente, 'recepción');
    }

    /** CU09 Observar expediente. */
    public function observar(Expediente $expediente, Usuario $operador, string $motivo, ?string $detalle): Observacion
    {
        $observacion = DB::transaction(function () use (&$expediente, $operador, $motivo, $detalle) {
            $expediente = Expediente::lockForUpdate()->findOrFail($expediente->id);
            $this->exigirEstado($expediente, [Expediente::PRESENTADO, Expediente::SUBSANADO, Expediente::RECIBIDO]);

            $observacion = $expediente->observaciones()->create([
                'usuario_id' => $operador->id, 'motivo' => $motivo, 'detalle' => $detalle, 'fecha' => now(),
            ]);
            $expediente->update(['estado' => Expediente::OBSERVADO]);
            $this->mover($expediente, $operador, 'observado', Area::mesaDePartes(), $motivo);

            return $observacion;
        });

        $this->notificador->notificar($expediente, 'observación');

        return $observacion;
    }

    /**
     * CU10 Derivar expediente (Mesa de Partes o el área que lo tiene en atención).
     * De uno a uno: va solo a $destino, que lo atiende. De uno a muchos: además se envía copia
     * para conocimiento a las áreas de $copias (no lo atienden, solo lo leen).
     *
     * @param  Area[]  $copias
     */
    public function derivar(Expediente $expediente, Usuario $usuario, Area $destino, ?Usuario $destinatario, ?string $proveido, array $copias = []): void
    {
        DB::transaction(function () use ($expediente, $usuario, $destino, $destinatario, $proveido, $copias) {
            // Se bloquea la fila: si dos personas derivan a la vez, la segunda espera y ya ve el estado nuevo
            $expediente = Expediente::lockForUpdate()->findOrFail($expediente->id);
            $this->exigirEstado($expediente, [Expediente::RECIBIDO, Expediente::EN_ATENCION]);
            $origen = $expediente->areaActual();

            if ($destino->id === $origen?->id) {
                throw new ReglaNegocio('No puedes derivar el expediente a tu propia área.'); // 3a
            }
            if (! $destino->activa) {
                throw new ReglaNegocio('El área destino no está activa.');
            }
            if ($destinatario && $destinatario->area_id !== $destino->id) {
                throw new ReglaNegocio('El usuario elegido no pertenece al área destino.');
            }
            foreach ($copias as $area) {
                if ($area->id === $destino->id || $area->id === $origen?->id) {
                    throw new ReglaNegocio("{$area->nombre} no puede recibir copia: ya es el área de origen o de destino.");
                }
                if ($area->sigla === Area::MESA_DE_PARTES) {
                    throw new ReglaNegocio('Mesa de Partes no recibe copias para conocimiento.');
                }
                if (! $area->activa) {
                    throw new ReglaNegocio("El área {$area->nombre} no está activa.");
                }
            }

            $movimiento = Movimiento::create([
                'expediente_id' => $expediente->id,
                'area_origen_id' => $origen?->id,
                'area_destino_id' => $destino->id,
                'usuario_id' => $usuario->id,
                'usuario_destino_id' => $destinatario?->id,
                'accion' => 'derivado',
                'proveido' => $proveido,
                'fecha_hora' => now(),
            ]);
            foreach ($copias as $area) {
                Copia::create([
                    'expediente_id' => $expediente->id,
                    'movimiento_id' => $movimiento->id,
                    'area_id' => $area->id,
                    'usuario_id' => $usuario->id,
                    'proveido' => $proveido,
                    'fecha_hora' => $movimiento->fecha_hora,
                ]);
            }
            $expediente->update(['estado' => Expediente::DERIVADO]);
        });

        $expediente->refresh();
        $this->notificador->notificar($expediente, 'derivación', ['area' => $destino->nombre]);
    }

    /** CU11 Confirmar recepción en área. */
    public function confirmarRecepcion(Expediente $expediente, Usuario $responsable): void
    {
        DB::transaction(function () use ($expediente) {
            // Mismo bloqueo que al derivar: nadie más cambia el expediente mientras se confirma
            $expediente = Expediente::lockForUpdate()->findOrFail($expediente->id);
            $this->exigirEstado($expediente, [Expediente::DERIVADO]);

            $expediente->ultimoMovimiento()->update(['confirmado' => true, 'fecha_confirmacion' => now()]);
            $expediente->update(['estado' => Expediente::EN_ATENCION]);
        });

        $expediente->refresh();
    }

    /** Derivación de uno a muchos: el área que recibió la copia marca que ya la leyó. */
    public function tomarConocimiento(Copia $copia, Usuario $usuario): void
    {
        DB::transaction(function () use ($copia, $usuario) {
            $copia = Copia::lockForUpdate()->findOrFail($copia->id);
            if ($copia->leido) {
                throw new ReglaNegocio('Esta copia ya estaba marcada como leída.');
            }
            $copia->update(['leido' => true, 'fecha_lectura' => now(), 'leido_por_id' => $usuario->id]);
        });
    }

    /** CU12 Actualizar estado del expediente (registrar avance). */
    public function registrarAvance(Expediente $expediente, Usuario $responsable, string $nota): void
    {
        $this->exigirEstado($expediente, [Expediente::EN_ATENCION]);
        $this->mover($expediente, $responsable, 'avance', $responsable->area, $nota);
    }

    /** CU13 Registrar respuesta. */
    public function responder(Expediente $expediente, Usuario $responsable, string $contenido, ?UploadedFile $documento = null): Respuesta
    {
        $this->exigirEstado($expediente, [Expediente::EN_ATENCION]);

        $respuesta = DB::transaction(function () use ($expediente, $responsable, $contenido, $documento) {
            $respuesta = $expediente->respuestas()->create([
                'usuario_id' => $responsable->id, 'contenido' => $contenido, 'fecha' => now(),
            ]);
            if ($documento) { // Mejora: el documento de respuesta es opcional
                $this->archivos->guardar($expediente, $documento, false, ['respuesta_id' => $respuesta->id]);
            }
            $expediente->update(['estado' => Expediente::FINALIZADO]);
            $this->mover($expediente, $responsable, 'finalizado', $responsable->area);

            return $respuesta;
        });

        $this->notificador->notificar($expediente, 'respuesta');

        return $respuesta;
    }

    /**
     * CU17 Registrar expediente presencial: entra directamente como Recibido. El escaneo es opcional.
     * Mejora: se guarda el N.° de expediente que Mesa anotó o selló a mano en el papel (codigo_propio, en mayúsculas).
     * Al terminar se avisa al correo del solicitante (o del representante, en persona jurídica).
     */
    public function registrarPresencial(Administrado $administrado, Usuario $operador, array $datos, ?UploadedFile $escaneado = null): Expediente
    {
        $expediente = DB::transaction(function () use ($administrado, $operador, $datos, $escaneado) {
            $ahora = now();
            $expediente = Expediente::forceCreate([
                'administrado_id' => $administrado->id,
                'tipo_documento_id' => $datos['tipo_documento_id'],
                'area_destino_id' => $datos['area_destino_id'],
                'numero' => Expediente::generarNumero(),
                'asunto' => $datos['asunto'],
                'detalle' => $datos['detalle'] ?? null,
                'folios' => $datos['folios'] ?? null,
                'nro_documento' => $datos['nro_documento'] ?? null,
                'codigo_propio' => isset($datos['codigo_propio']) ? mb_strtoupper($datos['codigo_propio']) : null,
                'modalidad' => 'Presencial',
                'fecha_presentacion' => $ahora,
                'fecha_recepcion' => $ahora,
                'estado' => Expediente::RECIBIDO,
                // Persona jurídica: quien presenta en nombre de la entidad (puede cambiar en cada trámite)
                'representante_dni' => $datos['representante_dni'] ?? null,
                'representante_nombres' => $datos['representante_nombres'] ?? null,
                'representante_apellido_paterno' => $datos['representante_apellido_paterno'] ?? null,
                'representante_apellido_materno' => $datos['representante_apellido_materno'] ?? null,
                'representante_celular' => $datos['representante_celular'] ?? null,
                'representante_correo' => $datos['representante_correo'] ?? null,
            ]);
            if ($escaneado) {
                $this->archivos->guardar($expediente, $escaneado, true);
            }
            // El movimiento guarda al operador de Mesa de Partes que registró el expediente
            $this->mover($expediente, $operador, 'recibido', Area::mesaDePartes());

            return $expediente;
        });

        // CU16: se avisa solo después de guardar; si el envío falla, el expediente queda registrado igual
        $this->notificador->notificar($expediente, 'registro presencial');

        return $expediente;
    }

    private function mover(Expediente $expediente, Usuario $usuario, string $accion, Area $area, ?string $proveido = null): Movimiento
    {
        return Movimiento::create([
            'expediente_id' => $expediente->id,
            'area_origen_id' => $area->id,
            'area_destino_id' => $area->id,
            'usuario_id' => $usuario->id,
            'accion' => $accion,
            'proveido' => $proveido,
            'fecha_hora' => now(),
        ]);
    }

    private function exigirEstado(Expediente $expediente, array $estados): void
    {
        if (! in_array($expediente->estado, $estados, true)) {
            throw new ReglaNegocio("Esta acción no se puede realizar con el expediente en estado {$expediente->estado}.");
        }
    }
}

<?php

namespace App\Services;

use App\Models\Expediente;
use App\Models\Notificacion;
use App\Notifications\AvisoExpediente;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Throwable;

/** CU16 Notificar al administrado (Figura 52). */
class Notificador
{
    private const MENSAJES = [
        'presentación' => 'Recibimos tu trámite. Tu código de expediente es :numero. Guárdalo para consultar el seguimiento.',
        'registro presencial' => 'Tu trámite fue registrado presencialmente en Mesa de Partes con el expediente :numero. Guarda este número para consultar su estado en la Mesa de Partes Virtual.',
        'recepción' => 'Tu expediente :numero fue recepcionado por Mesa de Partes.',
        'observación' => 'Tu expediente :numero fue observado. Consúltalo en la Mesa de Partes Virtual para ver qué debes subsanar.',
        'derivación' => 'Tu expediente :numero fue derivado a :area.',
        'respuesta' => 'Tu expediente :numero tiene respuesta. Consúltala en la Mesa de Partes Virtual.',
    ];

    public function notificar(Expediente $expediente, string $evento, array $datos = []): Notificacion
    {
        // 1. Arma el mensaje según el evento
        $mensaje = strtr(self::MENSAJES[$evento], [':numero' => $expediente->numero] + collect($datos)
            ->mapWithKeys(fn ($v, $k) => [":$k" => $v])->all());

        // 2. Guarda la notificación (visible en Mis trámites)
        $notificacion = Notificacion::create([
            'administrado_id' => $expediente->administrado_id,
            'expediente_id' => $expediente->id,
            'evento' => $evento,
            'mensaje' => $mensaje,
            'fecha' => now(),
            'enviada' => false,
        ]);

        // 3. Envía el correo (en persona jurídica, al representante de este expediente).
        // 3a: sin correo o si el envío falla, queda como no enviada.
        $correo = $expediente->correoDeAviso();
        if ($correo) {
            try {
                Notification::route('mail', $correo)->notify(new AvisoExpediente($expediente, $mensaje));
                $notificacion->update(['enviada' => true]);
            } catch (Throwable $e) {
                Log::warning("No se pudo enviar la notificación {$notificacion->id}: {$e->getMessage()}");
            }
        }

        return $notificacion;
    }
}

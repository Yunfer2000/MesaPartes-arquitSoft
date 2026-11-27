<?php

namespace App\Notifications;

use App\Models\Expediente;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AvisoExpediente extends Notification
{
    public function __construct(private Expediente $expediente, private string $mensaje)
    {
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Expediente {$this->expediente->numero} · Mesa de Partes Virtual")
            ->greeting('Hola, '.($this->expediente->tieneRepresentante()
                ? $this->expediente->representante_nombres
                : $this->expediente->administrado->nombres))
            ->line($this->mensaje)
            ->action('Consultar mi expediente', route('externo.consulta'))
            // La persona jurídica consulta con el RUC de la entidad, no con el DNI del representante
            ->line($this->expediente->tieneRepresentante()
                ? 'Para verlo, ingresa el número de expediente y el RUC de la entidad.'
                : 'Para verlo, ingresa tu número de expediente y tu documento de identidad.')
            ->salutation('Mesa de Partes · IESTP Manuel Antonio Hierro Pozo');
    }
}

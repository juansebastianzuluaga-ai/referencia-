<?php

namespace App\Notifications;

use App\Models\ExternalClinic;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ExternalClinicRejected extends Notification
{

    public function __construct(
        public ExternalClinic $clinic,
    ) {
    }

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $registerUrl = config('app.url').'/registro-clinica';

        $mail = (new MailMessage)
            ->subject('Actualización sobre su solicitud - Clínica Santa Bárbara')
            ->greeting('Hola '.$this->clinic->business_name.',')
            ->line('Lamentamos informarle que su solicitud de registro como clínica externa no ha sido aprobada en esta ocasión.')
            ->line('')
            ->line('Motivo del rechazo:')
            ->line($this->clinic->rejection_reason)
            ->line('')
            ->line('Si considera que se trata de un error o si puede corregir la información, puede realizar una nueva solicitud.');

        return $mail
            ->action('Realizar nueva solicitud', $registerUrl)
            ->salutation('Saludos, Equipo de Sistemas - Clínica Santa Bárbara');
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'clinic_id' => $this->clinic->id,
            'nit' => $this->clinic->nit,
            'business_name' => $this->clinic->business_name,
            'rejection_reason' => $this->clinic->rejection_reason,
        ];
    }
}

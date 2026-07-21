<?php

namespace App\Notifications;

use App\Models\ExternalClinic;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ExternalClinicRegistered extends Notification
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
        return (new MailMessage)
            ->subject('Solicitud de registro recibida - Clínica Santa Bárbara')
            ->greeting('Hola '.$this->clinic->business_name.',')
            ->line('Hemos recibido su solicitud de registro como clínica externa en el sistema de la Clínica Santa Bárbara.')
            ->line('Nuestro equipo administrativo revisará los datos y documentos adjuntos en las próximas 24-48 horas.')
            ->line('Le notificaremos por este medio una vez que la solicitud haya sido procesada.')
            ->line('')
            ->line('Datos de la solicitud:')
            ->line('NIT: '.$this->clinic->nit)
            ->line('Razón social: '.$this->clinic->business_name)
            ->line('Correo: '.$this->clinic->email)
            ->line('Teléfono: '.$this->clinic->phone)
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
        ];
    }
}

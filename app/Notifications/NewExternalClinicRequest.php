<?php

namespace App\Notifications;

use App\Models\ExternalClinic;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NewExternalClinicRequest extends Notification
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
        $adminUrl = config('app.url').'/solicitudes-clinicas';

        return (new MailMessage)
            ->subject('Nueva solicitud de clínica externa - '.$this->clinic->business_name)
            ->greeting('Hola,')
            ->line('Se ha recibido una nueva solicitud de registro de clínica externa en el sistema.')
            ->line('')
            ->line('Datos de la solicitud:')
            ->line('NIT: '.$this->clinic->nit)
            ->line('Razón social: '.$this->clinic->business_name)
            ->line('Ciudad: '.$this->clinic->city.', '.$this->clinic->department)
            ->line('Representante legal: '.$this->clinic->legal_rep_name)
            ->line('Correo: '.$this->clinic->email)
            ->line('Teléfono: '.$this->clinic->phone)
            ->action('Revisar Solicitud', $adminUrl)
            ->salutation('Saludos, Sistema de Notificaciones - Clínica Santa Bárbara');
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
            'message' => 'Nueva solicitud de clínica externa: '.$this->clinic->business_name,
            'url' => '/admin/solicitudes-clinicas',
        ];
    }
}

<?php

namespace App\Notifications;

use App\Models\ExternalClinic;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ExternalClinicApproved extends Notification
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
        $loginUrl = config('app.url').'/login-clinica';

        return (new MailMessage)
            ->subject('¡Su cuenta ha sido activada! - Clínica Santa Bárbara')
            ->greeting('Hola '.$this->clinic->business_name.',')
            ->line('Nos complace informarle que su solicitud de registro ha sido aprobada y su cuenta está activa.')
            ->line('Ahora puede acceder al sistema de clínicas externas de la Clínica Santa Bárbara.')
            ->line('')
            ->line('Para ingresar utilice:')
            ->line('NIT: '.$this->clinic->nit)
            ->line('Contraseña: la que registró en su solicitud')
            ->line('')
            ->line('Por seguridad, le recomendamos cambiar su contraseña después del primer ingreso.')
            ->action('Ingresar al Sistema', $loginUrl)
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

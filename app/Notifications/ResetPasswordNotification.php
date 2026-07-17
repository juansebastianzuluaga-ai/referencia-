<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ResetPasswordNotification extends Notification
{
    use Queueable;

    public function __construct(
        public string $token,
    ) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $resetUrl = config('app.url').'/reset-password?token='.$this->token.'&email='.urlencode($notifiable->email);

        return (new MailMessage)
            ->subject('Recuperación de Contraseña - Clínica Santa Bárbara')
            ->greeting('Hola '.$notifiable->first_name.',')
            ->line('Has solicitado restablecer tu contraseña para tu cuenta en el sistema de la Clínica Santa Bárbara.')
            ->line('Este enlace expirará en 60 minutos.')
            ->action('Restablecer Contraseña', $resetUrl)
            ->line('Si no solicitaste este cambio, puedes ignorar este correo y tu contraseña permanecerá sin cambios.')
            ->salutation('Saludos, Equipo de Sistemas - Clínica Santa Bárbara');
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'token' => $this->token,
            'email' => $notifiable->email,
        ];
    }
}

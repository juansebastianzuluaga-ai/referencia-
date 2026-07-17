<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Facades\Config;

class MailConfigService
{
    /**
     * Configura el mailer dinámicamente basado en settings de BD
     */
    public function configure(): void
    {
        $mailer = Setting::get('mail_mailer', 'log');
        $host = Setting::get('mail_host', '127.0.0.1');
        $port = Setting::get('mail_port', 1025);
        $encryption = Setting::get('mail_encryption', '');
        $username = Setting::get('mail_username');
        $password = Setting::get('mail_password');
        $fromAddress = Setting::get('mail_from_address', 'noreply@example.com');
        $fromName = Setting::get('mail_from_name', 'Sistema');

        // Configurar mailer
        // Mailpit funciona sobre el protocolo SMTP, por lo que se traduce
        // al transporte "smtp" que Laravel entiende (no existe un driver "mailpit").
        $defaultMailer = $mailer === 'mailpit' ? 'smtp' : $mailer;

        Config::set('mail.default', $defaultMailer);
        Config::set('mail.mailers.smtp.host', $host);
        Config::set('mail.mailers.smtp.port', $port);
        Config::set('mail.mailers.smtp.encryption', $encryption);
        Config::set('mail.mailers.smtp.username', $username);
        Config::set('mail.mailers.smtp.password', $password);
        Config::set('mail.from.address', $fromAddress);
        Config::set('mail.from.name', $fromName);

        // Configurar SES si está disponible
        $sesKey = Setting::get('ses_key');
        $sesSecret = Setting::get('ses_secret');
        $sesRegion = Setting::get('ses_region', 'us-east-1');

        if ($sesKey && $sesSecret) {
            Config::set('services.ses.key', $sesKey);
            Config::set('services.ses.secret', $sesSecret);
            Config::set('services.ses.region', $sesRegion);
        }

        // Configurar Mailgun si está disponible
        $mailgunDomain = Setting::get('mailgun_domain');
        $mailgunSecret = Setting::get('mailgun_secret');
        $mailgunEndpoint = Setting::get('mailgun_endpoint', 'api.mailgun.net');

        if ($mailgunDomain && $mailgunSecret) {
            Config::set('services.mailgun.domain', $mailgunDomain);
            Config::set('services.mailgun.secret', $mailgunSecret);
            Config::set('services.mailgun.endpoint', $mailgunEndpoint);
        }

        // Configurar Postmark si está disponible
        $postmarkToken = Setting::get('postmark_token');

        if ($postmarkToken) {
            Config::set('services.postmark.token', $postmarkToken);
        }
    }

    /**
     * Verifica si la configuración SMTP está completa
     */
    public function isSmtpConfigured(): bool
    {
        $mailer = Setting::get('mail_mailer');
        $host = Setting::get('mail_host');
        $port = Setting::get('mail_port');

        if (! in_array($mailer, ['smtp', 'mailpit'], true)) {
            return false;
        }

        return ! empty($host) && ! empty($port);
    }

    /**
     * Verifica si la configuración SMTP tiene credenciales
     */
    public function hasSmtpCredentials(): bool
    {
        $username = Setting::get('mail_username');
        $password = Setting::get('mail_password');

        return ! empty($username) && ! empty($password);
    }

    /**
     * Obtiene la configuración actual de mail como array
     */
    public function getCurrentConfig(): array
    {
        return [
            'mailer' => Setting::get('mail_mailer'),
            'host' => Setting::get('mail_host'),
            'port' => Setting::get('mail_port'),
            'encryption' => Setting::get('mail_encryption'),
            'username' => Setting::get('mail_username'),
            'password' => Setting::get('mail_password') ? '******' : null,
            'from_address' => Setting::get('mail_from_address'),
            'from_name' => Setting::get('mail_from_name'),
            'is_configured' => $this->isSmtpConfigured(),
            'has_credentials' => $this->hasSmtpCredentials(),
        ];
    }
}

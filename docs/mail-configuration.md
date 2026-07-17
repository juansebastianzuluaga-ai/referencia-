# Configuración de Correo - Settings de Base de Datos

Este documento explica cómo funciona la configuración de correo SMTP dinámica desde la tabla `settings` de la base de datos.

## 📋 Keys de Settings para SMTP

### Configuración Básica SMTP

| Key | Tipo | Default | Descripción |
|-----|------|---------|-------------|
| `mail_mailer` | string | `mailpit` | Mailer a usar: `smtp`, `log`, `mailpit`, `ses`, `mailgun`, `postmark` |
| `mail_host` | string | `127.0.0.1` | Host del servidor SMTP |
| `mail_port` | integer | `1025` | Puerto del servidor SMTP |
| `mail_encryption` | string | `''` | Encriptación: `tls`, `ssl`, o vacío para sin encriptación |
| `mail_username` | string | `null` | Usuario de autenticación SMTP |
| `mail_password` | string | `null` | Contraseña de autenticación SMTP |
| `mail_from_address` | string | `noreply@example.com` | Email de origen (from address) |
| `mail_from_name` | string | `Sistema` | Nombre de origen (from name) |

### AWS SES

| Key | Tipo | Default | Descripción |
|-----|------|---------|-------------|
| `ses_key` | string | `null` | AWS Access Key ID |
| `ses_secret` | string | `null` | AWS Secret Access Key |
| `ses_region` | string | `us-east-1` | Región de AWS SES |

### Mailgun

| Key | Tipo | Default | Descripción |
|-----|------|---------|-------------|
| `mailgun_domain` | string | `null` | Dominio de Mailgun |
| `mailgun_secret` | string | `null` | API Key de Mailgun |
| `mailgun_endpoint` | string | `api.mailgun.net` | Endpoint de API de Mailgun |

### Postmark

| Key | Tipo | Default | Descripción |
|-----|------|---------|-------------|
| `postmark_token` | string | `null` | API Token de Postmark |

---

## 🔧 Cómo Funciona

### Servicio de Configuración

El sistema usa `App\Services\MailConfigService` para:

1. **Leer configuración de BD:** Al iniciar la aplicación, lee los settings de correo de la tabla `settings`
2. **Configurar mailer dinámicamente:** Actualiza la configuración de Laravel en tiempo de ejecución
3. **Verificar configuración:** Métodos para verificar si SMTP está configurado correctamente

### Service Provider

`App\Providers\MailConfigServiceProvider` se ejecuta al iniciar la aplicación y configura el mailer automáticamente.

---

## 📝 Cómo Configurar SMTP

### Paso 1: Ir a Configuración en la Aplicación

Navega al módulo de Configuración en la aplicación.

### Paso 2: Configurar los Settings de Correo

Actualiza los siguientes valores:

```php
// Para SMTP estándar
Setting::set('mail_mailer', 'smtp');
Setting::set('mail_host', 'smtp.gmail.com');
Setting::set('mail_port', 587);
Setting::set('mail_encryption', 'tls');
Setting::set('mail_username', 'tu-email@gmail.com');
Setting::set('mail_password', 'tu-app-password');
Setting::set('mail_from_address', 'noreply@tudominio.com');
Setting::set('mail_from_name', 'Clínica Santa Bárbara');
```

### Paso 3: Verificar Configuración

Usa el servicio para verificar:

```php
$mailConfig = app(MailConfigService::class);

// Verificar si SMTP está configurado
if ($mailConfig->isSmtpConfigured()) {
    // SMTP está configurado
}

// Verificar si tiene credenciales
if ($mailConfig->hasSmtpCredentials()) {
    // Tiene credenciales
}

// Obtener configuración actual
$config = $mailConfig->getCurrentConfig();
```

---

## 🚀 Ejemplos de Configuración

### Gmail SMTP

```php
Setting::set('mail_mailer', 'smtp');
Setting::set('mail_host', 'smtp.gmail.com');
Setting::set('mail_port', 587);
Setting::set('mail_encryption', 'tls');
Setting::set('mail_username', 'tu-email@gmail.com');
Setting::set('mail_password', 'tu-app-password'); // Usar App Password
Setting::set('mail_from_address', 'noreply@tudominio.com');
Setting::set('mail_from_name', 'Clínica Santa Bárbara');
```

### SendGrid SMTP

```php
Setting::set('mail_mailer', 'smtp');
Setting::set('mail_host', 'smtp.sendgrid.net');
Setting::set('mail_port', 587);
Setting::set('mail_encryption', 'tls');
Setting::set('mail_username', 'apikey');
Setting::set('mail_password', 'tu-api-key');
Setting::set('mail_from_address', 'noreply@tudominio.com');
Setting::set('mail_from_name', 'Clínica Santa Bárbara');
```

### AWS SES

```php
Setting::set('mail_mailer', 'ses');
Setting::set('ses_key', 'AWS_ACCESS_KEY_ID');
Setting::set('ses_secret', 'AWS_SECRET_ACCESS_KEY');
Setting::set('ses_region', 'us-east-1');
Setting::set('mail_from_address', 'noreply@tudominio.com');
Setting::set('mail_from_name', 'Clínica Santa Bárbara');
```

### Mailgun

```php
Setting::set('mail_mailer', 'mailgun');
Setting::set('mailgun_domain', 'tudominio.com');
Setting::set('mailgun_secret', 'tu-mailgun-api-key');
Setting::set('mail_from_address', 'noreply@tudominio.com');
Setting::set('mail_from_name', 'Clínica Santa Bárbara');
```

### Postmark

```php
Setting::set('mail_mailer', 'postmark');
Setting::set('postmark_token', 'tu-postmark-api-token');
Setting::set('mail_from_address', 'noreply@tudominio.com');
Setting::set('mail_from_name', 'Clínica Santa Bárbara');
```

---

## ⚠️ Notas Importantes

### Seguridad

- **Nunca** guardar contraseñas en texto plano en el código
- Las contraseñas se guardan en la base de datos - asegúrate de que la BD esté segura
- Considera encriptar las contraseñas en la tabla `settings` para mayor seguridad

### Entorno de Desarrollo

Por defecto, el mailer está configurado en `mailpit` (o `log`) para desarrollo:

```php
// Default en SettingSeeder
['key' => 'mail_mailer', 'value' => 'mailpit', 'group' => 'mail', 'type' => 'string'],
```

### Verificación Antes de Enviar

Antes de enviar emails, verifica que la configuración esté completa:

```php
$mailConfig = app(MailConfigService::class);

if (!$mailConfig->isSmtpConfigured()) {
    // No enviar email, mostrar error
    return;
}

if (!$mailConfig->hasSmtpCredentials()) {
    // No enviar email, mostrar error
    return;
}

// Enviar email
Mail::to($user->email)->send(new WelcomeEmail($user));
```

---

## 🔄 Re-seedear Settings

Si necesitas restaurar los settings por defecto:

```bash
php artisan db:seed --class=SettingSeeder
```

**⚠️ Advertencia:** Esto sobrescribirá los valores actuales con los defaults del seeder.

---

## 📚 Referencias

- [Laravel Mail Documentation](https://laravel.com/docs/mail)
- [AWS SES Documentation](https://docs.aws.amazon.com/ses/)
- [Mailgun Documentation](https://documentation.mailgun.com/)
- [Postmark Documentation](https://postmarkapp.com/developer/documentation)

---

**Última actualización:** Julio 2026
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Correo de prueba</title>
</head>
<body style="margin:0;padding:0;background:#eef2f7;font-family:'Segoe UI',Arial,sans-serif;">
  <table width="100%" cellpadding="0" cellspacing="0" style="background:#eef2f7;padding:28px 0;">
    <tr><td align="center">
      <table width="620" cellpadding="0" cellspacing="0" style="background:#ffffff;border-radius:16px;overflow:hidden;box-shadow:0 4px 24px rgba(13,45,107,.10);">
        <tr><td style="background:linear-gradient(135deg,#0D2D6B 0%,#16468E 55%,#1e3a7a 100%);padding:32px 40px 24px;text-align:center;">
          <img src="{{ $message->embed(public_path('images/logo-w.png')) }}" alt="Clínica Santa Bárbara" style="height:48px;margin:0 auto 10px;display:block;">
          <p style="margin:0;color:#a0b8e8;font-size:13px;">Sistema de Referencia</p>
        </td></tr>
        <tr><td style="background:linear-gradient(90deg,#dcfce7,#bbf7d0);padding:14px 40px;border-bottom:3px solid #16a34a;text-align:center;">
          <p style="margin:0;color:#166534;font-size:14px;font-weight:bold;">&#9989; Correo de prueba — la configuración funciona</p>
        </td></tr>
        <tr><td style="padding:28px 40px;">
          <p style="margin:0 0 12px;color:#334155;font-size:13px;line-height:1.7;">Este es un correo de prueba enviado desde el módulo de <strong>Configuración &gt; Correo</strong> del Sistema de Referencia.</p>
          <p style="margin:0 0 12px;color:#334155;font-size:13px;line-height:1.7;">Si lo está leyendo, el servidor de correo quedó bien configurado y las notificaciones de solicitudes y evoluciones saldrán sin problema.</p>
          <p style="margin:0;color:#64748b;font-size:12px;">Enviado el {{ now()->format('d/m/Y') }} a las {{ now()->format('h:i a') }}</p>
        </td></tr>
        <tr><td style="background:#f7f9fc;padding:18px 40px;text-align:center;border-top:1px solid #e8edf2;">
          <p style="margin:0;color:#94a3b8;font-size:11px;line-height:1.5;">Mensaje automático del Sistema de Referencia Santa Bárbara.</p>
        </td></tr>
      </table>
    </td></tr>
  </table>
</body>
</html>

<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Nueva clínica pendiente de aprobación</title>
</head>
<body style="margin:0;padding:0;background:#f0f4f8;font-family:Arial,sans-serif;">
  <table width="100%" cellpadding="0" cellspacing="0" style="background:#f0f4f8;padding:32px 0;">
    <tr>
      <td align="center">
        <table width="600" cellpadding="0" cellspacing="0" style="background:#ffffff;border-radius:12px;overflow:hidden;box-shadow:0 2px 8px rgba(0,0,0,0.08);">

          <!-- Header -->
          <tr>
            <td style="background:#0D2D6B;padding:28px 40px;text-align:center;">
              <p style="margin:0;color:#ffffff;font-size:20px;font-weight:bold;">Clínica Santa Bárbara</p>
              <p style="margin:6px 0 0;color:#a0b8e8;font-size:13px;">Sistema de Referencia — Notificación interna</p>
            </td>
          </tr>

          <!-- Alerta -->
          <tr>
            <td style="background:#fff8e1;padding:16px 40px;border-bottom:1px solid #ffe082;">
              <p style="margin:0;font-size:14px;color:#7a5800;">
                ⚠️ <strong>Hay una nueva clínica esperando aprobación.</strong> Por favor revise los datos y active o rechace el registro desde el panel de administración.
              </p>
            </td>
          </tr>

          <!-- Datos -->
          <tr>
            <td style="padding:32px 40px;">
              <p style="margin:0 0 20px;font-size:16px;font-weight:bold;color:#1a1a2e;">Datos del registro</p>
              <table width="100%" cellpadding="0" cellspacing="0" style="background:#f7f9fc;border-radius:8px;padding:20px;">
                <tr><td style="padding:5px 0;font-size:13px;color:#888;width:40%;">NIT</td><td style="font-size:13px;color:#1a1a2e;font-weight:700;">{{ $clinica->nit }}</td></tr>
                <tr><td style="padding:5px 0;font-size:13px;color:#888;">Nombre / Sede</td><td style="font-size:13px;color:#1a1a2e;">{{ $clinica->nombre }}</td></tr>
                <tr><td style="padding:5px 0;font-size:13px;color:#888;">Razón social</td><td style="font-size:13px;color:#1a1a2e;">{{ $clinica->razon_social }}</td></tr>
                <tr><td style="padding:5px 0;font-size:13px;color:#888;">Correo</td><td style="font-size:13px;color:#1a1a2e;">{{ $clinica->email }}</td></tr>
                <tr><td style="padding:5px 0;font-size:13px;color:#888;">Teléfono</td><td style="font-size:13px;color:#1a1a2e;">{{ $clinica->telefono }}</td></tr>
                <tr><td style="padding:5px 0;font-size:13px;color:#888;">Ciudad</td><td style="font-size:13px;color:#1a1a2e;">{{ $clinica->ciudad }}, {{ $clinica->departamento }}</td></tr>
                <tr><td style="padding:5px 0;font-size:13px;color:#888;">Dirección</td><td style="font-size:13px;color:#1a1a2e;">{{ $clinica->direccion }}</td></tr>
                <tr><td style="padding:5px 0;font-size:13px;color:#888;">Representante legal</td><td style="font-size:13px;color:#1a1a2e;">{{ $clinica->representante_legal }}</td></tr>
                <tr><td style="padding:5px 0;font-size:13px;color:#888;">Cédula representante</td><td style="font-size:13px;color:#1a1a2e;">{{ $clinica->cedula_representante }}</td></tr>
                <tr><td style="padding:5px 0;font-size:13px;color:#888;">Fecha solicitud</td><td style="font-size:13px;color:#1a1a2e;">{{ $clinica->created_at->format('d/m/Y H:i') }}</td></tr>
              </table>
            </td>
          </tr>

          <!-- Footer -->
          <tr>
            <td style="background:#f7f9fc;padding:20px 40px;text-align:center;border-top:1px solid #e8edf2;">
              <p style="margin:0;font-size:12px;color:#aaa;">
                © {{ date('Y') }} Clínica Santa Bárbara — Notificación interna automática.
              </p>
            </td>
          </tr>

        </table>
      </td>
    </tr>
  </table>
</body>
</html>

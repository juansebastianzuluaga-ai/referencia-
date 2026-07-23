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
              <p style="margin:0;color:#ffffff;font-size:20px;font-weight:bold;letter-spacing:0.5px;">🏥 Clínica Santa Bárbara</p>
              <p style="margin:6px 0 0;color:#a0b8e8;font-size:13px;">Sistema de Referencia — Notificación interna</p>
            </td>
          </tr>

          <!-- Alerta -->
          <tr>
            <td style="background:#fff8e1;padding:16px 40px;border-bottom:2px solid #ffe082;">
              <p style="margin:0;font-size:14px;font-weight:bold;color:#7a5800;text-align:center;">
                ⚠️ Nueva clínica esperando aprobación
              </p>
            </td>
          </tr>

          <!-- Intro -->
          <tr>
            <td style="padding:28px 40px 0;">
              <p style="margin:0 0 6px;font-size:14px;color:#555;line-height:1.7;">
                Se ha recibido una nueva solicitud de registro. Por favor revise los datos y <strong>active o rechace</strong> el registro desde el panel de administración.
              </p>
            </td>
          </tr>

          <!-- Datos -->
          <tr>
            <td style="padding:20px 40px 36px;">
              <p style="margin:0 0 10px;font-size:13px;font-weight:bold;color:#0D2D6B;text-transform:uppercase;letter-spacing:0.5px;">
                📋 Datos del registro
              </p>
              <table width="100%" cellpadding="0" cellspacing="0" style="border-radius:8px;overflow:hidden;border:1px solid #e0e8f0;">
                <tr style="background:#f7f9fc;">
                  <td style="padding:10px 16px;font-size:13px;color:#888;width:42%;border-bottom:1px solid #e8edf2;">NIT</td>
                  <td style="padding:10px 16px;font-size:13px;color:#1a1a2e;font-weight:700;border-bottom:1px solid #e8edf2;">{{ $clinica->nit }}</td>
                </tr>
                <tr style="background:#ffffff;">
                  <td style="padding:10px 16px;font-size:13px;color:#888;border-bottom:1px solid #e8edf2;">🏥 Nombre / Sede</td>
                  <td style="padding:10px 16px;font-size:13px;color:#1a1a2e;border-bottom:1px solid #e8edf2;">{{ $clinica->nombre }}</td>
                </tr>
                <tr style="background:#f7f9fc;">
                  <td style="padding:10px 16px;font-size:13px;color:#888;border-bottom:1px solid #e8edf2;">Razón social</td>
                  <td style="padding:10px 16px;font-size:13px;color:#1a1a2e;border-bottom:1px solid #e8edf2;">{{ $clinica->razon_social }}</td>
                </tr>
                <tr style="background:#ffffff;">
                  <td style="padding:10px 16px;font-size:13px;color:#888;border-bottom:1px solid #e8edf2;">📧 Correo</td>
                  <td style="padding:10px 16px;font-size:13px;color:#1a1a2e;border-bottom:1px solid #e8edf2;">{{ $clinica->email }}</td>
                </tr>
                <tr style="background:#f7f9fc;">
                  <td style="padding:10px 16px;font-size:13px;color:#888;border-bottom:1px solid #e8edf2;">📞 Teléfono</td>
                  <td style="padding:10px 16px;font-size:13px;color:#1a1a2e;border-bottom:1px solid #e8edf2;">{{ $clinica->telefono }}</td>
                </tr>
                <tr style="background:#ffffff;">
                  <td style="padding:10px 16px;font-size:13px;color:#888;border-bottom:1px solid #e8edf2;">📍 Ciudad</td>
                  <td style="padding:10px 16px;font-size:13px;color:#1a1a2e;border-bottom:1px solid #e8edf2;">{{ $clinica->ciudad }}, {{ $clinica->departamento }}</td>
                </tr>
                <tr style="background:#f7f9fc;">
                  <td style="padding:10px 16px;font-size:13px;color:#888;border-bottom:1px solid #e8edf2;">Dirección</td>
                  <td style="padding:10px 16px;font-size:13px;color:#1a1a2e;border-bottom:1px solid #e8edf2;">{{ $clinica->direccion }}</td>
                </tr>
                <tr style="background:#ffffff;">
                  <td style="padding:10px 16px;font-size:13px;color:#888;border-bottom:1px solid #e8edf2;">Representante legal</td>
                  <td style="padding:10px 16px;font-size:13px;color:#1a1a2e;border-bottom:1px solid #e8edf2;">{{ $clinica->representante_legal }}</td>
                </tr>
                <tr style="background:#f7f9fc;">
                  <td style="padding:10px 16px;font-size:13px;color:#888;border-bottom:1px solid #e8edf2;">Cédula representante</td>
                  <td style="padding:10px 16px;font-size:13px;color:#1a1a2e;border-bottom:1px solid #e8edf2;">{{ $clinica->cedula_representante }}</td>
                </tr>
                <tr style="background:#ffffff;">
                  <td style="padding:10px 16px;font-size:13px;color:#888;">Fecha solicitud</td>
                  <td style="padding:10px 16px;font-size:13px;color:#1a1a2e;">{{ $clinica->created_at->format('d/m/Y H:i') }}</td>
                </tr>
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

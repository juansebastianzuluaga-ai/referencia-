<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Solicitud de registro recibida</title>
</head>
<body style="margin:0;padding:0;background:#f0f4f8;font-family:Arial,sans-serif;">
  <table width="100%" cellpadding="0" cellspacing="0" style="background:#f0f4f8;padding:32px 0;">
    <tr>
      <td align="center">
        <table width="600" cellpadding="0" cellspacing="0" style="background:#ffffff;border-radius:12px;overflow:hidden;box-shadow:0 2px 8px rgba(0,0,0,0.08);">

          <!-- Header -->
          <tr>
            <td style="background:#0D2D6B;padding:28px 40px;text-align:center;">
              <p style="margin:0;color:#ffffff;font-size:20px;font-weight:bold;letter-spacing:0.5px;">
                Clínica Santa Bárbara
              </p>
              <p style="margin:6px 0 0;color:#a0b8e8;font-size:13px;">Sistema de Referencia y Contrarreferencia</p>
            </td>
          </tr>

          <!-- Cuerpo -->
          <tr>
            <td style="padding:36px 40px;">
              <p style="margin:0 0 8px;font-size:22px;font-weight:bold;color:#1a1a2e;">
                ¡Solicitud recibida!
              </p>
              <p style="margin:0 0 24px;font-size:14px;color:#555;">
                Estimados representantes de <strong>{{ $clinica->nombre }}</strong>,
              </p>
              <p style="margin:0 0 16px;font-size:14px;color:#555;line-height:1.6;">
                Hemos recibido su solicitud de registro en el <strong>Sistema de Referencia y Contrarreferencia</strong> de la Clínica Santa Bárbara. Nuestro equipo revisará la información suministrada y le notificará el resultado a esta misma dirección de correo.
              </p>

              <!-- Resumen datos -->
              <table width="100%" cellpadding="0" cellspacing="0" style="background:#f7f9fc;border-radius:8px;padding:20px;margin:24px 0;">
                <tr><td style="padding:4px 0;font-size:13px;color:#888;width:40%;">NIT</td><td style="font-size:13px;color:#1a1a2e;font-weight:600;">{{ $clinica->nit }}</td></tr>
                <tr><td style="padding:4px 0;font-size:13px;color:#888;">Razón social</td><td style="font-size:13px;color:#1a1a2e;">{{ $clinica->razon_social }}</td></tr>
                <tr><td style="padding:4px 0;font-size:13px;color:#888;">Ciudad</td><td style="font-size:13px;color:#1a1a2e;">{{ $clinica->ciudad }}, {{ $clinica->departamento }}</td></tr>
                <tr><td style="padding:4px 0;font-size:13px;color:#888;">Representante</td><td style="font-size:13px;color:#1a1a2e;">{{ $clinica->representante_legal }}</td></tr>
              </table>

              <p style="margin:0 0 8px;font-size:14px;color:#555;line-height:1.6;">
                El proceso de verificación puede tardar hasta <strong>24 horas hábiles</strong>. Si tiene alguna duda, comuníquese con el área de referencia de la clínica.
              </p>
            </td>
          </tr>

          <!-- Footer -->
          <tr>
            <td style="background:#f7f9fc;padding:20px 40px;text-align:center;border-top:1px solid #e8edf2;">
              <p style="margin:0;font-size:12px;color:#aaa;">
                © {{ date('Y') }} Clínica Santa Bárbara — Este correo es generado automáticamente, por favor no responda.
              </p>
            </td>
          </tr>

        </table>
      </td>
    </tr>
  </table>
</body>
</html>

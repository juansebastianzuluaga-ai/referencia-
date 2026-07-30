<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Acceso al Sistema de Referencia</title>
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
                🏥 Clínica Santa Bárbara
              </p>
              <p style="margin:6px 0 0;color:#a0b8e8;font-size:13px;">Sistema de Referencia y Contrarreferencia</p>
            </td>
          </tr>

          <!-- Barra informativa -->
          <tr>
            <td style="background:#e3f2fd;padding:16px 40px;border-bottom:2px solid #90caf9;">
              <p style="margin:0;font-size:15px;font-weight:bold;color:#1565c0;text-align:center;letter-spacing:0.3px;">
                🔐 Enlace de acceso seguro
              </p>
            </td>
          </tr>

          <!-- Cuerpo -->
          <tr>
            <td style="padding:36px 40px;">
              <p style="margin:0 0 8px;font-size:22px;font-weight:bold;color:#1a1a2e;">
                ¡Hola, {{ $clinica->nombre }}!
              </p>
              <p style="margin:0 0 24px;font-size:14px;color:#555;line-height:1.7;">
                Hemos recibido una solicitud de acceso al <strong>Sistema de Referencia y Contrarreferencia</strong>. Haga clic en el botón a continuación para ingresar de forma segura.
              </p>

              <!-- Botón de acceso -->
              <table width="100%" cellpadding="0" cellspacing="0" style="background:#e3f2fd;border-radius:8px;border:1px solid #90caf9;margin-bottom:28px;">
                <tr>
                  <td style="padding:24px;text-align:center;">
                    <p style="margin:0 0 16px;font-size:13px;color:#1565c0;font-weight:bold;">
                      Haga clic aquí para acceder al sistema:
                    </p>
                    <a href="{{ $url }}" style="display:inline-block;background:#0D2D6B;color:#ffffff;text-decoration:none;padding:14px 32px;border-radius:6px;font-size:15px;font-weight:bold;letter-spacing:0.3px;">
                      Acceder al Sistema
                    </a>
                    <p style="margin:16px 0 0;font-size:12px;color:#888;">
                      Si el botón no funciona, copie y pegue este enlace en su navegador:
                    </p>
                    <p style="margin:6px 0 0;font-size:11px;color:#1565c0;word-break:break-all;">
                      {{ $url }}
                    </p>
                  </td>
                </tr>
              </table>

              <!-- Aviso de expiración -->
              <table width="100%" cellpadding="0" cellspacing="0" style="background:#fff8e1;border-radius:8px;border:1px solid #ffe082;margin-bottom:24px;">
                <tr>
                  <td style="padding:14px 20px;">
                    <p style="margin:0;font-size:13px;color:#7a5800;">
                      ⏱️ <strong>Este enlace es válido hasta las 11:59 PM de hoy.</strong> Pasado ese tiempo, deberá solicitar uno nuevo.
                    </p>
                  </td>
                </tr>
              </table>

              <p style="margin:0;font-size:13px;color:#888;line-height:1.6;">
                Si usted no solicitó este acceso, ignore este correo. Nadie más puede utilizar este enlace sin hacer clic en él.
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

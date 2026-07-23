<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Solicitud rechazada</title>
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

          <!-- Barra de alerta -->
          <tr>
            <td style="background:#ffebee;padding:16px 40px;border-bottom:2px solid #ef9a9a;">
              <p style="margin:0;font-size:15px;font-weight:bold;color:#c62828;text-align:center;letter-spacing:0.3px;">
                ❌ Su solicitud ha sido rechazada
              </p>
            </td>
          </tr>

          <!-- Cuerpo -->
          <tr>
            <td style="padding:36px 40px;">
              <p style="margin:0 0 8px;font-size:20px;font-weight:bold;color:#1a1a2e;">
                Resultado de su solicitud
              </p>
              <p style="margin:0 0 20px;font-size:14px;color:#555;line-height:1.7;">
                Estimados representantes de <strong>{{ $clinica->nombre }}</strong>,
              </p>
              <p style="margin:0 0 24px;font-size:14px;color:#555;line-height:1.7;">
                Lamentamos informarles que, tras revisar su solicitud de registro en el <strong>Sistema de Referencia y Contrarreferencia</strong> de la Clínica Santa Bárbara, la misma no ha podido ser aprobada en esta oportunidad.
              </p>

              <!-- Motivo de rechazo -->
              <p style="margin:0 0 10px;font-size:13px;font-weight:bold;color:#c62828;text-transform:uppercase;letter-spacing:0.5px;">
                Motivo del rechazo
              </p>
              <table width="100%" cellpadding="0" cellspacing="0" style="background:#fff3f3;border-radius:8px;border:1px solid #ffcdd2;margin-bottom:24px;">
                <tr>
                  <td style="padding:16px 20px;font-size:14px;color:#b71c1c;line-height:1.7;">
                    {{ $motivo }}
                  </td>
                </tr>
              </table>

              <!-- Datos de la clínica -->
              <p style="margin:0 0 10px;font-size:13px;font-weight:bold;color:#0D2D6B;text-transform:uppercase;letter-spacing:0.5px;">
                📋 Datos de la solicitud
              </p>
              <table width="100%" cellpadding="0" cellspacing="0" style="border-radius:8px;overflow:hidden;border:1px solid #e0e8f0;margin-bottom:24px;">
                <tr style="background:#f7f9fc;">
                  <td style="padding:10px 16px;font-size:13px;color:#888;width:40%;border-bottom:1px solid #e8edf2;">NIT</td>
                  <td style="padding:10px 16px;font-size:13px;color:#1a1a2e;font-weight:700;border-bottom:1px solid #e8edf2;">{{ $clinica->nit }}</td>
                </tr>
                <tr style="background:#ffffff;">
                  <td style="padding:10px 16px;font-size:13px;color:#888;">🏥 Nombre / Sede</td>
                  <td style="padding:10px 16px;font-size:13px;color:#1a1a2e;">{{ $clinica->nombre }}</td>
                </tr>
              </table>

              <p style="margin:0;font-size:14px;color:#555;line-height:1.7;">
                Si considera que esto es un error o tiene preguntas, comuníquese con el área de referencia de la Clínica Santa Bárbara.
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

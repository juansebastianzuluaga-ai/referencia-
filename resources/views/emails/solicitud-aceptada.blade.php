<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Solicitud aceptada</title>
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

          <!-- Barra de éxito -->
          <tr>
            <td style="background:#e8f5e9;padding:16px 40px;border-bottom:2px solid #a5d6a7;">
              <p style="margin:0;font-size:15px;font-weight:bold;color:#2e7d32;text-align:center;">
                ✅ Solicitud de referencia ACEPTADA
              </p>
            </td>
          </tr>

          <!-- Cuerpo -->
          <tr>
            <td style="padding:36px 40px;">
              <p style="margin:0 0 20px;font-size:14px;color:#555;line-height:1.7;">
                Estimados representantes de <strong>{{ $solicitud->clinica->nombre ?? 'Clínica' }}</strong>,
              </p>
              <p style="margin:0 0 20px;font-size:14px;color:#555;line-height:1.7;">
                Le informamos que su solicitud de referencia ha sido <strong style="color:#2e7d32;">aceptada</strong>.
              </p>

              <!-- Datos del paciente -->
              <p style="margin:0 0 10px;font-size:13px;font-weight:bold;color:#0D2D6B;text-transform:uppercase;letter-spacing:0.5px;">
                👤 Datos del paciente
              </p>
              <table width="100%" cellpadding="0" cellspacing="0" style="border-radius:8px;overflow:hidden;border:1px solid #e0e8f0;margin-bottom:24px;">
                <tr style="background:#f7f9fc;">
                  <td style="padding:10px 16px;font-size:13px;color:#888;width:40%;border-bottom:1px solid #e8edf2;">Paciente</td>
                  <td style="padding:10px 16px;font-size:13px;color:#1a1a2e;font-weight:700;border-bottom:1px solid #e8edf2;">{{ $solicitud->primer_nombre }} {{ $solicitud->segundo_nombre }} {{ $solicitud->primer_apellido }} {{ $solicitud->segundo_apellido }}</td>
                </tr>
                <tr style="background:#ffffff;">
                  <td style="padding:10px 16px;font-size:13px;color:#888;border-bottom:1px solid #e8edf2;">Documento</td>
                  <td style="padding:10px 16px;font-size:13px;color:#1a1a2e;border-bottom:1px solid #e8edf2;">{{ $solicitud->tipo_documento }} {{ $solicitud->numero_documento }}</td>
                </tr>
                <tr style="background:#f7f9fc;">
                  <td style="padding:10px 16px;font-size:13px;color:#888;border-bottom:1px solid #e8edf2;">Especialidad</td>
                  <td style="padding:10px 16px;font-size:13px;color:#1a1a2e;border-bottom:1px solid #e8edf2;">{{ $solicitud->especialidad_requerida }}</td>
                </tr>
                <tr style="background:#ffffff;">
                  <td style="padding:10px 16px;font-size:13px;color:#888;">EPS</td>
                  <td style="padding:10px 16px;font-size:13px;color:#1a1a2e;">{{ $solicitud->eps }}</td>
                </tr>
              </table>

              <!-- Código de aceptación -->
              <table width="100%" cellpadding="0" cellspacing="0" style="background:#e8f5e9;border-radius:8px;border:1px solid #a5d6a7;margin-bottom:24px;">
                <tr>
                  <td style="padding:20px 24px;text-align:center;">
                    <p style="margin:0 0 6px;font-size:13px;color:#388e3c;font-weight:bold;">Código de aceptación</p>
                    <p style="margin:0;font-size:24px;font-weight:bold;color:#1b5e20;letter-spacing:2px;font-family:monospace;">
                      {{ $solicitud->codigo_aceptacion }}
                    </p>
                    @if($solicitud->hora_respuesta)
                    <p style="margin:10px 0 0;font-size:12px;color:#555;">
                      Hora de respuesta: <strong>{{ $solicitud->hora_respuesta }}</strong>
                      · Respondió: <strong>{{ $solicitud->nombre_quien_responde }}</strong>
                    </p>
                    @endif
                  </td>
                </tr>
              </table>

              @if($solicitud->observaciones_respuesta)
              <p style="margin:0 0 6px;font-size:13px;font-weight:bold;color:#0D2D6B;">Observaciones:</p>
              <p style="margin:0 0 20px;font-size:13px;color:#555;line-height:1.6;">{{ $solicitud->observaciones_respuesta }}</p>
              @endif

              <p style="margin:0;font-size:13px;color:#888;line-height:1.6;">
                Puede consultar el estado de sus solicitudes ingresando al sistema con su NIT.
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

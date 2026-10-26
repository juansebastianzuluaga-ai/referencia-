<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Paciente atendido</title>
</head>
<body style="margin:0;padding:0;background:#eef2f7;font-family:Arial,Helvetica,sans-serif;">
  <table width="100%" cellpadding="0" cellspacing="0" style="background:#eef2f7;padding:32px 0;">
    <tr>
      <td align="center">
        <table width="600" cellpadding="0" cellspacing="0" style="background:#ffffff;border-radius:16px;overflow:hidden;box-shadow:0 4px 20px rgba(13,45,107,0.10);">

          <!-- Header -->
          <tr>
            <td style="background:#0D2D6B;padding:30px 40px;text-align:center;">
              <img src="{{ $message->embed(public_path('images/logo-w.png')) }}" alt="Clínica Santa Bárbara" style="height:52px;margin:0 auto 12px;display:block;">
              <p style="margin:0;color:#ffffff;font-size:15px;font-weight:bold;letter-spacing:0.5px;">Central de Referencia</p>
              <p style="margin:3px 0 0;color:#a0b8e8;font-size:12px;">Clínica Santa Bárbara · Alta Complejidad</p>
            </td>
          </tr>

          <!-- Héroe de estado -->
          <tr>
            <td style="background:#eef2ff;padding:26px 40px;text-align:center;border-bottom:3px solid #4f46e5;">
              <table cellpadding="0" cellspacing="0" align="center" style="margin:0 auto 10px;">
                <tr>
                  <td style="width:44px;height:44px;background:#4f46e5;border-radius:50%;text-align:center;vertical-align:middle;font-size:22px;color:#ffffff;font-weight:bold;">✓</td>
                </tr>
              </table>
              <p style="margin:0;font-size:20px;font-weight:bold;color:#4338ca;letter-spacing:0.5px;">PACIENTE ATENDIDO</p>
              <p style="margin:6px 0 0;font-size:14px;color:#3730a3;">
                {{ $solicitud->primer_nombre }} {{ $solicitud->segundo_nombre }} {{ $solicitud->primer_apellido }} {{ $solicitud->segundo_apellido }} — {{ $solicitud->especialidad_requerida }}
              </p>
            </td>
          </tr>

          <!-- Cuerpo -->
          <tr>
            <td style="padding:28px 40px 36px;">
              <p style="margin:0 0 20px;font-size:14px;color:#475569;line-height:1.7;">
                Estimados representantes de <strong>{{ $solicitud->clinica->nombre ?? $solicitud->institucion_remitente ?? 'la institución remitente' }}</strong>,
                le informamos que el paciente llegó a nuestra institución y fue atendido.
                La solicitud de referencia quedó marcada como <strong style="color:#4338ca;">completada</strong>.
              </p>

              <!-- Datos del paciente -->
              <p style="margin:0 0 8px;font-size:11px;font-weight:bold;color:#0D2D6B;text-transform:uppercase;letter-spacing:1px;">
                Información del paciente
              </p>
              <table width="100%" cellpadding="0" cellspacing="0" style="border-radius:10px;overflow:hidden;border:1px solid #e0e8f0;margin-bottom:20px;">
                <tr style="background:#f8fafc;">
                  <td style="padding:10px 16px;font-size:13px;color:#64748b;width:42%;border-bottom:1px solid #e8edf2;">Nombre del paciente</td>
                  <td style="padding:10px 16px;font-size:13px;color:#1e2d55;font-weight:700;border-bottom:1px solid #e8edf2;">{{ $solicitud->primer_nombre }} {{ $solicitud->segundo_nombre }} {{ $solicitud->primer_apellido }} {{ $solicitud->segundo_apellido }}</td>
                </tr>
                <tr style="background:#ffffff;">
                  <td style="padding:10px 16px;font-size:13px;color:#64748b;border-bottom:1px solid #e8edf2;">Tipo y número de documento</td>
                  <td style="padding:10px 16px;font-size:13px;color:#1e2d55;border-bottom:1px solid #e8edf2;">{{ $solicitud->tipo_documento }} {{ $solicitud->numero_documento }}</td>
                </tr>
                <tr style="background:#f8fafc;">
                  <td style="padding:10px 16px;font-size:13px;color:#64748b;border-bottom:1px solid #e8edf2;">Aseguradora (EPS)</td>
                  <td style="padding:10px 16px;font-size:13px;color:#1e2d55;border-bottom:1px solid #e8edf2;">{{ $solicitud->eps }}</td>
                </tr>
                <tr style="background:#ffffff;">
                  <td style="padding:10px 16px;font-size:13px;color:#64748b;border-bottom:1px solid #e8edf2;">Especialidad</td>
                  <td style="padding:10px 16px;font-size:13px;color:#1e2d55;font-weight:700;border-bottom:1px solid #e8edf2;">{{ $solicitud->especialidad_requerida }}</td>
                </tr>
                @if($solicitud->codigo_aceptacion)
                <tr style="background:#f8fafc;">
                  <td style="padding:10px 16px;font-size:13px;color:#64748b;border-bottom:1px solid #e8edf2;">Código de aceptación</td>
                  <td style="padding:10px 16px;font-size:13px;color:#1e2d55;font-family:'Courier New',monospace;font-weight:700;border-bottom:1px solid #e8edf2;">{{ $solicitud->codigo_aceptacion }}</td>
                </tr>
                @endif
                <tr style="background:#ffffff;">
                  <td style="padding:10px 16px;font-size:13px;color:#64748b;">Estado de la solicitud</td>
                  <td style="padding:10px 16px;font-size:13px;color:#4338ca;font-weight:700;">COMPLETADA</td>
                </tr>
              </table>

              @if($solicitud->observaciones_respuesta)
              <table width="100%" cellpadding="0" cellspacing="0" style="background:#f8fafc;border-left:3px solid #0D2D6B;border-radius:0 8px 8px 0;margin-bottom:20px;">
                <tr>
                  <td style="padding:12px 16px;">
                    <p style="margin:0 0 4px;font-size:11px;font-weight:bold;color:#0D2D6B;text-transform:uppercase;letter-spacing:0.5px;">Observaciones</p>
                    <p style="margin:0;font-size:13px;color:#475569;line-height:1.6;">{{ $solicitud->observaciones_respuesta }}</p>
                  </td>
                </tr>
              </table>
              @endif

              <p style="margin:0;font-size:13px;color:#64748b;line-height:1.7;">
                Agradecemos su colaboración en la continuidad del proceso de referencia, con el propósito de
                garantizar una atención oportuna y segura al paciente.
              </p>
            </td>
          </tr>

          <!-- Footer -->
          <tr>
            <td style="background:#f8fafc;padding:22px 40px;text-align:center;border-top:1px solid #e8edf2;">
              <p style="margin:0 0 4px;font-size:13px;font-weight:bold;color:#0D2D6B;">Cordialmente,</p>
              <p style="margin:0 0 10px;font-size:13px;color:#475569;">Central de Referencia — Clínica Santa Bárbara</p>
              <p style="margin:0;font-size:11px;color:#94a3b8;">
                © {{ date('Y') }} Clínica Santa Bárbara · Este correo fue generado automáticamente, por favor no responda.
              </p>
            </td>
          </tr>

        </table>
      </td>
    </tr>
  </table>
</body>
</html>

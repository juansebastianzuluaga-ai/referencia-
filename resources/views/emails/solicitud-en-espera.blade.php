<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Solicitud aceptada</title>
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
            <td style="background:#f0fdf4;padding:26px 40px;text-align:center;border-bottom:3px solid #16a34a;">
              <table cellpadding="0" cellspacing="0" align="center" style="margin:0 auto 10px;">
                <tr>
                  <td style="width:44px;height:44px;background:#16a34a;border-radius:50%;text-align:center;vertical-align:middle;font-size:22px;color:#ffffff;font-weight:bold;">✓</td>
                </tr>
              </table>
              <p style="margin:0;font-size:20px;font-weight:bold;color:#15803d;letter-spacing:0.5px;">SOLICITUD ACEPTADA</p>
              <p style="margin:6px 0 0;font-size:14px;color:#166534;">
                {{ $solicitud->primer_nombre }} {{ $solicitud->segundo_nombre }} {{ $solicitud->primer_apellido }} {{ $solicitud->segundo_apellido }} — {{ $solicitud->especialidad_requerida }}
              </p>
            </td>
          </tr>

          <!-- Código de aceptación — el dato clave -->
          <tr>
            <td style="padding:28px 40px 0;">
              <table width="100%" cellpadding="0" cellspacing="0" style="background:#f0fdf4;border:2px dashed #86efac;border-radius:12px;">
                <tr>
                  <td style="padding:20px 24px;text-align:center;">
                    <p style="margin:0 0 4px;font-size:11px;font-weight:bold;color:#16a34a;text-transform:uppercase;letter-spacing:1px;">Código de aceptación</p>
                    <p style="margin:0;font-size:30px;font-weight:bold;color:#14532d;letter-spacing:4px;font-family:'Courier New',monospace;">
                      {{ $solicitud->codigo_aceptacion }}
                    </p>
                    <p style="margin:8px 0 0;font-size:12px;color:#166534;line-height:1.5;">
                      Preséntelo al ingresar al paciente a la institución.
                    </p>
                  </td>
                </tr>
              </table>
            </td>
          </tr>

          <!-- Cuerpo -->
          <tr>
            <td style="padding:26px 40px 36px;">
              <p style="margin:0 0 20px;font-size:14px;color:#475569;line-height:1.7;">
                Estimados representantes de <strong>{{ $solicitud->clinica->nombre ?? $solicitud->institucion_remitente ?? 'la institución remitente' }}</strong>,
                nos permitimos informar que la solicitud de referencia fue <strong style="color:#15803d;">aceptada</strong> por nuestra institución.
                A continuación, los datos para gestionar el ingreso del paciente:
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
                <tr style="background:#f8fafc;">
                  <td style="padding:10px 16px;font-size:13px;color:#64748b;">Estado de la solicitud</td>
                  <td style="padding:10px 16px;font-size:13px;color:#15803d;font-weight:700;">ACEPTADA — paciente en espera de llegada</td>
                </tr>
              </table>

              <!-- Logística de la respuesta -->
              @if($solicitud->hora_respuesta || $solicitud->hora_llegada || $solicitud->lugar_llegada)
              <p style="margin:0 0 8px;font-size:11px;font-weight:bold;color:#0D2D6B;text-transform:uppercase;letter-spacing:1px;">
                Datos de la respuesta
              </p>
              <table width="100%" cellpadding="0" cellspacing="0" style="border-radius:10px;overflow:hidden;border:1px solid #e0e8f0;margin-bottom:20px;">
                @if($solicitud->nombre_quien_responde)
                <tr style="background:#f8fafc;">
                  <td style="padding:10px 16px;font-size:13px;color:#64748b;width:42%;border-bottom:1px solid #e8edf2;">Respondió</td>
                  <td style="padding:10px 16px;font-size:13px;color:#1e2d55;border-bottom:1px solid #e8edf2;">{{ $solicitud->nombre_quien_responde }}</td>
                </tr>
                @endif
                @if($solicitud->hora_respuesta)
                <tr style="background:#ffffff;">
                  <td style="padding:10px 16px;font-size:13px;color:#64748b;border-bottom:1px solid #e8edf2;">Hora de respuesta</td>
                  <td style="padding:10px 16px;font-size:13px;color:#1e2d55;border-bottom:1px solid #e8edf2;">{{ $solicitud->hora_respuesta }}</td>
                </tr>
                @endif
                @if($solicitud->hora_llegada)
                <tr style="background:#f8fafc;">
                  <td style="padding:10px 16px;font-size:13px;color:#64748b;border-bottom:1px solid #e8edf2;">Llegada estimada</td>
                  <td style="padding:10px 16px;font-size:13px;color:#1e2d55;font-weight:700;border-bottom:1px solid #e8edf2;">{{ $solicitud->hora_llegada }}</td>
                </tr>
                @endif
                @if($solicitud->lugar_llegada)
                <tr style="background:#ffffff;">
                  <td style="padding:10px 16px;font-size:13px;color:#64748b;border-bottom:1px solid #e8edf2;">Lugar de llegada</td>
                  <td style="padding:10px 16px;font-size:13px;color:#1e2d55;border-bottom:1px solid #e8edf2;">{{ $solicitud->lugar_llegada }}</td>
                </tr>
                @endif
                @if($solicitud->sede)
                <tr style="background:#f8fafc;">
                  <td style="padding:10px 16px;font-size:13px;color:#64748b;">Sede</td>
                  <td style="padding:10px 16px;font-size:13px;color:#1e2d55;font-weight:700;">{{ $solicitud->sede }}</td>
                </tr>
                @endif
              </table>
              @endif

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

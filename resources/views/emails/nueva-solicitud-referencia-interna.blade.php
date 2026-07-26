<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Nueva solicitud de referencia</title>
</head>
<body style="margin:0;padding:0;background:#eef2f7;font-family:'Segoe UI',Arial,sans-serif;">
  <table width="100%" cellpadding="0" cellspacing="0" style="background:#eef2f7;padding:28px 0;">
    <tr><td align="center">
      <table width="620" cellpadding="0" cellspacing="0" style="background:#ffffff;border-radius:16px;overflow:hidden;box-shadow:0 4px 24px rgba(13,45,107,.10);">

        <!-- Header con gradiente -->
        <tr><td style="background:linear-gradient(135deg,#0D2D6B 0%,#16468E 55%,#1e3a7a 100%);padding:32px 40px 24px;text-align:center;">
          <p style="margin:0;color:#ffffff;font-size:22px;font-weight:bold;letter-spacing:.02em;">Clínica Santa Bárbara</p>
          <p style="margin:8px 0 0;color:#a0b8e8;font-size:13px;">Sistema de Referencia &middot; Notificación interna</p>
        </td></tr>

        <!-- Banner de estado -->
        <tr><td style="background:linear-gradient(90deg,#fef3c7,#fde68a);padding:14px 40px;border-bottom:3px solid #f59e0b;text-align:center;">
          <p style="margin:0;color:#92400e;font-size:14px;font-weight:bold;">&#9888; Nueva solicitud de referencia pendiente de revisión</p>
        </td></tr>

        <!-- Cuerpo -->
        <tr><td style="padding:28px 40px 8px;">
          <p style="margin:0 0 6px;color:#0D2D6B;font-size:15px;font-weight:bold;">Nueva remisión recibida</p>
          <p style="margin:0 0 22px;color:#64748b;font-size:13px;line-height:1.6;">La institución <strong style="color:#16468E;">{{ $clinica->nombre }}</strong> ha enviado una nueva solicitud de referencia. A continuación se detallan los datos del paciente y la remisión.</p>

          <!-- Sección: Datos del paciente -->
          <p style="margin:0 0 10px;color:#0D2D6B;font-size:12px;font-weight:bold;text-transform:uppercase;letter-spacing:.06em;border-bottom:2px solid #e0ecf8;padding-bottom:6px;">Datos del paciente</p>
          <table width="100%" cellpadding="0" cellspacing="0" style="border:1px solid #e0e8f0;border-radius:10px;overflow:hidden;margin-bottom:20px;">
            <tr style="background:#f7f9fc;"><td style="padding:9px 16px;color:#64748b;font-size:12px;width:38%;">Paciente</td><td style="padding:9px 16px;color:#1a1a2e;font-size:13px;font-weight:bold;">{{ $solicitud->primer_nombre }} {{ $solicitud->segundo_nombre }} {{ $solicitud->primer_apellido }} {{ $solicitud->segundo_apellido }}</td></tr>
            <tr><td style="padding:9px 16px;color:#64748b;font-size:12px;">Documento</td><td style="padding:9px 16px;color:#1a1a2e;font-size:13px;">{{ $solicitud->tipo_documento }} {{ $solicitud->numero_documento }}</td></tr>
            <tr style="background:#f7f9fc;"><td style="padding:9px 16px;color:#64748b;font-size:12px;">Edad / Género</td><td style="padding:9px 16px;color:#1a1a2e;font-size:13px;">{{ $solicitud->edad }} años &middot; {{ $solicitud->genero === 'M' ? 'Masculino' : 'Femenino' }}</td></tr>
            <tr><td style="padding:9px 16px;color:#64748b;font-size:12px;">EPS</td><td style="padding:9px 16px;color:#1a1a2e;font-size:13px;">{{ $solicitud->eps }}</td></tr>
            <tr style="background:#f7f9fc;"><td style="padding:9px 16px;color:#64748b;font-size:12px;">Gestante</td><td style="padding:9px 16px;color:#1a1a2e;font-size:13px;">{{ $solicitud->gestante ? 'Sí' : 'No' }}</td></tr>
          </table>

          <!-- Sección: Datos de la remisión -->
          <p style="margin:0 0 10px;color:#0D2D6B;font-size:12px;font-weight:bold;text-transform:uppercase;letter-spacing:.06em;border-bottom:2px solid #e0ecf8;padding-bottom:6px;">Datos de la remisión</p>
          <table width="100%" cellpadding="0" cellspacing="0" style="border:1px solid #e0e8f0;border-radius:10px;overflow:hidden;margin-bottom:20px;">
            <tr style="background:#f7f9fc;"><td style="padding:9px 16px;color:#64748b;font-size:12px;width:38%;">Institución</td><td style="padding:9px 16px;color:#1a1a2e;font-size:13px;">{{ $clinica->nombre }} &middot; NIT {{ $clinica->nit }}</td></tr>
            <tr><td style="padding:9px 16px;color:#64748b;font-size:12px;">Especialidad requerida</td><td style="padding:9px 16px;color:#1a1a2e;font-size:13px;font-weight:bold;">{{ $solicitud->especialidad_requerida }}</td></tr>
            <tr style="background:#f7f9fc;"><td style="padding:9px 16px;color:#64748b;font-size:12px;">Diagnóstico (CIE-10)</td><td style="padding:9px 16px;color:#1a1a2e;font-size:13px;">{{ $solicitud->diagnostico }}</td></tr>
            <tr><td style="padding:9px 16px;color:#64748b;font-size:12px;">Municipio de origen</td><td style="padding:9px 16px;color:#1a1a2e;font-size:13px;">{{ $solicitud->municipio_capita }}</td></tr>
            <tr style="background:#f7f9fc;"><td style="padding:9px 16px;color:#64748b;font-size:12px;">Servicio actual</td><td style="padding:9px 16px;color:#1a1a2e;font-size:13px;">{{ $solicitud->servicio_ubicacion_actual }}</td></tr>
            @if($solicitud->servicio_remision)<tr><td style="padding:9px 16px;color:#64748b;font-size:12px;">Servicio al que se remite</td><td style="padding:9px 16px;color:#1a1a2e;font-size:13px;">{{ $solicitud->servicio_remision }}</td></tr>@endif
            @if($solicitud->via_contacto)<tr style="background:#f7f9fc;"><td style="padding:9px 16px;color:#64748b;font-size:12px;">Vía de contacto</td><td style="padding:9px 16px;color:#1a1a2e;font-size:13px;">{{ $solicitud->via_contacto }}</td></tr>@endif
            @if($solicitud->condicion_especial)<tr><td style="padding:9px 16px;color:#64748b;font-size:12px;">Condición especial</td><td style="padding:9px 16px;color:#1a1a2e;font-size:13px;">{{ $solicitud->condicion_especial }}</td></tr>@endif
            <tr style="background:#f7f9fc;"><td style="padding:9px 16px;color:#64748b;font-size:12px;">Fecha y hora</td><td style="padding:9px 16px;color:#1a1a2e;font-size:13px;">{{ $solicitud->fecha->format('d/m/Y') }} a las {{ $solicitud->hora }}</td></tr>
            <tr><td style="padding:9px 16px;color:#64748b;font-size:12px;">Fecha de envío</td><td style="padding:9px 16px;color:#1a1a2e;font-size:13px;">{{ $solicitud->created_at->format('d/m/Y H:i') }}</td></tr>
          </table>

          <!-- Sección: Resumen clínico -->
          <p style="margin:0 0 10px;color:#0D2D6B;font-size:12px;font-weight:bold;text-transform:uppercase;letter-spacing:.06em;border-bottom:2px solid #e0ecf8;padding-bottom:6px;">Resumen de historia clínica</p>
          <div style="background:#f8fbff;border:1px solid #e0ecf8;border-radius:10px;padding:14px 16px;margin-bottom:8px;">
            <p style="margin:0;color:#334155;font-size:13px;line-height:1.7;">{{ $solicitud->resumen_historia_clinica }}</p>
          </div>

          @if($solicitud->observaciones)
          <div style="background:#fefce8;border:1px solid #fde68a;border-radius:10px;padding:12px 16px;margin-bottom:20px;">
            <p style="margin:0 0 4px;color:#92400e;font-size:11px;font-weight:bold;text-transform:uppercase;letter-spacing:.04em;">Observaciones adicionales</p>
            <p style="margin:0;color:#78716c;font-size:13px;line-height:1.6;">{{ $solicitud->observaciones }}</p>
          </div>
          @endif

          @if($solicitud->adjuntos->isNotEmpty())
          <div style="background:#f0f7ff;border:1px solid #bfdbfe;border-radius:10px;padding:12px 16px;margin-bottom:20px;">
            <p style="margin:0 0 6px;color:#1e40af;font-size:12px;font-weight:bold;">&#128206; Archivos adjuntos ({{ $solicitud->adjuntos->count() }})</p>
            <ul style="margin:0;padding-left:18px;color:#475569;font-size:12px;line-height:1.8;">
              @foreach($solicitud->adjuntos as $adjunto)
                <li>{{ $adjunto->nombre_original }}</li>
              @endforeach
            </ul>
          </div>
          @endif
        </td></tr>

        <!-- Botón de acción -->
        <tr><td style="padding:8px 40px 28px;text-align:center;">
          <a href="{{ config('app.url', 'http://referencia.test') }}/solicitudes-referencia" style="display:inline-block;background:linear-gradient(135deg,#0D2D6B,#16468E);color:#ffffff;text-decoration:none;font-size:14px;font-weight:bold;padding:14px 36px;border-radius:10px;box-shadow:0 4px 14px rgba(13,45,107,.25);">&#128203; Revisar solicitud en el panel</a>
        </td></tr>

        <!-- Footer -->
        <tr><td style="background:#f7f9fc;padding:18px 40px;text-align:center;border-top:1px solid #e8edf2;">
          <p style="margin:0;color:#94a3b8;font-size:11px;line-height:1.5;">Notificación automática del Sistema de Referencia Santa Bárbara.<br>Este correo fue enviado porque tiene permisos para gestionar solicitudes de referencia.</p>
        </td></tr>
      </table>
    </td></tr>
  </table>
</body>
</html>

<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Nueva solicitud de referencia</title>
</head>
<body style="margin:0;padding:0;background:#f0f4f8;font-family:Arial,sans-serif;">
  <table width="100%" cellpadding="0" cellspacing="0" style="background:#f0f4f8;padding:32px 0;">
    <tr><td align="center">
      <table width="600" cellpadding="0" cellspacing="0" style="background:#ffffff;border-radius:12px;overflow:hidden;box-shadow:0 2px 8px rgba(0,0,0,.08);">
        <tr><td style="background:#0D2D6B;padding:28px 40px;text-align:center;">
          <p style="margin:0;color:#ffffff;font-size:20px;font-weight:bold;">Clínica Santa Bárbara</p>
          <p style="margin:6px 0 0;color:#a0b8e8;font-size:13px;">Sistema de Referencia — Notificación interna</p>
        </td></tr>
        <tr><td style="background:#fff8e1;padding:16px 40px;border-bottom:2px solid #ffe082;text-align:center;color:#7a5800;font-size:14px;font-weight:bold;">
          Nueva solicitud de referencia pendiente de revisión
        </td></tr>
        <tr><td style="padding:28px 40px;">
          <p style="margin:0 0 18px;color:#555;font-size:14px;line-height:1.6;">La institución <strong>{{ $clinica->nombre }}</strong> envió una nueva solicitud. Revísela desde el panel de solicitudes de referencia.</p>
          <table width="100%" cellpadding="0" cellspacing="0" style="border:1px solid #e0e8f0;border-radius:8px;overflow:hidden;">
            <tr style="background:#f7f9fc;"><td style="padding:10px 16px;color:#888;font-size:13px;width:42%;">Paciente</td><td style="padding:10px 16px;color:#1a1a2e;font-size:13px;font-weight:bold;">{{ $solicitud->primer_nombre }} {{ $solicitud->segundo_nombre }} {{ $solicitud->primer_apellido }} {{ $solicitud->segundo_apellido }}</td></tr>
            <tr><td style="padding:10px 16px;color:#888;font-size:13px;">Documento</td><td style="padding:10px 16px;color:#1a1a2e;font-size:13px;">{{ $solicitud->tipo_documento }} {{ $solicitud->numero_documento }}</td></tr>
            <tr style="background:#f7f9fc;"><td style="padding:10px 16px;color:#888;font-size:13px;">Institución</td><td style="padding:10px 16px;color:#1a1a2e;font-size:13px;">{{ $clinica->nombre }} · NIT {{ $clinica->nit }}</td></tr>
            <tr><td style="padding:10px 16px;color:#888;font-size:13px;">Especialidad</td><td style="padding:10px 16px;color:#1a1a2e;font-size:13px;">{{ $solicitud->especialidad_requerida }}</td></tr>
            <tr style="background:#f7f9fc;"><td style="padding:10px 16px;color:#888;font-size:13px;">Diagnóstico</td><td style="padding:10px 16px;color:#1a1a2e;font-size:13px;">{{ $solicitud->diagnostico }}</td></tr>
            <tr><td style="padding:10px 16px;color:#888;font-size:13px;">Fecha de envío</td><td style="padding:10px 16px;color:#1a1a2e;font-size:13px;">{{ $solicitud->created_at->format('d/m/Y H:i') }}</td></tr>
          </table>
        </td></tr>
        <tr><td style="background:#f7f9fc;padding:20px 40px;text-align:center;border-top:1px solid #e8edf2;color:#aaa;font-size:12px;">Notificación automática del Sistema de Referencia Santa Bárbara.</td></tr>
      </table>
    </td></tr>
  </table>
</body>
</html>

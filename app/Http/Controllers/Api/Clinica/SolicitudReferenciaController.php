<?php

namespace App\Http\Controllers\Api\Clinica;

use App\Http\Controllers\Controller;
use App\Mail\NuevaSolicitudReferenciaInterna;
use App\Models\Clinica;
use App\Models\ClinicaSession;
use App\Models\Notification;
use App\Models\SolicitudReferencia;
use App\Models\SolicitudReferenciaAdjunto;
use App\Models\SolicitudReferenciaEvento;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class SolicitudReferenciaController extends Controller
{
    private function getClinicaId(Request $request): ?int
    {
        $token = $request->bearerToken() ?? $request->header('X-Clinica-Token');

        if (! $token) {
            return null;
        }

        $session = ClinicaSession::where('token', hash('sha256', $token))
            ->where(function ($q): void {
                $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
            })
            ->first();

        return $session?->clinica_id;
    }

    public function index(Request $request): JsonResponse
    {
        $clinicaId = $this->getClinicaId($request);

        if (! $clinicaId) {
            return response()->json(['message' => 'No autenticado'], 401);
        }

        $solicitudes = SolicitudReferencia::where('clinica_id', $clinicaId)
            ->with(['adjuntos', 'eventos'])
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json(['data' => $solicitudes]);
    }

    public function store(Request $request): JsonResponse
    {
        $clinicaId = $this->getClinicaId($request);

        if (! $clinicaId) {
            return response()->json(['message' => 'No autenticado'], 401);
        }

        $validated = $request->validate([
            'fecha' => ['required', 'date'],
            'hora' => ['required', 'date_format:H:i'],
            'primer_nombre' => ['required', 'string', 'max:80'],
            'segundo_nombre' => ['nullable', 'string', 'max:80'],
            'primer_apellido' => ['required', 'string', 'max:80'],
            'segundo_apellido' => ['nullable', 'string', 'max:80'],
            'genero' => ['required', 'in:M,F'],
            'edad' => ['required', 'integer', 'min:0', 'max:120'],
            'tipo_documento' => ['required', 'string', 'max:5'],
            'numero_documento' => ['required', 'string', 'max:30'],
            'eps' => ['required', 'string', 'max:120'],
            'diagnostico' => ['required', 'string', 'max:400'],
            'municipio_capita' => ['required', 'string', 'max:120'],
            'especialidad_requerida' => ['required', 'string', 'max:120'],
            'servicio_ubicacion_actual' => ['required', 'string', 'max:60'],
            'servicio_remision' => ['nullable', 'string', 'max:60'],
            'resumen_historia_clinica' => ['required', 'string'],
            'via_contacto' => ['nullable', 'string', 'max:20'],
            'gestante' => ['nullable', 'boolean'],
            'condicion_especial' => ['nullable', 'string', 'max:250'],
            'observaciones' => ['nullable', 'string'],
            'adjuntos' => ['nullable', 'array', 'max:5'],
            'adjuntos.*' => ['file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'],
        ]);

        $validated['clinica_id'] = $clinicaId;
        $validated['estado'] = 'pendiente';

        $adjuntos = $validated['adjuntos'] ?? [];
        unset($validated['adjuntos']);

        $solicitud = SolicitudReferencia::create($validated);

        foreach ($adjuntos as $adjunto) {
            $ruta = $adjunto->store("solicitudes-referencia/{$solicitud->id}", 'local');

            $solicitud->adjuntos()->create([
                'nombre_original' => $adjunto->getClientOriginalName(),
                'ruta' => $ruta,
                'mime_type' => $adjunto->getMimeType(),
                'tamano' => $adjunto->getSize(),
            ]);
        }

        SolicitudReferenciaEvento::create([
            'solicitud_referencia_id' => $solicitud->id,
            'tipo' => 'creada',
            'titulo' => 'Solicitud enviada',
            'descripcion' => 'La institución envió la solicitud para revisión.',
        ]);

        // Notificar en campana a usuarios con permisos de referencia
        $clinica = Clinica::find($clinicaId);
        $paciente = trim("{$validated['primer_nombre']} {$validated['primer_apellido']}");
        $this->notificarUsuarios(
            titulo: 'Nueva solicitud de referencia',
            mensaje: "La institución \"{$clinica->nombre}\" solicita referencia para el paciente {$paciente} — {$validated['especialidad_requerida']}.",
            tipo: 'warning',
            link: '/solicitudes-referencia',
        );

        $destinatarios = User::permission('clinicas.view')
            ->where('is_active', true)
            ->whereNotNull('email')
            ->pluck('email')
            ->all();

        if ($destinatarios === []) {
            $destinatarios = [config('mail.referencia_interna', env('MAIL_REFERENCIA_INTERNA', 'referencia@cacsantabarbara.co'))];
        }

        Mail::to($destinatarios)->send(new NuevaSolicitudReferenciaInterna($clinica, $solicitud));

        return response()->json(['data' => $solicitud->load(['adjuntos', 'eventos']), 'message' => 'Solicitud enviada correctamente'], 201);
    }

    private function notificarUsuarios(string $titulo, string $mensaje, string $tipo = 'info', ?string $link = null): void
    {
        $usuarios = User::permission('clinicas.view')->where('is_active', true)->get();

        $now = now();
        $rows = $usuarios->map(fn (User $u) => [
            'user_id' => $u->id,
            'type' => $tipo,
            'title' => $titulo,
            'message' => $mensaje,
            'link' => $link,
            'read_at' => null,
            'created_at' => $now,
            'updated_at' => $now,
        ])->all();

        if ($rows) {
            Notification::insert($rows);
        }
    }

    public function show(int $id, Request $request): JsonResponse
    {
        $clinicaId = $this->getClinicaId($request);

        $solicitud = SolicitudReferencia::where('clinica_id', $clinicaId)
            ->with(['adjuntos', 'eventos'])
            ->findOrFail($id);

        return response()->json(['data' => $solicitud]);
    }

    public function descargarAdjunto(int $solicitudId, int $adjuntoId, Request $request): BinaryFileResponse
    {
        $clinicaId = $this->getClinicaId($request);

        if (! $clinicaId) {
            abort(401);
        }

        $solicitud = SolicitudReferencia::where('clinica_id', $clinicaId)->findOrFail($solicitudId);

        $adjunto = SolicitudReferenciaAdjunto::where('solicitud_referencia_id', $solicitud->id)
            ->findOrFail($adjuntoId);

        $path = storage_path('app/private/'.$adjunto->ruta);

        if (! file_exists($path)) {
            abort(404, 'Archivo no encontrado');
        }

        return response()->file($path, [
            'Content-Type' => $adjunto->mime_type,
            'Content-Disposition' => 'inline; filename="'.$adjunto->nombre_original.'"',
        ]);
    }
}

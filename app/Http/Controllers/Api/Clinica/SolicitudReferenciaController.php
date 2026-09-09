<?php

namespace App\Http\Controllers\Api\Clinica;

use App\Http\Controllers\Controller;
use App\Mail\NuevaSolicitudReferenciaInterna;
use App\Models\CiudadGomedisys;
use App\Models\Clinica;
use App\Models\ClinicaSession;
use App\Models\DiagnosticoCie10;
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
            ->with(['adjuntos', 'eventos', 'diagnosticos'])
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json(['data' => $solicitudes]);
    }

    /**
     * Busca en el catálogo real de diagnósticos CIE-10 (importado directo
     * de la tabla `diagnostics` de Gomedisys — ver migración
     * create_diagnosticos_cie10_table) por código o por descripción. Antes
     * el formulario filtraba una lista fija de 332 códigos de categoría
     * incompletos (ej. "B34" en vez de "B34.2"), que Gomedisys nunca
     * reconocía — ahora busca contra los ~12.500 códigos específicos que sí
     * existen allá.
     */
    public function buscarDiagnosticosCie10(Request $request): JsonResponse
    {
        $clinicaId = $this->getClinicaId($request);

        if (! $clinicaId) {
            return response()->json(['message' => 'No autenticado'], 401);
        }

        $buscar = trim((string) $request->query('buscar', ''));

        if ($buscar === '') {
            return response()->json(['data' => []]);
        }

        $resultados = DiagnosticoCie10::query()
            ->where(function ($q) use ($buscar) {
                $q->where('codigo', 'like', $buscar.'%')
                    ->orWhere('descripcion', 'like', '%'.$buscar.'%');
            })
            ->orderByRaw('CASE WHEN codigo LIKE ? THEN 0 ELSE 1 END', [$buscar.'%'])
            ->orderBy('descripcion')
            ->limit(20)
            ->get(['codigo', 'descripcion']);

        return response()->json(['data' => $resultados]);
    }

    /**
     * Catálogo completo de municipios de Gomedisys (importado directo de su
     * tabla `generalPoliticalDivisions` — ver migración
     * create_ciudades_gomedisys_table), para la lista desplegable del campo
     * "Municipio". Se manda completo (~1.100 filas) para que el formulario
     * solo permita elegir de la lista real en vez de escribir texto libre,
     * que podía tener errores de formato o nombres que Gomedisys no
     * reconocía al buscar.
     */
    public function listarCiudadesGomedisys(Request $request): JsonResponse
    {
        $clinicaId = $this->getClinicaId($request);

        if (! $clinicaId) {
            return response()->json(['message' => 'No autenticado'], 401);
        }

        $ciudades = CiudadGomedisys::orderBy('nombre')->get(['nombre']);

        return response()->json(['data' => $ciudades]);
    }

    public function store(Request $request): JsonResponse
    {
        $clinicaId = $this->getClinicaId($request);

        if (! $clinicaId) {
            return response()->json(['message' => 'No autenticado'], 401);
        }

        $validated = $request->validate([
            'fecha' => ['nullable', 'date'],
            'hora' => ['nullable', 'date_format:H:i'],
            'primer_nombre' => ['required', 'string', 'max:80'],
            'segundo_nombre' => ['nullable', 'string', 'max:80'],
            'primer_apellido' => ['required', 'string', 'max:80'],
            'segundo_apellido' => ['nullable', 'string', 'max:80'],
            'genero' => ['required', 'in:M,F'],
            'edad' => ['required', 'integer', 'min:0', 'max:120'],
            'tipo_documento' => ['required', 'string', 'max:5'],
            'numero_documento' => ['required', 'string', 'max:30'],
            'eps' => ['required', 'string', 'max:120'],
            'diagnostico' => ['nullable', 'string', 'max:400'],
            'diagnosticos' => ['nullable', 'array'],
            'diagnosticos.*.codigo_cie10' => ['nullable', 'string', 'max:20'],
            'diagnosticos.*.descripcion' => ['required', 'string', 'max:400'],
            'municipio_capita' => ['required', 'string', 'max:120'],
            // Mismos límites que #addressPatient / #telecomPatient en Gomedisys.
            'direccion_paciente' => ['nullable', 'string', 'max:25'],
            'telefono_paciente' => ['nullable', 'string', 'max:15', 'regex:/^[0-9]*$/'],
            'especialidad_requerida' => ['required', 'string', 'max:120'],
            'servicio_ubicacion_actual' => ['required', 'string', 'max:60'],
            'servicio_remision' => ['nullable', 'string', 'max:60'],
            'quien_remitente' => ['required', 'string', 'max:200'],
            'telefono_contacto' => ['required', 'string', 'max:30'],
            'correo_contacto' => ['required', 'email', 'max:150'],
            'resumen_historia_clinica' => ['required', 'string'],
            'via_contacto' => ['nullable', 'string', 'max:20'],
            'gestante' => ['nullable', 'boolean'],
            'condicion_especial' => ['nullable', 'string', 'max:250'],
            'observaciones' => ['nullable', 'string'],
            'adjuntos' => ['nullable', 'array', 'max:10'],
            'adjuntos.*' => ['file', 'mimes:pdf,jpg,jpeg,png,gif,webp,bmp,tiff,svg,doc,docx,xls,xlsx,ppt,pptx,txt,csv,zip,rar', 'max:10240'],
        ]);

        $validated['fecha'] ??= now()->toDateString();
        $validated['hora'] ??= now()->format('H:i');
        $validated['clinica_id'] = $clinicaId;
        $validated['estado'] = 'pendiente';
        // Normaliza el espacio alrededor de la coma ("Palmira,Valle del
        // Cauca" → "Palmira, Valle del Cauca") — Gomedisys siempre muestra
        // sus municipios con ese formato exacto, y sin el espacio la
        // extensión que autocompleta el registro no encuentra la opción
        // aunque sí exista en su catálogo.
        $validated['municipio_capita'] = trim(preg_replace('/\s*,\s*/', ', ', $validated['municipio_capita']));

        $adjuntos = $validated['adjuntos'] ?? [];
        $diagnosticos = $validated['diagnosticos'] ?? [];
        unset($validated['adjuntos'], $validated['diagnosticos']);

        $solicitud = SolicitudReferencia::create($validated);

        foreach ($diagnosticos as $dx) {
            $solicitud->diagnosticos()->create([
                'codigo_cie10' => $dx['codigo_cie10'] ?? '',
                'descripcion' => $dx['descripcion'],
            ]);
        }

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
            link: "/solicitudes-referencia?resaltar={$solicitud->id}",
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

        return response()->json(['data' => $solicitud->load(['adjuntos', 'eventos', 'diagnosticos']), 'message' => 'Solicitud enviada correctamente'], 201);
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
            ->with(['adjuntos', 'eventos', 'diagnosticos'])
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

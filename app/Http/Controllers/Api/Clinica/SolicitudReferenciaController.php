<?php

namespace App\Http\Controllers\Api\Clinica;

use App\Http\Controllers\Controller;
use App\Models\Clinica;
use App\Models\Notification;
use App\Models\SolicitudReferencia;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SolicitudReferenciaController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $clinicaId = session('clinica_id');

        if (!$clinicaId) {
            return response()->json(['message' => 'No autenticado'], 401);
        }

        $solicitudes = SolicitudReferencia::where('clinica_id', $clinicaId)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json(['data' => $solicitudes]);
    }

    public function store(Request $request): JsonResponse
    {
        $clinicaId = session('clinica_id');

        if (!$clinicaId) {
            return response()->json(['message' => 'No autenticado'], 401);
        }

        $validated = $request->validate([
            'fecha'                    => ['required', 'date'],
            'hora'                     => ['required', 'date_format:H:i'],
            'primer_nombre'            => ['required', 'string', 'max:80'],
            'segundo_nombre'           => ['nullable', 'string', 'max:80'],
            'primer_apellido'          => ['required', 'string', 'max:80'],
            'segundo_apellido'         => ['nullable', 'string', 'max:80'],
            'genero'                   => ['required', 'in:M,F'],
            'edad'                     => ['required', 'integer', 'min:0', 'max:120'],
            'tipo_documento'           => ['required', 'string', 'max:5'],
            'numero_documento'         => ['required', 'string', 'max:30'],
            'eps'                      => ['required', 'string', 'max:120'],
            'diagnostico'              => ['required', 'string', 'max:400'],
            'municipio_capita'         => ['required', 'string', 'max:120'],
            'especialidad_requerida'   => ['required', 'string', 'max:120'],
            'servicio_ubicacion_actual'=> ['required', 'string', 'max:60'],
            'servicio_remision'        => ['nullable', 'string', 'max:60'],
            'resumen_historia_clinica' => ['required', 'string'],
            'via_contacto'             => ['nullable', 'string', 'max:20'],
            'gestante'                 => ['nullable', 'boolean'],
            'condicion_especial'       => ['nullable', 'string', 'max:250'],
            'observaciones'            => ['nullable', 'string'],
        ]);

        $validated['clinica_id'] = $clinicaId;
        $validated['estado'] = 'pendiente';

        $solicitud = SolicitudReferencia::create($validated);

        // Notificar en campana a usuarios con permisos de referencia
        $clinica = Clinica::find($clinicaId);
        $paciente = trim("{$validated['primer_nombre']} {$validated['primer_apellido']}");
        $this->notificarUsuarios(
            titulo: 'Nueva solicitud de referencia',
            mensaje: "La institución \"{$clinica->nombre}\" solicita referencia para el paciente {$paciente} — {$validated['especialidad_requerida']}.",
            tipo: 'warning',
            link: '/solicitudes-referencia',
        );

        return response()->json(['data' => $solicitud, 'message' => 'Solicitud enviada correctamente'], 201);
    }

    private function notificarUsuarios(string $titulo, string $mensaje, string $tipo = 'info', ?string $link = null): void
    {
        $usuarios = User::permission('clinicas.view')->where('is_active', true)->get();

        $now = now();
        $rows = $usuarios->map(fn (User $u) => [
            'user_id'    => $u->id,
            'type'       => $tipo,
            'title'      => $titulo,
            'message'    => $mensaje,
            'link'       => $link,
            'read_at'    => null,
            'created_at' => $now,
            'updated_at' => $now,
        ])->all();

        if ($rows) {
            Notification::insert($rows);
        }
    }

    public function show(int $id): JsonResponse
    {
        $clinicaId = session('clinica_id');

        $solicitud = SolicitudReferencia::where('clinica_id', $clinicaId)->findOrFail($id);

        return response()->json(['data' => $solicitud]);
    }
}

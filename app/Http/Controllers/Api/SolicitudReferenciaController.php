<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use App\Models\SolicitudReferencia;
use App\Models\SolicitudReferenciaAdjunto;
use App\Models\SolicitudReferenciaEvento;
use App\Models\User;
use App\Services\SqlQueryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Throwable;

class SolicitudReferenciaController extends Controller
{
    /** Solo lo que sigue en curso — lo ya resuelto (completado/negado) vive en historico(). */
    public function index(): JsonResponse
    {
        abort_unless(request()->user()->hasPermission('clinicas.view'), Response::HTTP_FORBIDDEN);

        $solicitudes = SolicitudReferencia::with(['clinica', 'adjuntos', 'diagnosticos', 'eventos'])
            ->whereIn('estado', ['pendiente', 'en_espera'])
            ->orderByRaw("FIELD(estado, 'pendiente', 'en_espera')")
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json(['data' => $solicitudes]);
    }

    /** Histórico de solicitudes ya resueltas (completadas o negadas). */
    public function historico(): JsonResponse
    {
        abort_unless(request()->user()->hasPermission('clinicas.view'), Response::HTTP_FORBIDDEN);

        $solicitudes = SolicitudReferencia::with(['clinica', 'adjuntos', 'diagnosticos', 'eventos'])
            ->whereIn('estado', ['completado', 'negado'])
            ->orderBy('updated_at', 'desc')
            ->get();

        return response()->json(['data' => $solicitudes]);
    }

    public function show(SolicitudReferencia $solicitud): JsonResponse
    {
        abort_unless(request()->user()->hasPermission('clinicas.view'), Response::HTTP_FORBIDDEN);

        return response()->json(['data' => $solicitud->load(['clinica', 'diagnosticos', 'eventos'])]);
    }

    public function aceptar(Request $request, SolicitudReferencia $solicitud): JsonResponse
    {
        abort_unless($request->user()->hasPermission('clinicas.view'), Response::HTTP_FORBIDDEN);

        if ($solicitud->estado !== 'pendiente') {
            return response()->json(['message' => 'Solo se pueden aceptar solicitudes pendientes.'], 422);
        }

        $validated = $request->validate([
            'hora_respuesta' => ['required', 'date_format:H:i'],
            'nombre_quien_responde' => ['required', 'string', 'max:200'],
            // Código real que Gomedisys generó al registrar al paciente allá
            // (ya no se inventa acá — ver flujo: primero se registra en
            // Gomedisys, luego se acepta en la app con ese código).
            'codigo_aceptacion' => ['required', 'string', 'max:20', Rule::unique('solicitudes_referencia', 'codigo_aceptacion')],
            'hora_llegada' => ['required', 'date_format:H:i'],
            'lugar_llegada' => ['required', 'string', 'max:150'],
            'observaciones_respuesta' => ['nullable', 'string'],
        ]);

        // Aceptar y marcar en espera de llegada son un solo paso — no existe
        // un estado "aceptado" intermedio.
        $solicitud->update([
            'estado' => 'en_espera',
            'codigo_aceptacion' => $validated['codigo_aceptacion'],
            'hora_respuesta' => $validated['hora_respuesta'],
            'nombre_quien_responde' => $validated['nombre_quien_responde'],
            'hora_llegada' => $validated['hora_llegada'],
            'lugar_llegada' => $validated['lugar_llegada'],
            'observaciones_respuesta' => $validated['observaciones_respuesta'] ?? null,
        ]);

        $solicitud->load('clinica');

        SolicitudReferenciaEvento::create([
            'solicitud_referencia_id' => $solicitud->id,
            'tipo' => 'en_espera',
            'titulo' => 'Paciente en espera',
            'descripcion' => 'La solicitud fue aceptada y el paciente está en espera de llegada.',
        ]);

        if ($solicitud->clinica?->email) {
            Mail::send(
                'emails.solicitud-en-espera',
                ['solicitud' => $solicitud],
                function ($message) use ($solicitud) {
                    $message->to($solicitud->clinica->email)
                        ->subject("Solicitud de referencia ACEPTADA - {$solicitud->primer_nombre} {$solicitud->primer_apellido}");
                }
            );
        }

        $this->notificarClinica(
            $solicitud,
            'Solicitud aceptada',
            "La solicitud de {$solicitud->primer_nombre} {$solicitud->primer_apellido} fue aceptada y quedó en espera de llegada. Código: {$solicitud->codigo_aceptacion}.",
            'success',
        );

        return response()->json(['data' => $solicitud, 'message' => 'Solicitud aceptada: el paciente quedó en espera de llegada']);
    }

    public function negar(Request $request, SolicitudReferencia $solicitud): JsonResponse
    {
        abort_unless($request->user()->hasPermission('clinicas.view'), Response::HTTP_FORBIDDEN);

        if (in_array($solicitud->estado, ['negado', 'completado'], true)) {
            return response()->json(['message' => 'Esta solicitud ya no se puede negar.'], 422);
        }

        $validated = $request->validate([
            'hora_respuesta' => ['required', 'date_format:H:i'],
            'motivo_negacion' => ['required', 'string', 'max:250'],
            'nombre_quien_responde' => ['required', 'string', 'max:200'],
            'observaciones_respuesta' => ['nullable', 'string'],
        ]);

        $solicitud->update([
            'estado' => 'negado',
            'hora_respuesta' => $validated['hora_respuesta'],
            'motivo_negacion' => $validated['motivo_negacion'],
            'nombre_quien_responde' => $validated['nombre_quien_responde'],
            'observaciones_respuesta' => $validated['observaciones_respuesta'] ?? null,
        ]);

        $solicitud->load('clinica');

        if ($solicitud->clinica?->email) {
            Mail::send(
                'emails.solicitud-negada',
                ['solicitud' => $solicitud],
                function ($message) use ($solicitud) {
                    $message->to($solicitud->clinica->email)
                        ->subject("Solicitud de referencia NEGADA - {$solicitud->primer_nombre} {$solicitud->primer_apellido}");
                }
            );
        }

        $this->notificarClinica(
            $solicitud,
            'Solicitud negada',
            "La solicitud de {$solicitud->primer_nombre} {$solicitud->primer_apellido} fue negada. Motivo: {$validated['motivo_negacion']}.",
            'error',
        );

        return response()->json(['data' => $solicitud, 'message' => 'Solicitud negada']);
    }

    /**
     * Deshace la aceptación y devuelve la solicitud a pendiente.
     *
     * Una vez negada, la solicitud queda definitiva a propósito (el personal
     * debe revisar bien antes de negar) — por eso 'negado' no está entre los
     * estados que se pueden devolver a pendiente.
     */
    public function pendiente(Request $request, SolicitudReferencia $solicitud): JsonResponse
    {
        abort_unless($request->user()->hasPermission('clinicas.view'), Response::HTTP_FORBIDDEN);

        if ($solicitud->estado !== 'en_espera') {
            return response()->json(['message' => 'Esta solicitud no se puede devolver a pendiente.'], 422);
        }

        $validated = $request->validate([
            'observaciones_respuesta' => ['required', 'string'],
            'nombre_quien_responde' => ['required', 'string', 'max:200'],
        ]);

        $solicitud->update([
            'estado' => 'pendiente',
            'observaciones_respuesta' => $validated['observaciones_respuesta'],
            'nombre_quien_responde' => $validated['nombre_quien_responde'],
        ]);

        SolicitudReferenciaEvento::create([
            'solicitud_referencia_id' => $solicitud->id,
            'tipo' => 'revertido',
            'titulo' => 'Devuelta a pendiente',
            'descripcion' => "Se deshizo la decisión anterior. Motivo: {$validated['observaciones_respuesta']}",
        ]);

        $this->notificarClinica(
            $solicitud,
            'Solicitud en revisión nuevamente',
            "La solicitud de {$solicitud->primer_nombre} {$solicitud->primer_apellido} volvió a quedar pendiente de revisión.",
            'warning',
        );

        return response()->json(['data' => $solicitud, 'message' => 'Solicitud devuelta a pendiente']);
    }


    public function completado(Request $request, SolicitudReferencia $solicitud): JsonResponse
    {
        abort_unless($request->user()->hasPermission('clinicas.view'), Response::HTTP_FORBIDDEN);

        if ($solicitud->estado !== 'en_espera') {
            return response()->json(['message' => 'Solo se pueden completar solicitudes que están en espera de llegada.'], 422);
        }

        // numero_ingreso ya no es opcional: la única puerta a "completado" es
        // consultarIngreso() + confirmación del paciente real en Gomedisys —
        // ya no existe un botón "Completar" manual sin ese número verificado.
        $validated = $request->validate([
            'observaciones_respuesta' => ['nullable', 'string'],
            'numero_ingreso' => ['required', 'integer'],
        ]);

        $solicitud->update([
            'estado' => 'completado',
            'numero_ingreso' => $validated['numero_ingreso'],
            'observaciones_respuesta' => $validated['observaciones_respuesta'] ?? null,
        ]);

        $solicitud->load('clinica');

        SolicitudReferenciaEvento::create([
            'solicitud_referencia_id' => $solicitud->id,
            'tipo' => 'completado',
            'titulo' => 'Paciente atendido',
            'descripcion' => "El paciente llegó a la institución (ingreso Gomedisys #{$validated['numero_ingreso']}) y fue atendido.",
        ]);

        if ($solicitud->clinica?->email) {
            Mail::send(
                'emails.solicitud-completada',
                ['solicitud' => $solicitud],
                function ($message) use ($solicitud) {
                    $message->to($solicitud->clinica->email)
                        ->subject("Solicitud de referencia COMPLETADA - {$solicitud->primer_nombre} {$solicitud->primer_apellido}");
                }
            );
        }

        $this->notificarClinica(
            $solicitud,
            'Paciente atendido',
            "La solicitud de {$solicitud->primer_nombre} {$solicitud->primer_apellido} fue completada: el paciente fue atendido.",
            'success',
        );

        $this->notificarPersonal(
            'Paciente atendido',
            "{$solicitud->primer_nombre} {$solicitud->primer_apellido} llegó a la institución y fue atendido.",
            'success',
        );

        return response()->json(['data' => $solicitud, 'message' => 'Solicitud completada']);
    }

    /**
     * Consulta directo en la base de datos de Gomedisys si el paciente de
     * esta solicitud ya tiene un número de ingreso (o sea, si ya llegó y
     * fue admitido en la institución). Solo CONSULTA — no completa la
     * solicitud automáticamente. Devuelve los datos del paciente tal como
     * están en Gomedisys para que el personal los revise y confirme que es
     * la persona correcta antes de marcar la solicitud como completada
     * (ver completado()).
     */
    public function consultarIngreso(SolicitudReferencia $solicitud, SqlQueryService $sqlQueryService): JsonResponse
    {
        abort_unless(request()->user()->hasPermission('clinicas.view'), Response::HTTP_FORBIDDEN);

        if ($solicitud->estado !== 'en_espera') {
            return response()->json(['message' => 'Solo se puede consultar el ingreso de solicitudes en espera.'], 422);
        }

        try {
            $fila = $sqlQueryService->executeQueryFromFileFirst('consultaIngresoPaciente', [$solicitud->numero_documento]);
        } catch (Throwable $e) {
            Log::error('Error consultando ingreso en Gomedisys', ['solicitud_id' => $solicitud->id, 'error' => $e->getMessage()]);

            return response()->json(['message' => 'No se pudo conectar con la base de datos de Gomedisys: ' . $e->getMessage()], 502);
        }

        if (! $fila || ! $fila->admission_number) {
            return response()->json(['ingreso' => false, 'message' => 'El paciente todavía no tiene número de ingreso en Gomedisys.']);
        }

        return response()->json([
            'ingreso' => true,
            'paciente' => [
                'fullname' => $fila->fullname,
                'identification_number' => $fila->identification_number,
                'document_type' => $fila->document_type,
                'sex' => $fila->sex,
                'birthdate' => $fila->birthdate,
                'admission_number' => $fila->admission_number,
                'admission_date' => $fila->admission_date,
            ],
            'message' => "Se encontró ingreso #{$fila->admission_number} en Gomedisys. Confirme que es el paciente correcto.",
        ]);
    }

    private function notificarClinica(SolicitudReferencia $solicitud, string $titulo, string $mensaje, string $tipo = 'info'): void
    {
        if (! $solicitud->clinica_id) {
            return;
        }

        Notification::create([
            'clinica_id' => $solicitud->clinica_id,
            'type' => $tipo,
            'title' => $titulo,
            'message' => $mensaje,
            'link' => '/clinica/historial',
        ]);
    }

    /** Avisa a la campanita interna (todo el personal que puede ver solicitudes) que un paciente llegó. */
    private function notificarPersonal(string $titulo, string $mensaje, string $tipo = 'info'): void
    {
        $usuarios = User::permission('clinicas.view')->where('is_active', true)->get();

        $now = now();
        $rows = $usuarios->map(fn (User $u) => [
            'user_id' => $u->id,
            'type' => $tipo,
            'title' => $titulo,
            'message' => $mensaje,
            // Un paciente atendido pasa a 'completado', que ya no vive en
            // /solicitudes-referencia — el enlace debe llevar al histórico.
            'link' => '/historico',
            'read_at' => null,
            'created_at' => $now,
            'updated_at' => $now,
        ])->all();

        if ($rows) {
            Notification::insert($rows);
        }
    }

    public function descargarAdjunto(SolicitudReferencia $solicitud, SolicitudReferenciaAdjunto $adjunto): BinaryFileResponse
    {
        abort_unless(request()->user()->hasPermission('clinicas.view'), Response::HTTP_FORBIDDEN);

        if ($adjunto->solicitud_referencia_id !== $solicitud->id) {
            abort(404);
        }

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

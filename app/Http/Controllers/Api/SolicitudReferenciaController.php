<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SolicitudReferencia;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class SolicitudReferenciaController extends Controller
{
    public function index(): JsonResponse
    {
        $solicitudes = SolicitudReferencia::with('clinica')
            ->orderByRaw("FIELD(estado, 'pendiente', 'aceptado', 'negado')")
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json(['data' => $solicitudes]);
    }

    public function show(SolicitudReferencia $solicitud): JsonResponse
    {
        return response()->json(['data' => $solicitud->load('clinica')]);
    }

    public function aceptar(Request $request, SolicitudReferencia $solicitud): JsonResponse
    {
        $validated = $request->validate([
            'hora_respuesta'          => ['required', 'date_format:H:i'],
            'nombre_quien_responde'   => ['required', 'string', 'max:200'],
            'numero_ingreso'          => ['nullable', 'integer'],
            'observaciones_respuesta' => ['nullable', 'string'],
        ]);

        // Generate acceptance code
        $codigo = 'REF' . str_pad($solicitud->id, 8, '0', STR_PAD_LEFT);

        $solicitud->update([
            'estado'                  => 'aceptado',
            'codigo_aceptacion'       => $codigo,
            'hora_respuesta'          => $validated['hora_respuesta'],
            'nombre_quien_responde'   => $validated['nombre_quien_responde'],
            'numero_ingreso'          => $validated['numero_ingreso'] ?? null,
            'observaciones_respuesta' => $validated['observaciones_respuesta'] ?? null,
        ]);

        return response()->json(['data' => $solicitud, 'message' => 'Solicitud aceptada']);
    }

    public function negar(Request $request, SolicitudReferencia $solicitud): JsonResponse
    {
        $validated = $request->validate([
            'hora_respuesta'          => ['required', 'date_format:H:i'],
            'motivo_negacion'         => ['required', 'string', 'max:250'],
            'nombre_quien_responde'   => ['required', 'string', 'max:200'],
            'observaciones_respuesta' => ['nullable', 'string'],
        ]);

        $solicitud->update([
            'estado'                  => 'negado',
            'hora_respuesta'          => $validated['hora_respuesta'],
            'motivo_negacion'         => $validated['motivo_negacion'],
            'nombre_quien_responde'   => $validated['nombre_quien_responde'],
            'observaciones_respuesta' => $validated['observaciones_respuesta'] ?? null,
        ]);

        return response()->json(['data' => $solicitud, 'message' => 'Solicitud negada']);
    }

    public function pendiente(Request $request, SolicitudReferencia $solicitud): JsonResponse
    {
        $validated = $request->validate([
            'observaciones_respuesta' => ['required', 'string'],
            'nombre_quien_responde'   => ['required', 'string', 'max:200'],
        ]);

        $solicitud->update([
            'estado'                  => 'pendiente',
            'observaciones_respuesta' => $validated['observaciones_respuesta'],
            'nombre_quien_responde'   => $validated['nombre_quien_responde'],
        ]);

        return response()->json(['data' => $solicitud, 'message' => 'Solicitud marcada como pendiente']);
    }
}

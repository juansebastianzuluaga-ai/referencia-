<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Clinica;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class ClinicaController extends Controller
{
    public function index(): JsonResponse
    {
        $clinicas = Clinica::orderByRaw("FIELD(estado, 'pendiente', 'activa', 'rechazada')")
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json(['data' => $clinicas]);
    }

    public function aprobar(Clinica $clinica): JsonResponse
    {
        $clinica->update([
            'is_active'       => true,
            'estado'          => 'activa',
            'motivo_rechazo'  => null,
        ]);

        // Notificar a la clínica
        Mail::raw(
            "Estimados representantes de {$clinica->nombre},\n\n" .
            "Su solicitud de registro ha sido APROBADA. Ya puede acceder al Sistema de Referencia en:\n\n" .
            url('/login') . "\n\n" .
            "Ingrese con el NIT: {$clinica->nit}\n\n" .
            "Clínica Santa Bárbara",
            fn($m) => $m->to($clinica->email)
                ->subject('✅ Acceso aprobado — Sistema de Referencia Santa Bárbara')
        );

        return response()->json(['message' => 'Clínica aprobada correctamente.']);
    }

    public function rechazar(Request $request, Clinica $clinica): JsonResponse
    {
        $request->validate([
            'motivo' => ['required', 'string', 'max:500'],
        ]);

        $clinica->update([
            'is_active'      => false,
            'estado'         => 'rechazada',
            'motivo_rechazo' => $request->motivo,
        ]);

        // Notificar a la clínica
        Mail::raw(
            "Estimados representantes de {$clinica->nombre},\n\n" .
            "Lamentamos informarle que su solicitud de registro ha sido RECHAZADA por el siguiente motivo:\n\n" .
            "{$request->motivo}\n\n" .
            "Si considera que esto es un error, comuníquese con el área de referencia.\n\n" .
            "Clínica Santa Bárbara",
            fn($m) => $m->to($clinica->email)
                ->subject('❌ Solicitud rechazada — Sistema de Referencia Santa Bárbara')
        );

        return response()->json(['message' => 'Clínica rechazada correctamente.']);
    }
}

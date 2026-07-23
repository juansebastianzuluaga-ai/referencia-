<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Mail\ClinicaAprobacion;
use App\Mail\ClinicaRechazo;
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
        Mail::to($clinica->email)->send(new ClinicaAprobacion($clinica));

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
        Mail::to($clinica->email)->send(new ClinicaRechazo($clinica, $request->motivo));

        return response()->json(['message' => 'Clínica rechazada correctamente.']);
    }
}

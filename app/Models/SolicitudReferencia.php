<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class SolicitudReferencia extends Model
{
    use SoftDeletes;

    protected $table = 'solicitudes_referencia';

    protected $fillable = [
        'clinica_id',
        'fecha',
        'hora',
        'primer_nombre',
        'segundo_nombre',
        'primer_apellido',
        'segundo_apellido',
        'genero',
        'edad',
        'tipo_documento',
        'numero_documento',
        'eps',
        'diagnostico',
        'municipio_capita',
        'especialidad_requerida',
        'servicio_ubicacion_actual',
        'servicio_remision',
        'resumen_historia_clinica',
        'via_contacto',
        'gestante',
        'condicion_especial',
        'observaciones',
        'estado',
        'codigo_aceptacion',
        'hora_respuesta',
        'motivo_negacion',
        'motivo_no_ingreso',
        'numero_ingreso',
        'nombre_quien_responde',
        'observaciones_respuesta',
    ];

    protected $casts = [
        'fecha' => 'date',
        'gestante' => 'boolean',
    ];

    public function clinica(): BelongsTo
    {
        return $this->belongsTo(Clinica::class);
    }

    protected static function booted(): void
    {
        static::creating(function (SolicitudReferencia $solicitud) {
            // Auto-generate codigo_aceptacion on create (will be overwritten when accepted)
            // Leave null until accepted by internal staff
        });
    }
}

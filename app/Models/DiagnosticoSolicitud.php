<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DiagnosticoSolicitud extends Model
{
    protected $table = 'diagnosticos_solicitud';

    protected $fillable = [
        'solicitud_referencia_id',
        'codigo_cie10',
        'descripcion',
    ];

    public function solicitud(): BelongsTo
    {
        return $this->belongsTo(SolicitudReferencia::class, 'solicitud_referencia_id');
    }
}

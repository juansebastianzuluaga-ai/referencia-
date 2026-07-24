<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SolicitudReferenciaAdjunto extends Model
{
    protected $fillable = [
        'solicitud_referencia_id',
        'nombre_original',
        'ruta',
        'mime_type',
        'tamano',
    ];

    public function solicitud(): BelongsTo
    {
        return $this->belongsTo(SolicitudReferencia::class, 'solicitud_referencia_id');
    }
}

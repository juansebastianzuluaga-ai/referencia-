<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SolicitudReferenciaEvento extends Model
{
    protected $fillable = [
        'solicitud_referencia_id',
        'tipo',
        'titulo',
        'descripcion',
    ];

    public function solicitud(): BelongsTo
    {
        return $this->belongsTo(SolicitudReferencia::class, 'solicitud_referencia_id');
    }
}

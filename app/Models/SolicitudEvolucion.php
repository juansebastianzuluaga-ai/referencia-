<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SolicitudEvolucion extends Model
{
    protected $table = 'solicitud_evoluciones';

    protected $fillable = [
        'solicitud_referencia_id',
        'nota',
    ];

    public function solicitud(): BelongsTo
    {
        return $this->belongsTo(SolicitudReferencia::class, 'solicitud_referencia_id');
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Clinica extends Model
{
    protected $fillable = [
        'nit',
        'nombre',
        'razon_social',
        'email',
        'telefono',
        'direccion',
        'ciudad',
        'departamento',
        'representante_legal',
        'cedula_representante',
        'observaciones',
        'especialidades',
        'logo_path',
        'is_active',
        'estado',
        'motivo_rechazo',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'especialidades' => 'array',
    ];

    public function authTokens(): HasMany
    {
        return $this->hasMany(ClinicaAuthToken::class);
    }
}

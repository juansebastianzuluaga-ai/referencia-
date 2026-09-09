<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CiudadGomedisys extends Model
{
    protected $table = 'ciudades_gomedisys';

    protected $fillable = [
        'id_political_division',
        'municipio',
        'departamento',
        'nombre',
    ];
}

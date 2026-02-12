<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Estacion extends Model
{
    protected $table = 'estaciones';
    protected $primaryKey = 'id_estacion';
    public $timestamps = false;

    protected $fillable = [
        'id_operador', 'nombre', 'telefono', 'correo',
        'pais', 'departamento', 'municipio', 'direccion',
        'coordenada_1', 'coordenada_2', 'activa'
    ];
}

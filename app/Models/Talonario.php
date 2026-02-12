<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Talonario extends Model
{
    protected $table = 'talonarios';
    protected $primaryKey = 'id_talonario';
    public $timestamps = true; // tu tabla sí tiene created_at y updated_at

    protected $fillable = [
        'numero_talonario', 'numero_inicio', 'numero_fin',
        'cantidad_numeros', 'valor_talonario', 'valor_numero',
        'id_estacion', 'asignado_en', 'estado'
    ];
}

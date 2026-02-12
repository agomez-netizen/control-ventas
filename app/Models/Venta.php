<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Venta extends Model
{
    protected $fillable = [
        'estacion',
        'talonario_numero',
        'banco',
        'fecha',
        'no_boleta',
        'nota',
        'monto_en_boleta',
        'monto_calculado',
    ];

    protected $casts = [
        'fecha' => 'date',
        'monto_en_boleta' => 'boolean',
        'monto_calculado' => 'decimal:2',
    ];
}

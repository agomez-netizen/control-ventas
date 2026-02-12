<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;

class Usuario extends Authenticatable
{
    protected $table = 'usuarios';
    protected $primaryKey = 'id_usuario';
    public $timestamps = false;

    protected $fillable = [
        'nombre', 'apellido', 'telefono', 'usuario', 'pass',
        'id_rol', 'id_tm', 'id_operador', 'estado'
    ];

    protected $hidden = ['pass'];

    // Tu campo de password se llama "pass"
    public function getAuthPassword()
    {
        return $this->pass;
    }

    public function rol()
    {
        return $this->belongsTo(Rol::class, 'id_rol', 'id_rol');
    }
}

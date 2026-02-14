<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AsignacionTalonariosController extends Controller
{
    public function index()
    {
        // Operadores
        $operadores = DB::table('usuarios as u')
            ->join('roles as r', 'r.id_rol', '=', 'u.id_rol')
            ->whereIn(DB::raw('UPPER(TRIM(r.nombre))'), ['OP', 'OPERADOR', 'OPERADORES'])
            ->select('u.id_usuario', DB::raw("CONCAT(u.nombre,' ',u.apellido,' (',u.usuario,')') as label"))
            ->orderBy('u.nombre')
            ->get();

        // Estaciones
        $estaciones = DB::table('estaciones')
            ->select('id_estacion', 'nombre', 'id_operador')
            ->orderBy('nombre')
            ->get();

        return view('asignaciones.talonarios.index', compact('operadores', 'estaciones'));
    }

    public function estacionesByOperador($id_operador)
    {
        $rows = DB::table('estaciones')
            ->where('id_operador', (int)$id_operador)
            ->select('id_estacion', 'nombre')
            ->orderBy('nombre')
            ->get();

        return response()->json($rows);
    }


public function store(Request $request)
{
    $request->validate([
        'id_estacion' => ['required','integer'],
        'tal_del'     => ['required','integer','min:1'],
        'tal_al'      => ['required','integer','min:1'],
        'numero_del'  => ['required','integer','min:1'],
    ]);

    $idEstacion = (int)$request->id_estacion;
    $talDel     = (int)$request->tal_del;
    $talAl      = (int)$request->tal_al;
    $numDel     = (int)$request->numero_del;

    if ($talAl < $talDel) {
        return back()->withInput()->with('error', 'Tal al no puede ser menor que Tal del.');
    }

    // Valores fijos del negocio
    $NUMS_POR_TAL = 25;
    $VALOR_NUMERO = 20.00;
    $VALOR_TAL    = 500.00; // (o $NUMS_POR_TAL * $VALOR_NUMERO)

    $cantidad = ($talAl - $talDel + 1);
    $cantNums = $cantidad * $NUMS_POR_TAL;
    $numAl    = $numDel + $cantNums - 1;

    // 1) choque por numero_talonario (UNIQUE)
    $existeNumeroTalonario = DB::table('talonarios')
        ->whereBetween('numero_talonario', [$talDel, $talAl])
        ->exists();

    if ($existeNumeroTalonario) {
        return back()->withInput()->with('error',
            "Ya existen talonarios en el rango {$talDel}-{$talAl}. (numero_talonario es UNIQUE)"
        );
    }

    // 2) choque por rango de números en la estación
    $existeOverlap = DB::table('talonarios')
        ->where('id_estacion', $idEstacion)
        ->where(function($q) use ($numDel, $numAl) {
            $q->where('numero_inicio', '<=', $numAl)
              ->where('numero_fin', '>=', $numDel);
        })
        ->exists();

    if ($existeOverlap) {
        return back()->withInput()->with('error',
            "Ese bloque de números {$numDel}-{$numAl} se cruza con talonarios existentes en esa estación."
        );
    }

    // Crear talonarios consecutivos, cada uno con 25 números
    $talonarios = [];
    $cursor = $numDel;

    for ($t = 0; $t < $cantidad; $t++) {
        $ini = $cursor;
        $fin = $cursor + $NUMS_POR_TAL - 1;

        $talonarios[] = [
            'numero_talonario' => $talDel + $t,
            'numero_inicio'    => $ini,
            'numero_fin'       => $fin,
            'cantidad_numeros' => $NUMS_POR_TAL,
            'valor_talonario'  => $VALOR_TAL,
            'valor_numero'     => $VALOR_NUMERO,
            'id_estacion'      => $idEstacion,
            'asignado_en'      => now(),
            'estado'           => 'ASIGNADO',
            'created_at'       => now(),
            'updated_at'       => now(),
        ];

        $cursor = $fin + 1;
    }

    DB::table('talonarios')->insert($talonarios);

    return back()->with('ok',
        "Asignación creada ✅ Talonarios {$talDel}-{$talAl} con números {$numDel}-{$numAl} (25 números c/u, Q500 c/u)."
    );
}




}

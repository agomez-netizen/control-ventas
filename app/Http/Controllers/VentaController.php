<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class VentaController extends Controller
{
    /**
     * Mostrar la plantilla (solo vista).
     */
    public function create()
    {
        // Datos fake / mock (solo para la plantilla)
        $tm = 'Juan López';

        $operadores = [
            'Emilio Aguilar',
            'Andrea Morales',
            'María Méndez',
        ];

        $estaciones = [
            'Shell Antigua',
            'Shell Petén',
            'Shell Flores',
            'Shell Izabal',
        ];

        $talonarios = [
            ['numero' => 1541, 'estado' => 'Liquidado'],
            ['numero' => 1542, 'estado' => 'En proceso'],
            ['numero' => 1543, 'estado' => 'Perdido'],
            ['numero' => 1555, 'estado' => ''],
        ];

        $bancos = [
            'Banrural',
            'Banco Industrial',
            'G&T',
        ];

        $montoCalculado = 200.00;

        return view('ventas.create', compact(
            'tm',
            'operadores',
            'estaciones',
            'talonarios',
            'bancos',
            'montoCalculado'
        ));
    }

    /**
     * Simula guardar (NO guarda nada).
     */
    public function store(Request $request)
    {
        // Solo para ver qué llega del formulario
        return back()->with([
            'debug' => $request->all(),
            'success' => '🧪 Plantilla enviada correctamente (modo maqueta)'
        ]);
    }
}

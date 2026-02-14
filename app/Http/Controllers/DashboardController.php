<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $u = session('user') ?? [];

        $userId  = (int)($u['id_usuario'] ?? $u['id'] ?? 0);
        $nombre  = trim(($u['nombre'] ?? 'Usuario') . ' ' . ($u['apellido'] ?? ''));
        $rolName = strtoupper(trim($u['rol'] ?? $u['nombre_rol'] ?? ''));
        $rolId   = (int)($u['id_rol'] ?? 0);

        $q = trim((string)$request->get('q', ''));

        $isAdmin = ($rolName === 'ADMIN') || ($rolId === 1);
        $isTM    = ($rolName === 'TM');

        // 1) Operadores visibles según rol
        $operadoresQuery = DB::table('usuarios as op')
            ->select('op.id_usuario')
            ->where('op.estado', 1);

        if (!$isAdmin && $isTM) {
            $operadoresQuery->where('op.id_tm', $userId);
        } elseif (!$isAdmin && !$isTM) {
            $operadoresQuery->where('op.id_usuario', $userId);
        }

        $operadorIds = $operadoresQuery->pluck('id_usuario')->toArray();

        // 2) Estaciones + métricas por estación
        $estaciones = DB::table('estaciones as e')
            ->join('usuarios as op', 'op.id_usuario', '=', 'e.id_operador')
            ->leftJoin('talonarios as t', function ($join) {
                $join->on('t.id_estacion', '=', 'e.id_estacion');
                $join->whereIn('t.estado', ['ASIGNADO', 'PENDIENTE', 'LIQUIDADO', 'ANULADO']);
            })
            ->when(!empty($operadorIds), fn($qq) => $qq->whereIn('e.id_operador', $operadorIds))
            ->when($q !== '', function ($qq) use ($q) {
                $qq->where(function ($w) use ($q) {
                    $w->where('e.nombre', 'like', "%{$q}%")
                      ->orWhere('op.nombre', 'like', "%{$q}%")
                      ->orWhere('op.apellido', 'like', "%{$q}%");
                });
            })
            ->select([
                'op.id_usuario as operador_id',
                DB::raw("CONCAT(op.nombre,' ',op.apellido) as operador_nombre"),
                'e.id_estacion',
                'e.nombre as estacion_nombre',

                DB::raw("COUNT(DISTINCT t.id_talonario) as talonarios_asignados"),
                DB::raw("COALESCE(SUM(t.cantidad_numeros),0) as numeros"),
                DB::raw("COALESCE(SUM(t.valor_talonario),0) as monto_asignado"),

                DB::raw("COALESCE(SUM(CASE WHEN t.estado='LIQUIDADO' THEN 1 ELSE 0 END),0) as talonarios_liquidados"),
                DB::raw("COALESCE(SUM(CASE WHEN t.estado IN ('ASIGNADO','PENDIENTE') THEN 1 ELSE 0 END),0) as talonarios_pendientes"),
                DB::raw("COALESCE(SUM(CASE WHEN t.estado='ANULADO' THEN 1 ELSE 0 END),0) as talonarios_anulados"),

                // Vendidos (si LIQUIDADO = vendido completo)
                DB::raw("COALESCE(SUM(CASE WHEN t.estado='LIQUIDADO' THEN t.valor_talonario ELSE 0 END),0) as monto_vendido"),
                DB::raw("COALESCE(SUM(CASE WHEN t.estado='LIQUIDADO' THEN t.cantidad_numeros ELSE 0 END),0) as numeros_vendidos"),
            ])
            ->groupBy('op.id_usuario', 'op.nombre', 'op.apellido', 'e.id_estacion', 'e.nombre')
            ->orderBy('op.nombre')
            ->orderBy('e.nombre')
            ->get();

        // 3) KPIs
        $kpi = [
            'talonarios_total'    => (int)$estaciones->sum('talonarios_asignados'),
            'talonarios_vendidos' => (int)$estaciones->sum('talonarios_liquidados'),

            'numeros_total'       => (int)$estaciones->sum('numeros'),
            'numeros_vendidos'    => (int)$estaciones->sum('numeros_vendidos'),

            'monto_total'         => (float)$estaciones->sum('monto_asignado'),
            'monto_vendido'       => (float)$estaciones->sum('monto_vendido'),
        ];

        // 4) Agrupar por operador
        $porOperador = [];
        foreach ($estaciones as $row) {
            $opId = $row->operador_id;

            if (!isset($porOperador[$opId])) {
                $porOperador[$opId] = [
                    'operador_id'          => $row->operador_id,
                    'operador_nombre'      => $row->operador_nombre,

                    'tot_talonarios'       => 0,
                    'tot_liquidados'       => 0,
                    'tot_pendientes'       => 0,
                    'tot_anulados'         => 0,

                    'tot_numeros'          => 0,
                    'tot_monto'            => 0,

                    'tot_monto_vendido'    => 0,
                    'tot_numeros_vendidos' => 0,

                    'estaciones'           => [],
                ];
            }

            $porOperador[$opId]['tot_talonarios']       += (int)$row->talonarios_asignados;
            $porOperador[$opId]['tot_liquidados']       += (int)$row->talonarios_liquidados;
            $porOperador[$opId]['tot_pendientes']       += (int)$row->talonarios_pendientes;
            $porOperador[$opId]['tot_anulados']         += (int)$row->talonarios_anulados;

            $porOperador[$opId]['tot_numeros']          += (int)$row->numeros;
            $porOperador[$opId]['tot_monto']            += (float)$row->monto_asignado;

            $porOperador[$opId]['tot_monto_vendido']    += (float)$row->monto_vendido;
            $porOperador[$opId]['tot_numeros_vendidos'] += (int)$row->numeros_vendidos;

            $porOperador[$opId]['estaciones'][] = $row;
        }
        $porOperador = array_values($porOperador);

        // 5) Datos para gráficos (ventas por estación)
        $ventasAgrupadas = $estaciones
            ->groupBy('estacion_nombre')
            ->map(function ($items) {
                return [
                    'numeros_vendidos' => (int)$items->sum('numeros_vendidos'),
                    'monto_vendido'    => (float)$items->sum('monto_vendido'),
                ];
            });

        $chartLabels  = $ventasAgrupadas->keys()->values();
        $chartNumeros = $ventasAgrupadas->values()->pluck('numeros_vendidos');
        $chartMonto   = $ventasAgrupadas->values()->pluck('monto_vendido');

        // ✅ FIX: si no existe index, intenta dashboard/index o dashboard
        $candidates = ['index', 'dashboard.index', 'dashboard'];
        $viewName = null;
        foreach ($candidates as $v) {
            if (view()->exists($v)) { $viewName = $v; break; }
        }
        if (!$viewName) {
            abort(500, "No se encontró la vista del dashboard. Crea resources/views/index.blade.php (recomendado) o resources/views/dashboard/index.blade.php o resources/views/dashboard.blade.php");
        }

        return view($viewName, [
            'nombre'       => $nombre ?: 'Usuario',
            'q'            => $q,
            'kpi'          => $kpi,
            'porOperador'  => $porOperador,

            'chartLabels'  => $chartLabels,
            'chartNumeros' => $chartNumeros,
            'chartMonto'   => $chartMonto,
        ]);
    }
}

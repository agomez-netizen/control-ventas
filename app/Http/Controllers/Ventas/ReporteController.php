<?php

namespace App\Http\Controllers\Ventas;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ReporteController extends Controller
{
public function index(Request $request)
{
    [$estaciones, $operadores, $talonarios] = $this->dataBase($request);

    $numerosPorTalonario = [];
    $resumen = [];

    $tieneTablaNumeros = Schema::hasTable('talonario_numeros');

    // =========================
    // 1) Mapa de estados desde talonario_numeros
    //    $mapEstados[talonario_id][numero] = estado
    // =========================
    $mapEstados = [];

    if ($tieneTablaNumeros && $talonarios->count()) {
        $rowsEstados = DB::table('talonario_numeros')
            ->whereIn('talonario_id', $talonarios->pluck('id_talonario'))
            ->select([
                'talonario_id',
                'numero',
                DB::raw("LOWER(COALESCE(estado,'pendiente')) as estado"),
            ])
            ->get();

        foreach ($rowsEstados as $r) {
            $mapEstados[$r->talonario_id][(int)$r->numero] = $this->normalizarEstadoNumero($r->estado);
        }
    }

    // Límite de seguridad para no reventar memoria en pantalla
    $MAX_RANGO = 5000;

    // =========================
    // 2) Para cada talonario, generar TODO el rango + pisar estados
    // =========================
    foreach ($talonarios as $t) {
        $ini = (int)($t->numero_inicio ?? 0);
        $fin = (int)($t->numero_fin ?? 0);

        $arr = [];
        $liq = 0; $anu = 0; $pen = 0;

        if ($ini > 0 && $fin > 0 && $fin >= $ini && ($fin - $ini) <= $MAX_RANGO) {
            for ($n = $ini; $n <= $fin; $n++) {
                $estado = $mapEstados[$t->id_talonario][$n] ?? 'pendiente';

                if ($estado === 'liquidado') $liq++;
                elseif ($estado === 'anulado') $anu++;
                else $pen++;

                $arr[] = [
                    'numero' => $n,
                    'estado' => $estado,
                ];
            }
        }

        $numerosPorTalonario[$t->id_talonario] = $arr;

        $resumen[$t->id_talonario] = [
            'liquidado' => $liq,
            'anulado'   => $anu,
            'pendiente' => $pen,
            'total'     => count($arr),
        ];
    }

    return view('ventas.reportes.index', [
        'estaciones' => $estaciones,
        'operadores' => $operadores,
        'talonarios' => $talonarios,
        'numerosPorTalonario' => $numerosPorTalonario,
        'resumen' => $resumen,
        'estacionId' => $request->get('estacion_id'),
        'operadorId' => $request->get('operador_id'),
        'estado' => $request->get('estado', 'todos'),
    ]);
}


    /**
     * Export XLSX "modo gerente":
     * - Hoja 1: Resumen por talonario
     * - Hoja 2: Detalle por número
     * - Admin: todo
     * - TM: talonarios de sus operadores (usuarios.id_tm = TM)
     * - Operador: solo los suyos
     * Requiere: phpoffice/phpspreadsheet
     */
public function export(Request $request)
{
    $loggedId = $this->currentUserId();
    if (!$loggedId) abort(403, 'Sesión inválida.');

    // Talonarios ya vienen filtrados por permisos + filtros
    [$_estaciones, $_operadores, $talonarios] = $this->dataBase($request);

    $tieneTablaNumeros = Schema::hasTable('talonario_numeros');

    // =========================
    // 1) Mapa de estados desde talonario_numeros
    //    (solo para los números que existan ahí)
    //    $mapEstados[talonario_id][numero] = estado
    // =========================
    $mapEstados = [];

    if ($tieneTablaNumeros && $talonarios->count()) {
        $rowsEstados = DB::table('talonario_numeros')
            ->whereIn('talonario_id', $talonarios->pluck('id_talonario'))
            ->select([
                'talonario_id',
                'numero',
                DB::raw("LOWER(COALESCE(estado,'pendiente')) as estado"),
            ])
            ->get();

        foreach ($rowsEstados as $r) {
            $mapEstados[$r->talonario_id][(int)$r->numero] = $this->normalizarEstadoNumero($r->estado);
        }
    }

    // =========================
    // 2) Crear Excel (2 hojas)
    // =========================
    $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();

    /*
    |--------------------------------------------------------------------------
    | HOJA 1 - RESUMEN
    |--------------------------------------------------------------------------
    */
    $sheetResumen = $spreadsheet->getActiveSheet();
    $sheetResumen->setTitle('Resumen');

    $resumenRows = [];
    $resumenRows[] = [
        'Talonario',
        'Estado Talonario',
        'Estación',
        'Operador',
        'Número Inicio',
        'Número Fin',
        'Liquidados',
        'Anulados',
        'Pendientes',
        'Total'
    ];

    // Límite de seguridad para no reventar memoria (ajusta si quieres)
    $MAX_RANGO = 5000;

    foreach ($talonarios as $t) {
        $ini = (int)($t->numero_inicio ?? 0);
        $fin = (int)($t->numero_fin ?? 0);

        $liq = 0; $anu = 0; $pen = 0;

        if ($ini > 0 && $fin > 0 && $fin >= $ini && ($fin - $ini) <= $MAX_RANGO) {
            for ($n = $ini; $n <= $fin; $n++) {
                $estado = $mapEstados[$t->id_talonario][$n] ?? 'pendiente';
                if ($estado === 'liquidado') $liq++;
                elseif ($estado === 'anulado') $anu++;
                else $pen++;
            }
        }

        $total = $liq + $anu + $pen;

        $resumenRows[] = [
            $t->numero_talonario,
            ucfirst($t->estado_talonario),
            $t->estacion_nombre,
            $t->operador_nombre,
            $t->numero_inicio,
            $t->numero_fin,
            $liq,
            $anu,
            $pen,
            $total
        ];
    }

    $sheetResumen->fromArray($resumenRows, null, 'A1', true);
    $sheetResumen->freezePane('A2');
    $lastColResumen = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(count($resumenRows[0]));
    $sheetResumen->setAutoFilter("A1:{$lastColResumen}1");

    /*
    |--------------------------------------------------------------------------
    | HOJA 2 - DETALLE (TODOS LOS NÚMEROS)
    |--------------------------------------------------------------------------
    */
    $sheetDetalle = $spreadsheet->createSheet();
    $sheetDetalle->setTitle('Detalle');

    $detalleRows = [];
    $detalleRows[] = ['Talonario', 'Estación', 'Operador', 'Número', 'Estado'];

    foreach ($talonarios as $t) {
        $ini = (int)($t->numero_inicio ?? 0);
        $fin = (int)($t->numero_fin ?? 0);

        if ($ini > 0 && $fin > 0 && $fin >= $ini && ($fin - $ini) <= $MAX_RANGO) {
            for ($n = $ini; $n <= $fin; $n++) {
                $estado = $mapEstados[$t->id_talonario][$n] ?? 'pendiente';

                $detalleRows[] = [
                    $t->numero_talonario,
                    $t->estacion_nombre,
                    $t->operador_nombre,
                    $n,
                    ucfirst($estado)
                ];
            }
        }
    }

    $sheetDetalle->fromArray($detalleRows, null, 'A1', true);
    $sheetDetalle->freezePane('A2');
    $lastColDetalle = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(count($detalleRows[0]));
    $sheetDetalle->setAutoFilter("A1:{$lastColDetalle}1");

    // Colores por estado (columna E)
    $highestRow = $sheetDetalle->getHighestRow();
    for ($row = 2; $row <= $highestRow; $row++) {
        $estado = strtolower((string)$sheetDetalle->getCell("E{$row}")->getValue());

        if ($estado === 'liquidado') {
            $sheetDetalle->getStyle("E{$row}")->getFont()->getColor()->setRGB('006100'); // verde
        } elseif ($estado === 'anulado') {
            $sheetDetalle->getStyle("E{$row}")->getFont()->getColor()->setRGB('9C0006'); // rojo
        } else {
            $sheetDetalle->getStyle("E{$row}")->getFont()->getColor()->setRGB('7F6000'); // pendiente
        }
    }

    // AutoSize en ambas hojas
    foreach ([$sheetResumen, $sheetDetalle] as $sheet) {
        $highestColumnIndex = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($sheet->getHighestColumn());
        for ($i = 1; $i <= $highestColumnIndex; $i++) {
            $col = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($i);
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }
    }

    // =========================
    // 3) Guardar y descargar
    // =========================
    $filename = 'reporte_gerencial_' . date('Ymd_His') . '.xlsx';
    $tmpPath = storage_path('app/' . $filename);

    $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
    $writer->save($tmpPath);

    return response()->download($tmpPath, $filename)->deleteFileAfterSend(true);
}


    /**
     * Catálogos + talonarios con filtros y PERMISOS:
     * Admin -> todo
     * TM -> operadores con usuarios.id_tm = TM
     * Operador -> solo sus estaciones (estaciones.id_operador = operador)
     */
private function dataBase(Request $request)
{
    $loggedId = $this->currentUserId();
    if (!$loggedId) return [collect(), collect(), collect()];

    $rol = $this->currentRoleId();
    $isAdmin = ((int)$rol === 1);

    $estacionId = $request->get('estacion_id');
    $operadorId = $request->get('operador_id'); // útil para admin
    $estado     = $request->get('estado', 'todos');

    // ==========================
    // Determinar alcance permitido
    // Admin: todos
    // TM: operadores donde usuarios.id_tm = loggedId
    // Operador: él mismo
    // (Detectamos TM si tiene operadores)
    // ==========================
    $allowedOperadores = null; // null => sin restricción (admin)

    if (!$isAdmin) {
        $misOperadores = DB::table('usuarios')
            ->where('id_tm', $loggedId)
            ->where('estado', 1)
            ->pluck('id_usuario')
            ->toArray();

        $isTM = !empty($misOperadores);

        if ($isTM) {
            $allowedOperadores = $misOperadores;
        } else {
            $allowedOperadores = [$loggedId];
        }

        // Evitar traer todo si por algo queda vacío
        if (empty($allowedOperadores)) $allowedOperadores = [-1];
    }

    // ==========================
    // Estaciones visibles
    // ==========================
    $estaciones = DB::table('estaciones')
        ->when(!$isAdmin, fn($q) => $q->whereIn('id_operador', $allowedOperadores))
        ->select('id_estacion', 'nombre')
        ->orderBy('id_estacion', 'desc')
        ->get();

    // ==========================
    // Operadores visibles (para el filtro)
    // - Admin: operadores que estén asignados en estaciones
    // - TM: operadores con id_tm = loggedId
    // - Operador: solo él
    // ==========================
    $operadores = DB::table('usuarios as u')
        ->when($isAdmin,
            function ($q) {
                return $q->join('estaciones as e', 'e.id_operador', '=', 'u.id_usuario')
                    ->select(
                        'u.id_usuario',
                        DB::raw("TRIM(CONCAT(COALESCE(u.nombre,''),' ',COALESCE(u.apellido,''))) as nombre")
                    )
                    ->distinct();
            },
            function ($q) use ($allowedOperadores, $loggedId) {
                return $q->whereIn('u.id_usuario', $allowedOperadores)
                    ->where('u.estado', 1)
                    ->where('u.id_usuario', '<>', $loggedId) // <- NO listar el TM como operador
                    ->select(
                        'u.id_usuario',
                        DB::raw("TRIM(CONCAT(COALESCE(u.nombre,''),' ',COALESCE(u.apellido,''))) as nombre")
                    );
            }
        )
        ->orderBy(DB::raw("TRIM(CONCAT(COALESCE(u.nombre,''),' ',COALESCE(u.apellido,'')))"))
        ->get();

    // Si NO es admin y NO es TM (operador), mostrar solo él en el combo
    if (!$isAdmin && $operadores->isEmpty()) {
        $operadores = DB::table('usuarios as u')
            ->where('u.id_usuario', $loggedId)
            ->select(
                'u.id_usuario',
                DB::raw("TRIM(CONCAT(COALESCE(u.nombre,''),' ',COALESCE(u.apellido,''))) as nombre")
            )
            ->get();
    }

    // ==========================
    // Talonarios
    // ==========================
    $talonariosQ = DB::table('talonarios as t')
        ->leftJoin('estaciones as e', 'e.id_estacion', '=', 't.id_estacion')
        ->leftJoin('usuarios as u', 'u.id_usuario', '=', 'e.id_operador')
        ->select([
            't.id_talonario',
            't.numero_talonario',
            't.numero_inicio',
            't.numero_fin',
            DB::raw("LOWER(COALESCE(t.estado, 'pendiente')) as estado_talonario"),
            't.id_estacion',
            DB::raw("COALESCE(e.nombre, '—') as estacion_nombre"),
            DB::raw("e.id_operador as id_operador"),
            DB::raw("TRIM(CONCAT(COALESCE(u.nombre,''),' ',COALESCE(u.apellido,''))) as operador_nombre"),
        ]);

    // Permisos
    if (!$isAdmin) {
        $talonariosQ->whereIn('e.id_operador', $allowedOperadores);
    } else {
        // Admin puede filtrar por operador desde el select
        if ($operadorId) $talonariosQ->where('e.id_operador', $operadorId);
    }

    // Para TM/Operador: si seleccionan operador en el combo, aplicar ese filtro (pero seguro)
    if (!$isAdmin && $operadorId) {
        // solo si pertenece a su allowedOperadores
        if (in_array((int)$operadorId, array_map('intval', $allowedOperadores), true)) {
            $talonariosQ->where('e.id_operador', (int)$operadorId);
        }
    }

    // Filtro estación
    if ($estacionId) {
        $talonariosQ->where('t.id_estacion', $estacionId);
    }

    // Filtro estado talonario
    if ($estado !== 'todos') {
        if ($estado === 'pendiente') {
            $talonariosQ->whereNotIn(DB::raw("LOWER(COALESCE(t.estado,'pendiente'))"), ['liquidado', 'anulado']);
        } else {
            $talonariosQ->where(DB::raw("LOWER(COALESCE(t.estado,'pendiente'))"), $estado);
        }
    }

    $talonarios = $talonariosQ->orderBy('t.id_talonario', 'desc')->get();

    return [$estaciones, $operadores, $talonarios];
}



    private function currentUserId(): ?int
    {
        $u = session('user');
        if (is_array($u)) return isset($u['id_usuario']) ? (int)$u['id_usuario'] : null;
        if (is_object($u)) return isset($u->id_usuario) ? (int)$u->id_usuario : null;
        return null;
    }

    private function currentRoleId(): ?int
    {
        $u = session('user');
        if (is_array($u)) return isset($u['id_rol']) ? (int)$u['id_rol'] : null;
        if (is_object($u)) return isset($u->id_rol) ? (int)$u->id_rol : null;
        return null;
    }

    private function normalizarEstadoNumero($estado)
    {
        $e = strtolower(trim((string)$estado));
        if (str_starts_with($e, 'dispo')) return 'pendiente';
        if (in_array($e, ['liquidado', 'anulado', 'pendiente'], true)) return $e;
        return 'pendiente';
    }
}

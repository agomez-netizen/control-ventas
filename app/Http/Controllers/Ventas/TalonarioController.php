<?php

namespace App\Http\Controllers\Ventas;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TalonarioController extends Controller
{
    private function getSessionUser(): array
    {
        $u = session('user');

        if (!$u) {
            abort(401, 'No hay sesión activa. Inicia sesión de nuevo.');
        }

        if (is_object($u)) $u = (array) $u;

        return $u;
    }

    private function getUserId(): int
    {
        $u = $this->getSessionUser();
        $id = $u['id_usuario'] ?? null;

        if (!$id) abort(401, 'Sesión inválida: no se encontró id_usuario.');

        return (int)$id;
    }

    private function isAdmin(): bool
    {
        $u = $this->getSessionUser();
        $rolId = (int)($u['id_rol'] ?? 0);
        return $rolId === 1; // ✅ tu regla
    }

    private function requireAdmin(): void
    {
        if (!$this->isAdmin()) {
            abort(403, 'Solo ADMIN puede anular talonarios.');
        }
    }

    /**
     * Si el usuario logueado es TM, su "tmOwnerId" es su propio id_usuario.
     * Si es operador, su "tmOwnerId" es u['id_tm'].
     */
    private function getTmOwnerId(): int
    {
        $u = $this->getSessionUser();
        $userId = $this->getUserId();

        $tmOwnerId = $u['id_tm'] ?? null;

        return (int)($tmOwnerId ?: $userId);
    }

    public function index(Request $request)
    {
        $isAdmin   = $this->isAdmin();
        $tmOwnerId = $this->getTmOwnerId();

        // Filtros
        $idEstacion = $request->input('id_estacion');
        $idOperador = $request->input('id_operador');
        $estado     = $request->input('estado'); // ASIGNADO, LIQUIDADO, ANULADO
        $buscar     = trim((string)$request->input('buscar'));

        // Operadores dropdown (ADMIN ve todos / otros solo los suyos)
        $opQ = DB::table('usuarios as u')
            ->join('roles as r', 'r.id_rol', '=', 'u.id_rol')
            ->where('r.nombre', 'OPERADOR')
            ->where('u.estado', 1)
            ->orderBy('u.nombre')
            ->select('u.id_usuario', DB::raw("CONCAT(u.nombre,' ',u.apellido) as operador"));

        if (!$isAdmin) {
            $opQ->where('u.id_tm', $tmOwnerId);
        }

        $operadores = $opQ->get();

        // Estaciones dropdown (ADMIN ve todas / otros solo las suyas)
        $esQ = DB::table('estaciones as e')
            ->join('usuarios as u', 'u.id_usuario', '=', 'e.id_operador')
            ->orderBy('e.nombre')
            ->select('e.id_estacion', 'e.nombre as estacion');

        if (!$isAdmin) {
            $esQ->where('u.id_tm', $tmOwnerId);
        }

        $estaciones = $esQ->get();

        // Query base (ADMIN: todos / otros: solo los suyos)
        $q = DB::table('talonarios as t')
            ->join('estaciones as e', 'e.id_estacion', '=', 't.id_estacion')
            ->join('usuarios as u', 'u.id_usuario', '=', 'e.id_operador')
            ->whereNotNull('t.id_estacion'); // solo asignados a estación

        if (!$isAdmin) {
            $q->where('u.id_tm', $tmOwnerId);
        }

        // Aplicar filtros
        if (!empty($idEstacion)) $q->where('e.id_estacion', $idEstacion);
        if (!empty($idOperador)) $q->where('u.id_usuario', $idOperador);
        if (!empty($estado))     $q->where('t.estado', $estado);
        if ($buscar !== '')      $q->where('t.numero_talonario', $buscar);

        // Tabla
        $talonarios = (clone $q)
            ->orderBy('e.nombre')
            ->orderBy('t.numero_talonario')
            ->select([
                't.id_talonario',
                't.numero_talonario',
                't.numero_inicio',
                't.numero_fin',
                't.estado',
                't.valor_talonario',
                't.asignado_en',
                'e.id_estacion',
                'e.nombre as estacion',
                'u.id_usuario as id_operador',
                DB::raw("CONCAT(u.nombre,' ',u.apellido) as operador"),
            ])
            ->paginate(10)
            ->appends($request->query());

        // Resumen
        $resumen = (clone $q)
            ->selectRaw("
                SUM(t.estado = 'ASIGNADO')  AS total_asignados,
                SUM(t.estado = 'LIQUIDADO') AS total_liquidados,
                SUM(t.estado = 'ANULADO')   AS total_anulados,
                SUM(CASE WHEN t.estado='ASIGNADO' THEN t.valor_talonario ELSE 0 END) AS monto_pendiente
            ")
            ->first();

        return view('ventas.talonarios.index', compact(
            'talonarios', 'operadores', 'estaciones', 'resumen',
            'idEstacion', 'idOperador', 'estado', 'buscar'
        ));
    }

    public function estacion(int $id_estacion)
    {
        $isAdmin   = $this->isAdmin();
        $tmOwnerId = $this->getTmOwnerId();

        $infoQ = DB::table('estaciones as e')
            ->join('usuarios as u', 'u.id_usuario', '=', 'e.id_operador')
            ->where('e.id_estacion', $id_estacion)
            ->select([
                'e.*',
                DB::raw("CONCAT(u.nombre,' ',u.apellido) as operador"),
                'u.telefono as telefono_operador',
                'u.id_usuario as id_operador',
            ]);

        if (!$isAdmin) {
            $infoQ->where('u.id_tm', $tmOwnerId);
        }

        $info = $infoQ->first();

        abort_if(!$info, 403, 'No tienes permiso para ver esta estación.');

        $talonarios = DB::table('talonarios')
            ->where('id_estacion', $id_estacion)
            ->orderBy('numero_talonario')
            ->paginate(15);

        $resumen = DB::table('talonarios')
            ->where('id_estacion', $id_estacion)
            ->selectRaw("
                SUM(estado = 'ASIGNADO')  AS total_asignados,
                SUM(estado = 'LIQUIDADO') AS total_liquidados,
                SUM(estado = 'ANULADO')   AS total_anulados,
                SUM(CASE WHEN estado='ASIGNADO' THEN valor_talonario ELSE 0 END) AS monto_pendiente
            ")
            ->first();

        return view('ventas.talonarios.estacion', compact('info', 'talonarios', 'resumen'));
    }

    // =========================
    // ANULAR - SOLO ADMIN
    // =========================

    public function anularForm(int $id)
    {
        $this->requireAdmin();

        $talonario = DB::table('talonarios as t')
            ->join('estaciones as e', 'e.id_estacion', '=', 't.id_estacion')
            ->join('usuarios as u', 'u.id_usuario', '=', 'e.id_operador')
            ->where('t.id_talonario', $id)
            ->select([
                't.id_talonario',
                't.numero_talonario',
                't.numero_inicio',
                't.numero_fin',
                't.estado',
                't.valor_talonario',
                't.asignado_en',
                't.id_estacion',
                'e.nombre as estacion',
                DB::raw("CONCAT(u.nombre,' ',u.apellido) as operador"),
            ])
            ->first();

        abort_if(!$talonario, 404, 'Talonario no encontrado.');

        if (strtoupper((string)$talonario->estado) === 'LIQUIDADO') {
            return redirect()->route('ventas.talonarios.index')
                ->with('error', 'No se puede anular un talonario LIQUIDADO.');
        }

        return view('ventas.talonarios.anular', compact('talonario'));
    }

    public function anularStore(Request $request, int $id)
    {
        $this->requireAdmin();

        $userId = $this->getUserId();

        $request->validate([
            'motivo_anulacion' => ['required', 'string', 'max:255'],
        ]);

        return DB::transaction(function () use ($id, $request, $userId) {

            $row = DB::table('talonarios')
                ->where('id_talonario', $id)
                ->lockForUpdate()
                ->select('id_talonario', 'estado')
                ->first();

            abort_if(!$row, 404, 'Talonario no encontrado.');

            $estado = strtoupper((string)$row->estado);

            if ($estado === 'LIQUIDADO') {
                return redirect()->route('ventas.talonarios.index')
                    ->with('error', 'No se puede anular un talonario LIQUIDADO.');
            }

            if ($estado === 'ANULADO') {
                return redirect()->route('ventas.talonarios.index')
                    ->with('info', 'Este talonario ya estaba ANULADO.');
            }

            DB::table('talonarios')
                ->where('id_talonario', $id)
                ->update([
                    'estado'           => 'ANULADO',
                    'motivo_anulacion' => $request->motivo_anulacion,
                    'anulado_por'      => $userId,
                    'anulado_en'       => now(),
                    'updated_at'       => now(),
                ]);

            return redirect()->route('ventas.talonarios.index')
                ->with('success', 'Talonario anulado correctamente.');
        });
    }
}

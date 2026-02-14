<?php

namespace App\Http\Controllers\Ventas;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class LiquidacionController extends Controller
{
    private function userId(): int
    {
        $u = session('user');
        if (is_array($u)) return (int)($u['id_usuario'] ?? $u['id'] ?? 0);
        return (int)($u->id_usuario ?? $u->id ?? 0);
    }

    private function userRolName(): string
    {
        $u = session('user');
        $rol = is_array($u) ? ($u['rol'] ?? $u['nombre_rol'] ?? '') : ($u->rol ?? $u->nombre_rol ?? '');
        return strtoupper(trim((string)$rol));
    }

    private function userRolId(): int
    {
        $u = session('user');
        return is_array($u) ? (int)($u['id_rol'] ?? 0) : (int)($u->id_rol ?? 0);
    }

    private function isAdmin(): bool
    {
        // En tu sistema: id_rol 1 = ADMIN
        return $this->userRolId() === 1 || $this->userRolName() === 'ADMIN';
    }

    private function userTmId(): int
    {
        $u = session('user');
        $rol = $this->userRolName();
        $idUsuario = $this->userId();

        if ($rol === 'TM') return $idUsuario;

        $idTm = is_array($u) ? (int)($u['id_tm'] ?? 0) : (int)($u->id_tm ?? 0);
        return $idTm;
    }

    public function index()
    {
        $idTm = $this->userTmId();
        $rol  = $this->userRolName();

        $q = DB::table('liquidaciones as l')
            ->join('estaciones as e', 'e.id_estacion', '=', 'l.id_estacion')
            ->leftJoin('usuarios as u', 'u.id_usuario', '=', 'e.id_operador')
            ->select(
                'l.id_liquidacion',
                'l.created_at',
                'l.estado',
                'l.cantidad_talonarios',
                'l.monto_calculado',
                'l.monto_boletas',
                'l.excedente',
                'e.nombre as estacion',
                DB::raw("CONCAT(IFNULL(u.nombre,''),' ',IFNULL(u.apellido,'')) as operador_nombre")
            )
            ->orderByDesc('l.id_liquidacion');

        if ($rol !== 'ADMIN') $q->where('l.id_tm', $idTm);

        $liquidaciones = $q->paginate(15);

        return view('ventas.liquidaciones.index', compact('liquidaciones'));
    }

    public function show($id)
    {
        $id = (int)$id;
        $rol = $this->userRolName();
        $idTm = $this->userTmId();

        $liqQ = DB::table('liquidaciones as l')
            ->join('estaciones as e', 'e.id_estacion', '=', 'l.id_estacion')
            ->leftJoin('usuarios as u', 'u.id_usuario', '=', 'e.id_operador')
            ->select(
                'l.*',
                'e.nombre as estacion_nombre',
                'e.id_estacion',
                DB::raw("CONCAT(IFNULL(u.nombre,''),' ',IFNULL(u.apellido,'')) as operador_nombre")
            )
            ->where('l.id_liquidacion', $id);

        if ($rol !== 'ADMIN') $liqQ->where('l.id_tm', $idTm);

        $liquidacion = $liqQ->first();
        if (!$liquidacion) abort(404);

        $boletas = DB::table('boletas as b')
            ->join('bancos as ba', 'ba.id_banco', '=', 'b.id_banco')
            ->select('b.*', 'ba.nombre as banco_nombre')
            ->where('b.id_liquidacion', $id)
            ->orderBy('b.id_boleta', 'asc')
            ->get();

        $talonarios = DB::table('liquidacion_talonarios as lt')
            ->join('talonarios as t', 't.id_talonario', '=', 'lt.id_talonario')
            ->select('t.id_talonario', 't.numero_talonario', 't.numero_inicio', 't.numero_fin', 't.valor_talonario')
            ->where('lt.id_liquidacion', $id)
            ->orderBy('t.numero_talonario')
            ->get();

        $numeros = DB::table('liquidacion_numeros as ln')
            ->join('talonarios as t', 't.id_talonario', '=', 'ln.talonario_id')
            ->select('ln.talonario_id', 't.numero_talonario', 'ln.numero')
            ->where('ln.liquidacion_id', $id)
            ->orderBy('t.numero_talonario')
            ->orderBy('ln.numero')
            ->get()
            ->groupBy('numero_talonario');

        return view('ventas.liquidaciones.show', compact('liquidacion', 'boletas', 'talonarios', 'numeros'));
    }

public function create()
{
    $u = session('user');
    $idUsuario = $this->userId();
    $rolName   = $this->userRolName();

    $isAdmin = $this->isAdmin();
    $isTM    = ($rolName === 'TM');

    if ($isAdmin) {
        $estaciones = DB::table('estaciones')
            ->select('id_estacion','nombre')
            ->orderBy('nombre')
            ->get();
    } elseif ($isTM) {
        $operadoresIds = DB::table('usuarios as u')
            ->join('roles as r','r.id_rol','=','u.id_rol')
            ->whereIn(DB::raw('UPPER(TRIM(r.nombre))'), ['OP','OPERADOR','OPERADORES'])
            ->where('u.id_tm', $idUsuario)
            ->pluck('u.id_usuario');

        $estaciones = DB::table('estaciones')
            ->whereIn('id_operador', $operadoresIds)
            ->select('id_estacion','nombre')
            ->orderBy('nombre')
            ->get();
    } else {
        $estaciones = DB::table('estaciones')
            ->where('id_operador', $idUsuario)
            ->select('id_estacion','nombre')
            ->orderBy('nombre')
            ->get();
    }

    // ✅ Para que el select recuerde la estación al volver con errores
    $idEstacion = old('id_estacion', old('estacion_id', ''));

    // ✅ BANCOS para el @json($bancos) del blade
    $bancos = DB::table('bancos')
        ->select('id_banco','nombre')
        ->orderBy('nombre')
        ->get();

    return view('ventas.liquidaciones.create', compact('estaciones', 'idEstacion', 'bancos'));
}


public function talonariosDisponibles($id_estacion)
{
    $id_estacion = (int)$id_estacion;

    // ✅ Seguridad: si no es admin, la estación debe pertenecer al TM (o al operador)
    if (!$this->isAdmin()) {
        $idTm = $this->userTmId();

        $ok = DB::table('estaciones as e')
            ->join('usuarios as op', 'op.id_usuario', '=', 'e.id_operador')
            ->where('e.id_estacion', $id_estacion)
            ->where('op.id_tm', $idTm)
            ->exists();

        if (!$ok) {
            // fallback: si el usuario es operador, permitir solo si la estación es suya
            $idUsuario = $this->userId();
            $ok2 = DB::table('estaciones')
                ->where('id_estacion', $id_estacion)
                ->where('id_operador', $idUsuario)
                ->exists();

            if (!$ok2) {
                return response()->json(['error' => 'No autorizado'], 403);
            }
        }
    }

    // ✅ Info de estación para el cardInfoEstacion (tu JS usa data.estacion)
    $estacion = DB::table('estaciones as e')
        ->leftJoin('usuarios as op', 'op.id_usuario', '=', 'e.id_operador')
        ->select(
            'e.id_estacion',
            'e.nombre',
            'e.telefono',
            'e.correo',
            'e.pais',
            'e.departamento',
            'e.municipio',
            'e.direccion',
            DB::raw("CONCAT(IFNULL(op.nombre,''),' ',IFNULL(op.apellido,'')) as operador"),
            'op.telefono as telefono_operador'
        )
        ->where('e.id_estacion', $id_estacion)
        ->first();

    // ✅ Talonarios disponibles (recomendado: solo ASIGNADO)
    $talonarios = DB::table('talonarios')
        ->select(
            'id_talonario',
            'numero_talonario',
            'numero_inicio',
            'numero_fin',
            'cantidad_numeros',
            'valor_talonario',
            'valor_numero',
            'estado'
        )
        ->where('id_estacion', $id_estacion)
        ->where('estado', 'ASIGNADO')
        ->orderBy('numero_talonario')
        ->get();

    // ✅ ESTA es la forma que tu JS espera:
    return response()->json([
        'estacion'  => $estacion,
        'talonarios'=> $talonarios,
    ]);
}


    private function ensureTalonarioNumeros(int $talonarioId): void
    {
        $exists = DB::table('talonario_numeros')
            ->where('talonario_id', $talonarioId)
            ->exists();

        if ($exists) return;

        $t = DB::table('talonarios')
            ->select('id_talonario', 'numero_inicio', 'numero_fin')
            ->where('id_talonario', $talonarioId)
            ->first();

        if (!$t) return;

        $ini = (int)$t->numero_inicio;
        $fin = (int)$t->numero_fin;
        if ($ini <= 0 || $fin <= 0 || $fin < $ini) return;

        $batch = [];
        $now = now();

        for ($i = $ini; $i <= $fin; $i++) {
            $batch[] = [
                'talonario_id'   => $talonarioId,
                'liquidacion_id' => null,
                'numero'         => $i,
                'estado'         => 'DISPONIBLE',
                'created_at'     => $now,
                'updated_at'     => $now,
            ];

            if (count($batch) >= 500) {
                DB::table('talonario_numeros')->insertOrIgnore($batch);
                $batch = [];
            }
        }

        if (!empty($batch)) DB::table('talonario_numeros')->insertOrIgnore($batch);
    }

    public function numeros(Request $request, $talonarioId)
    {
        $talonarioId = (int)$talonarioId;

        $t = DB::table('talonarios')
            ->select('id_talonario', 'estado')
            ->where('id_talonario', $talonarioId)
            ->first();

        if (!$t) {
            return response()->json(['data' => [], 'readonly' => true, 'estado' => 'NO_ENCONTRADO']);
        }

        $estadoTal = strtoupper((string)$t->estado);

        $this->ensureTalonarioNumeros($talonarioId);

        $perPage = max(50, min(5000, (int)$request->query('per_page', 5000)));

        if ($estadoTal === 'LIQUIDADO') {
            $query = DB::table('talonario_numeros')
                ->select('numero')
                ->where('talonario_id', $talonarioId)
                ->where('estado', 'LIQUIDADO')
                ->orderBy('numero');

            $pag = $query->paginate($perPage);

            return response()->json([
                'data' => $pag->items(),
                'readonly' => true,
                'estado' => 'LIQUIDADO',
            ]);
        }

        $query = DB::table('talonario_numeros')
            ->select('numero')
            ->where('talonario_id', $talonarioId)
            ->where('estado', 'DISPONIBLE')
            ->orderBy('numero');

        $pag = $query->paginate($perPage);

        return response()->json([
            'data' => $pag->items(),
            'readonly' => false,
            'estado' => 'ASIGNADO',
        ]);
    }

    public function store(Request $request)
    {
        // ✅ Compatibilidad: si tu blade manda id_estacion en vez de estacion_id
        $incomingEstacion = $request->input('estacion_id', $request->input('id_estacion'));

        $request->merge(['estacion_id' => $incomingEstacion]);

        $request->validate([
            'estacion_id' => ['required','integer','min:1'],
            'boletas' => ['required','array','min:1'],
            'boletas.*.id_banco' => ['required','integer','min:1'],
            'boletas.*.tipo_pago' => ['required','in:DEPOSITO,TRANSFERENCIA'],
            'boletas.*.fecha_boleta' => ['required','date'],
            'boletas.*.numero_boleta' => ['required','string','max:80'],
            'boletas.*.monto' => ['required','numeric','gt:0'],
        ], [
            'estacion_id.required' => 'Selecciona una estación para continuar.',
            'boletas.required' => 'Agrega al menos 1 boleta para guardar la liquidación.',
            'boletas.*.id_banco.required' => 'Selecciona el banco en todas las boletas.',
            'boletas.*.tipo_pago.required' => 'Selecciona el tipo en todas las boletas.',
            'boletas.*.fecha_boleta.required' => 'Selecciona la fecha en todas las boletas.',
            'boletas.*.numero_boleta.required' => 'Ingresa el número de boleta en todas las boletas.',
            'boletas.*.monto.required' => 'Ingresa el monto en todas las boletas.',
            'boletas.*.monto.gt' => 'El monto debe ser mayor a 0.',
        ]);

        $estacionId = (int)$request->input('estacion_id', 0);

        // ✅ Seguridad: si NO es admin, esa estación debe ser del TM logueado
        if (!$this->isAdmin()) {
            $idTm = $this->userTmId();

            $ok = DB::table('estaciones as e')
                ->join('usuarios as op', 'op.id_usuario', '=', 'e.id_operador')
                ->where('e.id_estacion', $estacionId)
                ->where('op.id_tm', $idTm)
                ->exists();

            // Si el usuario es operador (no TM), id_tm puede ser 0; fallback: permitir solo si la estación es suya
            if (!$ok) {
                $idUsuario = $this->userId();
                $ok2 = DB::table('estaciones')
                    ->where('id_estacion', $estacionId)
                    ->where('id_operador', $idUsuario)
                    ->exists();

                if (!$ok2) {
                    return back()->with('err', 'Esa estación no te pertenece. Selecciona una estación válida.')->withInput();
                }
            }
        }

        // ---- TU STORE ORIGINAL (tal cual) ----
        $talonarios = json_decode((string)$request->input('talonarios_json', '[]'), true);
        if (!is_array($talonarios)) $talonarios = [];

        $numeros = json_decode((string)$request->input('numeros_json', '{}'), true);
        if (!is_array($numeros)) $numeros = [];

        $donar = (int)$request->input('donar_excedente', 0) === 1;

        $hasNums = false;
        foreach ($numeros as $tid => $arr) {
            if (is_array($arr) && count($arr) > 0) { $hasNums = true; break; }
        }

        if (count($talonarios) === 0 && !$hasNums) {
            return back()->with('err', 'Selecciona al menos 1 talonario o 1 número pendiente.')->withInput();
        }

        $boletas = $request->input('boletas', []);

        $seen = [];
        foreach ($boletas as $b) {
            $key = ((int)$b['id_banco']) . '|' . trim((string)$b['numero_boleta']);
            if (isset($seen[$key])) {
                return back()->with('err', 'Boletas duplicadas: el mismo número de boleta no puede repetirse en el mismo banco.')->withInput();
            }
            $seen[$key] = true;
        }

        foreach ($boletas as $b) {
            $idBanco = (int)$b['id_banco'];
            $numBol  = trim((string)$b['numero_boleta']);

            $exists = DB::table('boletas')
                ->where('id_banco', $idBanco)
                ->where('numero_boleta', $numBol)
                ->exists();

            if ($exists) {
                return back()->with('err', 'Ya existe una boleta con ese número para ese banco. Verifica antes de guardar.')->withInput();
            }
        }

        $idTm = $this->userTmId();
        $creadoPor = $this->userId();

        return DB::transaction(function () use ($request, $estacionId, $talonarios, $numeros, $boletas, $idTm, $creadoPor, $donar) {

            $allTalIds = array_unique(array_merge(
                array_map('intval', $talonarios),
                array_map('intval', array_keys($numeros))
            ));

            $talInfo = DB::table('talonarios')
                ->select('id_talonario', 'valor_talonario', 'valor_numero')
                ->whereIn('id_talonario', $allTalIds)
                ->get()
                ->keyBy('id_talonario');

            $total = 0.0;

            foreach ($talonarios as $tid) {
                $tid = (int)$tid;
                if (!isset($talInfo[$tid])) continue;
                $total += (float)$talInfo[$tid]->valor_talonario;
            }

            foreach ($numeros as $tid => $arr) {
                $tid = (int)$tid;
                if (!is_array($arr) || empty($arr)) continue;
                if (!isset($talInfo[$tid])) continue;

                $precio = (float)$talInfo[$tid]->valor_numero;
                $total += count($arr) * $precio;
            }

            $calc = round($total, 2);

            $sumBoletas = 0.0;
            foreach ($boletas as $b) {
                $sumBoletas += (float)($b['monto'] ?? 0);
            }
            $sumBoletas = round($sumBoletas, 2);

            if ($sumBoletas < $calc) {
                return back()->with('err', "Monto insuficiente. Total calculado Q{$calc} vs boletas Q{$sumBoletas}.")->withInput();
            }

            $excedente = ($sumBoletas > $calc) ? round($sumBoletas - $calc, 2) : 0.00;

            if ($excedente > 0 && !$donar) {
                return back()->with('err', "Hay excedente Q{$excedente}. Si no se dona, debe comunicarse con su asesor de ventas.")->withInput();
            }

            $obs = (string)$request->input('observacion', '');
            if ($excedente > 0 && $donar) {
                $obs = trim($obs . " | DONACIÓN EXCEDENTE Q" . number_format($excedente, 2));
            }

            $liqId = DB::table('liquidaciones')->insertGetId([
                'id_tm'              => $idTm,
                'id_estacion'        => $estacionId,
                'creado_por'         => $creadoPor,
                'cantidad_talonarios'=> count($talonarios),
                'monto_calculado'    => $calc,
                'monto_boletas'      => $sumBoletas,
                'excedente'          => $excedente,
                'estado'             => 'GUARDADA',
                'observacion'        => $obs,
                'created_at'         => now(),
                'updated_at'         => now(),
            ]);

            foreach ($talonarios as $tid) {
                $tid = (int)$tid;

                DB::table('liquidacion_talonarios')->insert([
                    'id_liquidacion' => $liqId,
                    'id_talonario'   => $tid,
                    'created_at'     => now(),
                ]);

                DB::table('talonarios')
                    ->where('id_talonario', $tid)
                    ->update(['estado' => 'LIQUIDADO', 'updated_at' => now()]);

                $this->ensureTalonarioNumeros($tid);
                DB::table('talonario_numeros')
                    ->where('talonario_id', $tid)
                    ->where('estado', 'DISPONIBLE')
                    ->update([
                        'estado' => 'LIQUIDADO',
                        'liquidacion_id' => $liqId,
                        'updated_at' => now()
                    ]);
            }

            foreach ($numeros as $tid => $arr) {
                $tid = (int)$tid;
                if (!is_array($arr) || empty($arr)) continue;
                if (in_array($tid, array_map('intval', $talonarios), true)) continue;

                $this->ensureTalonarioNumeros($tid);

                foreach ($arr as $num) {
                    $num = (int)$num;

                    DB::table('liquidacion_numeros')->insert([
                        'liquidacion_id' => $liqId,
                        'talonario_id'   => $tid,
                        'numero'         => $num,
                        'created_at'     => now(),
                        'updated_at'     => now(),
                    ]);

                    DB::table('talonario_numeros')
                        ->where('talonario_id', $tid)
                        ->where('numero', $num)
                        ->update([
                            'estado' => 'LIQUIDADO',
                            'liquidacion_id' => $liqId,
                            'updated_at' => now()
                        ]);
                }

                $pend = DB::table('talonario_numeros')
                    ->where('talonario_id', $tid)
                    ->where('estado', 'DISPONIBLE')
                    ->count();

                if ($pend === 0) {
                    DB::table('talonarios')
                        ->where('id_talonario', $tid)
                        ->update(['estado' => 'LIQUIDADO', 'updated_at' => now()]);
                }
            }

            foreach ($boletas as $i => $b) {
                $idBanco = (int)($b['id_banco']);
                $tipo    = strtoupper((string)($b['tipo_pago']));
                $numBol  = trim((string)($b['numero_boleta']));
                $fecha   = (string)($b['fecha_boleta']);
                $monto   = round((float)($b['monto']), 2);

                $archivo = null; $ruta = null; $mime = null; $size = null;

                if ($request->hasFile("boletas.$i.archivo")) {
                    $file = $request->file("boletas.$i.archivo");
                    if ($file && $file->isValid()) {
                        $path = $file->store("public/boletas");
                        $ruta = Storage::url($path);
                        $archivo = $file->getClientOriginalName();
                        $mime = $file->getMimeType();
                        $size = $file->getSize();
                    }
                }

                DB::table('boletas')->insert([
                    'id_liquidacion'  => $liqId,
                    'id_banco'        => $idBanco,
                    'tipo_pago'       => $tipo,
                    'numero_boleta'   => $numBol,
                    'fecha_boleta'    => $fecha,
                    'monto'           => $monto,
                    'archivo_nombre'  => $archivo,
                    'archivo_ruta'    => $ruta,
                    'archivo_mime'    => $mime,
                    'archivo_size'    => $size,
                    'created_at'      => now(),
                ]);
            }

            return redirect()
                ->route('ventas.liquidaciones.show', $liqId)
                ->with('ok', $excedente > 0
                    ? "Liquidación #{$liqId} guardada. Excedente Q{$excedente} DONADO."
                    : "Liquidación #{$liqId} guardada.");
        });
    }
}

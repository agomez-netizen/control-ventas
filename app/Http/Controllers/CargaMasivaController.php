<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;

class CargaMasivaController extends Controller
{
    private array $tmCache = []; // usuario_tm => id_usuario

    private function requireAdmin()
    {
        $u = session('user');
        $rol = is_array($u) ? ($u['id_rol'] ?? null) : ($u->id_rol ?? null);
        if ((int)$rol !== 1) abort(403, 'Solo un administrador puede usar la Carga Masiva.');
    }

    private function currentUserId(): ?int
    {
        $u = session('user');
        return (int)(is_array($u) ? ($u['id_usuario'] ?? 0) : ($u->id_usuario ?? 0)) ?: null;
    }

    public function index()
    {
        $this->requireAdmin();
        return view('carga.index');
    }

    public function preview(Request $request)
    {
        $this->requireAdmin();

        $request->validate([
            'archivo' => 'required|file|mimes:xlsx,xls|max:10240',
        ]);

        $path = $request->file('archivo')->store('cargas_tmp');

        [$headers, $rows] = $this->leerExcel(Storage::path($path));
        $previewRows = array_slice($rows, 0, 50);

        $token = 'carga_' . now()->format('Ymd_His') . '_' . substr(md5($path), 0, 8);
        Storage::put("cargas_tmp/{$token}.json", json_encode([
            'path' => $path,
            'headers' => $headers,
            'rows' => $rows,
        ], JSON_UNESCAPED_UNICODE));

        return view('carga.index', [
            'token' => $token,
            'headers' => $headers,
            'previewRows' => $previewRows,
            'totalRows' => count($rows),
        ]);
    }

    public function procesar(Request $request)
    {
        $this->requireAdmin();

        $request->validate([
            'token' => 'required|string',
        ]);

        $jsonPath = "cargas_tmp/{$request->token}.json";
        if (!Storage::exists($jsonPath)) {
            return back()->with('err', 'No encontré el preview. Sube el archivo otra vez.');
        }

        $payload = json_decode(Storage::get($jsonPath), true);
        $rows = $payload['rows'] ?? [];

        $ok = 0;
        $fail = 0;
        $errors = [];

        $insEst = 0;
        $updEst = 0;

        $this->tmCache = [];

        foreach ($rows as $i => $row) {
            try {
                DB::beginTransaction();

                $data = $this->mapearFila($row);

                [$cIns, $cUpd] = $this->importarFila($data);
                $insEst += $cIns;
                $updEst += $cUpd;

                DB::commit();
                $ok++;
            } catch (\Throwable $e) {
                DB::rollBack();
                $fail++;
                $errors[] = "Fila " . ($i + 2) . ": " . $e->getMessage();
            }
        }

        Storage::delete($payload['path'] ?? '');
        Storage::delete($jsonPath);

        return redirect()->route('carga.index')
            ->with('ok', "Procesado: {$ok} OK, {$fail} con error. Estaciones: {$insEst} insertadas, {$updEst} actualizadas.")
            ->with('carga_errors', array_slice($errors, 0, 25));
    }

    /**
     * Retorna: [estaciones_insertadas, estaciones_actualizadas]
     */
    private function importarFila(array $d): array
    {
        // Validación base
        if (!$d['pl']) throw new \Exception("PL vacío. Debe ser RBA, DEALER o NOLA.");
        $plVal = $this->normalizarPL($d['pl']);

        if (!$d['estacion']) throw new \Exception("ESTACION vacía.");
        if (!$d['tm']) throw new \Exception("TM vacío.");
        if (!$d['tm_usuario']) throw new \Exception("USUARIO (del TM) vacío.");

        // 1) TM por usuario (NO DUPLICAR)
        $idTM = $this->upsertTMporUsuario(
            $d['tm'],
            $d['tm_usuario'],
            $d['tm_pass'],
            $plVal
        );

        // 2) Operador (opcional)
        $idOperador = null;
        if (!empty($d['operador'])) {
            $idOperador = $this->upsertOperadorBasico(
                $d['operador'],
                $idTM,
                $d['celular_operador'] ?? null,
                $plVal
            );
        }

        // 3) Estación (identificación por nombre+municipio+departamento)
        [$idEstacion, $created] = $this->upsertEstacionPorNombre($d, $idOperador, $plVal);

        // 4) Talonarios (opcional)
        $this->generarYAsignarTalonarios($d, $idEstacion);

        return [$created ? 1 : 0, $created ? 0 : 1];
    }

    /**
     * TM: se identifica por usuarios.usuario (UNIQUE).
     * PASS: solo se setea/actualiza si viene lleno.
     * Si el TM NO existe y PASS viene vacío => error (según tu criterio).
     */
    private function upsertTMporUsuario(string $tmNombreCompleto, string $usuarioTM, ?string $passTM, string $plVal): int
    {
        $usuarioTM = trim($usuarioTM);
        $passTM = $passTM !== null ? trim((string)$passTM) : null;

        if ($usuarioTM === '') throw new \Exception("USUARIO (TM) vacío.");

        // cache
        if (isset($this->tmCache[$usuarioTM])) {
            return (int)$this->tmCache[$usuarioTM];
        }

        $idRolTM = (int) DB::table('roles')->where('nombre', 'TM')->value('id_rol');
        if (!$idRolTM) throw new \Exception("No existe rol TM en tabla roles.");

        [$nombre, $apellido] = $this->splitNombre($tmNombreCompleto);

        $existing = DB::table('usuarios')->where('usuario', $usuarioTM)->first();

        if (!$existing) {
            if (!$passTM) {
                throw new \Exception("El TM '{$tmNombreCompleto}' (usuario '{$usuarioTM}') no existe y PASS viene vacío. La primera vez debe traer PASS.");
            }

            $id = (int) DB::table('usuarios')->insertGetId([
                'nombre' => $nombre,
                'apellido' => $apellido,
                'telefono' => null,
                'usuario' => $usuarioTM,
                'pass' => $passTM,
                'id_rol' => $idRolTM,
                'id_tm' => null,
                'id_operador' => null,
                'estado' => 1,
                'pl' => $plVal, // ✅ obligatorio
            ]);

            $this->tmCache[$usuarioTM] = $id;
            return $id;
        }

        // Existe: actualizar nombre/apellido y pl; PASS solo si viene lleno
        $update = [
            'nombre' => $nombre,
            'apellido' => $apellido,
            'id_rol' => $idRolTM,
            'pl' => $plVal,
        ];
        if ($passTM) {
            $update['pass'] = $passTM;
        }

        DB::table('usuarios')->where('id_usuario', $existing->id_usuario)->update($update);

        $this->tmCache[$usuarioTM] = (int)$existing->id_usuario;
        return (int)$existing->id_usuario;
    }

    /**
     * Operador básico (si viene en el Excel).
     * Identificación: nombre+apellido + rol OPERADOR.
     */
private function upsertOperadorBasico(string $operadorNombre, int $idTM, ?string $telefono, string $plVal): int
{
    $idRol = (int) DB::table('roles')->where('nombre', 'OPERADOR')->value('id_rol');
    if (!$idRol) throw new \Exception("No existe rol OPERADOR.");

    [$nombre, $apellido] = $this->splitNombre($operadorNombre);

    // ✅ CLAVE: el operador se busca por nombre+apellido+rol+ID_TM
    // así el mismo nombre puede existir bajo distintos TM sin pisarse.
    $existing = DB::table('usuarios')
        ->where('nombre', $nombre)
        ->where('apellido', $apellido)
        ->where('id_rol', $idRol)
        ->where('id_tm', $idTM)
        ->first();

    if (!$existing) {
        $base = $this->slugUser($nombre . '.' . $apellido) ?: 'operador';
        $username = $this->usernameDisponible($base);

        return (int) DB::table('usuarios')->insertGetId([
            'nombre' => $nombre,
            'apellido' => $apellido,
            'telefono' => $telefono,
            'usuario' => $username,
            'pass' => '123',
            'id_rol' => $idRol,
            'id_tm' => $idTM,          // ✅ se fija el TM correcto
            'id_operador' => null,
            'estado' => 1,
            'pl' => $plVal,
        ]);
    }

    // ✅ Update sin cambiar de TM (se queda en ese TM)
    DB::table('usuarios')->where('id_usuario', $existing->id_usuario)->update([
        'telefono' => $telefono ?? $existing->telefono,
        'pl' => $plVal,
    ]);

    return (int)$existing->id_usuario;
}


    /**
     * Estación: llave por nombre+municipio+departamento.
     * PL se guarda como dato.
     */
    private function upsertEstacionPorNombre(array $d, ?int $idOperador, string $plVal): array
    {
        $nombre = trim((string)($d['estacion'] ?? ''));
        if ($nombre === '') throw new \Exception("ESTACION vacía.");

        $municipio = trim((string)($d['municipio'] ?? 'GUATEMALA'));
        $departamento = trim((string)($d['departamento'] ?? 'GUATEMALA'));
        $direccion = trim((string)($d['direccion_estacion'] ?? 'SIN DIRECCIÓN'));

        $data = [
            'pl' => $plVal,
            'id_operador' => $idOperador, // puede ser null
            'nombre' => $nombre,
            'telefono' => $d['tel_estacion'] ?? null,
            'correo' => $d['email'] ?? null,
            'pais' => 'GUATEMALA',
            'departamento' => $departamento ?: 'GUATEMALA',
            'municipio' => $municipio ?: 'GUATEMALA',
            'direccion' => $direccion ?: 'SIN DIRECCIÓN',
            'coordenada_1' => $this->toDecimal($d['coord1'] ?? null),
            'coordenada_2' => $this->toDecimal($d['coord2'] ?? null),
            'activa' => 1,
        ];

        $existing = DB::table('estaciones')
            ->where('nombre', $data['nombre'])
            ->where('municipio', $data['municipio'])
            ->where('departamento', $data['departamento'])
            ->first();

        if (!$existing) {
            $id = (int) DB::table('estaciones')->insertGetId($data);
            return [$id, true];
        }

        DB::table('estaciones')->where('id_estacion', $existing->id_estacion)->update($data);
        return [(int)$existing->id_estacion, false];
    }

    private function generarYAsignarTalonarios(array $d, int $idEstacion): void
    {
        $noTalonarios = (int)($d['no_talonarios'] ?? 0);
        $talDel = (int)($d['tal_del_1'] ?? 0);
        $talAl  = (int)($d['tal_al'] ?? 0);

        $numDel = (int)($d['numero_del_1'] ?? 0);
        $numAl  = (int)($d['numero_al'] ?? 0);

        if ($noTalonarios <= 0 || $talDel <= 0 || $talAl <= 0) return;
        if ($talAl < $talDel) throw new \Exception("Rango de talonarios inválido ({$talDel}-{$talAl}).");

        $cantidadTalonarios = ($talAl - $talDel + 1);

        $totalNums = ($numDel > 0 && $numAl > 0 && $numAl >= $numDel) ? ($numAl - $numDel + 1) : 0;
        $porTalonario = $totalNums > 0 ? intdiv($totalNums, $cantidadTalonarios) : 20;
        $sobrante = $totalNums > 0 ? ($totalNums % $cantidadTalonarios) : 0;

        $cursor = $numDel;
        $asignadoPor = $this->currentUserId() ?: 1;

        for ($t = $talDel; $t <= $talAl; $t++) {
            $extras = ($sobrante > 0) ? 1 : 0;
            if ($sobrante > 0) $sobrante--;

            $inicio = ($totalNums > 0) ? $cursor : 1;
            $fin = ($totalNums > 0) ? ($cursor + $porTalonario + $extras - 1) : 20;
            if ($totalNums > 0) $cursor = $fin + 1;

            $exists = DB::table('talonarios')->where('numero_talonario', $t)->first();

            $payload = [
                'numero_talonario' => $t,
                'numero_inicio' => $inicio,
                'numero_fin' => $fin,
                'cantidad_numeros' => max(1, ($fin - $inicio + 1)),
                'id_estacion' => $idEstacion,
                'asignado_en' => now(),
                'estado' => 'ASIGNADO',
            ];

            if (!$exists) {
                $idTalonario = (int) DB::table('talonarios')->insertGetId($payload);
            } else {
                DB::table('talonarios')->where('id_talonario', $exists->id_talonario)->update($payload);
                $idTalonario = (int)$exists->id_talonario;
            }

            $ya = DB::table('asignaciones_talonarios')
                ->where('id_talonario', $idTalonario)
                ->where('id_estacion', $idEstacion)
                ->exists();

            if (!$ya) {
                DB::table('asignaciones_talonarios')->insert([
                    'id_talonario' => $idTalonario,
                    'id_estacion' => $idEstacion,
                    'asignado_por' => $asignadoPor,
                    'asignado_en' => now(),
                    'nota' => 'Carga masiva desde Excel',
                ]);
            }
        }
    }

    private function leerExcel(string $fullPath): array
    {
        if (!class_exists(\PhpOffice\PhpSpreadsheet\IOFactory::class)) {
            throw new \Exception("Falta PhpSpreadsheet. Instala con: composer require phpoffice/phpspreadsheet");
        }

        $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($fullPath);
        $sheet = $spreadsheet->getActiveSheet();
        $raw = $sheet->toArray(null, true, true, true);

        if (count($raw) < 2) return [[], []];

        $headerRow = array_shift($raw);
        $headers = [];
        foreach ($headerRow as $col => $name) {
            $name = trim((string)$name);
            if ($name !== '') $headers[$col] = $name;
        }

        $rows = [];
        foreach ($raw as $r) {
            $fila = [];
            $hasAny = false;

            foreach ($headers as $col => $hName) {
                $val = $r[$col] ?? null;
                if (is_string($val)) $val = trim($val);
                if ($val !== null && $val !== '') $hasAny = true;
                $fila[$hName] = $val;
            }

            if ($hasAny) $rows[] = $fila;
        }

        return [array_values($headers), $rows];
    }

    /**
     * Mapeo robusto: aguanta tildes/espacios y "contiene".
     * TM:
     *  - TM (nombre completo)
     *  - USUARIO (del TM)
     *  - PASS (del TM) => puede venir vacío en repeticiones
     */
    private function mapearFila(array $row): array
    {
        $getLike = function(array $needles) use ($row) {
            foreach ($row as $k => $v) {
                $key = $this->norm((string)$k);
                foreach ($needles as $n) {
                    if (str_contains($key, $this->norm($n))) {
                        return is_string($v) ? trim($v) : $v;
                    }
                }
            }
            return null;
        };

        return [
            'pl' => $getLike(['PL']),
            'estacion' => $getLike(['ESTACION']),
            'tm' => $getLike(['TM']),
            'tm_usuario' => $getLike(['USUARIO']),
            'tm_pass' => $getLike(['PASS', 'CONTRASENA', 'CONTRASEÑA']),
            'operador' => $getLike(['OPERADOR']),
            'tel_estacion' => $getLike(['TEL ESTACION', 'TEL']),
            'celular_operador' => $getLike(['CELULAR OPERADOR', 'CELULAR']),
            'email' => $getLike(['EMAIL', 'CORREO']),
            'direccion_estacion' => $getLike(['DIRECCION ESTACION', 'DIRECCIÓN ESTACION', 'DIRECCION', 'DIRECCIÓN']),
            'coord1' => $getLike(['COORDENADAS 1', 'COORDENADA 1', 'COORDENAD']),
            'coord2' => $getLike(['COORDENADAS 2', 'COORDENADA 2']),
            'municipio' => $getLike(['MUNICIPIO']),
            'departamento' => $getLike(['DEPARTAMENTO']),
            'no_talonarios' => $getLike(['NO. TALONARIOS', 'NO TALONARIOS']),
            'tal_del_1' => $getLike(['TAL DEL 1', 'TAL DEL']),
            'tal_al' => $getLike(['TAL AL']),
            'cantidad_numeros' => $getLike(['CANTIDAD NUMEROS', 'CANTIDAD']),
            'numero_del_1' => $getLike(['NUMERO DEL 1', 'NUMERO DEL']),
            'numero_al' => $getLike(['NUMERO AL']),
        ];
    }

    private function normalizarPL(string $pl): string
    {
        $pl = strtoupper(trim($pl));
        if (!in_array($pl, ['RBA', 'DEALER', 'NOLA'], true)) {
            throw new \Exception("PL inválido: '{$pl}'. Solo se permite RBA, DEALER o NOLA.");
        }
        return $pl;
    }

    private function splitNombre(string $full): array
    {
        $full = trim(preg_replace('/\s+/', ' ', $full));
        if ($full === '') return ['NA', 'NA'];
        $parts = explode(' ', $full);
        $nombre = array_shift($parts);
        $apellido = count($parts) ? implode(' ', $parts) : '—';
        return [$nombre, $apellido];
    }

    private function slugUser(string $s): string
    {
        $s = strtolower($s);
        $s = iconv('UTF-8', 'ASCII//TRANSLIT', $s);
        $s = preg_replace('/[^a-z0-9._-]+/', '', $s);
        return trim($s, '._-');
    }

    private function usernameDisponible(string $base): string
    {
        $base = substr($base, 0, 45);
        $u = $base;
        $n = 1;

        while (DB::table('usuarios')->where('usuario', $u)->exists()) {
            $n++;
            $u = substr($base, 0, max(1, 45 - (strlen((string)$n) + 1))) . '_' . $n;
        }
        return $u;
    }

    private function toDecimal($val): ?float
    {
        if ($val === null || $val === '') return null;
        $s = trim((string)$val);
        if (preg_match('/[NSEW°\']/', $s)) return null;
        $s = str_replace(',', '.', $s);
        if (!is_numeric($s)) return null;
        return (float)$s;
    }

    private function norm(string $s): string
    {
        $s = trim(mb_strtolower($s));
        $s = str_replace(["\u{00A0}"], ' ', $s);
        $s = preg_replace('/\s+/', ' ', $s);
        $map = ['á'=>'a','é'=>'e','í'=>'i','ó'=>'o','ú'=>'u','ñ'=>'n'];
        return strtr($s, $map);
    }
}

<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class RegistroController extends Controller
{
    public function index()
    {
        // Roles
        $roles = DB::table('roles')
            ->select('id_rol', 'nombre')
            ->orderBy('nombre')
            ->get();

        // TMs (usuarios con rol TM)
        $tms = DB::table('usuarios as u')
            ->join('roles as r', 'r.id_rol', '=', 'u.id_rol')
            ->whereRaw('UPPER(TRIM(r.nombre)) = ?', ['TM'])
            ->select('u.id_usuario', DB::raw("CONCAT(u.nombre,' ',u.apellido,' (',u.usuario,')') as label"))
            ->orderBy('u.nombre')
            ->get();

        // Operadores (usuarios con rol OP / OPERADOR)
        $operadores = DB::table('usuarios as u')
            ->join('roles as r', 'r.id_rol', '=', 'u.id_rol')
            ->whereIn(DB::raw('UPPER(TRIM(r.nombre))'), ['OP', 'OPERADOR', 'OPERADORES'])
            ->select('u.id_usuario', DB::raw("CONCAT(u.nombre,' ',u.apellido,' (',u.usuario,')') as label"))
            ->orderBy('u.nombre')
            ->get();

        return view('registro.index', compact('roles', 'tms', 'operadores'));
    }

    public function storeUsuario(Request $request)
    {
        $request->validate([
            'nombre'   => ['required','string','max:80'],
            'apellido' => ['required','string','max:80'],
            'telefono' => ['nullable','string','max:20'],
            'usuario'  => ['required','string','max:50'],
            'pass'     => ['required','string','min:3','max:100'],
            'id_rol'   => ['required','integer', 'exists:roles,id_rol'],

            // Se vuelven requeridos según rol
            'id_tm'       => ['nullable','integer', 'exists:usuarios,id_usuario'],
            'id_operador' => ['nullable','integer', 'exists:usuarios,id_usuario'],

            'estado'   => ['required', Rule::in(['0','1',0,1])],

            // NUEVO: PL
            'pl' => ['required', Rule::in(['RBA','DEALER'])],
        ], [
            'pl.required' => 'Te faltó seleccionar el PL (RBA o DEALER).',
            'pl.in'       => 'PL inválido. Solo puede ser RBA o DEALER.',
            'usuario.required' => 'El usuario (login) es obligatorio.',
            'usuario.max' => 'El usuario (login) no puede exceder 50 caracteres.',
            'id_rol.exists' => 'El rol seleccionado no existe.',
        ]);

        // Normalizamos el usuario para evitar duplicados por espacios/case
        $usuarioLogin = trim($request->usuario);

        // Rol
        $rol = DB::table('roles')->where('id_rol', (int)$request->id_rol)->first();
        $rolName = strtoupper(trim($rol->nombre ?? ''));

        $isOperador = in_array($rolName, ['OP', 'OPERADOR', 'OPERADORES'], true);
        $isAdmOp = in_array($rolName, ['ADMOP', 'ADMIN OPERADOR', 'ADMIN_OPERADOR', 'ADMINISTRADOR OPERADOR'], true);

        // Reglas por rol
        if ($isOperador && !$request->id_tm) {
            return back()->withInput()->with('error', 'Para crear un Operador debes seleccionar un TM. 👀');
        }

        if ($isAdmOp && !$request->id_operador) {
            return back()->withInput()->with('error', 'Para crear un Administrador de Operador debes seleccionar un Operador. 👀');
        }

        // Usuario único (ya tenés índice UNIQUE, pero validamos bonito antes de explotar)
        $exists = DB::table('usuarios')
            ->whereRaw('TRIM(usuario) = ?', [$usuarioLogin])
            ->exists();

        if ($exists) {
            return back()->withInput()->with('error', 'Ese usuario (login) ya existe. Probá con otro y seguimos vivos ✅');
        }

        DB::table('usuarios')->insert([
            'nombre'      => trim($request->nombre),
            'apellido'    => trim($request->apellido),
            'telefono'    => $request->telefono ? trim($request->telefono) : null,
            'usuario'     => $usuarioLogin,
            'pass'        => Hash::make($request->pass), // ⚠️ si tu login usa texto plano, cambia esto
            'id_rol'      => (int)$request->id_rol,
            'pl'          => $request->pl, // ✅ NUEVO
            'id_tm'       => $isOperador ? (int)$request->id_tm : null,
            'id_operador' => $isAdmOp ? (int)$request->id_operador : null,
            'estado'      => (int)$request->estado,
        ]);

        return back()->with('ok', 'Usuario registrado correctamente ✅');
    }

    public function storeEstacion(Request $request)
    {
        $request->validate([
            'id_operador'  => ['required','integer', 'exists:usuarios,id_usuario'],
            'nombre'       => ['required','string','max:120'],

            'telefono'     => ['nullable','string','max:30'],
            'correo'       => ['nullable','email','max:120'],

            'pais'         => ['required','string','max:60'],
            'departamento' => ['required','string','max:80'],
            'municipio'    => ['required','string','max:80'],
            'direccion'    => ['required','string','max:255'],

            'coordenada_1' => ['nullable','numeric'],
            'coordenada_2' => ['nullable','numeric'],
            'activa'       => ['required', Rule::in(['0','1',0,1])],
        ]);

        // No duplica por nombre (como tu regla)
        $nombre = trim($request->nombre);
        $ya = DB::table('estaciones')
            ->whereRaw('TRIM(nombre) = ?', [$nombre])
            ->exists();

        if ($ya) {
            return back()->withInput()->with('error', 'Esa estación ya existe (mismo nombre). No la duplico 😄');
        }

        DB::table('estaciones')->insert([
            'id_operador'  => (int)$request->id_operador,
            'nombre'       => $nombre,
            'telefono'     => $request->telefono ? trim($request->telefono) : null,
            'correo'       => $request->correo ? trim($request->correo) : null,
            'pais'         => trim($request->pais ?? 'Guatemala'),
            'departamento' => trim($request->departamento),
            'municipio'    => trim($request->municipio),
            'direccion'    => trim($request->direccion),
            'coordenada_1' => $request->coordenada_1,
            'coordenada_2' => $request->coordenada_2,
            'activa'       => (int)$request->activa,
        ]);

        return back()->with('ok', 'Estación registrada correctamente ✅');
    }
}

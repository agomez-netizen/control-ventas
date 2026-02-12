<?php

namespace App\Http\Controllers;

use App\Models\Usuario;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function show()
    {
        return view('auth.login');
    }

    private function isBcryptHash(?string $value): bool
    {
        if (!$value) return false;

        // bcrypt normalmente inicia con $2y$, $2a$ o $2b$
        return str_starts_with($value, '$2y$')
            || str_starts_with($value, '$2a$')
            || str_starts_with($value, '$2b$');
    }

    public function login(Request $request)
    {
        $request->validate([
            'usuario'  => ['required','string'],
            'password' => ['required','string'],
        ]);

        $user = Usuario::with('rol')
            ->where('usuario', $request->usuario)
            ->where('estado', 1)
            ->first();

        if (!$user) {
            return back()->withErrors(['usuario' => 'Usuario o contraseña incorrectos'])->withInput();
        }

        $stored = (string) $user->pass;
        $input  = (string) $request->password;

        // ✅ Si ya es bcrypt, validamos con Hash::check
        if ($this->isBcryptHash($stored)) {
            if (!Hash::check($input, $stored)) {
                return back()->withErrors(['usuario' => 'Usuario o contraseña incorrectos'])->withInput();
            }
        } else {
            // 🩹 Si está en texto plano, comparamos directo
            if ($input !== $stored) {
                return back()->withErrors(['usuario' => 'Usuario o contraseña incorrectos'])->withInput();
            }

            // y convertimos a bcrypt en el primer login
            $user->pass = Hash::make($input);
            $user->save();
        }

        // ✅ Login OK
        $request->session()->regenerate();
        $request->session()->regenerateToken();
        $request->session()->put('user', [
            'id_usuario' => $user->id_usuario,
            'nombre'     => $user->nombre,
            'apellido'   => $user->apellido,
            'usuario'    => $user->usuario,
            'rol'        => $user->rol?->nombre,
            'id_rol'     => $user->id_rol,
        ]);

        return redirect()->route('dashboard');
    }

    public function logout(Request $request)
    {
        $request->session()->forget('user');
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}

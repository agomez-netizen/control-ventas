@extends('layouts.guest')
@section('title', 'Login')

@section('content')
<div class="login-bg">
  <div class="w-100" style="max-width: 520px;">

    <div class="card login-card glass">
      <div class="card-body p-4 p-md-5">

        <div class="card-logo">
          <img src="{{ asset('img/rifa.png') }}" alt="AA POS">
        </div>

        <h1 class="h3 mb-1 fw-bold text-center">Iniciar sesión</h1>
        <p class="subtle mb-4 text-center">Bienvenido</p>

        @if ($errors->any())
          <div class="alert alert-danger rounded-4">
            {{ $errors->first() }}
          </div>
        @endif

        <form method="POST" action="{{ route('login.post') }}">
          @csrf

          <div class="mb-3">
            <label class="form-label fw-semibold">Usuario</label>
            <input type="text" name="usuario" value="{{ old('usuario') }}"
                   class="form-control form-control-lg" required autofocus>
          </div>

          <div class="mb-3">
            <label class="form-label fw-semibold">Contraseña</label>
            <input type="password" name="password"
                   class="form-control form-control-lg" required>
          </div>

          <button type="submit" class="btn btn-primary btn-lg w-100 btn-login">
            Entrar
          </button>
        </form>
      </div>
    </div>

    <div class="foot">
      Gestión de Ventas • RIFA 2026
    </div>
  </div>
</div>
@endsection

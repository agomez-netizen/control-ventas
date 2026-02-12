<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>@yield('title', 'GESTION DE VENTAS SHELL')</title>

  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">

  <link rel="stylesheet" href="{{ asset('css/app.css') }}?v={{ time() }}">


</head>


<body>

@php
  $u = session('user');

  // Soporta array u objeto
  $nombre   = is_array($u) ? ($u['nombre'] ?? '') : ($u->nombre ?? '');
  $apellido = is_array($u) ? ($u['apellido'] ?? '') : ($u->apellido ?? '');
  $rol      = is_array($u) ? ($u['rol'] ?? null) : ($u->rol ?? null);

  $fullName = trim($nombre . ' ' . $apellido) ?: 'Usuario';
@endphp

<nav class="navbar app-navbar">
  <div class="container-fluid d-flex align-items-center gap-2">

    {{-- Toggle desktop --}}
    <button class="btn btn-toggle d-none d-lg-inline-flex"
            id="toggleSidebar"
            type="button"
            aria-label="Ocultar menú">
      ☰
    </button>

    {{-- Toggle móvil --}}
    <button class="btn btn-toggle d-lg-none"
            type="button"
            data-bs-toggle="offcanvas"
            data-bs-target="#sidebarMobile"
            aria-controls="sidebarMobile"
            aria-label="Abrir menú">
      ☰
    </button>

   <a class="navbar-brand ms-2 d-flex align-items-center gap-2" href="#">
  <img src="{{ asset('img/shell.png') }}" alt="Logo" class="brand-logo">
  <span>Gestión RIFA 2026</span>
</a>


    {{-- Usuario --}}
    <div class="ms-auto d-flex align-items-center gap-2 userbox">

      <div class="userinfo d-flex align-items-center gap-2 d-none d-sm-flex">
        <i class="bi bi-person-circle user-icon"></i>

        <div class="text-end">
          <div class="uname text-truncate">{{ $fullName }}</div>
          @if($rol)
            <div class="urol text-truncate">{{ $rol }}</div>
          @endif
        </div>
      </div>

      <form method="POST" action="{{ route('logout') }}" class="m-0">
        @csrf
        <button type="submit"
                class="btn btn-outline-light btn-sm logout-btn"
                aria-label="Cerrar sesión">
          <span class="d-none d-md-inline">Cerrar sesión</span>
          <span class="d-inline d-md-none">⎋</span>
        </button>
      </form>
    </div>

  </div>
</nav>

<div class="app-shell" id="appShell">

  {{-- ================= SIDEBAR DESKTOP ================= --}}
  <aside class="sidebar d-none d-lg-flex" id="sidebarDesktop">
    <div class="sidebar-inner">
      @include('partials.sidebar', ['scope' => 'desk'])
    </div>
  </aside>

  {{-- ================= SIDEBAR MÓVIL ================= --}}
  <div class="offcanvas offcanvas-start d-lg-none sidebar-offcanvas"
       tabindex="-1"
       id="sidebarMobile"
       aria-labelledby="sidebarMobileLabel">

    <div class="offcanvas-header">
      <h5 class="offcanvas-title" id="sidebarMobileLabel">Menú</h5>
      <button type="button"
              class="btn-close btn-close-white"
              data-bs-dismiss="offcanvas"
              aria-label="Cerrar">
      </button>
    </div>

    <div class="offcanvas-body p-0">
      <div class="sidebar-inner">
        @include('partials.sidebar', ['scope' => 'mob'])
      </div>
    </div>
  </div>

  {{-- ================= CONTENIDO ================= --}}
  <main class="main">
    @yield('content')
  </main>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="{{ asset('js/app.js') }}?v={{ time() }}"></script>


{{-- Toggle sidebar desktop --}}
<script>
(function () {
  const btn = document.getElementById('toggleSidebar');
  const shell = document.getElementById('appShell');
  if (!btn || !shell) return;

  btn.addEventListener('click', () => {
    shell.classList.toggle('collapsed');
  });
})();


document.addEventListener('DOMContentLoaded', function () {
  const els = document.querySelectorAll('[data-bs-toggle="tooltip"]');
  els.forEach(el => new bootstrap.Tooltip(el));
});

</script>
@stack('scripts')
</body>
</html>

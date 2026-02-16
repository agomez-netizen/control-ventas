@php
  $u = session('user');
  $rolId = (int)($u['id_rol'] ?? 0);
  $isAdmin = ($rolId === 1);
@endphp

<div>
  <div class="sidebar-title">Navegación</div>
</div>

<div class="navlist">

  {{-- ================= DASHBOARD ================= --}}
  <a href="{{ route('dashboard') }}"
     class="navitem {{ request()->routeIs('dashboard') ? 'active' : '' }}"
     data-bs-toggle="tooltip"
     data-bs-placement="right"
     data-bs-container="body"
     title="Dashboard">
    <span class="navicon">📊</span>
    <span>Dashboard</span>
  </a>

  {{-- ================= TALONARIOS ================= --}}
  <a href="{{ route('ventas.talonarios.index') }}"
     class="navitem {{ request()->routeIs('ventas.talonarios*') ? 'active' : '' }}"
     data-bs-toggle="tooltip"
     data-bs-placement="right"
     data-bs-container="body"
     title="Gestión de talonarios">
    <span class="navicon">📘</span>
    <span>Talonarios</span>
  </a>

  {{-- ================= LIQUIDACIONES ================= --}}
  <a href="{{ route('ventas.liquidaciones.index') }}"
     class="navitem {{ request()->routeIs('ventas.liquidaciones*') ? 'active' : '' }}"
     data-bs-toggle="tooltip"
     data-bs-placement="right"
     data-bs-container="body"
     title="Liquidaciones">
    <span class="navicon">💰</span>
    <span>Liquidaciones</span>
  </a>


@if($isAdmin)
  {{-- ================= REGISTRO ================= --}}
<a href="{{ route('registro.index') }}"
   class="navitem {{ request()->routeIs('registro.*') ? 'active' : '' }}"
   data-bs-toggle="tooltip"
   data-bs-placement="right"
   data-bs-container="body"
   title="Registro de usuarios y estaciones">
  <span class="navicon">🧾</span>
  <span>Registro</span>
</a>

<a href="{{ route('asignaciones.talonarios.index') }}"
   class="navitem {{ request()->routeIs('asignaciones.talonarios.*') ? 'active' : '' }}"
   title="Asignación de talonarios">
  <span class="navicon">📘</span>
  <span>Asignar Talonarios</span>
</a>

{{-- ================= CARGA ================= --}}
<a href="{{ route('carga.index') }}"
   class="navitem {{ request()->routeIs('carga.*') ? 'active' : '' }}"
   data-bs-toggle="tooltip"
   data-bs-placement="right"
   data-bs-container="body"
   title="Carga masiva desde Excel">
  <span class="navicon">📥</span>
  <span>Carga</span>
</a>

@endif
  <hr class="my-2">

  {{-- ================= CERRAR SESIÓN ================= --}}
  <form method="POST" action="{{ route('logout') }}" class="navlogout">
    @csrf
    <button type="submit"
            class="navitem btn-reset navlogout-btn"
            data-bs-toggle="tooltip"
            data-bs-placement="right"
            data-bs-container="body"
            title="Cerrar la sesión actual">
      <span class="navicon">🚪</span>
      <span>Cerrar sesión</span>
    </button>
  </form>

</div>

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

@php
  use Illuminate\Support\Facades\Route;

  $u = session('user') ?? [];
  $rolId   = (int)($u['id_rol'] ?? 0);
  $rolName = strtoupper(trim((string)($u['rol'] ?? $u['nombre_rol'] ?? '')));
  $isAdmin = ($rolId === 1) || ($rolName === 'ADMIN');

  // Activos
  $onDashboard     = request()->routeIs('dashboard');
  $onTalonarios    = request()->routeIs('ventas.talonarios*');
  $onAsignaciones  = request()->routeIs('asignaciones.*') || request()->routeIs('asignaciones.talonarios.*');
  $onRegistro      = request()->routeIs('registro.*') || request()->routeIs('estaciones.*') || request()->routeIs('operadores.*');
  $onLiquidaciones = request()->routeIs('ventas.liquidaciones*');
  $onReportes      = request()->routeIs('ventas.reportes*');
  $onCargas        = request()->routeIs('carga.*') || request()->routeIs('cargas.*');

  // Submenús abiertos
  $openMisTalonarios = $onTalonarios || $onAsignaciones;
  $openRegistro      = $onRegistro;
  $openLiquidaciones = $onLiquidaciones;
  $openReportes      = $onReportes;
  $openCargas        = $onCargas;

  // Rutas seguras
  $urlAsignarTalonario = Route::has('asignaciones.talonarios.index')
      ? route('asignaciones.talonarios.index')
      : (Route::has('asignaciones.create') ? route('asignaciones.create') : '#');

  $urlEstaciones = Route::has('registro.estaciones.index')
      ? route('registro.estaciones.index')
      : (Route::has('estaciones.index') ? route('estaciones.index') : '#');

  $urlOperadores = Route::has('registro.operadores.index')
      ? route('registro.operadores.index')
      : (Route::has('operadores.index') ? route('operadores.index') : '#');

  $urlCargas = Route::has('carga.index')
      ? route('carga.index')
      : (Route::has('cargas.index') ? route('cargas.index') : '#');
@endphp

<div>
  <div class="sidebar-title">Navegación</div>
</div>

<div class="navlist" id="sidebarAccordion">

  {{-- DASHBOARD --}}
  <a href="{{ route('dashboard') }}"
     class="navitem {{ $onDashboard ? 'active' : '' }}"
     data-sidebar-tip
     data-bs-toggle="tooltip"
     data-bs-placement="right"
     data-bs-container="body"
     title="Dashboard">

    <div class="navleft">
      <div class="navicon-wrap bg-dashboard">
        <i class="bi bi-speedometer2"></i>
      </div>
      <span class="navtext">Dashboard</span>
    </div>
  </a>

  {{-- MIS TALONARIOS --}}
  <a class="navitem navitem-parent {{ $openMisTalonarios ? 'active' : '' }}"
     data-bs-toggle="collapse"
     href="#smTalonarios"
     aria-expanded="{{ $openMisTalonarios ? 'true' : 'false' }}"
     data-sidebar-tip
     data-bs-toggle="tooltip"
     data-bs-placement="right"
     data-bs-container="body"
     title="Mis Talonarios">

    <div class="navleft">
      <div class="navicon-wrap bg-talonarios">
        <i class="bi bi-journal-text"></i>
      </div>
      <span class="navtext">Mis Talonarios</span>
    </div>
    <i class="bi bi-chevron-down navchev"></i>
  </a>

  <div id="smTalonarios"
       class="collapse {{ $openMisTalonarios ? 'show' : '' }}"
       data-bs-parent="#sidebarAccordion">

    <a href="{{ Route::has('ventas.talonarios.index') ? route('ventas.talonarios.index') : '#' }}"
       class="navitem navitem-child {{ $onTalonarios ? 'active' : '' }}"
       data-sidebar-tip
       data-bs-toggle="tooltip"
       data-bs-placement="right"
       data-bs-container="body"
       title="Talonarios">

      <div class="navleft">
        <div class="navicon-wrap sm bg-talonarios">
          <i class="bi bi-list-ul"></i>
        </div>
        <span class="navtext">Talonarios</span>
      </div>
    </a>

    @if($isAdmin)
      <a href="{{ $urlAsignarTalonario }}"
         class="navitem navitem-child {{ $onAsignaciones ? 'active' : '' }}"
         data-sidebar-tip
         data-bs-toggle="tooltip"
         data-bs-placement="right"
         data-bs-container="body"
         title="Asignar Talonario">

        <div class="navleft">
          <div class="navicon-wrap sm bg-talonarios">
            <i class="bi bi-plus-square"></i>
          </div>
          <span class="navtext">Asignar Talonario</span>
        </div>
      </a>
    @endif
  </div>

  {{-- REGISTRO --}}
  @if($isAdmin)
    <a class="navitem navitem-parent {{ $openRegistro ? 'active' : '' }}"
       data-bs-toggle="collapse"
       href="#smRegistro"
       aria-expanded="{{ $openRegistro ? 'true' : 'false' }}"
       data-sidebar-tip
       data-bs-toggle="tooltip"
       data-bs-placement="right"
       data-bs-container="body"
       title="Registro">

      <div class="navleft">
        <div class="navicon-wrap bg-registro">
          <i class="bi bi-person-plus"></i>
        </div>
        <span class="navtext">Registro</span>
      </div>
      <i class="bi bi-chevron-down navchev"></i>
    </a>

    <div id="smRegistro"
         class="collapse {{ $openRegistro ? 'show' : '' }}"
         data-bs-parent="#sidebarAccordion">

      <a href="{{ $urlEstaciones }}"
         class="navitem navitem-child"
         data-sidebar-tip
         data-bs-toggle="tooltip"
         data-bs-placement="right"
         data-bs-container="body"
         title="Estación">

        <div class="navleft">
          <div class="navicon-wrap sm bg-registro">
            <i class="bi bi-geo-alt"></i>
          </div>
          <span class="navtext">Estación</span>
        </div>
      </a>

      <a href="{{ $urlOperadores }}"
         class="navitem navitem-child"
         data-sidebar-tip
         data-bs-toggle="tooltip"
         data-bs-placement="right"
         data-bs-container="body"
         title="Operador">

        <div class="navleft">
          <div class="navicon-wrap sm bg-registro">
            <i class="bi bi-person-badge"></i>
          </div>
          <span class="navtext">Operador</span>
        </div>
      </a>

    </div>
  @endif

  {{-- LIQUIDACIONES --}}
  <a class="navitem navitem-parent {{ $openLiquidaciones ? 'active' : '' }}"
     data-bs-toggle="collapse"
     href="#smLiquidaciones"
     aria-expanded="{{ $openLiquidaciones ? 'true' : 'false' }}"
     data-sidebar-tip
     data-bs-toggle="tooltip"
     data-bs-placement="right"
     data-bs-container="body"
     title="Liquidaciones">

    <div class="navleft">
      <div class="navicon-wrap bg-liquidaciones">
        <i class="bi bi-cash-stack"></i>
      </div>
      <span class="navtext">Liquidaciones</span>
    </div>
    <i class="bi bi-chevron-down navchev"></i>
  </a>

  <div id="smLiquidaciones"
       class="collapse {{ $openLiquidaciones ? 'show' : '' }}"
       data-bs-parent="#sidebarAccordion">

    <a href="{{ Route::has('ventas.liquidaciones.index') ? route('ventas.liquidaciones.index') : '#' }}"
       class="navitem navitem-child"
       data-sidebar-tip
       data-bs-toggle="tooltip"
       data-bs-placement="right"
       data-bs-container="body"
       title="Liquidar">

      <div class="navleft">
        <div class="navicon-wrap sm bg-liquidaciones">
          <i class="bi bi-receipt"></i>
        </div>
        <span class="navtext">Liquidar</span>
      </div>
    </a>
  </div>

  {{-- REPORTES --}}
  <a class="navitem navitem-parent {{ $openReportes ? 'active' : '' }}"
     data-bs-toggle="collapse"
     href="#smReportes"
     aria-expanded="{{ $openReportes ? 'true' : 'false' }}"
     data-sidebar-tip
     data-bs-toggle="tooltip"
     data-bs-placement="right"
     data-bs-container="body"
     title="Reportes">

    <div class="navleft">
      <div class="navicon-wrap bg-reportes">
        <i class="bi bi-bar-chart-line"></i>
      </div>
      <span class="navtext">Reportes</span>
    </div>
    <i class="bi bi-chevron-down navchev"></i>
  </a>

  <div id="smReportes"
       class="collapse {{ $openReportes ? 'show' : '' }}"
       data-bs-parent="#sidebarAccordion">

    <a href="{{ Route::has('ventas.reportes.index') ? route('ventas.reportes.index') : '#' }}"
       class="navitem navitem-child"
       data-sidebar-tip
       data-bs-toggle="tooltip"
       data-bs-placement="right"
       data-bs-container="body"
       title="Estatus">

      <div class="navleft">
        <div class="navicon-wrap sm bg-reportes">
          <i class="bi bi-clipboard-data"></i>
        </div>
        <span class="navtext">Estatus</span>
      </div>
    </a>
  </div>

  {{-- CARGAS --}}
  @if($isAdmin)
    <a class="navitem navitem-parent {{ $openCargas ? 'active' : '' }}"
       data-bs-toggle="collapse"
       href="#smCargas"
       aria-expanded="{{ $openCargas ? 'true' : 'false' }}"
       data-sidebar-tip
       data-bs-toggle="tooltip"
       data-bs-placement="right"
       data-bs-container="body"
       title="Cargas">

      <div class="navleft">
        <div class="navicon-wrap bg-cargas">
          <i class="bi bi-upload"></i>
        </div>
        <span class="navtext">Cargas</span>
      </div>
      <i class="bi bi-chevron-down navchev"></i>
    </a>

    <div id="smCargas"
         class="collapse {{ $openCargas ? 'show' : '' }}"
         data-bs-parent="#sidebarAccordion">

      <a href="{{ $urlCargas }}"
         class="navitem navitem-child"
         data-sidebar-tip
         data-bs-toggle="tooltip"
         data-bs-placement="right"
         data-bs-container="body"
         title="Carga XLS">

        <div class="navleft">
          <div class="navicon-wrap sm bg-cargas">
            <i class="bi bi-file-earmark-excel"></i>
          </div>
          <span class="navtext">Carga XLS</span>
        </div>
      </a>
    </div>
  @endif

  <hr class="my-3">

  {{-- LOGOUT --}}
  <form method="POST" action="{{ route('logout') }}" class="navlogout">
    @csrf
    <button type="submit"
            class="navitem btn-reset navlogout-btn"
            data-sidebar-tip
            data-bs-toggle="tooltip"
            data-bs-placement="right"
            data-bs-container="body"
            title="Cerrar sesión">

      <div class="navleft">
        <div class="navicon-wrap bg-logout">
          <i class="bi bi-box-arrow-right"></i>
        </div>
        <span class="navtext">Cerrar sesión</span>
      </div>
    </button>
  </form>

</div>
<div class="sidebar-footer mt-auto">
  <div class="small text-white-50 text-center">
    © {{ date('Y') }} RIFA 2026
  </div>
  <div class="small text-white-50 text-center">
    Todos los derechos reservados <br>
     Ingeniería que impulsa resultados <br> Ing. Aníbal Gómez
  </div>
</div>

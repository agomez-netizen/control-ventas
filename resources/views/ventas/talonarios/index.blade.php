@extends('layouts.app')

@section('content')
<div class="container">

  {{-- ===== Estilos para badges de estado + paginación ===== --}}
  <style>
    .badge-estado{
      padding: 4px 12px;
      border-radius: 999px;
      font-size: 12px;
      font-weight: 700;
      text-transform: uppercase;
      display: inline-block;
      line-height: 1.4;
      letter-spacing: .3px;
    }
    .estado-asignado{ background:#6b7280; color:#fff; } /* gris */
    .estado-liquidado{ background:#16a34a; color:#fff; } /* verde */
    .estado-anulado{ background:#dc2626; color:#fff; }  /* rojo */

    /* ===== PAGINACIÓN (arregla flechas) ===== */
    .pagination { margin-bottom: 0; gap: 6px; }
    .page-link{
      display: flex;
      align-items: center;
      justify-content: center;
      min-width: 38px;
      height: 38px;
      padding: 0 12px;
      font-size: 14px;
      border-radius: 8px;
      line-height: 1;
    }
    .page-item.disabled .page-link{ opacity: .55; }
    .page-item.active .page-link{
      background-color: #0d6efd;
      border-color: #0d6efd;
      color: #fff;
    }
  </style>

  @php
    use Illuminate\Support\Facades\DB;

    $u = session('user');
    if (is_object($u)) $u = (array) $u;

    $myId    = (int)($u['id_usuario'] ?? 0);
    $rolId   = (int)($u['id_rol'] ?? 0);
    $rolName = strtoupper(trim((string)($u['rol'] ?? $u['nombre_rol'] ?? '')));

    $isAdmin = ($rolId === 1) || ($rolName === 'ADMIN');

    // TM si tiene al menos 1 usuario cuyo id_tm = mi id_usuario
    $isTM = $myId
      ? DB::table('usuarios')->where('id_tm', $myId)->exists()
      : false;

    $canAnularGlobal = $isAdmin;
  @endphp

  <div class="d-flex justify-content-between align-items-center mb-3">
    <h3 class="mb-0">Talonarios</h3>
  </div>

  {{-- Alerts --}}
  @if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
  @endif
  @if(session('error'))
    <div class="alert alert-danger">{{ session('error') }}</div>
  @endif
  @if(session('info'))
    <div class="alert alert-info">{{ session('info') }}</div>
  @endif

  {{-- Resumen --}}
  <div class="row g-3 mb-3">
    <div class="col-md-3">
      <div class="card p-3">
        <div class="text-muted">Asignados</div>
        <div class="fs-4">{{ $resumen->total_asignados ?? 0 }}</div>
      </div>
    </div>
    <div class="col-md-3">
      <div class="card p-3">
        <div class="text-muted">Liquidados</div>
        <div class="fs-4">{{ $resumen->total_liquidados ?? 0 }}</div>
      </div>
    </div>
    <div class="col-md-3">
      <div class="card p-3">
        <div class="text-muted">Anulados</div>
        <div class="fs-4">{{ $resumen->total_anulados ?? 0 }}</div>
      </div>
    </div>
    <div class="col-md-3">
      <div class="card p-3">
        <div class="text-muted">Monto pendiente (Asignados)</div>
        <div class="fs-4">Q {{ number_format($resumen->monto_pendiente ?? 0, 2) }}</div>
      </div>
    </div>
  </div>

  {{-- Filtros --}}
  <form class="card p-3 mb-3" method="GET" action="{{ route('ventas.talonarios.index') }}">
    <div class="row g-3 align-items-end">

      <div class="col-md-3">
        <label class="form-label">Estación</label>
        <select name="id_estacion" class="form-select">
          <option value="">Todas</option>
          @foreach($estaciones as $e)
            <option value="{{ $e->id_estacion }}" @selected((int)$idEstacion === (int)$e->id_estacion)>
              {{ $e->estacion }}
            </option>
          @endforeach
        </select>
      </div>

      <div class="col-md-3">
        <label class="form-label">Operador</label>
        <select name="id_operador" class="form-select">
          <option value="">Todos</option>
          @foreach($operadores as $o)
            <option value="{{ $o->id_usuario }}" @selected((int)$idOperador === (int)$o->id_usuario)>
              {{ $o->operador }}
            </option>
          @endforeach
        </select>
      </div>

      <div class="col-md-2">
        <label class="form-label">Estado</label>
        <select name="estado" class="form-select">
          <option value="">Todos</option>
          @foreach(['ASIGNADO','LIQUIDADO','ANULADO'] as $st)
            <option value="{{ $st }}" @selected($estado === $st)>{{ $st }}</option>
          @endforeach
        </select>
      </div>

      <div class="col-md-2">
        <label class="form-label">Buscar talonario</label>
        <input name="buscar" value="{{ $buscar }}" class="form-control" placeholder="Ej: 2273">
      </div>

      <div class="col-md-2 d-flex gap-2">
        <button class="btn btn-primary w-100">Filtrar</button>
        <a class="btn btn-outline-secondary w-100" href="{{ route('ventas.talonarios.index') }}">Limpiar</a>
      </div>

    </div>
  </form>

  {{-- Tabla --}}
  <div class="card">
    <div class="table-responsive">
      <table class="table table-hover mb-0 align-middle">
        <thead class="table-light">
          <tr>
            <th>Talonario</th>
            <th>Rango</th>
            <th>Estación</th>
            <th>Operador</th>
            <th>Estado</th>
            <th class="text-end">Valor</th>
            <th>Asignado</th>
            <th class="text-end">Acciones</th>
          </tr>
        </thead>
        <tbody>
          @forelse($talonarios as $t)
            @php
              $estadoClass = match($t->estado) {
                'ASIGNADO'  => 'estado-asignado',
                'LIQUIDADO' => 'estado-liquidado',
                'ANULADO'   => 'estado-anulado',
                default     => 'estado-asignado',
              };

              $canAnular = $canAnularGlobal && strtoupper((string)$t->estado) === 'ASIGNADO';
            @endphp

            <tr>
              <td>{{ $t->numero_talonario }}</td>
              <td>{{ $t->numero_inicio }} - {{ $t->numero_fin }}</td>
              <td>
                <a href="{{ route('ventas.talonarios.estacion', $t->id_estacion) }}">
                  {{ $t->estacion }}
                </a>
              </td>
              <td>{{ $t->operador }}</td>
              <td><span class="badge-estado {{ $estadoClass }}">{{ $t->estado }}</span></td>
              <td class="text-end">Q {{ number_format($t->valor_talonario, 2) }}</td>
              <td>{{ $t->asignado_en }}</td>

              <td class="text-end">
                @if($canAnular)
                  <a class="btn btn-sm btn-outline-danger"
                     href="{{ route('ventas.talonarios.anular.form', $t->id_talonario) }}">
                    Anular
                  </a>
                @else
                  <span class="text-muted small">—</span>
                @endif
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="8" class="text-center py-4 text-muted">Sin resultados.</td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>

    {{-- Paginación --}}
    <div class="card-footer py-2">
      <div class="d-flex justify-content-center">
        {{ $talonarios->withQueryString()->onEachSide(1)->links('pagination::bootstrap-5') }}
      </div>
    </div>
  </div>

</div>
@endsection

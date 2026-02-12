@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
<div class="container py-4">

  <div class="mb-3">
    <h3 class="fw-bold mb-1">Bienvenido, {{ $nombre }}</h3>
    <div class="text-muted">Aquí puedes ver las estaciones a cargo de tus operadores y gestionar los talonarios asignados.</div>
  </div>

  @php
    // Para los numeritos rojos tipo "2/32"
    $tVend = (int)($kpi['talonarios_vendidos'] ?? 0);
    $tTot  = (int)($kpi['talonarios_total'] ?? 0);

    $nVend = (int)($kpi['numeros_vendidos'] ?? 0);
    $nTot  = (int)($kpi['numeros_total'] ?? 0);

    $mVend = (float)($kpi['monto_vendido'] ?? 0);
    $mTot  = (float)($kpi['monto_total'] ?? 0);
  @endphp

  {{-- KPIs (estilo screenshot) --}}
  <div class="row g-3 mb-4">
    <div class="col-12 col-md-4">
      <div class="card shadow-sm border-0" style="border-radius:14px;">
        <div class="card-body d-flex align-items-center gap-3">
          <div class="rounded-3 d-flex align-items-center justify-content-center"
               style="width:44px;height:44px;background:#eef2ff;">
            <i class="bi bi-journal-text fs-4" style="color:#2563eb;"></i>
          </div>
          <div class="w-100">
            <div class="text-muted small">Talonarios</div>
            <div class="d-flex align-items-end justify-content-between">
              <div class="fs-4 fw-bold">{{ number_format($tTot) }}</div>
              <div class="small fw-semibold" style="color:#0c7a2d;">
                {{ number_format($tVend) }}/{{ number_format($tTot) }}
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="col-12 col-md-4">
      <div class="card shadow-sm border-0" style="border-radius:14px;">
        <div class="card-body d-flex align-items-center gap-3">
          <div class="rounded-3 d-flex align-items-center justify-content-center"
               style="width:44px;height:44px;background:#ecfdf5;">
            <i class="bi bi-check2-circle fs-4" style="color:#16a34a;"></i>
          </div>
          <div class="w-100">
            <div class="text-muted small">Números</div>
            <div class="d-flex align-items-end justify-content-between">
              <div class="fs-4 fw-bold">{{ number_format($nTot) }}</div>
              <div class="small fw-semibold" style="color:#0c7a2d;;">
                {{ number_format($nVend) }}/{{ number_format($nTot) }}
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="col-12 col-md-4">
      <div class="card shadow-sm border-0" style="border-radius:14px;">
        <div class="card-body d-flex align-items-center gap-3">
          <div class="rounded-3 d-flex align-items-center justify-content-center"
               style="width:44px;height:44px;background:#fffbeb;">
            <i class="bi bi-cash-coin fs-4" style="color:#f59e0b;"></i>
          </div>
          <div class="w-100">
            <div class="text-muted small">Monto Asignado</div>
            <div class="d-flex align-items-end justify-content-between">
              <div class="fs-4 fw-bold">Q {{ number_format($mTot, 2) }}</div>
              <div class="small fw-semibold" style="color:#0c7a2d;">
                {{ number_format($mVend, 0) }}/{{ number_format($mTot, 0) }}
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  {{-- Tabla --}}
  <div class="card shadow-sm border-0" style="border-radius:14px;">
    <div class="card-body">

      <div class="d-flex flex-wrap gap-2 align-items-center justify-content-between mb-3">
        <h5 class="fw-bold mb-0">Estaciones a Cargo</h5>

        <form method="GET" action="{{ route('dashboard') }}" class="d-flex gap-2">
          <div class="input-group">
            <span class="input-group-text bg-white"><i class="bi bi-search"></i></span>
            <input type="text" name="q" value="{{ $q }}" class="form-control" placeholder="Buscar estación...">
          </div>
          <button class="btn btn-outline-secondary">Buscar</button>
        </form>
      </div>

      <div class="table-responsive">
        <table class="table align-middle mb-0">
          <thead class="table-light">
            <tr>
              <th style="width:280px;">Operador</th>
              <th>Estaciones</th>
              <th class="text-center">Talonarios</th>
              <th class="text-center">Liquidados</th>
              <th class="text-center">Pendientes</th>
              <th class="text-center">Anulados</th>
              <th class="text-center">Números</th>
              <th class="text-end">Vendidos (Q)</th>
              <th class="text-end">Monto Asignado</th>
            </tr>
          </thead>

          <tbody>
          @forelse($porOperador as $op)

            {{-- fila operador (gris) --}}
            <tr class="table-secondary">
              <td class="fw-semibold">
                <i class="bi bi-person-circle me-2"></i>{{ $op['operador_nombre'] }}
              </td>
              <td class="text-muted">—</td>
              <td class="text-center fw-semibold">{{ number_format($op['tot_talonarios']) }}</td>

              <td class="text-center">
                <span class="badge bg-success">{{ number_format($op['tot_liquidados']) }}</span>
              </td>

              <td class="text-center">
                <span class="badge bg-warning text-dark">{{ number_format($op['tot_pendientes']) }}</span>
              </td>

              <td class="text-center">
                <span class="badge bg-danger">{{ number_format($op['tot_anulados']) }}</span>
              </td>

              <td class="text-center fw-semibold">{{ number_format($op['tot_numeros']) }}</td>
              <td class="text-end fw-semibold">Q {{ number_format($op['tot_monto_vendido'], 2) }}</td>
              <td class="text-end fw-semibold">Q {{ number_format($op['tot_monto'], 2) }}</td>
            </tr>

            {{-- filas estaciones (amarillo suave si hay pendientes) --}}
            @foreach($op['estaciones'] as $st)
              @php
                $pend = (int)$st->talonarios_pendientes;
                $rowStyle = $pend > 0 ? 'background:#fff7d6;' : '';
              @endphp

              <tr style="{{ $rowStyle }}">
                <td class="text-muted">
                  <span class="ms-4">↳</span>
                </td>

                <td class="fw-bold text-uppercase">{{ $st->estacion_nombre }}</td>

                <td class="text-center">{{ number_format((int)$st->talonarios_asignados) }}</td>

                <td class="text-center">
                  <span class="badge bg-success">{{ number_format((int)$st->talonarios_liquidados) }}</span>
                </td>

                <td class="text-center">
                  <span class="badge bg-warning text-dark">{{ number_format((int)$st->talonarios_pendientes) }}</span>
                </td>

                <td class="text-center">
                  <span class="badge bg-danger">{{ number_format((int)$st->talonarios_anulados) }}</span>
                </td>

                <td class="text-center">{{ number_format((int)$st->numeros) }}</td>

                <td class="text-end">Q {{ number_format((float)$st->monto_vendido, 2) }}</td>

                <td class="text-end">Q {{ number_format((float)$st->monto_asignado, 2) }}</td>
              </tr>
            @endforeach

          @empty
            <tr>
              <td colspan="9" class="text-center text-muted py-4">
                No hay datos para mostrar (o el filtro no encontró estaciones).
              </td>
            </tr>
          @endforelse
          </tbody>
        </table>
      </div>

    </div>
  </div>

</div>
@endsection

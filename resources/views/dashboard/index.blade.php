@extends('layouts.app')

@section('content')
@php
  $fmtQ = fn($n) => 'Q ' . number_format((float)$n, 2);
  $fmtN = fn($n) => number_format((int)$n);

  $talTotal = (int)($kpi['talonarios_total'] ?? 0);
  $talVend  = (int)($kpi['talonarios_vendidos'] ?? 0);

  $numTotal = (int)($kpi['numeros_total'] ?? 0);
  $numVend  = (int)($kpi['numeros_vendidos'] ?? 0);

  $monTotal = (float)($kpi['monto_total'] ?? 0);
  $monVend  = (float)($kpi['monto_vendido'] ?? 0);
@endphp

<style>
  .kpi-card{ border-radius:16px; border:1px solid #eef2f7; box-shadow:0 8px 22px rgba(16,24,40,.06); }
  .kpi-icon{ width:44px; height:44px; border-radius:12px; display:flex; align-items:center; justify-content:center; background:#f2f6ff; }
  .kpi-sub{ font-size:12px; color:#667085; }

  .pill{ font-size:12px; padding:4px 10px; border-radius:999px; display:inline-flex; align-items:center; gap:6px; font-weight:700; }
  .pill-green{ background:#e9f9ee; color:#0f7b3b; }
  .pill-yellow{ background:#fff6dd; color:#8a5b00; }
  .pill-red{ background:#ffe4e4; color:#b42318; }

  .chart-card{ border-radius:16px; border:1px solid #eef2f7; box-shadow:0 8px 22px rgba(16,24,40,.06); }
  .chart-wrap{ height: 320px; }
  canvas{ display:block; width:100% !important; height:100% !important; }

  /* ✅ Accordion UI */
  .op-card{
    border:1px solid #eef2f7;
    border-radius:16px;
    box-shadow:0 8px 22px rgba(16,24,40,.06);
    overflow:hidden;
    background:#fff;
  }
  .op-head{
    padding: 14px 16px;
    background:#f2f4f7;
    display:flex;
    align-items:center;
    justify-content:space-between;
    gap: 12px;
    cursor:pointer;
    user-select:none;
  }
  .op-name{
    font-weight: 900;
    display:flex;
    align-items:center;
    gap:10px;
    min-width: 220px;
  }
  .op-metrics{
    display:flex;
    flex-wrap:wrap;
    gap:10px;
    align-items:center;
    justify-content:flex-end;
  }
  .metric{
    background:#fff;
    border:1px solid #e7edf6;
    border-radius:12px;
    padding:8px 10px;
    min-width: 120px;
    text-align:center;
  }
  .metric .t{ font-size:11px; color:#667085; }
  .metric .v{ font-size:14px; font-weight:900; }

  .op-body{
    padding: 12px 16px 16px 16px;
    background:#fff;
  }

  /* table inside accordion */
  .table-st{
    width:100%;
    border-collapse:separate;
    border-spacing:0;
    overflow:hidden;
    border:1px solid #eef2f7;
    border-radius: 14px;
  }
  .table-st thead th{
    background:#fbfcfe;
    font-size:12px;
    color:#344054;
    white-space:nowrap;
    padding: 10px 10px;
    border-bottom:1px solid #eef2f7;
  }
  .table-st td{
    padding: 10px 10px;
    border-bottom:1px solid #eef2f7;
    vertical-align:middle;
    white-space:nowrap;
  }
  .table-st tbody tr:last-child td{ border-bottom:none; }
  .td-est{
    white-space:normal;
    line-height:1.15;
    font-weight:800;
  }
  .td-center{ text-align:center; }
  .td-right{ text-align:right; }

  .search-wrap{ gap:10px; }

  /* small arrow */
  .chev{
    width: 34px; height: 34px;
    border-radius: 10px;
    border: 1px solid #dbe7ff;
    display:flex;
    align-items:center;
    justify-content:center;
    background:#ffffff;
  }
</style>

<div class="mb-3">
  <h3 class="fw-bold mb-1">Bienvenido, {{ $nombre }}</h3>
  <div class="text-muted">Aquí puedes ver las estaciones a cargo y el avance de ventas por estación.</div>
</div>

{{-- KPIs --}}
<div class="row g-3 mb-4">
  <div class="col-12 col-lg-4">
    <div class="card kpi-card">
      <div class="card-body d-flex align-items-center gap-3">
        <div class="kpi-icon"><i class="bi bi-journal-text fs-4"></i></div>
        <div class="flex-grow-1">
          <div class="kpi-sub">Talonarios</div>
          <div class="fs-4 fw-bold">{{ $fmtN($talTotal) }}</div>
        </div>
        <div class="text-success fw-bold">{{ $fmtN($talVend) }}/{{ $fmtN($talTotal) }}</div>
      </div>
    </div>
  </div>

  <div class="col-12 col-lg-4">
    <div class="card kpi-card">
      <div class="card-body d-flex align-items-center gap-3">
        <div class="kpi-icon" style="background:#ecfdf3;"><i class="bi bi-check2-circle fs-4"></i></div>
        <div class="flex-grow-1">
          <div class="kpi-sub">Números</div>
          <div class="fs-4 fw-bold">{{ $fmtN($numTotal) }}</div>
        </div>
        <div class="text-success fw-bold">{{ $fmtN($numVend) }}/{{ $fmtN($numTotal) }}</div>
      </div>
    </div>
  </div>

  <div class="col-12 col-lg-4">
    <div class="card kpi-card">
      <div class="card-body d-flex align-items-center gap-3">
        <div class="kpi-icon" style="background:#fff7ed;"><i class="bi bi-cash-coin fs-4"></i></div>
        <div class="flex-grow-1">
          <div class="kpi-sub">Monto Asignado</div>
          <div class="fs-4 fw-bold">{{ $fmtQ($monTotal) }}</div>
        </div>
        <div class="text-success fw-bold">{{ $fmtQ($monVend) }}/{{ $fmtQ($monTotal) }}</div>
      </div>
    </div>
  </div>
</div>

{{-- GRÁFICAS: a la par --}}
<div class="row g-3 mb-4">
  <div class="col-12 col-lg-6">
    <div class="card chart-card h-100">
      <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-2">
          <h5 class="fw-bold mb-0">Números vendidos por estación</h5>
          <span class="text-muted small">Pastel</span>
        </div>
        <div class="chart-wrap">
          <canvas id="pieNumeros"></canvas>
        </div>
      </div>
    </div>
  </div>

  <div class="col-12 col-lg-6">
    <div class="card chart-card h-100">
      <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-2">
          <h5 class="fw-bold mb-0">Monto vendido (Q) por estación</h5>
          <span class="text-muted small">Pastel</span>
        </div>
        <div class="chart-wrap">
          <canvas id="pieMonto"></canvas>
        </div>
      </div>
    </div>
  </div>
</div>

{{-- ACCORDION por operador --}}
<div class="card shadow-sm" style="border-radius:16px;">
  <div class="card-body">
    <div class="d-flex flex-wrap align-items-center justify-content-between search-wrap mb-3">
      <h4 class="fw-bold mb-0">Estaciones a Cargo</h4>

      <form method="GET" action="{{ route('dashboard') }}" class="d-flex gap-2">
        <div class="input-group">
          <span class="input-group-text bg-white"><i class="bi bi-search"></i></span>
          <input type="text" name="q" value="{{ $q }}" class="form-control" placeholder="Buscar estación u operador...">
        </div>
        <button class="btn btn-outline-primary">Buscar</button>
      </form>
    </div>

    <div class="accordion" id="opsAccordion">
      @forelse($porOperador as $i => $op)
        @php
          $opKey = 'op_'.$i;
          $open  = $i === 0 ? 'show' : '';
          $btnCollapsed = $i === 0 ? '' : 'collapsed';
          $ariaExpanded = $i === 0 ? 'true' : 'false';
        @endphp

        <div class="op-card mb-3">
          <div class="op-head {{ $btnCollapsed }}"
               data-bs-toggle="collapse"
               data-bs-target="#{{ $opKey }}"
               aria-expanded="{{ $ariaExpanded }}"
               aria-controls="{{ $opKey }}">
            <div class="op-name">
              <span class="chev"><i class="bi bi-chevron-down"></i></span>
              <span><i class="bi bi-person-circle me-2"></i>{{ $op['operador_nombre'] }}</span>
            </div>

            <div class="op-metrics">
              <div class="metric">
                <div class="t">Talonarios</div>
                <div class="v">{{ $fmtN($op['tot_talonarios']) }}</div>
              </div>
              <div class="metric">
                <div class="t">Liquidados</div>
                <div class="v"><span class="pill pill-green">{{ $fmtN($op['tot_liquidados']) }}</span></div>
              </div>
              <div class="metric">
                <div class="t">Pendientes</div>
                <div class="v"><span class="pill pill-yellow">{{ $fmtN($op['tot_pendientes']) }}</span></div>
              </div>
              <div class="metric">
                <div class="t">Anulados</div>
                <div class="v"><span class="pill pill-red">{{ $fmtN($op['tot_anulados']) }}</span></div>
              </div>
              <div class="metric">
                <div class="t">Números</div>
                <div class="v">{{ $fmtN($op['tot_numeros']) }}</div>
              </div>
              <div class="metric">
                <div class="t">Vendidos (Q)</div>
                <div class="v">{{ $fmtQ($op['tot_monto_vendido']) }}</div>
              </div>
              <div class="metric">
                <div class="t">Monto Asignado</div>
                <div class="v">{{ $fmtQ($op['tot_monto']) }}</div>
              </div>
            </div>
          </div>

          <div id="{{ $opKey }}" class="collapse {{ $open }}" data-bs-parent="#opsAccordion">
            <div class="op-body">
              <div class="table-responsive">
                <table class="table-st">
                  <thead>
                    <tr>
                      <th style="width: 320px;">Estación</th>
                      <th class="td-center" style="width: 90px;">Talonarios</th>
                      <th class="td-center" style="width: 90px;">Liquidados</th>
                      <th class="td-center" style="width: 90px;">Pendientes</th>
                      <th class="td-center" style="width: 90px;">Anulados</th>
                      <th class="td-center" style="width: 90px;">Números</th>
                      <th class="td-right" style="width: 140px;">Vendidos (Q)</th>
                      <th class="td-right" style="width: 140px;">Monto Asignado</th>
                    </tr>
                  </thead>
                  <tbody>
                    @foreach($op['estaciones'] as $st)
                      <tr>
                        <td class="td-est" title="{{ $st->estacion_nombre }}">{{ $st->estacion_nombre }}</td>
                        <td class="td-center">{{ $fmtN($st->talonarios_asignados) }}</td>
                        <td class="td-center"><span class="pill pill-green">{{ $fmtN($st->talonarios_liquidados) }}</span></td>
                        <td class="td-center"><span class="pill pill-yellow">{{ $fmtN($st->talonarios_pendientes) }}</span></td>
                        <td class="td-center"><span class="pill pill-red">{{ $fmtN($st->talonarios_anulados) }}</span></td>
                        <td class="td-center">{{ $fmtN($st->numeros) }}</td>
                        <td class="td-right">{{ $fmtQ($st->monto_vendido) }}</td>
                        <td class="td-right">{{ $fmtQ($st->monto_asignado) }}</td>
                      </tr>
                    @endforeach
                  </tbody>
                </table>
              </div>
              <div class="text-muted small mt-2">
                Tip: haz clic en el encabezado del operador para contraer/expandir.
              </div>
            </div>
          </div>
        </div>

      @empty
        <div class="text-center text-muted py-4">No hay datos para mostrar 😅</div>
      @endforelse
    </div>
  </div>
</div>

{{-- Chart.js + plugin % + fix resize --}}
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@2"></script>

<script>
  const labels = @json($chartLabels ?? []);
  const dataNumeros = @json($chartNumeros ?? []);
  const dataMonto = @json($chartMonto ?? []);

  function pct(value, total){
    if(!total || total <= 0) return '0%';
    return ((value * 100) / total).toFixed(0) + '%';
  }

  Chart.register(ChartDataLabels);

  const charts = [];

  const totalN = dataNumeros.reduce((a,b)=>a+(+b||0), 0);
  charts.push(new Chart(document.getElementById('pieNumeros'), {
    type: 'pie',
    data: { labels, datasets: [{ label: 'Números vendidos', data: dataNumeros }] },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: {
        legend: { position: 'right' },
        datalabels: { color:'#fff', font:{ weight:'700' }, formatter:(v)=>pct(+v||0, totalN) }
      }
    }
  }));

  const totalM = dataMonto.reduce((a,b)=>a+(+b||0), 0);
  charts.push(new Chart(document.getElementById('pieMonto'), {
    type: 'pie',
    data: { labels, datasets: [{ label: 'Monto vendido (Q)', data: dataMonto }] },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: {
        legend: { position: 'right' },
        datalabels: { color:'#fff', font:{ weight:'700' }, formatter:(v)=>pct(+v||0, totalM) }
      }
    }
  }));

  function forceResize(){
    charts.forEach(c => { try { c.resize(); c.update(); } catch(e){} });
  }

  window.addEventListener('load', () => {
    requestAnimationFrame(forceResize);
    setTimeout(forceResize, 120);
    setTimeout(forceResize, 350);
  });

  window.addEventListener('resize', () => {
    requestAnimationFrame(forceResize);
  });

  // ✅ iconito del accordion gira (bonito)
  document.addEventListener('click', (e) => {
    const head = e.target.closest('.op-head');
    if(!head) return;
    setTimeout(() => {
      document.querySelectorAll('.op-head .chev i').forEach(i => i.className = 'bi bi-chevron-down');
      document.querySelectorAll('.op-head[aria-expanded="true"] .chev i').forEach(i => i.className = 'bi bi-chevron-up');
    }, 50);
  });
</script>
@endsection

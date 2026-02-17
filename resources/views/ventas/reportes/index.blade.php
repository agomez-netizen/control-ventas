@extends('layouts.app')

@section('title','Reportes')

@section('content')
<div class="container py-4">

  <style>
    .card-soft{border:1px solid #e5e7eb;border-radius:16px;background:#fff}
    .muted{color:#6b7280}
    .pill{display:inline-flex;align-items:center;gap:.4rem;padding:.25rem .6rem;border-radius:999px;font-weight:700;font-size:12px}
    .pill-ok{background:#dcfce7;color:#166534}
    .pill-bad{background:#fee2e2;color:#991b1b}
    .pill-warn{background:#ffedd5;color:#9a3412}
    .num-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(70px,1fr));gap:8px}
    .num{border:1px solid #e5e7eb;border-radius:12px;padding:8px 10px;font-weight:800;text-align:center}
    .num small{display:block;font-weight:700;margin-top:2px}
    .num.liquidado{background:#ecfdf5;border-color:#86efac;color:#065f46}
    .num.anulado{background:#fef2f2;border-color:#fca5a5;color:#7f1d1d}
    .num.pendiente{background:#f9fafb;border-color:#e5e7eb;color:#111827}
    .sticky-head{position:sticky;top:0;background:white;z-index:2}
  </style>

<div class="d-flex justify-content-between align-items-center mb-3">

  <div>
    <h3 class="mb-0">Reportes</h3>
    <div class="muted">
      Talonarios y números por estado, filtrado por operador o estación.
    </div>
  </div>

  <a class="btn btn-outline-success ms-auto"
     href="{{ route('ventas.reportes.export', request()->query()) }}"
     title="Exportar lo que estás viendo">
    <i class="bi bi-download"></i> Exportar
  </a>

</div>


  {{-- Filtros --}}
  <div class="card-soft p-3 mb-3">
    <form method="GET" class="row g-2 align-items-end">
      <div class="col-12 col-md-4">
        <label class="form-label mb-1">Estación</label>
        <select name="estacion_id" class="form-select">
          <option value="">Todas</option>
          @foreach($estaciones as $e)
            <option value="{{ $e->id_estacion }}" {{ (string)$estacionId===(string)$e->id_estacion ? 'selected' : '' }}>
              {{ $e->nombre }}
            </option>
          @endforeach
        </select>
      </div>

      <div class="col-12 col-md-4">
        <label class="form-label mb-1">Operador</label>
        <select name="operador_id" class="form-select">
          <option value="">Todos</option>
          @foreach($operadores as $o)
            <option value="{{ $o->id_usuario }}" {{ (string)$operadorId===(string)$o->id_usuario ? 'selected' : '' }}>
              {{ $o->nombre }}
            </option>
          @endforeach
        </select>
      </div>

      <div class="col-12 col-md-3">
        <label class="form-label mb-1">Estado</label>
        <select name="estado" class="form-select">
          <option value="todos" {{ $estado==='todos'?'selected':'' }}>Todos</option>
          <option value="pendiente" {{ $estado==='pendiente'?'selected':'' }}>Pendientes</option>
          <option value="liquidado" {{ $estado==='liquidado'?'selected':'' }}>Liquidados</option>
          <option value="anulado" {{ $estado==='anulado'?'selected':'' }}>Anulados</option>
        </select>
      </div>
<div class="col-12 col-md-1 d-grid">
  <button class="btn btn-primary" title="Filtrar">
    <i class="bi bi-funnel"></i>
  </button>
</div>






  {{-- Lista --}}
  <div class="card-soft p-0 overflow-hidden">
    <div class="p-3 border-bottom sticky-head">
      <div class="d-flex justify-content-between align-items-center">
        <div class="fw-bold">Talonarios</div>
        <div class="muted">{{ $talonarios->count() }} encontrados</div>
      </div>
    </div>

    @if($talonarios->isEmpty())
      <div class="p-4 text-center muted">
        No hay talonarios con esos filtros.
      </div>
    @else
      <div class="accordion" id="accReportes">
        @foreach($talonarios as $t)
          @php
            $st = strtolower($t->estado_talonario ?? 'pendiente');
            $stNorm = in_array($st,['liquidado','anulado']) ? $st : 'pendiente';
            $sum = $resumen[$t->id_talonario] ?? ['liquidado'=>0,'anulado'=>0,'pendiente'=>0,'total'=>0];
          @endphp

          <div class="accordion-item">
            <h2 class="accordion-header" id="h{{ $t->id_talonario }}">
              <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse"
                      data-bs-target="#c{{ $t->id_talonario }}" aria-expanded="false" aria-controls="c{{ $t->id_talonario }}">
                <div class="w-100 d-flex flex-column flex-md-row gap-2 justify-content-between align-items-start align-items-md-center">
                  <div>
                    <div class="fw-bold">
                      Talonario #{{ $t->numero_talonario ?? $t->id_talonario }}
                    </div>
                    <div class="muted">
                      Estación: <span class="fw-semibold">{{ $t->estacion_nombre }}</span> ·
                      Operador: <span class="fw-semibold">{{ $t->operador_nombre }}</span> ·
                      Rango: <span class="fw-semibold">{{ $t->numero_inicio ?? '—' }} - {{ $t->numero_fin ?? '—' }}</span>
                    </div>
                  </div>

                  <div class="d-flex flex-wrap gap-2">
                    <span class="pill {{ $stNorm==='liquidado'?'pill-ok':($stNorm==='anulado'?'pill-bad':'pill-warn') }}">
                      <i class="bi {{ $stNorm==='liquidado'?'bi-check-circle':($stNorm==='anulado'?'bi-x-circle':'bi-hourglass-split') }}"></i>
                      {{ ucfirst($stNorm) }}
                    </span>

                    @if(($sum['total'] ?? 0) > 0)
                      <span class="pill pill-ok">Liq: {{ $sum['liquidado'] }}</span>
                      <span class="pill pill-bad">Anu: {{ $sum['anulado'] }}</span>
                      <span class="pill pill-warn">Pend: {{ $sum['pendiente'] }}</span>
                      <span class="pill" style="background:#eef2ff;color:#3730a3">Total: {{ $sum['total'] }}</span>
                    @else
                      <span class="pill" style="background:#f3f4f6;color:#374151">Sin detalle de números</span>
                    @endif
                  </div>
                </div>
              </button>
            </h2>

            <div id="c{{ $t->id_talonario }}" class="accordion-collapse collapse" aria-labelledby="h{{ $t->id_talonario }}"
                 data-bs-parent="#accReportes">
              <div class="accordion-body">
                @php $nums = $numerosPorTalonario[$t->id_talonario] ?? []; @endphp

                @if(empty($nums))
                  <div class="muted">
                    No hay números cargados para este talonario (o el rango está raro).
                  </div>
                @else
                  <div class="num-grid">
                    @foreach($nums as $n)
                      <div class="num {{ $n['estado'] }}">
                        {{ $n['numero'] }}
                        <small>
                          @if($n['estado']==='liquidado')
                            <i class="bi bi-check2-circle"></i> Liq
                          @elseif($n['estado']==='anulado')
                            <i class="bi bi-x-circle"></i> Anu
                          @else
                            <i class="bi bi-hourglass-split"></i> Pend
                          @endif
                        </small>
                      </div>
                    @endforeach
                  </div>
                @endif
              </div>
            </div>
          </div>
        @endforeach
      </div>
    @endif
  </div>

</div>
@endsection

{{-- resources/views/ventas/liquidaciones/show.blade.php --}}
@extends('layouts.app')

@section('content')
<div class="container py-4">

  <style>
    .card-soft { border:1px solid #e5e7eb; border-radius:14px; background:#fff; }
    .muted { color:#6b7280; }
    .badge-count { font-size: 13px; padding:6px 10px; }
  </style>

  <div class="d-flex justify-content-between align-items-center mb-3">
    <div>
      <h3 class="mb-0">Liquidación #{{ $liquidacion->id_liquidacion }}</h3>
      <div class="muted">
        {{ $liquidacion->created_at }} · {{ $liquidacion->estacion_nombre }} · {{ $liquidacion->operador_nombre ?: '—' }}
      </div>
    </div>
    <a href="{{ route('ventas.liquidaciones.index') }}" class="btn btn-outline-secondary">Volver</a>
  </div>

  @if(session('ok'))
    <div class="alert alert-success">{{ session('ok') }}</div>
  @endif
  @if(session('err'))
    <div class="alert alert-danger">{{ session('err') }}</div>
  @endif

  {{-- RESUMEN --}}
  <div class="card-soft p-3 mb-3">
    <div class="row g-3">
      <div class="col-md-3">
        <div class="muted">Estado</div>
        <div class="fw-bold">{{ $liquidacion->estado }}</div>
      </div>
      <div class="col-md-3">
        <div class="muted">Calculado</div>
        <div class="fw-bold">Q {{ number_format($liquidacion->monto_calculado, 2) }}</div>
      </div>
      <div class="col-md-3">
        <div class="muted">Boletas</div>
        <div class="fw-bold">Q {{ number_format($liquidacion->monto_boletas, 2) }}</div>
      </div>
      <div class="col-md-3">
        <div class="muted">Excedente</div>
        <div class="fw-bold">Q {{ number_format($liquidacion->excedente, 2) }}</div>
      </div>
      <div class="col-12">
        <div class="muted">Observación</div>
        <div>{{ $liquidacion->observacion ?: '—' }}</div>
      </div>
    </div>
  </div>

  <div class="row g-3">

    {{-- TALONARIOS COMPLETOS --}}
    <div class="col-lg-6">
      <div class="card-soft p-3">
        <h5 class="mb-2">Talonarios completos</h5>

        @if($talonarios->isEmpty())
          <div class="alert alert-light border mb-0">
            No hay talonarios completos en esta liquidación.
          </div>
        @else

          @php
            $totalCompletos = 0;
          @endphp

          <div class="table-responsive">
            <table class="table table-sm align-middle mb-0">
              <thead>
                <tr>
                  <th>Talonario</th>
                  <th>Rango</th>
                  <th class="text-end">Valor</th>
                  <th class="text-end">Cantidad</th>
                </tr>
              </thead>
              <tbody>
                @foreach($talonarios as $t)
                  @php
                    $inicio = (int)$t->numero_inicio;
                    $fin    = (int)$t->numero_fin;
                    $cant   = ($fin >= $inicio) ? (($fin - $inicio) + 1) : 0;
                    $totalCompletos += $cant;
                  @endphp
                  <tr>
                    <td class="fw-bold">{{ $t->numero_talonario }}</td>
                    <td class="muted">{{ $t->numero_inicio }} - {{ $t->numero_fin }}</td>
                    <td class="text-end">Q {{ number_format($t->valor_talonario, 2) }}</td>
                    <td class="text-end">
                      <span class="badge bg-light text-dark border badge-count">
                        {{ $cant }}
                      </span>
                    </td>
                  </tr>
                @endforeach
              </tbody>
            </table>
          </div>

          <div class="pt-2 border-top mt-3 text-end">
            <span class="badge bg-primary badge-count">
              Total números en completos: {{ $totalCompletos }}
            </span>
          </div>

        @endif
      </div>
    </div>

    {{-- NUMEROS PARCIALES --}}
    <div class="col-lg-6">
      <div class="card-soft p-3">
        <h5 class="mb-3">Números liquidados (parciales)</h5>

        @if($numeros->isEmpty())
          <div class="alert alert-light border mb-0">
            No hay números parciales en esta liquidación.
          </div>
        @else

          @php
            $totalNums = 0;
          @endphp

          @foreach($numeros as $tal => $items)

            @php
              $cantidad = $items->count();
              $totalNums += $cantidad;
            @endphp

            <div class="mb-3 p-2 border rounded">
              <div class="fw-bold mb-1">
                Talonario {{ $tal }}
              </div>

              <div class="muted small">
                {{ $items->pluck('numero')->implode(', ') }}
              </div>

              <div class="mt-2 text-end">
                <span class="badge bg-light text-dark border badge-count">
                  Cantidad: <strong>{{ $cantidad }}</strong>
                </span>
              </div>
            </div>

          @endforeach

          <div class="pt-2 border-top mt-3 text-end">
            <span class="badge bg-primary badge-count">
              Total números parciales: {{ $totalNums }}
            </span>
          </div>

        @endif
      </div>
    </div>

    {{-- BOLETAS --}}
    <div class="col-12">
      <div class="card-soft p-3">
        <h5 class="mb-2">Boletas</h5>

        @if($boletas->isEmpty())
          <div class="alert alert-light border mb-0">
            No hay boletas registradas.
          </div>
        @else
          <div class="table-responsive">
            <table class="table table-sm align-middle mb-0">
              <thead>
                <tr>
                  <th>Banco</th>
                  <th>Tipo</th>
                  <th>Fecha</th>
                  <th>Número</th>
                  <th class="text-end">Monto</th>
                  <th>Archivo</th>
                </tr>
              </thead>
              <tbody>
                @foreach($boletas as $b)
                  <tr>
                    <td class="fw-semibold">{{ $b->banco_nombre }}</td>
                    <td>{{ $b->tipo_pago }}</td>
                    <td>{{ $b->fecha_boleta }}</td>
                    <td class="fw-bold">{{ $b->numero_boleta }}</td>
                    <td class="text-end">Q {{ number_format($b->monto, 2) }}</td>
                    <td>
                      @if($b->archivo_ruta)
                        <a class="btn btn-outline-primary btn-sm" href="{{ $b->archivo_ruta }}" target="_blank">
                          Ver archivo
                        </a>
                      @else
                        <span class="muted">—</span>
                      @endif
                    </td>
                  </tr>
                @endforeach
              </tbody>
            </table>
          </div>
        @endif

      </div>
    </div>

  </div>

</div>
@endsection

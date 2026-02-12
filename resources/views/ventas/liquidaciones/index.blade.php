@extends('layouts.app')

@section('content')
<div class="container py-4">

  <style>
    .card-soft { border:1px solid #e5e7eb; border-radius:14px; background:#fff; }
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
    .estado-guardada{ background:#16a34a; color:#fff; }
    .estado-borrador{ background:#6b7280; color:#fff; }
    .estado-rechazada{ background:#dc2626; color:#fff; }
  </style>

  <div class="d-flex justify-content-between align-items-center mb-3">
    <h3 class="mb-0">Liquidaciones</h3>
    <a class="btn btn-primary" href="{{ route('ventas.liquidaciones.create') }}">Nueva Liquidación</a>
  </div>

  <div class="card-soft p-3">
    <div class="table-responsive">
      <table class="table align-middle mb-0">
        <thead>
          <tr>
            <th>#</th>
            <th>Fecha</th>
            <th>Estación</th>
            <th>Operador</th>
            <th class="text-end">Talonarios</th>
            <th class="text-end">Calculado</th>
            <th class="text-end">Boletas</th>
            <th class="text-end">Excedente</th>
            <th>Estado</th>
            <th class="text-end">Acción</th>
          </tr>
        </thead>
        <tbody>
          @forelse($liquidaciones as $l)
            <tr>
              <td><strong>{{ $l->id_liquidacion }}</strong></td>
              <td class="text-muted small">{{ $l->created_at }}</td>
              <td>{{ $l->estacion }}</td>
              <td>{{ $l->operador_nombre ?: '—' }}</td>
              <td class="text-end">{{ $l->cantidad_talonarios }}</td>
              <td class="text-end">Q {{ number_format($l->monto_calculado, 2) }}</td>
              <td class="text-end">Q {{ number_format($l->monto_boletas, 2) }}</td>
              <td class="text-end">Q {{ number_format($l->excedente, 2) }}</td>
              <td>
                @php $st = strtoupper($l->estado ?? ''); @endphp
                @if($st === 'GUARDADA')
                  <span class="badge-estado estado-guardada">GUARDADA</span>
                @elseif($st === 'RECHAZADA')
                  <span class="badge-estado estado-rechazada">RECHAZADA</span>
                @else
                  <span class="badge-estado estado-borrador">{{ $st ?: 'BORRADOR' }}</span>
                @endif
              </td>
              <td class="text-end">
                <a class="btn btn-outline-primary btn-sm" href="{{ route('ventas.liquidaciones.show', $l->id_liquidacion) }}">
                  Ver
                </a>
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="10" class="text-center text-muted py-4">
                No hay liquidaciones todavía.
              </td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>

    <div class="mt-3">
      {{ $liquidaciones->links() }}
    </div>
  </div>

</div>
@endsection

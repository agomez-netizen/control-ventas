@extends('layouts.app')

@section('title', 'Talonarios por estación')

@section('content')
<div class="container py-3">

  <div class="d-flex justify-content-between align-items-center mb-3">
    <div>
      <h3 class="mb-0">Talonarios</h3>
      <div class="text-muted small">
        Estación:
        <strong>{{ $estacion->nombre ?? '—' }}</strong>
      </div>
    </div>

    {{-- RUTA CORRECTA --}}
    <a class="btn btn-outline-secondary"
       href="{{ route('ventas.talonarios.index') }}">
      Volver
    </a>
  </div>

  <div class="card">
    <div class="table-responsive">
      <table class="table mb-0 align-middle">
        <thead class="table-light">
          <tr>
            <th>Talonario</th>
            <th>Rango</th>
            <th class="text-center">Cantidad</th>
            <th class="text-end">Valor</th>
            <th>Estado</th>
          </tr>
        </thead>
        <tbody>

          @forelse($talonarios as $t)
            @php
              $estado = strtoupper((string)($t->estado ?? 'ASIGNADO'));
              $badge = match($estado) {
                'LIQUIDADO' => 'success',
                'ANULADO', 'RECHAZADO' => 'danger',
                'ASIGNADO' => 'secondary',
                default => 'secondary',
              };
            @endphp

            <tr>
              <td><strong>{{ $t->numero_talonario }}</strong></td>
              <td>{{ $t->numero_inicio }} - {{ $t->numero_fin }}</td>
              <td class="text-center">{{ $t->cantidad_numeros ?? 0 }}</td>
              <td class="text-end">
                Q {{ number_format($t->valor_talonario ?? 550, 2) }}
              </td>
              <td>
                <span class="badge bg-{{ $badge }}">
                  {{ $estado }}
                </span>
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="5" class="text-center text-muted py-4">
                Sin talonarios asignados.
              </td>
            </tr>
          @endforelse

        </tbody>
      </table>
    </div>
  </div>

</div>
@endsection

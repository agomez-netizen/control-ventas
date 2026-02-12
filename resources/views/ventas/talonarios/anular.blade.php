@extends('layouts.app')

@section('title', 'Anular Talonario')

@section('content')
<div class="container py-4">

  <div class="d-flex justify-content-between align-items-center mb-3">
    <div>
      <h3 class="mb-0">Anular talonario</h3>
      <div class="text-muted">Solo ADMIN • Requiere motivo</div>
    </div>
    <a href="{{ route('ventas.talonarios.index') }}" class="btn btn-light">← Volver</a>
  </div>

  @if(session('error'))
    <div class="alert alert-danger">{{ session('error') }}</div>
  @endif

  <div class="card shadow-sm">
    <div class="card-body">

      <div class="row g-3">
        <div class="col-md-3">
          <div class="text-muted small">ID</div>
          <div class="fw-bold">#{{ $talonario->id_talonario }}</div>
        </div>
        <div class="col-md-3">
          <div class="text-muted small">Estado</div>
          <span class="badge bg-secondary">{{ $talonario->estado }}</span>
        </div>
        <div class="col-md-3">
          <div class="text-muted small">Talonario</div>
          <div class="fw-semibold">{{ $talonario->numero_talonario }}</div>
        </div>
        <div class="col-md-3">
          <div class="text-muted small">Estación</div>
          <div class="fw-semibold">{{ $talonario->estacion ?? '—' }}</div>
        </div>

        <div class="col-md-3">
          <div class="text-muted small">Inicio</div>
          <div class="fw-semibold">{{ $talonario->numero_inicio }}</div>
        </div>
        <div class="col-md-3">
          <div class="text-muted small">Fin</div>
          <div class="fw-semibold">{{ $talonario->numero_fin }}</div>
        </div>
        <div class="col-md-3">
          <div class="text-muted small">Operador</div>
          <div class="fw-semibold">{{ $talonario->operador ?? '—' }}</div>
        </div>
        <div class="col-md-3">
          <div class="text-muted small">Valor</div>
          <div class="fw-semibold">{{ $talonario->valor_talonario }}</div>
        </div>
      </div>

      <hr class="my-3">

      <form method="POST" action="{{ route('ventas.talonarios.anular.store', $talonario->id_talonario) }}">
        @csrf

        <div class="mb-3">
          <label class="form-label fw-semibold">Motivo de anulación</label>
          <input type="text"
                 name="motivo_anulacion"
                 class="form-control @error('motivo_anulacion') is-invalid @enderror"
                 maxlength="255"
                 required
                 placeholder="Ej: talonario dañado / error de asignación / etc.">
          @error('motivo_anulacion')
            <div class="invalid-feedback">{{ $message }}</div>
          @enderror
        </div>

        <div class="alert alert-warning">
          <b>Confirmación:</b> esto cambia el estado a <b>ANULADO</b> y queda registrado.
        </div>

        <button class="btn btn-danger">Anular ahora</button>
      </form>

    </div>
  </div>

</div>
@endsection

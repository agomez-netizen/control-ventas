@extends('layouts.app')

@section('content')
<div class="container py-4">

  <style>
    .card-soft{ border:1px solid #e5e7eb; border-radius:14px; background:#fff; }
    .muted{ color:#6b7280; }
    .table thead th{ white-space:nowrap; font-size:12px; text-transform:uppercase; letter-spacing:.4px; color:#6b7280; }
    .sticky-actions{ position: sticky; bottom: 0; background: rgba(255,255,255,.92); backdrop-filter: blur(6px); border-top:1px solid #e5e7eb; padding:12px; border-radius: 0 0 14px 14px; }
  </style>

  <div class="d-flex justify-content-between align-items-center mb-3">
    <div>
      <h3 class="mb-0">Carga Masiva</h3>
      <div class="muted">Sube tu Excel y revisa el preview antes de “hacer magia” (controlada).</div>
    </div>
  </div>

  @if(session('ok'))
    <div class="alert alert-success">{{ session('ok') }}</div>
  @endif
  @if(session('err'))
    <div class="alert alert-danger">{{ session('err') }}</div>
  @endif

  @if(session('carga_errors'))
    <div class="alert alert-warning">
      <div class="fw-bold mb-1">Algunas filas fallaron:</div>
      <ul class="mb-0">
        @foreach(session('carga_errors') as $e)
          <li>{{ $e }}</li>
        @endforeach
      </ul>
    </div>
  @endif

  <div class="card-soft p-3 mb-3">
    <form method="POST" action="{{ route('carga.preview') }}" enctype="multipart/form-data" class="row g-3 align-items-end">
      @csrf
      <div class="col-md-8">
        <label class="form-label fw-semibold">Archivo Excel (.xlsx)</label>
        <input type="file" name="archivo" class="form-control" required accept=".xlsx,.xls">
        @error('archivo') <div class="text-danger mt-1">{{ $message }}</div> @enderror
      </div>
      <div class="col-md-4 d-grid">
        <button class="btn btn-primary">
          <i class="bi bi-eye"></i> Ver preview
        </button>
      </div>
    </form>
  </div>

  @isset($token)
    <div class="card-soft overflow-hidden">
      <div class="p-3 border-bottom">
        <div class="d-flex justify-content-between align-items-center">
          <div>
            <div class="fw-bold">Preview</div>
            <div class="muted">Mostrando hasta 50 filas. Total en el archivo: <b>{{ $totalRows }}</b>.</div>
          </div>
          <span class="badge text-bg-secondary">Token: {{ $token }}</span>
        </div>
      </div>

      <div class="table-responsive">
        <table class="table table-hover mb-0 align-middle">
          <thead class="table-light">
            <tr>
              @foreach($headers as $h)
                <th>{{ $h }}</th>
              @endforeach
            </tr>
          </thead>
          <tbody>
            @forelse($previewRows as $r)
              <tr>
                @foreach($headers as $h)
                  <td style="max-width:260px;">
                    <div class="text-truncate" title="{{ $r[$h] ?? '' }}">{{ $r[$h] ?? '' }}</div>
                  </td>
                @endforeach
              </tr>
            @empty
              <tr><td colspan="{{ count($headers) }}" class="text-center p-4 muted">No hay filas para mostrar.</td></tr>
            @endforelse
          </tbody>
        </table>
      </div>

      <div class="sticky-actions">
        <form method="POST" action="{{ route('carga.procesar') }}" class="d-flex gap-2 justify-content-end">
          @csrf
          <input type="hidden" name="token" value="{{ $token }}">
          <button type="submit" class="btn btn-success">
            <i class="bi bi-upload"></i> Procesar carga
          </button>
        </form>
      </div>
    </div>
  @endisset

</div>
@endsection

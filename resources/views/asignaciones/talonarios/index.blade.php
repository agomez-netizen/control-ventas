@extends('layouts.app')

@section('content')
<div class="container py-4">
  <style>
    .card-soft{ border:1px solid #e5e7eb; border-radius:14px; background:#fff; }
    .section-title{ font-weight:800; letter-spacing:.3px; }
    .muted{ color:#6b7280; }
    .req{ color:#dc2626; font-weight:800; }
    .pill{ display:inline-block; padding:6px 10px; border-radius:999px; background:#f3f4f6; font-weight:700; font-size:12px; }
    .readonly{ background:#f9fafb; }
  </style>

  <div class="d-flex justify-content-between align-items-center mb-3">
    <div>
      <h3 class="mb-0 section-title">Asignación de Talonarios</h3>
      <div class="muted">Valores fijos: <span class="pill">Q20 por número</span> <span class="pill">25 números por talonario</span> <span class="pill">Q500 por talonario</span></div>
    </div>
  </div>

  @if(session('ok')) <div class="alert alert-success">{{ session('ok') }}</div> @endif
  @if(session('error')) <div class="alert alert-warning">{{ session('error') }}</div> @endif

  @if($errors->any())
    <div class="alert alert-danger">
      <div class="fw-bold mb-1">Revisa esto:</div>
      <ul class="mb-0">
        @foreach($errors->all() as $e) <li>{{ $e }}</li> @endforeach
      </ul>
    </div>
  @endif

  <div class="card card-soft">
    <div class="card-body">
      <form method="POST" action="{{ route('asignaciones.talonarios.store') }}">
        @csrf

        <div class="row g-3">
          <div class="col-md-6">
            <label class="form-label">Operador (para filtrar estaciones)</label>
            <select id="id_operador" class="form-select">
              <option value="">-- (opcional) --</option>
              @foreach($operadores as $op)
                <option value="{{ $op->id_usuario }}">{{ $op->label }}</option>
              @endforeach
            </select>
            <div class="form-text muted">Si no eliges operador, puedes elegir estación igual.</div>
          </div>

          <div class="col-md-6">
            <label class="form-label">Estación <span class="req">*</span></label>
            <select name="id_estacion" id="id_estacion" class="form-select" required>
              <option value="">-- Seleccionar estación --</option>
              @foreach($estaciones as $e)
                <option value="{{ $e->id_estacion }}"
                        data-operador="{{ $e->id_operador }}"
                        {{ old('id_estacion') == $e->id_estacion ? 'selected' : '' }}>
                  {{ $e->nombre }}
                </option>
              @endforeach
            </select>
          </div>

          <hr class="my-2">

          <div class="col-md-3">
            <label class="form-label">Tal del <span class="req">*</span></label>
            <input type="number" id="tal_del" name="tal_del" class="form-control" min="1" value="{{ old('tal_del') }}" required>
          </div>

          <div class="col-md-3">
            <label class="form-label">Tal al <span class="req">*</span></label>
            <input type="number" id="tal_al" name="tal_al" class="form-control" min="1" value="{{ old('tal_al') }}" required>
          </div>

          <div class="col-md-3">
            <label class="form-label">No. Talonarios</label>
            <input type="number" id="cantidad" name="cantidad" class="form-control readonly" value="{{ old('cantidad', 0) }}" readonly>
            <div class="form-text muted">Se calcula automáticamente con Tal del/al.</div>
          </div>

          <div class="col-md-3">
            <label class="form-label">Cantidad números</label>
            <input type="number" id="cantidad_numeros" name="cantidad_numeros" class="form-control readonly" value="{{ old('cantidad_numeros', 0) }}" readonly>
            <div class="form-text muted">No. Talonarios × 25</div>
          </div>

          <div class="col-md-3">
            <label class="form-label">Número del <span class="req">*</span></label>
            <input type="number" id="numero_del" name="numero_del" class="form-control" min="1" value="{{ old('numero_del') }}" required>
          </div>

          <div class="col-md-3">
            <label class="form-label">Número al</label>
            <input type="number" id="numero_al" name="numero_al" class="form-control readonly" value="{{ old('numero_al') }}" readonly>
            <div class="form-text muted">Se calcula automáticamente.</div>
          </div>

          {{-- valores fijos (se envían ocultos por si quieres guardarlos) --}}
          <input type="hidden" name="valor_numero" value="20">
          <input type="hidden" name="valor_talonario" value="500">
          <input type="hidden" name="numeros_por_talonario" value="25">

          <div class="col-12">
            <div class="alert alert-info mb-0">
              Reglas:
              <b>No. Talonarios = (Tal al - Tal del + 1)</b> |
              <b>Cantidad números = No. Talonarios × 25</b> |
              <b>Número al = Número del + Cantidad números - 1</b>.
            </div>
          </div>

          <div class="col-12 d-flex justify-content-end mt-2">
            <button class="btn btn-primary">
              <i class="bi bi-plus-circle"></i> Generar talonarios
            </button>
          </div>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
(function(){
  const opSel = document.getElementById('id_operador');
  const estSel = document.getElementById('id_estacion');

  const talDel = document.getElementById('tal_del');
  const talAl  = document.getElementById('tal_al');
  const cantidad = document.getElementById('cantidad');

  const cantNums = document.getElementById('cantidad_numeros');
  const numDel = document.getElementById('numero_del');
  const numAl  = document.getElementById('numero_al');

  const NUMS_POR_TAL = 25;

  function filterEstaciones() {
    const op = opSel.value;
    [...estSel.options].forEach((opt, idx) => {
      if (idx === 0) return;
      const opId = opt.getAttribute('data-operador');
      opt.hidden = (op && opId !== op);
    });
    if (estSel.selectedOptions[0] && estSel.selectedOptions[0].hidden) estSel.value = '';
  }

  function toInt(el){
    const v = parseInt(el.value, 10);
    return Number.isFinite(v) ? v : 0;
  }

  function recalc() {
    const a = toInt(talDel);
    const b = toInt(talAl);

    let c = 0;
    if (a > 0 && b > 0 && b >= a) c = (b - a + 1);

    cantidad.value = c;
    cantNums.value = c * NUMS_POR_TAL;

    const nd = toInt(numDel);
    if (nd > 0 && c > 0) {
      numAl.value = nd + (c * NUMS_POR_TAL) - 1;
    } else {
      numAl.value = '';
    }
  }

  opSel.addEventListener('change', filterEstaciones);
  talDel.addEventListener('input', recalc);
  talAl.addEventListener('input', recalc);
  numDel.addEventListener('input', recalc);

  filterEstaciones();
  recalc();
})();
</script>
@endsection

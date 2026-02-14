@extends('layouts.app')

@section('title', 'Nueva Liquidación')

@section('content')
@php
  $idEstacion = old('id_estacion', old('estacion_id', $idEstacion ?? ''));
@endphp

<div class="container">

  <div class="d-flex justify-content-between align-items-center mb-3">
    <div>
      <h3 class="mb-0">Nueva Liquidación</h3>
      <div class="text-muted">Click en el talonario para ver números · Checkbox del talonario = liquidación completa.</div>
    </div>
    <a class="btn btn-outline-secondary" href="{{ route('ventas.liquidaciones.index') }}">Volver</a>
  </div>

  @if(session('error')) <div class="alert alert-danger">{{ session('error') }}</div> @endif
  @if(session('ok'))    <div class="alert alert-success">{{ session('ok') }}</div> @endif
  @if(session('err'))   <div class="alert alert-danger">{{ session('err') }}</div> @endif

  @if($errors->any())
    <div class="alert alert-danger">
      <div class="fw-bold mb-1">Revisa esto:</div>
      <ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
    </div>
  @endif

  <style>
    /* ===== Look & feel tabla ===== */
    .card-soft{ border:1px solid #e5e7eb; border-radius:14px; background:#fff; }
    .table-wrap{ border:1px solid #eef2f7; border-radius:12px; overflow:hidden; }
    .table thead th{
      position: sticky; top: 0; z-index: 1;
      background:#f8fafc !important;
      border-bottom:1px solid #e5e7eb !important;
      font-weight:800;
      letter-spacing:.2px;
    }
    .table tbody tr:hover{ background:#f9fafb; }
    .tal-row{ cursor:pointer; }
    .tal-num{ color:#0d6efd; font-weight:800; }
    .tal-num:hover{ text-decoration: underline; }
    .pill{
      display:inline-flex; align-items:center; gap:6px;
      padding:4px 10px; border-radius:999px;
      background:#f1f5f9; border:1px solid #e2e8f0;
      font-size:12px; font-weight:700; color:#0f172a;
      white-space:nowrap;
    }
    .pill-ok{ background:#ecfdf5; border-color:#a7f3d0; color:#065f46; }
    .pill-warn{ background:#fffbeb; border-color:#fcd34d; color:#92400e; }
    .pill-bad{ background:#fef2f2; border-color:#fecaca; color:#991b1b; }

    /* ===== Detalle números ===== */
    .nums-wrap{ max-height: 260px; overflow:auto; }
    .num-tag{
      border:1px solid #e5e7eb; background:#fff;
      border-radius:10px; padding:6px 10px;
      display:flex; align-items:center; gap:8px;
      font-weight:700; font-size:12px;
    }
    .num-tag input{ margin-top:0; }

    /* Botón cerrar blindado */
    .btn-cerrar-numeros{
      white-space: nowrap;
      min-width: 92px;
      height: 34px;
      display:inline-flex;
      align-items:center;
      justify-content:center;
      gap:8px;
      padding: 0 12px;
      border: 1px solid #cbd5e1;
      border-radius: 10px;
      background: #fff;
      color: #111827;
      font-weight: 800;
      line-height: 1;
      text-decoration: none;
    }
    .btn-cerrar-numeros:hover { background:#f8fafc; }
    .x-dot{
      width:18px; height:18px; border-radius:999px;
      display:inline-flex; align-items:center; justify-content:center;
      border:1px solid #e5e7eb; font-size: 12px; color:#111827;
    }

    /* Mensaje bonito lado boletas */
    .msg-box{
      border:1px dashed #e5e7eb;
      border-radius:12px;
      padding:10px 12px;
      background:#fafafa;
      min-height:44px;
      display:flex;
      align-items:center;
      justify-content:flex-end;
      gap:10px;
    }
  </style>

  {{-- Selector estación --}}
  <div class="card card-soft p-3 mb-3">
    <div class="row g-3 align-items-end">
      <div class="col-md-6">
        <label class="form-label fw-semibold">Estación</label>
        <select id="selEstacion" class="form-select">
          <option value="">Selecciona una estación</option>
          @foreach($estaciones as $e)
            <option value="{{ $e->id_estacion }}" @selected((string)$idEstacion === (string)$e->id_estacion)>
              {{ $e->nombre }}
            </option>
          @endforeach
        </select>
      </div>
      <div class="col-md-6 text-muted">
        <div class="small">Selecciona estación y luego marca talonarios o números.</div>
      </div>
    </div>
  </div>

  {{-- Info estación --}}
  <div class="card card-soft p-3 mb-3" id="cardInfoEstacion" style="display:none;">
    <div class="d-flex justify-content-between align-items-start gap-2">
      <div>
        <div class="text-muted small">Estación</div>
        <div class="h5 mb-1" id="infoEstacionNombre">—</div>

        <div class="text-muted small mt-2">Operador</div>
        <div class="mb-1"><strong id="infoOperador">—</strong></div>

        <div class="text-muted small mt-2">Contacto</div>
        <div class="small">
          Tel estación: <span id="infoTelEstacion">—</span> · Email: <span id="infoEmailEstacion">—</span>
        </div>
        <div class="small">Tel operador: <span id="infoTelOperador">—</span></div>

        <div class="text-muted small mt-2">Ubicación</div>
        <div class="small" id="infoUbicacion">—</div>
      </div>

      <div class="text-end">
        <a href="#" class="btn btn-sm btn-outline-primary" id="btnVerTalonariosEstacion" target="_self">
          Ver talonarios de esta estación
        </a>
      </div>
    </div>
  </div>

  {{-- FORM --}}
  <form method="POST" action="{{ route('ventas.liquidaciones.store') }}" enctype="multipart/form-data" id="formLiquidacion">
    @csrf

    <input type="hidden" name="id_estacion" id="id_estacion" value="{{ $idEstacion }}">
    <input type="hidden" name="talonarios_json" id="talonarios_json" value="[]">
    <input type="hidden" name="numeros_json" id="numeros_json" value="{}">
    <input type="hidden" name="donar_excedente" id="donar_excedente" value="0">

    <div class="row g-3">

      {{-- IZQ --}}
      <div class="col-lg-6">
        <div class="card card-soft p-3 h-100">
          <div class="d-flex justify-content-between align-items-center mb-2">
            <h5 class="mb-0 fw-bold">Seleccionar Talonarios</h5>
            <span class="pill">Mixto: talonario + números</span>
          </div>

          <div class="table-wrap">
            <div class="table-responsive">
              <table class="table table-sm align-middle mb-0">
                <thead>
                  <tr>
                    <th style="width:44px;" title="Marcar = liquidación completa">✓</th>
                    <th>Talonario</th>
                    <th>Rango</th>
                    <th class="text-end">Valor</th>
                  </tr>
                </thead>
                <tbody id="tbodyTalonarios">
                  <tr><td colspan="4" class="text-center text-muted py-3">Selecciona una estación.</td></tr>
                </tbody>
              </table>
            </div>
          </div>

          <div class="d-flex justify-content-between align-items-center mt-3">
            <div class="text-muted">
              <div><strong id="txtCantidad">0</strong> talonarios completos</div>
              <div><strong id="txtNums">0</strong> números seleccionados</div>
            </div>
            <div class="fs-4">
              Total a liquidar: <strong>Q <span id="totalCalculado">0.00</span></strong>
            </div>
          </div>

          <div class="text-muted small mt-2">
            Para liquidar por números: <strong>NO</strong> marques el ✓ del talonario. Dale click a la fila y marca números.
          </div>
        </div>
      </div>

      {{-- DER --}}
      <div class="col-lg-6">
        <div class="card card-soft p-3">
          <div class="d-flex justify-content-between align-items-center mb-2">
            <h5 class="mb-0 fw-bold">Datos de la Liquidación</h5>
            <button type="button" class="btn btn-sm btn-primary" id="btnAddBoleta">+ Agregar boleta</button>
          </div>

          <div class="text-muted mb-3">
            Ingresa una o varias boletas. El total debe coincidir con el monto calculado.
          </div>

          <div class="alert alert-light border d-flex justify-content-between align-items-center mb-3">
            <div class="fw-semibold">Total a liquidar</div>
            <div class="fs-5">Q <strong id="totalCalculadoSide">0.00</strong></div>
          </div>

          <div id="boletasWrap"></div>

          <div class="d-flex justify-content-between align-items-center mt-3">
            <div class="fs-5">
              Total Boletas: <strong>Q <span id="totalBoletas">0.00</span></strong>
            </div>
            <div class="msg-box">
              <span id="msgValidacion" class="small text-muted">Selecciona talonarios o números.</span>
            </div>
          </div>

          <div class="mt-3">
            <label class="form-label">Observación (opcional)</label>
            <input class="form-control" name="observacion" value="{{ old('observacion') }}">
          </div>

          <div class="mt-4 d-flex gap-2">
            <button class="btn btn-success w-100" type="submit">Guardar Liquidación</button>
          </div>

          <div class="mt-2 text-muted small">
            Si el monto en boletas es mayor, el sistema pedirá confirmar donación del excedente.
          </div>
        </div>
      </div>

    </div>
  </form>
</div>
@endsection

@push('scripts')
<script>
(function(){
  const bancos = @json($bancos);

  const TALONARIOS_URL = (idEst) => `{{ url('/ventas/estaciones') }}/${idEst}/talonarios-disponibles`;
  const NUMEROS_URL    = (talId) => `{{ url('/ventas/liquidaciones/talonarios') }}/${talId}/numeros?per_page=5000`;

  const tbody = document.getElementById('tbodyTalonarios');
  const selEstacion = document.getElementById('selEstacion');
  const hiddenEst = document.getElementById('id_estacion');

  const totalCalculadoEl = document.getElementById('totalCalculado');
  const totalCalculadoSideEl = document.getElementById('totalCalculadoSide');
  const totalBoletasEl   = document.getElementById('totalBoletas');
  const txtCantidadEl    = document.getElementById('txtCantidad');
  const txtNumsEl        = document.getElementById('txtNums');
  const msgEl            = document.getElementById('msgValidacion');
  const donarInput       = document.getElementById('donar_excedente');

  const talJson = document.getElementById('talonarios_json');
  const numJson = document.getElementById('numeros_json');

  const cardInfo = document.getElementById('cardInfoEstacion');
  const infoNombre = document.getElementById('infoEstacionNombre');
  const infoOperador = document.getElementById('infoOperador');
  const infoTelEst = document.getElementById('infoTelEstacion');
  const infoEmailEst = document.getElementById('infoEmailEstacion');
  const infoTelOp = document.getElementById('infoTelOperador');
  const infoUbic = document.getElementById('infoUbicacion');
  const btnVerTal = document.getElementById('btnVerTalonariosEstacion');

  const boletasWrap = document.getElementById('boletasWrap');

  let talonariosCache = new Map();
  let talonariosFull  = new Set();
  let numerosSel      = {};
  let expanded        = null;
  let numerosLoaded   = new Set();

  function money(n){ return (Math.round(n * 100) / 100).toFixed(2); }

  function setMsg(text, kind){
    msgEl.className = 'small';
    msgEl.textContent = text;

    if(kind === 'ok') msgEl.classList.add('text-success');
    else if(kind === 'warn') msgEl.classList.add('text-warning');
    else if(kind === 'bad') msgEl.classList.add('text-danger');
    else msgEl.classList.add('text-muted');
  }

  function syncHidden(){
    talJson.value = JSON.stringify(Array.from(talonariosFull).map(Number));
    numJson.value = JSON.stringify(numerosSel);
  }

  function calcTotales(){
    let totalCalc = 0;
    let countNums = 0;

    for (const talId of talonariosFull) {
      const t = talonariosCache.get(String(talId));
      totalCalc += parseFloat(t?.valor_talonario ?? 0);
    }

    for (const talId in numerosSel) {
      if (talonariosFull.has(String(talId))) continue;
      const arr = numerosSel[talId] || [];
      if (!Array.isArray(arr) || arr.length === 0) continue;

      const t = talonariosCache.get(String(talId));
      const precio = parseFloat(t?.valor_numero ?? 0);

      totalCalc += arr.length * precio;
      countNums += arr.length;
    }

    txtCantidadEl.textContent = talonariosFull.size;
    txtNumsEl.textContent = countNums;

    const calcStr = money(totalCalc);
    totalCalculadoEl.textContent = calcStr;
    totalCalculadoSideEl.textContent = calcStr;

    let totalBol = 0;
    document.querySelectorAll('.inp-monto').forEach(i => {
      const v = parseFloat(i.value || '0');
      if(!isNaN(v)) totalBol += v;
    });
    totalBoletasEl.textContent = money(totalBol);

    donarInput.value = '0';

    if(totalCalc <= 0){
      setMsg('Selecciona talonarios o números.', 'muted');
      syncHidden();
      return;
    }
    if(totalBol <= 0){
      setMsg('Ingresa monto(s) de boleta.', 'muted');
      syncHidden();
      return;
    }
    if (Math.abs(totalBol - totalCalc) < 0.01) {
      setMsg('Montos coinciden. Puedes guardar.', 'ok');
    } else if (totalBol < totalCalc) {
      setMsg('No se puede procesar la liquidación porque está incompleta.', 'bad');
    } else {
      setMsg('El monto excede el total. Al guardar te pedirá confirmar donación.', 'warn');
    }

    syncHidden();
  }

  function setRowPill(t, id){
    const el = document.getElementById(`pill-${id}`);
    if(!el) return;

    if(talonariosFull.has(id)){
      el.className = 'pill pill-ok';
      el.textContent = 'Completo';
      return;
    }
    const hasNums = Array.isArray(numerosSel[id]) && numerosSel[id].length > 0;
    if(hasNums){
      el.className = 'pill pill-warn';
      el.textContent = `${numerosSel[id].length} nums`;
      return;
    }
    el.className = 'pill';
    el.textContent = '#';
  }

  function disableNumbersUI(talId, disabled){
    const wrap = document.getElementById(`nums-${talId}`);
    if(!wrap) return;
    wrap.querySelectorAll('input.chk-num').forEach(i => {
      i.disabled = disabled;
      if(disabled) i.checked = false;
    });
  }

  function closeDetail(talId){
    const detail = document.getElementById(`detail-${talId}`);
    if(detail) detail.style.display = 'none';
    if(expanded === talId) expanded = null;
  }

  async function toggleDetail(talId){
    if (expanded && expanded !== talId) closeDetail(expanded);

    const detail = document.getElementById(`detail-${talId}`);
    if (!detail) return;

    const willOpen = (detail.style.display === 'none' || detail.style.display === '');
    detail.style.display = willOpen ? '' : 'none';
    expanded = willOpen ? talId : null;

    if (!willOpen) return;

    if (!numerosLoaded.has(talId)) {
      await loadNumeros(talId);
      numerosLoaded.add(talId);
    } else {
      disableNumbersUI(talId, talonariosFull.has(talId));
    }
  }

  async function loadNumeros(talId){
    const numsWrap = document.getElementById(`nums-${talId}`);
    if(!numsWrap) return;

    numsWrap.innerHTML = `<div class="text-muted">Cargando números…</div>`;

    const res = await fetch(NUMEROS_URL(talId), { headers: { 'X-Requested-With': 'XMLHttpRequest' }});
    if(!res.ok){
      numsWrap.innerHTML = `<div class="text-danger">Error al cargar números.</div>`;
      return;
    }

    const payload = await res.json();
    const list = payload.data || [];
    const readonly = !!payload.readonly;

    if(list.length === 0){
      numsWrap.innerHTML = `<div class="text-muted">No hay números disponibles.</div>`;
      return;
    }

    const selected = new Set((numerosSel[talId] || []).map(n => parseInt(n)));

    numsWrap.innerHTML = list.map(nobj => {
      const num = parseInt(nobj.numero);
      const checked = selected.has(num) ? 'checked' : '';
      const dis = (readonly || talonariosFull.has(talId)) ? 'disabled' : '';
      return `
        <label class="num-tag">
          <input type="checkbox" class="form-check-input chk-num" data-tal="${talId}" data-num="${num}" ${checked} ${dis}>
          <span>${num}</span>
        </label>
      `;
    }).join('');

    numsWrap.querySelectorAll('.chk-num').forEach(chk => {
      chk.addEventListener('change', () => {
        const tId = String(chk.dataset.tal);
        const num = parseInt(chk.dataset.num);

        if (talonariosFull.has(tId)) {
          chk.checked = false;
          return;
        }

        const arr = Array.isArray(numerosSel[tId]) ? numerosSel[tId] : [];
        const s = new Set(arr.map(x => parseInt(x)));

        if (chk.checked) s.add(num);
        else s.delete(num);

        const out = Array.from(s).sort((a,b)=>a-b);
        if (out.length === 0) delete numerosSel[tId];
        else numerosSel[tId] = out;

        setRowPill(null, tId);
        calcTotales();
      });
    });

    setRowPill(null, talId);
    calcTotales();
  }

  function renderTalonarios(list){
    if(!list || list.length === 0){
      tbody.innerHTML = `<tr><td colspan="4" class="text-center text-muted py-3">No hay talonarios disponibles.</td></tr>`;
      return;
    }

    tbody.innerHTML = list.map(t => {
      const id = String(t.id_talonario);
      return `
        <tr class="tal-row" data-id="${id}">
          <td style="width:44px;">
            <input type="checkbox" class="form-check-input chk-full" data-id="${id}">
          </td>

          <td class="tal-num">
            ${t.numero_talonario}
            <span class="ms-2" id="pill-${id}" class="pill">—</span>
          </td>

          <td>${t.numero_inicio} - ${t.numero_fin}</td>
          <td class="text-end">Q ${money(parseFloat(t.valor_talonario ?? 0))}</td>
        </tr>

        <tr id="detail-${id}" style="display:none;">
          <td colspan="4">
            <div class="border rounded p-3 bg-light">
              <div class="d-flex justify-content-between align-items-center mb-2">
                <div>
                  <div class="fw-bold">Números del talonario <span class="text-primary">${t.numero_talonario}</span></div>
                  <div class="small text-muted">Precio por número: <strong>Q ${money(parseFloat(t.valor_numero ?? 0))}</strong></div>
                  {{-- ✅ SOLO UNA VEZ (ya no se repite) --}}
                  <div class="small text-muted">Marca números vendidos para liquidación parcial.</div>
                </div>

                <button type="button" class="btn-cerrar-numeros js-close" data-id="${id}">
                  <span class="x-dot">×</span> Cerrar
                </button>
              </div>

              <div class="d-flex flex-wrap gap-2 nums-wrap" id="nums-${id}">
                <div class="text-muted">Cargando…</div>
              </div>
            </div>
          </td>
        </tr>
      `;
    }).join('');

    document.querySelectorAll('.chk-full').forEach(chk => {
      chk.addEventListener('click', (ev) => {
        ev.stopPropagation();
        const id = String(chk.dataset.id);

        if (chk.checked) {
          talonariosFull.add(id);
          if (numerosSel[id]) delete numerosSel[id];
          disableNumbersUI(id, true);
        } else {
          talonariosFull.delete(id);
          disableNumbersUI(id, false);
        }

        setRowPill(null, id);
        calcTotales();
      });
    });

    // click fila abre/cierra (excepto checkbox)
    document.querySelectorAll('.tal-row').forEach(row => {
      row.addEventListener('click', async (ev) => {
        const tag = (ev.target?.tagName || '').toLowerCase();
        if(tag === 'input' || tag === 'label') return;
        await toggleDetail(String(row.dataset.id));
      });
    });

    document.querySelectorAll('.js-close').forEach(btn => {
      btn.addEventListener('click', (ev) => {
        ev.stopPropagation();
        closeDetail(String(btn.dataset.id));
      });
    });

    // init pills
    list.forEach(t => setRowPill(null, String(t.id_talonario)));

    calcTotales();
  }

  async function cargarTalonarios(idEst){
    tbody.innerHTML = `<tr><td colspan="4" class="text-center text-muted py-3">Cargando...</td></tr>`;
    talonariosCache = new Map();
    talonariosFull = new Set();
    numerosSel = {};
    expanded = null;
    numerosLoaded = new Set();
    syncHidden();
    calcTotales();
    cardInfo.style.display = 'none';

    if(!idEst){
      tbody.innerHTML = `<tr><td colspan="4" class="text-center text-muted py-3">Selecciona una estación.</td></tr>`;
      return;
    }

    const res = await fetch(TALONARIOS_URL(idEst), { headers: { 'X-Requested-With': 'XMLHttpRequest' }});
    if(!res.ok){
      tbody.innerHTML = `<tr><td colspan="4" class="text-center text-danger py-3">Error al cargar talonarios.</td></tr>`;
      return;
    }

    const data = await res.json();
    const list = data.talonarios || [];

    if(data.estacion){
      const e = data.estacion;
      cardInfo.style.display = '';
      infoNombre.textContent = e.nombre ?? '—';
      infoOperador.textContent = e.operador ?? '—';
      infoTelEst.textContent = e.telefono ?? '—';
      infoEmailEst.textContent = e.correo ?? '—';
      infoTelOp.textContent = e.telefono_operador ?? '—';
      infoUbic.textContent = `${e.pais ?? ''} · ${e.departamento ?? ''} · ${e.municipio ?? ''} · ${e.direccion ?? ''}`.replace(/\s+/g,' ').trim() || '—';
      btnVerTal.href = `{{ url('/ventas/talonarios/estacion') }}/${e.id_estacion}`;
    }

    list.forEach(t => talonariosCache.set(String(t.id_talonario), t));
    renderTalonarios(list);
  }

  // Boletas
  function boletaRow(index){
    const div = document.createElement('div');
    div.className = 'border rounded p-3 mb-3 boleta-item';
    div.dataset.index = index;

    const banksOptions = bancos.map(b => `<option value="${b.id_banco}">${b.nombre}</option>`).join('');

    div.innerHTML = `
      <div class="d-flex justify-content-between align-items-center mb-2">
        <strong>Boleta #${index+1}</strong>
        <button type="button" class="btn btn-sm btn-outline-danger btnRemove">Quitar</button>
      </div>

      <div class="row g-2">
        <div class="col-md-4">
          <label class="form-label">Banco</label>
          <select name="boletas[${index}][id_banco]" class="form-select" required>
            <option value="">-- Seleccionar --</option>
            ${banksOptions}
          </select>
        </div>

        <div class="col-md-4">
          <label class="form-label">Tipo</label>
          <select name="boletas[${index}][tipo_pago]" class="form-select" required>
            <option value="DEPOSITO">Depósito</option>
            <option value="TRANSFERENCIA">Transferencia</option>
          </select>
        </div>

        <div class="col-md-4">
          <label class="form-label">Fecha</label>
          <input type="date" name="boletas[${index}][fecha_boleta]" class="form-control" required>
        </div>

        <div class="col-md-6">
          <label class="form-label">Número de boleta</label>
          <input name="boletas[${index}][numero_boleta]" class="form-control" placeholder="Ej: 123456" required>
        </div>

        <div class="col-md-3">
          <label class="form-label">Monto</label>
          <input name="boletas[${index}][monto]" class="form-control inp-monto" inputmode="decimal" placeholder="0.00" required>
        </div>

        <div class="col-md-3">
          <label class="form-label">Archivo</label>
          <input type="file" name="boletas[${index}][archivo]" class="form-control">
        </div>
      </div>
    `;

    div.querySelector('.btnRemove').addEventListener('click', () => {
      div.remove();
      calcTotales();
    });

    div.querySelectorAll('input,select').forEach(el => {
      el.addEventListener('input', calcTotales);
      el.addEventListener('change', calcTotales);
    });

    return div;
  }

  function addBoleta(){
    const index = document.querySelectorAll('.boleta-item').length;
    boletasWrap.appendChild(boletaRow(index));
  }

  document.getElementById('btnAddBoleta')?.addEventListener('click', () => {
    addBoleta();
    calcTotales();
  });

  // ✅ Reglas completas al guardar (donación / incompleta)
  document.getElementById('formLiquidacion').addEventListener('submit', function(e){
    const totalCalc = parseFloat(totalCalculadoEl.textContent || '0');
    const totalBol  = parseFloat(totalBoletasEl.textContent || '0');

    if(!hiddenEst.value){
      e.preventDefault();
      setMsg('Selecciona una estación.', 'bad');
      return;
    }

    const hasFull = talonariosFull.size > 0;
    const hasNums = Object.keys(numerosSel).some(k => Array.isArray(numerosSel[k]) && numerosSel[k].length > 0);

    if(!hasFull && !hasNums){
      e.preventDefault();
      setMsg('Selecciona al menos 1 talonario o 1 número.', 'bad');
      return;
    }

    if(totalCalc <= 0){
      e.preventDefault();
      setMsg('No hay nada que liquidar.', 'bad');
      return;
    }

    if(totalBol <= 0){
      e.preventDefault();
      setMsg('Agrega al menos 1 boleta con monto.', 'bad');
      return;
    }

    // ✅ Menor = incompleta
    if(totalBol < totalCalc - 0.01){
      e.preventDefault();
      setMsg('No se puede procesar la liquidación porque está incompleta.', 'bad');
      return;
    }

    // ✅ Mayor = preguntar donación
    if(totalBol > totalCalc + 0.01){
      const ok = confirm(`El monto en boletas excede el total (Q${money(totalBol)} vs Q${money(totalCalc)}).\n\n¿Deseas donar el excedente?`);
      if(!ok){
        e.preventDefault();
        setMsg('No se puede procesar la liquidación porque el monto excede su totalidad.', 'bad');
        return;
      }
      donarInput.value = '1';
      setMsg('Excedente confirmado como donación. Se procesará la liquidación.', 'ok');
      return;
    }

    // ✅ Exacto
    donarInput.value = '0';
    setMsg('Montos correctos. Procesando…', 'ok');
  });

  // Init
  addBoleta();
  calcTotales();

  selEstacion?.addEventListener('change', () => {
    const id = selEstacion.value;
    hiddenEst.value = id;
    cargarTalonarios(id);
  });

  if(hiddenEst.value){
    selEstacion.value = hiddenEst.value;
    cargarTalonarios(hiddenEst.value);
  }
})();
</script>
@endpush

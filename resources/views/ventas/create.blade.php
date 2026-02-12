{{-- resources/views/ventas/create.blade.php --}}
@extends('layouts.app')

@section('content')
<div class="container py-4">

  <style>
    .card-soft { border:1px solid #e5e7eb; border-radius:14px; background:#fff; }
    .section-title { font-weight:700; letter-spacing:.2px; }
    .muted { color:#6b7280; }
    .grid-2 { display:grid; grid-template-columns:1fr 1fr; gap:18px; }
    @media (max-width: 992px){ .grid-2 { grid-template-columns: 1fr; } }

    .tal-row{
      border:1px solid #e5e7eb;
      border-radius:12px;
      padding:10px 12px;
      display:flex;
      align-items:flex-start;
      justify-content:space-between;
      gap:12px;
      background:#fff;
      margin-bottom:10px;
      cursor:pointer;
      transition: box-shadow .15s ease;
    }
    .tal-row:hover{ box-shadow:0 10px 24px rgba(0,0,0,.06); }
    .tal-left{ display:flex; gap:10px; align-items:flex-start; width:100%; }
    .tal-meta{ font-size:12px; color:#6b7280; }
    .tal-title{ font-weight:800; font-size:18px; line-height:1.1; }

    .pill{
      display:inline-flex; align-items:center; gap:8px;
      padding:4px 10px; border-radius:999px;
      border:1px solid #e5e7eb; background:#fff;
      font-size:12px; color:#374151;
    }
    .pill strong{ font-weight:800; }
    .mini-list{ font-size:12px; color:#6b7280; }

    .nums-box{
      margin-top:10px;
      padding:12px;
      border-radius:12px;
      background:#f9fafb;
      border:1px solid #e5e7eb;
      cursor: default;
    }

    .num-grid{
      display:grid;
      grid-template-columns: repeat(auto-fill, minmax(92px, 1fr));
      gap:10px;
    }

    .num-item{
      border:1px solid #e5e7eb;
      border-radius:12px;
      padding:10px 10px;
      display:flex;
      align-items:center;
      justify-content:center;
      gap:10px;
      background:#fff;
      min-height:46px;
      cursor:pointer;
      user-select:none;
      transition: box-shadow .15s ease, border-color .15s ease, background .15s ease;
    }
    .num-item:hover{ box-shadow:0 6px 18px rgba(0,0,0,.06); }

    .num-item input[type="checkbox"]{
      width:18px;
      height:18px;
      margin:0;
      flex:0 0 auto;
    }

    .num-text{
      font-weight:900;
      font-size:14px;
      letter-spacing:.2px;
      line-height:1;
      min-width:54px;
      text-align:center;
      display:inline-block;
    }

    .num-item.selected{
      border-color:#16a34a;
      background:rgba(22,163,74,.08);
    }

    .num-item.disabled{
      opacity:.60;
      pointer-events:none;
    }

    .sticky-footer{
      position: sticky; bottom: 0;
      background: linear-gradient(to top, rgba(255,255,255,1), rgba(255,255,255,.9));
      border-top: 1px solid #e5e7eb;
      padding-top: 10px; margin-top: 14px;
    }

    .total-pill{
      display:inline-flex; align-items:center; gap:8px;
      padding:6px 10px; border-radius:999px;
      border:1px solid #e5e7eb; background:#fff;
      font-weight:800;
    }
    .total-ok{ border-color:#16a34a; color:#16a34a; }
    .total-bad{ border-color:#dc2626; color:#dc2626; }
    .total-warn{ border-color:#f59e0b; color:#b45309; }
  </style>

  <div class="d-flex justify-content-between align-items-center mb-3">
    <div>
      <h3 class="mb-0">Nueva Liquidación</h3>
      <div class="muted">
        Click en el <b>talonario</b> para ver números · Checkbox del talonario = liquidación completa.
      </div>
    </div>
    <a href="{{ url()->previous() }}" class="btn btn-outline-secondary">Volver</a>
  </div>

  @if(session('ok'))
    <div class="alert alert-success">{{ session('ok') }}</div>
  @endif
  @if(session('err'))
    <div class="alert alert-danger">{{ session('err') }}</div>
  @endif

  <div class="card-soft p-3 mb-3">
    <label class="form-label fw-semibold mb-2">Estación</label>
    <div class="d-flex gap-2 align-items-center">
      <select class="form-select" id="estacionSelect">
        <option value="" disabled {{ ((int)$estacionId <= 0) ? 'selected' : '' }}>
          -- Selecciona estación --
        </option>
        @foreach($estaciones as $e)
          <option value="{{ $e->id }}" {{ ((int)$estacionId === (int)$e->id) ? 'selected' : '' }}>
            {{ $e->nombre }}
          </option>
        @endforeach
      </select>
      <small class="muted">Mixto: talonario + números</small>
    </div>
  </div>

  @if($estacion)
  <div class="card-soft p-3 mb-3">
    <div class="d-flex justify-content-between align-items-start">
      <div>
        <div class="muted">Estación</div>
        <div class="fs-5 fw-bold">{{ $estacion->nombre }}</div>

        <div class="mt-2">
          <div class="muted">Operador</div>
          <div class="fw-semibold">{{ $estacion->operador_nombre ?: '—' }}</div>
          <div class="muted">Tel operador: {{ $estacion->operador_telefono ?: '—' }}</div>
        </div>

        <div class="mt-2">
          <div class="muted">Contacto</div>
          <div>Tel estación: {{ $estacion->telefono ?: '—' }} · Correo: {{ $estacion->correo ?: '—' }}</div>
        </div>

        <div class="mt-2">
          <div class="muted">Ubicación</div>
          <div>{{ $estacion->pais }} · {{ $estacion->departamento }} · {{ $estacion->municipio }} · {{ $estacion->direccion }}</div>
        </div>
      </div>

      <span class="badge text-bg-light border">Mixto: talonario + números</span>
    </div>
  </div>
  @endif

  <form method="POST" action="{{ route('ventas.liquidaciones.store') }}" enctype="multipart/form-data" id="formLiquidacion">
    @csrf

    <input type="hidden" name="estacion_id" value="{{ (int)$estacionId }}">
    <input type="hidden" name="talonarios_json" id="talonarios_json" value="[]">
    <input type="hidden" name="numeros_json" id="numeros_json" value="{}">
    <input type="hidden" name="donar_excedente" id="donar_excedente" value="0">

    <div class="grid-2">
      {{-- IZQUIERDA --}}
      <div class="card-soft p-3">
        <div class="d-flex justify-content-between align-items-center mb-2">
          <div class="section-title">Seleccionar Talonarios / Números</div>
          <span class="badge text-bg-secondary">ASIGNADO</span>
        </div>

        @if((int)$estacionId <= 0)
          <div class="alert alert-info mb-0">Selecciona una estación arriba para cargar talonarios.</div>
        @else
          <div class="d-flex gap-2 mb-3">
            <button type="button" class="btn btn-outline-danger btn-sm" id="btnClearAll">Limpiar todo</button>
            <button type="button" class="btn btn-outline-dark btn-sm" id="btnReload">Recargar</button>
          </div>

          <div id="talonariosWrap">
            <div class="alert alert-light border mb-0">Cargando talonarios...</div>
          </div>

          <div class="sticky-footer">
            <div class="d-flex justify-content-between align-items-center">
              <div class="muted">
                Talonarios: <b id="countT">0</b> · Números: <b id="countN">0</b>
              </div>
              <div class="fs-5 fw-bold">
                Total Calculado: Q <span id="totalCalc">0.00</span>
              </div>
            </div>
          </div>
        @endif
      </div>

      {{-- DERECHA --}}
      <div class="card-soft p-3">
        <div class="d-flex justify-content-between align-items-start">
          <div>
            <div class="section-title">Datos de la Liquidación</div>
            <div class="muted">
              Banco, tipo, fecha, número y monto son obligatorios. Si hay excedente, puedes donarlo.
            </div>
          </div>
          <button type="button" class="btn btn-primary btn-sm" id="btnAddBoleta">+ Agregar boleta</button>
        </div>

        <div class="mt-3">
          <div class="fw-bold mb-2">Boletas</div>
          <div id="boletasWrap"></div>

          <div class="d-flex justify-content-between align-items-center mt-3">
            <div class="fw-semibold">
              Total Boletas:
              <span class="total-pill total-bad" id="boletasPill">Q <span id="totalBoletas">0.00</span></span>
            </div>
            <div class="muted" id="msgMatch">Selecciona talonarios/números para calcular.</div>
          </div>
        </div>

        <div class="mt-3">
          <label class="form-label fw-semibold">Observación (opcional)</label>
          <textarea class="form-control" name="observacion" rows="3" maxlength="255" placeholder="Notas...">{{ old('observacion') }}</textarea>
        </div>

        <div class="mt-3 d-grid">
          <button type="submit" class="btn btn-success btn-lg">Guardar Liquidación</button>
        </div>
      </div>
    </div>
  </form>

  {{-- Modal bonito (fallback a alert/confirm si no hay bootstrap JS) --}}
  <div class="modal fade" id="uiModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content" style="border-radius:16px;">
        <div class="modal-header">
          <h5 class="modal-title" id="uiModalTitle">Aviso</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
        </div>
        <div class="modal-body" id="uiModalBody"></div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary d-none" id="uiCancelBtn" data-bs-dismiss="modal">Cancelar</button>
          <button type="button" class="btn btn-primary" id="uiOkBtn">Aceptar</button>
        </div>
      </div>
    </div>
  </div>

</div>

<script>
(function(){
  const estacionId = Number("{{ (int)$estacionId }}");
  const estacionSelect = document.getElementById('estacionSelect');

  const talonariosWrap = document.getElementById('talonariosWrap');
  const talonariosJson = document.getElementById('talonarios_json');
  const numerosJson    = document.getElementById('numeros_json');
  const donarExcedente = document.getElementById('donar_excedente');

  const countT = document.getElementById('countT');
  const countN = document.getElementById('countN');
  const totalCalcEl = document.getElementById('totalCalc');

  const btnClearAll = document.getElementById('btnClearAll');
  const btnReload   = document.getElementById('btnReload');

  const boletasWrap = document.getElementById('boletasWrap');
  const btnAddBoleta = document.getElementById('btnAddBoleta');
  const totalBoletasEl = document.getElementById('totalBoletas');

  const boletasPill = document.getElementById('boletasPill');
  const msgMatch = document.getElementById('msgMatch');

  const bancos = @json($bancos ?? []);

  let talonarios = [];
  let selectedT = new Set(); // talonarios completos
  let selectedN = {};        // { talId: Set(nums) }
  let openTal = new Set();   // talonarios abiertos

  // ===== modal bonito con fallback =====
  const modalEl = document.getElementById('uiModal');
  const modalTitle = document.getElementById('uiModalTitle');
  const modalBody = document.getElementById('uiModalBody');
  const okBtn = document.getElementById('uiOkBtn');
  const cancelBtn = document.getElementById('uiCancelBtn');

  const hasBootstrap = (typeof window.bootstrap !== 'undefined') && modalEl;
  const bsModal = hasBootstrap ? new bootstrap.Modal(modalEl) : null;

  function niceAlert(title, html){
    if (!hasBootstrap) { alert((title ? title + "\n\n" : "") + (html || '').replace(/<[^>]+>/g,'')); return Promise.resolve(true); }
    return new Promise(resolve => {
      modalTitle.textContent = title || 'Aviso';
      modalBody.innerHTML = html || '';
      cancelBtn.classList.add('d-none');
      okBtn.textContent = 'Entendido';
      okBtn.className = 'btn btn-primary';
      okBtn.onclick = () => { bsModal.hide(); resolve(true); };
      bsModal.show();
    });
  }

  function niceConfirm(title, html, okText='Sí', cancelText='No'){
    if (!hasBootstrap) return Promise.resolve(confirm((title ? title + "\n\n" : "") + (html || '').replace(/<[^>]+>/g,'')));
    return new Promise(resolve => {
      modalTitle.textContent = title || 'Confirmar';
      modalBody.innerHTML = html || '';
      cancelBtn.classList.remove('d-none');
      cancelBtn.textContent = cancelText;
      okBtn.textContent = okText;
      okBtn.className = 'btn btn-primary';
      okBtn.onclick = () => { bsModal.hide(); resolve(true); };
      cancelBtn.onclick = () => { bsModal.hide(); resolve(false); };
      bsModal.show();
    });
  }

  function moneyQ(n){ return Number(n || 0).toFixed(2); }

  estacionSelect?.addEventListener('change', () => {
    const v = estacionSelect.value || '';
    const url = new URL(window.location.href);
    if (!v) url.searchParams.delete('estacion_id');
    else url.searchParams.set('estacion_id', v);
    window.location.href = url.toString();
  });

  function getSelectedNumsArray(talId){
    const set = selectedN[talId];
    if (!set || !set.size) return [];
    return Array.from(set).map(Number).sort((a,b)=>a-b);
  }

  function getTotals(){
    const totalCalc = Number((totalCalcEl?.textContent || '0').replace(',', ''));
    const totalBol  = Number((totalBoletasEl?.textContent || '0').replace(',', ''));
    const diff = Math.round((totalBol - totalCalc) * 100) / 100;
    return { totalCalc, totalBol, diff };
  }

  function refreshMatchUI(){
    const { totalCalc, diff } = getTotals();
    boletasPill?.classList.remove('total-ok','total-bad','total-warn');

    if (!boletasPill || !msgMatch) return;

    if (totalCalc <= 0) {
      boletasPill.classList.add('total-bad');
      msgMatch.textContent = 'Selecciona talonarios o números para calcular.';
      return;
    }

    if (diff === 0) {
      boletasPill.classList.add('total-ok');
      msgMatch.textContent = '✅ Todo cuadra perfecto.';
      return;
    }

    if (diff < 0) {
      boletasPill.classList.add('total-bad');
      msgMatch.textContent = `Faltan Q ${Math.abs(diff).toFixed(2)} para cuadrar.`;
      return;
    }

    boletasPill.classList.add('total-warn');
    msgMatch.textContent = `Excedente Q ${diff.toFixed(2)} (puedes donarlo).`;
  }

  function syncHidden(){
    talonariosJson.value = JSON.stringify(Array.from(selectedT).map(Number));

    const obj = {};
    Object.keys(selectedN).forEach(tid => {
      const arr = getSelectedNumsArray(tid);
      if (arr.length) obj[tid] = arr;
    });
    numerosJson.value = JSON.stringify(obj);

    if (countT) countT.textContent = String(selectedT.size);

    let nCount = 0;
    let total = 0;

    selectedT.forEach(id => {
      const t = talonarios.find(x => Number(x.id) === Number(id));
      if (t) total += Number(t.valor_talonario || 0);
    });

    Object.keys(obj).forEach(tid => {
      const t = talonarios.find(x => Number(x.id) === Number(tid));
      const precio = t ? Number(t.valor_numero || 0) : 0;
      nCount += obj[tid].length;
      total += obj[tid].length * precio;
    });

    if (countN) countN.textContent = String(nCount);
    if (totalCalcEl) totalCalcEl.textContent = moneyQ(total);

    refreshMatchUI();
  }

  function bancosOptions(){
    return bancos.map(b => `<option value="${b.id_banco}">${b.nombre}</option>`).join('');
  }

  // ===== Boletas =====
  let boletaIndex = 0;

  function boletaTemplate(i){
    return `
      <div class="card-soft p-3 mb-2" data-boleta="${i}">
        <div class="d-flex justify-content-between align-items-center mb-2">
          <div class="fw-bold">Boleta #${i+1}</div>
          <button type="button" class="btn btn-outline-danger btn-sm" data-remove="${i}">Quitar</button>
        </div>

        <div class="row g-2">
          <div class="col-md-4">
            <label class="form-label fw-semibold">Banco *</label>
            <select class="form-select boleta-banco" name="boletas[${i}][id_banco]" required>
              <option value="">-- Selecciona --</option>
              ${bancosOptions()}
            </select>
          </div>
          <div class="col-md-4">
            <label class="form-label fw-semibold">Tipo *</label>
            <select class="form-select boleta-tipo" name="boletas[${i}][tipo_pago]" required>
              <option value="">-- Selecciona --</option>
              <option value="DEPOSITO">Depósito</option>
              <option value="TRANSFERENCIA">Transferencia</option>
            </select>
          </div>
          <div class="col-md-4">
            <label class="form-label fw-semibold">Fecha *</label>
            <input type="date" class="form-control boleta-fecha" name="boletas[${i}][fecha_boleta]" required>
          </div>

          <div class="col-md-6">
            <label class="form-label fw-semibold">Número de boleta *</label>
            <input class="form-control boleta-numero" name="boletas[${i}][numero_boleta]" required placeholder="Ej: 123456">
          </div>
          <div class="col-md-3">
            <label class="form-label fw-semibold">Monto *</label>
            <input class="form-control boleta-monto" name="boletas[${i}][monto]" required placeholder="0.00" value="0" inputmode="decimal">
          </div>
          <div class="col-md-3">
            <label class="form-label fw-semibold">Archivo</label>
            <input type="file" class="form-control" name="boletas[${i}][archivo]">
          </div>
        </div>
      </div>
    `;
  }

  function calcTotalBoletas(){
    let sum = 0;
    document.querySelectorAll('.boleta-monto').forEach(inp => sum += Number(inp.value || 0));
    if (totalBoletasEl) totalBoletasEl.textContent = moneyQ(sum);
    refreshMatchUI();
  }

  function addBoleta(){
    if (!boletasWrap) return;
    boletasWrap.insertAdjacentHTML('beforeend', boletaTemplate(boletaIndex));
    const idx = boletaIndex;
    boletaIndex++;

    boletasWrap.querySelector(`[data-remove="${idx}"]`)?.addEventListener('click', () => {
      boletasWrap.querySelector(`[data-boleta="${idx}"]`)?.remove();
      calcTotalBoletas();
    });

    boletasWrap.querySelectorAll('.boleta-monto').forEach(inp => inp.oninput = calcTotalBoletas);
    calcTotalBoletas();
  }

  btnAddBoleta?.addEventListener('click', addBoleta);
  addBoleta();

  function validateUniqueBoletasFront(){
    const seen = new Set();
    let dup = false;

    document.querySelectorAll('#boletasWrap [data-boleta]').forEach(card => {
      const banco = card.querySelector('.boleta-banco')?.value || '';
      const num   = (card.querySelector('.boleta-numero')?.value || '').trim();

      if (!banco || !num) return;

      const key = banco + '::' + num;
      if (seen.has(key)) dup = true;
      seen.add(key);
    });

    return !dup;
  }

  // ===== Talonarios + Números =====
  function updateSelInfo(talId){
    const selLabel = document.getElementById(`selLabel-${talId}`);
    const mini = document.getElementById(`mini-${talId}`);
    const pill = document.getElementById(`pill-${talId}`);
    if (!selLabel || !mini || !pill) return;

    if (selectedT.has(talId)) {
      pill.classList.remove('d-none');
      selLabel.innerHTML = `Talonario completo`;
      mini.textContent = `✅ Se liquidará todo el rango`;
      return;
    }

    const arr = getSelectedNumsArray(talId);
    if (!arr.length) {
      pill.classList.add('d-none');
      mini.textContent = '';
      return;
    }

    const show = arr.slice(0, 8);
    const extra = arr.length - show.length;

    pill.classList.remove('d-none');
    selLabel.innerHTML = `Seleccionados: <strong>${arr.length}</strong>`;
    mini.textContent = `${show.join(', ')}${extra > 0 ? `  +${extra}` : ''}`;
  }

  function lockNumsBox(talId, locked){
    const box = document.getElementById(`nums-${talId}`);
    if (!box) return;
    const content = box.querySelector('.nums-content');
    if (!content) return;

    if (locked) {
      content.querySelectorAll('.num-item').forEach(x => x.classList.add('disabled'));
      content.querySelectorAll('input[type="checkbox"]').forEach(ch => ch.disabled = true);
      const clear = box.querySelector('button.btn-clear-tal');
      if (clear) clear.disabled = true;
    } else {
      content.querySelectorAll('.num-item').forEach(x => x.classList.remove('disabled'));
      content.querySelectorAll('input[type="checkbox"]').forEach(ch => ch.disabled = false);
      const clear = box.querySelector('button.btn-clear-tal');
      if (clear) clear.disabled = false;
    }
  }

  async function loadNums(talId){
    const box = document.getElementById(`nums-${talId}`);
    if (!box) return;

    const url = new URL(`{{ url('/ventas/liquidaciones/talonarios') }}/${talId}/numeros`, window.location.origin);
    url.searchParams.set('per_page', '5000');

    const content = box.querySelector('.nums-content');
    content.innerHTML = `<div class="alert alert-light border mb-0">Cargando…</div>`;

    try{
      const res = await fetch(url.toString(), { headers: { 'Accept': 'application/json' }});
      if (!res.ok) throw new Error('HTTP ' + res.status);
      const json = await res.json();

      const items = json.data || [];
      const readonly = !!json.readonly;
      const estadoTal = String(json.estado || '').toUpperCase();

      const title = box.querySelector('.fw-semibold');
      if (title) title.textContent = readonly ? 'Números del talonario (LIQUIDADO)' : 'Números pendientes (DISPONIBLES)';

      const clearBtn = box.querySelector('.btn-clear-tal');
      if (clearBtn) clearBtn.disabled = readonly;

      if (!items.length){
        content.innerHTML = readonly
          ? `<div class="alert alert-success mb-0">🎉 Este talonario está LIQUIDADO por completo.</div>`
          : `<div class="alert alert-success mb-0">🎉 No hay pendientes: este talonario ya está completo.</div>`;
        lockNumsBox(talId, true);
        return;
      }

      if (!readonly && !selectedN[talId]) selectedN[talId] = new Set();

      content.innerHTML = `
        <div class="num-grid">
          ${items.map(x => {
            const num = x.numero ?? x;
            const isChecked = readonly ? true : (selectedN[talId]?.has(String(num)) ?? false);
            return `
              <div class="num-item ${isChecked ? 'selected' : ''} ${readonly ? 'disabled' : ''}" data-tal="${talId}" data-num="${num}">
                <input type="checkbox" data-tal="${talId}" data-num="${num}"
                       ${isChecked ? 'checked' : ''} ${readonly ? 'disabled' : ''}>
                <span class="num-text">${num}</span>
              </div>
            `;
          }).join('')}
        </div>
      `;

      if (readonly) {
        // Solo lectura, no se puede seleccionar nada
        lockNumsBox(talId, true);
        updateSelInfo(talId);
        syncHidden();
        return;
      }

      function setChipState(ch){
        const t = Number(ch.getAttribute('data-tal'));
        const n = String(ch.getAttribute('data-num'));
        const chip = ch.closest('.num-item');

        if (selectedT.has(t)) return;

        if (ch.checked) {
          if (!selectedN[t]) selectedN[t] = new Set();
          selectedN[t].add(n);
          chip?.classList.add('selected');
        } else {
          selectedN[t]?.delete(n);
          chip?.classList.remove('selected');
          if (selectedN[t] && selectedN[t].size === 0) delete selectedN[t];
        }

        updateSelInfo(t);
        syncHidden();
      }

      content.querySelectorAll('.num-item[data-num]').forEach(item => {
        item.addEventListener('click', (e) => {
          e.stopPropagation();
          if (item.classList.contains('disabled')) return;
          const ch = item.querySelector('input[type="checkbox"]');
          if (!ch || ch.disabled) return;

          if (e.target?.tagName?.toLowerCase() !== 'input') {
            ch.checked = !ch.checked;
            setChipState(ch);
          }
        });
      });

      content.querySelectorAll('input[type="checkbox"][data-num]').forEach(ch => {
        ch.addEventListener('click', (e) => e.stopPropagation());
        ch.addEventListener('change', () => setChipState(ch));
      });

      lockNumsBox(talId, selectedT.has(talId));
    }catch(err){
      content.innerHTML = `<div class="alert alert-danger mb-0">No se pudieron cargar los números. (Error: ${String(err)})</div>`;
    }
  }

  function renderTalonarios(){
    if (!talonariosWrap) return;

    if (!talonarios.length){
      talonariosWrap.innerHTML = `<div class="alert alert-warning mb-0">No hay talonarios para esta estación.</div>`;
      return;
    }

    talonariosWrap.innerHTML = talonarios.map(t => {
      const id = Number(t.id);
      const estado = String(t.estado || '').toUpperCase();
      const isLiquidado = (estado === 'LIQUIDADO');
      const checked = selectedT.has(id) ? 'checked' : '';

      const open = openTal.has(id);

      return `
        <div class="tal-row" data-tal="${id}">
          <div class="tal-left">
            <input type="checkbox"
                   class="form-check-input mt-1 tal-check"
                   data-id="${id}"
                   ${isLiquidado ? 'disabled checked' : checked}>

            <div style="width:100%;">
              <div class="d-flex justify-content-between align-items-start">
                <div>
                  <div class="tal-title">${t.numero_talonario}</div>
                  <div class="tal-meta">
                    Rango: ${t.numero_inicio}-${t.numero_fin} ·
                    Q ${moneyQ(t.valor_talonario)} / talonario ·
                    Q ${moneyQ(t.valor_numero)} / número
                    ${isLiquidado ? ' · <b class="text-success">LIQUIDADO</b>' : ''}
                  </div>
                </div>
                <div class="text-end">
                  <div class="badge ${isLiquidado ? 'text-bg-success' : 'text-bg-secondary'}">${t.estado}</div>
                </div>
              </div>

              <div class="d-flex align-items-center gap-2 mt-2">
                <span class="pill d-none" id="pill-${id}">
                  <span id="selLabel-${id}">Seleccionados: <strong>0</strong></span>
                </span>
                <span class="mini-list" id="mini-${id}"></span>
                <span class="muted ms-auto">${open ? 'Click para ocultar' : 'Click para ver números'}</span>
              </div>

              <div class="nums-box ${open ? '' : 'd-none'}" id="nums-${id}">
                <div class="d-flex justify-content-between align-items-center mb-2">
                  <div class="fw-semibold">${isLiquidado ? 'Números del talonario (LIQUIDADO)' : 'Números pendientes (DISPONIBLES)'}</div>
                  <div class="d-flex gap-2">
                    <button type="button" class="btn btn-outline-danger btn-sm btn-clear-tal" data-id="${id}" ${isLiquidado ? 'disabled' : ''}>
                      Limpiar #${t.numero_talonario}
                    </button>
                  </div>
                </div>

                <div class="nums-content">
                  <div class="alert alert-light border mb-0">Cargando…</div>
                </div>
              </div>
            </div>
          </div>
        </div>
      `;
    }).join('');

    talonariosWrap.querySelectorAll('.tal-check').forEach(ch => {
      ch.addEventListener('click', (e) => e.stopPropagation());
      ch.addEventListener('change', (e) => {
        const id = Number(e.target.getAttribute('data-id'));
        if (e.target.disabled) return;

        if (e.target.checked) {
          selectedT.add(id);
          delete selectedN[id];
          lockNumsBox(id, true);
        } else {
          selectedT.delete(id);
          lockNumsBox(id, false);
        }

        updateSelInfo(id);
        syncHidden();
      });
    });

    talonariosWrap.querySelectorAll('.tal-row').forEach(row => {
      row.addEventListener('click', async (e) => {
        const talId = Number(row.getAttribute('data-tal'));
        const tag = (e.target.tagName || '').toLowerCase();
        if (tag === 'input' || tag === 'button' || e.target.closest('button')) return;

        if (openTal.has(talId)) openTal.delete(talId);
        else openTal.add(talId);

        renderTalonarios();

        if (openTal.has(talId)) {
          await loadNums(talId);
          updateSelInfo(talId);
        }
      });
    });

    talonariosWrap.querySelectorAll('.nums-box').forEach(box => {
      box.addEventListener('click', (e) => e.stopPropagation());
    });

    talonariosWrap.querySelectorAll('.btn-clear-tal').forEach(btn => {
      btn.addEventListener('click', async (e) => {
        e.stopPropagation();
        const id = Number(btn.getAttribute('data-id'));
        if (btn.disabled) return;

        delete selectedN[id];
        updateSelInfo(id);
        syncHidden();

        if (openTal.has(id)) {
          await loadNums(id);
          updateSelInfo(id);
        }
      });
    });

    talonarios.forEach(t => updateSelInfo(Number(t.id)));
    syncHidden();
  }

  async function fetchTalonarios(){
    if (!talonariosWrap) return;
    talonariosWrap.innerHTML = `<div class="alert alert-light border mb-0">Cargando talonarios...</div>`;

    try{
      const url = new URL(
        "{{ route('ventas.estaciones.talonarios_disponibles', ['id_estacion' => 0]) }}".replace('/0', `/${estacionId}`),
        window.location.origin
      );

      const res = await fetch(url.toString(), { headers: { 'Accept': 'application/json' }});
      if (!res.ok) throw new Error('HTTP ' + res.status);

      const json = await res.json();

      talonarios = (json.data || []).map(t => ({
        id: Number(t.id),
        numero_talonario: t.numero_talonario,
        numero_inicio: t.numero_inicio,
        numero_fin: t.numero_fin,
        cantidad_numeros: t.cantidad_numeros,
        valor_talonario: Number(t.valor_talonario || 0),
        valor_numero: Number(t.valor_numero || 0),
        estado: t.estado,
      }));

      selectedT = new Set();
      selectedN = {};
      openTal = new Set();
      donarExcedente.value = '0';

      renderTalonarios();
    }catch(err){
      talonariosWrap.innerHTML = `
        <div class="alert alert-danger mb-0">
          <b>No se pudieron cargar los talonarios.</b><br>
          <span class="muted">Detalle: ${String(err)}</span>
        </div>
      `;
      await niceAlert('Error cargando talonarios', `
        <div class="alert alert-danger mb-0">
          No se pudieron cargar los talonarios de esta estación.<br>
          <div class="mt-2"><b>Detalle:</b> ${String(err)}</div>
        </div>
      `);
    }
  }

  btnClearAll?.addEventListener('click', () => {
    selectedT = new Set();
    selectedN = {};
    donarExcedente.value = '0';
    syncHidden();
    renderTalonarios();
  });

  btnReload?.addEventListener('click', () => fetchTalonarios());

  // ===== Validación submit =====
  document.getElementById('formLiquidacion')?.addEventListener('submit', async (e) => {
    const t = JSON.parse(talonariosJson.value || '[]');
    const n = JSON.parse(numerosJson.value || '{}');
    const hasNums = Object.keys(n).some(k => Array.isArray(n[k]) && n[k].length);

    if (!t.length && !hasNums) {
      e.preventDefault();
      await niceAlert('Falta selección', `
        <div class="alert alert-warning mb-0">
          Selecciona al menos <b>1 talonario</b> (completo) o <b>1 número</b>.
        </div>
      `);
      return;
    }

    // Boletas únicas por (banco + número)
    if (!validateUniqueBoletasFront()) {
      e.preventDefault();
      await niceAlert('Boletas duplicadas', `
        <div class="alert alert-danger mb-0">
          Tienes boletas repetidas con el mismo <b>banco</b> y <b>número de boleta</b>.<br>
          Si el número se repite, debe ser en <b>otro banco</b>.
        </div>
      `);
      return;
    }

    const { totalCalc, totalBol, diff } = getTotals();

    if (totalCalc <= 0) {
      e.preventDefault();
      await niceAlert('Total inválido', `<div class="alert alert-danger mb-0">No se pudo calcular el total.</div>`);
      return;
    }

    if (diff < 0) {
      e.preventDefault();
      await niceAlert('Monto insuficiente', `
        <div class="alert alert-danger mb-0">
          <div><b>Total calculado:</b> Q ${totalCalc.toFixed(2)}</div>
          <div><b>Total boletas:</b> Q ${totalBol.toFixed(2)}</div>
          <hr>
          <div><b>Faltan:</b> Q ${Math.abs(diff).toFixed(2)}</div>
        </div>
      `);
      return;
    }

    if (diff === 0) {
      donarExcedente.value = '0';
      return;
    }

    e.preventDefault();

    const ok = await niceConfirm('Hay excedente', `
      <div class="alert alert-warning mb-0">
        <div><b>Total calculado:</b> Q ${totalCalc.toFixed(2)}</div>
        <div><b>Total boletas:</b> Q ${totalBol.toFixed(2)}</div>
        <hr>
        <div><b>Excedente:</b> Q ${diff.toFixed(2)}</div>
        <div class="mt-2">¿Deseas <b>DONAR</b> el excedente?</div>
      </div>
    `, 'Sí, donar', 'No');

    if (ok) {
      donarExcedente.value = '1';
      document.getElementById('formLiquidacion').submit();
      return;
    }

    await niceAlert('Acción requerida', `
      <div class="alert alert-info mb-0">
        Si no deseas donar el excedente, por favor comunícate con tu <b>asesor de ventas</b>.
      </div>
    `);
  });

  // ✅ Arranque
  if (estacionId > 0) fetchTalonarios();

})();
</script>

@endsection

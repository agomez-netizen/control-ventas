@extends('layouts.app')

@section('title', 'Nueva Liquidación')

@section('content')
<div class="container">

  <div class="d-flex justify-content-between align-items-center mb-3">
    <h3 class="mb-0">Nueva Liquidación</h3>
    <a class="btn btn-outline-secondary" href="{{ route('ventas.liquidaciones.index') }}">Volver</a>
  </div>

  @if(session('error'))
    <div class="alert alert-danger">{{ session('error') }}</div>
  @endif
  @if(session('ok'))
    <div class="alert alert-success">{{ session('ok') }}</div>
  @endif

  {{-- Selector estación (AJAX) --}}
  <div class="card p-3 mb-3">
    <div class="row g-3 align-items-end">
      <div class="col-md-6">
        <label class="form-label">Estación</label>
        <select id="selEstacion" class="form-select">
          <option value="">Selecciona una estación</option>
          @foreach($estaciones as $e)
            <option value="{{ $e->id_estacion }}" @selected((string)$idEstacion === (string)$e->id_estacion)>
              {{ $e->estacion }}
            </option>
          @endforeach
        </select>
      </div>
      <div class="col-md-6 text-muted">
        <div class="small">Selecciona estación y luego marca talonarios.</div>
      </div>
    </div>
  </div>

  {{-- Info estación/operador --}}
  <div class="card p-3 mb-3" id="cardInfoEstacion" style="display:none;">
    <div class="d-flex justify-content-between align-items-start gap-2">
      <div>
        <div class="text-muted small">Estación</div>
        <div class="h5 mb-1" id="infoEstacionNombre">—</div>

        <div class="text-muted small mt-2">Operador</div>
        <div class="mb-1"><strong id="infoOperador">—</strong></div>

        <div class="text-muted small mt-2">Contacto</div>
        <div class="small">
          Tel estación: <span id="infoTelEstacion">—</span> ·
          Email: <span id="infoEmailEstacion">—</span>
        </div>
        <div class="small">
          Tel operador: <span id="infoTelOperador">—</span>
        </div>

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

  {{-- FORM PRINCIPAL --}}
  <form method="POST" action="{{ route('ventas.liquidaciones.store') }}" enctype="multipart/form-data" id="formLiquidacion">
    @csrf
    <input type="hidden" name="id_estacion" id="id_estacion" value="{{ $idEstacion }}">

    <div class="row g-3">
      {{-- IZQ: TALONARIOS --}}
      <div class="col-lg-6">
        <div class="card p-3 h-100">
          <div class="d-flex justify-content-between align-items-center mb-2">
            <h5 class="mb-0">Seleccionar Talonarios</h5>
            <span class="badge bg-light text-dark">Disponibles (ASIGNADO)</span>
          </div>

          <div class="table-responsive">
            <table class="table table-sm align-middle">
              <thead class="table-light">
                <tr>
                  <th style="width:40px;"></th>
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

          <div class="d-flex justify-content-between mt-2">
            <div class="text-muted">
              <div><strong id="txtCantidad">0</strong> talonarios seleccionados</div>
              <div class="small">(Q550 por talonario)</div>
            </div>
            <div class="fs-4">
              Total Calculado: <strong>Q <span id="totalCalculado">0.00</span></strong>
            </div>
          </div>
        </div>
      </div>

      {{-- DER: BOLETAS --}}
      <div class="col-lg-6">
        <div class="card p-3">
          <div class="d-flex justify-content-between align-items-center mb-2">
            <h5 class="mb-0">Datos de la Liquidación</h5>
            <button type="button" class="btn btn-sm btn-primary" id="btnAddBoleta">+ Agregar boleta</button>
          </div>

          <div class="text-muted mb-3">
            Ingresa una o varias boletas. El total debe coincidir con el monto calculado.
          </div>

          <div id="boletasWrap"></div>

          <div class="d-flex justify-content-between align-items-center mt-3">
            <div class="fs-5">
              Total Boletas: <strong>Q <span id="totalBoletas">0.00</span></strong>
            </div>
            <div id="msgValidacion" class="small"></div>
          </div>

          <div class="mt-3">
            <label class="form-label">Observación (opcional)</label>
            <input class="form-control" name="observacion" value="{{ old('observacion') }}">
          </div>

          <input type="hidden" name="donar_excedente" id="donar_excedente" value="0">

          <div class="mt-4 d-flex gap-2">
            <button class="btn btn-success w-100" type="submit" id="btnGuardar">Guardar Liquidación</button>
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

  const boletasWrap = document.getElementById('boletasWrap');

  const totalCalculadoEl = document.getElementById('totalCalculado');
  const totalBoletasEl   = document.getElementById('totalBoletas');
  const txtCantidadEl    = document.getElementById('txtCantidad');
  const msgEl            = document.getElementById('msgValidacion');
  const donarInput       = document.getElementById('donar_excedente');

  const selEstacion = document.getElementById('selEstacion');
  const hiddenEst = document.getElementById('id_estacion');
  const tbody = document.getElementById('tbodyTalonarios');

  // info estación
  const cardInfo = document.getElementById('cardInfoEstacion');
  const infoNombre = document.getElementById('infoEstacionNombre');
  const infoOperador = document.getElementById('infoOperador');
  const infoTelEst = document.getElementById('infoTelEstacion');
  const infoEmailEst = document.getElementById('infoEmailEstacion');
  const infoTelOp = document.getElementById('infoTelOperador');
  const infoUbic = document.getElementById('infoUbicacion');
  const btnVerTal = document.getElementById('btnVerTalonariosEstacion');

  function money(n){ return (Math.round(n * 100) / 100).toFixed(2); }

  function calcTotales(){
    const chks = document.querySelectorAll('.chk-talonario:checked');
    let totalCalc = 0;
    chks.forEach(c => totalCalc += parseFloat(c.dataset.valor || '550'));
    totalCalculadoEl.textContent = money(totalCalc);
    txtCantidadEl.textContent = chks.length;

    let totalBol = 0;
    document.querySelectorAll('.inp-monto').forEach(i => {
      const v = parseFloat(i.value || '0');
      if(!isNaN(v)) totalBol += v;
    });
    totalBoletasEl.textContent = money(totalBol);

    msgEl.className = 'small';
    donarInput.value = '0';

    if(totalCalc === 0){
      msgEl.textContent = 'Selecciona talonarios.';
      msgEl.classList.add('text-muted');
      return;
    }
    if(totalBol === 0){
      msgEl.textContent = 'Ingresa monto(s) de boleta.';
      msgEl.classList.add('text-muted');
      return;
    }
    if (Math.abs(totalBol - totalCalc) < 0.01) {
      msgEl.textContent = 'Montos coinciden. Puedes guardar.';
      msgEl.classList.add('text-success');
    } else if (totalBol < totalCalc) {
      msgEl.textContent = 'Monto insuficiente. No se podrá guardar.';
      msgEl.classList.add('text-danger');
    } else {
      msgEl.textContent = 'Monto mayor. Al guardar te pedirá donar excedente.';
      msgEl.classList.add('text-warning');
    }
  }

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
          <select name="boletas[${index}][id_banco]" class="form-select">
            ${banksOptions}
          </select>
        </div>
        <div class="col-md-4">
          <label class="form-label">Tipo</label>
          <select name="boletas[${index}][tipo_pago]" class="form-select">
            <option value="DEPOSITO">Depósito</option>
            <option value="TRANSFERENCIA">Transferencia</option>
          </select>
        </div>
        <div class="col-md-4">
          <label class="form-label">Fecha</label>
          <input type="date" name="boletas[${index}][fecha_boleta]" class="form-control" value="">
        </div>

        <div class="col-md-6">
          <label class="form-label">Número de boleta</label>
          <input name="boletas[${index}][numero_boleta]" class="form-control" placeholder="Ej: 123456">
        </div>
        <div class="col-md-3">
          <label class="form-label">Monto</label>
          <input name="boletas[${index}][monto]" class="form-control inp-monto" inputmode="decimal" placeholder="0.00">
        </div>
        <div class="col-md-3">
          <label class="form-label">Archivo</label>
          <input type="file" name="boletas_archivo_${index}" class="form-control">
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

  async function cargarTalonarios(idEst){
    cardInfo.style.display = 'none';
    tbody.innerHTML = `<tr><td colspan="4" class="text-center text-muted py-3">Cargando...</td></tr>`;

    if(!idEst){
      tbody.innerHTML = `<tr><td colspan="4" class="text-center text-muted py-3">Selecciona una estación.</td></tr>`;
      calcTotales();
      return;
    }

    const url = `{{ url('/ventas/estaciones') }}/${idEst}/talonarios-disponibles`;
    const res = await fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' }});

    if(!res.ok){
      tbody.innerHTML = `<tr><td colspan="4" class="text-center text-danger py-3">Error al cargar talonarios.</td></tr>`;
      return;
    }

    const data = await res.json();

    // info estación
    if(data.estacion){
      const e = data.estacion;
      cardInfo.style.display = '';
      infoNombre.textContent = e.nombre ?? '—';
      infoOperador.textContent = e.operador ?? '—';
      infoTelEst.textContent = e.telefono ?? '—';
      infoEmailEst.textContent = e.correo ?? '—';
      infoTelOp.textContent = e.telefono_operador ?? '—';

      const ubic = `${e.pais ?? ''} · ${e.departamento ?? ''} · ${e.municipio ?? ''} · ${e.direccion ?? ''}`.replace(/\s+/g,' ').trim();
      infoUbic.textContent = ubic || '—';

      // link a talonarios de estación
      btnVerTal.href = `{{ url('/ventas/talonarios/estacion') }}/${e.id_estacion}`;
    }

    const list = data.talonarios || [];
    if(list.length === 0){
      tbody.innerHTML = `<tr><td colspan="4" class="text-center text-muted py-3">No hay talonarios disponibles.</td></tr>`;
      calcTotales();
      return;
    }

    tbody.innerHTML = list.map(t => `
      <tr>
        <td>
          <input type="checkbox"
                 class="form-check-input chk-talonario"
                 name="talonarios[]"
                 value="${t.id_talonario}"
                 data-valor="${t.valor_talonario ?? 550}">
        </td>
        <td><strong>${t.numero_talonario}</strong></td>
        <td>${t.numero_inicio} - ${t.numero_fin}</td>
        <td class="text-end">Q ${money(parseFloat(t.valor_talonario ?? 550))}</td>
      </tr>
    `).join('');

    document.querySelectorAll('.chk-talonario').forEach(c => {
      c.addEventListener('change', calcTotales);
    });

    calcTotales();
  }

  document.getElementById('btnAddBoleta')?.addEventListener('click', () => {
    addBoleta();
    calcTotales();
  });

  document.getElementById('formLiquidacion').addEventListener('submit', function(e){
    const totalCalc = parseFloat(totalCalculadoEl.textContent || '0');
    const totalBol  = parseFloat(totalBoletasEl.textContent || '0');

    if(!hiddenEst.value){
      e.preventDefault();
      alert('Selecciona una estación.');
      return;
    }
    if(totalCalc <= 0){
      e.preventDefault();
      alert('Selecciona al menos 1 talonario.');
      return;
    }
    if(totalBol <= 0){
      e.preventDefault();
      alert('Agrega al menos 1 boleta con monto.');
      return;
    }
    if(totalBol < totalCalc - 0.01){
      e.preventDefault();
      alert('El monto no equivale a la cantidad de talonarios seleccionados.');
      return;
    }
    if(totalBol > totalCalc + 0.01){
      const ok = confirm('El monto en boleta es mayor. ¿Deseas donar el excedente a nuestros proyectos?');
      if(!ok){
        e.preventDefault();
        alert('No se guardó. Comuníquese con su asesor de ventas.');
        return;
      }
      donarInput.value = '1';
    }
  });

  selEstacion?.addEventListener('change', () => {
    const id = selEstacion.value;
    hiddenEst.value = id;
    cargarTalonarios(id);
  });

  // init
  addBoleta();
  calcTotales();

  if(hiddenEst.value){
    selEstacion.value = hiddenEst.value;
    cargarTalonarios(hiddenEst.value);
  }
})();
</script>
@endpush

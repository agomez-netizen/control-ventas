@extends('layouts.app')

@section('content')
<div class="container py-4">

  <style>
    .card-soft{ border:1px solid #e5e7eb; border-radius:14px; background:#fff; }
    .section-title{ font-weight:800; letter-spacing:.3px; }
    .muted{ color:#6b7280; }
    .req{ color:#dc2626; font-weight:800; }
    .grid-2{ display:grid; grid-template-columns:1fr 1fr; gap:16px; }
    @media (max-width: 992px){ .grid-2{ grid-template-columns:1fr; } }
  </style>

  <div class="d-flex align-items-center justify-content-between mb-3">
    <div>
      <h3 class="mb-0 section-title">Registro</h3>
      <div class="muted">Nuevos Ingresos</div>
    </div>
  </div>

  @if(session('ok'))
    <div class="alert alert-success">{{ session('ok') }}</div>
  @endif
  @if(session('error'))
    <div class="alert alert-warning">{{ session('error') }}</div>
  @endif
  @if($errors->any())
    <div class="alert alert-danger">
      <div class="fw-bold mb-1">Revisa esto:</div>
      <ul class="mb-0">
        @foreach($errors->all() as $e) <li>{{ $e }}</li> @endforeach
      </ul>
    </div>
  @endif

  <div class="grid-2">

    {{-- ===================== USUARIOS ===================== --}}
    <div class="card card-soft">
      <div class="card-body">
        <h5 class="fw-bold mb-1">Registrar Usuario</h5>
        <div class="muted mb-3">TM / Operador / Administrador de Operador</div>

        <form method="POST" action="{{ route('registro.usuarios.store') }}">
          @csrf

          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label">Nombre <span class="req">*</span></label>
              <input type="text" name="nombre" class="form-control" value="{{ old('nombre') }}" required>
            </div>
            <div class="col-md-6">
              <label class="form-label">Apellido <span class="req">*</span></label>
              <input type="text" name="apellido" class="form-control" value="{{ old('apellido') }}" required>
            </div>

            <div class="col-md-6">
              <label class="form-label">Teléfono</label>
              <input type="text" name="telefono" class="form-control" value="{{ old('telefono') }}">
            </div>

            <div class="col-md-6">
              <label class="form-label">Usuario (login) <span class="req">*</span></label>
              <input type="text" name="usuario" class="form-control" value="{{ old('usuario') }}" required>
            </div>

            <div class="col-md-6">
              <label class="form-label">Contraseña <span class="req">*</span></label>
              <input type="password" name="pass" class="form-control" required>
              <div class="form-text muted">Password de 7 caracteres</div>
            </div>

            <div class="col-md-6">
              <label class="form-label">Rol <span class="req">*</span></label>
              <select name="id_rol" id="id_rol" class="form-select" required>
                <option value="">-- Seleccionar --</option>
                @foreach($roles as $r)
                  <option value="{{ $r->id_rol }}" {{ old('id_rol') == $r->id_rol ? 'selected' : '' }}>
                    {{ $r->nombre }}
                  </option>
                @endforeach
              </select>
            </div>

            {{-- NUEVO: PL --}}
            <div class="col-md-6">
              <label class="form-label">PL <span class="req">*</span></label>
              <select name="pl" class="form-select" required>
                <option value="">-- Seleccionar --</option>
                <option value="RBA" {{ old('pl','RBA')=='RBA' ? 'selected' : '' }}>RBA</option>
                <option value="DEALER" {{ old('pl')=='DEALER' ? 'selected' : '' }}>DEALER</option>
              </select>
            </div>

            <div class="col-md-6" id="wrap_tm" style="display:none;">
              <label class="form-label">TM (para Operador) <span class="req">*</span></label>
              <select name="id_tm" id="id_tm" class="form-select">
                <option value="">-- Seleccionar TM --</option>
                @foreach($tms as $tm)
                  <option value="{{ $tm->id_usuario }}" {{ old('id_tm') == $tm->id_usuario ? 'selected' : '' }}>
                    {{ $tm->label }}
                  </option>
                @endforeach
              </select>
            </div>

            <div class="col-md-6" id="wrap_operador" style="display:none;">
              <label class="form-label">Operador (para Admin Operador) <span class="req">*</span></label>
              <select name="id_operador" id="id_operador" class="form-select">
                <option value="">-- Seleccionar Operador --</option>
                @foreach($operadores as $op)
                  <option value="{{ $op->id_usuario }}" {{ old('id_operador') == $op->id_usuario ? 'selected' : '' }}>
                    {{ $op->label }}
                  </option>
                @endforeach
              </select>
            </div>

            <div class="col-md-6">
              <label class="form-label">Estado <span class="req">*</span></label>
              <select name="estado" class="form-select" required>
                <option value="1" {{ old('estado','1')=='1' ? 'selected' : '' }}>Activo</option>
                <option value="0" {{ old('estado')=='0' ? 'selected' : '' }}>Inactivo</option>
              </select>
            </div>

            <div class="col-12 d-flex justify-content-end gap-2 mt-2">
              <button class="btn btn-primary">
                <i class="bi bi-person-plus"></i> Guardar usuario
              </button>
            </div>
          </div>
        </form>
      </div>
    </div>

    {{-- ===================== ESTACIONES ===================== --}}
    <div class="card card-soft">
      <div class="card-body">
        <h5 class="fw-bold mb-1">Registrar Estación</h5>
        <div class="muted mb-3">Se asigna a un Operador. No se permite duplicar por nombre.</div>

        <form method="POST" action="{{ route('registro.estaciones.store') }}">
          @csrf

          <div class="row g-3">
            <div class="col-md-12">
              <label class="form-label">Operador <span class="req">*</span></label>
              <select name="id_operador" class="form-select" required>
                <option value="">-- Seleccionar Operador --</option>
                @foreach($operadores as $op)
                  <option value="{{ $op->id_usuario }}" {{ old('id_operador') == $op->id_usuario ? 'selected' : '' }}>
                    {{ $op->label }}
                  </option>
                @endforeach
              </select>
            </div>

            <div class="col-md-12">
              <label class="form-label">Nombre Estación <span class="req">*</span></label>
              <input type="text" name="nombre" class="form-control" value="{{ old('nombre') }}" required>
            </div>

            <div class="col-md-6">
              <label class="form-label">Teléfono</label>
              <input type="text" name="telefono" class="form-control" value="{{ old('telefono') }}">
            </div>

            <div class="col-md-6">
              <label class="form-label">Correo</label>
              <input type="email" name="correo" class="form-control" value="{{ old('correo') }}">
            </div>

            <div class="col-md-4">
              <label class="form-label">País</label>
              <input type="text" name="pais" class="form-control" value="{{ old('pais','Guatemala') }}" required>
            </div>

            <div class="col-md-4">
              <label class="form-label">Departamento</label>
              <input type="text" name="departamento" class="form-control" value="{{ old('departamento') }}" required>
            </div>

            <div class="col-md-4">
              <label class="form-label">Municipio</label>
              <input type="text" name="municipio" class="form-control" value="{{ old('municipio') }}" required>
            </div>

            <div class="col-md-12">
              <label class="form-label">Dirección</label>
              <textarea name="direccion" class="form-control" rows="2" required>{{ old('direccion') }}</textarea>
            </div>

            <div class="col-md-6">
              <label class="form-label">Coordenada 1</label>
              <input type="number" step="0.000001" name="coordenada_1" class="form-control" value="{{ old('coordenada_1') }}">
            </div>

            <div class="col-md-6">
              <label class="form-label">Coordenada 2</label>
              <input type="number" step="0.000001" name="coordenada_2" class="form-control" value="{{ old('coordenada_2') }}">
            </div>

            <div class="col-md-6">
              <label class="form-label">Activa <span class="req">*</span></label>
              <select name="activa" class="form-select" required>
                <option value="1" {{ old('activa','1')=='1' ? 'selected' : '' }}>Sí</option>
                <option value="0" {{ old('activa')=='0' ? 'selected' : '' }}>No</option>
              </select>
            </div>

            <div class="col-12 d-flex justify-content-end gap-2 mt-2">
              <button class="btn btn-success">
                <i class="bi bi-building-add"></i> Guardar estación
              </button>
            </div>
          </div>
        </form>

      </div>
    </div>

  </div>
</div>

<script>
(function(){
  const rolSelect = document.getElementById('id_rol');
  const wrapTM = document.getElementById('wrap_tm');
  const wrapOP = document.getElementById('wrap_operador');
  const selTM = document.getElementById('id_tm');
  const selOP = document.getElementById('id_operador');

  function normalize(s){ return (s || '').toUpperCase().trim(); }

  function refresh() {
    const opt = rolSelect.options[rolSelect.selectedIndex];
    const roleText = normalize(opt ? opt.text : '');

    const isOperador = (roleText === 'OP' || roleText === 'OPERADOR' || roleText === 'OPERADORES');
    const isAdmOp = (roleText === 'ADMOP' || (roleText.includes('ADMIN') && roleText.includes('OPERADOR')));

    wrapTM.style.display = isOperador ? '' : 'none';
    selTM.required = !!isOperador;
    if (!isOperador) selTM.value = '';

    wrapOP.style.display = isAdmOp ? '' : 'none';
    selOP.required = !!isAdmOp;
    if (!isAdmOp) selOP.value = '';
  }

  rolSelect.addEventListener('change', refresh);
  refresh();
})();
</script>
@endsection

@extends('layouts.admin')

@section('title', $formula ? 'Editar fórmula' : 'Nueva fórmula')

@section('content')
<x-page-header
    :title="$formula ? 'Editar fórmula' : 'Nueva fórmula'"
    subtitle="Define la plantilla una vez y reutilízala con cualquier variable"
    icon="fa-solid fa-function" />

<div class="container py-4">
    @if($errors->any())
        <div class="alert alert-danger d-flex align-items-start gap-2">
            <i class="fa-solid fa-circle-exclamation mt-1"></i>
            <div>
                @foreach($errors->all() as $error)
                    <div>{{ $error }}</div>
                @endforeach
            </div>
        </div>
    @endif

    <form method="POST" action="{{ $formula ? route('admin.formulas.update', $formula) : route('admin.formulas.store') }}"
        id="formulaForm">
        @csrf
        @if($formula)
            @method('PUT')
        @endif

        <div class="row g-4">
            <div class="col-lg-7">
                <div class="card-panel h-100">
                    <div class="card-body p-4">
                        <h5 class="fw-bold text-dark mb-3">Definición</h5>

                        <div class="mb-3">
                            <label for="nombre" class="form-label fw-bold text-secondary small">Nombre</label>
                            <input type="text" name="nombre" id="nombre" class="form-control" required
                                value="{{ old('nombre', $formula->nombre ?? '') }}"
                                placeholder="Porcentaje estatal de viviendas con drenaje">
                        </div>

                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label for="ambito" class="form-label fw-bold text-secondary small">Alcance del resultado</label>
                                <select name="ambito" id="ambito" class="form-select">
                                    @foreach([
                                        'estatal' => 'Estatal (Puebla)',
                                        'municipal' => 'Municipal',
                                        'regional' => 'Regional',
                                    ] as $valor => $etiqueta)
                                        <option value="{{ $valor }}" @selected(old('ambito', $formula->ambito ?? 'estatal') === $valor)>
                                            {{ $etiqueta }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6 d-flex align-items-end pb-2">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" name="activa" value="1" id="activa"
                                        @checked(old('activa', $formula->activa ?? true))>
                                    <label class="form-check-label small fw-semibold" for="activa">Fórmula activa</label>
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="descripcion" class="form-label fw-bold text-secondary small">Descripción</label>
                            <textarea name="descripcion" id="descripcion" rows="2" class="form-control"
                                placeholder="Explica en pocas palabras qué calcula esta fórmula">{{ old('descripcion', $formula->descripcion ?? '') }}</textarea>
                        </div>

                        <div class="mb-3">
                            <span class="form-label fw-bold text-secondary small d-block">Expresión</span>

                            <div class="border rounded-3 p-3 bg-light">
                                <div id="expressionCanvas" class="formula-canvas d-flex flex-wrap align-items-center gap-2 mb-2"></div>

                                <div class="d-flex flex-wrap gap-1 align-items-center pt-2 border-top">
                                    <span class="small fw-bold text-secondary me-1">Agregación</span>
                                    <button type="button" class="btn btn-sm btn-outline-primary formula-agg" data-fn="SUM">Suma</button>
                                    <button type="button" class="btn btn-sm btn-outline-primary formula-agg" data-fn="AVG">Promedio</button>
                                    <button type="button" class="btn btn-sm btn-outline-primary formula-agg" data-fn="COUNT">Conteo</button>
                                    <button type="button" class="btn btn-sm btn-outline-primary formula-agg" data-fn="MIN">Mínimo</button>
                                    <button type="button" class="btn btn-sm btn-outline-primary formula-agg" data-fn="MAX">Máximo</button>

                                    <span class="vr mx-1"></span>

                                    <span class="small fw-bold text-secondary me-1">Operadores</span>
                                    <button type="button" class="btn btn-sm btn-outline-secondary formula-op" data-token=" + ">+</button>
                                    <button type="button" class="btn btn-sm btn-outline-secondary formula-op" data-token=" - ">−</button>
                                    <button type="button" class="btn btn-sm btn-outline-secondary formula-op" data-token=" * ">×</button>
                                    <button type="button" class="btn btn-sm btn-outline-secondary formula-op" data-token=" / ">÷</button>

                                    <span class="vr mx-1"></span>

                                    <span class="small fw-bold text-secondary me-1">Variables</span>
                                    <span id="paletteVariables" class="d-inline-flex gap-1"></span>

                                    <span class="vr mx-1"></span>

                                    <button type="button" class="btn btn-sm btn-outline-secondary formula-op" data-token="(">(</button>
                                    <button type="button" class="btn btn-sm btn-outline-secondary formula-op" data-token=")">)</button>
                                    <button type="button" class="btn btn-sm btn-outline-secondary formula-op" data-token="100">100</button>
                                    <button type="button" class="btn btn-sm btn-outline-secondary formula-op" data-token="1">1</button>
                                </div>

                                <div id="expressionHint" class="small text-muted mt-2"></div>
                            </div>

                            <input type="hidden" name="expresion" id="expresion"
                                value="{{ old('expresion', $expresionSugerida ?: 'SUM(A) / SUM(B) * 100') }}">

                            <details class="mt-2">
                                <summary class="text-secondary fw-semibold small" style="cursor:pointer">Modo texto (avanzado)</summary>
                                <input type="text" id="expresionTexto" class="form-control form-control-sm font-monospace mt-2"
                                    autocomplete="off" spellcheck="false"
                                    value="{{ old('expresion', $expresionSugerida ?: 'SUM(A) / SUM(B) * 100') }}">
                                <div class="form-text">Sintaxis: <code>SUM(A) / SUM(B) * 100</code>. Funciones: SUM, AVG, COUNT, MIN, MAX.</div>
                            </details>

                            <details class="mt-2 small">
                                <summary class="text-secondary fw-semibold" style="cursor:pointer">Reglas de tipos de dato</summary>
                                <div class="table-responsive mt-2">
                                    <table class="table table-sm table-bordered small mb-0">
                                        <thead>
                                            <tr><th>Operación</th><th>Combinaciones válidas</th></tr>
                                        </thead>
                                        <tbody>
                                            <tr><td>Suma / resta</td><td>Solo entre tipos iguales (absoluto+absoluto, porcentaje+porcentaje, tasa+tasa, índice+índice).</td></tr>
                                            <tr><td>Multiplicación</td><td>Absoluto × razón (porcentaje, tasa, índice). No se permiten absoluto × absoluto ni razón × razón.</td></tr>
                                            <tr><td>División</td><td>Absoluto ÷ absoluto (produce una razón) o razón ÷ razón del mismo tipo.</td></tr>
                                            <tr><td>Constante</td><td>Una razón multiplicada por 100 se interpreta como porcentaje.</td></tr>
                                        </tbody>
                                    </table>
                                </div>
                            </details>
                        </div>

                        <div>
                            <label class="form-label fw-bold text-secondary small">Variables requeridas</label>
                            <div id="slotsEditor" class="border rounded-3 overflow-hidden"></div>
                            <div class="form-text">Se detectan automáticamente de la expresión. Aquí solo pones el nombre que verá el usuario.</div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-5">
                <div class="card-panel mb-4">
                    <div class="card-body p-4">
                        <h5 class="fw-bold text-dark mb-1">Prueba rápida</h5>
                        <p class="text-muted small mb-3">Asigna variables existentes para ver el resultado antes de guardar.</p>

                        <div id="previewAsignaciones" class="mb-3"></div>

                        <button type="button" class="btn btn-outline-info w-100" id="previewBtn">
                            <i class="fa-solid fa-calculator me-1"></i>Calcular
                        </button>

                        <div id="previewResultado" class="mt-3"></div>
                    </div>
                </div>

                <div class="card-panel">
                    <div class="card-body p-4">
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-custom-primary">
                                <i class="fa-solid fa-floppy-disk me-1"></i>{{ $formula ? 'Guardar cambios' : 'Guardar fórmula' }}
                            </button>
                            <a href="{{ route('admin.formulas.index') }}" class="btn btn-outline-secondary">Cancelar</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

@push('scripts')
<link href="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/css/tom-select.bootstrap5.min.css"
    rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/js/tom-select.complete.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const inputExpresion = document.getElementById('expresion');
        const inputTexto = document.getElementById('expresionTexto');
        const canvas = document.getElementById('expressionCanvas');
        const paletteVariables = document.getElementById('paletteVariables');
        const slotsEditor = document.getElementById('slotsEditor');
        const hint = document.getElementById('expressionHint');
        const previewAsignaciones = document.getElementById('previewAsignaciones');
        const previewResultado = document.getElementById('previewResultado');
        const variables = @json($variablesJson);
        const previewUrl = @json(route('admin.formulas.preview'));
        const nombresExistentes = @json(collect($slots)->pluck('nombre', 'clave'));

        const AGREGACIONES = {
            SUM: { etiqueta: 'Suma de', icono: 'Σ' },
            AVG: { etiqueta: 'Promedio de', icono: 'x̄' },
            COUNT: { etiqueta: 'Conteo de', icono: '#' },
            MIN: { etiqueta: 'Mínimo de', icono: '↓' },
            MAX: { etiqueta: 'Máximo de', icono: '↑' },
        };

        let seleccion = null;

        // ---------- Utilidades de expresión ----------

        function slotsDeExpresion(expresion) {
            const sinFunciones = expresion.replace(/\b(SUM|AVG|COUNT|MIN|MAX)\s*\(/gi, '(');
            const matches = sinFunciones.match(/[A-Za-z_][A-Za-z0-9_]*/g) || [];
            return [...new Set(matches.map(m => m.toUpperCase()))];
        }

        function tokenizar(expresion) {
            const regex = /\s*(SUM|AVG|COUNT|MIN|MAX)\s*\(\s*([A-Za-z0-9_]+)\s*\)|(\d+(?:\.\d+)?)|([A-Za-z_][A-Za-z0-9_]*)|([()+\-*/^])/gi;
            const fichas = [];
            let coincidencia;

            while ((coincidencia = regex.exec(expresion)) !== null) {
                const texto = coincidencia[0];
                const inicio = coincidencia[1]
                    ? coincidencia.index + texto.indexOf(coincidencia[1])
                    : coincidencia.index + (texto.length - texto.trimStart().length);
                const fin = coincidencia.index + texto.length;

                if (coincidencia[1]) {
                    fichas.push({
                        tipo: 'agregacion',
                        fn: coincidencia[1].toUpperCase(),
                        variable: coincidencia[2].toUpperCase(),
                        inicio, fin
                    });
                } else if (coincidencia[4]) {
                    fichas.push({
                        tipo: 'variable',
                        variable: coincidencia[4].toUpperCase(),
                        inicio, fin
                    });
                } else if (coincidencia[5]) {
                    fichas.push({
                        tipo: 'numero',
                        valor: coincidencia[5],
                        inicio, fin
                    });
                } else if (coincidencia[6]) {
                    fichas.push({
                        tipo: coincidencia[5] === '(' || coincidencia[5] === ')' ? 'parentesis' : 'operador',
                        valor: coincidencia[5],
                        inicio, fin
                    });
                }
            }

            return fichas;
        }

        function siguienteLetra(slots) {
            const letras = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
            for (const letra of letras) {
                if (!slots.includes(letra)) return letra;
            }
            return 'A';
        }

        // ---------- Render del editor visual ----------

        function render() {
            const expresion = inputExpresion.value;
            const fichas = tokenizar(expresion);

            canvas.innerHTML = '';

            if (!fichas.length) {
                const vacio = document.createElement('span');
                vacio.className = 'small text-muted fst-italic';
                vacio.textContent = 'Construye la expresión con los botones de abajo.';
                canvas.appendChild(vacio);
            }

            fichas.forEach((ficha, indice) => {
                const elemento = document.createElement('span');
                elemento.className = 'formula-chip';
                elemento.dataset.indice = indice;

                if (ficha.tipo === 'agregacion') {
                    const config = AGREGACIONES[ficha.fn];
                    elemento.classList.add('formula-chip--agg');
                    elemento.innerHTML = `<span class="formula-chip__icon">${config.icono}</span>
                        <span class="formula-chip__label">${config.etiqueta} ${ficha.variable}</span>
                        <span class="formula-chip__code">${ficha.fn}(${ficha.variable})</span>`;
                } else if (ficha.tipo === 'variable') {
                    elemento.classList.add('formula-chip--var');
                    elemento.innerHTML = `<span class="formula-chip__label">Variable ${ficha.variable}</span>`;
                } else if (ficha.tipo === 'numero') {
                    elemento.classList.add('formula-chip--num');
                    elemento.innerHTML = `<span class="formula-chip__label">${ficha.valor}</span>`;
                } else if (ficha.tipo === 'parentesis') {
                    elemento.classList.add('formula-chip--paren');
                    elemento.innerHTML = `<span class="formula-chip__label">${ficha.valor}</span>`;
                } else {
                    elemento.classList.add('formula-chip--op');
                    const simbolos = { '+': '+', '-': '−', '*': '×', '/': '÷', '^': '^' };
                    elemento.innerHTML = `<span class="formula-chip__label">${simbolos[ficha.valor] || ficha.valor}</span>`;
                }

                const eliminar = document.createElement('button');
                eliminar.type = 'button';
                eliminar.className = 'formula-chip__remove';
                eliminar.innerHTML = '<i class="fa-solid fa-xmark"></i>';
                eliminar.title = 'Quitar';
                eliminar.addEventListener('click', function(event) {
                    event.stopPropagation();
                    eliminarFicha(indice);
                });

                elemento.appendChild(eliminar);

                if (seleccion === indice) elemento.classList.add('is-selected');

                elemento.addEventListener('click', function() {
                    seleccion = (seleccion === indice) ? null : indice;
                    render();
                });

                canvas.appendChild(elemento);
            });

            renderPalette();
            renderHint(fichas);
        }

        function renderHint(fichas) {
            const sinCerrar = fichas.filter(f => f.tipo === 'parentesis' && f.valor === '(').length
                - fichas.filter(f => f.tipo === 'parentesis' && f.valor === ')').length;

            if (sinCerrar > 0) {
                hint.innerHTML = '<span class="text-warning-emphasis"><i class="fa-solid fa-triangle-exclamation me-1"></i>Faltan ' +
                    sinCerrar + ' paréntesis de cierre.</span>';
            } else if (sinCerrar < 0) {
                hint.innerHTML = '<span class="text-warning-emphasis"><i class="fa-solid fa-triangle-exclamation me-1"></i>Hay paréntesis de cierre sin abrir.</span>';
            } else {
                hint.textContent = 'Haz clic en una pieza para seleccionarla y usa los botones para construir la fórmula.';
            }
        }

        function renderPalette() {
            const slots = slotsDeExpresion(inputExpresion.value);
            const letras = [...new Set([...slots, 'A', 'B', 'C'])].sort();

            paletteVariables.innerHTML = '';
            letras.forEach(letra => {
                const boton = document.createElement('button');
                boton.type = 'button';
                boton.className = 'btn btn-sm btn-outline-info';
                boton.textContent = letra;
                boton.title = 'Insertar variable ' + letra;
                boton.addEventListener('click', function() {
                    aplicarVariable(letra);
                });
                paletteVariables.appendChild(boton);
            });
        }

        // ---------- Manipulación ----------

        function fijarExpresion(nueva, focoAlFinal = true) {
            inputExpresion.value = nueva;
            inputTexto.value = nueva;
            seleccion = null;
            render();
            renderSlots();
        }

        function fichaSeleccionada() {
            if (seleccion === null) return null;
            return tokenizar(inputExpresion.value)[seleccion] || null;
        }

        function aplicarEnSeleccion(texto) {
            const expresion = inputExpresion.value;
            const ficha = fichaSeleccionada();
            const posicion = ficha ? ficha.fin : expresion.length;

            fijarExpresion(expresion.slice(0, posicion) + texto + expresion.slice(posicion));
        }

        function aplicarVariable(letra) {
            const ficha = fichaSeleccionada();

            if (ficha && ficha.tipo === 'variable') {
                const expresion = inputExpresion.value;
                fijarExpresion(expresion.slice(0, ficha.inicio) + letra + expresion.slice(ficha.fin));
                return;
            }

            aplicarEnSeleccion(letra);
        }

        function eliminarFicha(indice) {
            const fichas = tokenizar(inputExpresion.value);
            const ficha = fichas[indice];

            if (!ficha) return;

            const expresion = inputExpresion.value;
            fijarExpresion(expresion.slice(0, ficha.inicio) + expresion.slice(ficha.fin));
        }

        function aplicarAgregacion(fn) {
            const ficha = fichaSeleccionada();

            if (ficha && ficha.tipo === 'agregacion') {
                const expresion = inputExpresion.value;
                const inicioNombre = ficha.inicio;
                const largoNombre = ficha.fn.length;
                fijarExpresion(
                    expresion.slice(0, inicioNombre) + fn + expresion.slice(inicioNombre + largoNombre)
                );
                return;
            }

            if (ficha && ficha.tipo === 'variable') {
                const expresion = inputExpresion.value;
                fijarExpresion(
                    expresion.slice(0, ficha.inicio) + fn + '(' + ficha.variable + ')' + expresion.slice(ficha.fin)
                );
                return;
            }

            const slots = slotsDeExpresion(inputExpresion.value);
            const letra = siguienteLetra(slots);
            aplicarEnSeleccion(fn + '(' + letra + ')');
        }

        document.querySelectorAll('.formula-agg').forEach(boton => {
            boton.addEventListener('click', function() {
                aplicarAgregacion(boton.dataset.fn);
            });
        });

        document.querySelectorAll('.formula-op').forEach(boton => {
            boton.addEventListener('click', function() {
                aplicarEnSeleccion(boton.dataset.token);
            });
        });

        inputTexto.addEventListener('input', function() {
            inputExpresion.value = inputTexto.value;
            seleccion = null;
            render();
            renderSlots();
        });

        // ---------- Slots ----------

        function renderSlots() {
            const slots = slotsDeExpresion(inputExpresion.value);
            slotsEditor.innerHTML = slots.length ? '' :
                '<div class="p-3 text-muted small">Sin variables detectadas. Agrega una agregación sobre una variable.</div>';

            slots.forEach(function(clave, posicion) {
                const fila = document.createElement('div');
                fila.className = 'd-flex align-items-center gap-2 p-2 border-bottom';
                fila.innerHTML = `
                    <span class="badge rounded-pill text-bg-secondary" style="min-width:3rem">${clave}</span>
                    <input type="hidden" name="slots[${posicion}][clave]" value="${clave}">
                    <input type="text" name="slots[${posicion}][nombre]" class="form-control form-control-sm"
                        placeholder="Nombre para mostrar (ej. Viviendas con drenaje)"
                        value="${nombresExistentes[clave] || ''}">`;
                slotsEditor.appendChild(fila);
            });

            renderPreviewAsignaciones(slots);
        }

        function initPreviewSelects() {
            previewAsignaciones.querySelectorAll('.preview-variable').forEach(function(el) {
                if (el.tomselect) el.tomselect.destroy();
                new TomSelect(el, {
                    placeholder: 'Selecciona la variable...',
                    allowEmptyOption: true,
                    dropdownParent: 'body',
                    searchField: 'text',
                });
            });
        }

        function renderPreviewAsignaciones(slots) {
            previewAsignaciones.querySelectorAll('.preview-variable').forEach(function(el) {
                if (el.tomselect) el.tomselect.destroy();
            });
            previewAsignaciones.innerHTML = '';

            if (!slots.length) {
                previewAsignaciones.innerHTML = '<p class="text-muted small mb-0">Aún no hay variables en la expresión.</p>';
                return;
            }

            slots.forEach(function(clave) {
                const opciones = variables.map(v =>
                    `<option value="${v.id}">${v.nombre}${v.indicador ? ' — ' + v.indicador : ''}${v.tipo ? ' (' + v.tipo + ')' : ''}</option>`).join('');
                const div = document.createElement('div');
                div.className = 'mb-2';
                div.innerHTML = `
                    <label class="form-label small fw-bold text-secondary mb-1">${clave}</label>
                    <select class="form-select form-select-sm preview-variable" data-clave="${clave}">
                        <option value="">-- Sin asignar --</option>
                        ${opciones}
                    </select>`;
                previewAsignaciones.appendChild(div);
            });

            initPreviewSelects();
        }

        // ---------- Cálculo de prueba ----------

        document.getElementById('previewBtn').addEventListener('click', function() {
            const asignaciones = {};
            document.querySelectorAll('.preview-variable').forEach(function(select) {
                if (select.value) asignaciones[select.dataset.clave] = select.value;
            });

            previewResultado.innerHTML = '<p class="text-muted small mb-0">Calculando...</p>';

            fetch(previewUrl, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        expresion: inputExpresion.value,
                        ambito: document.getElementById('ambito').value,
                        asignaciones: asignaciones
                    })
                })
                .then(r => r.json())
                .then(data => {
                    if (!data.success) {
                        previewResultado.innerHTML = `<div class="alert alert-danger small mb-0">${data.error}</div>`;
                        return;
                    }

                    if (!data.rows.length) {
                        previewResultado.innerHTML =
                            '<div class="alert alert-warning small mb-0">Asigna variables con datos para ver el resultado.</div>';
                        return;
                    }

                    const filas = data.rows.map(row => `
                        <tr>
                            <td>${row.anio ?? '—'}</td>
                            <td class="fw-bold">${row.valor ?? '—'}</td>
                        </tr>`).join('');

                    const tipo = data.tipoResultado
                        ? `<p class="small text-muted mt-2 mb-0">Tipo del resultado: <strong>${data.tipoResultado}</strong></p>`
                        : '';

                    previewResultado.innerHTML = `
                        <div class="table-responsive">
                            <table class="table table-sm table-bordered mb-0 small">
                                <thead><tr><th>Año</th><th>Resultado</th></tr></thead>
                                <tbody>${filas}</tbody>
                            </table>
                        </div>${tipo}`;
                })
                .catch(() => {
                    previewResultado.innerHTML = '<div class="alert alert-danger small mb-0">Error al calcular.</div>';
                });
        });

        render();
        renderSlots();
    });
</script>
@endpush

<style>
    .formula-canvas { min-height: 3.25rem; padding: .5rem; background: #fff; border: 1px dashed #c9c2bd; border-radius: .75rem; }
    .formula-chip { position: relative; display: inline-flex; align-items: center; gap: .35rem; padding: .35rem .6rem; border: 1px solid #d8d2cd; border-radius: .6rem; background: #f8f6f4; cursor: pointer; user-select: none; transition: box-shadow .15s, transform .15s; }
    .formula-chip:hover { transform: translateY(-1px); box-shadow: 0 3px 10px rgba(39, 49, 58, .12); }
    .formula-chip.is-selected { outline: 2px solid #651b2c; outline-offset: 1px; }
    .formula-chip__label { font-size: .82rem; font-weight: 600; color: #27313a; }
    .formula-chip__icon { font-size: .95rem; color: #651b2c; }
    .formula-chip__code { font-size: .68rem; color: #8a9299; font-family: Consolas, monospace; }
    .formula-chip--agg { background: #f8eef0; border-color: #e3c7cd; }
    .formula-chip--var { background: #eef4fb; border-color: #c6d8ec; }
    .formula-chip--num { background: #fdf6e8; border-color: #ecd9ac; }
    .formula-chip--op { background: #f2f4f5; border-color: #d6dade; }
    .formula-chip--paren { background: #fff; border-color: #d8d2cd; }
    .formula-chip__remove { position: absolute; top: -.45rem; right: -.45rem; display: none; width: 1.1rem; height: 1.1rem; padding: 0; color: #fff; background: #af1731; border: 0; border-radius: 50%; font-size: .6rem; line-height: 1; }
    .formula-chip:hover .formula-chip__remove, .formula-chip.is-selected .formula-chip__remove { display: block; }
</style>
@endsection

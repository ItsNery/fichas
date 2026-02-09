@extends('layouts.plantilla')
@php
    // Valores específicos para Datos Abiertos
    $pageTitle = 'Datos Abiertos - Transparencia y Acceso a la Información Pública';
    $pageDescription =
        'Accede a datasets, indicadores y información pública del Gobierno del Estado de Puebla. Descarga datos en formatos abiertos para análisis e investigación.';
    $currentUrl = url()->current();
@endphp

@section('title', $pageTitle)
@section('meta-description', $pageDescription)
@section('canonical-url', $currentUrl)

{{-- Open Graph --}}
@section('og-title', $pageTitle)
@section('og-description', $pageDescription)
@section('og:url', $currentUrl)
@section('og:image', asset('img/mapa_puebla.png')) {{-- Imagen específica si tienes --}}

{{-- Twitter --}}
@section('twitter-title', $pageTitle)
@section('twitter-description', $pageDescription)
@section('twitter:image', asset('img/mapa_puebla.png'))

{{-- Keywords específicas para Datos Abiertos --}}
@section('keywords',
    'datos abiertos Puebla, transparencia, información pública, datasets, datos gubernamentales, open
    data, gobierno abierto')

@section('content')
    <div class="container my-5">
        {{-- Encabezado --}}
        <div class="text-center border-bottom pb-4 mb-5">
            <h1 class="display-4 fw-bold">Datos Abiertos</h1>
            <p class="lead text-muted">
                Consulta y descarga los catálogos que estructuran la información del portal en formatos abiertos.
            </p>
        </div>

        {{-- Grid de Tarjetas de Descarga --}}
        <div class="row g-4 d-flex justify-content-center">

            {{-- Tarjeta para Dimensiones --}}
            <div class="col-md-6 col-lg-3">
                <div class="card h-100 text-center shadow-sm">
                    <div class="card-body">
                        <i class="fas fa-layer-group fa-3x texto-color-1 mb-3"></i>
                        <h5 class="card-title">Catálogo de Dimensiones</h5>
                        <p class="card-text small text-muted">La estructura principal que agrupa las temáticas.</p>
                    </div>
                    <div class="card-footer bg-transparent border-0 pb-3">
                        <a href="{{ route('datos-abiertos.export', ['tipo' => 'dimensiones']) }}"
                            class="btn btn-outline-color-1">
                            <i class="fas fa-download me-1"></i> Descargar (.xlsx)
                        </a>
                    </div>
                </div>
            </div>

            {{-- Tarjeta para Temáticas --}}
            <div class="col-md-6 col-lg-3">
                <div class="card h-100 text-center shadow-sm">
                    <div class="card-body">
                        <i class="fas fa-bookmark fa-3x texto-color-2 mb-3"></i>
                        <h5 class="card-title">Catálogo de Temáticas</h5>
                        <p class="card-text small text-muted">Los temas específicos dentro de cada dimensión.</p>
                    </div>
                    <div class="card-footer bg-transparent border-0 pb-3">
                        <a href="{{ route('datos-abiertos.export', ['tipo' => 'tematicas']) }}"
                            class="btn btn-outline-color-2">
                            <i class="fas fa-download me-1"></i> Descargar (.xlsx)
                        </a>
                    </div>
                </div>
            </div>

            {{-- Tarjeta para Indicadores --}}
            <div class="col-md-6 col-lg-3">
                <div class="card h-100 text-center shadow-sm">
                    <div class="card-body">
                        <i class="fas fa-chart-line fa-3x texto-color-3 mb-3"></i>
                        <h5 class="card-title">Catálogo de Indicadores</h5>
                        <p class="card-text small text-muted">La lista completa de indicadores disponibles en el portal.</p>
                    </div>
                    <div class="card-footer bg-transparent border-0 pb-3">
                        <a href="{{ route('datos-abiertos.export', ['tipo' => 'indicadores']) }}"
                            class="btn btn-outline-color-3">
                            <i class="fas fa-download me-1"></i> Descargar (.xlsx)
                        </a>
                    </div>
                </div>
            </div>

            {{-- Tarjeta para Variables --}}
            <div class="col-md-6 col-lg-3">
                <div class="card h-100 text-center shadow-sm">
                    <div class="card-body">
                        <i class="fas fa-database fa-3x texto-color-4 mb-3"></i>
                        <h5 class="card-title">Catálogo de Variables</h5>
                        <p class="card-text small text-muted">El desglose detallado de cada indicador.</p>
                    </div>
                    <div class="card-footer bg-transparent border-0 pb-3">
                        <a href="{{ route('datos-abiertos.export', ['tipo' => 'variables']) }}"
                            class="btn btn-outline-color-4">
                            <i class="fas fa-download me-1"></i> Descargar (.xlsx)
                        </a>
                    </div>
                </div>
            </div>
        </div>
        <div class="row g-4 my-3 d-flex justify-content-center">
            {{-- Tarjeta para Datos Históricos (con Dropdown) --}}
            <div class="col-md-6 col-lg-4">
                <div class="card h-100 shadow-sm border-danger">
                    <div class="card-body d-flex flex-column">
                        <div class="text-center">
                            <i class="fas fa-database fa-3x text-danger mb-3"></i>
                            <h5 class="card-title">Base de Datos Históricos</h5>
                            <p class="card-text small text-muted">Selecciona una dimensión y un año para descargar los datos
                                correspondientes.</p>
                        </div>

                        {{-- Filtros --}}
                        <div class="mt-3">
                            <label for="filtro-dimension" class="form-label fw-bold">1. Dimensión:</label>
                            <select id="filtro-dimension" class="form-select">
                                <option value="">Selecciona una dimensión...</option>
                                @foreach ($dimensiones as $dimension)
                                    <option value="{{ $dimension->id }}">{{ $dimension->nombre }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="mt-3">
                            <label for="filtro-anio" class="form-label fw-bold">2. Año:</label>
                            <select id="filtro-anio" class="form-select" disabled>
                                <option value="">Primero selecciona una dimensión...</option>
                            </select>
                        </div>

                        {{-- Botón de Descarga (aparece al final) --}}
                        <div class="mt-auto pt-3 text-center">
                            <a href="#" id="btn-descargar-historicos" class="btn btn-outline-danger disabled w-100">
                                <i class="fas fa-download me-1"></i> Descargar Datos (.csv)
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-6 col-lg-4">
                <div class="card h-100 shadow-sm border-info">
                    <div class="card-body d-flex flex-column">
                        <div class="text-center">
                            <i class="fas fa-seedling fa-3x text-info mb-3"></i>
                            <h5 class="card-title">Datos Complejos (Cultivos, etc.)</h5>
                            <p class="card-text small text-muted">Selecciona un indicador y un año para descargar los datos.
                            </p>
                        </div>

                        {{-- Filtro 1: Indicador Complejo --}}
                        <div class="mt-3">
                            <label for="filtro-complejo" class="form-label fw-bold">1. Indicador:</label>
                            <select id="filtro-complejo" class="form-select">
                                <option value="">Selecciona un indicador...</option>
                                {{-- Asumimos que pasas $indicadoresComplejos desde el HomeController --}}
                                @foreach ($indicadoresComplejos as $indicador)
                                    <option value="{{ $indicador->id }}">{{ $indicador->nombre_amigable }}</option>
                                @endforeach
                            </select>
                        </div>

                        {{-- Filtro 2: Año --}}
                        <div class="mt-3">
                            <label for="filtro-complejo-anio" class="form-label fw-bold">2. Año:</label>
                            <select id="filtro-complejo-anio" class="form-select" disabled>
                                <option value="">Primero selecciona un indicador...</option>
                            </select>
                        </div>

                        {{-- Botón de Descarga --}}
                        <div class="mt-auto pt-3 text-center">
                            <a href="#" id="btn-descargar-complejos" class="btn btn-outline-info disabled w-100">
                                <i class="fas fa-download me-1"></i> Descargar Datos (.csv)
                            </a>
                        </div>
                    </div>
                </div>
            </div>

        </div>
        <div class="row d-flex justify-content-center">
            <div class="col-md-6 col-lg-3">
                <div class="card h-100 text-center shadow-sm">
                    <div class="card-body">
                        <i class="fas fa-map-marker-alt fa-3x texto-color-5 mb-3"></i>
                        <h5 class="card-title">Catálogo de Municipios</h5>
                        <p class="card-text small text-muted">Lista completa de los 217 municipios del estado.</p>
                    </div>
                    <div class="card-footer bg-transparent border-0 pb-3">
                        <a href="{{ route('datos-abiertos.export', ['tipo' => 'municipios']) }}"
                            class="btn btn-outline-color-5">
                            <i class="fas fa-download me-1"></i> Descargar (.xlsx)
                        </a>
                    </div>
                </div>
            </div>
            <div class="col-md-6 col-lg-3">
                <div class="card h-100 text-center shadow-sm">
                    <div class="card-body">
                        <i class="fas fa-object-group fa-3x texto-color-6 mb-3"></i>
                        <h5 class="card-title">Catálogo de Microrregiones</h5>
                        <p class="card-text small text-muted">Lista completa de las microrregiones del estado.</p>
                    </div>
                    <div class="card-footer bg-transparent border-0 pb-3">
                        <a href="{{ route('datos-abiertos.export', ['tipo' => 'microrregiones']) }}"
                            class="btn btn-outline-color-6">
                            <i class="fas fa-download me-1"></i> Descargar (.xlsx)
                        </a>
                    </div>
                </div>
            </div>
            <div class="col-md-6 col-lg-3">
                <div class="card h-100 text-center shadow-sm">
                    <div class="card-body">
                        <i class="fas fa-atlas fa-3x texto-color-7 mb-3"></i>
                        <h5 class="card-title">Catálogo de Macrorregiones</h5>
                        <p class="card-text small text-muted">Lista completa de las microrregiones del estado.</p>
                    </div>
                    <div class="card-footer bg-transparent border-0 pb-3">
                        <a href="{{ route('datos-abiertos.export', ['tipo' => 'macrorregiones']) }}"
                            class="btn btn-outline-color-7">
                            <i class="fas fa-download me-1"></i> Descargar (.xlsx)
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="container my-5">
        {{-- Encabezado --}}
        <div class="text-center border-bottom pb-4 mb-5">
            <h1 class="display-4 fw-bold">Acceso a Datos Abiertos vía API</h1>
            <p class="lead text-muted">
                Utiliza nuestro endpoint público para realizar consultas personalizadas y obtener datos en formato JSON.
            </p>
        </div>

        {{-- Tarjeta de Información General --}}
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-light">
                <h5 class="mb-0">Información General</h5>
            </div>
            <div class="card-body">
                <p><strong>Endpoint:</strong> <code>{{ route('api.public.consulta') }}</code></p>
                <p><strong>Método HTTP:</strong> <span class="badge bg-primary">POST</span></p>
                <p class="mb-0"><strong>Formato de Cuerpo:</strong> <code>application/json</code></p>
            </div>
        </div>

        {{-- Tabla de Parámetros --}}
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-light">
                <h5 class="mb-0">Parámetros de la Petición</h5>
            </div>
            <div class="table-responsive">
                <table class="table table-striped table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Parámetro</th>
                            <th>Tipo</th>
                            <th>Obligatorio</th>
                            <th>Descripción y Ejemplo</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><code>indicador_id</code></td>
                            <td>Entero</td>
                            <td>Sí</td>
                            <td>ID del indicador a consultar. <em>Ej: <code>26</code></em></td>
                        </tr>
                        <tr>
                            <td><code>nivel_de_agregacion</code></td>
                            <td>String</td>
                            <td>Sí</td>
                            <td>"municipio", "microrregion" o "macrorregion". <em>Ej: <code>"municipio"</code></em></td>
                        </tr>
                        <tr>
                            <td><code>municipio_ids</code></td>
                            <td>Array de Strings</td>
                            <td>Condicional</td>
                            <td>Requerido si el nivel es "municipio". Puede ser un ID o "estatal". <em>Ej: <code>["1",
                                        "114"]</code> o <code>["estatal"]</code></em></td>
                        </tr>
                        <tr>
                            <td><code>region_id</code></td>
                            <td>Entero</td>
                            <td>Condicional</td>
                            <td>Requerido si el nivel es "microrregion" o "macrorregion". <em>Ej: <code>3</code></em></td>
                        </tr>
                        <tr>
                            <td><code>anios</code></td>
                            <td>Array de Enteros</td>
                            <td>No</td>
                            <td>Filtra por años específicos. Si se omite, devuelve el histórico o el año más reciente.
                                <em>Ej: <code>[2020, 2022]</code></em>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Ejemplos --}}
        <div class="card shadow-sm">
            <div class="card-header bg-light">
                <h5 class="mb-0">Ejemplo de Uso</h5>
            </div>
            <div class="card-body">
                <h6>Ejemplo de Petición (Cuerpo JSON):</h6>
                <p>Para obtener la "Población total" (suponiendo que es el indicador 26) para los municipios de Puebla y
                    Atlixco en el año 2020:</p>
                <pre>
                    <code class="language-json bg-dark text-white p-3 rounded d-block">
                    {
                    "indicador_id": 26,
                    "nivel_de_agregacion": "municipio",
                    "municipio_ids": ["114", "14"],
                    "anios": [2020]
                    }
                    </code>
                </pre>
                <h6 class="mt-4">Ejemplo de Respuesta Exitosa (JSON):</h6>
                <pre>
                    <code class="language-json bg-dark text-white p-3 rounded d-block">
                        {
                        "success": true,
                        "data": {
                            "titulo": "Población total - Comparación Año: 2020",
                            "tipo_grafico": "bar",
                            "series": [
                            {
                                "name": "Población total",
                                "data": [1692181, 135246]
                            }
                            ],
                            "eje_x": {
                            "categorias": ["Puebla", "Atlixco"]
                            },
                            // ...resto de los metadatos...
                        }
                        }
                    </code>
                </pre>
            </div>
        </div>
    </div>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const dimensionSelect = document.getElementById('filtro-dimension');
            const anioSelect = document.getElementById('filtro-anio');
            const descargarBtn = document.getElementById('btn-descargar-historicos');

            const complejoSelect = document.getElementById('filtro-complejo');
            const complejoAnioSelect = document.getElementById('filtro-complejo-anio');
            const complejoDescargarBtn = document.getElementById('btn-descargar-complejos');

            // 1. Cuando el usuario selecciona una Dimensión
            dimensionSelect.addEventListener('change', function() {
                const dimensionId = this.value;

                // Reseteamos el selector de año y el botón
                anioSelect.innerHTML = '<option value="">Cargando años...</option>';
                anioSelect.disabled = true;
                descargarBtn.classList.add('disabled');
                descargarBtn.href = '#';

                if (!dimensionId) {
                    anioSelect.innerHTML = '<option value="">Primero selecciona una dimensión...</option>';
                    return;
                }

                // 2. Buscamos los años disponibles para esa dimensión
                fetch(`${window.APP_URL}/api/dimension/${dimensionId}/anios-disponibles`)
                    .then(response => response.json())
                    .then(anios => {
                        anioSelect.innerHTML = '<option value="">Selecciona un año...</option>';
                        if (anios.length === 0) {
                            anioSelect.innerHTML = '<option value="">No hay años con datos...</option>';
                            return;
                        }

                        anios.forEach(anio => {
                            anioSelect.add(new Option(anio, anio));
                        });
                        anioSelect.disabled = false; // Habilitamos el selector de año
                    })
                    .catch(error => {
                        console.error('Error al cargar años:', error);
                        anioSelect.innerHTML = '<option value="">Error al cargar años</option>';
                    });
            });


            // 3. Cuando el usuario selecciona un Año
            anioSelect.addEventListener('change', function() {
                const dimensionId = dimensionSelect.value;
                const anio = this.value;

                if (dimensionId && anio) {
                    // Construimos la URL de descarga y habilitamos el botón
                    let url =
                        "{{ route('datos-abiertos.export-historicos', ['dimension' => 'DIM_ID', 'anio' => 'ANIO_ID']) }}";
                    url = url.replace('DIM_ID', dimensionId).replace('ANIO_ID', anio);

                    descargarBtn.href = url;
                    descargarBtn.classList.remove('disabled');
                } else {
                    descargarBtn.classList.add('disabled');
                    descargarBtn.href = '#';
                }
            });
            complejoSelect.addEventListener('change', function() {
                const indicadorId = this.value;

                // Reseteamos
                complejoAnioSelect.innerHTML = '<option value="">Cargando años...</option>';
                complejoAnioSelect.disabled = true;
                complejoDescargarBtn.classList.add('disabled');

                if (!indicadorId) {
                    /* ... (manejo de error) ... */
                    return;
                }

                // 2. Buscamos los años disponibles para ESE indicador complejo
                fetch(`${window.APP_URL}/api/indicador-complejo/${indicadorId}/anios-disponibles`)
                    .then(response => response.json())
                    .then(anios => {
                        complejoAnioSelect.innerHTML = '<option value="">Selecciona un año...</option>';
                        anios.forEach(anio => {
                            complejoAnioSelect.add(new Option(anio, anio));
                        });
                        complejoAnioSelect.disabled = false;
                    });
            });

            // 3. Cuando el usuario selecciona un Año
            complejoAnioSelect.addEventListener('change', function() {
                const indicadorId = complejoSelect.value;
                const anio = this.value;

                if (indicadorId && anio) {
                    // Construimos la URL de descarga y habilitamos el botón
                    let url =
                        "{{ route('datos-abiertos.export-complejos', ['indicador' => 'IND_ID', 'anio' => 'ANIO_ID']) }}";
                    url = url.replace('IND_ID', indicadorId).replace('ANIO_ID', anio);

                    complejoDescargarBtn.href = url;
                    complejoDescargarBtn.classList.remove('disabled');
                } else {
                    complejoDescargarBtn.classList.add('disabled');
                }
            });
        });
    </script>
@endsection

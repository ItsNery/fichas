@extends('layouts.plantilla')
@section('title', 'Directorio de Municipios')
@section('css')
@endsection

@section('content')
<section class="banner-directorio">
    <div class="container">
        <h1 class="banner-directorio__titulo">Directorio de municipios</h1>
        <p class="banner-directorio__descripcion">
            Explora las fichas estadísticas de los 217 municipios del estado de Puebla.
        </p>
        <div class="container d-flex justify-content-center align-items-center">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item ">
                        <a href="{{ url('/') }}" class="text-white">
                            Inicio
                        </a>
                    </li>
                    <li class="breadcrumb-item text-white active" aria-current="page">
                        Directorio de municipios
                    </li>
                </ol>
            </nav>
        </div>
    </div>
</section>

<section class="contenedor-directorio container">
    <form class="directorio-filtros" id="directorio-filtros" role="search" aria-label="Buscar y filtrar municipios">
        <div class="directorio-filtros__campo directorio-filtros__campo--busqueda">
            <label for="municipio-search">Nombre del municipio</label>
            <div class="directorio-filtros__control">
                <i class="bi bi-search" aria-hidden="true"></i>
                <input type="search" id="municipio-search" class="contenedor-directorio__input"
                    placeholder="Ej. Tehuacán, Atlixco..." autocomplete="off" spellcheck="false">
            </div>
        </div>

        <div class="directorio-filtros__campo">
            <label for="macro-filter">Macrorregión</label>
            <select id="macro-filter" class="directorio-filtros__select">
                <option value="">Todas las macrorregiones</option>
                @foreach ($macrorregiones as $macro)
                    <option value="{{ $macro->id }}">{{ $macro->id }} - {{ $macro->nombre }}</option>
                @endforeach
            </select>
        </div>

        <div class="directorio-filtros__campo">
            <label for="micro-filter">Microrregión</label>
            <select id="micro-filter" class="directorio-filtros__select">
                <option value="">Todas las microrregiones</option>
                @foreach ($macrorregiones as $macro)
                    @foreach ($macro->microrregiones as $micro)
                        <option value="{{ $micro->id }}" data-macro="{{ $macro->id }}">{{ $micro->nombre }}</option>
                    @endforeach
                @endforeach
            </select>
        </div>
    </form>

    <h2 class="visually-hidden">Listado de municipios</h2>

    <div class="directorio-resultados__encabezado">
        <p id="resultados-resumen" class="directorio-resultados__resumen" aria-live="polite">
            Mostrando <strong>{{ min(24, $municipios->count()) }}</strong> de <strong>{{ $municipios->count() }}</strong> municipios
        </p>
        <button type="button" id="limpiar-filtros" class="directorio-filtros__limpiar" hidden>
            <i class="bi bi-x-circle" aria-hidden="true"></i> Limpiar filtros
        </button>
    </div>

    <div class="row g-4 directorio-resultados" id="municipios-grid">
        @foreach ($municipios as $mun)
            @php
                $micro = $mun->microrregion;
                $macro = $micro?->macrorregion;
            @endphp
            <div class="col-12 col-sm-6 col-lg-4 col-xl-3 item-municipio"
                data-name="{{ $mun->nombre }}"
                data-macro="{{ $macro?->id }}"
                data-micro="{{ $micro?->id }}">
                <article class="tarjeta-municipio">
                    <img class="tarjeta-municipio__imagen"
                        src="{{ $mun->banner_image_url ?: asset(config('regionalizacion.fallback_hero')) }}"
                        alt="Foto de {{ $mun->nombre }}"
                        loading="lazy"
                        decoding="async">

                    <div class="tarjeta-municipio__capa">
                        <span class="tarjeta-municipio__region">{{ $macro?->nombre ?? 'Sin macrorregión' }}</span>
                        <h3 class="tarjeta-municipio__nombre">{{ $mun->nombre }}</h3>
                        <p class="tarjeta-municipio__info">Microrregión: {{ $micro?->nombre ?? 'Sin asignar' }}</p>
                        <a href="{{ route('ficha-municipal.perfil', $mun) }}" class="tarjeta-municipio__boton">Ver ficha</a>
                    </div>
                </article>
            </div>
        @endforeach
    </div>

    <div class="directorio-resultados__vacio" id="sin-resultados" hidden>
        <i class="bi bi-search" aria-hidden="true"></i>
        <h3>No encontramos municipios</h3>
        <p>Prueba con otro nombre o limpia los filtros seleccionados.</p>
        <button type="button" class="tarjeta-municipio__boton" data-reset-filters>Limpiar filtros</button>
    </div>

    <div class="directorio-resultados__acciones">
        <button type="button" id="mostrar-mas" class="directorio-resultados__mas">
            Mostrar más municipios <i class="bi bi-chevron-down" aria-hidden="true"></i>
        </button>
    </div>
</section>
@endsection

@section('jss')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const pageSize = 24;
        const filtersForm = document.getElementById('directorio-filtros');
        const searchInput = document.getElementById('municipio-search');
        const macroFilter = document.getElementById('macro-filter');
        const microFilter = document.getElementById('micro-filter');
        const items = Array.from(document.querySelectorAll('.item-municipio'));
        const resultsSummary = document.getElementById('resultados-resumen');
        const emptyState = document.getElementById('sin-resultados');
        const showMoreButton = document.getElementById('mostrar-mas');
        const clearFiltersButton = document.getElementById('limpiar-filtros');
        const resetButtons = document.querySelectorAll('[data-reset-filters]');
        let visibleLimit = pageSize;

        const normalizeText = value => value
            .normalize('NFD')
            .replace(/[\u0300-\u036f]/g, '')
            .toLocaleLowerCase('es')
            .trim();

        items.forEach(item => {
            item.dataset.normalizedName = normalizeText(item.dataset.name);
        });

        function updateMicroregions() {
            const macroId = macroFilter.value;

            Array.from(microFilter.options).forEach((option, index) => {
                if (index === 0) return;

                const isAvailable = !macroId || option.dataset.macro === macroId;
                option.hidden = !isAvailable;
                option.disabled = !isAvailable;
            });

            if (microFilter.selectedOptions[0]?.disabled) {
                microFilter.value = '';
            }
        }

        function applyFilters() {
            const term = normalizeText(searchInput.value);
            const macroId = macroFilter.value;
            const microId = microFilter.value;
            const matches = items.filter(item => {
                const matchesName = !term || item.dataset.normalizedName.includes(term);
                const matchesMacro = !macroId || item.dataset.macro === macroId;
                const matchesMicro = !microId || item.dataset.micro === microId;

                return matchesName && matchesMacro && matchesMicro;
            });

            const visibleItems = matches.slice(0, visibleLimit);
            const visibleSet = new Set(visibleItems);

            items.forEach(item => item.classList.toggle('d-none', !visibleSet.has(item)));

            const visibleCount = visibleItems.length;
            const totalMatches = matches.length;
            const noun = totalMatches === 1 ? 'municipio' : 'municipios';
            resultsSummary.innerHTML = `Mostrando <strong>${visibleCount}</strong> de <strong>${totalMatches}</strong> ${noun}`;

            emptyState.hidden = totalMatches !== 0;
            showMoreButton.parentElement.hidden = visibleCount >= totalMatches;
            clearFiltersButton.hidden = !term && !macroId && !microId;
        }

        function resetFilters() {
            filtersForm.reset();
            visibleLimit = pageSize;
            updateMicroregions();
            applyFilters();
            searchInput.focus();
        }

        filtersForm.addEventListener('submit', event => event.preventDefault());

        searchInput.addEventListener('input', function() {
            visibleLimit = pageSize;
            applyFilters();
        });

        macroFilter.addEventListener('change', function() {
            visibleLimit = pageSize;
            updateMicroregions();
            applyFilters();
        });

        microFilter.addEventListener('change', function() {
            visibleLimit = pageSize;
            applyFilters();
        });

        showMoreButton.addEventListener('click', function() {
            visibleLimit += pageSize;
            applyFilters();
        });

        clearFiltersButton.addEventListener('click', resetFilters);
        resetButtons.forEach(button => button.addEventListener('click', resetFilters));

        updateMicroregions();
        applyFilters();
    });
</script>
@endsection


@extends('layouts.plantilla')

@section('title', $municipio->nombre . ' | Ficha municipal v4')

@section('css')
    @vite('resources/css/ficha-municipal-v4.css')
@endsection

@section('jss')
    @vite('resources/js/ficha-municipal-v4.js')
@endsection

@section('content')
    <div class="municipio-v4" data-municipio-slug="{{ $municipio->slug }}" data-geojson-url="{{ asset('geojson/municipios_puebla_slim.geojson') }}">
        <section class="municipio-v4__hero">
            <div class="container municipio-v4__hero-inner">
                <nav class="municipio-v4__breadcrumb" aria-label="Breadcrumb">
                    <a href="{{ url('/') }}">Inicio</a>
                    <span aria-hidden="true">/</span>
                    <a href="{{ route('ficha-municipal.index') }}">Directorio</a>
                    <span aria-hidden="true">/</span>
                    <span aria-current="page">{{ $municipio->nombre }}</span>
                </nav>

                <div class="municipio-v4__hero-grid">
                    <div>
                        <p class="municipio-v4__eyebrow">Ficha municipal · Nueva versión</p>
                        <h1>{{ $municipio->nombre }}</h1>
                        <p class="municipio-v4__location">
                            {{ $municipio->microrregion?->macrorregion?->nombre ?? 'Estado de Puebla' }}
                            <span aria-hidden="true">·</span>
                            {{ $municipio->microrregion?->nombre ?? 'Región no disponible' }}
                        </p>
                        <p class="municipio-v4__hero-note">Consulta indicadores, comparaciones y señales territoriales en un solo lugar.</p>
                        <div class="municipio-v4__actions">
                            <a class="btn municipio-v4__button municipio-v4__button--primary" href="{{ route('ficha-municipal.perfil', $municipio->slug) }}">
                                Ver ficha actual
                            </a>
                            <a class="btn municipio-v4__button municipio-v4__button--secondary" href="{{ route('ficha-municipal.perfil.pdf', $municipio->slug) }}" target="_blank">
                                <i class="fa-solid fa-file-pdf" aria-hidden="true"></i> Descargar PDF
                            </a>
                        </div>
                    </div>
                    <div class="municipio-v4__context" aria-label="Contexto territorial">
                        <span class="municipio-v4__context-label">Contexto</span>
                        <strong>{{ $municipio->cabecera ?? $municipio->nombre }}</strong>
                        <span>Cabecera municipal</span>
                        <strong>{{ $municipio->clima ?? 'No disponible' }}</strong>
                        <span>Clima predominante</span>
                    </div>
                </div>
            </div>
        </section>

        <section class="municipio-v4__summary" aria-labelledby="summary-title">
            <div class="container">
                <div class="municipio-v4__section-heading">
                    <div>
                        <p class="municipio-v4__eyebrow">Lectura rápida</p>
                        <h2 id="summary-title">{{ $summary['headline'] }}</h2>
                    </div>
                    <span class="municipio-v4__freshness"><i class="fa-solid fa-triangle-exclamation" aria-hidden="true"></i> {{ $summary['quality']['label'] }}</span>
                </div>

                <p class="municipio-v4__quality-note">{{ $summary['quality']['message'] }}</p>

                <div class="municipio-v4__summary-grid">
                    @foreach($summary['cards'] as $card)
                        <article class="municipio-v4__summary-card">
                            <span>{{ $card['label'] }}</span>
                            <strong>{{ $card['value'] }}</strong>
                            <small>{{ $card['unit'] }}</small>
                        </article>
                    @endforeach
                </div>
            </div>
        </section>

        <section class="municipio-v4__compare" aria-labelledby="compare-title">
            <div class="container municipio-v4__compare-inner">
                <div>
                    <p class="municipio-v4__eyebrow">Explorar</p>
                    <h2 id="compare-title">Compara este municipio</h2>
                    <p>Busca otro municipio para abrir una comparación lado a lado.</p>
                </div>
                <div class="municipio-v4__compare-search">
                    <label for="municipio-v4-peer">Municipio a comparar</label>
                    <input id="municipio-v4-peer" type="search" autocomplete="off" placeholder="Escribe al menos 2 caracteres" data-peer-search>
                    <div class="municipio-v4__search-results" data-peer-results role="listbox" hidden></div>
                </div>
            </div>
        </section>

        <div class="municipio-v4__navigation-wrap">
            <div class="container">
                <nav class="municipio-v4__navigation" aria-label="Secciones de la ficha">
                    <a href="#resumen">Resumen</a>
                    @foreach($sections as $section)
                        <a href="#dimension-{{ $section['slug'] }}">{{ $section['name'] }} <span>{{ $section['count'] }}</span></a>
                    @endforeach
                </nav>
            </div>
        </div>

        <div class="container municipio-v4__sections" id="resumen">
            @foreach($sections as $section)
                <section class="municipio-v4__dimension" id="dimension-{{ $section['slug'] }}" data-section-url="{{ $section['url'] }}" data-section-state="idle" aria-labelledby="title-{{ $section['slug'] }}">
                    <header class="municipio-v4__dimension-header" style="--dimension-color: {{ $section['color'] ?: '#861e34' }}">
                        <div>
                            <p class="municipio-v4__eyebrow">Dimensión</p>
                            <h2 id="title-{{ $section['slug'] }}">{{ $section['name'] }}</h2>
                        </div>
                        <span>{{ $section['count'] }} indicadores</span>
                    </header>
                    <div class="municipio-v4__section-status" data-section-status role="status">
                        <span class="spinner-border spinner-border-sm" aria-hidden="true"></span> Preparando indicadores...
                    </div>
                    <div class="municipio-v4__cards" data-section-cards></div>
                </section>
            @endforeach
        </div>
    </div>
@endsection

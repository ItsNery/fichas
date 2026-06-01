@extends('layouts.plantilla')
@section('title', 'Directorio de Municipios')
@section('css')
@endsection

@section('content')
<section class="banner-directorio">
    <div class="container">
        <h1 class="banner-directorio__titulo">Municipios</h1>
        <p class="banner-directorio__descripcion">
            Explora la ficha con información estadística de cada uno de los 217 municipios del estado de Puebla
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
                        Directorio
                    </li>
                </ol>
            </nav>
        </div>
    </div>
</section>

<section class="contenedor-directorio container">

    <div class="contenedor-directorio__buscador">
        <input type="text" id="municipio-search" class="contenedor-directorio__input" placeholder="Buscar municipio por nombre...">
    </div>

    <div class="contenedor-directorio__encabezado">
        <h4 class="contenedor-directorio__titulo-seccion">Macrorregiones</h4>
        <span class="contenedor-directorio__etiqueta">Consulta los municipios agrupados por macrorregión.</span>
    </div>

    <div class="navegacion-regiones shadow-sm py-2">
        <ul class="nav nav-pills justify-content-center" id="regionTabs" role="tablist">
            @foreach ($macrorregiones as $index => $macro)
            <li class="nav-item" role="presentation">
                <button class="nav-link {{ $index === 0 ? 'active' : '' }} navegacion-regiones__enlace"
                    id="macro-{{ $macro->id }}-tab"
                    data-bs-toggle="pill"
                    data-bs-target="#macro-{{ $macro->id }}"
                    type="button"
                    role="tab">
                    {{ $macro->id }} - {{ $macro->nombre }}
                </button>
            </li>
            @endforeach
        </ul>
    </div>

    <div class="tab-content my-3" id="regionTabsContent">
        @foreach ($macrorregiones as $index => $macro)
        <div class="tab-pane fade {{ $index === 0 ? 'show active' : '' }}" id="macro-{{ $macro->id }}" role="tabpanel">
            <div class="row g-4">
                @foreach ($macro->microrregiones as $micro)
                @foreach ($micro->municipios as $mun)
                <div class="col-md-6 col-lg-3 item-municipio" data-name="{{ strtolower($mun->nombre) }}">

                    <article class="tarjeta-municipio">
                        <img class="tarjeta-municipio__imagen"
                            src="{{ $mun->banner_image_url ? $mun->banner_image_url : "https://picsum.photos/seed/{$mun->id}/400/250" }}"
                            alt="Foto de {{ $mun->nombre }}">

                        <div class="tarjeta-municipio__capa">
                            <h3 class="tarjeta-municipio__nombre">{{ $mun->nombre }}</h3>
                            <p class="tarjeta-municipio__info">Microrregión: {{ $micro->nombre }}</p>
                            <a href="{{ route('ficha-municipal.perfil', $mun) }}" class="tarjeta-municipio__boton">Ver Ficha</a>
                        </div>
                    </article>

                </div>
                @endforeach
                @endforeach
            </div>
        </div>
        @endforeach
    </div>
</section>
@endsection

@section('jss')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const searchInput = document.getElementById('municipio-search');
        const items = document.querySelectorAll('.item-municipio'); // Clase corregida
        const tabPanes = document.querySelectorAll('.tab-pane');
        const tabsNav = document.querySelector('.navegacion-regiones');
        const headerMacro = document.querySelector('.contenedor-directorio__encabezado');

        searchInput.addEventListener('input', function(e) {
            const term = e.target.value.toLowerCase().trim();
            
            if (term.length > 0) {
                // MODO BÚSQUEDA: Escondemos las pestañas para ver todo en una lista
                tabsNav.style.display = 'none';
                headerMacro.style.display = 'none';

                items.forEach(item => {
                    const name = item.getAttribute('data-name');
                    // Usamos display: block o none según coincida
                    item.style.display = name.includes(term) ? 'block' : 'none';
                });

                // Forzamos a que todos los paneles sean visibles
                tabPanes.forEach(pane => {
                    pane.classList.add('show', 'active');
                    // Ocultamos el panel completo si no tiene resultados visibles
                    const hasVisibleItems = pane.querySelector('.item-municipio[style="display: block;"]');
                    pane.style.display = hasVisibleItems ? 'block' : 'none';
                });

            } else {
                // MODO NORMAL: Restauramos el comportamiento de pestañas
                tabsNav.style.display = 'block';
                headerMacro.style.display = 'block';
                
                items.forEach(item => item.style.display = 'block');

                tabPanes.forEach((pane, index) => {
                    pane.style.display = 'block';
                    // Restauramos solo la primera pestaña activa (o la que estaba)
                    if (index === 0) {
                        pane.classList.add('show', 'active');
                    } else {
                        pane.classList.remove('show', 'active');
                    }
                });
            }
        });
    });
</script>
@endsection


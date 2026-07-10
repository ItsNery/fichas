{{-- 1. HEADER (Solo Logos) --}}
<header class="site-header">
    <div class="header-logos-container">
        <div class="container">
            <div class="logos-wrapper">

                {{-- GRUPO IZQUIERDA: Tira de Logos Gobierno --}}
                <div class="logos-group left">
                    <a href="https://puebla.gob.mx/" target="_blank" title="Gobierno del Estado de Puebla">
                        {{-- Asegúrate de poner la ruta correcta de tu tira de logos --}}
                        <img src="{{ asset('img/Logos-SPFA.png') }}" alt="Gobierno de Puebla">
                    </a>
                </div>

                {{-- GRUPO DERECHA: Logo SEI --}}
                <div class="logos-group right">
                    <a href="https://sei.puebla.gob.mx/" target="_self" title="Sistema Estatal de Información">
                        <img src="{{ asset('img/logo-sei.png') }}" alt="Logo SEI Puebla">
                    </a>
                </div>

            </div>

            {{-- Botón Hamburguesa --}}
            <button id="hamburger-menu" class="hamburger-menu" aria-label="Abrir menú">
                <span class="hamburger-line"></span>
                <span class="hamburger-line"></span>
                <span class="hamburger-line"></span>
            </button>
        </div>
    </div>
</header>
{{-- ¡IMPORTANTE: El header cierra aquí! --}}

{{-- 2. NAVEGACIÓN (Hermano del header, para el Sticky) --}}
<nav id="main-nav" class="main-nav">
    <div class="container nav-container">
        <ul class="nav-links">

            {{-- ENLACE 1: Inicio --}}
            <li>
                <a href="{{ url('/') }}" class="{{ request()->is('/') ? 'active' : '' }}">
                    Inicio
                </a>
            </li>

            {{-- ENLACE 2: Banco de Indicadores --}}
            <li>
                <a href="{{ url('/banco-indicadores') }}"
                    class="{{ request()->is('banco-indicadores*') ? 'active' : '' }}">
                    Banco de Indicadores
                </a>
            </li>

            {{-- ENLACE 3: Fichas Municipales --}}
            <li>
                <a href="{{ route('ficha-municipal.index') }}"
                    class="{{ request()->is('ficha*') ? 'active' : '' }}">
                    Fichas Municipales
                </a>
            </li>

            {{-- ENLACE 3: Datos Abiertos --}}
            <li>
                <a href="{{ url('/datos-abiertos') }}" class="{{ request()->is('datos-abiertos*') ? 'active' : '' }}">
                    Datos abiertos
                </a>
            </li>

            {{-- EJEMPLO DE DROPDOWN CON LÓGICA 'ACTIVE' --}}
            {{-- Si estás dentro de cualquier sub-ruta de 'informacion', el padre se pinta activo --}}
             {{-- <li class="dropdown">
                <a href="#" class="{{ request()->is('informacion*') ? 'active' : '' }}">
                    Información <span class="arrow">▾</span>
                </a>
                <ul class="dropdown-content">
                    <li><a href="{{ url('/informacion/historia') }}">Historia</a></li>

                    <li class="dropdown-nested">
                        <a href="#" class="nested-trigger">
                            Transparencia <span class="arrow-right">▸</span>
                        </a>
                        <ul class="dropdown-content-nested">
                            <li><a href="{{ url('/informacion/art70') }}">Art. 70</a></li>
                            <li><a href="{{ url('/informacion/art80') }}">Art. 80</a></li>
                        </ul>
                    </li>
                </ul>
            </li>  --}}

        </ul>
    </div>
</nav>
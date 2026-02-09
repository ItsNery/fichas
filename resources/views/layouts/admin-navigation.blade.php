<nav class="navbar navbar-expand-lg navbar-light bg-light border-bottom shadow-sm">
    <div class="container">
        <a class="navbar-brand" href="{{ route('admin.dashboard') }}">
            <img src="{{ asset('img/logo-sei.png') }}" alt="Logo" style="height: 40px; width: auto;">
        </a>

        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNavbar"
            aria-controls="mainNavbar" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="mainNavbar">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}"
                        href="{{ route('admin.dashboard') }}">{{ __('Inicio') }}</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('admin.catalogos.*') ? 'active' : '' }}"
                        href="{{ route('admin.catalogos.index') }}">{{ __('Catálogos') }}</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('admin.datos.*') ? 'active' : '' }}"
                        href="{{ route('admin.datos.index') }}">{{ __('Datos') }}</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('admin.salud-datos*') ? 'active' : '' }}"
                        href="{{ route('admin.salud-datos') }}">{{ __('Salud') }}</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('admin.import.*') ? 'active' : '' }}"
                        href="{{ route('admin.import.index') }}">{{ __('Importación') }}</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('admin.users.*') ? 'active' : '' }}"
                        href="{{ route('admin.users.index') }}">{{ __('Usuarios') }}</a>
                </li>
                <li class="nav-item dropdown">
                    {{-- Este es el enlace principal que activa el menú --}}
                    <a class="nav-link dropdown-toggle {{ request()->routeIs('admin.municipios.*') || request()->routeIs('admin.instrumentos.*') ? 'active' : '' }}"
                        href="#" id="navbarDropdownMunicipios" role="button" data-bs-toggle="dropdown"
                        aria-expanded="false">
                        Municipios
                    </a>
                    {{-- Aquí empieza la lista de opciones que se desplegará --}}
                    <ul class="dropdown-menu" aria-labelledby="navbarDropdownMunicipios">
                        <li>
                            <a class="dropdown-item" href="{{ route('admin.municipios.index') }}">
                                Gestionar Municipios
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item" href="{{ route('admin.instrumentos.index') }}">
                                Catálogo de Instrumentos
                            </a>
                        </li>
                    </ul>
                </li>
            </ul>

            <ul class="navbar-nav ms-auto mb-2 mb-lg-0">
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button"
                        data-bs-toggle="dropdown" aria-expanded="false">
                        {{ Auth::user()->name }}
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                        <li>
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <a class="dropdown-item" href="{{ route('logout') }}"
                                    onclick="event.preventDefault();
                                            this.closest('form').submit();">
                                    {{ __('Cerrar sesión') }}
                                </a>
                            </form>
                        </li>
                    </ul>
                </li>
            </ul>
        </div>
    </div>
</nav>

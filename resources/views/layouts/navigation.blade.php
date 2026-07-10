<nav class="navbar navbar-expand-lg navbar-light bg-white border-bottom shadow-sm py-2">
    <div class="container">
        <!-- Logo -->
        <a class="navbar-brand d-flex align-items-center" href="{{ route('dashboard') }}">
            <img src="{{ asset('img/logo-ceigep.png') }}" alt="Logo" class="d-inline-block align-text-top" style="height: 40px;">
        </a>

        <!-- Hamburger Toggler for mobile -->
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#adminNavbar" aria-controls="adminNavbar" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>

        <!-- Navbar Links -->
        <div class="collapse navbar-collapse" id="adminNavbar">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0 ms-lg-4 gap-2">
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('dashboard') ? 'active fw-bold' : '' }}" href="{{ route('dashboard') }}">
                        {{ __('Inicio') }}
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('admin.catalogos.*') ? 'active fw-bold' : '' }}" href="{{ route('admin.catalogos.index') }}">
                        {{ __('Catálogos') }}
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('admin.datos.*') ? 'active fw-bold' : '' }}" href="{{ route('admin.datos.index') }}">
                        {{ __('Datos') }}
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('admin.import.*') ? 'active fw-bold' : '' }}" href="{{ route('admin.import.index') }}">
                        {{ __('Importación') }}
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('admin.users.*') ? 'active fw-bold' : '' }}" href="{{ route('admin.users.index') }}">
                        {{ __('Usuarios') }}
                    </a>
                </li>
            </ul>

            <!-- Settings Dropdown -->
            <ul class="navbar-nav ms-auto mb-2 mb-lg-0">
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" id="navbarUserDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="fa-solid fa-circle-user me-1 fs-5 text-secondary"></i>
                        <span>{{ Auth::user()->name }}</span>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end shadow-sm" aria-labelledby="navbarUserDropdown">
                        <li>
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <a class="dropdown-item text-danger d-flex align-items-center" href="{{ route('logout') }}"
                                   onclick="event.preventDefault(); this.closest('form').submit();">
                                    <i class="fa-solid fa-right-from-bracket me-2"></i>
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

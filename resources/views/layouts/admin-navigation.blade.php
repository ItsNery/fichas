<aside class="admin-sidebar" id="adminSidebar">
    <div class="sidebar-header">
        <a href="{{ route('inicio') }}"><img src="{{ asset('img/logo-sei.png') }}" alt="SEI" height="36"></a>
        <button class="sidebar-close d-lg-none" type="button" data-sidebar-toggle><i class="fas fa-times"></i></button>
    </div>

    <div class="sidebar-user">
        <div class="d-flex align-items-center gap-2">
            <div class="avatar-circle">{{ strtoupper(substr(Auth::user()->name, 0, 1)) }}</div>
            <div class="sidebar-user-info">
                <div class="sidebar-user-name">{{ Auth::user()->name }}</div>
                <div class="sidebar-user-role">
                    {{ Auth::user()->getRoleNames()->map(fn($role) => str_replace('_', ' ', ucfirst($role)))->join(', ') ?: 'Sin rol' }}
                </div>
            </div>
        </div>
    </div>

    <ul class="sidebar-nav">
        @can('dashboard.ejecutivo')
            <li class="sidebar-item"><a class="sidebar-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}"
                    href="{{ route('admin.dashboard') }}" title="Inicio"><i class="fas fa-home"></i><span>Inicio</span></a>
            </li>
        @endcan
        @can('catalogos.ver')
            <li class="sidebar-item"><a class="sidebar-link {{ request()->routeIs('admin.catalogos.*') ? 'active' : '' }}"
                    href="{{ route('admin.catalogos.index') }}" title="Catálogos"><i
                        class="fas fa-book"></i><span>Catálogos</span></a></li>
        @endcan
        @can('datos.ver')
            <li class="sidebar-item"><a class="sidebar-link {{ request()->routeIs('admin.datos.*') ? 'active' : '' }}"
                    href="{{ route('admin.datos.index') }}" title="Datos"><i
                        class="fas fa-chart-bar"></i><span>Datos</span></a></li>
        @endcan
        @can('configuracion-fichas.ver')
            <li class="sidebar-item"><a
                    class="sidebar-link {{ request()->routeIs('admin.configuracion-fichas.*') ? 'active' : '' }}"
                    href="{{ route('admin.configuracion-fichas.index') }}" title="Config. Fichas"><i
                        class="fas fa-cog"></i><span>Config. Fichas</span></a></li>
        @endcan
        @can('auditoria.ver')
            <li class="sidebar-item"><a class="sidebar-link {{ request()->routeIs('admin.auditoria.*') ? 'active' : '' }}"
                    href="{{ route('admin.auditoria.index') }}" title="Auditoría"><i
                        class="fas fa-clipboard-list"></i><span>Auditoría</span></a></li>
        @endcan
        @can('diccionario.ver')
            <li class="sidebar-item"><a class="sidebar-link {{ request()->routeIs('admin.diccionario.*') ? 'active' : '' }}"
                    href="{{ route('admin.diccionario.index') }}" title="Diccionario"><i
                        class="fas fa-book-open"></i><span>Diccionario</span></a></li>
        @endcan
        @can('salud-datos.ver')
            <li class="sidebar-item"><a class="sidebar-link {{ request()->routeIs('admin.salud-datos*') ? 'active' : '' }}"
                    href="{{ route('admin.salud-datos') }}" title="Salud"><i
                        class="fas fa-heartbeat"></i><span>Salud</span></a></li>
        @endcan
        @if(Auth::user()->canAny(['datos.importar', 'catalogos.importar', 'instrumentos.importar']))
            <li class="sidebar-item"><a class="sidebar-link {{ request()->routeIs('admin.import.*') ? 'active' : '' }}"
                    href="{{ route('admin.import.index') }}" title="Importación"><i
                        class="fas fa-upload"></i><span>Importación</span></a></li>
        @endif
        @can('datos.ver')
            <li class="sidebar-item"><a class="sidebar-link {{ request()->routeIs('admin.lotes-datos.*') ? 'active' : '' }}"
                    href="{{ route('admin.lotes-datos.index') }}" title="Lotes de datos"><i
                        class="fas fa-box-archive"></i><span>Lotes de datos</span></a></li>
        @endcan
        @can('usuarios.ver')
            <li class="sidebar-item"><a class="sidebar-link {{ request()->routeIs('admin.users.*') ? 'active' : '' }}"
                    href="{{ route('admin.users.index') }}" title="Usuarios"><i
                        class="fas fa-users"></i><span>Usuarios</span></a></li>
        @endcan
        @can('roles.ver')
            <li class="sidebar-item"><a class="sidebar-link {{ request()->routeIs('admin.roles.*') ? 'active' : '' }}"
                    href="{{ route('admin.roles.index') }}" title="Roles"><i
                        class="fas fa-user-shield"></i><span>Roles</span></a></li>
        @endcan
        @can('permisos.ver')
            <li class="sidebar-item"><a class="sidebar-link {{ request()->routeIs('admin.permissions.*') ? 'active' : '' }}"
                    href="{{ route('admin.permissions.index') }}" title="Permisos"><i
                        class="fas fa-key"></i><span>Permisos</span></a></li>
        @endcan
    </ul>

    @if(Auth::user()->can('municipios.ver') || Auth::user()->can('instrumentos.ver'))
        <hr class="sidebar-divider">
        <ul class="sidebar-nav">
            <li class="sidebar-item"><span class="sidebar-label">Territorio</span></li>
            @can('municipios.ver')
                <li class="sidebar-item"><a class="sidebar-link {{ request()->routeIs('admin.municipios.*') ? 'active' : '' }}"
                        href="{{ route('admin.municipios.index') }}" title="Gestionar Municipios"><i
                            class="fas fa-city"></i><span>Municipios</span></a></li>
            @endcan
            @can('instrumentos.ver')
                <li class="sidebar-item"><a
                        class="sidebar-link {{ request()->routeIs('admin.instrumentos.*') ? 'active' : '' }}"
                        href="{{ route('admin.instrumentos.index') }}" title="Instrumentos"><i
                            class="fas fa-tools"></i><span>Instrumentos</span></a></li>
            @endcan
        </ul>
    @endif

    <div class="sidebar-footer">
        <form method="POST" action="{{ route('logout') }}" id="logout-form">
            @csrf
            <a class="sidebar-link text-danger" href="{{ route('logout') }}" title="Cerrar sesión"
                onclick="event.preventDefault(); document.getElementById('logout-form').submit();"><i
                    class="fas fa-sign-out-alt"></i><span>Cerrar sesión</span></a>
        </form>
    </div>
</aside>
<div class="sidebar-overlay" id="sidebarOverlay" data-sidebar-toggle></div>
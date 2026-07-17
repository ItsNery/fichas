<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-g">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title') | Administración: Fichas de Información Municipal y Regional</title>

    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700&display=swap">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" />

    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>

    <link rel="stylesheet" href="https://cdn.datatables.net/2.0.8/css/dataTables.bootstrap5.css">

    <script src="https://cdn.datatables.net/2.0.8/js/dataTables.js"></script>
    <script src="https://cdn.datatables.net/2.0.8/js/dataTables.bootstrap5.js"></script>

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" />
    <link rel="stylesheet" href="{{ asset('css/custom.css') }}">
</head>

<body class="font-sans antialiased bg-light">

    <div class="admin-wrapper">
        @include('layouts.admin-navigation')

        <div class="admin-content">
            <nav class="admin-topbar">
                <button class="btn btn-sm btn-outline-secondary border-0" type="button" data-sidebar-toggle>
                    <i class="fas fa-bars fa-lg"></i>
                </button>
                <span class="admin-topbar-title">@yield('title', 'Panel de Administración')</span>
                <span class="ms-auto small text-muted">{{ Auth::user()->name ?? '' }}</span>
            </nav>

            <main class="container-fluid py-4 px-4">
                @isset($slot)
                    {{ $slot }}
                @else
                    @yield('content')
                @endisset
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    @stack('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
            var tooltipList = tooltipTriggerList.map(function(tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl)
            });

            function isDesktop() {
                return window.innerWidth >= 992;
            }

            var STORAGE_KEY = 'adminSidebarCollapsed';

            function applySidebarState() {
                if (!isDesktop()) return;
                var saved = localStorage.getItem(STORAGE_KEY);
                if (saved === 'true') {
                    document.querySelector('.admin-wrapper').classList.add('sidebar-collapsed');
                } else {
                    document.querySelector('.admin-wrapper').classList.remove('sidebar-collapsed');
                }
            }

            applySidebarState();

            document.querySelectorAll('[data-sidebar-toggle]').forEach(function(el) {
                el.addEventListener('click', function() {
                    var sidebar = document.getElementById('adminSidebar');
                    var overlay = document.getElementById('sidebarOverlay');

                    if (isDesktop()) {
                        document.querySelector('.admin-wrapper').classList.toggle('sidebar-collapsed');
                        var collapsed = document.querySelector('.admin-wrapper').classList.contains('sidebar-collapsed');
                        localStorage.setItem(STORAGE_KEY, collapsed);
                    } else {
                        sidebar.classList.toggle('show');
                        overlay.classList.toggle('show');
                    }
                });
            });
        });
    </script>
</body>

</html>

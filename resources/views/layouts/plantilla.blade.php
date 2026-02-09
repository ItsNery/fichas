<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    @php
        $defaults = [
            'title' => 'Portal de información municipal y Regional del Gobierno del Estado de Puebla',
            'description' =>
                'Página informativa del Portal de información municipal y Regional del Gobierno del Estado de Puebla del Estado de Puebla',
            'keywords' =>
                'SEI, Información, Sistema Estatal de Información, Puebla, Estadística, Gobierno, Estado de Puebla',
            'image' => asset('img/mapa_puebla.png'),
            'url' => url()->current(),
            'theme_color' => '#a0153e',
            'author' => 'Ing. Nery Pozos',
        ];

        // --- Valores actuales con fallbacks inteligentes ---
        $currentTitle = View::yieldContent('title', $defaults['title']);
        $currentDescription = View::yieldContent('meta-description', $defaults['description']);
        $currentImage = View::yieldContent('og:image', $defaults['image']);
        $currentUrl = View::yieldContent('canonical-url', $defaults['url']);

        // --- Reutilización inteligente para redes sociales ---
        $socialTitle = View::yieldContent('og-title', $currentTitle . ' | ' . $defaults['title']);
        $socialDescription = View::yieldContent('og-description', $currentDescription);
    @endphp

    {{-- Título optimizado --}}
    <title>{{ $currentTitle }} | {{ $defaults['title'] }}</title>

    {{-- Meta tags esenciales --}}
    <meta name="description" content="{{ $currentDescription }}">
    <meta name="keywords" content="@yield('keywords', $defaults['keywords'])">
    <link rel="canonical" href="{{ $currentUrl }}">
    <meta name="author" content="{{ $defaults['author'] }}">

    {{-- Robots y técnicos --}}
    <meta name="robots" content="@yield('robots', 'index, follow, max-snippet:-1, max-image-preview:large, max-video-preview:-1')">
    <meta name="googlebot" content="index, follow">
    <meta name="bingbot" content="index, follow">
    <meta name="referrer" content="no-referrer-when-downgrade">
    <meta name="format-detection" content="telephone=no">
    <meta name="HandheldFriendly" content="True">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="theme-color" content="{{ $defaults['theme_color'] }}">
    <meta name="google" content="notranslate">
    <meta name="google-site-verification" content="SqonXYfmVQHShjVZynKkL9mNRhYQfCm97J8zW2fYiyc" />

    {{-- Open Graph optimizado --}}
    <meta property="og:title" content="{{ $socialTitle }}">
    <meta property="og:description" content="{{ $socialDescription }}">
    <meta property="og:url" content="{{ $currentUrl }}">
    <meta property="og:image" content="{{ $currentImage }}">
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="{{ $defaults['title'] }}">

    {{-- Twitter Cards optimizado --}}
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="@yield('twitter-title', $socialTitle)">
    <meta name="twitter:description" content="@yield('twitter-description', $socialDescription)">
    <meta name="twitter:image" content="@yield('twitter:image', $currentImage)">

    <!-- Favicon -->
    <link href="{{ asset('img/favicon.ico') }}" rel="icon">
    <link rel="shortcut icon" href="{{ asset('img/favicon.ico') }}" type="image/x-icon">

    <!-- Recursos CSS -->
    <link rel="stylesheet" href="{{ asset('css/bootstrap.min.css') }}">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.4.1/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('fontAwesome/css/all.min.css') }}">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <link rel="stylesheet" href="{{ asset('css/estilos.css') }}?v={{ time() }}">

    <!-- Recursos JS -->
    <script src="{{ asset('js/bootstrap.bundle.min.js') }}"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="{{ asset('js/scripts.js') }}"></script>

    <script>
        window.APP_URL = "{{ url('/') }}";
    </script>

    @yield('page_css')
    @yield('jss')
    @yield('css')
</head>

<body>
    <!-- Loader -->
    <div class="loader-section" role="status" aria-label="Cargando">
        <span class="loader"></span>
    </div>

    <!-- Navigation -->
    @auth
        <nav class="navbar navbar-expand-md navbar-light bg-white shadow-sm" aria-label="Navegación principal">
            <div class="container">
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse"
                    data-bs-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false"
                    aria-label="Alternar navegación">
                    <span class="navbar-toggler-icon"></span>
                </button>

                <div class="collapse navbar-collapse" id="navbarSupportedContent">
                    <ul class="navbar-nav ms-auto">
                        @guest
                            @if (Route::has('login'))
                                <li class="nav-item">
                                    <a class="nav-link" href="{{ route('login') }}">
                                        <i class="bi bi-box-arrow-in-right me-1"></i>{{ __('Iniciar Sesión') }}
                                    </a>
                                </li>
                            @endif
                        @else
                            <li class="nav-item dropdown">
                                <a id="navbarDropdown" class="nav-link dropdown-toggle" href="#" role="button"
                                    data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false" v-pre>
                                    <i class="bi bi-person-circle me-1"></i>{{ Auth::user()->name }}
                                </a>

                                <div class="dropdown-menu dropdown-menu-end" aria-labelledby="navbarDropdown">
                                    <a class="dropdown-item" href="{{ route('admin.dashboard') }}">
                                        <i class="bi bi-speedometer2 me-2"></i>{{ __('Panel de Control') }}
                                    </a>
                                    <div class="dropdown-divider"></div>
                                    <a class="dropdown-item text-danger" href="{{ route('logout') }}"
                                        onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                                        <i class="bi bi-box-arrow-right me-2"></i>{{ __('Cerrar Sesión') }}
                                    </a>
                                    <form id="logout-form" action="{{ route('logout') }}" method="POST" class="d-none">
                                        @csrf
                                    </form>
                                </div>
                            </li>
                        @endguest
                    </ul>
                </div>
            </div>
        </nav>
    @endauth

    <!-- Main Content -->
    <main id="main-content">
        @include('layouts.header')

        @yield('content')

        @include('layouts.footer')
    </main>

    <!-- Scroll to Top Button -->
    <button id="scrollTopBtn" class="scroll-top-btn" title="Volver arriba"
        aria-label="Volver al inicio de la página">
        <svg width="40" height="40" viewBox="0 0 40 40" aria-hidden="true">
            <g class="progress-ring-wrapper">
                <circle class="progress-ring__bg" cx="20" cy="20" r="18" />
                <circle class="progress-ring__circle" cx="20" cy="20" r="18" />
            </g>
            <polyline class="arrow-up" points="16,22 20,18 24,22" />
        </svg>
    </button>

    <!-- Scripts -->
    @stack('scripts')

    <!-- Leaflet JS -->
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

    <!-- Scroll to Top Functionality -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const scrollTopBtn = document.getElementById('scrollTopBtn');
            const loaderSection = document.querySelector('.loader-section');

            // Hide loader when page is loaded
            window.addEventListener('load', function() {
                if (loaderSection) {
                    loaderSection.style.display = 'none';
                }
            });

            // Fallback to hide loader after 3 seconds
            setTimeout(() => {
                if (loaderSection && loaderSection.style.display !== 'none') {
                    loaderSection.style.display = 'none';
                }
            }, 3000);

            // Scroll to top functionality
            if (scrollTopBtn) {
                window.addEventListener('scroll', function() {
                    if (window.pageYOffset > 300) {
                        scrollTopBtn.classList.add('visible');
                    } else {
                        scrollTopBtn.classList.remove('visible');
                    }
                });

                scrollTopBtn.addEventListener('click', function() {
                    window.scrollTo({
                        top: 0,
                        behavior: 'smooth'
                    });
                });
            }
        });
    </script>
</body>


</html>

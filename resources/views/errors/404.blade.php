@extends('layouts.plantilla')
@php
    // Valores específicos para 404
    $pageTitle = '404 - Página no encontrada';
    $pageDescription = 'Lamentablemente se nos perdió esta página. Prueba las opciones de más abajo.';
    $currentUrl = url()->current();
@endphp

@section('title', $pageTitle)
@section('meta-description', $pageDescription)
@section('canonical-url', $currentUrl)

{{-- Open Graph --}}
@section('og-title', $pageTitle)
@section('og-description', $pageDescription)
@section('og:url', $currentUrl)
@section('og:image', asset('img/mapa_puebla.png'))

{{-- Twitter --}}
@section('twitter-title', $pageTitle)
@section('twitter-description', $pageDescription)
@section('twitter:image', asset('img/mapa_puebla.png'))

@section('content')


    <div class="container  d-flex align-items-center justify-content-center">
        <div class="row align-items-center">

            {{-- Columna para la Imagen --}}
            <div class="col-md-6 text-center">
                <img src="{{ asset('img/404.png') }}" alt="" class="w-100">
            </div>

            {{-- Columna para el Texto --}}
            <div class="col-md-6">
                <h1 class="display-1 fw-bold custom-text-primary">404</h1>
                <h2 class="fw-bold">Página No Encontrada</h2>
                <p class="lead text-muted">
                    ¡Auxilio! Parece que esta página se perdió en el mapa.
                </p>
                <p>
                    Es posible que el enlace que seguiste esté roto, que la página haya sido eliminada o que se haya movido
                    a una nueva ubicación.
                </p>

                <h5 class="mt-4">¿Qué puedes hacer ahora?</h5>
                <ul class="list-unstyled">
                    <li class="mb-2">
                        <i class="fas fa-arrow-left me-2 text-muted"></i>
                        <a href="javascript:history.back()" class="text-decoration-none">Regresar a la página anterior</a>
                    </li>
                    <li class="mb-2">
                        <i class="fas fa-home me-2 text-muted"></i>
                        <a href="{{ url('/') }}" class="text-decoration-none">Ir a la página de inicio</a>
                    </li>
                    <li class="mb-2">
                        <i class="fas fa-search me-2 text-muted"></i>
                        <a href="{{ route('fichas.index') }}" class="text-decoration-none">Explorar los indicadores</a>
                    </li>
                </ul>
            </div>

        </div>
    </div>
@endsection

@extends('layouts.plantilla')
@php
    // Valores específicos para 404
    $pageTitle = '419 - Página Expirada';
    $pageDescription = 'Lamentablemente tu sesión ha expirado. Prueba las opciones de más abajo.';
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
    <div class="container d-flex align-items-center justify-content-center">
        <div class="row align-items-center">

            {{-- Columna para la Imagen --}}
            <div class="col-md-6 text-center">
                <img src="{{ asset('img/419.png') }}" alt="" class="w-100 py-5 px-5">
            </div>

            {{-- Columna para el Texto --}}
            <div class="col-md-6">
                <h1 class="display-1 fw-bold text-warning">419</h1>
                <h2 class="fw-bold">Página Expirada</h2>
                <p class="lead text-muted">
                    Tu sesión ha expirado, probablemente por inactividad.
                </p>
                <p>
                    Por razones de seguridad, tu "pase" temporal para enviar formularios ha vencido.
                </p>

                <h5 class="mt-4">¿Qué puedes hacer ahora?</h5>
                <ul class="list-unstyled">
                    <li class="mb-2">
                        <i class="fas fa-arrow-left me-2 text-muted"></i>
                        <a href="{{ url()->previous() }}" class="text-decoration-none fw-bold">
                            Regresa a la página anterior y recárgala
                        </a>
                    </li>
                    <li class="mb-2">
                        <i class="fas fa-home me-2 text-muted"></i>
                        <a href="{{ url('/') }}" class="text-decoration-none">Ir a la página de inicio</a>
                    </li>
                </ul>
            </div>

        </div>
    </div>
@endsection

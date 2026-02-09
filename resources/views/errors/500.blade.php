@extends('layouts.plantilla')
@php
    // Valores específicos para 404
    $pageTitle = '500 - Error del Servidor';
    $pageDescription = 'Lamentablemente algo se perdió en el servidor. Prueba las opciones de más abajo';
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
                <img src="{{ asset('img/500.png') }}" alt="" class="w-100">
            </div>

            {{-- Columna para el Texto --}}
            <div class="col-md-6">
                <h1 class="display-1 fw-bold custom-text-secondary">500</h1>
                <h2 class="fw-bold">Error Interno del Servidor</h2>
                <p class="lead text-muted">
                    ¡Ups! Parece que algo se rompió en nuestro motor de datos.
                </p>
                <p>
                    No te preocupes, no es tu culpa. Ya hemos sido notificados automáticamente sobre este inconveniente y
                    estamos trabajando para solucionarlo.
                </p>

                <h5 class="mt-4">¿Qué puedes hacer ahora?</h5>
                <ul class="list-unstyled">
                    <li class="mb-2">
                        <i class="fas fa-redo me-2 text-muted"></i>
                        <a href="javascript:location.reload()" class="text-decoration-none">Intentar recargar la página</a>
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

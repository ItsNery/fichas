@extends('layouts.plantilla')
@php
    // Valores específicos para 503
    $pageTitle = '503 - Servicio no disponible';
    $pageDescription = 'Estamos realizando labores de mantenimiento o mejoras en la plataforma. Volveremos a estar en línea muy pronto.';
    $currentUrl = url()->current();
@endphp

@section('title', $pageTitle)
@section('meta-description', $pageDescription)
@section('canonical-url', $currentUrl)
@section('robots', 'noindex, nofollow')

{{-- Open Graph --}}
@section('og-title', $pageTitle)
@section('og-description', $pageDescription)
@section('og:url', $currentUrl)
@section('og:image', asset('img/503.svg'))

{{-- Twitter --}}
@section('twitter-title', $pageTitle)
@section('twitter-description', $pageDescription)
@section('twitter:image', asset('img/503.svg'))

@section('content')
    <div class="container d-flex align-items-center justify-content-center">
        <div class="row align-items-center">

            {{-- Columna para la Imagen --}}
            <div class="col-md-6 text-center">
                <img src="{{ asset('img/503.svg') }}" alt="Servicio no disponible o en mantenimiento" class="w-100 py-4 px-3" onerror="this.onerror=null;this.src='{{ asset('img/500.png') }}';">
            </div>

            {{-- Columna para el Texto --}}
            <div class="col-md-6">
                <h1 class="display-1 fw-bold custom-text-secondary">503</h1>
                <h2 class="fw-bold">Servicio No Disponible</h2>
                <p class="lead text-muted">
                    Estamos realizando tareas de mantenimiento y actualización en la plataforma.
                </p>

                @if (isset($exception) && filled($exception->getMessage()))
                    <div class="alert alert-warning border-0 shadow-sm py-2 px-3 mb-3">
                        <i class="fas fa-info-circle me-1"></i> {{ $exception->getMessage() }}
                    </div>
                @else
                    <p>
                        El portal se encuentra temporalmente fuera de línea mientras aplicamos mejoras programadas en el sistema para brindarte una mejor experiencia.
                    </p>
                @endif

                <p class="text-muted">
                    Agradecemos tu paciencia y comprensión. El servicio se reanudará en breve.
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
                    <li class="mb-2">
                        <i class="fas fa-arrow-left me-2 text-muted"></i>
                        <a href="javascript:history.back()" class="text-decoration-none">Regresar a la página anterior</a>
                    </li>
                </ul>
            </div>

        </div>
    </div>
@endsection

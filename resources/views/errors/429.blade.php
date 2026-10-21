@extends('layouts.plantilla')

@php
    $pageTitle = '429 - Demasiadas solicitudes';
    $pageDescription = 'Recibimos demasiadas solicitudes en poco tiempo. Espera un momento antes de intentarlo nuevamente.';
    $currentUrl = url()->current();
@endphp

@section('title', $pageTitle)
@section('meta-description', $pageDescription)
@section('canonical-url', $currentUrl)
@section('robots', 'noindex, nofollow')

@section('og-title', $pageTitle)
@section('og-description', $pageDescription)
@section('og:url', $currentUrl)
@section('og:image', asset('img/503.svg'))

@section('twitter-title', $pageTitle)
@section('twitter-description', $pageDescription)
@section('twitter:image', asset('img/503.svg'))

@section('content')
    <div class="container d-flex align-items-center justify-content-center">
        <div class="row align-items-center">
            <div class="col-md-6 text-center">
                <img src="{{ asset('img/503.svg') }}" alt="Solicitud temporalmente limitada" class="w-100 py-4 px-3"
                    onerror="this.onerror=null;this.src='{{ asset('img/500.png') }}';">
            </div>

            <div class="col-md-6">
                <h1 class="display-1 fw-bold custom-text-secondary">429</h1>
                <h2 class="fw-bold">Demasiadas solicitudes</h2>
                <p class="lead text-muted">
                    Estamos atendiendo muchas solicitudes en este momento.
                </p>
                <p>
                    Para mantener estable la consulta de información, limitamos temporalmente las peticiones repetidas.
                    Espera un momento antes de volver a intentarlo.
                </p>

                <h5 class="mt-4">¿Qué puedes hacer ahora?</h5>
                <ul class="list-unstyled">
                    <li class="mb-2">
                        <i class="fas fa-clock me-2 text-muted"></i>
                        Esperar unos segundos antes de volver a cargar la página.
                    </li>
                    <li class="mb-2">
                        <i class="fas fa-redo me-2 text-muted"></i>
                        <a href="javascript:location.reload()" class="text-decoration-none">Intentar de nuevo</a>
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

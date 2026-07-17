@extends('layouts.plantilla')

@php
    $pageTitle = '403 - Acceso restringido';
    $pageDescription = 'Tu cuenta no tiene permisos para acceder a este recurso.';
    $currentUrl = url()->current();
@endphp

@section('title', $pageTitle)
@section('meta-description', $pageDescription)
@section('canonical-url', $currentUrl)
@section('robots', 'noindex, nofollow')

@section('og-title', $pageTitle)
@section('og-description', $pageDescription)
@section('og:url', $currentUrl)
@section('og:image', asset('img/403.svg'))

@section('twitter-title', $pageTitle)
@section('twitter-description', $pageDescription)
@section('twitter:image', asset('img/403.svg'))

@section('content')
    <div class="container d-flex align-items-center justify-content-center">
        <div class="row align-items-center">
            <div class="col-md-6 text-center">
                <img src="{{ asset('img/403.svg') }}" alt="Acceso restringido" class="w-100 py-5 px-5">
            </div>

            <div class="col-md-6">
                <h1 class="display-1 fw-bold custom-text-primary">403</h1>
                <h2 class="fw-bold">Acceso Restringido</h2>
                <p class="lead text-muted">
                    Tu cuenta está activa, pero no tiene permiso para consultar esta sección.
                </p>
                <p>
                    Si necesitas acceder a este recurso, solicita al administrador que revise los permisos asignados a tu rol.
                </p>

                <h5 class="mt-4">¿Qué puedes hacer ahora?</h5>
                <ul class="list-unstyled">
                    @auth
                        <li class="mb-2">
                            <i class="bi bi-grid me-2 text-muted"></i>
                            <a href="{{ route('dashboard') }}" class="text-decoration-none">Ir a mi panel de trabajo</a>
                        </li>
                    @else
                        <li class="mb-2">
                            <i class="bi bi-box-arrow-in-right me-2 text-muted"></i>
                            <a href="{{ route('login') }}" class="text-decoration-none">Iniciar sesión</a>
                        </li>
                    @endauth
                    <li class="mb-2">
                        <i class="bi bi-arrow-left me-2 text-muted"></i>
                        <a href="{{ url()->previous() }}" class="text-decoration-none">Regresar a la página anterior</a>
                    </li>
                    <li class="mb-2">
                        <i class="bi bi-house me-2 text-muted"></i>
                        <a href="{{ url('/') }}" class="text-decoration-none">Ir a la página de inicio</a>
                    </li>
                </ul>
            </div>
        </div>
    </div>
@endsection

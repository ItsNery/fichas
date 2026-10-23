@extends('layouts.plantilla')

@section('title', 'Verificación de seguridad')
@section('robots', 'noindex, nofollow')

@section('content')
    <section class="turnstile turnstile--challenge" aria-labelledby="titulo-verificacion">
        <div class="container">
            <div class="turnstile__panel">
                <svg class="turnstile__mark" viewBox="0 0 48 48" fill="none" aria-hidden="true" focusable="false">
                    <path d="M24 4 39 10v11c0 10.2-6.4 18.8-15 23-8.6-4.2-15-12.8-15-23V10L24 4Z" stroke="currentColor" stroke-width="2.5"/>
                    <path d="m17 24 4.5 4.5L31 19" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
                <h1 class="turnstile__title" id="titulo-verificacion">Verificación de seguridad</h1>
                <p class="turnstile__lead">Completa esta verificación para continuar con tu consulta. Ayuda a mantener disponibles las consultas y descargas del portal.</p>

                <form class="turnstile__form" method="POST" action="{{ route('turnstile.verify') }}" id="form-challenge">
                    @csrf
                    <input type="hidden" name="redirect" value="{{ $redirect }}">
                    <div class="turnstile__challenge-frame">
                        <div class="cf-turnstile" data-sitekey="{{ $siteKey }}" data-callback="liberarBoton" data-error-callback="falloCaptcha" data-theme="light"></div>
                    </div>
                    @error('cf-turnstile-response')
                        <p class="turnstile__error" id="error-token" role="alert">{{ $message }}</p>
                    @enderror
                    <p class="turnstile__error" id="error-captcha" role="alert" hidden></p>
                    <p class="turnstile__status" id="estado-verificacion" aria-live="polite">Completa la verificación para habilitar la continuación.</p>
                    <button class="turnstile__button" type="submit" id="btn-enviar" disabled>Continuar</button>
                </form>
            </div>
        </div>
    </section>
@endsection

@push('scripts')
    <script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>
    <script>
        const form = document.getElementById('form-challenge');
        const button = document.getElementById('btn-enviar');
        const status = document.getElementById('estado-verificacion');
        const error = document.getElementById('error-captcha');

        function liberarBoton() {
            button.disabled = false;
            status.textContent = 'Verificación completada. Ya puedes continuar.';
            error.hidden = true;
        }

        function falloCaptcha() {
            button.disabled = true;
            status.textContent = 'La verificación no se completó.';
            error.textContent = 'Recarga la página e intenta de nuevo.';
            error.hidden = false;
        }

        form.addEventListener('submit', function () {
            button.disabled = true;
            button.textContent = 'Verificando…';
            status.textContent = 'Estamos confirmando la verificación.';
        });
    </script>
@endpush

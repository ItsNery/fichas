@extends('layouts.plantilla')

@section('title', 'Preparando descarga')
@section('robots', 'noindex, nofollow')

@section('content')
    <section class="turnstile turnstile--download" aria-labelledby="titulo-descarga" aria-live="polite">
        <div class="container">
            <div class="turnstile__panel">
                <svg class="turnstile__mark" viewBox="0 0 48 48" fill="none" aria-hidden="true">
                    <path d="M11 5h17l9 9v29H11V5Z" stroke="currentColor" stroke-width="2.5" stroke-linejoin="round"/>
                    <path d="M28 5v10h9M24 18v13m0 0-5-5m5 5 5-5" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
                <h1 class="turnstile__title" id="titulo-descarga">Preparando tu PDF</h1>
                <p class="turnstile__lead">La descarga comenzará en un momento. Conserva esta pestaña abierta mientras se prepara el archivo.</p>
                <p class="turnstile__status" id="estado-descarga">Cuando el archivo esté listo, esta pestaña intentará cerrarse automáticamente.</p>
                <button class="turnstile__button" type="button" id="cerrar">Cerrar esta pestaña</button>
            </div>
        </div>
    </section>

    <iframe class="turnstile__download-frame" id="pdf-descarga" src="{{ $downloadUrl }}" title="Descarga de PDF"></iframe>
@endsection

@push('scripts')
    <script>
        const frame = document.getElementById('pdf-descarga');
        const state = document.getElementById('estado-descarga');
        const closeButton = document.getElementById('cerrar');
        let attempted = false;

        function closeWindow() {
            if (attempted) return;
            attempted = true;
            state.textContent = 'La descarga está lista. Cerrando esta pestaña…';
            window.close();
            window.setTimeout(function () {
                state.textContent = 'La descarga está lista. Puedes cerrar esta pestaña.';
            }, 900);
        }

        frame.addEventListener('load', function () {
            window.setTimeout(closeWindow, 350);
        });
        closeButton.addEventListener('click', closeWindow);
    </script>
@endpush

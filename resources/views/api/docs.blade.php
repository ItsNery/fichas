@extends('layouts.plantilla')

@section('title', 'API Pública - Documentación')
@section('meta-description', 'Documentación de la API pública para acceder a municipios, indicadores, metadatos y datos estadísticos.')

@section('content')
<div class="container my-5">
    <div class="text-center mb-5">
        <h1 class="display-5">Documentación de la API Pública</h1>
        <p class="text-muted">Consulta los endpoints públicos para descargar y explorar datos municipales.</p>
    </div>
    <div id="redoc-container"></div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.redoc.ly/redoc/latest/bundles/redoc.standalone.js"></script>
<script>
    Redoc.init('{{ route('api.openapi') }}', {
        scrollYOffset: 80,
        theme: {
            colors: {
                primary: {
                    main: '#1d4ed8'
                }
            }
        }
    }, document.getElementById('redoc-container'));
</script>
@endpush

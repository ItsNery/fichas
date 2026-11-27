@extends('layouts.plantilla')

@section('title', 'API Pública - Documentación')
@section('meta-description', 'Documentación de la API pública para acceder a municipios, indicadores, metadatos y datos estadísticos.')

@section('content')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swagger-ui-dist@5.17.14/swagger-ui.css">
<link rel="stylesheet" href="{{ asset('css/api-docs.css') }}">
<div class="container my-5 api-docs">
    <x-public-section-header title="Documentación de la API pública"
        description="Consulta los recursos públicos para explorar datos municipales." />

    <div class="api-docs__intro">
        <p>Las pruebas interactivas realizan peticiones reales y están sujetas a los límites de la API.</p>
        <div class="api-docs__links">
            <a href="{{ route('datos-abiertos.index') }}"><i class="fas fa-arrow-left" aria-hidden="true"></i> Volver a Datos Abiertos</a>
            <a href="{{ route('api.openapi') }}">Consultar especificación OpenAPI <i class="fas fa-arrow-right" aria-hidden="true"></i></a>
        </div>
    </div>

    <div id="swagger-ui">
        <p class="api-docs__loading" role="status">Cargando documentación interactiva…</p>
    </div>
    <noscript><p>Activa JavaScript para ver la documentación interactiva o consulta la especificación OpenAPI mediante el enlace anterior.</p></noscript>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/swagger-ui-dist@5.17.14/swagger-ui-bundle.js"></script>
<script src="{{ asset('js/swagger-es.js') }}"></script>
<script>
    if (typeof SwaggerUIBundle === 'function') {
        SwaggerUIBundle({
            url: @json(route('api.openapi')),
            dom_id: '#swagger-ui',
            deepLinking: true,
            validatorUrl: null,
            queryConfigEnabled: false
        });
    } else {
        document.getElementById('swagger-ui').textContent = 'No fue posible cargar la documentación interactiva. Puedes consultar la especificación OpenAPI en el enlace anterior.';
    }
</script>
@endpush

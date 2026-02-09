{{-- resources/views/components/page-header.blade.php --}}
@props([
'title',
'subtitle' => null, // El subtítulo es opcional
'icon' => 'fa-solid fa-layer-group' // Un icono por defecto por si se te olvida ponerlo
])

<div class="page-header-solid">
    <div>
        <h2>{{ $title }}</h2>
        @if($subtitle)
        <p class="mb-0 text-white-50 small">{{ $subtitle }}</p>
        @endif
    </div>
    <div>
        {{-- Aquí concatenamos las clases que pasas con las clases base del estilo --}}
        <i class="{{ $icon }} text-white opacity-25 fa-3x"></i>
    </div>
</div>
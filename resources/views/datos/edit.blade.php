<x-admin-layout>
    @section('title', 'Proponer Edición de Dato')
    <x-page-header title="Proponer Edición de Dato" subtitle="El cambio será enviado a revisión antes de publicarse" icon="fa-solid fa-pen-to-square" />
    @if ($message = Session::get('success'))
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                Swal.fire({
                    icon: 'success',
                    title: '{{ $message }}'
                });
            });
        </script>
    @endif
    <div class="container py-4">
        <div class="card-panel">
            <div class="card-body p-4">
                <p><strong>Municipio:</strong> {{ $dato->municipio->nombre }}</p>
                <p><strong>Variable:</strong> {{ $dato->variable->nombre_amigable }}</p>
                <p><strong>Año:</strong> {{ $dato->anio }}</p>

                <form method="POST" action="{{ route('admin.datos.update', $dato) }}">
                    @csrf
                    @method('PUT')

                    <div class="mb-3">
                        <label for="valor" class="form-label">Valor</label>
                        <input type="text" class="form-control @error('valor') is-invalid @enderror" id="valor"
                            name="valor" value="{{ old('valor', $dato->valor) }}">
                        @error('valor')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <button type="submit" class="btn btn-custom-primary"><i class="fa-solid fa-paper-plane me-2"></i>Enviar a revisión</button>
                    <a href="{{ route('admin.datos.index') }}" class="btn btn-outline-secondary"><i class="fa-solid fa-arrow-left me-1"></i>Cancelar</a>
                </form>
            </div>
        </div>
    </div>
</x-admin-layout>

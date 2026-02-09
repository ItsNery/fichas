<x-admin-layout>
    @section('title', 'Sociedades Civiles: Inicio')
    <x-slot name="header">
        <h2 class="h4 font-weight-bold">
            {{ __('Gestión de Datos Históricos') }}
        </h2>
    </x-slot>
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
    <div class="container">
        <div class="card">
            <div class="card-body">
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

                    <button type="submit" class="btn btn-primary">Actualizar Dato</button>
                    <a href="{{ route('admin.datos.index') }}" class="btn btn-secondary">Cancelar</a>
                </form>
            </div>
        </div>
    </div>
</x-admin-layout>

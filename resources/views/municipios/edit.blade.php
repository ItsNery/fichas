<x-admin-layout>
    @section('title', 'Editar Municipio: ' . $municipio->nombre)

    <x-page-header
        title="Editar Municipio"
        subtitle="Actualización de información general y visual del municipio"
        icon="fa-solid fa-pen-to-square" />

    <div class="container py-4">
        <div class="row justify-content-center">
            <div class="col-lg-10">
                <div class="card-panel">
                    <div class="card-body p-4">
                        <form action="{{ route('admin.municipios.update', $municipio) }}" method="POST">
                            @csrf
                            @method('PUT')

                            <div class="row g-4">
                                {{-- Información Básica --}}
                                <div class="col-md-12">
                                    <h5 class="fw-bold text-vino mb-3 border-bottom pb-2">
                                        <i class="fa-solid fa-circle-info me-2 text-dorado"></i>Información Básica
                                    </h5>
                                </div>

                                <div class="col-md-6">
                                    <label for="nombre" class="form-label fw-bold small text-secondary">Nombre del Municipio</label>
                                    <input type="text" name="nombre" id="nombre" class="form-control" value="{{ old('nombre', $municipio->nombre) }}" required>
                                    @error('nombre') <div class="text-danger small">{{ $message }}</div> @enderror
                                </div>

                                <div class="col-md-6">
                                    <label for="cvegeo" class="form-label fw-bold small text-secondary">Clave GEO</label>
                                    <input type="text" name="cvegeo" id="cvegeo" class="form-control font-monospace" value="{{ old('cvegeo', $municipio->cvegeo) }}" required>
                                    @error('cvegeo') <div class="text-danger small">{{ $message }}</div> @enderror
                                </div>

                                <div class="col-md-6">
                                    <label for="cabecera" class="form-label fw-bold small text-secondary">Cabecera Municipal</label>
                                    <input type="text" name="cabecera" id="cabecera" class="form-control" value="{{ old('cabecera', $municipio->cabecera) }}">
                                </div>

                                <div class="col-md-6">
                                    <label for="presidente_municipal" class="form-label fw-bold small text-secondary">Presidente Municipal</label>
                                    <input type="text" name="presidente_municipal" id="presidente_municipal" class="form-control" value="{{ old('presidente_municipal', $municipio->presidente_municipal) }}">
                                </div>

                                <div class="col-md-6">
                                    <label for="periodo_gobierno" class="form-label fw-bold small text-secondary">Periodo de Gobierno</label>
                                    <input type="text" name="periodo_gobierno" id="periodo_gobierno" class="form-control" value="{{ old('periodo_gobierno', $municipio->periodo_gobierno) }}" placeholder="Ej. 2024 - 2027">
                                </div>

                                {{-- Datos Técnicos --}}
                                <div class="col-md-12 mt-5">
                                    <h5 class="fw-bold text-vino mb-3 border-bottom pb-2">
                                        <i class="fa-solid fa-gears me-2 text-dorado"></i>Datos Técnicos
                                    </h5>
                                </div>

                                <div class="col-md-6">
                                    <label for="clima" class="form-label fw-bold small text-secondary">Clima Predominante</label>
                                    <input type="text" name="clima" id="clima" class="form-control" value="{{ old('clima', $municipio->clima) }}">
                                </div>

                                <div class="col-md-6">
                                    <label for="superficie" class="form-label fw-bold small text-secondary">Superficie (km²)</label>
                                    <input type="number" step="0.01" name="superficie" id="superficie" class="form-control" value="{{ old('superficie', $municipio->superficie) }}">
                                </div>

                                {{-- Identidad Visual --}}
                                <div class="col-md-12 mt-5">
                                    <h5 class="fw-bold text-vino mb-3 border-bottom pb-2">
                                        <i class="fa-solid fa-image me-2 text-dorado"></i>Identidad Visual
                                    </h5>
                                </div>

                                <div class="col-md-12">
                                    <label for="banner_image_url" class="form-label fw-bold small text-secondary">URL de Imagen Hero (Banner)</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="fa-solid fa-link"></i></span>
                                        <input type="url" name="banner_image_url" id="banner_image_url" class="form-control" value="{{ old('banner_image_url', $municipio->banner_image_url) }}" placeholder="https://ejemplo.com/imagen.jpg">
                                    </div>
                                    <div class="form-text small">Se utiliza en la cabecera de la ficha municipal.</div>
                                </div>

                                <div class="col-md-12">
                                    <label for="logo_url" class="form-label fw-bold small text-secondary">URL del Logo Municipal</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="fa-solid fa-link"></i></span>
                                        <input type="url" name="logo_url" id="logo_url" class="form-control" value="{{ old('logo_url', $municipio->logo_url) }}" placeholder="https://ejemplo.com/logo.png">
                                    </div>
                                </div>
                            </div>

                            <div class="mt-5 d-flex justify-content-between border-top pt-4">
                                <a href="{{ route('admin.municipios.index') }}" class="btn btn-outline-secondary px-4">
                                    <i class="fa-solid fa-arrow-left me-2"></i>Regresar
                                </a>
                                <button type="submit" class="btn btn-custom-primary px-5">
                                    <i class="fa-solid fa-floppy-disk me-2"></i>Guardar Cambios
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-admin-layout>

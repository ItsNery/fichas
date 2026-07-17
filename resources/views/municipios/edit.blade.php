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
                                {{-- Información General --}}
                                <div class="col-md-12">
                                    <h5 class="fw-bold text-vino mb-3 border-bottom pb-2">
                                        <i class="fa-solid fa-circle-info me-2 text-dorado"></i>Información General
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
                                    <label for="microrregion_id" class="form-label fw-bold small text-secondary">Microrregión</label>
                                    <select name="microrregion_id" id="microrregion_id" class="form-select" required>
                                        <option value="">Seleccionar microrregión</option>
                                        @php
                                            $grupos = $microrregiones->groupBy(fn($mr) => $mr->macrorregion->nombre);
                                        @endphp
                                        @foreach ($grupos as $macroNombre => $mrs)
                                            <optgroup label="{{ $macroNombre }}">
                                                @foreach ($mrs as $mr)
                                                    <option value="{{ $mr->id }}" @selected(old('microrregion_id', $municipio->microrregion_id) == $mr->id)>
                                                        {{ $mr->nombre }}
                                                    </option>
                                                @endforeach
                                            </optgroup>
                                        @endforeach
                                    </select>
                                    @error('microrregion_id') <div class="text-danger small">{{ $message }}</div> @enderror
                                </div>

                                {{-- Datos Geográficos --}}
                                <div class="col-md-12 mt-5">
                                    <h5 class="fw-bold text-vino mb-3 border-bottom pb-2">
                                        <i class="fa-solid fa-earth-americas me-2 text-dorado"></i>Datos Geográficos
                                    </h5>
                                </div>

                                <div class="col-md-6">
                                    <label for="clima" class="form-label fw-bold small text-secondary">Clima Predominante</label>
                                    <select name="clima" id="clima" class="form-select">
                                        <option value="">Sin clasificación</option>
                                        @foreach (['Cálido húmedo', 'Cálido subhúmedo', 'Seco o muy seco', 'Templado o frío (húmedo o subhúmedo)'] as $clima)
                                            <option value="{{ $clima }}" @selected(old('clima', $municipio->clima) === $clima)>{{ $clima }}</option>
                                        @endforeach
                                    </select>
                                    <div class="form-text small">Clasificación de Köppen modificada por E. García, con fuente INEGI/CONAGUA.</div>
                                    @error('clima') <div class="text-danger small">{{ $message }}</div> @enderror
                                </div>

                                <div class="col-md-6">
                                    <label for="superficie" class="form-label fw-bold small text-secondary">Superficie (km²)</label>
                                    <input type="number" step="0.01" name="superficie" id="superficie" class="form-control" value="{{ old('superficie', $municipio->superficie) }}">
                                    <div class="form-text small">Se sincroniza desde el último dato oficial de superficie territorial.</div>
                                    @error('superficie') <div class="text-danger small">{{ $message }}</div> @enderror
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

                                @php $attr = old('banner_attribution', $municipio->banner_attribution ?? []); @endphp
                                <div class="col-md-12 attribution-fields">
                                    <div class="border rounded p-3 bg-light bg-opacity-25">
                                        <div class="d-flex align-items-center justify-content-between mb-2">
                                            <label class="form-label fw-bold small text-secondary mb-0">
                                                <i class="fa-regular fa-rectangle-ad me-1"></i>Atribución de imagen
                                            </label>
                                            <div class="form-check form-check-inline mb-0">
                                                <input type="checkbox" class="form-check-input" id="sin_atribucion" autocomplete="off">
                                                <label class="form-check-label small text-muted" for="sin_atribucion">Sin atribución</label>
                                            </div>
                                        </div>
                                        <div class="row g-2">
                                            <div class="col-md-5">
                                                <input type="text" name="banner_attribution[author]" id="attr_author" class="form-control form-control-sm" placeholder="Autor / Fotógrafo" value="{{ old('banner_attribution.author', $attr['author'] ?? '') }}">
                                            </div>
                                            <div class="col-md-3">
                                                <input type="text" name="banner_attribution[license]" id="attr_license" class="form-control form-control-sm" placeholder="Licencia" value="{{ old('banner_attribution.license', $attr['license'] ?? '') }}">
                                            </div>
                                            <div class="col-md-4">
                                                <input type="url" name="banner_attribution[source_url]" id="attr_source_url" class="form-control form-control-sm" placeholder="URL de la fuente" value="{{ old('banner_attribution.source_url', $attr['source_url'] ?? '') }}">
                                            </div>
                                        </div>
                                        <div class="form-text small mt-1">Los créditos aparecen en la cabecera de la ficha municipal. Se obtienen automáticamente vía Wikimedia Commons al sincronizar banners.</div>
                                    </div>
                                    @error('banner_attribution') <div class="text-danger small">{{ $message }}</div> @enderror
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

    @push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var check = document.getElementById('sin_atribucion');
            var inputs = [
                document.getElementById('attr_author'),
                document.getElementById('attr_license'),
                document.getElementById('attr_source_url'),
            ];

            function toggleInputs(clear) {
                inputs.forEach(function (el) {
                    if (clear) {
                        el.value = '';
                        el.disabled = true;
                    } else {
                        el.disabled = false;
                    }
                });
            }

            // If all are empty on load, disable them
            var allEmpty = inputs.every(function (el) { return el.value === ''; });
            if (allEmpty) {
                check.checked = true;
                toggleInputs(true);
            }

            check.addEventListener('change', function () {
                toggleInputs(this.checked);
            });
        });
    </script>
    @endpush
</x-admin-layout>
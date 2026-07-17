@extends('layouts.admin')

@section('title', 'Editar Metadatos')

@section('content')
<x-page-header
    title="Editar Metadatos"
    subtitle="Documentación y gobernanza del indicador"
    icon="fa-solid fa-file-pen" />

<div class="container py-4">
    @if($errors->any())
        <div class="alert alert-danger d-flex gap-2" role="alert">
            <i class="fa-solid fa-circle-exclamation mt-1"></i>
            <div>
                <strong>Revisa la información capturada.</strong>
                <div class="small">Algunos campos contienen errores de validación.</div>
            </div>
        </div>
    @endif

    <div class="card-panel">
        <div class="card-header-simple d-flex align-items-center justify-content-between flex-wrap gap-2">
            <div>
                <span class="text-uppercase text-muted small fw-bold">Indicador</span>
                <h5 class="text-vino fw-bold mb-0">{{ $indicador->nombre_amigable }}</h5>
            </div>
            <span class="badge bg-light text-secondary border px-3 py-2">
                <i class="fa-solid fa-hashtag me-1 text-dorado"></i>ID {{ $indicador->id }}
            </span>
        </div>
        <div class="card-body p-4">
            <form method="POST" action="{{ route('admin.diccionario.update', $indicador) }}">
                @csrf
                @method('PUT')

                <h6 class="form-section-title">
                    <i class="fa-solid fa-building-shield me-2 text-dorado"></i>Responsabilidad y clasificación
                </h6>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Responsable</label>
                        <input type="text" name="responsable" class="form-control @error('responsable') is-invalid @enderror"
                            value="{{ old('responsable', $indicador->responsable) }}">
                        @error('responsable') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">Periodicidad</label>
                        <select name="periodicidad" class="form-select @error('periodicidad') is-invalid @enderror">
                            <option value="">Seleccionar...</option>
                            <option value="anual" @selected(old('periodicidad', $indicador->periodicidad) == 'anual')>Anual</option>
                            <option value="semestral" @selected(old('periodicidad', $indicador->periodicidad) == 'semestral')>Semestral</option>
                            <option value="trimestral" @selected(old('periodicidad', $indicador->periodicidad) == 'trimestral')>Trimestral</option>
                            <option value="mensual" @selected(old('periodicidad', $indicador->periodicidad) == 'mensual')>Mensual</option>
                            <option value="unica" @selected(old('periodicidad', $indicador->periodicidad) == 'unica')>Única vez</option>
                        </select>
                        @error('periodicidad') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">Clasificación</label>
                        <select name="clasificacion" class="form-select @error('clasificacion') is-invalid @enderror">
                            <option value="publica" @selected(old('clasificacion', $indicador->clasificacion) == 'publica')>Pública</option>
                            <option value="uso_interno" @selected(old('clasificacion', $indicador->clasificacion) == 'uso_interno')>Uso interno</option>
                            <option value="confidencial" @selected(old('clasificacion', $indicador->clasificacion) == 'confidencial')>Confidencial</option>
                        </select>
                        @error('clasificacion') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-12 mt-4">
                        <h6 class="form-section-title mb-0">
                            <i class="fa-solid fa-calendar-check me-2 text-dorado"></i>Vigencia y publicación
                        </h6>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Fecha vigencia inicio</label>
                        <input type="date" name="fecha_vigencia_inicio"
                            class="form-control @error('fecha_vigencia_inicio') is-invalid @enderror"
                            value="{{ old('fecha_vigencia_inicio', $indicador->fecha_vigencia_inicio?->format('Y-m-d')) }}">
                        @error('fecha_vigencia_inicio') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Fecha vigencia fin</label>
                        <input type="date" name="fecha_vigencia_fin"
                            class="form-control @error('fecha_vigencia_fin') is-invalid @enderror"
                            value="{{ old('fecha_vigencia_fin', $indicador->fecha_vigencia_fin?->format('Y-m-d')) }}">
                        @error('fecha_vigencia_fin') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Estado de publicación</label>
                        <select name="estado_publicacion" class="form-select @error('estado_publicacion') is-invalid @enderror">
                            <option value="borrador" @selected(old('estado_publicacion', $indicador->estado_publicacion) == 'borrador')>Borrador</option>
                            <option value="en_revision" @selected(old('estado_publicacion', $indicador->estado_publicacion) == 'en_revision')>En revisión</option>
                            <option value="publicado" @selected(old('estado_publicacion', $indicador->estado_publicacion) == 'publicado')>Publicado</option>
                            <option value="deprecado" @selected(old('estado_publicacion', $indicador->estado_publicacion) == 'deprecado')>Deprecado</option>
                        </select>
                        @error('estado_publicacion') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-12 mt-4">
                        <h6 class="form-section-title mb-0">
                            <i class="fa-solid fa-map-location-dot me-2 text-dorado"></i>Cobertura y normalización
                        </h6>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Cobertura geográfica</label>
                        <select name="cobertura_geografica" class="form-select @error('cobertura_geografica') is-invalid @enderror">
                            <option value="">Seleccionar...</option>
                            <option value="estatal" @selected(old('cobertura_geografica', $indicador->cobertura_geografica) == 'estatal')>Estatal</option>
                            <option value="municipal" @selected(old('cobertura_geografica', $indicador->cobertura_geografica) == 'municipal')>Municipal</option>
                            <option value="localidad" @selected(old('cobertura_geografica', $indicador->cobertura_geografica) == 'localidad')>Localidad</option>
                        </select>
                        @error('cobertura_geografica') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Unidad responsable</label>
                        <input type="text" name="unidad_responsable"
                            class="form-control @error('unidad_responsable') is-invalid @enderror"
                            value="{{ old('unidad_responsable', $indicador->unidad_responsable) }}">
                        @error('unidad_responsable') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Norma técnica SNIEG</label>
                        <input type="text" name="norma_tecnica"
                            class="form-control @error('norma_tecnica') is-invalid @enderror"
                            value="{{ old('norma_tecnica', $indicador->norma_tecnica) }}">
                        @error('norma_tecnica') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-12 mt-4">
                        <h6 class="form-section-title mb-0">
                            <i class="fa-solid fa-flask-vial me-2 text-dorado"></i>Documentación metodológica
                        </h6>
                    </div>

                    <div class="col-12">
                        <label class="form-label">URL metodología</label>
                        <input type="url" name="metodologia_url"
                            class="form-control @error('metodologia_url') is-invalid @enderror"
                            value="{{ old('metodologia_url', $indicador->metodologia_url) }}">
                        @error('metodologia_url') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-12">
                        <label class="form-label">Metodología</label>
                        <textarea name="metodologia" rows="3"
                            class="form-control @error('metodologia') is-invalid @enderror">{{ old('metodologia', $indicador->metodologia) }}</textarea>
                        @error('metodologia') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-12">
                        <label class="form-label">Notas metodológicas</label>
                        <textarea name="notas_metodologicas" rows="3"
                            class="form-control @error('notas_metodologicas') is-invalid @enderror">{{ old('notas_metodologicas', $indicador->notas_metodologicas) }}</textarea>
                        @error('notas_metodologicas') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </div>

                <div class="mt-4 pt-3 border-top d-flex justify-content-end gap-2">
                    <a href="{{ route('admin.diccionario.index') }}" class="btn btn-outline-secondary px-4">
                        <i class="fa-solid fa-arrow-left me-2"></i>Cancelar
                    </a>
                    <button type="submit" class="btn btn-custom-primary px-4 shadow-sm">
                        <i class="fa-solid fa-floppy-disk me-2"></i>Guardar metadatos
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@extends('layouts.admin')

@section('title', $permission->exists ? 'Editar Permiso' : 'Crear Permiso')

@section('content')
<x-page-header :title="$permission->exists ? 'Editar Permiso' : 'Crear Permiso'" subtitle="Definición de una capacidad administrativa" icon="fa-solid fa-key" />
<div class="container py-4">
    <div class="card-panel"><div class="card-body p-4">
        <form method="POST" action="{{ $permission->exists ? route('admin.permissions.update', $permission) : route('admin.permissions.store') }}">
            @csrf @if($permission->exists) @method('PUT') @endif
            <div class="mb-3">
                <label class="form-label">Nombre técnico</label>
                <input name="name" value="{{ old('name', $permission->name) }}" class="form-control @error('name') is-invalid @enderror" required {{ $permission->is_system ? 'readonly' : '' }} placeholder="modulo.accion">
                @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                @if($permission->is_system)<div class="form-text">Este permiso está vinculado al código y no puede renombrarse.</div>@endif
            </div>
            <div class="mb-4">
                <label class="form-label">Descripción</label>
                <input name="description" value="{{ old('description', $permission->description) }}" class="form-control">
            </div>
            <div class="border-top pt-3 d-flex justify-content-end gap-2">
                <a href="{{ route('admin.permissions.index') }}" class="btn btn-outline-secondary"><i class="fa-solid fa-arrow-left me-1"></i>Cancelar</a>
                <button class="btn btn-custom-primary"><i class="fa-solid fa-floppy-disk me-2"></i>Guardar permiso</button>
            </div>
        </form>
    </div></div>
</div>
@endsection

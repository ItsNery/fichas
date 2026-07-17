@extends('layouts.admin')

@section('title', $role->exists ? 'Editar Rol' : 'Crear Rol')

@section('content')
<x-page-header :title="$role->exists ? 'Editar Rol' : 'Crear Rol'" subtitle="Configuración del perfil y sus capacidades" icon="fa-solid fa-user-shield" />

<div class="container py-4">
    <form method="POST" action="{{ $role->exists ? route('admin.roles.update', $role) : route('admin.roles.store') }}">
        @csrf
        @if($role->exists) @method('PUT') @endif
        <div class="card-panel mb-4">
            <div class="card-body p-4">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">Nombre técnico</label>
                        <input name="name" value="{{ old('name', $role->name) }}" class="form-control @error('name') is-invalid @enderror" required {{ $role->is_system ? 'readonly' : '' }}>
                        @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-8">
                        <label class="form-label">Descripción</label>
                        <input name="description" value="{{ old('description', $role->description) }}" class="form-control @error('description') is-invalid @enderror">
                    </div>
                </div>
            </div>
        </div>

        <div class="card-panel">
            <div class="card-header-simple"><h5 class="mb-0 text-vino fw-bold"><i class="fa-solid fa-key me-2 text-dorado"></i>Permisos</h5></div>
            <div class="card-body p-4">
                @php $selected = old('permissions', $role->exists ? $role->permissions->pluck('name')->all() : []); @endphp
                <div class="row g-3">
                    @foreach($permissions->groupBy(fn($permission) => explode('.', $permission->name)[0]) as $module => $modulePermissions)
                        <div class="col-lg-4 col-md-6">
                            <div class="border rounded p-3 h-100">
                                <h6 class="text-uppercase text-vino fw-bold small">{{ str_replace('-', ' ', $module) }}</h6>
                                @foreach($modulePermissions as $permission)
                                    <div class="form-check mb-2">
                                        <input class="form-check-input" type="checkbox" name="permissions[]" value="{{ $permission->name }}" id="p-{{ $permission->id }}"
                                            @checked(in_array($permission->name, $selected)) {{ $role->name === 'super_admin' ? 'disabled' : '' }}>
                                        <label class="form-check-label small" for="p-{{ $permission->id }}">{{ $permission->name }}</label>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </div>
                <div class="border-top mt-4 pt-3 d-flex justify-content-end gap-2">
                    <a href="{{ route('admin.roles.index') }}" class="btn btn-outline-secondary"><i class="fa-solid fa-arrow-left me-1"></i>Cancelar</a>
                    <button class="btn btn-custom-primary"><i class="fa-solid fa-floppy-disk me-2"></i>Guardar rol</button>
                </div>
            </div>
        </div>
    </form>
</div>
@endsection

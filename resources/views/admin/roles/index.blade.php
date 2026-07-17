@extends('layouts.admin')

@section('title', 'Roles')

@section('content')
<x-page-header title="Roles" subtitle="Administración de perfiles institucionales y sus permisos" icon="fa-solid fa-user-shield" />

<div class="container py-4">
    @if(session('success'))
        <div class="alert alert-success"><i class="fa-solid fa-circle-check me-2"></i>{{ session('success') }}</div>
    @endif
    @if($errors->any())
        <div class="alert alert-danger"><i class="fa-solid fa-circle-exclamation me-2"></i>{{ $errors->first() }}</div>
    @endif

    <div class="card-panel">
        <div class="card-body p-4">
            <div class="d-flex justify-content-between gap-3 flex-wrap mb-4">
                <form method="GET" class="d-flex gap-2 flex-grow-1" style="max-width: 600px;">
                    <div class="input-group">
                        <span class="input-group-text bg-white"><i class="fa-solid fa-search"></i></span>
                        <input name="search" value="{{ $search }}" class="form-control" placeholder="Buscar rol...">
                    </div>
                    <button class="btn btn-custom-primary">Buscar</button>
                </form>
                @can('roles.crear')
                    <a href="{{ route('admin.roles.create') }}" class="btn btn-custom-primary">
                        <i class="fa-solid fa-plus me-2"></i>Nuevo rol
                    </a>
                @endcan
            </div>

            <div class="table-responsive">
                <table class="table table-custom align-middle">
                    <thead><tr><th>Rol</th><th>Descripción</th><th class="text-center">Usuarios</th><th class="text-center">Permisos</th><th class="text-center">Acciones</th></tr></thead>
                    <tbody>
                        @forelse($roles as $role)
                            <tr>
                                <td><span class="fw-bold text-vino">{{ $role->name }}</span>@if($role->is_system)<span class="badge bg-dark ms-2">Sistema</span>@endif</td>
                                <td>{{ $role->description ?? '—' }}</td>
                                <td class="text-center">{{ $role->users_count }}</td>
                                <td class="text-center">{{ $role->permissions_count }}</td>
                                <td class="text-center">
                                    @can('roles.editar')
                                        <a href="{{ route('admin.roles.edit', $role) }}" class="btn-icon-square edit" title="Editar rol"><i class="fa-regular fa-pen-to-square"></i></a>
                                    @endcan
                                    @can('roles.eliminar')
                                        @if(!$role->is_system)
                                            <form method="POST" action="{{ route('admin.roles.destroy', $role) }}" class="d-inline confirm-delete" data-name="{{ $role->name }}">
                                                @csrf @method('DELETE')
                                                <button class="btn-icon-square danger" title="Eliminar rol"><i class="fa-solid fa-trash"></i></button>
                                            </form>
                                        @endif
                                    @endcan
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="text-center py-5 text-muted">No se encontraron roles.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="d-flex justify-content-center mt-3">{{ $roles->links() }}</div>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.querySelectorAll('.confirm-delete').forEach(form => form.addEventListener('submit', function (event) {
    event.preventDefault();
    Swal.fire({
        icon: 'warning', title: '¿Eliminar rol?',
        text: `Se eliminará el rol ${this.dataset.name}.`,
        showCancelButton: true, confirmButtonText: 'Eliminar', cancelButtonText: 'Cancelar',
        confirmButtonColor: '#af1731'
    }).then(result => { if (result.isConfirmed) this.submit(); });
}));
</script>
@endpush
@endsection

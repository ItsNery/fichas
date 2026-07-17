@extends('layouts.admin')

@section('title', 'Permisos')

@section('content')
<x-page-header title="Permisos" subtitle="Catálogo de capacidades disponibles en el sistema" icon="fa-solid fa-key" />
<div class="container py-4">
    @if(session('success'))<div class="alert alert-success"><i class="fa-solid fa-circle-check me-2"></i>{{ session('success') }}</div>@endif
    @if($errors->any())<div class="alert alert-danger"><i class="fa-solid fa-circle-exclamation me-2"></i>{{ $errors->first() }}</div>@endif
    <div class="card-panel"><div class="card-body p-4">
        <div class="d-flex justify-content-between gap-3 flex-wrap mb-4">
            <form method="GET" class="d-flex gap-2 flex-grow-1" style="max-width: 600px;">
                <input name="search" value="{{ $search }}" class="form-control" placeholder="Buscar permiso...">
                <button class="btn btn-custom-primary"><i class="fa-solid fa-search"></i></button>
            </form>
            @can('permisos.crear')<a href="{{ route('admin.permissions.create') }}" class="btn btn-custom-primary"><i class="fa-solid fa-plus me-2"></i>Nuevo permiso</a>@endcan
        </div>
        <div class="table-responsive"><table class="table table-custom align-middle">
            <thead><tr><th>Permiso</th><th>Descripción</th><th class="text-center">Roles</th><th class="text-center">Acciones</th></tr></thead>
            <tbody>
            @forelse($permissions as $permission)
                <tr>
                    <td><code>{{ $permission->name }}</code>@if($permission->is_system)<span class="badge bg-dark ms-2">Sistema</span>@endif</td>
                    <td>{{ $permission->description ?? '—' }}</td>
                    <td class="text-center">{{ $permission->roles_count }}</td>
                    <td class="text-center">
                        @can('permisos.editar')<a href="{{ route('admin.permissions.edit', $permission) }}" class="btn-icon-square edit"><i class="fa-regular fa-pen-to-square"></i></a>@endcan
                        @can('permisos.eliminar')
                            @if(!$permission->is_system)<form method="POST" action="{{ route('admin.permissions.destroy', $permission) }}" class="d-inline confirm-delete" data-name="{{ $permission->name }}">@csrf @method('DELETE')<button class="btn-icon-square danger"><i class="fa-solid fa-trash"></i></button></form>@endif
                        @endcan
                    </td>
                </tr>
            @empty<tr><td colspan="4" class="text-center py-5 text-muted">No se encontraron permisos.</td></tr>@endforelse
            </tbody>
        </table></div>
        <div class="d-flex justify-content-center mt-3">{{ $permissions->links() }}</div>
    </div></div>
</div>
@push('scripts')
<script>
document.querySelectorAll('.confirm-delete').forEach(form => form.addEventListener('submit', function (event) {
    event.preventDefault();
    Swal.fire({icon:'warning', title:'¿Eliminar permiso?', text:this.dataset.name, showCancelButton:true, confirmButtonText:'Eliminar', cancelButtonText:'Cancelar', confirmButtonColor:'#af1731'}).then(result => { if(result.isConfirmed) this.submit(); });
}));
</script>
@endpush
@endsection

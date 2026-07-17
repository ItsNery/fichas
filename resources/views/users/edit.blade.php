<x-admin-layout>
    <x-slot name="header">
        <h2 class="h4 font-weight-bold">Editando Usuario: {{ $user->name }}</h2>
    </x-slot>

    <div class="container py-4">
        <div class="card shadow-sm rounded-lg">
            <div class="card-body">
                <form action="{{ route('admin.users.update', $user) }}" method="POST">
                    @csrf
                    @method('PUT')

                    <div class="mb-3">
                        <label for="name" class="form-label">Nombre</label>
                        <input type="text" name="name" id="name" class="form-control"
                            value="{{ old('name', $user->name) }}" required>
                    </div>

                    <div class="mb-3">
                        <label for="email" class="form-label">Email</label>
                        <input type="email" name="email" id="email" class="form-control"
                            value="{{ old('email', $user->email) }}" required>
                    </div>

                    <hr>
                    <p class="text-muted">Deja los siguientes campos en blanco si no quieres cambiar la contraseña.</p>

                    <div class="mb-3">
                        <label for="password" class="form-label">Nueva Contraseña</label>
                        <input type="password" name="password" id="password" class="form-control">
                    </div>

                    <div class="mb-3">
                        <label for="password_confirmation" class="form-label">Confirmar Nueva Contraseña</label>
                        <input type="password" name="password_confirmation" id="password_confirmation"
                            class="form-control">
                    </div>

                    <hr>
                    @can('usuarios.asignar-roles')
                    <div class="mb-3">
                        <label for="role" class="form-label">Rol</label>
                        <select name="role" id="role" class="form-select @error('role') is-invalid @enderror">
                            <option value="">Sin rol</option>
                            @foreach($roles ?? [] as $role)
                                <option value="{{ $role->name }}" @selected($user->hasRole($role->name))>{{ $role->name }}</option>
                            @endforeach
                        </select>
                        @error('role')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    @endcan

                    <button type="submit" class="btn btn-primary">Actualizar Usuario</button>
                    <a href="{{ route('admin.users.index') }}" class="btn btn-secondary">Cancelar</a>
                </form>
            </div>
        </div>
    </div>
</x-admin-layout>

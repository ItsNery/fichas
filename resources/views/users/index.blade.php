<x-admin-layout>
    @section('title', 'Gestión de Usuarios')

    {{-- 1. HEADER UNIFICADO --}}
    <x-page-header
        title="Gestión de Usuarios"
        subtitle="Administración de cuentas, accesos y roles del sistema"
        icon="fa-solid fa-users-gear" />

    <div class="container py-4">

        {{-- 2. TARJETA PRINCIPAL --}}
        <div class="card-panel">
            <div class="card-body p-4">

                {{-- Barra Superior (Buscador o Botón Añadir) --}}
                <div class="d-flex justify-content-end mb-4">
                    <button class="btn btn-custom-primary shadow-sm px-4" data-bs-toggle="modal" data-bs-target="#userModal" id="addUserBtn">
                        <i class="fa-solid fa-user-plus me-2"></i>Añadir Nuevo Usuario
                    </button>
                </div>

                {{-- 3. TABLA ESTILIZADA --}}
                <div class="table-responsive">
                    <table class="table table-custom w-100 align-middle" id="usersTable">
                        <thead>
                            <tr>
                                <th class="ps-4">Usuario</th>
                                <th>Credenciales</th>
                                <th class="text-center pe-4">Acciones</th>
                            </tr>
                        </thead>
                        <tbody id="users-table-body">
                            @forelse ($users as $user)
                            <tr id="user-row-{{ $user->id }}">
                                <td class="ps-4">
                                    <div class="d-flex align-items-center">
                                        {{-- Avatar Simulado --}}
                                        <div class="rounded-circle bg-light d-flex align-items-center justify-content-center me-3 text-vino border"
                                            style="width: 45px; height: 45px; font-size: 1.2rem;">
                                            <i class="fa-regular fa-user"></i>
                                        </div>
                                        <div>
                                            <h6 class="mb-0 fw-bold text-vino user-name">{{ $user->name }}</h6>
                                            <small class="text-muted">ID: {{ $user->id }}</small>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div class="d-flex align-items-center text-secondary">
                                        <i class="fa-regular fa-envelope me-2 text-dorado"></i>
                                        <span class="user-email">{{ $user->email }}</span>
                                    </div>
                                </td>
                                <td class="text-center">
                                    {{-- Badge Simulado --}}
                                    <span class="badge bg-success bg-opacity-10 text-success border border-success px-3">
                                        Activo
                                    </span>
                                </td>
                                <td class="text-center pe-4">
                                    <div class="d-flex justify-content-center gap-2">
                                        {{-- Botón Editar --}}
                                        <button class="btn-icon-square edit edit-btn"
                                            data-bs-toggle="modal" data-bs-target="#userModal"
                                            data-id="{{ $user->id }}"
                                            data-name="{{ $user->name }}"
                                            data-email="{{ $user->email }}">
                                            <i class="fa-regular fa-pen-to-square" data-bs-toggle="tooltip" title="Editar Usuario"></i>
                                        </button>

                                        {{-- Botón Eliminar --}}
                                        <button class="btn-icon-square danger delete-btn"
                                            data-id="{{ $user->id }}"
                                            data-name="{{ $user->name }}">
                                            <i class="fa-solid fa-trash" data-bs-toggle="tooltip" title="Eliminar Usuario"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            @empty
                            {{-- ESTADO VACÍO (Empty State) --}}
                            <tr>
                                <td colspan="4" class="text-center py-5">
                                    <div class="py-4">
                                        <div class="mb-3 text-muted opacity-25">
                                            {{-- Icono de usuarios desvanecido --}}
                                            <i class="fa-solid fa-users-slash fa-4x"></i>
                                        </div>
                                        <h5 class="text-vino fw-bold small">No hay usuarios registrados</h5>
                                        <p class="text-muted small mb-0">
                                            Comienza añadiendo un nuevo usuario con el botón de arriba.
                                        </p>
                                    </div>
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                {{-- Paginación (Si usas paginación de Laravel) --}}
                <div class="mt-4 d-flex justify-content-center" id="pagination-links">
                    {{ $users->links() }}
                </div>
            </div>
        </div>
    </div>

    {{-- 4. MODAL UNIFICADO (Estilo Datos Históricos / Catálogos v2) --}}
    <div class="modal fade" id="userModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header bg-white border-bottom">
                    <h5 class="modal-title fw-bold text-vino" id="modalTitle"></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <form id="userForm" autocomplete="off">
                    @csrf
                    <input type="hidden" id="formMethod" name="_method" value="POST">
                    <input type="hidden" id="userId" name="user_id">

                    <div class="modal-body p-4">
                        <div class="mb-3">
                            <label for="name" class="form-label fw-bold text-secondary small">Nombre Completo</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light border-end-0"><i class="fa-regular fa-user text-muted"></i></span>
                                <input type="text" name="name" id="name" class="form-control text-vino fw-bold border-start-0 ps-0" required placeholder="Ej: Juan Pérez">
                            </div>
                        </div>

                        <div class="mb-4">
                            <label for="email" class="form-label fw-bold text-secondary small">Correo Electrónico</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light border-end-0"><i class="fa-regular fa-envelope text-muted"></i></span>
                                <input type="email" name="email" id="email" class="form-control text-dark border-start-0 ps-0" required placeholder="correo@ejemplo.com">
                            </div>
                        </div>

                        <div class="bg-light p-3 rounded border">
                            <h6 class="small fw-bold text-uppercase text-muted mb-3"><i class="fa-solid fa-lock me-1"></i> Seguridad</h6>

                            <p class="text-muted small mb-2 fst-italic" id="password-help-text"></p>

                            <div class="mb-3">
                                <label for="password" class="form-label small">Contraseña</label>
                                <input type="password" name="password" id="password" class="form-control">
                            </div>
                            <div class="mb-0">
                                <label for="password_confirmation" class="form-label small">Confirmar Contraseña</label>
                                <input type="password" name="password_confirmation" id="password_confirmation" class="form-control">
                            </div>
                        </div>
                    </div>

                    <div class="modal-footer border-0 bg-light">
                        <button type="button" class="btn btn-outline-secondary px-4" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-custom-primary px-4">Guardar Usuario</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- SCRIPTS --}}
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Inicializar Tooltips
            var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
            var tooltipList = tooltipTriggerList.map(function(tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl)
            });

            // DataTables (Configuración limpia)
            // Si usas paginación de Laravel ($users->links()), quizás no necesites DataTables completo, 
            // pero si lo usas para ordenar/buscar en la página actual, esta config es la mejor:
            /*
            const usersTable = new DataTable('#usersTable', {
                language: { url: 'https://cdn.datatables.net/plug-ins/2.3.2/i18n/es-MX.json' },
                paging: false, // Desactivar si usas links de Laravel
                searching: false, // Desactivar si no quieres buscador JS
                info: false,
                autoWidth: false
            });
            */

            const userModal = new bootstrap.Modal(document.getElementById('userModal'));
            const form = document.getElementById('userForm');
            const modalTitle = document.getElementById('modalTitle');

            // Configurar modal para "Crear"
            document.getElementById('addUserBtn').addEventListener('click', () => {
                form.reset();
                form.action = "{{ route('admin.users.store') }}";
                document.getElementById('formMethod').value = 'POST';

                // Título con Icono Dorado
                modalTitle.innerHTML = '<i class="fa-solid fa-user-plus me-2 text-dorado"></i>Añadir Nuevo Usuario';

                document.getElementById('password').required = true;
                document.getElementById('password_confirmation').required = true;
                document.getElementById('password-help-text').innerHTML = '<span class="text-danger">*</span> La contraseña es obligatoria para nuevos usuarios.';
            });

            // Configurar modal para "Editar"
            // Usamos delegación de eventos para que funcione incluso si redibujas la tabla
            document.getElementById('users-table-body').addEventListener('click', function(e) {
                // Buscar el botón o el icono dentro del botón
                const button = e.target.closest('.edit-btn');

                if (button) {
                    form.reset();
                    form.action = `/admin/users/${button.dataset.id}`;
                    document.getElementById('formMethod').value = 'PUT';

                    // Título con Icono Dorado
                    modalTitle.innerHTML = `<i class="fa-solid fa-user-pen me-2 text-dorado"></i>Editar Usuario`;

                    document.getElementById('name').value = button.dataset.name;
                    document.getElementById('email').value = button.dataset.email;

                    document.getElementById('password').required = false;
                    document.getElementById('password_confirmation').required = false;
                    document.getElementById('password-help-text').textContent = 'Deja los campos en blanco si no deseas cambiar la contraseña.';
                }
            });

            // Enviar formulario (AJAX)
            form.addEventListener('submit', function(e) {
                e.preventDefault();

                // Pequeño feedback visual en el botón
                const submitBtn = form.querySelector('button[type="submit"]');
                const originalText = submitBtn.innerText;
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Guardando...';

                fetch(form.action, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify(Object.fromEntries(new FormData(form)))
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            userModal.hide();
                            Swal.fire({
                                icon: 'success',
                                title: '¡Éxito!',
                                text: data.success,
                                confirmButtonColor: '#5f1b2d',
                                timer: 1500,
                                showConfirmButton: false
                            }).then(() => window.location.reload());
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: data.message || 'Ocurrió un problema.',
                                confirmButtonColor: '#af1731'
                            });
                        }
                    })
                    .catch(error => {
                        console.error(error);
                        Swal.fire('Error', 'Error de conexión.', 'error');
                    })
                    .finally(() => {
                        submitBtn.disabled = false;
                        submitBtn.innerText = originalText;
                    });
            });

            // Eliminar usuario
            document.getElementById('users-table-body').addEventListener('click', function(e) {
                const button = e.target.closest('.delete-btn');
                if (button) {
                    Swal.fire({
                        title: `¿Eliminar a "${button.dataset.name}"?`,
                        text: "Esta acción no se puede deshacer.",
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: '#af1731',
                        confirmButtonText: 'Sí, eliminar',
                        cancelButtonText: 'Cancelar'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            fetch(`/admin/users/${button.dataset.id}`, {
                                    method: 'DELETE',
                                    headers: {
                                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                        'Accept': 'application/json'
                                    }
                                })
                                .then(response => response.json())
                                .then(data => {
                                    if (data.success) {
                                        Swal.fire({
                                            icon: 'success',
                                            title: '¡Eliminado!',
                                            showConfirmButton: false,
                                            timer: 1000
                                        });
                                        document.getElementById(`user-row-${button.dataset.id}`).remove();
                                    } else {
                                        Swal.fire('Error', data.error, 'error');
                                    }
                                });
                        }
                    });
                }
            });
        });
    </script>
</x-admin-layout>
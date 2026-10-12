<x-admin-layout>
    <link href="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/css/tom-select.bootstrap5.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/js/tom-select.complete.min.js"></script>
    @section('title', 'Gestión de Datos Históricos')

    @php($isTerritorial = $mode === 'territorial')
    <x-page-header title="Gestión de Datos Históricos" icon="fa-solid fa-database" />

    <div class="container py-4">
        <div class="card-panel">
            <div class="card-body p-4">
                <div class="d-flex flex-wrap gap-2 mb-4" role="group" aria-label="Tipo de datos">
                    <a href="{{ route('admin.datos.index') }}" class="btn {{ $isTerritorial ? 'btn-outline-secondary' : 'btn-custom-primary' }}">
                        <i class="fa-solid fa-building me-2"></i>Datos municipales
                    </a>
                    <a href="{{ route('admin.datos.index', ['mode' => 'territorial']) }}" class="btn {{ $isTerritorial ? 'btn-custom-primary' : 'btn-outline-secondary' }}">
                        <i class="fa-solid fa-map-location-dot me-2"></i>Datos territoriales
                    </a>
                </div>

                <div class="bg-light rounded-3 p-3 mb-4 border">
                    <form method="GET" action="{{ route('admin.datos.index') }}" class="row g-3 align-items-end">
                        <input type="hidden" name="mode" value="{{ $mode }}">
                        <div class="col-12">
                            <h6 class="text-uppercase text-muted small fw-bold mb-0"><i class="fa-solid fa-filter me-1 text-dorado"></i>Filtros de búsqueda</h6>
                        </div>
                        <div class="col-md-5">
                            <label for="select-territorio" class="form-label small fw-bold text-secondary">{{ $isTerritorial ? 'Territorio' : 'Municipio' }}</label>
                            <select id="select-territorio" class="form-select" name="{{ $isTerritorial ? 'unidad_geografica_id' : 'municipio_id' }}">
                                <option value="">{{ $isTerritorial ? 'Todos los territorios' : 'Todos los municipios' }}</option>
                                @foreach($isTerritorial ? $territorios : $municipios as $territorio)
                                    <option value="{{ $territorio->id }}" @selected(request($isTerritorial ? 'unidad_geografica_id' : 'municipio_id') == $territorio->id)>
                                        {{ $territorio->nombre }}@if($isTerritorial) ({{ ucfirst($territorio->nivel) }})@endif
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-5">
                            <label for="select-variable" class="form-label small fw-bold text-secondary">Variable</label>
                            <select id="select-variable" class="form-select" name="variable_id">
                                <option value="">Todas las variables</option>
                                @foreach ($variables as $variable)
                                    <option value="{{ $variable->id }}" @selected(request('variable_id') == $variable->id)>{{ $variable->nombre_amigable }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2"><button type="submit" class="btn btn-custom-primary w-100"><i class="fa-solid fa-magnifying-glass me-2"></i>Filtrar</button></div>
                    </form>
                </div>

                <div class="table-responsive">
                    <table class="table table-custom w-100 align-middle">
                        <thead><tr>
                            <th>{{ $isTerritorial ? 'Territorio' : 'Municipio' }}</th><th>Variable</th><th class="text-center">Año</th><th class="text-end">Valor</th>
                            @canany(['datos.editar', 'datos.eliminar'])<th class="text-center">Acciones</th>@endcanany
                        </tr></thead>
                        <tbody>
                            @forelse($datos as $dato)
                                @php($geografia = $isTerritorial ? $dato->unidadGeografica : $dato->municipio)
                                <tr id="dato-row-{{ $dato->id }}">
                                    <td><span class="fw-medium text-secondary">{{ $geografia?->nombre }}@if($isTerritorial)<small class="text-muted"> ({{ $geografia?->nivel }})</small>@endif</span></td>
                                    <td><span class="text-vino fw-bold">{{ $dato->variable?->nombre_amigable }}</span></td>
                                    <td class="text-center"><span class="badge bg-light text-dark border fw-normal px-3">{{ $dato->anio }}</span></td>
                                    <td class="text-end fw-bold">{{ $dato->motivoSinDato?->codigo ?? number_format((float) $dato->valor, 2) }}</td>
                                    @canany(['datos.editar', 'datos.eliminar'])
                                        <td class="text-center text-nowrap">
                                            @can('datos.editar')
                                                <button type="button" class="btn-icon-square edit-btn" data-bs-toggle="modal" data-bs-target="#editModal"
                                                    data-update-url="{{ $isTerritorial ? route('admin.datos.territorial.update', $dato) : route('admin.datos.update', $dato) }}"
                                                    data-info-text="{{ $dato->variable?->nombre_amigable }} de {{ $geografia?->nombre }} ({{ $dato->anio }})"
                                                    data-current-value="{{ rtrim(rtrim(number_format($dato->valor, 8, '.', ''), '0'), '.') }}"><i class="fa-regular fa-pen-to-square" title="Editar valor"></i></button>
                                            @endcan
                                            @if($isTerritorial)
                                                @can('datos.eliminar')
                                                    <button type="button" class="btn btn-sm btn-outline-danger delete-btn ms-1" data-delete-url="{{ route('admin.datos.territorial.destroy', $dato) }}" data-info-text="{{ $dato->variable?->nombre_amigable }} de {{ $geografia?->nombre }} ({{ $dato->anio }})"><i class="fa-regular fa-trash-can" title="Solicitar eliminación"></i></button>
                                                @endcan
                                            @endcan
                                        </td>
                                    @endcanany
                                </tr>
                            @empty
                                <tr><td colspan="5" class="text-center py-5 text-muted">No se encontraron registros. Intenta ajustar los filtros de búsqueda.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="mt-4 d-flex justify-content-center">{{ $datos->links() }}</div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="editModal" tabindex="-1" aria-labelledby="editModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered"><div class="modal-content border-0 shadow">
            <div class="modal-header"><h5 class="modal-title fw-bold text-vino" id="editModalLabel"><i class="fa-solid fa-pen-to-square me-2 text-dorado"></i>Proponer edición</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button></div>
            <form id="editForm" method="POST" autocomplete="off">@csrf @method('PUT')
                <div class="modal-body p-4"><div class="alert alert-light border small" id="modal-info-text"></div><label for="modal-valor" class="form-label fw-bold">Nuevo valor</label><input type="text" inputmode="decimal" class="form-control" id="modal-valor" name="valor" required><div class="invalid-feedback"></div></div>
                <div class="modal-footer"><button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button><button type="submit" class="btn btn-custom-primary"><i class="fa-solid fa-paper-plane me-2"></i>Enviar a revisión</button></div>
            </form>
        </div></div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const config = { create: false, allowEmptyOption: true, plugins: ['clear_button'], sortField: { field: 'text', direction: 'asc' } };
            new TomSelect('#select-territorio', config);
            new TomSelect('#select-variable', config);

            const editForm = document.getElementById('editForm');
            const modal = new bootstrap.Modal(document.getElementById('editModal'));
            const value = document.getElementById('modal-valor');
            document.querySelectorAll('.edit-btn').forEach(button => button.addEventListener('click', () => {
                editForm.action = button.dataset.updateUrl;
                document.getElementById('modal-info-text').textContent = button.dataset.infoText;
                value.value = button.dataset.currentValue;
                value.classList.remove('is-invalid');
            }));
            editForm.addEventListener('submit', event => {
                event.preventDefault();
                fetch(editForm.action, { method: 'POST', headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' }, body: JSON.stringify({ _method: 'PUT', valor: value.value.replace(',', '.') }) })
                    .then(response => response.ok ? response.json() : response.json().then(Promise.reject.bind(Promise)))
                    .then(data => { modal.hide(); Swal.fire({ icon: 'success', title: 'Enviado a revisión', html: `${data.success}<br><a href="${data.detail_url}" class="fw-bold">Ver lote #${data.lote_id}</a>`, confirmButtonColor: '#5f1b2d' }); })
                    .catch(error => { value.classList.toggle('is-invalid', Boolean(error.errors?.valor)); if (error.errors?.valor) value.nextElementSibling.textContent = error.errors.valor[0]; });
            });
            document.querySelectorAll('.delete-btn').forEach(button => button.addEventListener('click', () => {
                Swal.fire({ icon: 'warning', title: '¿Solicitar eliminación?', text: `El dato seguirá publicado hasta que se apruebe el lote de revisión.`, showCancelButton: true, confirmButtonText: 'Enviar a revisión', cancelButtonText: 'Cancelar', confirmButtonColor: '#af1731' }).then(result => {
                    if (!result.isConfirmed) return;
                    fetch(button.dataset.deleteUrl, { method: 'DELETE', headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' } })
                        .then(response => response.ok ? response.json() : Promise.reject())
                        .then(data => Swal.fire({ icon: 'success', title: 'Enviado a revisión', html: `${data.success}<br><a href="${data.detail_url}" class="fw-bold">Ver lote #${data.lote_id}</a>`, confirmButtonColor: '#5f1b2d' }))
                        .catch(() => Swal.fire({ icon: 'error', title: 'Error', text: 'No se pudo crear la solicitud de eliminación.', confirmButtonColor: '#af1731' }));
                });
            }));
        });
    </script>
</x-admin-layout>

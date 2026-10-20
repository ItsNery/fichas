<x-admin-layout>
    @section('title', 'Catálogo territorial')

    <x-page-header
        title="Catálogo territorial"
        subtitle="Consulta y descarga de la organización territorial de Puebla"
        icon="fa-solid fa-map-location-dot" />

    <div class="container py-4">
        <div class="alert alert-light border d-flex align-items-center gap-3 mb-4" role="note">
            <i class="fa-solid fa-circle-info text-dorado fs-4"></i>
            <div>
                <strong class="d-block text-vino">Consulta de sólo lectura</strong>
                <span class="small text-muted">Este catálogo no permite modificar la organización territorial.</span>
            </div>
        </div>

        <div class="row g-3 mb-4">
            <div class="col-sm-6 col-lg-2">
                <div class="card-panel h-100"><div class="card-body p-3">
                    <div class="small text-muted text-uppercase fw-bold">País</div>
                    <div class="h5 mb-2 text-vino">México</div>
                    <a class="small" href="{{ route('admin.catalogos.territorial.export', 'pais', false) }}">Exportar</a>
                </div></div>
            </div>
            <div class="col-sm-6 col-lg-2">
                <div class="card-panel h-100"><div class="card-body p-3">
                    <div class="small text-muted text-uppercase fw-bold">Estado</div>
                    <div class="h5 mb-2 text-vino">Puebla</div>
                    <a class="small" href="{{ route('admin.catalogos.territorial.export', 'estado', false) }}">Exportar</a>
                </div></div>
            </div>
            <div class="col-sm-6 col-lg-2">
                <div class="card-panel h-100"><div class="card-body p-3">
                    <div class="small text-muted text-uppercase fw-bold">Macrorregiones</div>
                    <div class="h4 mb-0 text-vino">{{ $macrorregiones->count() }}</div>
                </div></div>
            </div>
            <div class="col-sm-6 col-lg-2">
                <div class="card-panel h-100"><div class="card-body p-3">
                    <div class="small text-muted text-uppercase fw-bold">Microrregiones</div>
                    <div class="h4 mb-0 text-vino">{{ $microrregiones->count() }}</div>
                </div></div>
            </div>
            <div class="col-sm-6 col-lg-2">
                <div class="card-panel h-100"><div class="card-body p-3">
                    <div class="small text-muted text-uppercase fw-bold">Municipios</div>
                    <div class="h4 mb-0 text-vino">{{ $municipios->count() }}</div>
                </div></div>
            </div>
            <div class="col-sm-6 col-lg-2">
                <div class="card-panel h-100"><div class="card-body p-3">
                    <div class="small text-muted text-uppercase fw-bold">Instrumentos</div>
                    <div class="h4 mb-0 text-vino">{{ $instrumentos->count() }}</div>
                </div></div>
            </div>
        </div>

        <div class="card-panel">
            <div class="card-body p-4">
                <ul class="nav nav-tabs mb-4" id="territorialTabs" role="tablist">
                    <li class="nav-item" role="presentation"><button class="nav-link active" data-bs-toggle="tab" data-bs-target="#macrorregiones" type="button">Macrorregiones</button></li>
                    <li class="nav-item" role="presentation"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#microrregiones" type="button">Microrregiones</button></li>
                    <li class="nav-item" role="presentation"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#municipios" type="button">Municipios</button></li>
                    <li class="nav-item" role="presentation"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#instrumentos" type="button">Instrumentos</button></li>
                </ul>

                <div class="tab-content">
                    <div class="tab-pane fade show active" id="macrorregiones" role="tabpanel">
                        <div class="d-flex flex-wrap justify-content-between gap-2 mb-3">
                            <h2 class="h5 text-vino mb-0">Macrorregiones</h2>
                            <a class="btn btn-outline-success btn-sm" href="{{ route('admin.catalogos.territorial.export', 'macrorregiones', false) }}"><i class="fa-solid fa-file-excel me-1"></i>Exportar Excel</a>
                        </div>
                        <div class="table-responsive"><table class="table table-custom w-100 align-middle catalogo-territorial-table">
                            <thead><tr><th>PK: ID</th><th>Nombre</th><th class="text-center">Microrregiones</th></tr></thead>
                            <tbody>@foreach ($macrorregiones as $macrorregion)<tr><td class="text-muted">{{ $macrorregion->id }}</td><td class="fw-bold text-vino">{{ $macrorregion->nombre }}</td><td class="text-center">{{ $macrorregion->microrregiones_count }}</td></tr>@endforeach</tbody>
                        </table></div>
                    </div>

                    <div class="tab-pane fade" id="microrregiones" role="tabpanel">
                        <div class="d-flex flex-wrap justify-content-between gap-2 mb-3">
                            <h2 class="h5 text-vino mb-0">Microrregiones</h2>
                            <a class="btn btn-outline-success btn-sm" href="{{ route('admin.catalogos.territorial.export', 'microrregiones', false) }}"><i class="fa-solid fa-file-excel me-1"></i>Exportar Excel</a>
                        </div>
                        <div class="table-responsive"><table class="table table-custom w-100 align-middle catalogo-territorial-table">
                            <thead><tr><th>PK: ID</th><th>Microrregión</th><th>FK: macrorregion_id</th><th>Macrorregión</th><th class="text-center">Municipios</th></tr></thead>
                            <tbody>@foreach ($microrregiones as $microrregion)<tr><td class="text-muted">{{ $microrregion->id }}</td><td class="fw-bold text-vino">{{ $microrregion->nombre }}</td><td>{{ $microrregion->macrorregion_id }}</td><td>{{ $microrregion->macrorregion?->nombre ?? 'Sin asignar' }}</td><td class="text-center">{{ $microrregion->municipios_count }}</td></tr>@endforeach</tbody>
                        </table></div>
                    </div>

                    <div class="tab-pane fade" id="municipios" role="tabpanel">
                        <div class="d-flex flex-wrap justify-content-between gap-2 mb-3">
                            <h2 class="h5 text-vino mb-0">Municipios</h2>
                            <a class="btn btn-outline-success btn-sm" href="{{ route('admin.catalogos.territorial.export', 'municipios', false) }}"><i class="fa-solid fa-file-excel me-1"></i>Exportar Excel</a>
                        </div>
                        <div class="table-responsive"><table class="table table-custom w-100 align-middle catalogo-territorial-table">
                            <thead><tr><th>PK: ID</th><th>Clave GEO</th><th>Municipio</th><th>FK: microrregion_id</th><th>Microrregión</th><th>Macrorregión</th></tr></thead>
                            <tbody>@foreach ($municipios as $municipio)<tr><td class="text-muted">{{ $municipio->id }}</td><td><span class="badge bg-light text-dark border font-monospace">{{ $municipio->cvegeo }}</span></td><td class="fw-bold text-vino">{{ $municipio->nombre }}</td><td>{{ $municipio->microrregion_id }}</td><td>{{ $municipio->microrregion?->nombre ?? 'Sin asignar' }}</td><td>{{ $municipio->microrregion?->macrorregion?->nombre ?? 'Sin asignar' }}</td></tr>@endforeach</tbody>
                        </table></div>
                    </div>

                    <div class="tab-pane fade" id="instrumentos" role="tabpanel">
                        <div class="d-flex flex-wrap justify-content-between gap-2 mb-3">
                            <h2 class="h5 text-vino mb-0">Instrumentos de planeación</h2>
                            <a class="btn btn-outline-success btn-sm" href="{{ route('admin.catalogos.territorial.export', 'instrumentos', false) }}"><i class="fa-solid fa-file-excel me-1"></i>Exportar Excel</a>
                        </div>
                        <div class="table-responsive"><table class="table table-custom w-100 align-middle catalogo-territorial-table">
                            <thead><tr><th>PK: instrumento_id</th><th>Instrumento</th><th class="text-center">Municipios vinculados</th><th>Relación: instrumento_municipio</th></tr></thead>
                            <tbody>@forelse ($instrumentos as $instrumento)<tr><td class="text-muted">{{ $instrumento->id }}</td><td><span class="fw-bold text-vino d-block">{{ $instrumento->nombre }}</span><small class="text-muted">{{ $instrumento->descripcion ?: 'Sin descripción' }}</small></td><td class="text-center">{{ $instrumento->municipios_count }}</td><td>@forelse ($instrumento->municipios as $municipio)<div class="small mb-1"><code>instrumento_id={{ $instrumento->id }}</code> · <code>municipio_id={{ $municipio->id }}</code> · {{ $municipio->nombre }} · {{ $municipio->pivot->anio }}</div>@empty<span class="text-muted small">Sin municipios vinculados</span>@endforelse</td></tr>@empty<tr><td colspan="4" class="text-center text-muted py-4">No hay instrumentos registrados.</td></tr>@endforelse</tbody>
                        </table></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                document.querySelectorAll('.catalogo-territorial-table').forEach(function (table) {
                    new DataTable(table, {
                        language: { url: 'https://cdn.datatables.net/plug-ins/2.3.2/i18n/es-MX.json' },
                        pageLength: 25,
                        autoWidth: false,
                        dom: '<"d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3"f>t<"d-flex justify-content-center mt-3"p>'
                    });
                });
            });
        </script>
    @endpush
</x-admin-layout>

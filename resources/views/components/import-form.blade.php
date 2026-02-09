@props(['action', 'template', 'btnText'])

<form action="{{ $action }}" method="POST" enctype="multipart/form-data">
    @csrf
    <div class="file-drop-zone">
        <span class="file-info">
            <i class="fa-solid fa-cloud-arrow-up fa-3x d-block mb-3 text-muted opacity-50"></i>
            <span class="fw-bold text-vino">Arrastra el archivo aquí</span>
            <br><small class="text-muted">o haz clic para seleccionar (.xlsx, .csv)</small>
        </span>
        <input type="file" name="archivo" class="file-input" required accept=".xlsx, .xls, .csv" onchange="updateFileInfo(this)">
    </div>

    <div class="d-flex justify-content-center gap-3 mt-4">
        <a href="{{ $template }}" class="btn btn-outline-secondary">
            <i class="fas fa-download me-2"></i>Plantilla
        </a>
        <button type="submit" class="btn btn-custom-primary">
            <i class="fas fa-upload me-2"></i>{{ $btnText }}
        </button>
    </div>

    @if(isset($instructions))
    <div class="import-instructions">
        <strong><i class="fas fa-info-circle me-1"></i> Columnas Requeridas:</strong>
        {{ $instructions }}
    </div>
    @endif
</form>
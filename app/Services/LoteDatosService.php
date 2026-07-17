<?php

namespace App\Services;

use App\Models\CatMotivoSinDato;
use App\Models\DatoHistorico;
use App\Models\DatoIndicadorComplejo;
use App\Models\Indicador;
use App\Models\LoteDatoIndicadorComplejo;
use App\Models\LoteDatoHistorico;
use App\Models\LoteDatos;
use App\Models\Municipio;
use App\Models\User;
use App\Models\Variable;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Maatwebsite\Excel\Facades\Excel;

class LoteDatosService
{
    public function crearBorrador(UploadedFile $file, User $usuario): array
    {
        $filePath = $file->getPathname();
        if (!$filePath || !is_file($filePath)) {
            return ['errors' => [['fila' => 1, 'error' => 'No se encontró el archivo temporal cargado.']]];
        }

        $storedName = Str::uuid() . '.' . ($file->getClientOriginalExtension() ?: 'tmp');
        $path = Storage::disk('local')->putFileAs('lotes_datos', $filePath, $storedName);
        if (!$path) {
            return ['errors' => [['fila' => 1, 'error' => 'No se encontró el archivo temporal cargado.']]];
        }
        $storedFilePath = Storage::disk('local')->path($path);

        // Usar la ruta física evita que Laravel Excel dependa de getRealPath()
        // cuando PHP está ejecutándose con una carpeta temporal no estándar.
        try {
            $sheet = Excel::toCollection(null, $storedFilePath)->first();
        } catch (\Throwable $e) {
            Storage::disk('local')->delete($path);
            throw $e;
        }
        if (!$sheet || $sheet->isEmpty()) {
            Storage::disk('local')->delete($path);
            return ['errors' => [['fila' => 1, 'error' => 'El archivo no contiene registros.']]];
        }

        $headings = array_map(
            fn($heading) => strtolower(trim((string) $heading)),
            $sheet->shift()->toArray()
        );

        $required = ['anio', 'valor'];
        foreach ($required as $heading) {
            if (!in_array($heading, $headings, true)) {
                Storage::disk('local')->delete($path);
                return ['errors' => [['fila' => 1, 'error' => "La columna '{$heading}' es requerida."]]];
            }
        }

        if (!array_intersect(['municipio_cvegeo', 'municipio_id'], $headings)) {
            Storage::disk('local')->delete($path);
            return ['errors' => [['fila' => 1, 'error' => 'Se requiere municipio_cvegeo o municipio_id.']]];
        }
        if (!array_intersect(['variable_tecnico', 'variable_id'], $headings)) {
            Storage::disk('local')->delete($path);
            return ['errors' => [['fila' => 1, 'error' => 'Se requiere variable_tecnico o variable_id.']]];
        }

        $municipiosCvegeo = Municipio::pluck('id', 'cvegeo')->mapWithKeys(
            fn($id, $key) => [(string) $key => $id]
        );
        $municipiosIds = Municipio::pluck('id')->flip();
        $variablesTecnico = Variable::pluck('id', 'nombre_tecnico');
        $variablesIds = Variable::pluck('id')->flip();
        $motivos = CatMotivoSinDato::pluck('id', 'codigo')->mapWithKeys(
            fn($id, $codigo) => [strtoupper($codigo) => $id]
        );

        $errors = [];
        $normalized = [];

        foreach ($sheet as $index => $row) {
            $values = array_pad($row->toArray(), count($headings), null);
            $rowData = array_combine($headings, array_slice($values, 0, count($headings)));
            $sourceRow = $index + 2;

            if (collect($rowData)->filter(fn($value) => $value !== null && $value !== '')->isEmpty()) {
                continue;
            }

            $municipioId = null;
            if (!empty($rowData['municipio_cvegeo'])) {
                $municipioId = $municipiosCvegeo[(string) $rowData['municipio_cvegeo']] ?? null;
            }
            if (!$municipioId && !empty($rowData['municipio_id']) && $municipiosIds->has((int) $rowData['municipio_id'])) {
                $municipioId = (int) $rowData['municipio_id'];
            }

            $variableId = null;
            if (!empty($rowData['variable_tecnico'])) {
                $variableId = $variablesTecnico[trim((string) $rowData['variable_tecnico'])] ?? null;
            }
            if (!$variableId && !empty($rowData['variable_id']) && $variablesIds->has((int) $rowData['variable_id'])) {
                $variableId = (int) $rowData['variable_id'];
            }

            $anio = filter_var($rowData['anio'] ?? null, FILTER_VALIDATE_INT);
            $valorCelda = $rowData['valor'] ?? null;
            $motivoCodigo = strtoupper(trim((string) ($rowData['motivo_sin_dato'] ?? $valorCelda)));
            $valor = is_numeric($valorCelda) ? (float) $valorCelda : null;
            $motivoId = $valor === null && $motivoCodigo !== '' ? ($motivos[$motivoCodigo] ?? null) : null;

            if (!$municipioId) {
                $errors[] = ['fila' => $sourceRow, 'error' => 'Municipio no válido o no identificado.'];
            }
            if (!$variableId) {
                $errors[] = ['fila' => $sourceRow, 'error' => 'Variable no válida o no identificada.'];
            }
            if (!$anio || $anio < 1900 || $anio > 2100) {
                $errors[] = ['fila' => $sourceRow, 'error' => 'El año debe ser un entero de cuatro dígitos.'];
            }
            if ($valor === null && $motivoCodigo !== '' && !$motivoId) {
                $errors[] = ['fila' => $sourceRow, 'error' => "El valor o motivo '{$motivoCodigo}' no es válido."];
            }

            if ($municipioId && $variableId && $anio && ($valor !== null || $motivoCodigo === '' || $motivoId)) {
                $key = "{$municipioId}:{$variableId}:{$anio}";
                $normalized[$key] = [
                    'fila_origen' => $sourceRow,
                    'municipio_id' => $municipioId,
                    'variable_id' => $variableId,
                    'anio' => $anio,
                    'valor' => $valor,
                    'motivo_sin_dato_id' => $motivoId,
                ];
            }
        }

        if ($errors) {
            Storage::disk('local')->delete($path);
            return ['errors' => $errors];
        }
        if (!$normalized) {
            Storage::disk('local')->delete($path);
            return ['errors' => [['fila' => 1, 'error' => 'El archivo no contiene filas válidas.']]];
        }

        $municipioIds = collect($normalized)->pluck('municipio_id')->unique();
        $variableIds = collect($normalized)->pluck('variable_id')->unique();
        $anios = collect($normalized)->pluck('anio')->unique();
        $existing = DatoHistorico::whereIn('municipio_id', $municipioIds)
            ->whereIn('variable_id', $variableIds)
            ->whereIn('anio', $anios)
            ->get(['municipio_id', 'variable_id', 'anio', 'valor', 'motivo_sin_dato_id', 'updated_at'])
            ->mapWithKeys(fn($dato) => ["{$dato->municipio_id}:{$dato->variable_id}:{$dato->anio}" => $dato]);

        $hash = hash_file('sha256', $storedFilePath);

        try {
            $lote = DB::transaction(function () use ($usuario, $file, $path, $hash, $normalized, $existing) {
                $insertar = collect($normalized)->keys()->filter(fn($key) => !$existing->has($key))->count();
                $actualizar = count($normalized) - $insertar;

                $lote = LoteDatos::create([
                    'tipo' => 'datos_historicos',
                    'estado' => LoteDatos::BORRADOR,
                    'archivo_original' => $file->getClientOriginalName(),
                    'archivo_path' => $path,
                    'archivo_hash' => $hash,
                    'usuario_carga_id' => $usuario->id,
                    'total_filas' => count($normalized),
                    'filas_insertar' => $insertar,
                    'filas_actualizar' => $actualizar,
                ]);

                $now = now();
                $rows = collect($normalized)->map(function ($row, $key) use ($lote, $existing, $now) {
                    $original = $existing->get($key);
                    return $row + [
                        'lote_datos_id' => $lote->id,
                        'accion' => $original ? 'actualizar' : 'insertar',
                        'valor_original' => $original?->valor,
                        'motivo_sin_dato_original_id' => $original?->motivo_sin_dato_id,
                        'dato_historico_updated_at' => $original?->updated_at,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                })->values();

                $rows->chunk(1000)->each(fn($chunk) => LoteDatoHistorico::insert($chunk->all()));

                return $lote;
            });
        } catch (\Throwable $e) {
            Storage::disk('local')->delete($path);
            throw $e;
        }

        return ['lote' => $lote];
    }

    public function crearEdicionManual(DatoHistorico $dato, float $valor, User $usuario): LoteDatos
    {
        return DB::transaction(function () use ($dato, $valor, $usuario) {
            $lote = LoteDatos::create([
                'tipo' => 'dato_historico_manual',
                'estado' => LoteDatos::EN_REVISION,
                'archivo_original' => "Edición manual del dato #{$dato->id}",
                'archivo_path' => "manual://dato-historico/{$dato->id}",
                'usuario_carga_id' => $usuario->id,
                'total_filas' => 1,
                'filas_insertar' => 0,
                'filas_actualizar' => 1,
                'enviado_revision_at' => now(),
            ]);

            $lote->filas()->create([
                'fila_origen' => 1,
                'municipio_id' => $dato->municipio_id,
                'variable_id' => $dato->variable_id,
                'anio' => $dato->anio,
                'valor' => $valor,
                'motivo_sin_dato_id' => null,
                'accion' => 'actualizar',
                'valor_original' => $dato->valor,
                'motivo_sin_dato_original_id' => $dato->motivo_sin_dato_id,
                'dato_historico_updated_at' => $dato->updated_at,
            ]);

            return $lote;
        });
    }

    public function crearBorradorComplejo(UploadedFile $file, Indicador $indicador, User $usuario): array
    {
        if (!$indicador->es_complejo) {
            return ['errors' => [['fila' => 1, 'error' => 'El indicador seleccionado no es complejo.']]];
        }

        $sheet = Excel::toCollection(null, $file)->first();
        if (!$sheet || $sheet->isEmpty()) {
            return ['errors' => [['fila' => 1, 'error' => 'El archivo no contiene registros.']]];
        }

        $rawHeadings = array_map(fn($heading) => trim((string) $heading), $sheet->shift()->toArray());
        $normalizedHeadings = array_map('strtolower', $rawHeadings);
        $municipioColumn = in_array('municipio_cvegeo', $normalizedHeadings, true)
            ? 'municipio_cvegeo'
            : (in_array('municipio_id', $normalizedHeadings, true) ? 'municipio_id' : null);

        if (!$municipioColumn || !in_array('anio', $normalizedHeadings, true)) {
            return ['errors' => [['fila' => 1, 'error' => 'Se requieren municipio_cvegeo o municipio_id y anio.']]];
        }

        $municipiosCvegeo = Municipio::pluck('id', 'cvegeo')->mapWithKeys(fn($id, $key) => [(string) $key => $id]);
        $municipiosIds = Municipio::pluck('id')->flip();
        $errors = [];
        $normalized = [];

        foreach ($sheet as $index => $row) {
            $values = array_pad($row->toArray(), count($rawHeadings), null);
            $rowData = array_combine($normalizedHeadings, array_slice($values, 0, count($rawHeadings)));
            $sourceRow = $index + 2;
            if (collect($rowData)->filter(fn($value) => $value !== null && $value !== '')->isEmpty()) {
                continue;
            }

            $municipioId = null;
            if ($municipioColumn === 'municipio_cvegeo' && !empty($rowData[$municipioColumn])) {
                $municipioId = $municipiosCvegeo[(string) $rowData[$municipioColumn]] ?? null;
            } elseif (!empty($rowData[$municipioColumn]) && $municipiosIds->has((int) $rowData[$municipioColumn])) {
                $municipioId = (int) $rowData[$municipioColumn];
            }
            $anio = filter_var($rowData['anio'] ?? null, FILTER_VALIDATE_INT);
            $datos = [];

            foreach ($rawHeadings as $columnIndex => $category) {
                $normalizedCategory = $normalizedHeadings[$columnIndex];
                if (in_array($normalizedCategory, ['municipio_cvegeo', 'municipio_id', 'anio'], true)) {
                    continue;
                }
                $value = $values[$columnIndex] ?? null;
                if ($value === null || $value === '') {
                    continue;
                }
                if (!is_numeric($value)) {
                    $errors[] = ['fila' => $sourceRow, 'error' => "El valor de '{$category}' debe ser numérico."];
                    continue;
                }
                $datos[$category] = (float) $value;
            }

            if (!$municipioId) {
                $errors[] = ['fila' => $sourceRow, 'error' => 'Municipio no válido o no identificado.'];
            }
            if (!$anio || $anio < 1900 || $anio > 2100) {
                $errors[] = ['fila' => $sourceRow, 'error' => 'El año debe ser un entero de cuatro dígitos.'];
            }
            if (!$datos) {
                $errors[] = ['fila' => $sourceRow, 'error' => 'La fila no contiene categorías con valores numéricos.'];
            }

            if ($municipioId && $anio && $datos) {
                $key = "{$indicador->id}:{$municipioId}:{$anio}";
                if (isset($normalized[$key])) {
                    $errors[] = ['fila' => $sourceRow, 'error' => 'La combinación indicador, municipio y año está duplicada.'];
                }
                $normalized[$key] = [
                    'fila_origen' => $sourceRow,
                    'indicador_id' => $indicador->id,
                    'municipio_id' => $municipioId,
                    'anio' => $anio,
                    'datos' => $datos,
                ];
            }
        }

        if ($errors) {
            return ['errors' => $errors];
        }
        if (!$normalized) {
            return ['errors' => [['fila' => 1, 'error' => 'El archivo no contiene filas válidas.']]];
        }

        $municipioIds = collect($normalized)->pluck('municipio_id')->unique();
        $anios = collect($normalized)->pluck('anio')->unique();
        $existing = DatoIndicadorComplejo::where('indicador_id', $indicador->id)
            ->whereIn('municipio_id', $municipioIds)
            ->whereIn('anio', $anios)
            ->get()
            ->mapWithKeys(fn($dato) => ["{$dato->indicador_id}:{$dato->municipio_id}:{$dato->anio}" => $dato]);

        $hash = hash_file('sha256', $file->getRealPath());
        $path = $file->store('lotes_datos', 'local');

        try {
            $lote = DB::transaction(function () use ($usuario, $file, $path, $hash, $normalized, $existing) {
                $insertar = collect($normalized)->keys()->filter(fn($key) => !$existing->has($key))->count();
                $lote = LoteDatos::create([
                    'tipo' => 'datos_complejos',
                    'estado' => LoteDatos::BORRADOR,
                    'archivo_original' => $file->getClientOriginalName(),
                    'archivo_path' => $path,
                    'archivo_hash' => $hash,
                    'usuario_carga_id' => $usuario->id,
                    'total_filas' => count($normalized),
                    'filas_insertar' => $insertar,
                    'filas_actualizar' => count($normalized) - $insertar,
                ]);

                foreach ($normalized as $key => $row) {
                    $original = $existing->get($key);
                    $lote->filasComplejas()->create([
                        ...$row,
                        'datos_originales' => $original?->datos,
                        'dato_complejo_updated_at' => $original?->updated_at,
                        'accion' => $original ? 'actualizar' : 'insertar',
                    ]);
                }

                return $lote;
            });
        } catch (\Throwable $e) {
            Storage::disk('local')->delete($path);
            throw $e;
        }

        return ['lote' => $lote];
    }

    public function enviarRevision(LoteDatos $lote, User $usuario): void
    {
        DB::transaction(function () use ($lote, $usuario) {
            $locked = LoteDatos::lockForUpdate()->findOrFail($lote->id);
            if ($locked->estado !== LoteDatos::BORRADOR) {
                throw ValidationException::withMessages(['lote' => 'El lote ya fue enviado a revisión.']);
            }
            if ($locked->usuario_carga_id !== $usuario->id && !$usuario->can('datos.aprobar')) {
                abort(403);
            }

            $locked->update([
                'estado' => LoteDatos::EN_REVISION,
                'enviado_revision_at' => now(),
                'observaciones' => null,
            ]);
        });
    }

    public function aprobar(LoteDatos $lote, User $revisor): void
    {
        DB::transaction(function () use ($lote, $revisor) {
            $locked = LoteDatos::lockForUpdate()->findOrFail($lote->id);
            if ($locked->estado !== LoteDatos::EN_REVISION) {
                throw ValidationException::withMessages(['lote' => 'Solo pueden aprobarse lotes en revisión.']);
            }

            $now = now();
            match ($locked->tipo) {
                'datos_historicos', 'dato_historico_manual' => $this->aprobarHistoricos($locked, $now),
                'datos_complejos' => $this->aprobarComplejos($locked, $now),
                default => throw ValidationException::withMessages(['lote' => 'El tipo de lote no es compatible.']),
            };

            $locked->update([
                'estado' => LoteDatos::APROBADO,
                'usuario_revision_id' => $revisor->id,
                'revisado_at' => $now,
                'aplicado_at' => $now,
                'observaciones' => null,
            ]);
        });
    }

    public function rechazar(LoteDatos $lote, User $revisor, string $observaciones): void
    {
        DB::transaction(function () use ($lote, $revisor, $observaciones) {
            $locked = LoteDatos::lockForUpdate()->findOrFail($lote->id);
            if ($locked->estado !== LoteDatos::EN_REVISION) {
                throw ValidationException::withMessages(['lote' => 'Solo pueden rechazarse lotes en revisión.']);
            }

            $locked->update([
                'estado' => LoteDatos::RECHAZADO,
                'usuario_revision_id' => $revisor->id,
                'revisado_at' => now(),
                'observaciones' => $observaciones,
            ]);
        });
    }

    private function aprobarHistoricos(LoteDatos $lote, $now): void
    {
        $filas = $lote->filas()->get();
        $this->assertHistoricalConflicts($filas);

        $filas->chunk(1000)->each(function ($chunk) use ($lote, $now) {
            $rows = $chunk->map(fn($fila) => [
                'municipio_id' => $fila->municipio_id,
                'variable_id' => $fila->variable_id,
                'anio' => $fila->anio,
                'valor' => $fila->valor,
                'motivo_sin_dato_id' => $fila->motivo_sin_dato_id,
                'lote_datos_id' => $lote->id,
                'created_at' => $now,
                'updated_at' => $now,
            ])->all();

            DatoHistorico::upsert(
                $rows,
                ['municipio_id', 'variable_id', 'anio'],
                ['valor', 'motivo_sin_dato_id', 'lote_datos_id', 'updated_at']
            );
        });

        $surfaceVariableId = Variable::where(
            'nombre_tecnico',
            MunicipioReferenceDataService::SUPERFICIE_VARIABLE
        )->value('id');
        $surfaceMunicipioIds = $filas
            ->where('variable_id', $surfaceVariableId)
            ->pluck('municipio_id')
            ->unique()
            ->values()
            ->all();

        if ($surfaceMunicipioIds) {
            app(MunicipioReferenceDataService::class)->syncSuperficies(false, $surfaceMunicipioIds);
        }
    }

    private function aprobarComplejos(LoteDatos $lote, $now): void
    {
        $filas = $lote->filasComplejas()->get();
        $this->assertComplexConflicts($filas);

        $filas->chunk(500)->each(function ($chunk) use ($lote, $now) {
            $rows = $chunk->map(fn($fila) => [
                'indicador_id' => $fila->indicador_id,
                'municipio_id' => $fila->municipio_id,
                'anio' => $fila->anio,
                'datos' => json_encode($fila->datos, JSON_UNESCAPED_UNICODE),
                'lote_datos_id' => $lote->id,
                'created_at' => $now,
                'updated_at' => $now,
            ])->all();

            DatoIndicadorComplejo::upsert(
                $rows,
                ['indicador_id', 'municipio_id', 'anio'],
                ['datos', 'lote_datos_id', 'updated_at']
            );
        });
    }

    private function assertHistoricalConflicts($filas): void
    {
        foreach ($filas as $fila) {
            $actual = DatoHistorico::where('municipio_id', $fila->municipio_id)
                ->where('variable_id', $fila->variable_id)
                ->where('anio', $fila->anio)
                ->first();

            if ($fila->accion === 'insertar' && $actual) {
                throw ValidationException::withMessages(['lote' => "Conflicto en la fila {$fila->fila_origen}: el dato ya existe."]);
            }
            $hasSnapshot = $fila->dato_historico_updated_at !== null;
            if ($fila->accion === 'actualizar' && (!$actual || ($hasSnapshot && (
                (float) $actual->valor !== (float) $fila->valor_original
                || $actual->motivo_sin_dato_id !== $fila->motivo_sin_dato_original_id
                || $actual->updated_at?->format('Y-m-d H:i:s') !== $fila->dato_historico_updated_at?->format('Y-m-d H:i:s')
            )))) {
                throw ValidationException::withMessages(['lote' => "Conflicto en la fila {$fila->fila_origen}: el dato cambió después de crear el lote."]);
            }
        }
    }

    private function assertComplexConflicts($filas): void
    {
        foreach ($filas as $fila) {
            $actual = DatoIndicadorComplejo::where('indicador_id', $fila->indicador_id)
                ->where('municipio_id', $fila->municipio_id)
                ->where('anio', $fila->anio)
                ->first();

            if ($fila->accion === 'insertar' && $actual) {
                throw ValidationException::withMessages(['lote' => "Conflicto en la fila {$fila->fila_origen}: el dato complejo ya existe."]);
            }
            if ($fila->accion === 'actualizar' && (!$actual
                || $actual->datos != $fila->datos_originales
                || $actual->updated_at?->format('Y-m-d H:i:s') !== $fila->dato_complejo_updated_at?->format('Y-m-d H:i:s'))) {
                throw ValidationException::withMessages(['lote' => "Conflicto en la fila {$fila->fila_origen}: el dato complejo cambió después de crear el lote."]);
            }
        }
    }
}

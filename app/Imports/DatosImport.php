<?php

namespace App\Imports;

use App\Models\DatoHistorico;
use App\Models\Municipio;
use App\Models\Variable;
use App\Models\CatMotivoSinDato;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithBatchInserts;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithUpserts;

class DatosImport implements ToModel, WithHeadingRow, WithChunkReading, WithBatchInserts, WithUpserts
{
    private $variablesCache;
    private $municipiosCache;
    private $motivosCache;

    public function __construct()
    {
        // Mapa de traducción: ['nombre_tecnico_variable' => id_variable]
        $this->variablesCache = Variable::pluck('id', 'nombre_tecnico');

        // Mapa de traducción: ['cvegeo_municipio' => id_municipio]
        $this->municipiosCache = Municipio::pluck('id', 'cvegeo');
        // Cargamos los motivos en memoria para no consultar la BD en cada fila
        // Convertimos las claves a mayúsculas para evitar errores si escriben "nd" o "ND"
        $this->motivosCache = CatMotivoSinDato::all()->pluck('id', 'codigo')->mapWithKeys(fn($id, $codigo) => [strtoupper($codigo) => $id])->toArray();
    }

    /**
     * @param array $row
     *
     * @return \Illuminate\Database\Eloquent\Model|null
     */
    public function model(array $row)
    {
        // --- 1. PARCHE DE SEGURIDAD (Blindaje contra filas fantasma) ---
        // Si la fila no tiene ni nombre técnico ni ID, asumimos que es basura y la saltamos.
        if (!isset($row['variable_tecnico']) && !isset($row['variable_id'])) {
            return null;
        }

        // --- 2. INICIO DE LA LÓGICA CON NOMBRES TÉCNICOS ---

        // Recuperamos el nombre técnico de forma segura
        $nombreTecnico = $row['variable_tecnico'] ?? null;

        // Buscamos en el caché solo si tenemos un nombre
        $variableId = null;
        if ($nombreTecnico) {
            $variableId = $this->variablesCache[$nombreTecnico] ?? null;
        }

        // Fallback: Si no hay variable_tecnico o no coincide, buscamos variable_id
        if (!$variableId && isset($row['variable_id'])) {
            $variableId = $row['variable_id'];
        }

        // --- 3. Lógica de Municipio (Con seguridad isset) ---
        $municipioId = null;

        // Verificamos si existe la llave antes de usarla
        if (isset($row['municipio_cvegeo'])) {
            $municipioId = $this->municipiosCache[$row['municipio_cvegeo']] ?? null;
        }

        // Si no se encontró por cvegeo, intentamos usar municipio_id
        if (! $municipioId && isset($row['municipio_id'])) {
            $municipioId = $row['municipio_id'];
        }
        // --- FIN DE LA LÓGICA DE IDs ---


        // Si faltan datos clave, ignoramos la fila por completo.
        if (! $variableId || ! $municipioId || empty($row['anio'])) {
            return null;
        }

        // --- 4. LÓGICA INTELIGENTE: VALOR vs MOTIVO ---

        // Obtenemos el valor de forma segura
        $valorCelda = $row['valor'] ?? null;

        $valorFinal = null;
        $motivoId = null;

        // CASO A: Es un número
        if (is_numeric($valorCelda)) {
            $valorFinal = $valorCelda;
            $motivoId = null;
        }
        // CASO B: Es texto o vacío
        else {
            $valorFinal = null;
            $textoCodigo = strtoupper(trim((string)$valorCelda));

            // Verificamos si el código existe en el caché
            if (!empty($textoCodigo) && isset($this->motivosCache[$textoCodigo])) {
                $motivoId = $this->motivosCache[$textoCodigo];
            }
        }

        // Devolvemos la instancia del modelo
        return new DatoHistorico([
            'municipio_id'       => $municipioId,
            'variable_id'        => $variableId,
            'anio'               => $row['anio'],
            'valor'              => $valorFinal,
            'motivo_sin_dato_id' => $motivoId,
        ]);
    }

    // El resto de tus optimizaciones se quedan exactamente igual
    public function chunkSize(): int
    {
        return 1000;
    }

    public function batchSize(): int
    {
        return 1000;
    }

    public function uniqueBy()
    {
        return ['municipio_id', 'variable_id', 'anio'];
    }
}

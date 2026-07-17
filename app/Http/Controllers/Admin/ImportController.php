<?php

namespace App\Http\Controllers\Admin;

use App\Exports\PlantillaExport;
use App\Http\Controllers\Controller;
use App\Imports\DimensionesImport;
use App\Imports\IndicadorsImport;
use App\Imports\InstrumentoMunicipioImport;
use App\Imports\InstrumentosImport;
use App\Imports\TematicasImport;
use App\Imports\VariablesImport;
use App\Models\Dimension;
use App\Models\Indicador;
use App\Models\Municipio;
use App\Models\Variable;
use App\Models\LoteDatos;
use App\Services\LoteDatosService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;

class ImportController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:datos.importar|catalogos.importar|instrumentos.importar')->only(['index', 'descargarPlantilla']);
        $this->middleware('permission:catalogos.importar')->only([
            'importDimensiones', 'importTematicas', 'importIndicadores', 'importVariables',
        ]);
        $this->middleware('permission:datos.importar')->only([
            'validateDatos', 'validateDatos1', 'importDatos', 'importDatosComplejos',
        ]);
        $this->middleware('permission:instrumentos.importar')->only([
            'importInstrumentos', 'importInstrumentosAsignacion',
        ]);
    }

    /**
     * Display the resource import page.
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        return view('import.index');
    }

    /**
     * Handles the upload and import of the Dimensiones catalog from an Excel/CSV file.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function importDimensiones(Request $request)
    {
        $request->validate(['archivo' => 'required|mimes:xlsx,xls,csv']);

        try {
            Excel::import(new DimensionesImport, $request->file('archivo'));
            return back()->with('success', '¡Catálogo de Dimensiones importado correctamente!');
        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Ocurrió un error al importar Dimensiones: ' . $e->getMessage()]);
        }
    }

    /**
     * Handles the upload and import of the Temáticas catalog from an Excel/CSV file.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function importTematicas(Request $request)
    {
        $request->validate(['archivo' => 'required|mimes:xlsx,xls,csv']);

        try {
            Excel::import(new TematicasImport, $request->file('archivo'));
            return back()->with('success', '¡Catálogo de Temáticas importado correctamente!');
        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Ocurrió un error al importar Temáticas: ' . $e->getMessage()]);
        }
    }

    /**
     * Handles the upload and import of the Indicadores catalog from an Excel/CSV file.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function importIndicadores(Request $request)
    {
        $request->validate(['archivo' => 'required|mimes:xlsx,xls,csv']);

        try {
            Excel::import(new IndicadorsImport, $request->file('archivo'));
            return back()->with('success', '¡Catálogo de indicadores importado correctamente!');
        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Ocurrió un error al importar indicadores: ' . $e->getMessage()]);
        }
    }

    /**
     * Handles the upload and import of the Variables catalog from an Excel/CSV file.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function importVariables(Request $request)
    {
        $request->validate(['archivo' => 'required|mimes:xlsx,xls,csv']);

        try {
            Excel::import(new VariablesImport, $request->file('archivo'));
            return back()->with('success', '¡Catálogo de variables importado correctamente!');
        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Ocurrió un error al importar variables: ' . $e->getMessage()]);
        }
    }

    /**
     * Procesa la carga del catálogo de Indicadores.
     */

    // public function importDatos(Request $request)
    // {
    //     $request->validate(['archivo' => 'required|mimes:xlsx,xls,csv']);

    //     try {
    //         Excel::import(new DatosImport, $request->file('archivo'));
    //         return back()->with('success', '¡Catálogo de datos historicos importado correctamente!');
    //     } catch (\Exception $e) {
    //         return back()->withErrors(['error' => 'Ocurrió un error al importar datos historicos: ' . $e->getMessage()]);
    //     }
    // }

    /**
     * Handles the upload and import of the Datos Históricos catalog from an Excel/CSV file.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function importDatos(Request $request, LoteDatosService $service)
    {
        $validated = $request->validate(['lote_id' => 'required|integer|exists:lotes_datos,id']);
        $lote = LoteDatos::findOrFail($validated['lote_id']);
        $service->enviarRevision($lote, auth()->user());

        return redirect()->route('admin.lotes-datos.show', $lote)
            ->with('success', 'El lote fue enviado a revisión. Los datos aún no son públicos.');
    }

    /**
     * Genera y descarga una plantilla de Excel (archivo con encabezados y diccionario de datos)
     * para la importación del catálogo especificado.
     *
     * @param  string  $tipo  El tipo de catálogo para el cual se requiere la plantilla ('dimensiones', 'tematicas', etc.).
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     */
    public function descargarPlantilla($tipo)
    {
        $headings           = [];
        $fileName           = 'plantilla.xlsx';
        $diccionarioDeDatos = []; // <--- Variable para nuestro diccionario

        switch ($tipo) {
            case 'dimensiones':
                $headings           = ['nombre', 'color', 'nombre_tecnico', 'orden'];
                $fileName           = 'plantilla_dimensiones.xlsx';
                $diccionarioDeDatos = [
                    ['nombre', 'Nombre de la dimensión.', 'Texto, ej: "Demografía"'],
                    ['color', 'Color hexadecimal para identificar la dimensión.', 'Ej: "#264653"'],
                    ['nombre_tecnico', 'Nombre técnico único de la dimensión.', 'Texto, ej: "demografia" (sin espacios, puede usar guiones bajos)'],
                    ['orden', 'Orden de aparición.', 'Número entero, ej: 1'],
                ];
                break;
            case 'tematicas':
                $headings           = ['dimension_tecnico', 'nombre', 'nombre_tecnico', 'orden'];
                $fileName           = 'plantilla_tematicas.xlsx';
                $diccionarioDeDatos = [
                    ['dimension_tecnico', 'Nombre tecnico de la dimensión.', 'Texto, ej: "demografia", debe coincidir con alguno en la pestaña "Catálogo Dimensiones"'],
                    ['nombre', 'Nombre de la temática.', 'Texto, ej: "Población"'],
                    ['nombre_tecnico', 'Nombre técnico único de la temática.', 'Texto, ej: "poblacion"'],
                    ['orden', 'Orden de aparición.', 'Número entero, ej: 1'],
                ];
                break;
            case 'indicadores':
                $headings           = ['tematica_tecnico', 'nombre_amigable', 'nombre_tecnico', 'descripcion', 'fuente', 'tipo_dato', 'tipo_grafico_default', 'metodo_calculo', 'solo_resumen', 'es_complejo', 'priorizar_total', 'orden'];
                $fileName           = 'plantilla_indicadores.xlsx';
                $diccionarioDeDatos = [
                    ['tematica_tecnico', 'Nombre técnico de la temática. ', 'Texto, ej: "gob_poblacion", debe coincidir con alguno en la pestaña "Catálogo Temáticas"'],
                    ['nombre_amigable', 'Nombre amigable del indicador.', 'Texto, ej: "Población Total"'],
                    ['nombre_tecnico', 'Nombre técnico único del indicador.', 'Texto, ej: "poblacion_total" (sin espacios, puede usar guiones bajos)'],
                    ['descripcion', 'Descripción detallada del indicador.', 'Texto, puede estar vacío'],
                    ['fuente', 'Fuente de donde se obtiene el dato.', 'Texto, puede estar vacío'],
                    ['tipo_dato', 'Tipo de dato que representa el indicador.', 'Texto, ej: "absoluto" o "porcentaje"'],
                    ['tipo_grafico_default', 'Tipo de gráfico recomendado para este indicador.', 'Texto, ej: "barras", "lineal", "piramide"'],
                    ['metodo_calculo', 'Método usado para calcular el indicador.', 'Texto, ej: "promedio", "suma", etc.'],
                    ['solo_resumen', 'Indica si el indicador es solo para resúmenes de municipio.', '0 o 1 (0 = No, 1 = Sí)'],
                    ['es_complejo', 'Indica si el indicador es complejo.', '0 o 1 (0 = No, 1 = Sí)'],
                    ['priorizar_total', 'Indica si se debe priorizar el total en los cálculos y visualizaciones.', '0 o 1 (0 = No, 1 = Sí)'],
                    ['orden', 'Orden de aparición.', 'Número entero, ej: 1'],
                ];
                break;
            case 'variables':
                $headings           = ['indicador_tecnico', 'nombre_tecnico', 'nombre_amigable', 'unidad_medida', 'es_kpi', 'mapeo_valores', 'orden'];
                $fileName           = 'plantilla_variables.xlsx';
                $diccionarioDeDatos = [
                    ['indicador_tecnico', 'Nombre técnico del indicador padre, es unico.', 'Texto, ej: "pob_total", debe coincidir con alguno en la pestaña "Catálogo Indicadores"'],
                    ['nombre_tecnico', 'Nombre técnico único de la variable.', 'Texto, ej: "poblacion_total"'],
                    ['nombre_amigable', 'Nombre amigable de la variable.', 'Texto, ej: "Población Total"'],
                    ['unidad_medida', 'Unidad de medida de la variable.', 'Texto, ej: "habitantes", puede estar vacío'],
                    ['es_kpi', 'Indica si la variable es un KPI (Indicador Clave de Desempeño).', '0 o 1 (0 = No, 1 = Sí)'],
                    ['mapeo_valores', 'Mapeo opcional de valores para categorías específicas.', 'JSON, ej: {"1":"Urbano","2":"Rural"}, puede estar vacío'],
                    ['orden', 'Orden de aparición.', 'Número entero, ej: 1'],
                ];
                break;

            case 'datos-historicos':
                $headings           = ['municipio_cvegeo', 'variable_tecnico', 'anio', 'valor', 'motivo_sin_dato'];
                $fileName           = 'plantilla_datos_historicos.xlsx';
                $diccionarioDeDatos = [
                    ['municipio_cvegeo', 'Clave Geoestadística del municipio (ej. 21001).', 'Obtener del catálogo de Municipios'],
                    ['variable_tecnico', 'Nombre técnico único de la variable.', 'Obtener del catálogo de Variables'],
                    ['anio', 'Año del dato en formato de 4 dígitos.', 'Ej: 2023'],
                    ['valor', 'Valor numérico del dato. Si no hay dato, dejar la celda en blanco.', 'Ej: 123.45'],
                    ['motivo_sin_dato', 'Motivo por el que no hay dato.', 'Texto, ej: "ND", "C", "ND"'],
                ];
                break;
            case 'catalogo-instrumentos':
                $headings           = ['nombre'];
                $fileName           = 'plantilla_catalogo_instrumentos.xlsx';
                $diccionarioDeDatos = [
                    ['nombre', 'Nombre amigable del instrumento.', 'Texto, ej: "Plan de Desarrollo Urbano".'],
                ];
                break;
            case 'asignacion-instrumentos':
                $headings           = ['municipio_cvegeo', 'instrumento_nombre', 'anio'];
                $fileName           = 'plantilla_asignacion_instrumentos.xlsx';
                $diccionarioDeDatos = [
                    ['municipio_cvegeo', 'Clave Geoestadística del municipio (ej. 21001).', 'Obtener del catálogo de Municipios'],
                    ['instrumento_nombre', 'Nombre amigable del instrumento.', 'Debe coincidir con uno del Catálogo de Instrumentos'],
                    ['anio', 'Año del instrumento en formato de 4 dígitos.', 'Ej: 2023'],
                ];
                break;
            case 'datos-complejos':
                $headings           = ['municipio_id', 'anio', 'Ejemplo de nombre cultivo 1', 'Ejemplo de nombre cultivo 2'];
                $fileName           = 'plantilla_datos_complejos.xlsx';
                $diccionarioDeDatos = [
                    ['municipio_id', 'ID numérico del municipio.', 'Obtener de la pestaña "Catálogo Municipios"'],
                    ['anio', 'Año del dato en formato de 4 dígitos.', 'Ej: 2023'],
                    ['Ejemplo de nombre cultivo 1', 'Nombre del cultivo tal como aparece (puede tener espacios y acentos).', 'Ej: "Maíz Grano".'],
                    ['Ejemplo de nombre cultivo 2', 'Nombre del cultivo tal como aparece (puede tener espacios y acentos).', 'Ej: "Cebada".'],
                ];
                break;
        }

        if (empty($headings)) {
            abort(404, 'Tipo de plantilla no encontrado.');
        }

        // Pasamos tanto las cabeceras como el diccionario al export
        return Excel::download(new PlantillaExport($headings, $diccionarioDeDatos), $fileName);
    }

    /**
     * Handles the upload and import of complex historical data (Cultivos)
     * for a specific Indicator ID from an Excel/CSV file.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function importDatosComplejos(Request $request, LoteDatosService $service)
    {
        $request->validate([
            'indicador_id' => 'required|integer|exists:indicadors,id',
            'archivo'      => 'required|file|mimes:xlsx,xls,csv',
        ]);

        $indicador = Indicador::whereKey($request->integer('indicador_id'))
            ->where('es_complejo', true)
            ->first();
        if (!$indicador) {
            return back()->withErrors(['indicador_id' => 'El indicador seleccionado no es complejo.']);
        }

        $result = $service->crearBorradorComplejo($request->file('archivo'), $indicador, auth()->user());
        if (isset($result['errors'])) {
            return back()->withErrors(['archivo' => collect($result['errors'])->pluck('error')->implode(' ')]);
        }

        $lote = $result['lote'];
        $service->enviarRevision($lote, auth()->user());

        return redirect()->route('admin.lotes-datos.show', $lote)
            ->with('success', 'El lote de datos complejos fue validado y enviado a revisión.');
    }

    // public function descargarPlantilla($tipo)
    // {
    //     $headings = [];
    //     $fileName = 'plantilla.xlsx';

    //     switch ($tipo) {
    //         case 'dimensiones':
    //             $headings = ['nombre', 'color'];
    //             $fileName = 'plantilla_dimensiones.xlsx';
    //             break;

    //         case 'tematicas':
    //             $headings = ['dimension_id', 'dimension_nombre', 'nombre'];
    //             $fileName = 'plantilla_tematicas.xlsx';
    //             break;

    //         case 'indicadores':
    //             $headings = ['tematica_id','tematica_nombre', 'nombre_amigable', 'descripcion', 'fuente', 'tipo_dato', 'tipo_grafico_default', 'metodo_calculo', 'solo_resumen', 'es_complejo', 'priorizar_total'];
    //             $fileName = 'plantilla_indicadores.xlsx';
    //             break;

    //         case 'variables':
    //             $headings = ['indicador_id','indicador_nombre', 'nombre_tecnico', 'nombre_amigable', 'unidad_medida', 'es_kpi', 'mapeo_valores'];
    //             $fileName = 'plantilla_variables.xlsx';
    //             break;

    //         case 'datos-historicos':
    //             $headings = ['municipio_id', 'variable_id', 'variable_nombre_tecnico', 'anio', 'valor'];
    //             $fileName = 'plantilla_datos_historicos.xlsx';
    //             break;
    //     }

    //     if (empty($headings)) {
    //         abort(404, 'Tipo de plantilla no encontrado.');
    //     }

    //     return Excel::download(new PlantillaExport($headings), $fileName);
    // }

    /**
     * Valida el archivo de datos históricos sin guardarlo en la BD (Dry Run).
     */

    /**
     * Validates the structure and content of the historical data file before final import.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function validateDatos(Request $request, LoteDatosService $service)
    {
        $request->validate(['archivo_datos' => 'required|file|mimes:xlsx,xls,csv']);
        $file = $request->file('archivo_datos');
        if (!$file || !$file->isValid() || !$file->getPathname() || !is_file($file->getPathname())) {
            return response()->json([
                'success' => false,
                'errors' => [[
                    'fila' => 1,
                    'error' => 'El archivo no pudo ser recibido correctamente. Vuelve a seleccionarlo e inténtalo de nuevo.',
                ]],
            ], 422);
        }

        $result = $service->crearBorrador($file, auth()->user());

        if (isset($result['errors'])) {
            return response()->json(['success' => false, 'errors' => $result['errors']], 422);
        }

        $lote = $result['lote'];
        return response()->json([
            'success' => true,
            'message' => "Archivo validado. Se creó el lote #{$lote->id} con {$lote->total_filas} registros.",
            'lote_id' => $lote->id,
            'detalle_url' => route('admin.lotes-datos.show', $lote),
        ]);
    }

    // Old validateDatos method for reference
    public function validateDatos1(Request $request)
    {
        Log::info('--- ImportController: Iniciando validateDatos ---');
        $request->validate(['archivo_datos' => 'required|file|mimes:xlsx,xls']);

        $file     = $request->file('archivo_datos');
        $rows     = Excel::toCollection(null, $file)->first();
        $headings = $rows->shift()->toArray();
        $errors   = [];

        // Obtenemos los IDs válidos una sola vez para eficiencia
        $validMunicipioIds = Municipio::pluck('id')->all();
        $validVariableIds  = Variable::pluck('id')->all();

        foreach ($rows as $rowIndex => $row) {
            $rowData = array_combine($headings, $row->toArray());

            if (empty($rowData['municipio_id']) || ! in_array($rowData['municipio_id'], $validMunicipioIds)) {
                $errors[] = ['fila' => $rowIndex + 2, 'error' => "El 'municipio_id' (" . ($rowData['municipio_id'] ?? 'vacío') . ") no es válido."];
            }
            if (empty($rowData['variable_id']) || ! in_array($rowData['variable_id'], $validVariableIds)) {
                $errors[] = ['fila' => $rowIndex + 2, 'error' => "La 'variable_id' (" . ($rowData['variable_id'] ?? 'vacío') . ") no es válida."];
            }
            if (! is_numeric($rowData['valor'])) {
                $errors[] = ['fila' => $rowIndex + 2, 'error' => "El 'valor' (" . ($rowData['valor'] ?? 'vacío') . ") debe ser un número."];
            }
        }

        if (count($errors) > 0) {
            Log::warning('--- ImportController: Se encontraron errores de validación. ---', $errors);
            // Si hay errores, por ahora devolvemos un JSON con los errores.
            // Más adelante podríamos generar un Excel de errores.

            return response()->json(['success' => false, 'errors' => $errors], 422);
        }

        // Si no hay errores, guardamos el archivo temporalmente
        $path = $file->store('temp_imports', 'local');
        Log::info('--- ImportController: Validación exitosa. Archivo temporal guardado en: ' . $path . ' ---');
        return response()->json([
            'success' => true,
            'message' => '¡Archivo validado con éxito! Se encontraron ' . count($rows) . ' registros listos para importar.',
            'path'    => $path,
        ]);
    }

    /**
     * Handles the upload and import of the Instrumentos catalog from an Excel/CSV file.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function importInstrumentos(Request $request)
    {
        $request->validate(['archivo' => 'required|file|mimes:xlsx,xls,csv']);
        try {
            Excel::import(new InstrumentosImport, $request->file('archivo'));
            return back()->with('success', '¡El catálogo de instrumentos se ha importado con éxito!');
        } catch (\Exception $e) {
            return back()->withErrors(['msg' => 'Ocurrió un error: ' . $e->getMessage()]);
        }
    }

    /**
     * Handles the upload and import of Instrumento-Municipio assignments from an Excel/CSV file.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function importInstrumentosAsignacion(Request $request)
    {
        $request->validate(['archivo' => 'required|file|mimes:xlsx,xls,csv']);
        try {
            Excel::import(new InstrumentoMunicipioImport, $request->file('archivo'));
            return back()->with('success', '¡Las asignaciones de instrumentos se han importado con éxito!');
        } catch (\Exception $e) {
            return back()->withErrors(['msg' => 'Ocurrió un error: ' . $e->getMessage()]);
        }
    }
}

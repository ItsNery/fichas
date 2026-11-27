<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class ApiDocumentationController extends Controller
{
    public function index()
    {
        return view('api.docs');
    }

    public function openapi(Request $request)
    {
        $url = url('/');

        $openapi = [
            'openapi' => '3.0.3',
            'info' => [
                'title' => 'API Pública - Fichas Municipales',
                'version' => '1.0.0',
                'description' => 'Documentación pública para acceder a municipios, indicadores, metadatos y datos estadísticos.',
            ],
            'servers' => [
                [
                    'url' => $url,
                    'description' => 'Servidor principal',
                ],
            ],
            'paths' => [
                '/api/v1/municipios' => [
                    'get' => [
                        'summary' => 'Lista de municipios',
                        'description' => 'Devuelve el catálogo de municipios registrados.',
                        'responses' => [
                            '200' => [
                                'description' => 'Listado de municipios',
                                'content' => [
                                    'application/json' => [
                                        'schema' => ['$ref' => '#/components/schemas/MunicipioResponse'],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                '/api/v1/microrregiones' => [
                    'get' => [
                        'summary' => 'Lista de microrregiones',
                        'responses' => [
                            '200' => [
                                'description' => 'Listado de microrregiones',
                                'content' => [
                                    'application/json' => [
                                        'schema' => ['$ref' => '#/components/schemas/MicroMacrorregionResponse'],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                '/api/v1/macrorregiones' => [
                    'get' => [
                        'summary' => 'Lista de macrorregiones',
                        'responses' => [
                            '200' => [
                                'description' => 'Listado de macrorregiones',
                                'content' => [
                                    'application/json' => [
                                        'schema' => ['$ref' => '#/components/schemas/MicroMacrorregionResponse'],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                '/api/v1/indicadores' => [
                    'get' => [
                        'summary' => 'Lista de indicadores',
                        'parameters' => [
                            [
                                'name' => 'tematica_id',
                                'in' => 'query',
                                'schema' => ['type' => 'integer'],
                                'description' => 'Filtra indicadores por temática',
                            ],
                            [
                                'name' => 'dimension_id',
                                'in' => 'query',
                                'schema' => ['type' => 'integer'],
                                'description' => 'Filtra indicadores por dimensión',
                            ],
                        ],
                        'responses' => [
                            '200' => [
                                'description' => 'Listado de indicadores',
                                'content' => [
                                    'application/json' => [
                                        'schema' => ['$ref' => '#/components/schemas/IndicadorListResponse'],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                '/api/v1/indicadores/{id}' => [
                    'get' => [
                        'summary' => 'Detalle de un indicador',
                        'parameters' => [
                            [
                                'name' => 'id',
                                'in' => 'path',
                                'required' => true,
                                'schema' => ['type' => 'integer'],
                                'description' => 'ID del indicador',
                            ],
                        ],
                        'responses' => [
                            '200' => [
                                'description' => 'Indicador encontrado',
                                'content' => [
                                    'application/json' => [
                                        'schema' => ['$ref' => '#/components/schemas/IndicadorResponse'],
                                    ],
                                ],
                            ],
                            '404' => [
                                'description' => 'Indicador no encontrado',
                            ],
                        ],
                    ],
                ],
                '/api/v1/metadata' => [
                    'get' => [
                        'summary' => 'Metadatos públicos',
                        'description' => 'Devuelve dimensiones, indicadores y variables disponibles.',
                        'responses' => [
                            '200' => [
                                'description' => 'Metadatos de catálogo',
                                'content' => [
                                    'application/json' => [
                                        'schema' => ['$ref' => '#/components/schemas/MetadataResponse'],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                '/api/v1/data' => [
                    'get' => [
                        'summary' => 'Consulta de datos mediante parámetros',
                        'description' => 'Acepta los mismos parámetros que POST /api/v1/consulta en la cadena de consulta. Use la notación de arreglos de PHP, por ejemplo municipio_ids[]=114.',
                        'parameters' => [
                            ['name' => 'indicador_id', 'in' => 'query', 'required' => true, 'schema' => ['type' => 'integer']],
                            ['name' => 'nivel_de_agregacion', 'in' => 'query', 'required' => true, 'schema' => ['type' => 'string', 'enum' => ['municipio', 'microrregion', 'macrorregion', 'estatal']]],
                            ['name' => 'municipio_ids[]', 'in' => 'query', 'schema' => ['type' => 'array', 'items' => ['type' => 'string']], 'style' => 'form', 'explode' => true],
                            ['name' => 'region_id', 'in' => 'query', 'schema' => ['type' => 'integer']],
                            ['name' => 'anios[]', 'in' => 'query', 'schema' => ['type' => 'array', 'items' => ['type' => 'integer']], 'style' => 'form', 'explode' => true],
                        ],
                        'responses' => [
                            '200' => ['description' => 'Datos procesados', 'content' => ['application/json' => ['schema' => ['$ref' => '#/components/schemas/DataResponse']]]],
                            '422' => ['description' => 'Validación de parámetros fallida'],
                            '500' => ['description' => 'Error al procesar la consulta'],
                        ],
                    ],
                    'post' => [
                        'summary' => 'Consulta de datos',
                        'description' => 'Obtiene datos estadísticos para un indicador y selección geográfica.',
                        'requestBody' => [
                            'required' => true,
                            'content' => [
                                'application/json' => [
                                    'schema' => ['$ref' => '#/components/schemas/DataRequest'],
                                ],
                            ],
                        ],
                        'responses' => [
                            '200' => [
                                'description' => 'Datos de indicador procesados',
                                'content' => [
                                    'application/json' => [
                                        'schema' => ['$ref' => '#/components/schemas/DataResponse'],
                                    ],
                                ],
                            ],
                            '422' => [
                                'description' => 'Validación de parámetros fallida',
                            ],
                            '500' => [
                                'description' => 'Error al procesar la consulta',
                            ],
                        ],
                    ],
                ],
                '/api/v1/consulta' => [
                    'post' => [
                        'summary' => 'Consulta de datos estadísticos',
                        'description' => 'Consulta un indicador con una selección geográfica y de años. La forma de los datos depende del indicador.',
                        'requestBody' => [
                            'required' => true,
                            'content' => ['application/json' => ['schema' => ['$ref' => '#/components/schemas/DataRequest']]],
                        ],
                        'responses' => [
                            '200' => ['description' => 'Datos procesados', 'content' => ['application/json' => ['schema' => ['$ref' => '#/components/schemas/DataResponse']]]],
                            '422' => ['description' => 'Validación de parámetros fallida'],
                            '500' => ['description' => 'Error al procesar la consulta'],
                        ],
                    ],
                ],
            ],
            'components' => [
                'schemas' => [
                    'BaseResponse' => [
                        'type' => 'object',
                        'properties' => [
                            'success' => ['type' => 'boolean'],
                            'message' => ['type' => 'string'],
                        ],
                    ],
                    'MunicipioResponse' => [
                        'allOf' => [
                            ['$ref' => '#/components/schemas/BaseResponse'],
                            [
                                'type' => 'object',
                                'properties' => [
                                    'data' => [
                                        'type' => 'array',
                                        'items' => ['type' => 'object'],
                                    ],
                                ],
                            ],
                        ],
                    ],
                    'MicroMacrorregionResponse' => [
                        'allOf' => [
                            ['$ref' => '#/components/schemas/BaseResponse'],
                            [
                                'type' => 'object',
                                'properties' => [
                                    'data' => [
                                        'type' => 'array',
                                        'items' => ['type' => 'object'],
                                    ],
                                ],
                            ],
                        ],
                    ],
                    'IndicadorListResponse' => [
                        'allOf' => [
                            ['$ref' => '#/components/schemas/BaseResponse'],
                            [
                                'type' => 'object',
                                'properties' => [
                                    'data' => [
                                        'type' => 'array',
                                        'items' => ['type' => 'object'],
                                    ],
                                ],
                            ],
                        ],
                    ],
                    'IndicadorResponse' => [
                        'allOf' => [
                            ['$ref' => '#/components/schemas/BaseResponse'],
                            [
                                'type' => 'object',
                                'properties' => [
                                    'data' => ['type' => 'object'],
                                ],
                            ],
                        ],
                    ],
                    'MetadataResponse' => [
                        'allOf' => [
                            ['$ref' => '#/components/schemas/BaseResponse'],
                            [
                                'type' => 'object',
                                'properties' => [
                                    'data' => [
                                        'type' => 'object',
                                        'properties' => [
                                            'dimensiones' => ['type' => 'array', 'items' => ['type' => 'object']],
                                            'indicadores' => ['type' => 'array', 'items' => ['type' => 'object']],
                                            'variables' => ['type' => 'array', 'items' => ['type' => 'object']],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                    'DataRequest' => [
                        'type' => 'object',
                        'required' => ['indicador_id', 'nivel_de_agregacion'],
                        'properties' => [
                            'indicador_id' => ['type' => 'integer', 'description' => 'Identificador existente; consulte el catálogo de indicadores.'],
                            'nivel_de_agregacion' => ['type' => 'string', 'enum' => ['municipio', 'microrregion', 'macrorregion', 'estatal']],
                            'municipio_ids' => ['type' => 'array', 'nullable' => true, 'description' => 'Selección de municipios para el nivel municipal.', 'items' => ['type' => 'string']],
                            'region_id' => ['type' => 'integer', 'nullable' => true, 'description' => 'Identificador de la micro o macrorregión seleccionada.'],
                            'anios' => ['type' => 'array', 'nullable' => true, 'description' => 'Años que se desean consultar.', 'items' => ['type' => 'integer']],
                        ],
                    ],
                    'DataResponse' => [
                        'allOf' => [
                            ['$ref' => '#/components/schemas/BaseResponse'],
                            [
                                'type' => 'object',
                                'properties' => [
                                    'data' => ['type' => 'object'],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        return response()->json($openapi);
    }
}

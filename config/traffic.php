<?php

return [
    'retention_days' => (int) env('TRAFFIC_EVENT_RETENTION_DAYS', 7),

    // Habilita el perfilado de una comparativa concreta con el encabezado
    // X-Comparison-Profile: TOKEN.
    // Nunca se activa si el token no está configurado.
    'comparison_profile_token' => env('COMPARISON_PROFILE_TOKEN'),

    'routes' => [
        'municipios.comparar',
        'municipios.comparar.dimension',
        'municipios.comparar.pdf',
        'municipios.dimension',
        'municipios.perfil.pdf',
        'municipios.panorama.pdf',
    ],
];

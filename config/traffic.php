<?php

return [
    'retention_days' => (int) env('TRAFFIC_EVENT_RETENTION_DAYS', 7),

    // Habilita el perfilado de una comparativa concreta con el encabezado
    // X-Comparison-Profile: TOKEN.
    // Nunca se activa si el token no está configurado.
    'comparison_profile_token' => env('COMPARISON_PROFILE_TOKEN'),

    'routes' => [
        'ficha-municipal.comparar',
        'ficha-municipal.comparar.pdf',
        'ficha-municipal.v3.pdf',
        'ficha-municipal.perfil.pdf',
        'ficha-municipal.panorama.pdf',
        'ficha-municipal.pdf',
    ],
];

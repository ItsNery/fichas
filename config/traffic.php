<?php

return [
    'retention_days' => (int) env('TRAFFIC_EVENT_RETENTION_DAYS', 7),

    // Habilita el perfilado de una comparativa concreta con el encabezado
    // X-Comparison-Profile: TOKEN.
    // Nunca se activa si el token no está configurado.
    'comparison_profile_token' => env('COMPARISON_PROFILE_TOKEN'),

    // Identificador aleatorio de primera parte creado en el navegador. Sólo
    // se registra como HMAC para correlacionar eventos, nunca como identidad.
    'visitor_cookie' => env('TRAFFIC_VISITOR_COOKIE', 'sei_visitor_id'),

    'routes' => [
        'municipios.comparar',
        'municipios.comparar.dimension',
        'municipios.comparar.pdf',
        'municipios.dimension',
        'municipios.perfil.pdf',
        'municipios.panorama.pdf',
    ],
];

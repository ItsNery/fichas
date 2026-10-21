<?php

return [
    'retention_days' => (int) env('TRAFFIC_EVENT_RETENTION_DAYS', 7),

    'routes' => [
        'ficha-municipal.comparar',
        'ficha-municipal.comparar.pdf',
        'ficha-municipal.v3.pdf',
        'ficha-municipal.perfil.pdf',
        'ficha-municipal.panorama.pdf',
        'ficha-municipal.pdf',
    ],
];

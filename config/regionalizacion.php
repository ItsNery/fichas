<?php

return [
    'url' => env('REGIONALIZACION_OFICIAL_URL', 'https://planeader.puebla.gob.mx/regionalizacion'),
    'macrorregiones' => (int) env('REGIONALIZACION_MACRORREGIONES', 7),
    'microrregiones' => (int) env('REGIONALIZACION_MICRORREGIONES', 31),
    'fallback_hero' => env('REGIONALIZACION_HERO_IMAGE', 'img/fondos/Fondo-hero.webp'),
];

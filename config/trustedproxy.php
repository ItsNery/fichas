<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Proxies confiables
    |--------------------------------------------------------------------------
    |
    | Sólo las conexiones que provengan de estas direcciones pueden definir
    | X-Forwarded-For. No utilices "*": permitiría falsificar la IP de origen
    | cuando la aplicación sea accesible sin pasar por el balanceador.
    |
    */
    'proxies' => env('TRUSTED_PROXIES', '10.1.4.9,10.1.4.15'),
];

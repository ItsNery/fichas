<?php

return [
    /*
     * Política base deliberadamente compatible con los scripts y recursos
     * externos ya usados por las vistas. Puede hacerse más estricta por ruta.
     */
    'content_security_policy' => "base-uri 'self'; form-action 'self'; frame-ancestors 'self'; object-src 'none'",

    // Solo se envía en respuestas HTTPS.
    'hsts_max_age' => (int) env('SECURITY_HSTS_MAX_AGE', 31536000),
];

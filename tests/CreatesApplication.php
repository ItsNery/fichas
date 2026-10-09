<?php

namespace Tests;

use Illuminate\Contracts\Console\Kernel;

trait CreatesApplication
{
    /**
     * Creates the application.
     *
     * @return \Illuminate\Foundation\Application
     */
    public function createApplication()
    {
        $app = require __DIR__.'/../bootstrap/app.php';

        $app->make(Kernel::class)->bootstrap();

        $environment = $app->environment();
        $connection = $app['config']->get('database.default');
        $database = $app['config']->get('database.connections.sqlite.database');

        if ($environment !== 'testing' || $connection !== 'sqlite' || $database !== ':memory:') {
            throw new \RuntimeException(sprintf(
                'Pruebas bloqueadas: se requiere APP_ENV=testing y SQLite :memory:; configuración actual: APP_ENV=%s, conexión=%s, base=%s.',
                $environment,
                $connection,
                $database,
            ));
        }

        return $app;
    }
}

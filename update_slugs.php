<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

foreach(\App\Models\Macrorregion::all() as $m) {
    $m->slug = \Illuminate\Support\Str::slug($m->nombre);
    $m->save();
}

foreach(\App\Models\Microrregion::all() as $m) {
    $m->slug = \Illuminate\Support\Str::slug($m->nombre);
    $m->save();
}

foreach(\App\Models\Municipio::all() as $m) {
    $m->slug = \Illuminate\Support\Str::slug($m->nombre);
    $m->save();
}

echo "Slugs actualizados correctamente.\n";

<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Municipio;
use Illuminate\Support\Str;

$count = 0;
foreach (Municipio::all() as $m) {
    if (empty($m->slug)) {
        $m->slug = Str::slug($m->nombre);
        $m->save();
        $count++;
        echo "Generando slug para: {$m->nombre} -> {$m->slug}\n";
    }
}

echo "\nCompletado. Se generaron {$count} slugs.\n";

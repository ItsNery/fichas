<?php
use App\Models\ConfiguracionFicha;
use App\Models\Municipio;
use App\Http\Controllers\FichaController;

require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "Running Verification Test for Module 1 Backend...\n";

// Get our newly added scatter config
$config = ConfiguracionFicha::where('tipo_visualizacion', 'scatter')->first();

if (!$config) {
    die("ERROR: Scatter plot card configuration not found in database. Did you run the migrations?\n");
}

echo "Found Configuration ID: {$config->id}\n";
echo "Title: '{$config->titulo_reporte}'\n";
echo "Section: '{$config->seccion}'\n";

// Pick a test municipality (e.g. Puebla or the first available)
$municipio = Municipio::first();
if (!$municipio) {
    die("ERROR: No municipalities found in database.\n");
}

echo "Testing with Municipio: '{$municipio->nombre}' (ID: {$municipio->id})\n";

// Create instance of FichaController to invoke the private/protected method
// We will use Reflection to invoke the private method obtenerDatosParaConfig
$controller = new FichaController();
$reflection = new \ReflectionClass(FichaController::class);
$method = $reflection->getMethod('obtenerDatosParaConfig');
$method->setAccessible(true);

try {
    $result = $method->invoke($controller, $config, $municipio);
    
    echo "\nVerification Success!\n";
    echo "=====================\n";
    echo "Year (Anio): {$result['anio']}\n";
    echo "Description: {$result['descripcion']}\n";
    echo "Source: {$result['fuente']}\n";
    
    $echarts = $result['echarts'];
    echo "ECharts Chart Type: {$echarts['type']}\n";
    echo "ECharts Axis X Title: {$echarts['eje_x']['titulo']}\n";
    echo "ECharts Axis Y Title: {$echarts['eje_y']['titulo']}\n";
    
    $seriesNormal = $echarts['series'][0];
    $seriesHighlight = $echarts['series'][1];
    
    echo "Other municipalities data points count: " . count($seriesNormal['data']) . "\n";
    echo "Highlighted municipality data points count: " . count($seriesHighlight['data']) . "\n";
    if (!empty($seriesHighlight['data'])) {
        echo "Highlighted point value: " . json_encode($seriesHighlight['data'][0]) . "\n";
    }
} catch (\Exception $e) {
    echo "ERROR during execution: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
}
?>

<?php

namespace Tests\Feature\Services;

use App\Services\CachedPdfService;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CachedPdfServiceTest extends TestCase
{
    public function test_it_reuses_a_pdf_when_the_rendered_html_is_unchanged(): void
    {
        Storage::fake('local');
        $generations = 0;
        $service = app(CachedPdfService::class);

        $first = $service->remember('perfil/17', '<html>versión uno</html>', function () use (&$generations) {
            $generations++;

            return 'pdf generado';
        });
        $second = $service->remember('perfil/17', '<html>versión uno</html>', function () use (&$generations) {
            $generations++;

            return 'no debe generarse';
        });

        $this->assertSame('pdf generado', $first);
        $this->assertSame('pdf generado', $second);
        $this->assertSame(1, $generations);
        Storage::disk('local')->assertExists('fichas-pdf/perfil/17.pdf');
        Storage::disk('local')->assertExists('fichas-pdf/perfil/17.sha256');
    }

    public function test_it_regenerates_a_pdf_when_the_rendered_html_changes(): void
    {
        Storage::fake('local');
        $service = app(CachedPdfService::class);

        $service->remember('panorama/17', '<html>versión uno</html>', fn () => 'pdf uno');
        $pdf = $service->remember('panorama/17', '<html>versión dos</html>', fn () => 'pdf dos');

        $this->assertSame('pdf dos', $pdf);
        Storage::disk('local')->assertExists('fichas-pdf/panorama/17.pdf');
    }
}

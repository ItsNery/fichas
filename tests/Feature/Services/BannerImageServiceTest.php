<?php

namespace Tests\Feature\Services;

use App\Models\Municipio;
use App\Services\BannerImageService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class BannerImageServiceTest extends TestCase
{
    public function test_it_leaves_the_banner_empty_when_wikipedia_has_no_image(): void
    {
        $municipio = new Municipio(['id' => 999999, 'nombre' => 'Municipio Sin Imagen De Prueba']);
        $cacheKey = 'banner_wiki_municipio-sin-imagen-de-prueba';

        Cache::forget($cacheKey);
        Http::fake(['es.wikipedia.org/*' => Http::response([], 404)]);

        $result = app(BannerImageService::class)->resolve($municipio);

        $this->assertSame('fallback', $result['source']);
        $this->assertNull($result['url']);
        $this->assertNull($result['attribution']);

        Cache::forget($cacheKey);
    }
}

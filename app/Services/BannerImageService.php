<?php

namespace App\Services;

use App\Models\Municipio;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class BannerImageService
{
    private const CACHE_TTL = 604800; // 7 días
    private const WIKI_API = 'https://es.wikipedia.org/api/rest_v1/page/summary';
    private const COMMONS_API = 'https://commons.wikimedia.org/w/api.php';

    private const MACRORREGION_FALLBACKS = [
        'Sierra Norte' => [
            'url' => 'https://images.unsplash.com/photo-1506905925346-21bda4d32df4?w=1920&q=80',
            'attribution' => [
                'author' => 'eberhard grossgasteiger',
                'license' => 'Unsplash',
                'source_url' => 'https://unsplash.com/photos/JCl8Vc9c1Tg',
            ],
            'note' => 'Imagen representativa de paisaje montañoso',
        ],
        'Sierra Nororiental' => [
            'url' => 'https://images.unsplash.com/photo-1464822759023-fed622ff2c3b?w=1920&q=80',
            'attribution' => [
                'author' => 'Luca Bravo',
                'license' => 'Unsplash',
                'source_url' => 'https://unsplash.com/photos/zBdhuQqkPq0',
            ],
            'note' => 'Imagen representativa de paisaje montañoso',
        ],
        'Valle de Serdán' => [
            'url' => 'https://images.unsplash.com/photo-1504198453319-5ce911bafcde?w=1920&q=80',
            'attribution' => [
                'author' => 'Luca Bravo',
                'license' => 'Unsplash',
                'source_url' => 'https://unsplash.com/photos/Tk7hNezEaHA',
            ],
            'note' => 'Imagen representativa de valle',
        ],
        'Angelópolis' => [
            'url' => 'https://images.unsplash.com/photo-1599940824399-b11787f2a01e?w=1920&q=80',
            'attribution' => [
                'author' => 'Gabriela Sánchez',
                'license' => 'Unsplash',
                'source_url' => 'https://unsplash.com/photos/MmP6zVJ7HnE',
            ],
            'note' => 'Imagen representativa de zona urbana',
        ],
        'Valle de Atlixco y Matamoros' => [
            'url' => 'https://images.unsplash.com/photo-1590608897129-79c46d0e4f3e?w=1920&q=80',
            'attribution' => [
                'author' => 'Javier Miranda',
                'license' => 'Unsplash',
                'source_url' => 'https://unsplash.com/photos/S9tS2Mokuf8',
            ],
            'note' => 'Imagen representativa de valle agrícola',
        ],
        'Mixteca' => [
            'url' => 'https://images.unsplash.com/photo-1509316785289-025f5b846b35?w=1920&q=80',
            'attribution' => [
                'author' => 'Jukan Tateisi',
                'license' => 'Unsplash',
                'source_url' => 'https://unsplash.com/photos/bJhT_8nbUA0',
            ],
            'note' => 'Imagen representativa de paisaje semiárido',
        ],
        'Tehuacán y Sierra Negra' => [
            'url' => 'https://images.unsplash.com/photo-1509316785289-025f5b846b35?w=1920&q=80',
            'attribution' => [
                'author' => 'Jukan Tateisi',
                'license' => 'Unsplash',
                'source_url' => 'https://unsplash.com/photos/bJhT_8nbUA0',
            ],
            'note' => 'Imagen representativa de paisaje semiárido',
        ],
    ];

    public function resolve(Municipio $municipio): array
    {
        $wiki = $this->getWikipediaImage($municipio->nombre);
        if ($wiki) {
            return $wiki;
        }

        $fallback = $this->getRegionalFallback($municipio);
        if ($fallback) {
            return $fallback;
        }

        return $this->getPicsumFallback($municipio);
    }

    public function getWikipediaImage(string $nombre): ?array
    {
        $cacheKey = 'banner_wiki_' . Str::slug($nombre);

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($nombre) {
            $slug = str_replace(' ', '_', $nombre);

            $intents = [
                "Municipio_de_{$slug}_(Puebla)",
                "{$slug}_(Puebla)",
                "Municipio_de_{$slug}",
                "{$slug},_Puebla",
            ];

            foreach ($intents as $title) {
                try {
                    $response = Http::timeout(5)->withHeaders([
                        'User-Agent' => 'PortalMunicipalPuebla/1.0 (nery.pozos@puebla.gob.mx)',
                    ])->get(self::WIKI_API . '/' . urlencode($title));

                    if (!$response->successful() || $response->json('type') === 'disambiguation') {
                        continue;
                    }

                    $image = $response->json('originalimage');
                    if (!$image || empty($image['source'])) {
                        continue;
                    }

                    $attribution = $this->fetchCommonsAttribution($image['source']);
                    $pageUrl = $response->json('content_urls.desktop.page');

                    return [
                        'source' => 'wikipedia',
                        'url' => $image['source'],
                        'attribution' => $attribution,
                        'page_url' => $pageUrl,
                    ];
                } catch (\Exception $e) {
                    Log::warning("BannerImage: Wikipedia timeout para '{$title}': " . $e->getMessage());
                }
            }

            return null;
        });
    }

    private function fetchCommonsAttribution(string $imageUrl): ?array
    {
        $filename = $this->extractFilename($imageUrl);
        if (!$filename) {
            return null;
        }

        $cacheKey = 'banner_commons_' . $filename;

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($filename, $imageUrl) {
            try {
                $response = Http::timeout(5)->withHeaders([
                    'User-Agent' => 'PortalMunicipalPuebla/1.0 (nery.pozos@puebla.gob.mx)',
                ])->get(self::COMMONS_API, [
                    'action' => 'query',
                    'prop' => 'imageinfo',
                    'iiprop' => 'extmetadata|url',
                    'titles' => "File:{$filename}",
                    'format' => 'json',
                ]);

                if (!$response->successful()) {
                    return null;
                }

                $pages = $response->json('query.pages');
                if (!$pages) {
                    return null;
                }

                $page = reset($pages);
                if (isset($page['missing'])) {
                    return null;
                }

                $info = $page['imageinfo'][0] ?? null;
                if (!$info) {
                    return null;
                }

                $meta = $info['extmetadata'] ?? [];

                $author = $this->cleanHtml($meta['Artist']['value'] ?? null);
                $license = $meta['LicenseShortName']['value'] ?? null;
                $fileUrl = $info['descriptionurl'] ?? $imageUrl;

                if (!$author && !$license) {
                    return null;
                }

                return [
                    'author' => $author ?: null,
                    'license' => $license ?: null,
                    'source_url' => $fileUrl,
                ];
            } catch (\Exception $e) {
                Log::warning("BannerImage: Commons API error para '{$filename}': " . $e->getMessage());
                return null;
            }
        });
    }

    private function extractFilename(string $url): ?string
    {
        $path = parse_url($url, PHP_URL_PATH);
        if (!$path) {
            return null;
        }

        $basename = basename($path);
        $basename = rawurldecode($basename);
        $basename = str_replace('_', ' ', $basename);

        return $basename;
    }

    private function cleanHtml(?string $html): ?string
    {
        if (!$html) {
            return null;
        }

        $text = strip_tags($html);
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = preg_replace('/\s+/', ' ', $text);
        $text = trim($text);

        return $text ?: null;
    }

    public function getRegionalFallback(Municipio $municipio): ?array
    {
        try {
            $macro = $municipio->microrregion?->macrorregion?->nombre;
            if ($macro && isset(self::MACRORREGION_FALLBACKS[$macro])) {
                $data = self::MACRORREGION_FALLBACKS[$macro];

                return [
                    'source' => 'representative',
                    'url' => $data['url'],
                    'attribution' => $data['attribution'],
                    'note' => $data['note'],
                ];
            }
        } catch (\Exception $e) {
            Log::warning("BannerImage: Error al obtener macrorregión para {$municipio->nombre}: " . $e->getMessage());
        }

        return null;
    }

    public function getPicsumFallback(Municipio $municipio): array
    {
        return [
            'source' => 'picsum',
            'url' => 'https://picsum.photos/seed/' . $municipio->id . '/1920/650',
            'attribution' => null,
        ];
    }
}

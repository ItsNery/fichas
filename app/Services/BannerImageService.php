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

    public function resolve(Municipio $municipio): array
    {
        $wiki = $this->getWikipediaImage($municipio->nombre);
        if ($wiki) {
            return $wiki;
        }

        return [
            'source' => 'fallback',
            'url' => null,
            'attribution' => null,
        ];
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

}

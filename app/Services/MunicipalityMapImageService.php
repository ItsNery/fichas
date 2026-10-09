<?php

namespace App\Services;

use RuntimeException;

class MunicipalityMapImageService
{
    public function render(string $cvegeo, int $width = 438, int $height = 302): string
    {
        $contents = file_get_contents(public_path('geojson/municipios_puebla_slim.geojson'));

        if ($contents === false) {
            throw new RuntimeException('No fue posible leer el mapa municipal de Puebla.');
        }

        $geojson = json_decode($contents, true, flags: JSON_THROW_ON_ERROR);
        $features = $geojson['features'] ?? [];
        $bounds = $this->bounds($features);
        $image = imagecreatetruecolor($width, $height);

        if ($image === false) {
            throw new RuntimeException('No fue posible crear la imagen del mapa municipal.');
        }

        imageantialias($image, true);
        $white = imagecolorallocate($image, 255, 255, 255);
        $neutral = imagecolorallocate($image, 234, 234, 234);
        $border = imagecolorallocate($image, 175, 175, 175);
        $primary = imagecolorallocate($image, 135, 35, 65);
        $accent = imagecolorallocate($image, 184, 139, 91);
        imagefill($image, 0, 0, $white);

        $selected = null;

        foreach ($features as $feature) {
            $isSelected = (string) ($feature['properties']['cvegeo'] ?? '') === $cvegeo;

            if ($isSelected) {
                $selected = $feature;
                continue;
            }

            $this->drawFeature($image, $feature, $bounds, $width, $height, $neutral, $border, $white);
        }

        if ($selected !== null) {
            imagesetthickness($image, 2);
            $this->drawFeature($image, $selected, $bounds, $width, $height, $primary, $accent, $white);
            imagesetthickness($image, 1);
        }

        ob_start();
        imagepng($image, null, 7);
        $png = ob_get_clean();
        imagedestroy($image);

        if ($png === false) {
            throw new RuntimeException('No fue posible codificar la imagen del mapa municipal.');
        }

        return $png;
    }

    private function bounds(array $features): array
    {
        $bounds = [INF, INF, -INF, -INF];

        foreach ($features as $feature) {
            $coordinates = $feature['geometry']['coordinates'] ?? [];
            array_walk_recursive($coordinates, function ($value, $key) use (&$bounds): void {
                if (! is_numeric($value)) {
                    return;
                }

                if ($key === 0) {
                    $bounds[0] = min($bounds[0], (float) $value);
                    $bounds[2] = max($bounds[2], (float) $value);
                } elseif ($key === 1) {
                    $bounds[1] = min($bounds[1], (float) $value);
                    $bounds[3] = max($bounds[3], (float) $value);
                }
            });
        }

        if (! is_finite($bounds[0]) || $bounds[0] === $bounds[2] || $bounds[1] === $bounds[3]) {
            throw new RuntimeException('El mapa municipal no contiene coordenadas válidas.');
        }

        return $bounds;
    }

    private function drawFeature($image, array $feature, array $bounds, int $width, int $height, int $fill, int $border, int $background): void
    {
        $geometry = $feature['geometry'] ?? [];
        $polygons = match ($geometry['type'] ?? null) {
            'Polygon' => [$geometry['coordinates']],
            'MultiPolygon' => $geometry['coordinates'],
            default => [],
        };

        foreach ($polygons as $polygon) {
            foreach ($polygon as $index => $ring) {
                $points = $this->project($ring, $bounds, $width, $height);

                if (count($points) < 6) {
                    continue;
                }

                imagefilledpolygon($image, $points, $index === 0 ? $fill : $background);
                imagepolygon($image, $points, $border);
            }
        }
    }

    private function project(array $ring, array $bounds, int $width, int $height): array
    {
        [$minX, $minY, $maxX, $maxY] = $bounds;
        $padding = 8;
        $scale = min(
            ($width - ($padding * 2)) / ($maxX - $minX),
            ($height - ($padding * 2)) / ($maxY - $minY),
        );
        $mapWidth = ($maxX - $minX) * $scale;
        $mapHeight = ($maxY - $minY) * $scale;
        $offsetX = ($width - $mapWidth) / 2;
        $offsetY = ($height - $mapHeight) / 2;
        $points = [];

        foreach ($ring as $coordinate) {
            if (! isset($coordinate[0], $coordinate[1])) {
                continue;
            }

            $points[] = (int) round($offsetX + (($coordinate[0] - $minX) * $scale));
            $points[] = (int) round($height - $offsetY - (($coordinate[1] - $minY) * $scale));
        }

        return $points;
    }
}

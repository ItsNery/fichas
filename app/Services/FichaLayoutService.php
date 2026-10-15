<?php

namespace App\Services;

/**
 * Asigna una composición responsive a las tarjetas del perfil municipal.
 * La decisión se basa en la complejidad del dato y no en clases Bootstrap
 * elegidas manualmente desde el administrador.
 */
class FichaLayoutService
{
    public function compose(array $items): array
    {
        return array_map(function (array $item): array {
            $item['layout'] = $this->forItem($item);

            return $item;
        }, $items);
    }

    private function forItem(array $item): array
    {
        $config = $item['config'];
        $data = $item['datos'] ?? [];
        $mode = data_get($config->ajustes_visuales, 'layout_mode', 'auto');
        $type = $config->tipo_visualizacion;
        $series = $data['echarts']['series'] ?? [];
        $categories = $data['echarts']['xAxis']['data'] ?? $data['echarts']['yAxis']['data'] ?? [];
        $labelLength = collect($categories)->map(fn ($label) => mb_strlen((string) $label))->max() ?? 0;
        $isDense = count($categories) >= 6 || count($series) > 1 || $labelLength > 24;

        if ($mode === 'destacado') {
            return $this->layout(12, 'featured');
        }

        if ($mode === 'amplio') {
            return $this->layout(8, 'wide');
        }

        if ($mode === 'compacto') {
            return $this->layout(4, 'compact');
        }

        if ($mode === 'estandar') {
            return $this->layout(6, 'standard');
        }

        if ($type === 'piramide') {
            return $this->layout(12, 'featured');
        }

        if ($type === 'mapa') {
            return $this->layout(8, 'wide');
        }

        if ($type === 'kpi') {
            return $this->layout(4, 'compact');
        }

        if ($isDense || in_array($type, ['barras', 'bar'], true)) {
            return $this->layout(8, 'wide');
        }

        return $this->layout(6, 'standard');
    }

    private function layout(int $columns, string $variant): array
    {
        return compact('columns', 'variant');
    }
}

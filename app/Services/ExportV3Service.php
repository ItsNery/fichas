<?php

namespace App\Services;

use Spatie\Browsershot\Browsershot;

class ExportV3Service
{
    public function captureHtml(string $html, string $fileName, bool $waitForPdfReady = false, array $extraWaitFunctions = [])
    {
        $shot = Browsershot::html($html)
            ->format('A4')
            ->margins(12, 10, 12, 10)
            ->timeout(120)
            ->showBackground()
            ->protocolTimeout(120)
            ->setOption('viewport', [
                'width'             => 1240,
                'height'            => 1754,
                'deviceScaleFactor' => 2,
            ])
            ->setOption('args', ['--no-sandbox', '--disable-setuid-sandbox', '--font-render-hinting=none']);

        if ($waitForPdfReady) {
            $shot->waitForFunction('window.__pdfReady', null, 110000);
        }

        foreach ($extraWaitFunctions as $fn) {
            $shot->waitForFunction($fn);
        }

        $pdf = $shot->pdf();

        return response()->streamDownload(function () use ($pdf) {
            echo $pdf;
        }, $fileName, [
            'Content-Type' => 'application/pdf',
        ]);
    }

    public function exportResumenV3PDF(string $html, string $fileName = 'resumen-municipal.pdf')
    {
        return $this->captureHtml($html, $fileName, true);
    }

    public function exportPerfilPDF(string $html, string $fileName = 'perfil-municipal.pdf')
    {
        return $this->captureHtml($html, $fileName, true);
    }

    public function exportPanoramaPDF(string $html, string $fileName = 'panorama-municipal.pdf')
    {
        $pdf = Browsershot::html($html)
            ->paperSize(432, 279, 'mm')
            ->landscape()
            ->margins(0, 0, 0, 0)
            ->timeout(600)
            ->showBackground()
            ->protocolTimeout(600)
            ->setOption('viewport', [
                'width' => 1920,
                'height' => 1240,
                'deviceScaleFactor' => 1,
            ])
            ->setOption('args', ['--no-sandbox', '--disable-setuid-sandbox', '--font-render-hinting=none'])
            ->waitForFunction('window.__pdfReady', null, 110000)
            ->pdf();

        return response()->streamDownload(function () use ($pdf) {
            echo $pdf;
        }, $fileName, ['Content-Type' => 'application/pdf']);
    }
}

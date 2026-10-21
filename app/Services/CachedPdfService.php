<?php

namespace App\Services;

use Closure;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

class CachedPdfService
{
    private const DIRECTORY = 'fichas-pdf';
    private const VERSION = 'v1';
    private const LOCK_SECONDS = 660;
    // Una solicitud que ya está generando el mismo PDF no debe retener un
    // worker web: el cliente recibirá 503 y podrá reintentar después.
    private const LOCK_WAIT_SECONDS = 0;
    private const RENDERER_LOCK = 'fichas-pdf:renderer';

    /**
     * Reutiliza el PDF si el HTML que lo origina no ha cambiado.
     *
     * @param Closure(): string $generator
     */
    public function remember(string $documentKey, string $html, Closure $generator): string
    {
        $fingerprint = hash('sha256', self::VERSION."\0".$html);
        $paths = $this->paths($documentKey);

        if ($pdf = $this->cachedPdf($paths, $fingerprint)) {
            return $pdf;
        }

        $lock = Cache::lock('fichas-pdf:'.$documentKey, self::LOCK_SECONDS);

        return $lock->block(self::LOCK_WAIT_SECONDS, function () use ($paths, $fingerprint, $generator) {
            if ($pdf = $this->cachedPdf($paths, $fingerprint)) {
                return $pdf;
            }

            // Chromium consume bastante memoria. PDFs de municipios distintos
            // comparten este candado para evitar varios navegadores simultáneos.
            $rendererLock = Cache::lock(self::RENDERER_LOCK, self::LOCK_SECONDS);
            $pdf = $rendererLock->block(0, $generator);
            $storage = Storage::disk('local');
            $storage->put($paths['pdf'], $pdf);
            $storage->put($paths['fingerprint'], $fingerprint);

            return $pdf;
        });
    }

    private function cachedPdf(array $paths, string $fingerprint): ?string
    {
        $storage = Storage::disk('local');

        if (! $storage->exists($paths['pdf']) || ! $storage->exists($paths['fingerprint'])) {
            return null;
        }

        return hash_equals(trim($storage->get($paths['fingerprint'])), $fingerprint)
            ? $storage->get($paths['pdf'])
            : null;
    }

    private function paths(string $documentKey): array
    {
        $documentKey = trim($documentKey, '/');

        return [
            'pdf' => self::DIRECTORY.'/'.$documentKey.'.pdf',
            'fingerprint' => self::DIRECTORY.'/'.$documentKey.'.sha256',
        ];
    }
}

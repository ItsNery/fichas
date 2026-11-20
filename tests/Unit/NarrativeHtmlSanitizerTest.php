<?php

namespace Tests\Unit;

use App\Support\NarrativeHtmlSanitizer;
use PHPUnit\Framework\TestCase;

class NarrativeHtmlSanitizerTest extends TestCase
{
    public function test_it_preserves_utf8_text_across_repeated_sanitization(): void
    {
        $html = 'En el último corte, <strong>Puebla</strong> registra 41.42. '
            . 'La evolución histórica muestra una variación del 1.9%. '
            . '<em>Niñez, población indígena y educación</em>.';

        $sanitized = NarrativeHtmlSanitizer::sanitize($html);

        $this->assertSame($html, $sanitized);
        $this->assertSame($html, NarrativeHtmlSanitizer::sanitize($sanitized));
    }

    public function test_it_keeps_only_safe_editorial_markup(): void
    {
        $sanitized = NarrativeHtmlSanitizer::sanitize(
            '<strong data-origin="editor">Dato</strong>'
            . '<a href="https://example.test">enlace</a>'
            . '<script>alert(1)</script>'
            . '<br onclick="alert(1)">',
        );

        $this->assertStringContainsString('<strong>Dato</strong>', $sanitized);
        $this->assertStringContainsString('enlace', $sanitized);
        $this->assertStringNotContainsString('<a ', $sanitized);
        $this->assertStringNotContainsString('<script', $sanitized);
        $this->assertStringNotContainsString('data-origin', $sanitized);
        $this->assertStringNotContainsString('onclick', $sanitized);
        $this->assertStringContainsString('<br>', $sanitized);
    }
}

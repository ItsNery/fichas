<?php

namespace Tests\Unit;

use App\Support\NarrativeHtmlSanitizer;
use PHPUnit\Framework\TestCase;

class NarrativeHtmlSanitizerTest extends TestCase
{
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

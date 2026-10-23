<?php

namespace App\Support;

use DOMDocument;
use DOMElement;
use DOMNode;

class NarrativeHtmlSanitizer
{
    /**
     * Conserva solo el formato editorial mínimo de una narrativa.
     */
    public static function sanitize(?string $html): string
    {
        if ($html === null || $html === '') {
            return '';
        }

        $document = new DOMDocument('1.0', 'UTF-8');
        $previousErrors = libxml_use_internal_errors(true);

        try {
            $document->loadHTML(
                '<!DOCTYPE html><html><body><div id="narrativa">'.$html.'</div></body></html>',
                LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD,
            );

            $container = $document->getElementById('narrativa');

            if (!$container instanceof DOMElement) {
                return '';
            }

            self::sanitizeChildren($container);

            $sanitized = '';
            foreach ($container->childNodes as $child) {
                $sanitized .= $document->saveHTML($child);
            }

            return $sanitized;
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previousErrors);
        }
    }

    private static function sanitizeChildren(DOMNode $parent): void
    {
        $children = [];
        foreach ($parent->childNodes as $child) {
            $children[] = $child;
        }

        foreach ($children as $child) {
            if (!$child instanceof DOMElement) {
                continue;
            }

            if (!in_array(strtolower($child->tagName), ['strong', 'em', 'b', 'i', 'br'], true)) {
                $parent->replaceChild(
                    $parent->ownerDocument->createTextNode($child->textContent ?? ''),
                    $child,
                );

                continue;
            }

            while ($child->attributes->length > 0) {
                $child->removeAttributeNode($child->attributes->item(0));
            }

            self::sanitizeChildren($child);
        }
    }
}

<?php

declare(strict_types=1);

namespace BonsaiPress;

class PlainTextExtractor
{
    public static function extract(string $html): string
    {
        // strip_tags() lässt Inhalte von <script>/<style> stehen — für den
        // Content-Bereich unkritisch, dort steht laut Konvention kein JS/CSS.
        $text = strip_tags($html);
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');

        $lines = array_map('trim', explode("\n", $text));
        $lines = array_filter($lines, fn(string $l) => $l !== '');

        return implode("\n", $lines);
    }
}

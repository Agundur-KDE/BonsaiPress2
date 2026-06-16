<?php

declare(strict_types=1);

namespace BonsaiPress;

class HtmlMinifier
{
    public function minify(string $html): string
    {
        $blocks = [];

        // Extract script/style/pre/textarea — must not be touched
        $html = preg_replace_callback(
            '~<(script|style|pre|textarea)(\s[^>]*)?>.*?</\1>~si',
            function (array $m) use (&$blocks): string {
                $key = "\x00BP_" . count($blocks) . "\x00";
                $blocks[$key] = $m[0];
                return $key;
            },
            $html
        );

        // Remove HTML comments — safe non-backtracking pattern
        $html = preg_replace('/<!--(?:[^-]|-(?!->))*-->/', '', $html);

        // Collapse whitespace between tags
        $html = preg_replace('/>\s+</', '><', $html);

        // Collapse runs of spaces/tabs to single space
        $html = preg_replace('/[ \t]{2,}/', ' ', $html);

        // Remove leading whitespace per line and collapse blank lines
        $html = preg_replace('/^\s+/m', '', $html);
        $html = preg_replace('/\n{2,}/', "\n", $html);

        // Restore protected blocks
        return strtr($html, $blocks);
    }
}

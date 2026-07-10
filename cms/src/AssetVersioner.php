<?php

declare(strict_types=1);

namespace BonsaiPress;

class AssetVersioner
{
    public static function apply(string $html, string $resourcesUrl, string $resourcesPath): string
    {
        $resourcesUrl = rtrim($resourcesUrl, '/');

        return preg_replace_callback(
            '~(href|src)="(' . preg_quote($resourcesUrl, '~') . '/[^"?]+\.(?:css|js))"~',
            function (array $m) use ($resourcesUrl, $resourcesPath) {
                $relative = substr($m[2], strlen($resourcesUrl));
                $mtime = @filemtime($resourcesPath . $relative);
                return $mtime !== false
                    ? $m[1] . '="' . $m[2] . '?v=' . date('Ymd-His', $mtime) . '"'
                    : $m[0];
            },
            $html
        );
    }
}

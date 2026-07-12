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
                $hash = GitBlobHash::ofFile($resourcesPath . $relative);
                return $hash !== false
                    ? $m[1] . '="' . $m[2] . '?v=' . substr($hash, 0, 10) . '"'
                    : $m[0];
            },
            $html
        );
    }
}

<?php

declare(strict_types=1);

namespace BonsaiPress;

/**
 * Same hash git uses for blob objects (sha1('blob ' . strlen . "\0" . content)),
 * so values here match `git hash-object <file>` — shared by DeployCommand's
 * hash-diff and AssetVersioner's cache-busting instead of each rolling its own.
 *
 * cms/templates/hashme.php duplicates this formula by hand (it runs standalone
 * on the remote server and can't require this class) — keep both in sync.
 */
class GitBlobHash
{
    public static function of(string $content): string
    {
        $content = str_replace("\r\n", "\n", $content);
        return sha1('blob ' . strlen($content) . "\0" . $content);
    }

    public static function ofFile(string $path): string|false
    {
        $content = @file_get_contents($path);
        return $content === false ? false : self::of($content);
    }
}

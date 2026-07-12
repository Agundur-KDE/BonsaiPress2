<?php

declare(strict_types=1);

namespace BonsaiPress\Tests;

use PHPUnit\Framework\TestCase;
use BonsaiPress\AssetVersioner;
use BonsaiPress\GitBlobHash;

class AssetVersionerTest extends TestCase
{
    private string $resourcesPath = __DIR__ . '/fixtures/assets';

    public function testAppendsContentHashToCssHref(): void
    {
        $version = substr(GitBlobHash::ofFile($this->resourcesPath . '/style.css'), 0, 10);
        $html    = '<link href="/_resources/style.css" rel="stylesheet">';

        $result = AssetVersioner::apply($html, '/_resources', $this->resourcesPath);

        $this->assertSame(
            '<link href="/_resources/style.css?v=' . $version . '" rel="stylesheet">',
            $result
        );
    }

    public function testAppendsContentHashToJsSrc(): void
    {
        $version = substr(GitBlobHash::ofFile($this->resourcesPath . '/app.js'), 0, 10);
        $html    = '<script src="/_resources/app.js"></script>';

        $result = AssetVersioner::apply($html, '/_resources', $this->resourcesPath);

        $this->assertSame(
            '<script src="/_resources/app.js?v=' . $version . '"></script>',
            $result
        );
    }

    public function testVersionIsStableAcrossMtimeChangesWithSameContent(): void
    {
        $file = $this->resourcesPath . '/style.css';
        $html = '<link href="/_resources/style.css" rel="stylesheet">';

        $before = AssetVersioner::apply($html, '/_resources', $this->resourcesPath);

        // Simulate a rewrite that doesn't change bytes (git checkout, a watcher
        // recompile of unchanged SCSS, ...) — only the mtime moves forward.
        touch($file, time() + 3600);
        clearstatcache(true, $file);

        $after = AssetVersioner::apply($html, '/_resources', $this->resourcesPath);

        $this->assertSame($before, $after);
    }

    public function testVersionChangesWhenContentChanges(): void
    {
        $file = $this->resourcesPath . '/style.css';
        $html = '<link href="/_resources/style.css" rel="stylesheet">';
        $original = file_get_contents($file);

        $before = AssetVersioner::apply($html, '/_resources', $this->resourcesPath);

        file_put_contents($file, $original . ' /* changed */');
        try {
            $after = AssetVersioner::apply($html, '/_resources', $this->resourcesPath);
            $this->assertNotSame($before, $after);
        } finally {
            file_put_contents($file, $original);
        }
    }

    public function testLeavesMissingFileUntouched(): void
    {
        $html = '<link href="/_resources/does-not-exist.css" rel="stylesheet">';

        $result = AssetVersioner::apply($html, '/_resources', $this->resourcesPath);

        $this->assertSame($html, $result);
    }

    public function testLeavesNonResourcesUrlUntouched(): void
    {
        $html = '<link href="https://cdn.example.com/style.css" rel="stylesheet">';

        $result = AssetVersioner::apply($html, '/_resources', $this->resourcesPath);

        $this->assertSame($html, $result);
    }

    public function testIgnoresNonCssJsExtensions(): void
    {
        $html = '<img src="/_resources/logo.png">';

        $result = AssetVersioner::apply($html, '/_resources', $this->resourcesPath);

        $this->assertSame($html, $result);
    }
}

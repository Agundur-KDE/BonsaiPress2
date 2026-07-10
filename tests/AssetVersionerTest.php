<?php

declare(strict_types=1);

namespace BonsaiPress\Tests;

use PHPUnit\Framework\TestCase;
use BonsaiPress\AssetVersioner;

class AssetVersionerTest extends TestCase
{
    private string $resourcesPath = __DIR__ . '/fixtures/assets';

    public function testAppendsFilemtimeToCssHref(): void
    {
        $version = date('Ymd-His', filemtime($this->resourcesPath . '/style.css'));
        $html    = '<link href="/_resources/style.css" rel="stylesheet">';

        $result = AssetVersioner::apply($html, '/_resources', $this->resourcesPath);

        $this->assertSame(
            '<link href="/_resources/style.css?v=' . $version . '" rel="stylesheet">',
            $result
        );
    }

    public function testAppendsFilemtimeToJsSrc(): void
    {
        $version = date('Ymd-His', filemtime($this->resourcesPath . '/app.js'));
        $html    = '<script src="/_resources/app.js"></script>';

        $result = AssetVersioner::apply($html, '/_resources', $this->resourcesPath);

        $this->assertSame(
            '<script src="/_resources/app.js?v=' . $version . '"></script>',
            $result
        );
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

<?php

declare(strict_types=1);

namespace BonsaiPress\Tests;

use BonsaiPress\IndexNowNotifier;
use PHPUnit\Framework\TestCase;

class IndexNowNotifierTest extends TestCase
{
    public function testRootIndexMapsToSlash(): void
    {
        $urls = IndexNowNotifier::buildUrls(['index.html'], 'https://www.agundur.de');
        $this->assertSame(['https://www.agundur.de/'], $urls);
    }

    public function testNestedIndexMapsToTrailingSlashDirectory(): void
    {
        $urls = IndexNowNotifier::buildUrls(['projects/index.html'], 'https://www.agundur.de');
        $this->assertSame(['https://www.agundur.de/projects/'], $urls);
    }

    public function testRegularPageKeepsHtmlExtension(): void
    {
        $urls = IndexNowNotifier::buildUrls(['geo-scanner.html'], 'https://www.agundur.de');
        $this->assertSame(['https://www.agundur.de/geo-scanner.html'], $urls);
    }

    public function testNonHtmlFilesAreIgnored(): void
    {
        $urls = IndexNowNotifier::buildUrls(['sitemap.xml', '_resources/images/foo.png'], 'https://www.agundur.de');
        $this->assertSame([], $urls);
    }

    public function testTrailingSlashOnBaseUrlIsNormalized(): void
    {
        $urls = IndexNowNotifier::buildUrls(['index.html'], 'https://www.agundur.de/');
        $this->assertSame(['https://www.agundur.de/'], $urls);
    }

    public function testEmptyFileListYieldsEmptyUrls(): void
    {
        $this->assertSame([], IndexNowNotifier::buildUrls([], 'https://www.agundur.de'));
    }

    public function testBuildPayloadIncludesHostKeyAndUrlList(): void
    {
        $payload = IndexNowNotifier::buildPayload(
            'www.agundur.de',
            'abc123',
            'https://www.agundur.de/abc123.txt',
            ['https://www.agundur.de/', 'https://www.agundur.de/geo-scanner.html']
        );

        $this->assertSame([
            'host'        => 'www.agundur.de',
            'key'         => 'abc123',
            'keyLocation' => 'https://www.agundur.de/abc123.txt',
            'urlList'     => ['https://www.agundur.de/', 'https://www.agundur.de/geo-scanner.html'],
        ], $payload);
    }

    public function testEnsureKeyGeneratesAndPersistsKeyWhenMissing(): void
    {
        $dir = sys_get_temp_dir() . '/indexnow-test-' . uniqid();
        mkdir($dir);

        try {
            $key = IndexNowNotifier::ensureKey($dir, fn(): string => 'generated-key');

            $this->assertSame('generated-key', $key);
            $this->assertSame('generated-key', file_get_contents($dir . '/indexnow-key.txt'));
        } finally {
            @unlink($dir . '/indexnow-key.txt');
            @rmdir($dir);
        }
    }

    public function testEnsureKeyReusesExistingFileWithoutRegenerating(): void
    {
        $dir = sys_get_temp_dir() . '/indexnow-test-' . uniqid();
        mkdir($dir);
        file_put_contents($dir . '/indexnow-key.txt', 'existing-key');

        try {
            $key = IndexNowNotifier::ensureKey($dir, fn(): string => 'should-not-be-used');

            $this->assertSame('existing-key', $key);
        } finally {
            @unlink($dir . '/indexnow-key.txt');
            @rmdir($dir);
        }
    }

    public function testEnsureKeyDefaultGeneratorProducesHexString(): void
    {
        $dir = sys_get_temp_dir() . '/indexnow-test-' . uniqid();
        mkdir($dir);

        try {
            $key = IndexNowNotifier::ensureKey($dir);

            $this->assertMatchesRegularExpression('/^[a-f0-9]{32}$/', $key);
        } finally {
            @unlink($dir . '/indexnow-key.txt');
            @rmdir($dir);
        }
    }
}

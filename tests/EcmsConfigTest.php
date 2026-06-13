<?php

declare(strict_types=1);

namespace BonsaiPress\Tests;

use BonsaiPress\EcmsConfig;
use PHPUnit\Framework\TestCase;

class EcmsConfigTest extends TestCase
{
    private EcmsConfig $config;

    protected function setUp(): void
    {
        // basePath is the project root (one level up from tests/)
        $this->config = new EcmsConfig(dirname(__DIR__));
    }

    public function testDefaultLangFromClientConfig(): void
    {
        // Client config doesn't override default_lang → falls back to CMS default 'de'
        $this->assertSame('de', $this->config->defaultLang());
    }

    public function testDomainFromClientConfig(): void
    {
        // Client config sets domain to 'agundur.de'
        $this->assertSame('agundur.de', $this->config->domain());
    }

    public function testAllowedLanguagesIsArray(): void
    {
        $langs = $this->config->allowedLanguages();
        $this->assertIsArray($langs);
        $this->assertNotEmpty($langs);
        // Each entry must be a plain language code, not 'de,en'
        foreach ($langs as $lang) {
            $this->assertStringNotContainsString(',', $lang);
        }
    }
}

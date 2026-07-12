<?php

declare(strict_types=1);

namespace BonsaiPress\Tests;

use BonsaiPress\EcmsConfig;
use PHPUnit\Framework\TestCase;

class EcmsConfigTest extends TestCase
{
    private EcmsConfig $config;
    private string $basePath;

    protected function setUp(): void
    {
        // EcmsConfig always loads <basePath>/cms/include/ecms_config.php (the
        // real, generic CMS defaults — safe to reuse via symlink) plus
        // <basePath>/current/config/ecms_config.php (the client override,
        // which must NOT be the real current/ symlink: that follows whatever
        // `bonsai switch` last activated, so this test's expectations would
        // silently depend on unrelated developer-machine state instead of a
        // fixed fixture).
        $this->basePath = sys_get_temp_dir() . '/ecmsconfig-' . uniqid();
        mkdir($this->basePath . '/current/config', 0777, true);
        symlink(dirname(__DIR__) . '/cms', $this->basePath . '/cms');
        file_put_contents(
            $this->basePath . '/current/config/ecms_config.php',
            "<?php\n\\ECMS_CONFIG::\$domain = 'domain.tld';\n"
        );

        $this->config = new EcmsConfig($this->basePath);
    }

    protected function tearDown(): void
    {
        $this->rrmdir($this->basePath);
    }

    private function rrmdir(string $dir): void
    {
        foreach (glob($dir . '/*') ?: [] as $file) {
            if (is_link($file)) {
                unlink($file);
            } elseif (is_dir($file)) {
                $this->rrmdir($file);
            } else {
                unlink($file);
            }
        }
        rmdir($dir);
    }

    public function testDefaultLangFromClientConfig(): void
    {
        // Client config doesn't override default_lang → falls back to CMS default 'de'
        $this->assertSame('de', $this->config->defaultLang());
    }

    public function testDomainFromClientConfig(): void
    {
        $this->assertSame('domain.tld', $this->config->domain());
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

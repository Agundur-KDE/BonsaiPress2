<?php

declare(strict_types=1);

namespace BonsaiPress\Tests;

use BonsaiPress\Config;
use BonsaiPress\Page;
use BonsaiPress\PageRenderer;
use PHPUnit\Framework\TestCase;

class PageRendererLastModTest extends TestCase
{
    private string $basePath;

    protected function setUp(): void
    {
        $this->basePath = sys_get_temp_dir() . '/pagerenderer-lastmod-' . uniqid();

        $de = $this->basePath . '/current/config/de';
        mkdir($de . '/templates', 0777, true);
        mkdir($de . '/contenfiles', 0777, true);
        mkdir($de . '/page_config', 0777, true);

        file_put_contents($de . '/templates/head.html',
            "<!--sebastiany.net::Prolog::Start--><!DOCTYPE html><!--sebastiany.net::Prolog::End-->\n"
            . "<!--sebastiany.net::Head::Start-->{META}{SCHEMAORG}{CSS}{JS}<!--sebastiany.net::Head::End-->"
        );
        file_put_contents($de . '/templates/main.html',
            "<!--sebastiany.net::Body1::Start--><!--sebastiany.net::Body1::End-->\n"
            . "<!--sebastiany.net::Content::Start-->{CONTENT}<!--sebastiany.net::Content::End-->\n"
            . "<!--sebastiany.net::Body2::Start-->{ENDSCRIPTS}<!--sebastiany.net::Body2::End-->"
        );
        file_put_contents($de . '/page_config/1.html',
            "<!--sebastiany.net::head_template::Start--><!--sebastiany.net::head_template::End-->\n"
            . "<!--sebastiany.net::main_template::Start--><!--sebastiany.net::main_template::End-->\n"
            . "<!--sebastiany.net::css::Start--><!--sebastiany.net::css::End-->\n"
            . "<!--sebastiany.net::css_dynamic::Start--><!--sebastiany.net::css_dynamic::End-->\n"
            . "<!--sebastiany.net::js_top::Start--><!--sebastiany.net::js_top::End-->\n"
            . "<!--sebastiany.net::js_bottom::Start--><!--sebastiany.net::js_bottom::End-->\n"
            . "<!--sebastiany.net::js_bottom_dynamic::Start--><!--sebastiany.net::js_bottom_dynamic::End-->"
        );

        $this->contenfilePath = $de . '/contenfiles/1.html';
        file_put_contents($this->contenfilePath,
            "<!--sebastiany.net::Content::Start--><p>hello</p><!--sebastiany.net::Content::End-->\n"
            . "<!--sebastiany.net::Meta::Start--><meta name=\"x\"><!--sebastiany.net::Meta::End-->\n"
            . "<!--sebastiany.net::Json::Start-->{\n\"dateModified\": \"{LASTMOD_ISO}\"\n}<!--sebastiany.net::Json::End-->"
        );
        touch($this->contenfilePath, mktime(12, 0, 0, 3, 5, 2026));
    }

    private string $contenfilePath;

    protected function tearDown(): void
    {
        $this->rrmdir($this->basePath);
    }

    private function rrmdir(string $dir): void
    {
        foreach (glob($dir . '/*') ?: [] as $file) {
            is_dir($file) ? $this->rrmdir($file) : unlink($file);
        }
        rmdir($dir);
    }

    public function testLastModIsoPlaceholderInContenfileJsonIsResolved(): void
    {
        $config = new class implements Config {
            public function defaultLang(): string { return 'de'; }
            public function allowedLanguages(): array { return ['de']; }
            public function domain(): string { return 'example.test'; }
            public function defaultHeadTemplate(): string { return 'head.html'; }
            public function defaultMainTemplate(): string { return 'main.html'; }
            public function defaultCss(): string { return 'main.css'; }
            public function pathToResources(): string { return '/_resources'; }
            public function baseUrl(): string { return 'https://example.test'; }
            public function minifyHtmlOutput(): bool { return false; }
            public function generateLlmsFull(): bool { return false; }
            public function sassCreateMap(): bool { return false; }
        };

        $page = new Page(
            id: 1,
            title: 'Test',
            location: 'test',
            contentType: 'standard_html',
        );

        $renderer = new PageRenderer($config, $this->basePath);
        $html = $renderer->render($page, 'static', ['1' => '/test'], [$page]);

        $this->assertStringContainsString('"dateModified": "2026-03-05"', $html);
        $this->assertStringNotContainsString('{LASTMOD_ISO}', $html);
    }
}

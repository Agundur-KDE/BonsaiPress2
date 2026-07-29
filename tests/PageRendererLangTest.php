<?php

declare(strict_types=1);

namespace BonsaiPress\Tests;

use BonsaiPress\Config;
use BonsaiPress\Page;
use BonsaiPress\PageRenderer;
use PHPUnit\Framework\TestCase;

/**
 * {LANG} used to always resolve to the site-wide Config::defaultLang(),
 * regardless of a page's actual content language — so bilingual
 * EN-default pages (e.g. geo-scanner.html, schema-checker.html) shipped
 * <html lang="de"> while displaying English content by default. This
 * verifies a per-page lang override on Page takes precedence, with the
 * site-wide default as fallback when no override is set.
 */
class PageRendererLangTest extends TestCase
{
    private string $basePath;

    protected function setUp(): void
    {
        $this->basePath = sys_get_temp_dir() . '/pagerenderer-lang-' . uniqid();

        $de = $this->basePath . '/current/config/de';
        mkdir($de . '/templates', 0777, true);
        mkdir($de . '/contenfiles', 0777, true);
        mkdir($de . '/page_config', 0777, true);

        file_put_contents($de . '/templates/head.html',
            "<!--sebastiany.net::Prolog::Start--><!DOCTYPE html><html lang=\"{LANG}\"><!--sebastiany.net::Prolog::End-->\n"
            . "<!--sebastiany.net::Head::Start-->{META}{SCHEMAORG}{CSS}{JS}<!--sebastiany.net::Head::End-->"
        );
        file_put_contents($de . '/templates/main.html',
            "<!--sebastiany.net::Body1::Start--><!--sebastiany.net::Body1::End-->\n"
            . "<!--sebastiany.net::Content::Start-->{CONTENT}<!--sebastiany.net::Content::End-->\n"
            . "<!--sebastiany.net::Body2::Start-->{ENDSCRIPTS}<!--sebastiany.net::Body2::End-->"
        );

        foreach ([1, 2] as $id) {
            file_put_contents($de . "/page_config/$id.html",
                "<!--sebastiany.net::head_template::Start--><!--sebastiany.net::head_template::End-->\n"
                . "<!--sebastiany.net::main_template::Start--><!--sebastiany.net::main_template::End-->\n"
                . "<!--sebastiany.net::css::Start--><!--sebastiany.net::css::End-->\n"
                . "<!--sebastiany.net::css_dynamic::Start--><!--sebastiany.net::css_dynamic::End-->\n"
                . "<!--sebastiany.net::js_top::Start--><!--sebastiany.net::js_top::End-->\n"
                . "<!--sebastiany.net::js_bottom::Start--><!--sebastiany.net::js_bottom::End-->\n"
                . "<!--sebastiany.net::js_bottom_dynamic::Start--><!--sebastiany.net::js_bottom_dynamic::End-->"
            );
            file_put_contents($de . "/contenfiles/$id.html",
                "<!--sebastiany.net::Content::Start--><p>page $id</p><!--sebastiany.net::Content::End-->\n"
                . "<!--sebastiany.net::Meta::Start--><!--sebastiany.net::Meta::End-->\n"
                . "<!--sebastiany.net::Json::Start-->{}<!--sebastiany.net::Json::End-->"
            );
        }
    }

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

    private function config(): Config
    {
        return new class implements Config {
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
    }

    public function testPageLangOverrideWinsOverSiteDefault(): void
    {
        $page = new Page(
            id: 2, title: 'EN Landing', location: 'en-landing', contentType: 'standard_html',
            lang: 'en',
        );

        $renderer = new PageRenderer($this->config(), $this->basePath);
        $html = $renderer->render($page, 'static', ['2' => '/en-landing'], [$page]);

        $this->assertStringContainsString('<html lang="en">', $html);
    }

    public function testPageWithoutLangOverrideFallsBackToSiteDefault(): void
    {
        $page = new Page(
            id: 1, title: 'Normal Page', location: 'normal', contentType: 'standard_html',
        );

        $renderer = new PageRenderer($this->config(), $this->basePath);
        $html = $renderer->render($page, 'static', ['1' => '/normal'], [$page]);

        $this->assertStringContainsString('<html lang="de">', $html);
    }
}

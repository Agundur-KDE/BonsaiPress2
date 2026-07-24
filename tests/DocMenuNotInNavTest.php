<?php

declare(strict_types=1);

namespace BonsaiPress\Tests;

use BonsaiPress\Addon;
use BonsaiPress\Config;
use BonsaiPress\Page;
use BonsaiPress\PageRenderer;
use PHPUnit\Framework\TestCase;

/**
 * not_in_nav is parsed by XmlPageRepository onto Page::$notInNav (confirmed by
 * XmlPageRepositoryTest), but that flag was never consulted by the doc-menu
 * addon that actually renders the nav — every page in the tree showed up in
 * the menu regardless. This test exercises the real addon file end-to-end
 * (not just the repository) so it catches exactly that gap.
 */
class DocMenuNotInNavTest extends TestCase
{
    private string $basePath;

    protected function setUp(): void
    {
        $this->basePath = sys_get_temp_dir() . '/docmenu-notinnav-' . uniqid();
        $de = $this->basePath . '/current/config/de';
        mkdir($de . '/templates', 0777, true);
        mkdir($de . '/contenfiles', 0777, true);
        mkdir($de . '/page_config', 0777, true);
        mkdir($this->basePath . '/cms/addons', 0777, true);

        // Real addon under test — copied so the test exercises the actual
        // production file, not a hand-written stand-in.
        copy(
            dirname(__DIR__) . '/cms/addons/doc-menu.php',
            $this->basePath . '/cms/addons/doc-menu.php'
        );

        // Real nav template — copied for the same reason (section-boundary
        // fidelity matters here, not worth hand-rolling a second copy).
        copy(
            dirname(__DIR__) . '/current/config/de/templates/doc-menu.html',
            $de . '/templates/doc-menu.html'
        );

        file_put_contents($de . '/templates/head.html',
            "<!--sebastiany.net::Prolog::Start--><!DOCTYPE html><!--sebastiany.net::Prolog::End-->\n"
            . "<!--sebastiany.net::Head::Start-->{META}{SCHEMAORG}{CSS}{JS}<!--sebastiany.net::Head::End-->"
        );
        file_put_contents($de . '/templates/main.html',
            "<!--sebastiany.net::Body1::Start-->{MAINMENU}<!--sebastiany.net::Body1::End-->\n"
            . "<!--sebastiany.net::Content::Start-->{CONTENT}<!--sebastiany.net::Content::End-->\n"
            . "<!--sebastiany.net::Body2::Start-->{ENDSCRIPTS}<!--sebastiany.net::Body2::End-->"
        );

        foreach ([1, 2, 3] as $id) {
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

    public function testNotInNavPageIsOmittedFromRenderedMenu(): void
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

        $menuAddon = new Addon(name: 'doc-menu', placeholder: 'MAINMENU');

        $visible = new Page(
            id: 2, title: 'Visible', location: 'visible', contentType: 'standard_html',
        );
        $hidden = new Page(
            id: 3, title: 'Hidden', location: 'hidden-page', contentType: 'standard_html',
            notInNav: true,
        );
        $root = new Page(
            id: 1, title: 'Root', location: 'index', contentType: 'standard_html',
            isRoot: true, addons: [$menuAddon],
        );

        $pageTree = [$root, $visible, $hidden];
        $pathMap  = ['1' => '/', '2' => '/visible', '3' => '/hidden-page'];

        $renderer = new PageRenderer($config, $this->basePath);
        $html = $renderer->render($root, 'static', $pathMap, $pageTree);

        $this->assertStringContainsString('/visible.html', $html,
            'sanity check: a normal page must still appear in the menu');
        $this->assertStringNotContainsString('/hidden-page.html', $html,
            'a page with not_in_nav must not appear in the rendered menu');
    }
}

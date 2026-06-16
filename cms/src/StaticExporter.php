<?php

declare(strict_types=1);

namespace BonsaiPress;

class StaticExporter
{
    private string $staticDir;

    public function __construct(
        private PageRenderer $renderer,
        private PageRepository $repo,
        string $basePath,
        private string $baseUrl = '',
    ) {
        $this->staticDir = $basePath . '/current/static';
    }

    public function export(): \Generator
    {
        $this->cleanStatic();

        $pathMap  = $this->repo->getPathMap();
        $sitemap  = [];

        $pages = new \RecursiveIteratorIterator(
            new PageTreeIterator($this->repo->getTree()),
            \RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($pages as $page) {
            $html = $this->renderer->render($page, 'static', $pathMap);
            $html = $this->rewriteLinks($html, $pathMap);

            $path     = $pathMap[$page->id] ?? '/';
            $filePath = $this->resolveFilePath($path);
            $this->writeFile($filePath, $html);

            if (!$page->notInSitemap) {
                $sitemap[] = $path;
            }

            yield $page->title => $filePath;
        }

        if (!empty($this->baseUrl)) {
            $this->writeSitemap($sitemap);
        }
    }

    private function writeSitemap(array $paths): void
    {
        $xml = new \SimpleXMLElement(
            '<?xml version="1.0" encoding="UTF-8"?><urlset/>'
        );
        $xml->addAttribute('xmlns', 'http://www.sitemaps.org/schemas/sitemap/0.9');
        $xml->addAttribute('xmlns:xsi', 'http://www.w3.org/2001/XMLSchema-instance', 'http://www.w3.org/2001/XMLSchema-instance');
        $xml->addAttribute(
            'xsi:schemaLocation',
            'http://www.sitemaps.org/schemas/sitemap/0.9 http://www.sitemaps.org/schemas/sitemap/0.9/sitemap.xsd',
            'http://www.w3.org/2001/XMLSchema-instance'
        );

        foreach ($paths as $path) {
            $loc = $this->baseUrl . (str_ends_with($path, '/') ? $path : $path . '.html');
            $url = $xml->addChild('url');
            $url->addChild('loc', htmlspecialchars($loc, ENT_XML1));
            $url->addChild('changefreq', 'weekly');
        }

        $dom = dom_import_simplexml($xml)->ownerDocument;
        $dom->formatOutput = true;
        file_put_contents($this->staticDir . '/sitemap.xml', $dom->saveXML(), LOCK_EX);
    }

    private function resolveFilePath(string $path): string
    {
        if ($path === '/' || str_ends_with($path, '/')) {
            return $this->staticDir . rtrim($path, '/') . '/index.html';
        }
        return $this->staticDir . $path . '.html';
    }

    private function writeFile(string $path, string $html): void
    {
        $dir = dirname($path);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        file_put_contents($path, $html, LOCK_EX);
    }

    private function rewriteLinks(string $html, array $pathMap): string
    {
        // matches both /?site=X and /ecms/index.php?site=X (legacy)
        preg_match_all('~(?:/ecms/index\.php)?\?site=([0-9]+)~', $html, $matches);

        if (empty($matches[1])) {
            return $html;
        }

        $replacements = [];
        foreach ($matches[0] as $key => $link) {
            $id = (int) $matches[1][$key];
            if (!isset($pathMap[$id])) {
                continue;
            }
            $path = $pathMap[$id];
            $replacements[$link] = str_ends_with($path, '/') ? $path : $path . '.html';
        }

        // longest first to avoid partial replacements (site=12 before site=1)
        uksort($replacements, fn($a, $b) => strlen($b) - strlen($a));

        return str_replace(array_keys($replacements), array_values($replacements), $html);
    }

    private function cleanStatic(): void
    {
        if (!is_dir($this->staticDir)) {
            return;
        }
        $this->cleanDir($this->staticDir, true);
    }

    private function cleanDir(string $dir, bool $isRoot = false): void
    {
        foreach (scandir($dir) as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }

            $path = $dir . '/' . $item;

            if (is_link($path)) {
                continue;
            }

            if ($isRoot && $item === '_resources') {
                continue;
            }

            if (is_dir($path)) {
                $this->cleanDir($path);
                @rmdir($path);
            } elseif (str_ends_with($item, '.html') || $item === 'sitemap.xml') {
                unlink($path);
            }
        }
    }
}

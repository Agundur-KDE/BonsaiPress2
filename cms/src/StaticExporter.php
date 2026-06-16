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
        $lines = [
            '<?xml version="1.0" encoding="UTF-8"?>',
            '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"'
            . ' xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"'
            . ' xsi:schemaLocation="http://www.sitemaps.org/schemas/sitemap/0.9'
            . ' http://www.sitemaps.org/schemas/sitemap/0.9/sitemap.xsd">',
        ];

        foreach ($paths as $path) {
            $loc = htmlspecialchars(
                $this->baseUrl . (str_ends_with($path, '/') ? $path : $path . '.html'),
                ENT_XML1
            );
            $lines[] = '  <url><loc>' . $loc . '</loc><changefreq>weekly</changefreq></url>';
        }

        $lines[] = '</urlset>';

        file_put_contents($this->staticDir . '/sitemap.xml', implode("\n", $lines) . "\n", LOCK_EX);
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
        // matches /?site=X, ?site=X, /ecms/index.php?site=X (legacy)
        preg_match_all('~(?:/ecms/index\.php)?/?\?site=([0-9]+)~', $html, $matches);

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

        foreach (scandir($this->staticDir) as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }

            $path = $this->staticDir . '/' . $item;

            if (is_link($path)) {
                continue;
            }

            if ($item === '_resources') {
                continue;
            }

            if (is_dir($path)) {
                // BonsaiPress owns all subdirs — delete entirely so removed pages leave no trace
                $this->deleteDir($path);
            } elseif ($item === 'index.html' || $item === 'sitemap.xml') {
                // Only these two root-level files are BonsaiPress-generated
                unlink($path);
            }
            // Everything else at root level (manual HTML, images, favicons etc.) is preserved
        }
    }

    private function deleteDir(string $dir): void
    {
        foreach (scandir($dir) as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }
            $path = $dir . '/' . $item;
            if (is_link($path)) {
                continue;
            }
            is_dir($path) ? $this->deleteDir($path) : unlink($path);
        }
        rmdir($dir);
    }
}

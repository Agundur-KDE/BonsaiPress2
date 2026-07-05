<?php

declare(strict_types=1);

namespace BonsaiPress;

class PageRenderer
{
    private string $contentPath;
    private string $templatePath;

    public function __construct(
        private Config $config,
        private string $basePath,
    ) {
        $lang = $config->defaultLang();
        $this->contentPath  = $basePath . '/current/config/' . $lang;
        $this->templatePath = $this->contentPath . '/templates';
    }

    public function render(Page $page, string $mode = 'dynamic', array $pathMap = [], array $pageTree = []): string
    {
        if (empty($pathMap) || empty($pageTree)) {
            $repo = new XmlPageRepository($this->contentPath . '/site_structure.xml');
            if (empty($pathMap))  $pathMap  = $repo->getPathMap();
            if (empty($pageTree)) $pageTree = $repo->getTree();
        }

        $pageConfig = $this->loadPageConfig($page->id);
        $tpl        = $this->loadTemplate($pageConfig);

        $tpl->assign('LANG',       $this->config->defaultLang());
        $tpl->assign('DEFAULTCSS', $this->config->defaultCss());
        $tpl->assign('_RESOURCES', $this->config->pathToResources() . '/');

        $pageConfig->assign('_RESOURCES', $this->config->pathToResources() . '/');
        $pageConfig->assign('DEFAULTCSS', $this->config->defaultCss());

        $tpl->add('CSS', $pageConfig->fetch('css'));
        if ($mode === 'dynamic') {
            $tpl->add('CSS', $pageConfig->fetch('css_dynamic'));
        }
        $tpl->add('JS',         $pageConfig->fetch('js_top'));
        $tpl->add('ENDSCRIPTS', $pageConfig->fetch('js_bottom'));
        if ($mode === 'dynamic') {
            $tpl->add('ENDSCRIPTS', $pageConfig->fetch('js_bottom_dynamic'));
            $tpl->add('ENDSCRIPTS', '<script src="/js/reload_client.js"></script>');
        }

        $content = $this->loadContent($page->id);
        $content->assign('LASTMOD_ISO', LastModResolver::resolveIso(
            $this->contentPath . '/contenfiles/' . $page->id . '.html'
        ));
        $tpl->assign('CONTENT',  $content->fetch('Content'));
        $tpl->assign('META',     $content->fetch('Meta'));
        $tpl->assign('SCHEMAORG', $content->fetch('Json'));

        $this->processAddons($page, $tpl, $mode, $pathMap, $pageTree);

        $html = $tpl->fetch('Prolog')
              . $tpl->fetch('Head')
              . $tpl->fetch('Body1')
              . $tpl->fetch('Content')
              . $tpl->fetch('Body2');

        $html = AssetVersioner::apply(
            $html,
            $this->config->pathToResources(),
            $this->basePath . '/current/static' . $this->config->pathToResources()
        );

        if ($mode === 'static' && $this->config->minifyHtmlOutput()) {
            $html = (new HtmlMinifier())->minify($html);
        }

        return $html;
    }

    private function loadPageConfig(int $pageId): Section
    {
        $path = $this->contentPath . '/page_config/' . $pageId . '.html';
        if (!file_exists($path)) {
            copy($this->contentPath . '/page_config/page.tpl', $path);
        }
        $section = new Section();
        $section->read($path);
        return $section;
    }

    private function loadTemplate(Section $pageConfig): Section
    {
        $head = trim($pageConfig->fetch('head_template')) ?: $this->config->defaultHeadTemplate();
        $main = trim($pageConfig->fetch('main_template')) ?: $this->config->defaultMainTemplate();

        $tpl = new Section();
        $tpl->read($this->templatePath . '/' . $head);
        $tpl->read($this->templatePath . '/' . $main);
        return $tpl;
    }

    private function processAddons(Page $page, Section $tpl, string $mode, array $pathMap, array $pageTree): void
    {
        $template_path = $this->templatePath;
        $page_id       = $page->id;
        $ancestors     = $this->findAncestors($pageTree, $page->id) ?? [];

        foreach ($page->addons as $addon) {
            $addonFile = $this->resolveAddon($addon->name);
            if ($addonFile === null) {
                continue;
            }
            $addonconfig  = (object) $addon->config;
            $addonparams  = $addon->config;
            $modus        = $mode;
            $addoncontent = '';
            require $addonFile;
            $tpl->assign($addon->placeholder, $addoncontent);
        }
    }

    private function findAncestors(array $pages, int $targetId): ?array
    {
        foreach ($pages as $page) {
            if ($page->id === $targetId) {
                return [];
            }
            $sub = $this->findAncestors($page->children, $targetId);
            if ($sub !== null) {
                return array_merge([$page->id], $sub);
            }
        }
        return null;
    }

    private function resolveAddon(string $name): ?string
    {
        $client = $this->basePath . '/current/config/addons/' . $name . '.php';
        if (file_exists($client)) {
            return $client;
        }
        $global = $this->basePath . '/cms/addons/' . $name . '.php';
        if (file_exists($global)) {
            return $global;
        }
        return null;
    }

    private function loadContent(int $pageId): Section
    {
        $path = $this->contentPath . '/contenfiles/' . $pageId . '.html';
        if (!file_exists($path)) {
            copy($this->contentPath . '/contenfiles/content.tpl', $path);
        }
        $content = new Section();
        $content->read($path);
        return $content;
    }
}

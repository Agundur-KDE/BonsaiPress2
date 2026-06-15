<?php

declare(strict_types=1);

namespace BonsaiPress;

class PageRenderer
{
    private string $contentPath;
    private string $templatePath;

    public function __construct(
        private Config $config,
        string $basePath,
    ) {
        $lang = $config->defaultLang();
        $this->contentPath  = $basePath . '/current/config/' . $lang;
        $this->templatePath = $this->contentPath . '/templates';
    }

    public function render(Page $page, string $mode = 'dynamic'): string
    {
        $pageConfig = $this->loadPageConfig($page->id);
        $tpl        = $this->loadTemplate($pageConfig);

        $tpl->assign('LANG',       $this->config->defaultLang());
        $tpl->assign('DEFAULTCSS', $this->config->defaultCss());
        $tpl->assign('_RESOURCES', $this->config->pathToResources() . '/');

        $tpl->add('CSS', $pageConfig->fetch('css'));
        if ($mode === 'dynamic') {
            $tpl->add('CSS', $pageConfig->fetch('css_dynamic'));
        }
        $tpl->add('JS',         $pageConfig->fetch('js_top'));
        $tpl->add('ENDSCRIPTS', $pageConfig->fetch('js_bottom'));
        if ($mode === 'dynamic') {
            $tpl->add('ENDSCRIPTS', $pageConfig->fetch('js_bottom_dynamic'));
        }

        $content = $this->loadContent($page->id);
        $tpl->assign('CONTENT',  $content->fetch('Content'));
        $tpl->assign('META',     $content->fetch('Meta'));
        $tpl->assign('SCHEMAORG', $content->fetch('Json'));

        return $tpl->fetch('Prolog')
             . $tpl->fetch('Head')
             . $tpl->fetch('Body1')
             . $tpl->fetch('Content')
             . $tpl->fetch('Body2');
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

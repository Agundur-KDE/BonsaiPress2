<?php

declare(strict_types=1);

use BonsaiPress\Section;
use BonsaiPress\XmlPageRepository;

// Variables provided by PageRenderer::processAddons():
// $template_path, $page_id, $pathMap, $modus, $addonconfig, $addoncontent

$repo = new XmlPageRepository(dirname($template_path) . '/site_structure.xml');

if (!function_exists('breadcrumbAncestors')) :
function breadcrumbAncestors(array $pages, int $targetId, array $path = []): array
{
    foreach ($pages as $page) {
        $current = array_merge($path, [$page]);
        if ($page->id === $targetId) {
            return $path;
        }
        $found = breadcrumbAncestors($page->children, $targetId, $current);
        if (!empty($found) || array_filter($page->children, fn($c) => $c->id === $targetId)) {
            return $current;
        }
    }
    return [];
}
endif;

$ancestors = breadcrumbAncestors($repo->getTree(), $page_id);
$current   = $repo->findById($page_id);

if (empty($ancestors) && $current?->isRoot) {
    return;
}

$addontemplate = new Section();
$addontemplate->read($template_path . '/bs4breadcrumb.html');

$lis = '';
foreach ($ancestors as $ancestor) {
    $href = $modus === 'static'
        ? ($pathMap[$ancestor->id] ?? '/') . (str_ends_with($pathMap[$ancestor->id] ?? '/', '/') ? '' : '.html')
        : '/?site=' . $ancestor->id;

    $addontemplate->assign('LINKNAME',  htmlspecialchars($ancestor->title, ENT_QUOTES, 'UTF-8'));
    $addontemplate->assign('LINKTITLE', htmlspecialchars($ancestor->titleDesc, ENT_QUOTES, 'UTF-8'));
    $addontemplate->assign('LINKHREF',  htmlspecialchars($href, ENT_QUOTES, 'UTF-8'));
    $lis .= $addontemplate->fetch('Bcli');
}

if ($current) {
    $addontemplate->assign('LINKNAME', htmlspecialchars($current->title, ENT_QUOTES, 'UTF-8'));
    $lis .= $addontemplate->fetch('Bclicurrent');
}

$addontemplate->assign('LI', $lis);
$addoncontent = $addontemplate->fetch('Breadcrumb');

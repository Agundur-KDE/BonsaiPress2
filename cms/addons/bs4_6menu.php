<?php

declare(strict_types=1);

namespace BonsaiPress\Addons\Bs4_6menu;

use BonsaiPress\PageTreeIterator;
use BonsaiPress\Section;
use BonsaiPress\XmlPageRepository;

// Variables provided by PageRenderer::processAddons():
// $template_path, $page_id, $pathMap, $modus, $addonconfig, $addoncontent

if (!class_exists(MenuUlIterator::class)) {
    class MenuUlIterator extends \RecursiveIteratorIterator
    {
        public static string $UL   = '';
        public static string $ULCSS = '';

        public function endChildren(): void
        {
            self::$UL .= '</ul></li>';
        }

        public function beginChildren(): void
        {
            self::$UL .= self::$ULCSS;
        }
    }
}

$config = [
    'css_childIsCurrent' => 'childIsActive',
    'css_noChild'        => 'noChild',
    'css_hasParent'      => 'hasParent',
    'css_hasChild'       => 'hasChild',
    'css_current'        => 'active',
    'css_lvl'            => 'lvl_',
    'menu_maxDepth'      => -1,
];

foreach ($config as $key => $default) {
    $$key = isset($addonconfig->$key) ? (string) $addonconfig->$key : $default;
}

$addontemplate = new Section();
$addontemplate->read($template_path . '/bs4_6menu.html');

// Build ancestor id list for current page (for css_childIsCurrent)
$ancestors = [];
$repo      = new XmlPageRepository(dirname($template_path) . '/site_structure.xml');

function findAncestors(array $pages, int $targetId, array $path = []): array
{
    foreach ($pages as $p) {
        $current = array_merge($path, [$p->id]);
        if ($p->id === $targetId) {
            return $path;
        }
        $found = findAncestors($p->children, $targetId, $current);
        if (!empty($found) || in_array($targetId, array_column($p->children, 'id'))) {
            return $current;
        }
    }
    return [];
}

$ancestors = findAncestors($repo->getTree(), $page_id);

$rit = new MenuUlIterator(
    new PageTreeIterator($repo->getTree()),
    \RecursiveIteratorIterator::SELF_FIRST
);

if ((int) $menu_maxDepth >= 0) {
    $rit->setMaxDepth((int) $menu_maxDepth);
}

MenuUlIterator::$UL = MenuUlIterator::$ULCSS = $addontemplate->fetch('MenuUL');

foreach ($rit as $p) {
    $cssattributes = [];

    if (in_array($p->id, $ancestors)) {
        $cssattributes[] = $css_childIsCurrent;
    }

    if (empty($p->children)) {
        $cssattributes[] = $css_noChild;
    } else {
        $cssattributes[] = $css_hasChild;
    }

    if ($rit->getDepth() > 0) {
        $cssattributes[] = $css_hasParent;
    }

    if ($p->id === $page_id) {
        $cssattributes[] = $css_current;
    }

    $cssattributes[] = $css_lvl . $rit->getDepth();

    $href = $modus === 'static'
        ? ($pathMap[$p->id] ?? '/') . (empty($p->children) && !$p->isRoot ? '.html' : '')
        : '/?site=' . $p->id;

    if (!empty($p->children)) {
        $addontemplate->assign('HASCHILD', $addontemplate->fetch('MenuLinkHasChild'));
    } else {
        $addontemplate->assign('HASCHILD', '</li>');
    }

    if (isset($addonconfig->navclass) && !empty($addonconfig->navclass)) {
        $addontemplate->assign('NAVCLASS', (string) $addonconfig->navclass);
    }

    $addontemplate->assign('ID',             (string) $p->id);
    $addontemplate->assign('LINKTITLE',      htmlspecialchars($p->titleDesc, ENT_QUOTES, 'UTF-8'));
    $addontemplate->assign('LINKNAME',       htmlspecialchars($p->title, ENT_QUOTES, 'UTF-8'));
    $addontemplate->assign('LINKHREF',       htmlspecialchars($href, ENT_QUOTES, 'UTF-8'));
    $addontemplate->assign('CSSATTRIBUTES',  htmlspecialchars(implode(' ', $cssattributes), ENT_QUOTES, 'UTF-8'));
    $addontemplate->assign('LINK',           $addontemplate->fetch('MenuLink'));

    MenuUlIterator::$UL .= $addontemplate->fetch('MenuChild');
}

MenuUlIterator::$UL .= '</ul>';
$addontemplate->assign('UL', MenuUlIterator::$UL);
$addoncontent = $addontemplate->fetch('Nav');

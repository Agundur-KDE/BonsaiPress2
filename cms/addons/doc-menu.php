<?php

declare(strict_types=1);

$cfg = array_merge([
    'css_childIsCurrent' => 'childIsActive',
    'css_noChild'        => 'noChild',
    'css_hasParent'      => 'hasParent',
    'css_hasChild'       => 'hasChild',
    'css_current'        => 'active',
    'css_lvl'            => 'lvl_',
], array_filter((array) $addonconfig, fn($v) => $v !== ''));

$tplMenu = new \BonsaiPress\Section();
$tplMenu->keep_unassigned = false;
$tplMenu->read($template_path . '/doc-menu.html');

$ulOpen = $tplMenu->fetch('MenuUL');

$buildMenu = function (array $pages, int $depth) use (
    &$buildMenu, $tplMenu, $ulOpen, $cfg, $page_id, $pathMap, $modus, $ancestors
): string {
    $html = $ulOpen;

    foreach ($pages as $menuPage) {
        $css = [$cfg['css_lvl'] . $depth];

        if ($depth > 0)                                    $css[] = $cfg['css_hasParent'];
        if ($menuPage->id === $page_id)                    $css[] = $cfg['css_current'];
        if (in_array($menuPage->id, $ancestors, true))     $css[] = $cfg['css_childIsCurrent'];
        if (!empty($menuPage->children))                   $css[] = $cfg['css_hasChild'];
        else                                               $css[] = $cfg['css_noChild'];

        if ($modus === 'static') {
            $path = $pathMap[$menuPage->id] ?? '/';
            $href = str_ends_with($path, '/') ? $path : $path . '.html';
        } else {
            $href = '/?site=' . $menuPage->id;
        }

        $tplMenu->assign('GLYPH',         '');
        $tplMenu->assign('ID',            (string) $menuPage->id);
        $tplMenu->assign('LINKTITLE',     htmlspecialchars($menuPage->titleDesc, ENT_QUOTES, 'UTF-8'));
        $tplMenu->assign('LINKNAME',      htmlspecialchars($menuPage->title,     ENT_QUOTES, 'UTF-8'));
        $tplMenu->assign('LINKHREF',      htmlspecialchars($href,               ENT_QUOTES, 'UTF-8'));
        $tplMenu->assign('CSSATTRIBUTES', htmlspecialchars(implode(' ', $css),  ENT_QUOTES, 'UTF-8'));
        $tplMenu->assign('LINK',          $tplMenu->fetch('MenuLink'));

        if (!empty($menuPage->children)) {
            $tplMenu->assign('HASCHILD', $tplMenu->fetch('MenuLinkHasChild'));
            $html .= $tplMenu->fetch('MenuChild');
            $html .= $buildMenu($menuPage->children, $depth + 1);
            $html .= '</li>';
        } else {
            $tplMenu->assign('HASCHILD', '</li>');
            $html .= $tplMenu->fetch('MenuChild');
        }
    }

    return $html . '</ul>';
};

$tplMenu->assign('UL', $buildMenu($pageTree, 0));
$addoncontent = $tplMenu->fetch('Nav');

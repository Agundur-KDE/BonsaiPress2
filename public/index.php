<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use BonsaiPress\EcmsConfig;
use BonsaiPress\XmlPageRepository;

$config  = new EcmsConfig(dirname(__DIR__));
$lang    = $config->defaultLang();
$xmlPath = __DIR__ . '/../current/config/' . $lang . '/site_structure.xml';

if (!file_exists($xmlPath)) {
    http_response_code(500);
    echo 'site_structure.xml not found: ' . htmlspecialchars($xmlPath);
    exit;
}

$repo = new XmlPageRepository($xmlPath);

$pageId = (int) filter_input(INPUT_GET, 'site', FILTER_VALIDATE_INT,
    ['options' => ['min_range' => 1]]);

if ($pageId > 0) {
    $current = $repo->findById($pageId);
    if ($current === null) {
        http_response_code(404);
        echo '<p>Seite nicht gefunden (id=' . $pageId . ')</p>';
        exit;
    }
} else {
    // Wurzelseite (root="true") als Startseite
    $current = null;
    foreach ($repo->getTree() as $page) {
        if ($page->isRoot) {
            $current = $page;
            break;
        }
    }
}

function renderTree(array $pages, array $pathMap, int $currentId): string
{
    if (empty($pages)) {
        return '';
    }
    $html = '<ul>';
    foreach ($pages as $page) {
        $active = $page->id === $currentId ? ' class="active"' : '';
        $path   = htmlspecialchars($pathMap[$page->id] ?? '#');
        $title  = htmlspecialchars($page->title);
        $html  .= "<li{$active}><a href=\"?site={$page->id}\">{$title}</a> <small>{$path}</small>";
        $html  .= renderTree($page->children, $pathMap, $currentId);
        $html  .= '</li>';
    }
    $html .= '</ul>';
    return $html;
}

$pathMap = $repo->getPathMap();
$tree    = $repo->getTree();
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <title>BonsaiPress2 – Dev</title>
    <style>
        body { font-family: monospace; padding: 2rem; }
        ul   { margin: .25rem 0 .25rem 1.5rem; }
        .active > a { font-weight: bold; color: darkgreen; }
        small { color: #888; }
    </style>
</head>
<body>
    <h1>Seitenbaum</h1>
    <?= renderTree($tree, $pathMap, $current?->id ?? 0) ?>

    <?php if ($current): ?>
    <hr>
    <h2><?= htmlspecialchars($current->title) ?></h2>
    <dl>
        <dt>id</dt>          <dd><?= $current->id ?></dd>
        <dt>location</dt>    <dd><?= htmlspecialchars($current->location) ?></dd>
        <dt>content_type</dt><dd><?= htmlspecialchars($current->contentType) ?></dd>
        <dt>path</dt>        <dd><?= htmlspecialchars($current->path) ?></dd>
        <dt>titledesc</dt>   <dd><?= htmlspecialchars($current->titleDesc) ?></dd>
        <dt>addons</dt>      <dd><?= count($current->addons) ?></dd>
    </dl>
    <?php endif; ?>
</body>
</html>

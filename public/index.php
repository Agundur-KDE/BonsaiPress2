<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use BonsaiPress\EcmsConfig;
use BonsaiPress\PageRenderer;
use BonsaiPress\XmlPageRepository;

$basePath = dirname(__DIR__);
$config   = new EcmsConfig($basePath);
$lang     = $config->defaultLang();
$xmlPath  = $basePath . '/current/config/' . $lang . '/site_structure.xml';

if (!file_exists($xmlPath)) {
    http_response_code(500);
    echo 'site_structure.xml not found: ' . htmlspecialchars($xmlPath);
    exit;
}

$repo = new XmlPageRepository($xmlPath);

$pageId = (int) filter_input(INPUT_GET, 'site', FILTER_VALIDATE_INT,
    ['options' => ['min_range' => 1]]);

if ($pageId > 0) {
    $page = $repo->findById($pageId);
    if ($page === null) {
        http_response_code(404);
        echo 'Seite nicht gefunden (id=' . $pageId . ')';
        exit;
    }
} else {
    $page = null;
    foreach ($repo->getTree() as $p) {
        if ($p->isRoot) {
            $page = $p;
            break;
        }
    }
}

if ($page === null) {
    http_response_code(500);
    echo 'Keine Root-Seite gefunden';
    exit;
}

$renderer = new PageRenderer($config, $basePath);
echo $renderer->render($page, 'dynamic', $repo->getPathMap(), $repo->getTree());

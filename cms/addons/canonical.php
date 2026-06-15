<?php

declare(strict_types=1);

// Variables provided by PageRenderer::processAddons():
// $template_path, $page_id, $pathMap, $modus, $addonconfig, $addonparams, $addoncontent

$proto = $addonparams[0] ?? 'https';

$path = $pathMap[$page_id] ?? '/';
$url  = $proto . '://' . \ECMS_CONFIG::$domain;

if (!empty(\ECMS_CONFIG::$subdomain)) {
    $url = $proto . '://' . \ECMS_CONFIG::$subdomain . '.' . \ECMS_CONFIG::$domain;
}

if ($path !== '/') {
    $url .= $path;
    if (!str_ends_with($path, '/')) {
        $url .= '.html';
    }
}

$addoncontent = '<link rel="canonical" href="' . htmlspecialchars($url, ENT_QUOTES, 'UTF-8') . '" />';

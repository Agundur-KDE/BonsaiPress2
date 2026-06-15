<?php

declare(strict_types=1);

// Variables provided by PageRenderer::processAddons():
// $template_path, $page_id, $pathMap, $modus, $addonconfig, $addoncontent

$scriptDir = dirname($template_path) . '/inline_js/';
$scripts   = isset($addonconfig->scripts) ? explode(',', (string) $addonconfig->scripts) : [];

if (empty($scripts)) {
    return;
}

$buffer = '';
foreach ($scripts as $script) {
    $file = $scriptDir . trim($script) . '.js';
    if (file_exists($file) && is_readable($file)) {
        $buffer .= file_get_contents($file);
    }
}

if (empty($buffer)) {
    return;
}

$addoncontent = '<script>' . $buffer . '</script>';

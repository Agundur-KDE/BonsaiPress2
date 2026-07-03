<?php

declare(strict_types=1);

// Variables provided by PageRenderer::processAddons():
// $template_path, $page_id, $pathMap, $modus, $addonconfig, $addonparams, $addoncontent

$contenfile = dirname($template_path) . '/contenfiles/' . $page_id . '.html';

$date    = \BonsaiPress\LastModResolver::resolve($contenfile);
$dateIso = \BonsaiPress\LastModResolver::resolveIso($contenfile);

$addoncontent = '<time datetime="' . htmlspecialchars($dateIso, ENT_QUOTES, 'UTF-8') . '">'
    . 'Zuletzt aktualisiert: ' . htmlspecialchars($date, ENT_QUOTES, 'UTF-8') . '</time>';

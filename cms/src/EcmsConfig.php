<?php

declare(strict_types=1);

namespace BonsaiPress;

class EcmsConfig implements Config
{
    public function __construct(string $basePath)
    {
        // Constants are needed by cms/include/ecms_config.php and the client override
        if (!defined('_base_path_')) {
            define('_base_path_', $basePath);
        }
        if (!defined('_path_to_content_')) {
            define('_path_to_content_', $basePath . '/current');
        }

        // 1. CMS defaults (defines the ECMS_CONFIG class with all static properties)
        require_once $basePath . '/cms/include/ecms_config.php';

        // 2. Client overrides (sets ECMS_CONFIG::$* for this project)
        $clientConfig = $basePath . '/current/config/ecms_config.php';
        if (file_exists($clientConfig)) {
            require_once $clientConfig;
        }
    }

    public function defaultLang(): string
    {
        return \ECMS_CONFIG::$default_lang;
    }

    public function allowedLanguages(): array
    {
        $raw = \ECMS_CONFIG::$allowed_languages;
        // Old configs store this as array('de,en') instead of array('de', 'en')
        if (count($raw) === 1 && str_contains($raw[0], ',')) {
            return array_map('trim', explode(',', $raw[0]));
        }
        return $raw;
    }

    public function domain(): string
    {
        return \ECMS_CONFIG::$domain;
    }
}

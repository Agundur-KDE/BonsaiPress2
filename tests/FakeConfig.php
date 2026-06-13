<?php

declare(strict_types=1);

namespace BonsaiPress\Tests;

use BonsaiPress\Config;

class FakeConfig implements Config
{
    public function __construct(
        private string $defaultLang       = 'de',
        private array  $allowedLanguages  = ['de'],
        private string $domain            = 'example.com',
    ) {}

    public function defaultLang(): string    { return $this->defaultLang; }
    public function allowedLanguages(): array { return $this->allowedLanguages; }
    public function domain(): string          { return $this->domain; }
}

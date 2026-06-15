<?php

declare(strict_types=1);

namespace BonsaiPress;

interface Config
{
    public function defaultLang(): string;

    /** @return string[] e.g. ['de', 'en'] */
    public function allowedLanguages(): array;

    public function domain(): string;

    public function defaultHeadTemplate(): string;

    public function defaultMainTemplate(): string;

    public function defaultCss(): string;

    public function pathToResources(): string;
}

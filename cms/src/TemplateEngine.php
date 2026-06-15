<?php

    declare(strict_types=1);

    namespace BonsaiPress;
    interface TemplateEngine
    {
        public function read(string $template): void;

        public function assign(string $holder, string $value): void;

        public function add(string $holder, string $value): void;

        public function render(): string;
    }


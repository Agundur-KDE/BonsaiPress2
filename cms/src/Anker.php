<?php

declare(strict_types=1);

namespace BonsaiPress;

class Anker
{
    public function __construct(
        public readonly string $title,
        public readonly string $titleDesc,
        public readonly string $location,
    ) {}
}

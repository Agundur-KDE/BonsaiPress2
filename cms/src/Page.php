<?php

declare(strict_types=1);

namespace BonsaiPress;

class Page
{
    /**
     * @param Page[]  $children
     * @param Addon[] $addons
     */
    public function __construct(
        public readonly int    $id,
        public readonly string $title,
        public readonly string $location,
        public readonly string $contentType,
        public readonly string $titleDesc = '',
        public readonly bool   $isRoot = false,
        public readonly string $path = '',
        public readonly array  $children = [],
        public readonly array  $addons = [],
    ) {}

}

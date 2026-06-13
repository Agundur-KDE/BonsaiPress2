<?php

declare(strict_types=1);

namespace BonsaiPress;

interface PageRepository
{
    /** @return Page[] root-level pages in document order */
    public function getTree(): array;

    /** Primary navigation: returns null for unknown ids (caller must handle as 404) */
    public function findById(int $id): ?Page;

    /** @return array<int, string>  all pages flat: id => path */
    public function getPathMap(): array;
}

<?php

declare(strict_types=1);

namespace BonsaiPress;

class PageTreeIterator implements \RecursiveIterator
{
    private int $position = 0;

    /** @param Page[] $pages */
    public function __construct(private array $pages) {}

    public function current(): Page
    {
        return $this->pages[$this->position];
    }

    public function key(): int
    {
        return $this->position;
    }

    public function next(): void
    {
        $this->position++;
    }

    public function rewind(): void
    {
        $this->position = 0;
    }

    public function valid(): bool
    {
        return isset($this->pages[$this->position]);
    }

    public function hasChildren(): bool
    {
        return !empty($this->pages[$this->position]->children);
    }

    public function getChildren(): PageTreeIterator
    {
        return new PageTreeIterator($this->pages[$this->position]->children);
    }
}

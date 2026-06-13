<?php

declare(strict_types=1);

namespace BonsaiPress;

class Addon
{
    public function __construct(
        public readonly string $name,
        public readonly string $placeholder,
        public readonly array  $config = [],
    ) {}

    /**
     * Parses the legacy inline format: "name::PLACEHOLDER::param"
     */
    public static function fromString(string $raw): self
    {
        $parts = explode('::', trim($raw));
        return new self(
            name:        $parts[0] ?? '',
            placeholder: $parts[1] ?? '',
            config:      array_slice($parts, 2),
        );
    }
}

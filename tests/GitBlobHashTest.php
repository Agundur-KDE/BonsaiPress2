<?php

declare(strict_types=1);

namespace BonsaiPress\Tests;

use PHPUnit\Framework\TestCase;
use BonsaiPress\GitBlobHash;

class GitBlobHashTest extends TestCase
{
    public function testOfMatchesGitHashObject(): void
    {
        // Ground truth via `git hash-object` on this exact fixture file.
        $this->assertSame(
            '991912d894b345372b278ec2b8c67eeaa237cf65',
            GitBlobHash::ofFile(__DIR__ . '/fixtures/assets/style.css')
        );
    }

    public function testOfNormalizesCrlfBeforeHashing(): void
    {
        $this->assertSame(
            GitBlobHash::of("line1\nline2\n"),
            GitBlobHash::of("line1\r\nline2\r\n")
        );
    }

    public function testOfFileReturnsFalseForMissingFile(): void
    {
        $this->assertFalse(GitBlobHash::ofFile(__DIR__ . '/fixtures/assets/does-not-exist.css'));
    }
}

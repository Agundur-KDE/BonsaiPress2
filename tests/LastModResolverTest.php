<?php

declare(strict_types=1);

namespace BonsaiPress\Tests;

use BonsaiPress\LastModResolver;
use PHPUnit\Framework\TestCase;

class LastModResolverTest extends TestCase
{
    private string $repoDir;

    protected function setUp(): void
    {
        $this->repoDir = sys_get_temp_dir() . '/lastmod-test-' . uniqid();
        mkdir($this->repoDir);
        shell_exec('git -C ' . escapeshellarg($this->repoDir) . ' init -q -b main');
        shell_exec('git -C ' . escapeshellarg($this->repoDir) . ' config user.email test@example.com');
        shell_exec('git -C ' . escapeshellarg($this->repoDir) . ' config user.name Test');
    }

    protected function tearDown(): void
    {
        shell_exec('rm -rf ' . escapeshellarg($this->repoDir));
    }

    public function testResolvesGitCommitDateInBerlinTimezone(): void
    {
        $file = $this->repoDir . '/page.html';
        file_put_contents($file, 'content');

        // committed at 23:30 UTC on 2026-01-15 -> 2026-01-16 00:30 in Europe/Berlin (UTC+1 in January)
        putenv('GIT_AUTHOR_DATE=2026-01-15T23:30:00+00:00');
        putenv('GIT_COMMITTER_DATE=2026-01-15T23:30:00+00:00');
        shell_exec('git -C ' . escapeshellarg($this->repoDir) . ' add page.html');
        shell_exec('git -C ' . escapeshellarg($this->repoDir) . ' commit -q -m init');
        putenv('GIT_AUTHOR_DATE');
        putenv('GIT_COMMITTER_DATE');

        $this->assertSame('16.01.2026', LastModResolver::resolve($file));
    }

    public function testFallsBackToFilesystemMtimeWhenNoGitHistory(): void
    {
        $file = $this->repoDir . '/untracked.html';
        file_put_contents($file, 'content');
        touch($file, mktime(12, 0, 0, 3, 5, 2026));

        $this->assertSame('05.03.2026', LastModResolver::resolve($file));
    }

    public function testResolveIsoMatchesResolveForSameFile(): void
    {
        $file = $this->repoDir . '/page.html';
        file_put_contents($file, 'content');
        putenv('GIT_AUTHOR_DATE=2026-01-15T23:30:00+00:00');
        putenv('GIT_COMMITTER_DATE=2026-01-15T23:30:00+00:00');
        shell_exec('git -C ' . escapeshellarg($this->repoDir) . ' add page.html');
        shell_exec('git -C ' . escapeshellarg($this->repoDir) . ' commit -q -m init');
        putenv('GIT_AUTHOR_DATE');
        putenv('GIT_COMMITTER_DATE');

        $this->assertSame('2026-01-16', LastModResolver::resolveIso($file));
    }

    public function testReturnsLatestCommitAfterMultipleChanges(): void
    {
        $file = $this->repoDir . '/page.html';
        file_put_contents($file, 'v1');
        shell_exec('git -C ' . escapeshellarg($this->repoDir) . ' add page.html');
        putenv('GIT_AUTHOR_DATE=2026-02-01T10:00:00+01:00');
        putenv('GIT_COMMITTER_DATE=2026-02-01T10:00:00+01:00');
        shell_exec('git -C ' . escapeshellarg($this->repoDir) . ' commit -q -m v1');

        file_put_contents($file, 'v2');
        shell_exec('git -C ' . escapeshellarg($this->repoDir) . ' add page.html');
        putenv('GIT_AUTHOR_DATE=2026-06-20T10:00:00+02:00');
        putenv('GIT_COMMITTER_DATE=2026-06-20T10:00:00+02:00');
        shell_exec('git -C ' . escapeshellarg($this->repoDir) . ' commit -q -m v2');
        putenv('GIT_AUTHOR_DATE');
        putenv('GIT_COMMITTER_DATE');

        $this->assertSame('20.06.2026', LastModResolver::resolve($file));
    }
}

<?php

declare(strict_types=1);

namespace BonsaiPress\Command;

use BonsaiPress\EcmsConfig;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'rewrite-links', description: 'Rewrite legacy /ecms/index.php?site=X links to /?site=X in all content and template files')]
class RewriteLinksCommand extends Command
{
    private const PATTERN = '~/ecms/index\.php\?site=([0-9]+)~';

    protected function configure(): void
    {
        $this->addOption('dry-run', 'd', InputOption::VALUE_NONE, 'Show what would change without writing files');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $basePath = dirname(__DIR__, 3);
        $config   = new EcmsConfig($basePath);
        $dryRun   = (bool) $input->getOption('dry-run');

        if ($dryRun) {
            $output->writeln('<comment>Dry-run — keine Dateien werden geändert.</comment>');
        }

        $total   = 0;
        $changed = 0;

        foreach ($config->allowedLanguages() as $lang) {
            $dirs = [
                $basePath . '/current/config/' . $lang . '/contenfiles',
                $basePath . '/current/config/' . $lang . '/templates',
            ];

            foreach ($dirs as $dir) {
                if (!is_dir($dir)) {
                    continue;
                }

                foreach (glob($dir . '/*.html') ?: [] as $file) {
                    $total++;
                    $original = file_get_contents($file);

                    if (!preg_match(self::PATTERN, $original)) {
                        continue;
                    }

                    $rewritten = preg_replace_callback(
                        self::PATTERN,
                        fn($m) => '/?site=' . $m[1],
                        $original
                    );

                    $rel = str_replace($basePath, '', $file);
                    $output->writeln('  ✓ ' . $rel);

                    foreach ($this->diffLines($original, $rewritten) as $line) {
                        $output->writeln('    ' . $line);
                    }

                    if (!$dryRun) {
                        file_put_contents($file, $rewritten, LOCK_EX);
                    }

                    $changed++;
                }
            }
        }

        $output->writeln('');
        $output->writeln(sprintf(
            '<info>%d von %d Dateien %s.</info>',
            $changed,
            $total,
            $dryRun ? 'würden geändert' : 'geändert'
        ));

        return Command::SUCCESS;
    }

    /** @return string[] */
    private function diffLines(string $before, string $after): array
    {
        $lines  = [];
        $bLines = explode("\n", $before);
        $aLines = explode("\n", $after);

        foreach ($bLines as $i => $line) {
            if (($aLines[$i] ?? $line) !== $line) {
                $lines[] = '<fg=red>- ' . trim($line) . '</>';
                $lines[] = '<fg=green>+ ' . trim($aLines[$i]) . '</>';
            }
        }

        return $lines;
    }
}

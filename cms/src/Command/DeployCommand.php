<?php

declare(strict_types=1);

namespace BonsaiPress\Command;

use BonsaiPress\EcmsConfig;
use BonsaiPress\FtpClient;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'deploy', description: 'Deploy static export to server via FTP')]
class DeployCommand extends Command
{
    protected function configure(): void
    {
        $this
            ->addOption('dry-run',     'd', InputOption::VALUE_NONE, 'Show what would be deployed without uploading')
            ->addOption('no-include',  null, InputOption::VALUE_NONE, 'Skip current/include/ sync');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $basePath = dirname(__DIR__, 3);
        $config   = new EcmsConfig($basePath);
        $lang     = $config->defaultLang();

        if (\ECMS_CONFIG::$publish_methode !== 'ftp') {
            $output->writeln('<error>publish_methode ist nicht "ftp" — abbruch.</error>');
            return Command::FAILURE;
        }

        $dryRun    = (bool) $input->getOption('dry-run');
        $noInclude = (bool) $input->getOption('no-include');

        $ftpBase      = rtrim((string) \ECMS_CONFIG::$ftp_path_to_publish_, '/');
        $webRemote    = $ftpBase . '/web/';
        $includeRemote = $ftpBase . '/include/';

        $webLocal     = $basePath . '/current/static';
        $includeLocal = $basePath . '/current/include';
        $hashmeLocal  = $basePath . '/cms/templates/hashme.php';
        $hashmeRemote = $webRemote . 'hashme.php';

        $output->writeln('Domain : <comment>' . \ECMS_CONFIG::$url . '</comment>');
        $output->writeln('Host   : <comment>' . \ECMS_CONFIG::$ftp_host . '</comment>');
        $output->writeln('');

        try {
            $ftp = new FtpClient(
                \ECMS_CONFIG::$ftp_host,
                !(bool) \ECMS_CONFIG::$ftp_force_ssl_off,
                (int) \ECMS_CONFIG::$ftp_port,
            );
            $ftp->login(\ECMS_CONFIG::$ftp_user, \ECMS_CONFIG::$ftp_passwd);
            $ftp->passive((bool) \ECMS_CONFIG::$ftp_force_active);
        } catch (\RuntimeException $e) {
            $output->writeln('<error>' . $e->getMessage() . '</error>');
            return Command::FAILURE;
        }

        // Ensure Hetzner Managed server structure exists
        $output->write('Server-Verzeichnisse prüfen ... ');
        $ftp->ensureDir($ftpBase . '/web');
        $ftp->ensureDir($ftpBase . '/include');
        $output->writeln('<info>ok</info>');

        // Upload hashme.php temporarily to server
        $output->write('Hash-Script hochladen ... ');
        try {
            $ftp->upload($hashmeRemote, $hashmeLocal, false);
            $output->writeln('<info>ok</info>');
        } catch (\RuntimeException $e) {
            $output->writeln('<error>' . $e->getMessage() . '</error>');
            return Command::FAILURE;
        }

        // Fetch server hashes via HTTP
        $output->write('Server-Hashes abrufen (web) ... ');
        $serverWebHashes = $this->fetchRemoteHashes(\ECMS_CONFIG::$url . 'hashme.php', $output);
        if ($serverWebHashes === null) {
            $ftp->delete($hashmeRemote);
            return Command::FAILURE;
        }
        $output->writeln('<info>' . count($serverWebHashes) . ' Dateien</info>');

        $serverIncludeHashes = [];
        if (!$noInclude && is_dir($includeLocal)) {
            $output->write('Server-Hashes abrufen (include) ... ');
            $serverIncludeHashes = $this->fetchRemoteHashes(\ECMS_CONFIG::$url . 'hashme.php?include=true', $output);
            if ($serverIncludeHashes === null) {
                $serverIncludeHashes = [];
            }
            $output->writeln('<info>' . count($serverIncludeHashes) . ' Dateien</info>');
        }

        // Remove hashme.php from server
        $output->write('Hash-Script entfernen ... ');
        try {
            $ftp->delete($hashmeRemote);
            $output->writeln('<info>ok</info>');
        } catch (\RuntimeException $e) {
            $output->writeln('<comment>Warnung: ' . $e->getMessage() . '</comment>');
        }

        // Load .bonsaiignore
        $ignorePatterns = $this->loadIgnorePatterns($basePath . '/current/.bonsaiignore');

        // Compute local hashes
        $output->write('Lokale Hashes berechnen ... ');
        $localWebHashes     = $this->buildLocalHashes($webLocal, $ignorePatterns);
        $localIncludeHashes = (!$noInclude && is_dir($includeLocal))
            ? $this->buildLocalHashes($includeLocal, $ignorePatterns)
            : [];
        $output->writeln('<info>ok</info>');
        $output->writeln('');

        // Strip ignored files from server hashes so they are never deleted
        $serverWebHashes     = $this->stripIgnored($serverWebHashes, $ignorePatterns);
        $serverIncludeHashes = $this->stripIgnored($serverIncludeHashes, $ignorePatterns);

        // Compare
        $webDiff     = $this->diff($localWebHashes, $serverWebHashes);
        $includeDiff = $this->diff($localIncludeHashes, $serverIncludeHashes);

        $this->printDiff($output, $webDiff, 'web');
        if (!$noInclude) {
            $this->printDiff($output, $includeDiff, 'include');
        }

        if ($dryRun) {
            $output->writeln('<comment>Dry-run — nichts hochgeladen.</comment>');
            $ftp->close();
            return Command::SUCCESS;
        }

        // Sync web
        $total = count($webDiff['add']) + count($webDiff['update']) + count($webDiff['delete']);
        $done  = 0;

        foreach (array_merge($webDiff['add'], $webDiff['update']) as $file) {
            $ftp->upload($webRemote . $file, $webLocal . '/' . $file);
            $output->writeln('  ✓ ' . $file);
            $done++;
        }
        foreach ($webDiff['delete'] as $file) {
            try {
                $ftp->delete($webRemote . $file);
                $output->writeln('  ✗ ' . $file);
            } catch (\RuntimeException) {
                $output->writeln('  <comment>! konnte nicht löschen: ' . $file . '</comment>');
            }
            $done++;
        }

        // Sync include
        if (!$noInclude) {
            foreach (array_merge($includeDiff['add'], $includeDiff['update']) as $file) {
                $ftp->upload($includeRemote . $file, $includeLocal . '/' . $file);
                $output->writeln('  ✓ include/' . $file);
            }
            foreach ($includeDiff['delete'] as $file) {
                try {
                    $ftp->delete($includeRemote . $file);
                    $output->writeln('  ✗ include/' . $file);
                } catch (\RuntimeException) {
                    $output->writeln('  <comment>! konnte nicht löschen: include/' . $file . '</comment>');
                }
            }
        }

        $ftp->close();
        $output->writeln('<info>Deploy fertig.</info>');

        $this->printGeoScores($output, $basePath, $webDiff);

        return Command::SUCCESS;
    }

    /**
     * Optionale Post-Deploy-Prüfung: nur aktiv wenn ECMS_CONFIG::$geo_check_on_deploy
     * true ist UND current/include/geo-scanner/GeoScanHandler.php existiert (Submodule
     * agundur-kde/geo-scanner). Scannt jede geänderte .html-Seite live und gibt den
     * GEO-Score aus. Default false — macht bei jedem Deploy einen HTTP-Roundtrip pro
     * geänderter Seite, soll nicht ungefragt jeden Deploy verlangsamen.
     *
     * @param array{add: string[], update: string[], delete: string[]} $webDiff
     */
    private function printGeoScores(OutputInterface $output, string $basePath, array $webDiff): void
    {
        if (!(bool) (\ECMS_CONFIG::$geo_check_on_deploy ?? false)) {
            return;
        }

        $handlerFile = $basePath . '/current/include/geo-scanner/GeoScanHandler.php';
        if (!is_file($handlerFile)) {
            return;
        }

        $pages = array_values(array_filter(
            array_merge($webDiff['add'], $webDiff['update']),
            fn(string $f) => str_ends_with($f, '.html')
        ));
        if (empty($pages)) {
            return;
        }

        require_once $handlerFile;

        $output->writeln('');
        $output->writeln('GEO-Check der geänderten Seiten:');
        $base = rtrim((string) \ECMS_CONFIG::$url, '/');

        foreach ($pages as $page) {
            $url = $base . '/' . $page;
            try {
                $result = (new \GeoScanHandler($url, 'de'))->run();
                $output->writeln(sprintf('  %-45s %d/100', $page, $result['score']));
            } catch (\Throwable) {
                $output->writeln("  <comment>! $page — konnte nicht geprüft werden</comment>");
            }
        }
    }

    /** @return array<string,string>|null */
    private function fetchRemoteHashes(string $url, OutputInterface $output): ?array
    {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_USERAGENT      => 'BonsaiPress/2.0',
            CURLOPT_TIMEOUT        => 30,
        ]);

        if (!empty(\ECMS_CONFIG::$htaccess_user) && !empty(\ECMS_CONFIG::$htaccess_passwd)) {
            curl_setopt($ch, CURLOPT_USERPWD, \ECMS_CONFIG::$htaccess_user . ':' . \ECMS_CONFIG::$htaccess_passwd);
        }

        $body = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err  = curl_error($ch);
        curl_close($ch);

        if ($err) {
            $output->writeln('<error>curl: ' . $err . '</error>');
            return null;
        }

        if ($code !== 200) {
            $output->writeln('<error>HTTP ' . $code . ' von ' . $url . '</error>');
            return null;
        }

        $data = json_decode((string) $body, true);
        if (!is_array($data)) {
            $output->writeln('<error>Ungültige JSON-Antwort von ' . $url . '</error>');
            return null;
        }

        return $data;
    }

    /** @return array<string,string> file => sha1 */
    /** @param string[] $ignorePatterns */
    private function buildLocalHashes(string $dir, array $ignorePatterns = []): array
    {
        $hashes = [];
        $it     = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($it as $file) {
            if (!$file->isFile() || $file->isLink()) {
                continue;
            }
            if (str_starts_with($file->getFilename(), '.')) {
                continue;
            }

            $rel = ltrim(str_replace($dir, '', $file->getPathname()), DIRECTORY_SEPARATOR);

            foreach ($ignorePatterns as $pattern) {
                if (fnmatch($pattern, $file->getFilename()) || fnmatch($pattern, $rel)) {
                    continue 2;
                }
            }

            $content = (string) file_get_contents($file->getPathname());
            $content = str_replace("\r\n", "\n", $content);
            $hashes[$rel] = sha1('blob ' . strlen($content) . "\0" . $content);
        }

        return $hashes;
    }

    /**
     * @param array<string,string> $hashes
     * @param string[] $patterns
     * @return array<string,string>
     */
    private function stripIgnored(array $hashes, array $patterns): array
    {
        if (empty($patterns)) {
            return $hashes;
        }
        return array_filter($hashes, function(string $rel) use ($patterns): bool {
            $filename = basename($rel);
            foreach ($patterns as $pattern) {
                if (fnmatch($pattern, $filename) || fnmatch($pattern, $rel)) {
                    return false;
                }
            }
            return true;
        }, ARRAY_FILTER_USE_KEY);
    }

    /** @return string[] */
    private function loadIgnorePatterns(string $path): array
    {
        if (!is_file($path)) {
            return [];
        }
        return array_values(array_filter(
            array_map('trim', file($path, FILE_IGNORE_NEW_LINES)),
            fn(string $l) => $l !== '' && !str_starts_with($l, '#')
        ));
    }

    /**
     * @param array<string,string> $local
     * @param array<string,string> $remote
     * @return array{add: string[], update: string[], delete: string[]}
     */
    private function diff(array $local, array $remote): array
    {
        $add    = array_keys(array_diff_key($local, $remote));
        $delete = array_keys(array_diff_key($remote, $local));
        $update = [];

        foreach (array_intersect_key($local, $remote) as $file => $hash) {
            if ($hash !== $remote[$file]) {
                $update[] = $file;
            }
        }

        return compact('add', 'update', 'delete');
    }

    /** @param array{add: string[], update: string[], delete: string[]} $diff */
    private function printDiff(OutputInterface $output, array $diff, string $label): void
    {
        $output->writeln("🟡 Neu hochladen ($label):");
        foreach ($diff['add'] as $f) {
            $output->writeln('   ' . $f);
        }
        $output->writeln("🟢 Aktualisieren ($label):");
        foreach ($diff['update'] as $f) {
            $output->writeln('   ' . $f);
        }
        $output->writeln("🔴 Löschen ($label):");
        foreach ($diff['delete'] as $f) {
            $output->writeln('   ' . $f);
        }
        $output->writeln('');
    }
}

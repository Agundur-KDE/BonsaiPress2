<?php

declare(strict_types=1);

namespace BonsaiPress;

class FtpClient
{
    private string $baseUrl;
    private string $userPwd = '';
    private bool $usePassive = true;
    private int $sslMode;
    private ?\CurlHandle $ch = null;

    public function __construct(string $host, bool $ssl = true, int $port = 21)
    {
        $this->baseUrl = 'ftp://' . $host . ':' . $port;
        // CURLFTPSSL_ALL = explicit TLS (AUTH TLS on port 21), CURLFTPSSL_NONE = plain FTP
        $this->sslMode = $ssl ? CURLFTPSSL_ALL : CURLFTPSSL_NONE;
    }

    public function login(string $user, string $pass): void
    {
        $this->userPwd = $user . ':' . $pass;

        // Verify credentials with a NOOP command
        $ch = $this->init($this->baseUrl . '/');
        curl_setopt($ch, CURLOPT_NOBODY, true);
        curl_exec($ch);
        $err = curl_error($ch);

        if ($err !== '') {
            throw new \RuntimeException("FTP: login failed for user $user: $err");
        }
    }

    public function passive(bool $mode = true): void
    {
        $this->usePassive = $mode;
    }

    public function upload(string $remotePath, string $localPath, bool $createDirs = true): void
    {
        $fp = fopen($localPath, 'rb');
        if ($fp === false) {
            throw new \RuntimeException("FTP: cannot open local file: $localPath");
        }
        $size = filesize($localPath);

        $ch = $this->init($this->baseUrl . $remotePath);
        curl_setopt($ch, CURLOPT_UPLOAD, true);
        curl_setopt($ch, CURLOPT_INFILE, $fp);
        curl_setopt($ch, CURLOPT_INFILESIZE, $size !== false ? $size : -1);
        curl_setopt($ch, CURLOPT_FTP_CREATE_MISSING_DIRS, $createDirs ? CURLFTP_CREATE_DIR : CURLFTP_CREATE_DIR_NONE);

        $this->exec($ch, "upload failed: $remotePath");
        fclose($fp);
    }

    public function delete(string $remotePath): void
    {
        $dir = dirname($remotePath);

        $ch = $this->init($this->baseUrl . rtrim($dir, '/') . '/');
        curl_setopt($ch, CURLOPT_NOBODY, true);
        curl_setopt($ch, CURLOPT_QUOTE, ['DELE ' . $remotePath]);

        $this->exec($ch, "delete failed: $remotePath");
    }

    /** No-op: cURL creates directories automatically via CURLOPT_FTP_CREATE_MISSING_DIRS. */
    public function mkdirs(string $remotePath): void {}

    /** Create remote directory if it doesn't exist. Ignores 550 (already exists). */
    public function ensureDir(string $remotePath): void
    {
        $ch = $this->init($this->baseUrl . '/');
        curl_setopt($ch, CURLOPT_NOBODY, true);
        curl_setopt($ch, CURLOPT_QUOTE, ['MKD ' . $remotePath]);
        curl_exec($ch); // 550 if dir exists — intentionally ignored
    }

    /** Closes the reused connection. Call once at the end of a deploy run. */
    public function close(): void
    {
        if ($this->ch !== null) {
            curl_close($this->ch);
            $this->ch = null;
        }
    }

    private function init(string $url): \CurlHandle
    {
        // ponytail: reuse one curl handle for the whole run instead of
        // curl_init()+curl_close() per file — libcurl then keeps the FTP
        // control connection (incl. TLS handshake) open across calls.
        // Re-handshaking per file was the actual deploy bottleneck for
        // many-small-files trees, not bandwidth.
        if ($this->ch === null) {
            $ch = curl_init();
            if ($ch === false) {
                throw new \RuntimeException('FTP: curl_init failed');
            }
            $this->ch = $ch;
        } else {
            curl_reset($this->ch);
        }

        curl_setopt_array($this->ch, [
            CURLOPT_URL            => $url,
            CURLOPT_USERPWD        => $this->userPwd,
            CURLOPT_USE_SSL        => $this->sslMode,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => 0,
            CURLOPT_FTP_USE_EPRT   => !$this->usePassive,
            CURLOPT_FTP_USE_EPSV   => $this->usePassive,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 60,
            CURLOPT_FORBID_REUSE   => false,
            CURLOPT_FRESH_CONNECT  => false,
        ]);
        return $this->ch;
    }

    private function exec(\CurlHandle $ch, string $context): void
    {
        curl_exec($ch);
        $err = curl_error($ch);
        if ($err !== '') {
            throw new \RuntimeException("FTP: $context: $err");
        }
    }
}

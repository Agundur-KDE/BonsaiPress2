<?php

declare(strict_types=1);

namespace BonsaiPress;

class FtpClient
{
    private string $baseUrl;
    private string $userPwd = '';
    private bool $usePassive = true;
    private int $sslMode;

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
        curl_close($ch);

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

    public function close(): void {}

    private function init(string $url): \CurlHandle
    {
        $ch = curl_init($url);
        if ($ch === false) {
            throw new \RuntimeException('FTP: curl_init failed');
        }
        curl_setopt_array($ch, [
            CURLOPT_USERPWD       => $this->userPwd,
            CURLOPT_USE_SSL       => $this->sslMode,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => 0,
            CURLOPT_FTP_USE_EPRT  => !$this->usePassive,
            CURLOPT_FTP_USE_EPSV  => $this->usePassive,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT       => 60,
        ]);
        return $ch;
    }

    private function exec(\CurlHandle $ch, string $context): void
    {
        curl_exec($ch);
        $err = curl_error($ch);
        curl_close($ch);
        if ($err !== '') {
            throw new \RuntimeException("FTP: $context: $err");
        }
    }
}

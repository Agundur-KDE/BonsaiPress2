<?php

declare(strict_types=1);

namespace BonsaiPress;

class FtpClient
{
    private \FTP\Connection $conn;

    public function __construct(string $host, bool $ssl = true, int $port = 21, int $timeout = 10)
    {
        $conn = false;

        if ($ssl && function_exists('ftp_ssl_connect')) {
            $conn = @ftp_ssl_connect($host, $port, $timeout);
        }

        if ($conn === false) {
            $conn = @ftp_connect($host, $port, $timeout);
        }

        if ($conn === false) {
            throw new \RuntimeException("FTP: cannot connect to $host");
        }

        $this->conn = $conn;
    }

    public function login(string $user, string $pass): void
    {
        if (!@ftp_login($this->conn, $user, $pass)) {
            throw new \RuntimeException("FTP: login failed for user $user");
        }
    }

    public function passive(bool $mode = true): void
    {
        ftp_pasv($this->conn, $mode);
    }

    public function upload(string $remotePath, string $localPath): void
    {
        $this->mkdirs($remotePath);
        if (!ftp_put($this->conn, $remotePath, $localPath, FTP_BINARY)) {
            throw new \RuntimeException("FTP: upload failed: $remotePath");
        }
    }

    public function delete(string $remotePath): void
    {
        if (!@ftp_delete($this->conn, $remotePath)) {
            throw new \RuntimeException("FTP: delete failed: $remotePath");
        }
    }

    /** Returns file content from remote path. */
    public function get(string $remotePath): string
    {
        $fp = fopen('php://temp', 'r+');
        if ($fp === false || !ftp_fget($this->conn, $fp, $remotePath, FTP_BINARY)) {
            if ($fp !== false) {
                fclose($fp);
            }
            throw new \RuntimeException("FTP: download failed: $remotePath");
        }
        rewind($fp);
        $content = stream_get_contents($fp);
        fclose($fp);
        return $content ?: '';
    }

    /** Creates all intermediate directories for a remote path. */
    public function mkdirs(string $remotePath): void
    {
        $dir = dirname($remotePath);
        if ($dir === '.' || $dir === '/') {
            return;
        }

        $parts = explode('/', ltrim($dir, '/'));
        @ftp_chdir($this->conn, '/');

        foreach ($parts as $part) {
            if ($part === '') {
                continue;
            }
            if (!@ftp_chdir($this->conn, $part)) {
                ftp_mkdir($this->conn, $part);
                ftp_chdir($this->conn, $part);
            }
        }

        @ftp_chdir($this->conn, '/');
    }

    public function close(): void
    {
        ftp_close($this->conn);
    }
}

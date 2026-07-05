<?php

declare(strict_types=1);

namespace BonsaiPress;

class IndexNowNotifier
{
    /** @param string[] $files relative paths as produced by DeployCommand's web diff */
    /** @return string[] */
    public static function buildUrls(array $files, string $baseUrl): array
    {
        $baseUrl = rtrim($baseUrl, '/');
        $urls    = [];

        foreach ($files as $file) {
            if (!str_ends_with($file, '.html')) {
                continue;
            }

            if ($file === 'index.html') {
                $urls[] = $baseUrl . '/';
            } elseif (str_ends_with($file, '/index.html')) {
                $urls[] = $baseUrl . '/' . substr($file, 0, -strlen('index.html'));
            } else {
                $urls[] = $baseUrl . '/' . $file;
            }
        }

        return $urls;
    }

    /** @param string[] $urls */
    /** @return array{host: string, key: string, keyLocation: string, urlList: string[]} */
    public static function buildPayload(string $host, string $key, string $keyLocation, array $urls): array
    {
        return [
            'host'        => $host,
            'key'         => $key,
            'keyLocation' => $keyLocation,
            'urlList'     => $urls,
        ];
    }

    /** @param string[] $urls */
    public function notify(string $host, string $key, string $keyLocation, array $urls): bool
    {
        if (empty($urls)) {
            return true;
        }

        $payload = self::buildPayload($host, $key, $keyLocation, $urls);

        $ch = curl_init('https://api.indexnow.org/indexnow');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_HTTPHEADER     => ['Content-Type: application/json; charset=utf-8'],
            CURLOPT_POSTFIELDS     => json_encode($payload, JSON_UNESCAPED_SLASHES),
            CURLOPT_TIMEOUT        => 15,
        ]);
        curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return $code >= 200 && $code < 300;
    }
}

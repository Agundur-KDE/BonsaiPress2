<?php

declare(strict_types=1);

namespace BonsaiPress;

class LastModResolver
{
    public static function resolve(string $filePath, string $timezone = 'Europe/Berlin'): string
    {
        return self::date($filePath, $timezone)->format('d.m.Y');
    }

    public static function resolveIso(string $filePath, string $timezone = 'Europe/Berlin'): string
    {
        return self::date($filePath, $timezone)->format('Y-m-d');
    }

    private static function date(string $filePath, string $timezone): \DateTimeImmutable
    {
        $iso = self::gitDate($filePath);
        $dt  = $iso !== null
            ? new \DateTimeImmutable($iso)
            : (new \DateTimeImmutable())->setTimestamp(filemtime($filePath) ?: time());

        return $dt->setTimezone(new \DateTimeZone($timezone));
    }

    private static function gitDate(string $filePath): ?string
    {
        if (!is_file($filePath)) {
            return null;
        }
        $dir  = escapeshellarg(dirname($filePath));
        $file = escapeshellarg(basename($filePath));
        $out  = @shell_exec("git -C $dir log -1 --format=%aI -- $file 2>/dev/null");
        $out  = $out !== null ? trim($out) : '';

        return $out !== '' ? $out : null;
    }
}

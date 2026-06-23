<?php

namespace Simple\Storage;

class Storage
{
    public static function disk(?string $name = null): Disk
    {
        return StorageManager::disk($name);
    }

    public static function write(string $path, string $contents, string $visibility = 'public'): void
    {
        self::disk()->write($path, $contents, $visibility);
    }

    public static function writeStream(string $path, $stream, string $visibility = 'public'): void
    {
        self::disk()->writeStream($path, $stream, $visibility);
    }

    public static function get(string $path): ?string
    {
        return self::disk()->get($path);
    }

    public static function readStream(string $path)
    {
        return self::disk()->readStream($path);
    }

    public static function exists(string $path): bool
    {
        return self::disk()->exists($path);
    }

    public static function delete(string $path): void
    {
        self::disk()->delete($path);
    }

    public static function copy(string $from, string $to): void
    {
        self::disk()->copy($from, $to);
    }

    public static function move(string $from, string $to): void
    {
        self::disk()->move($from, $to);
    }

    public static function size(string $path): ?int
    {
        return self::disk()->size($path);
    }

    public static function mimeType(string $path): ?string
    {
        return self::disk()->mimeType($path);
    }

    public static function lastModified(string $path): ?int
    {
        return self::disk()->lastModified($path);
    }

    public static function url(string $path): ?string
    {
        return self::disk()->url($path);
    }

    public static function temporaryUrl(string $path, int $expiresInSeconds): ?string
    {
        return self::disk()->temporaryUrl($path, $expiresInSeconds);
    }

    public static function setVisibility(string $path, string $visibility): void
    {
        self::disk()->setVisibility($path, $visibility);
    }

    public static function visibility(string $path): ?string
    {
        return self::disk()->visibility($path);
    }

    public static function configure(array $disks, string $default): void
    {
        StorageManager::configure($disks, $default);
    }
}

<?php

namespace Simple\Storage;

use League\Flysystem\Filesystem;
use League\Flysystem\Local\LocalFilesystemAdapter;
use League\Flysystem\UnixVisibility\PortableVisibilityConverter;

class StorageManager
{
    private static array $disks = [];
    private static array $config = [];

    public static function configure(array $disks, string $default): void
    {
        self::$config = ['disks' => $disks, 'default' => $default];
        self::$disks = [];
    }

    public static function disk(?string $name = null): Disk
    {
        $name ??= self::$config['default'] ?? 'local';

        if (isset(self::$disks[$name])) {
            return self::$disks[$name];
        }

        $config = self::$config['disks'][$name] ?? null;
        if (!$config) {
            throw new \InvalidArgumentException("Disk [$name] is not configured.");
        }

        return self::$disks[$name] = self::resolve($name, $config);
    }

    private static function resolve(string $name, array $config): Disk
    {
        return match ($config['driver']) {
            'local' => self::resolveLocal($name, $config),
            's3'    => self::resolveS3($name, $config),
            default => throw new \InvalidArgumentException("Driver [{$config['driver']}] is not supported."),
        };
    }

    private static function resolveLocal(string $name, array $config): LocalDisk
    {
        $root = rtrim($config['root'] ?? getcwd() . '/public/storage', '/');
        $visibility = PortableVisibilityConverter::fromArray([
            'file' => ['public' => 0644, 'private' => 0600],
            'dir'  => ['public' => 0755, 'private' => 0700],
        ]);
        $adapter = new LocalFilesystemAdapter($root, $visibility);
        $filesystem = new Filesystem($adapter);
        $url = $config['url'] ?? null;
        return new LocalDisk($filesystem, $root, $url);
    }

    private static function resolveS3(string $name, array $config): S3Disk
    {
        return new S3Disk($config);
    }
}

<?php

namespace Simple\Storage;

use League\Flysystem\Filesystem;

class LocalDisk implements Disk
{
    private Filesystem $filesystem;
    private string $root;
    private ?string $urlPrefix;

    public function __construct(Filesystem $filesystem, string $root, ?string $urlPrefix)
    {
        $this->filesystem = $filesystem;
        $this->root = $root;
        $this->urlPrefix = $urlPrefix;
    }

    public function write(string $path, string $contents, string $visibility = 'public'): void
    {
        $config = [];
        if ($visibility === 'public' || $visibility === 'private') {
            $config['visibility'] = $visibility;
        }
        $this->filesystem->write($path, $contents, $config);
    }

    public function writeStream(string $path, $stream, string $visibility = 'public'): void
    {
        $config = [];
        if ($visibility === 'public' || $visibility === 'private') {
            $config['visibility'] = $visibility;
        }
        $this->filesystem->writeStream($path, $stream, $config);
    }

    public function get(string $path): ?string
    {
        return $this->filesystem->read($path) ?: null;
    }

    public function readStream(string $path)
    {
        return $this->filesystem->readStream($path) ?: null;
    }

    public function exists(string $path): bool
    {
        return $this->filesystem->has($path);
    }

    public function delete(string $path): void
    {
        $this->filesystem->delete($path);
    }

    public function copy(string $from, string $to): void
    {
        $this->filesystem->copy($from, $to);
    }

    public function move(string $from, string $to): void
    {
        $this->filesystem->move($from, $to);
    }

    public function size(string $path): ?int
    {
        return $this->filesystem->fileSize($path);
    }

    public function mimeType(string $path): ?string
    {
        return $this->filesystem->mimeType($path);
    }

    public function lastModified(string $path): ?int
    {
        return $this->filesystem->lastModified($path);
    }

    public function url(string $path): ?string
    {
        if ($this->urlPrefix === null) {
            return null;
        }
        return rtrim($this->urlPrefix, '/') . '/' . ltrim($path, '/');
    }

    public function temporaryUrl(string $path, int $expiresInSeconds): ?string
    {
        return $this->url($path);
    }

    public function setVisibility(string $path, string $visibility): void
    {
        $this->filesystem->setVisibility($path, $visibility);
    }

    public function visibility(string $path): ?string
    {
        return $this->filesystem->visibility($path);
    }
}

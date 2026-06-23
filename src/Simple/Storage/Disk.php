<?php

namespace Simple\Storage;

interface Disk
{
    public function write(string $path, string $contents, string $visibility = 'public'): void;
    public function writeStream(string $path, $stream, string $visibility = 'public'): void;
    public function get(string $path): ?string;
    public function readStream(string $path);
    public function exists(string $path): bool;
    public function delete(string $path): void;
    public function copy(string $from, string $to): void;
    public function move(string $from, string $to): void;
    public function size(string $path): ?int;
    public function mimeType(string $path): ?string;
    public function lastModified(string $path): ?int;
    public function url(string $path): ?string;
    public function temporaryUrl(string $path, int $expiresInSeconds): ?string;
    public function setVisibility(string $path, string $visibility): void;
    public function visibility(string $path): ?string;
}

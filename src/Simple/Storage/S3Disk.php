<?php

namespace Simple\Storage;

use Aws\S3\S3Client;
use League\Flysystem\AwsS3V3\AwsS3V3Adapter;
use League\Flysystem\Filesystem;

class S3Disk implements Disk
{
    private Filesystem $filesystem;
    private S3Client $client;
    private string $bucket;
    private ?string $urlPrefix;

    public function __construct(array $config)
    {
        $this->bucket = $config['bucket'];
        $this->urlPrefix = $config['url'] ?? null;

        $this->client = new S3Client([
            'credentials' => [
                'key'    => $config['key'] ?? '',
                'secret' => $config['secret'] ?? '',
            ],
            'region' => $config['region'] ?? 'us-east-1',
            'version' => 'latest',
        ]);

        $adapter = new AwsS3V3Adapter($this->client, $this->bucket);
        $this->filesystem = new Filesystem($adapter);
    }

    public function write(string $path, string $contents, string $visibility = 'public'): void
    {
        $config = [];
        if ($visibility === 'public') {
            $config['ACL'] = 'public-read';
        } elseif ($visibility === 'private') {
            $config['ACL'] = 'private';
        }
        $this->filesystem->write($path, $contents, $config);
    }

    public function writeStream(string $path, $stream, string $visibility = 'public'): void
    {
        $config = [];
        if ($visibility === 'public') {
            $config['ACL'] = 'public-read';
        } elseif ($visibility === 'private') {
            $config['ACL'] = 'private';
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
        if ($this->urlPrefix) {
            return rtrim($this->urlPrefix, '/') . '/' . ltrim($path, '/');
        }
        return $this->client->getObjectUrl($this->bucket, $path);
    }

    public function temporaryUrl(string $path, int $expiresInSeconds): ?string
    {
        $cmd = $this->client->getCommand('GetObject', [
            'Bucket' => $this->bucket,
            'Key'    => $path,
        ]);
        $request = $this->client->createPresignedRequest($cmd, "+{$expiresInSeconds} seconds");
        return (string) $request->getUri();
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

<?php

namespace Simple\Tests;

use PHPUnit\Framework\TestCase;
use Simple\Storage\Storage;
use Simple\Storage\StorageManager;

class StorageTest extends TestCase
{
    private string $tempDir;

    protected function setUp(): void
    {
        $this->tempDir = sys_get_temp_dir() . '/simply_storage_' . uniqid();
        mkdir($this->tempDir, 0755, true);

        StorageManager::configure([
            'local' => [
                'driver' => 'local',
                'root' => $this->tempDir,
                'url' => '/storage',
            ],
        ], 'local');
    }

    protected function tearDown(): void
    {
        $this->rmdirRecursive($this->tempDir);
    }

    private function rmdirRecursive(string $dir): void
    {
        if (!is_dir($dir)) return;
        $files = array_diff(scandir($dir), ['.', '..']);
        foreach ($files as $f) {
            $p = "$dir/$f";
            is_dir($p) ? $this->rmdirRecursive($p) : unlink($p);
        }
        rmdir($dir);
    }

    public function testWriteAndGet(): void
    {
        Storage::write('hello.txt', 'Hello World');
        $this->assertSame('Hello World', Storage::get('hello.txt'));
    }

    public function testExists(): void
    {
        Storage::write('exists.txt', 'test');
        $this->assertTrue(Storage::exists('exists.txt'));
        $this->assertFalse(Storage::exists('nope.txt'));
    }

    public function testDelete(): void
    {
        Storage::write('delete.txt', 'delete me');
        $this->assertTrue(Storage::exists('delete.txt'));
        Storage::delete('delete.txt');
        $this->assertFalse(Storage::exists('delete.txt'));
    }

    public function testCopy(): void
    {
        Storage::write('original.txt', 'copy test');
        Storage::copy('original.txt', 'copy.txt');
        $this->assertTrue(Storage::exists('copy.txt'));
        $this->assertSame('copy test', Storage::get('copy.txt'));
    }

    public function testMove(): void
    {
        Storage::write('source.txt', 'move test');
        Storage::move('source.txt', 'dest.txt');
        $this->assertFalse(Storage::exists('source.txt'));
        $this->assertTrue(Storage::exists('dest.txt'));
        $this->assertSame('move test', Storage::get('dest.txt'));
    }

    public function testSize(): void
    {
        Storage::write('size.txt', '12345');
        $this->assertSame(5, Storage::size('size.txt'));
    }

    public function testMimeType(): void
    {
        Storage::write('test.json', '{"key":"value"}');
        $this->assertStringContainsString('json', Storage::mimeType('test.json'));
    }

    public function testLastModified(): void
    {
        Storage::write('modified.txt', 'fresh');
        $ts = Storage::lastModified('modified.txt');
        $this->assertIsInt($ts);
        $this->assertGreaterThan(0, $ts);
    }

    public function testUrl(): void
    {
        Storage::write('public/file.jpg', 'data');
        $this->assertSame('/storage/public/file.jpg', Storage::url('public/file.jpg'));
    }

    public function testStoreWithDirectory(): void
    {
        Storage::write('sub/deep/file.txt', 'nested');
        $this->assertTrue(Storage::exists('sub/deep/file.txt'));
        $this->assertSame('nested', Storage::get('sub/deep/file.txt'));
    }

    public function testWriteStream(): void
    {
        $stream = fopen('php://memory', 'rb+');
        fwrite($stream, 'stream content');
        rewind($stream);
        Storage::writeStream('stream.txt', $stream);
        fclose($stream);
        $this->assertSame('stream content', Storage::get('stream.txt'));
    }

    public function testReadStream(): void
    {
        Storage::write('readable.txt', 'read stream');
        $stream = Storage::readStream('readable.txt');
        $this->assertIsResource($stream);
        $this->assertSame('read stream', stream_get_contents($stream));
        fclose($stream);
    }

    public function testVisibility(): void
    {
        Storage::write('vis.txt', 'content', 'public');
        $this->assertSame('public', Storage::visibility('vis.txt'));
        Storage::setVisibility('vis.txt', 'private');
        $this->assertSame('private', Storage::visibility('vis.txt'));
    }

    public function testDiskSelection(): void
    {
        StorageManager::configure([
            'local' => [
                'driver' => 'local',
                'root' => $this->tempDir,
                'url' => '/storage',
            ],
            'other' => [
                'driver' => 'local',
                'root' => $this->tempDir . '/alt',
                'url' => '/alt',
            ],
        ], 'local');

        Storage::disk('other')->write('disk.txt', 'other disk');
        $this->assertTrue(Storage::disk('other')->exists('disk.txt'));
        $this->assertFalse(Storage::exists('disk.txt'));
    }

    public function testNonexistentDiskThrows(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        Storage::disk('nope');
    }
}

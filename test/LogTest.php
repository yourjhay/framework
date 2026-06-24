<?php

namespace Simple\Tests;

use PHPUnit\Framework\TestCase;
use Simple\Log;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;

class LogTest extends TestCase
{
    private string $logFile;
    private Logger $monolog;

    protected function setUp(): void
    {
        $this->logFile = sys_get_temp_dir() . '/simply_log_test_' . uniqid() . '.log';
        $this->monolog = new Logger('test');
        $this->monolog->pushHandler(new StreamHandler($this->logFile, Logger::DEBUG));
        Log::setLogger($this->monolog);
    }

    protected function tearDown(): void
    {
        @unlink($this->logFile);
    }

    public function testInfoLogsMessage(): void
    {
        Log::info('info message', ['key' => 'val']);
        $contents = file_get_contents($this->logFile);
        $this->assertStringContainsString('info message', $contents);
    }

    public function testErrorLogsMessage(): void
    {
        Log::error('error message');
        $contents = file_get_contents($this->logFile);
        $this->assertStringContainsString('error message', $contents);
    }

    public function testDebugLogsMessage(): void
    {
        Log::debug('debug message');
        $contents = file_get_contents($this->logFile);
        $this->assertStringContainsString('debug message', $contents);
    }

    public function testWarningLogsMessage(): void
    {
        Log::warning('warning message');
        $contents = file_get_contents($this->logFile);
        $this->assertStringContainsString('warning message', $contents);
    }

    public function testContextIsLogged(): void
    {
        Log::info('with context', ['user_id' => 42]);
        $contents = file_get_contents($this->logFile);
        $this->assertStringContainsString('with context', $contents);
        $this->assertStringContainsString('user_id', $contents);
        $this->assertStringContainsString('42', $contents);
    }

    public function testWithContextMergesIntoAllCalls(): void
    {
        Log::withContext(['request_id' => 'abc']);
        Log::info('first');
        Log::info('second');
        $contents = file_get_contents($this->logFile);
        $this->assertStringContainsString('request_id', $contents);
    }

    public function testLogMethodDynamicLevel(): void
    {
        Log::log('notice', 'dynamic level');
        $contents = file_get_contents($this->logFile);
        $this->assertStringContainsString('dynamic level', $contents);
    }

    public function testNoOpsWhenNoLoggerSet(): void
    {
        Log::setLogger(null);
        Log::info('should not crash');
        $this->assertTrue(true);
    }
}

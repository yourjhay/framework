<?php

namespace Simple\Tests;

use PHPUnit\Framework\TestCase;
use Simple\Config;
use Simple\Log\LogManager;
use Monolog\Handler\StreamHandler;
use Monolog\Handler\RotatingFileHandler;
use Monolog\Handler\SyslogHandler;
use Monolog\Handler\ErrorLogHandler;

class LogManagerTest extends TestCase
{
    private string $logDir;

    protected function setUp(): void
    {
        $this->logDir = sys_get_temp_dir() . '/simply_logs_' . uniqid();
        mkdir($this->logDir, 0777, true);
        Config::clear();
        Config::set('app.project_root', $this->logDir);
    }

    protected function tearDown(): void
    {
        Config::clear();
        array_map('unlink', glob($this->logDir . '/*.log'));
        rmdir($this->logDir);
    }

    public function testMakeReturnsConfiguredLogger(): void
    {
        $logger = LogManager::make([
            'name'    => 'test',
            'handler' => 'single',
            'path'    => $this->logDir . '/test.log',
            'level'   => 'debug',
            'days'    => 14,
        ]);
        $this->assertInstanceOf(\Monolog\Logger::class, $logger);
    }

    public function testSingleHandler(): void
    {
        $logger = LogManager::make([
            'name'    => 'test',
            'handler' => 'single',
            'path'    => $this->logDir . '/test.log',
            'level'   => 'debug',
            'days'    => 14,
        ]);
        $handlers = $logger->getHandlers();
        $this->assertCount(1, $handlers);
        $this->assertInstanceOf(StreamHandler::class, $handlers[0]);
    }

    public function testDailyHandler(): void
    {
        $logger = LogManager::make([
            'name'    => 'test',
            'handler' => 'daily',
            'path'    => $this->logDir . '/test.log',
            'level'   => 'debug',
            'days'    => 14,
        ]);
        $handlers = $logger->getHandlers();
        $this->assertCount(1, $handlers);
        $this->assertInstanceOf(RotatingFileHandler::class, $handlers[0]);
    }

    public function testSyslogHandler(): void
    {
        $logger = LogManager::make([
            'name'    => 'test',
            'handler' => 'syslog',
            'path'    => $this->logDir . '/test.log',
            'level'   => 'warning',
            'days'    => 14,
        ]);
        $handlers = $logger->getHandlers();
        $this->assertInstanceOf(SyslogHandler::class, $handlers[0]);
    }

    public function testErrorlogHandler(): void
    {
        $logger = LogManager::make([
            'name'    => 'test',
            'handler' => 'errorlog',
            'path'    => $this->logDir . '/test.log',
            'level'   => 'debug',
            'days'    => 14,
        ]);
        $handlers = $logger->getHandlers();
        $this->assertInstanceOf(ErrorLogHandler::class, $handlers[0]);
    }

    public function testStderrHandler(): void
    {
        $logger = LogManager::make([
            'name'    => 'test',
            'handler' => 'stderr',
            'path'    => $this->logDir . '/test.log',
            'level'   => 'debug',
            'days'    => 14,
        ]);
        $handlers = $logger->getHandlers();
        $this->assertInstanceOf(StreamHandler::class, $handlers[0]);
    }

    public function testLogsToFile(): void
    {
        $logFile = $this->logDir . '/test.log';
        $logger = LogManager::make([
            'name'    => 'test',
            'handler' => 'single',
            'path'    => $logFile,
            'level'   => 'debug',
            'days'    => 14,
        ]);
        $logger->info('test message');
        $contents = file_get_contents($logFile);
        $this->assertStringContainsString('test message', $contents);
    }

    public function testResolvesRelativePath(): void
    {
        $logger = LogManager::make([
            'name'    => 'test',
            'handler' => 'single',
            'path'    => './storage/logs/app.log',
            'level'   => 'debug',
            'days'    => 14,
        ]);
        $handlers = $logger->getHandlers();
        $url = $handlers[0]->getUrl();
        $this->assertStringStartsWith($this->logDir, $url);
        $this->assertStringContainsString('storage/logs/app.log', $url);
    }

    public function testDefaultHandler(): void
    {
        $logger = LogManager::make([
            'name'    => 'test',
            'handler' => 'unknown',
            'path'    => $this->logDir . '/test.log',
            'level'   => 'debug',
            'days'    => 14,
        ]);
        $handlers = $logger->getHandlers();
        $this->assertInstanceOf(StreamHandler::class, $handlers[0]);
    }

    public function testLevelFiltering(): void
    {
        $logFile = $this->logDir . '/test.log';
        $logger = LogManager::make([
            'name'    => 'test',
            'handler' => 'single',
            'path'    => $logFile,
            'level'   => 'warning',
            'days'    => 14,
        ]);
        $logger->debug('should not appear');
        $logger->warning('should appear');
        $contents = file_get_contents($logFile);
        $this->assertStringNotContainsString('should not appear', $contents);
        $this->assertStringContainsString('should appear', $contents);
    }
}

<?php

namespace Simple\Tests;

use PHPUnit\Framework\TestCase;
use Simple\Config;

class ConfigTest extends TestCase
{
    protected function setUp(): void
    {
        Config::clear();
    }

    protected function tearDown(): void
    {
        Config::clear();
    }

    public function testSetAndGet(): void
    {
        Config::set('app.name', 'Simply PHP');
        $this->assertEquals('Simply PHP', Config::get('app.name'));
    }

    public function testGetWithDefault(): void
    {
        $this->assertEquals('default', Config::get('nonexistent.key', 'default'));
    }

    public function testHas(): void
    {
        Config::set('foo.bar', 'baz');
        $this->assertTrue(Config::has('foo.bar'));
        $this->assertFalse(Config::has('foo.nonexistent'));
    }

    public function testDotNotationSetsNestedArray(): void
    {
        Config::set('database.server', 'localhost');
        Config::set('database.name', 'testdb');
        $db = Config::get('database');
        $this->assertEquals(['server' => 'localhost', 'name' => 'testdb'], $db);
    }

    public function testLoadFromFiles(): void
    {
        $tmpDir = sys_get_temp_dir() . '/config-test-' . getmypid();
        mkdir($tmpDir, 0755, true);
        file_put_contents($tmpDir . '/app.php', '<?php return ["name" => "TestApp"];');
        file_put_contents($tmpDir . '/database.php', '<?php return ["host" => "localhost"];');

        Config::load($tmpDir);
        $this->assertEquals('TestApp', Config::get('app.name'));
        $this->assertEquals('localhost', Config::get('database.host'));

        array_map('unlink', glob($tmpDir . '/*'));
        rmdir($tmpDir);
    }

    public function testEnvReturnsDefaultWhenNotSet(): void
    {
        putenv('TEST_VAR');
        $_ENV['TEST_VAR'] = null;
        $this->assertEquals('default', env('TEST_VAR', 'default'));
    }

    public function testEnvCastsBooleanStrings(): void
    {
        putenv('TEST_TRUE=true');
        putenv('TEST_FALSE=false');
        $this->assertTrue(env('TEST_TRUE'));
        $this->assertFalse(env('TEST_FALSE'));
    }

    public function testEnvCastsNullString(): void
    {
        putenv('TEST_NULL=null');
        $this->assertNull(env('TEST_NULL'));
    }

    public function testConfigHelperReturnsConfigValue(): void
    {
        Config::set('app.test_key', 'helper_value');
        $this->assertEquals('helper_value', config('app.test_key'));
    }

    public function testConfigHelperReturnsDefault(): void
    {
        $this->assertEquals('fallback', config('nonexistent.key', 'fallback'));
    }
}

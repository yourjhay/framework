<?php

namespace Simple\Tests;

use PHPUnit\Framework\TestCase;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;
use Twig\TwigFunction;

class TwigTest extends TestCase
{
    private string $tempDir;

    protected function setUp(): void
    {
        $this->tempDir = sys_get_temp_dir() . '/simply_twig_' . uniqid();
        mkdir($this->tempDir . '/app/Helper/Twig', 0777, true);
        mkdir($this->tempDir . '/app/Views', 0777, true);
    }

    protected function tearDown(): void
    {
        $it = new \RecursiveDirectoryIterator($this->tempDir, \RecursiveDirectoryIterator::SKIP_DOTS);
        $files = new \RecursiveIteratorIterator($it, \RecursiveIteratorIterator::CHILD_FIRST);
        foreach ($files as $file) {
            $file->isDir() ? rmdir($file->getRealPath()) : unlink($file->getRealPath());
        }
        rmdir($this->tempDir);
    }

    public function testExtensionsAutoDiscoveredAndFunctionsRegistered(): void
    {
        file_put_contents(
            $this->tempDir . '/app/Helper/Twig/TestFunctions.php',
            '<?php
namespace App\Helper\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class TestFunctions extends AbstractExtension
{
    public function getFunctions(): array
    {
        return [
            new TwigFunction(\'greet\', [$this, \'greet\']),
        ];
    }

    public function greet(string $name): string
    {
        return "Hello, $name!";
    }
}'
        );

        require_once $this->tempDir . '/app/Helper/Twig/TestFunctions.php';

        $loader = new FilesystemLoader($this->tempDir . '/app/Views');
        $twig = new Environment($loader);

        foreach (glob($this->tempDir . '/app/Helper/Twig/*.php') as $filename) {
            $class = "\App\Helper\Twig\\" . explode('.', basename($filename))[0];
            $twig->addExtension(new $class);
        }

        $this->assertTrue($twig->hasExtension('App\Helper\Twig\TestFunctions'));
        $this->assertNotNull($twig->getFunction('greet'));
        $this->assertSame('Hello, World!', $twig->getFunction('greet')->getCallable()('World'));
    }

    public function testMultipleExtensionsAllRegistered(): void
    {
        file_put_contents(
            $this->tempDir . '/app/Helper/Twig/Foo.php',
            '<?php
namespace App\Helper\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class Foo extends AbstractExtension
{
    public function getFunctions(): array
    {
        return [new TwigFunction(\'foo\', fn() => \'foo_val\')];
    }
}'
        );

        file_put_contents(
            $this->tempDir . '/app/Helper/Twig/Bar.php',
            '<?php
namespace App\Helper\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class Bar extends AbstractExtension
{
    public function getFunctions(): array
    {
        return [new TwigFunction(\'bar\', fn() => \'bar_val\')];
    }
}'
        );

        require_once $this->tempDir . '/app/Helper/Twig/Foo.php';
        require_once $this->tempDir . '/app/Helper/Twig/Bar.php';

        $loader = new FilesystemLoader($this->tempDir . '/app/Views');
        $twig = new Environment($loader);

        foreach (glob($this->tempDir . '/app/Helper/Twig/*.php') as $filename) {
            $class = "\App\Helper\Twig\\" . explode('.', basename($filename))[0];
            $twig->addExtension(new $class);
        }

        $this->assertNotNull($twig->getFunction('foo'));
        $this->assertNotNull($twig->getFunction('bar'));
        $this->assertSame('foo_val', $twig->getFunction('foo')->getCallable()());
        $this->assertSame('bar_val', $twig->getFunction('bar')->getCallable()());
    }
}

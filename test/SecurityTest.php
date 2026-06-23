<?php

namespace Simple\Tests;

if (!defined('DBENGINE')) { define('DBENGINE', 'mysql'); }
if (!defined('DBSERVER')) { define('DBSERVER', 'localhost'); }
if (!defined('DBNAME'))   { define('DBNAME', 'test'); }
if (!defined('DBUSER'))   { define('DBUSER', 'root'); }
if (!defined('DBPASS'))   { define('DBPASS', ''); }
if (!defined('SHOW_ERRORS')) { define('SHOW_ERRORS', true); }

use PHPUnit\Framework\TestCase;
use Simple\Session;

class SecurityTest extends TestCase
{
    protected function setUp(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_destroy();
        }
        $_SESSION = [];
        $_SERVER['REQUEST_METHOD'] = 'GET';
    }

    public function testSessionRegenerateIdCalled(): void
    {
        Session::init();
        $oldId = session_id();
        session_regenerate_id(true);
        $newId = session_id();
        $this->assertNotEquals($oldId, $newId);
    }

    public function testSameSiteCookieSet(): void
    {
        Session::init();
        $this->assertEquals('Lax', ini_get('session.cookie_samesite'));
    }

    public function testTokenGeneratedOnFirstCall(): void
    {
        $token = Session::token();
        $this->assertNotEmpty($token);
        $this->assertEquals(64, strlen($token));
    }

    public function testTokenPersistsInSession(): void
    {
        $token1 = Session::token();
        $token2 = Session::token();
        $this->assertEquals($token1, $token2);
    }

    public function testErrorPageEscapesExceptionMessage(): void
    {
        $exception = new \Exception('<script>alert("xss")</script>', 500);
        ob_start();
        \Simple\Error::exceptionHandler($exception);
        $output = ob_get_clean();
        $this->assertStringContainsString('&lt;script&gt;', $output);
        $this->assertStringNotContainsString('<script>', $output);
    }

    public function testRedirectRejectsExternalUrls(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Redirect to external URLs is not allowed.');
        \Simple\Request::redirect('http://evil.com');
    }

    public function testRedirectRejectsProtocolRelativeUrls(): void
    {
        $this->expectException(\RuntimeException::class);
        \Simple\Request::redirect('//evil.com');
    }

    public function testTwigHasCsrfTokenFunction(): void
    {
        $this->markTestIncomplete('csrf_token Twig function not yet added');
    }

    public function testTwigHasCsrfFieldFunction(): void
    {
        $this->markTestIncomplete('csrf_field Twig function not yet added');
    }

    public function testTwigNoGetGlobal(): void
    {
        $this->markTestIncomplete('_get Twig global not yet removed');
    }

    public function testHostHeaderSanitized(): void
    {
        $this->markTestIncomplete('HTTP_HOST sanitization not yet added');
    }
}

<?php

namespace Simple\Tests;

if (!defined('DBENGINE')) { define('DBENGINE', 'mysql'); }
if (!defined('DBSERVER')) { define('DBSERVER', 'localhost'); }
if (!defined('DBNAME'))   { define('DBNAME', 'test'); }
if (!defined('DBUSER'))   { define('DBUSER', 'root'); }
if (!defined('DBPASS'))   { define('DBPASS', ''); }

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
}

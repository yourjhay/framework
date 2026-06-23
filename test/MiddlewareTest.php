<?php

namespace Simple\Tests;

if (!defined('DBENGINE')) { define('DBENGINE', 'mysql'); }
if (!defined('DBSERVER')) { define('DBSERVER', 'localhost'); }
if (!defined('DBNAME'))   { define('DBNAME', 'test'); }
if (!defined('DBUSER'))   { define('DBUSER', 'root'); }
if (!defined('DBPASS'))   { define('DBPASS', ''); }

use PHPUnit\Framework\TestCase;
use Simple\Middleware\Pipeline;
use Simple\Middleware\Middleware;
use Simple\Request;

class MiddlewareTest extends TestCase
{
    protected function setUp(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_destroy();
        }
        $_SESSION = [];
        $_SERVER['REQUEST_METHOD'] = 'GET';
    }

    public function testPipelineWithNoMiddlewareExecutesDestination(): void
    {
        $pipeline = new Pipeline([]);
        $executed = false;
        $pipeline->send(new Request([], [], [], [], [], $_SERVER), function($req) use (&$executed) {
            $executed = true;
        });
        $this->assertTrue($executed);
    }

    public function testPipelineExecutesMiddlewareInOrder(): void
    {
        $order = [];
        $mw1 = new class implements \Simple\Middleware\Middleware {
            public function handle(Request $request, \Closure $next) {
                $GLOBALS['test_order'][] = 'first';
                return $next($request);
            }
        };
        $mw2 = new class implements \Simple\Middleware\Middleware {
            public function handle(Request $request, \Closure $next) {
                $GLOBALS['test_order'][] = 'second';
                return $next($request);
            }
        };

        $pipeline = new Pipeline([get_class($mw1), get_class($mw2)]);
        $pipeline->send(new Request([], [], [], [], [], $_SERVER), function($req) {
            $GLOBALS['test_order'][] = 'destination';
        });

        $this->assertEquals(['first', 'second', 'destination'], $GLOBALS['test_order']);
        unset($GLOBALS['test_order']);
    }

    public function testPipelineShortCircuits(): void
    {
        $blocker = new class implements \Simple\Middleware\Middleware {
            public function handle(Request $request, \Closure $next) {
                return 'blocked';
            }
        };

        $pipeline = new Pipeline([get_class($blocker)]);
        $result = $pipeline->send(new Request([], [], [], [], [], $_SERVER), function($req) {
            return 'passed';
        });

        $this->assertEquals('blocked', $result);
    }

    public function testMiddlewareAliasResolution(): void
    {
        \Simple\Routing\Router::middlewareAlias('test-mw', get_class(new class implements \Simple\Middleware\Middleware {
            public function handle(Request $request, \Closure $next) { return $next($request); }
        }));
        $this->assertTrue(true); // no exception thrown
    }

    public function testFluentMiddleware(): void
    {
        $this->markTestIncomplete('Router fluent middleware() not yet implemented');
    }

    public function testGroupMiddleware(): void
    {
        $this->markTestIncomplete('Router group middleware not yet implemented');
    }

    public function testCsrfSkipsGetRequests(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $request = new Request([], [], [], [], [], $_SERVER);
        $csrf = new \Simple\Middleware\Csrf;
        $passed = false;
        $csrf->handle($request, function($req) use (&$passed) {
            $passed = true;
        });
        $this->assertTrue($passed);
    }

    public function testCsrfValidTokenPasses(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $token = bin2hex(random_bytes(32));
        $_SESSION['_token'] = $token;
        $_POST['_token'] = $token;
        $request = new Request([], $_POST, [], [], [], $_SERVER);
        $csrf = new \Simple\Middleware\Csrf;
        $passed = false;
        $csrf->handle($request, function($req) use (&$passed) {
            $passed = true;
        });
        $this->assertTrue($passed);
    }

    public function testCsrfInvalidTokenThrows(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('CSRF token mismatch');
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SESSION['_token'] = bin2hex(random_bytes(32));
        $_POST['_token'] = 'wrong-token';
        $request = new Request([], $_POST, [], [], [], $_SERVER);
        $csrf = new \Simple\Middleware\Csrf;
        $csrf->handle($request, function($req) {});
    }

    public function testCsrfSkipsHeadAndOptions(): void
    {
        foreach (['HEAD', 'OPTIONS'] as $method) {
            $_SERVER['REQUEST_METHOD'] = $method;
            $request = new Request([], [], [], [], [], $_SERVER);
            $csrf = new \Simple\Middleware\Csrf;
            $passed = false;
            $csrf->handle($request, function($req) use (&$passed) {
                $passed = true;
            });
            $this->assertTrue($passed);
        }
    }

    public function testCsrfReadsHeaderToken(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $token = bin2hex(random_bytes(32));
        $_SESSION['_token'] = $token;
        $_SERVER['HTTP_X_CSRF_TOKEN'] = $token;
        $_POST = [];
        $request = new Request([], [], [], [], [], $_SERVER);
        $csrf = new \Simple\Middleware\Csrf;
        $passed = false;
        $csrf->handle($request, function($req) use (&$passed) {
            $passed = true;
        });
        $this->assertTrue($passed);
    }

    public function testSecurityHeadersSet(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $request = new Request([], [], [], [], [], $_SERVER);
        $headers = new \Simple\Middleware\SecurityHeaders;
        $passed = false;
        $headers->handle($request, function($req) use (&$passed) {
            $passed = true;
        });
        $this->assertTrue($passed);
    }

    public function testAuthWithUserPasses(): void
    {
        $_SESSION['user'] = json_encode(['id' => 1, 'email' => 'test@test.com']);
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $request = new Request([], [], [], [], [], $_SERVER);
        $auth = new \Simple\Middleware\Auth;
        $passed = false;
        $auth->handle($request, function($req) use (&$passed) {
            $passed = true;
        });
        $this->assertTrue($passed);
    }

    public function testAuthWithoutUserThrows(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Unauthenticated');
        $_SESSION = [];
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $request = new Request([], [], [], [], [], $_SERVER);
        $auth = new \Simple\Middleware\Auth;
        $auth->handle($request, function($req) {});
    }
}

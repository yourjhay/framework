<?php

namespace Simple\Tests;

if (!defined('DBENGINE')) { define('DBENGINE', 'mysql'); }
if (!defined('DBSERVER')) { define('DBSERVER', 'localhost'); }
if (!defined('DBNAME'))   { define('DBNAME', 'test'); }
if (!defined('DBUSER'))   { define('DBUSER', 'root'); }
if (!defined('DBPASS'))   { define('DBPASS', ''); }

use PHPUnit\Framework\TestCase;
use Simple\Middleware\Pipeline;
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
}

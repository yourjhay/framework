<?php

namespace App\Controllers;

use Simple\Routing\BaseController;
use Simple\Request;
use Simple\Validation\FormRequest;
use Simple\Validation\ValidationException;

class StubController extends BaseController
{
    public function indexAction() {}
}

class ParamController extends BaseController
{
    public function show($id)
    {
        echo "id:$id";
    }

    public function showWithDefault($id, $sort = 'asc')
    {
        echo "id:$id,sort:$sort";
    }

    public function requestInjection(Request $request)
    {
        echo $request ? 'request:injected' : 'request:null';
    }

    public function mixedParams(Request $request, $id)
    {
        echo "id:$id";
    }

    public function optionalParam($page = 1)
    {
        echo "page:$page";
    }
}

class ServiceWithoutDeps
{
    public function greet(): string
    {
        return 'hello from service';
    }
}

class ServiceWithDeps
{
    public ServiceWithoutDeps $service;

    public function __construct(ServiceWithoutDeps $service)
    {
        $this->service = $service;
    }
}

class AutoWireController extends BaseController
{
    public function autoService(ServiceWithoutDeps $service)
    {
        echo $service->greet();
    }

    public function nestedService(ServiceWithDeps $service)
    {
        echo $service->service->greet();
    }
}

class CustomFormRequest extends Request
{
    public function validated(): array
    {
        return ['name' => 'test'];
    }
}

class FormRequestController extends BaseController
{
    public function store(CustomFormRequest $request)
    {
        echo json_encode($request->validated());
    }
}

class TestFormRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => 'required|alpha',
        ];
    }
}

class FormRequestIntegrationController extends BaseController
{
    public function store(TestFormRequest $request)
    {
        echo 'validated';
    }
}

namespace Simple\Tests;

\Simple\Config::set('database.engine', 'mysql');
\Simple\Config::set('database.server', 'localhost');
\Simple\Config::set('database.name', 'test');
\Simple\Config::set('database.user', 'root');
\Simple\Config::set('database.pass', '');

use PHPUnit\Framework\TestCase;
use Simple\Middleware\Middleware;
use Simple\Request;
use Simple\Routing\BaseRouter;
use Simple\Routing\ControllerDispatcher;
use Simple\Routing\Router;
use Simple\Validation\ValidationException;
use Closure;

class TestMiddleware implements Middleware
{
    public static bool $called = false;

    public function handle(Request $request, Closure $next)
    {
        static::$called = true;
        return $next($request);
    }

    public static function reset(): void
    {
        static::$called = false;
    }
}

class BlockingMiddleware implements Middleware
{
    public function handle(Request $request, Closure $next)
    {
        throw new \RuntimeException('blocked', 403);
    }
}

class RouterTest extends TestCase
{
    protected function setUp(): void
    {
        self::resetRouterState();
        $_SERVER['REQUEST_METHOD'] = 'GET';
        unset($_POST['_method']);
    }

    private static function resetRouterState(): void
    {
        $ref = new \ReflectionClass(BaseRouter::class);
        $defaults = [
            'routes' => [],
            'params' => [],
            'compiled_routes' => [],
            'currentGroupPrefix' => '',
            'globalMiddleware' => [],
            'middlewareAliases' => [],
            'currentGroupMiddleware' => [],
            'current_route' => '',
            'current_param' => [],
            'raw_current_route' => '',
        ];
        foreach ($defaults as $prop => $val) {
            $p = $ref->getProperty($prop);
            $p->setAccessible(true);
            $p->setValue(null, $val);
        }
    }

    protected function tearDown(): void
    {
        self::resetRouterState();
        ControllerDispatcher::reset();
    }

    // -----------------------------------------------------------------------
    // Static routes
    // -----------------------------------------------------------------------

    public function testStaticRouteMatches(): void
    {
        Router::get('/about', ['controller' => 'Page', 'action' => 'index']);
        $this->assertTrue(BaseRouter::match('/about'));
    }

    public function testStaticRouteDoesNotMatchDifferentPath(): void
    {
        Router::get('/about', ['controller' => 'Page', 'action' => 'index']);
        $this->assertFalse(BaseRouter::match('/contact'));
    }

    public function testRootRouteMatches(): void
    {
        Router::get('/', ['controller' => 'Home', 'action' => 'index']);
        $this->assertTrue(BaseRouter::match('/'));
    }

    // -----------------------------------------------------------------------
    // Parameterized routes
    // -----------------------------------------------------------------------

    public function testSimpleParamExtractsValue(): void
    {
        Router::get('/user/{id}', ['controller' => 'User', 'action' => 'show']);
        $this->assertTrue(BaseRouter::match('/user/42'));
        $params = BaseRouter::getParams();
        $this->assertSame('42', $params['id']);
    }

    public function testSimpleParamWithHyphenAndUnderscore(): void
    {
        Router::get('/blog/{slug}', ['controller' => 'Post', 'action' => 'show']);
        $this->assertTrue(BaseRouter::match('/blog/hello-world_123'));
        $params = BaseRouter::getParams();
        $this->assertSame('hello-world_123', $params['slug']);
    }

    public function testCustomRegexParamMatches(): void
    {
        Router::get('/post/{id:\d+}', ['controller' => 'Post', 'action' => 'show']);
        $this->assertTrue(BaseRouter::match('/post/42'));
        $params = BaseRouter::getParams();
        $this->assertSame('42', $params['id']);
    }

    public function testCustomRegexParamRejectsNonMatch(): void
    {
        Router::get('/post/{id:\d+}', ['controller' => 'Post', 'action' => 'show']);
        $this->assertFalse(BaseRouter::match('/post/abc'));
    }

    public function testMultipleParams(): void
    {
        Router::get('/post/{year}/{slug}', ['controller' => 'Post', 'action' => 'show']);
        $this->assertTrue(BaseRouter::match('/post/2024/hello-world'));
        $params = BaseRouter::getParams();
        $this->assertSame('2024', $params['year']);
        $this->assertSame('hello-world', $params['slug']);
    }

    public function testCustomRegexWithMultipleParams(): void
    {
        Router::get('/post/{id:\d+}-{slug}', ['controller' => 'Post', 'action' => 'show']);
        $this->assertTrue(BaseRouter::match('/post/42-hello'));
        $this->assertFalse(BaseRouter::match('/post/abc-hello'));
    }

    public function testParamNamedController(): void
    {
        Router::get('/{controller}', ['controller' => 'Page', 'action' => 'index']);
        $this->assertTrue(BaseRouter::match('/User'));
        $params = BaseRouter::getParams();
        $this->assertSame('User', $params['controller']);
    }

    // -----------------------------------------------------------------------
    // Optional parameters
    // -----------------------------------------------------------------------

    public function testOptionalParamPresent(): void
    {
        Router::get('/blog/{slug?}', ['controller' => 'Blog', 'action' => 'index']);
        $this->assertTrue(BaseRouter::match('/blog/my-post'));
        $params = BaseRouter::getParams();
        $this->assertSame('my-post', $params['slug']);
    }

    public function testOptionalParamAbsent(): void
    {
        Router::get('/blog/{slug?}', ['controller' => 'Blog', 'action' => 'index']);
        $this->assertTrue(BaseRouter::match('/blog'));
        $params = BaseRouter::getParams();
        $this->assertArrayNotHasKey('slug', $params);
    }

    public function testOptionalParamWithTrailingSlash(): void
    {
        Router::get('/blog/{slug?}', ['controller' => 'Blog', 'action' => 'index']);
        $this->assertTrue(BaseRouter::match('/blog'));
    }

    public function testOptionalRegexParamPresent(): void
    {
        Router::get('/page/{id?:\d+}', ['controller' => 'Page', 'action' => 'show']);
        $this->assertTrue(BaseRouter::match('/page/99'));
        $params = BaseRouter::getParams();
        $this->assertSame('99', $params['id']);
    }

    public function testOptionalRegexParamAbsent(): void
    {
        Router::get('/page/{id?:\d+}', ['controller' => 'Page', 'action' => 'show']);
        $this->assertTrue(BaseRouter::match('/page'));
        $params = BaseRouter::getParams();
        $this->assertArrayNotHasKey('id', $params);
    }

    public function testOptionalRegexParamRejectsMismatch(): void
    {
        Router::get('/page/{id?:\d+}', ['controller' => 'Page', 'action' => 'show']);
        $this->assertFalse(BaseRouter::match('/page/abc'));
    }

    public function testMultipleOptionalParamsAllPresent(): void
    {
        Router::get('/filter/{category?}/{page?}', ['controller' => 'Filter', 'action' => 'index']);
        $this->assertTrue(BaseRouter::match('/filter/tech/2'));
        $params = BaseRouter::getParams();
        $this->assertSame('tech', $params['category']);
        $this->assertSame('2', $params['page']);
    }

    public function testMultipleOptionalParamsFirstOnly(): void
    {
        Router::get('/filter/{category?}/{page?}', ['controller' => 'Filter', 'action' => 'index']);
        $this->assertTrue(BaseRouter::match('/filter/tech'));
        $params = BaseRouter::getParams();
        $this->assertSame('tech', $params['category']);
        $this->assertArrayNotHasKey('page', $params);
    }

    public function testMultipleOptionalParamsNone(): void
    {
        Router::get('/filter/{category?}/{page?}', ['controller' => 'Filter', 'action' => 'index']);
        $this->assertTrue(BaseRouter::match('/filter'));
        $params = BaseRouter::getParams();
        $this->assertArrayNotHasKey('category', $params);
        $this->assertArrayNotHasKey('page', $params);
    }

    public function testMixedRequiredAndOptionalBothPresent(): void
    {
        Router::get('/blog/{category}/{slug?}', ['controller' => 'Blog', 'action' => 'show']);
        $this->assertTrue(BaseRouter::match('/blog/tech/my-post'));
        $params = BaseRouter::getParams();
        $this->assertSame('tech', $params['category']);
        $this->assertSame('my-post', $params['slug']);
    }

    public function testMixedRequiredAndOptionalAbsent(): void
    {
        Router::get('/blog/{category}/{slug?}', ['controller' => 'Blog', 'action' => 'show']);
        $this->assertTrue(BaseRouter::match('/blog/tech'));
        $params = BaseRouter::getParams();
        $this->assertSame('tech', $params['category']);
        $this->assertArrayNotHasKey('slug', $params);
    }

    public function testMixedRequiredMissingCausesNoMatch(): void
    {
        Router::get('/blog/{category}/{slug?}', ['controller' => 'Blog', 'action' => 'show']);
        $this->assertFalse(BaseRouter::match('/blog'));
    }

    public function testOptionalWithFollowingStatic(): void
    {
        Router::get('/post/{slug?}/comments', ['controller' => 'Post', 'action' => 'comments']);
        $this->assertTrue(BaseRouter::match('/post/hello/comments'));
        $params = BaseRouter::getParams();
        $this->assertSame('hello', $params['slug']);
    }

    public function testOptionalWithFollowingStaticAbsent(): void
    {
        Router::get('/post/{slug?}/comments', ['controller' => 'Post', 'action' => 'comments']);
        $this->assertTrue(BaseRouter::match('/post/comments'));
        $params = BaseRouter::getParams();
        $this->assertArrayNotHasKey('slug', $params);
    }

    // -----------------------------------------------------------------------
    // Catch-all
    // -----------------------------------------------------------------------

    public function testCatchAllMatchesMultiSegment(): void
    {
        Router::get('/path/{:all?}', ['controller' => 'Catch', 'action' => 'all']);
        $this->assertTrue(BaseRouter::match('/path/a/b/c'));
    }

    public function testCatchAllMatchesSingleSegment(): void
    {
        Router::get('/path/{:all?}', ['controller' => 'Catch', 'action' => 'all']);
        $this->assertTrue(BaseRouter::match('/path/foo'));
    }

    public function testCatchAllMatchesNoExtraPath(): void
    {
        Router::get('/path/{:all?}', ['controller' => 'Catch', 'action' => 'all']);
        $this->assertTrue(BaseRouter::match('/path'));
    }

    // -----------------------------------------------------------------------
    // Route aliases
    // -----------------------------------------------------------------------

    public function testAliasStoresCompiledRoute(): void
    {
        Router::get('/about', ['controller' => 'Page', 'action' => 'index'])
            ->alias('about');
        $compiled = Router::compiledRoutes();
        $this->assertArrayHasKey('about', $compiled);
    }

    public function testMultipleAliases(): void
    {
        Router::get('/', ['controller' => 'Home', 'action' => 'index'])
            ->alias('home');
        Router::get('/contact', ['controller' => 'Page', 'action' => 'contact'])
            ->alias('contact');
        $compiled = Router::compiledRoutes();
        $this->assertCount(2, $compiled);
        $this->assertArrayHasKey('home', $compiled);
        $this->assertArrayHasKey('contact', $compiled);
    }

    public function testAliasStoresRawUrl(): void
    {
        Router::get('/user/{id}', ['controller' => 'User', 'action' => 'show'])
            ->alias('user.show');
        $compiled = Router::compiledRoutes();
        $this->assertSame('/user/{id}', $compiled['user.show']['url']);
    }

    // -----------------------------------------------------------------------
    // Route groups
    // -----------------------------------------------------------------------

    public function testGroupAppliesPrefix(): void
    {
        Router::group('admin', function () {
            Router::get('/dashboard', ['controller' => 'Admin', 'action' => 'dashboard']);
        });
        $this->assertTrue(BaseRouter::match('/admin/dashboard'));
        $this->assertFalse(BaseRouter::match('/dashboard'));
    }

    public function testNestedGroups(): void
    {
        Router::group('admin', function () {
            Router::group('blog', function () {
                Router::get('/posts', ['controller' => 'AdminBlog', 'action' => 'posts']);
            });
        });
        $this->assertTrue(BaseRouter::match('/admin/blog/posts'));
        $this->assertFalse(BaseRouter::match('/admin/posts'));
        $this->assertFalse(BaseRouter::match('/blog/posts'));
    }

    public function testGroupDoesNotLeakAfterException(): void
    {
        try {
            Router::group('leaky', function () {
                Router::get('/inside', ['controller' => 'Test', 'action' => 'index']);
                throw new \RuntimeException('rollback');
            });
        } catch (\RuntimeException) {
        }
        Router::get('/outside', ['controller' => 'Test', 'action' => 'outside']);
        $this->assertTrue(BaseRouter::match('/outside'));
        $this->assertFalse(BaseRouter::match('/leaky/outside'));
    }

    // -----------------------------------------------------------------------
    // Middleware — single route
    // -----------------------------------------------------------------------

    public function testSingleRouteWithMiddleware(): void
    {
        TestMiddleware::reset();
        Router::get('/secure', ['controller' => 'Stub', 'action' => 'index'])
            ->middleware(TestMiddleware::class);
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $this->assertTrue(BaseRouter::match('/secure'));
        $params = BaseRouter::getParams();
        $this->assertArrayHasKey('middleware', $params);
        $this->assertContains(TestMiddleware::class, $params['middleware']);
    }

    public function testMiddlewareExecutesOnDispatch(): void
    {
        TestMiddleware::reset();
        Router::get('/middle-exec', ['controller' => 'Stub', 'action' => 'index'])
            ->middleware(TestMiddleware::class);
        $_SERVER['REQUEST_METHOD'] = 'GET';
        ob_start();
        BaseRouter::dispatch('/middle-exec');
        ob_end_clean();
        $this->assertTrue(TestMiddleware::$called);
    }

    public function testBlockingMiddlewareStopsRequest(): void
    {
        Router::get('/blocked', ['controller' => 'Stub', 'action' => 'index'])
            ->middleware(BlockingMiddleware::class);
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionCode(403);
        BaseRouter::dispatch('/blocked');
    }

    // -----------------------------------------------------------------------
    // Middleware — group routes
    // -----------------------------------------------------------------------

    public function testGroupMiddlewarePersistsToRouteParams(): void
    {
        TestMiddleware::reset();
        Router::group(['prefix' => 'admin', 'middleware' => [TestMiddleware::class]], function () {
            Router::get('/dashboard', ['controller' => 'Admin', 'action' => 'dashboard']);
        });
        $this->assertTrue(BaseRouter::match('/admin/dashboard'));
        $params = BaseRouter::getParams();
        $this->assertArrayHasKey('middleware', $params);
        $this->assertContains(TestMiddleware::class, $params['middleware']);
    }

    public function testGroupMiddlewareExecutesOnDispatch(): void
    {
        TestMiddleware::reset();
        Router::group(['prefix' => 'admin', 'middleware' => [TestMiddleware::class]], function () {
            Router::get('/dashboard', ['controller' => 'Stub', 'action' => 'index']);
        });
        $_SERVER['REQUEST_METHOD'] = 'GET';
        ob_start();
        BaseRouter::dispatch('/admin/dashboard');
        ob_end_clean();
        $this->assertTrue(TestMiddleware::$called);
    }

    public function testGroupMiddlewareNotAppliedOutsideGroup(): void
    {
        TestMiddleware::reset();
        Router::group(['prefix' => 'admin', 'middleware' => [TestMiddleware::class]], function () {
            Router::get('/inside', ['controller' => 'Admin', 'action' => 'dashboard']);
        });
        Router::get('/outside', ['controller' => 'Public', 'action' => 'index']);
        $this->assertTrue(BaseRouter::match('/outside'));
        $params = BaseRouter::getParams();
        $this->assertArrayNotHasKey('middleware', $params);
    }

    // -----------------------------------------------------------------------
    // Middleware — route alias inside group
    // -----------------------------------------------------------------------

    public function testAliasedRouteInsideMiddlewareGroup(): void
    {
        TestMiddleware::reset();
        Router::group(['prefix' => 'api', 'middleware' => [TestMiddleware::class]], function () {
            Router::get('/users', 'UserController@index')
                ->alias('api.users');
        });
        $this->assertTrue(BaseRouter::match('/api/users'));
        $params = BaseRouter::getParams();
        $this->assertArrayHasKey('middleware', $params);
        $this->assertContains(TestMiddleware::class, $params['middleware']);
        $this->assertSame('api.users', $params['alias']);
    }

    public function testAliasedRouteWithGroupMiddlewareExecutes(): void
    {
        TestMiddleware::reset();
        Router::group(['prefix' => 'api', 'middleware' => [TestMiddleware::class]], function () {
            Router::get('/users', 'StubController@index')
                ->alias('api.users');
        });
        $_SERVER['REQUEST_METHOD'] = 'GET';
        ob_start();
        BaseRouter::dispatch('/api/users');
        ob_end_clean();
        $this->assertTrue(TestMiddleware::$called);
    }

    // -----------------------------------------------------------------------
    // Middleware — nested groups
    // -----------------------------------------------------------------------

    public function testNestedGroupAccumulatesMiddleware(): void
    {
        TestMiddleware::reset();
        Router::group(['prefix' => 'api', 'middleware' => [TestMiddleware::class]], function () {
            Router::group(['prefix' => 'v1', 'middleware' => [BlockingMiddleware::class]], function () {
                Router::get('/users', 'UserController@index');
            });
        });
        $this->assertTrue(BaseRouter::match('/api/v1/users'));
        $params = BaseRouter::getParams();
        $this->assertArrayHasKey('middleware', $params);
        $this->assertCount(2, $params['middleware']);
    }

    // -----------------------------------------------------------------------
    // Auth routes middleware (CSRF)
    // -----------------------------------------------------------------------

    public function testAuthRoutesHaveCsrfMiddleware(): void
    {
        Router::auth();
        $postRoutes = ['/auth/authenticate', '/auth/signup-new'];
        foreach ($postRoutes as $url) {
            BaseRouter::match($url);
            $params = BaseRouter::getParams();
            $this->assertArrayHasKey('middleware', $params, "Auth POST route $url should have middleware");
            $this->assertStringContainsString('Csrf', $params['middleware'][0]);
        }
    }

    public function testAuthGetRoutesDoNotBlockWithoutToken(): void
    {
        TestMiddleware::reset();
        Router::auth();
        $getRoutes = ['/auth/login', '/auth/logout', '/auth/signup'];
        foreach ($getRoutes as $url) {
            TestMiddleware::reset();
            $this->assertTrue(BaseRouter::match($url), "Auth GET route $url should match");
        }
    }

    // -----------------------------------------------------------------------
    // Resource routes
    // -----------------------------------------------------------------------

    public function testResourceRegistersAllRoutes(): void
    {
        Router::resource('photos', 'Photo');
        $match = [
            ['/photos', 'GET', 'index'],
            ['/photos/create', 'GET', 'create'],
            ['/photos/store', 'POST', 'store'],
            ['/photos/show/1', 'GET', 'show'],
            ['/photos/edit/1', 'GET', 'edit'],
            ['/photos/update/1', 'PATCH', 'update'],
            ['/photos/destroy/1', 'DELETE', 'destroy'],
        ];
        foreach ($match as [$url, $method, $action]) {
            $_SERVER['REQUEST_METHOD'] = $method;
            $this->assertTrue(BaseRouter::match($url), "Resource route /$url ($method) should match");
            $params = BaseRouter::getParams();
            $this->assertSame('Photo', $params['controller'], "Resource $action controller mismatch");
        }
    }

    // -----------------------------------------------------------------------
    // Auth routes
    // -----------------------------------------------------------------------

    public function testAuthRegistersRoutesWithPrefix(): void
    {
        Router::auth();
        $routes = [
            '/auth/login',
            '/auth/logout',
            '/auth/authenticate',
            '/auth/signup',
            '/auth/signup-new',
        ];
        foreach ($routes as $url) {
            $this->assertTrue(
                BaseRouter::match($url),
                "Auth route $url should match"
            );
        }
    }

    // -----------------------------------------------------------------------
    // String notation (Controller@action)
    // -----------------------------------------------------------------------

    public function testStringNotationParsesControllerAndAction(): void
    {
        Router::get('/string-test', 'PageController@indexAction');
        $this->assertTrue(BaseRouter::match('/string-test'));
        $params = BaseRouter::getParams();
        $this->assertSame('PageController', $params['controller']);
        $this->assertSame('indexAction', $params['action']);
    }

    // -----------------------------------------------------------------------
    // Closure routes
    // -----------------------------------------------------------------------

    public function testClosureRouteMatchDoesNotCallClosure(): void
    {
        Router::get('/closure', function () {
            return 'hello';
        });
        $this->assertTrue(BaseRouter::match('/closure'));
        $params = BaseRouter::getParams();
        $this->assertArrayHasKey('closure', $params);
    }

    // -----------------------------------------------------------------------
    // Trailing slash normalization (via dispatch)
    // -----------------------------------------------------------------------

    public function testDispatchNormalizesTrailingSlash(): void
    {
        Router::get('/about', ['controller' => 'Stub', 'action' => 'index']);
        $caught = null;
        try {
            BaseRouter::dispatch('/about/');
        } catch (\Throwable $e) {
            $caught = $e->getMessage();
        }
        $this->assertNull($caught, 'Trailing slash should not cause 404');
    }

    public function testDispatchKeepsRootSlash(): void
    {
        Router::get('/', ['controller' => 'Stub', 'action' => 'index']);
        $caught = null;
        try {
            BaseRouter::dispatch('/');
        } catch (\Throwable $e) {
            $caught = $e->getMessage();
        }
        $this->assertNull($caught, 'Root should still match');
    }

    // -----------------------------------------------------------------------
    // HTTP method enforcement (via dispatch)
    // -----------------------------------------------------------------------

    public function testDispatchRejectsWrongHttpMethod(): void
    {
        Router::get('/submit', ['controller' => 'Stub', 'action' => 'index']);
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $this->expectException(\Exception::class);
        $this->expectExceptionCode(405);
        BaseRouter::dispatch('/submit');
    }

    public function testDispatchAcceptsCorrectHttpMethod(): void
    {
        Router::post('/submit', ['controller' => 'Stub', 'action' => 'index']);
        $_SERVER['REQUEST_METHOD'] = 'POST';
        ob_start();
        BaseRouter::dispatch('/submit');
        ob_end_clean();
        $this->assertTrue(true);
    }

    public function testMethodOverrideWorks(): void
    {
        Router::patch('/update', ['controller' => 'Stub', 'action' => 'index']);
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST['_method'] = 'PATCH';
        ob_start();
        BaseRouter::dispatch('/update');
        ob_end_clean();
        $this->assertTrue(true);
    }

    public function testAnyMethodAcceptsAll(): void
    {
        Router::any('/any-route', ['controller' => 'Page', 'action' => 'index']);
        foreach (['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'HEAD'] as $method) {
            $_SERVER['REQUEST_METHOD'] = $method;
            $this->assertTrue(
                BaseRouter::match('/any-route'),
                "ANY route should match $method"
            );
        }
    }

    // -----------------------------------------------------------------------
    // Dispatch 404
    // -----------------------------------------------------------------------

    public function testDispatchThrows404ForInvalidRoute(): void
    {
        Router::get('/exists', ['controller' => 'Page', 'action' => 'index']);
        $this->expectException(\Exception::class);
        $this->expectExceptionCode(404);
        BaseRouter::dispatch('/does-not-exist');
    }

    // -----------------------------------------------------------------------
    // Route ordering / priority
    // -----------------------------------------------------------------------

    public function testRouteOrderIsRegistrationOrder(): void
    {
        Router::get('/post/{slug}', ['controller' => 'Post', 'action' => 'show']);
        Router::get('/post/latest', ['controller' => 'Post', 'action' => 'latest']);
        $this->assertTrue(BaseRouter::match('/post/latest'));
        $params = BaseRouter::getParams();
        $this->assertSame('Post', $params['controller']);
        $this->assertSame('show', $params['action']);
    }

    // -----------------------------------------------------------------------
    // Closures in dispatch (output buffering)
    // -----------------------------------------------------------------------

    public function testDispatchClosureExecutesAndReturns(): void
    {
        Router::get('/hello', function () {
            return 'Hello, World!';
        });
        ob_start();
        BaseRouter::dispatch('/hello');
        $output = ob_get_clean();
        $this->assertSame('Hello, World!', $output);
    }

    public function testDispatchClosureWithParams(): void
    {
        Router::get('/greet/{name}', function () {
            $params = BaseRouter::getParams();
            return 'Hi, ' . $params['name'];
        });
        ob_start();
        BaseRouter::dispatch('/greet/Alice');
        $output = ob_get_clean();
        $this->assertSame('Hi, Alice', $output);
    }

    // -----------------------------------------------------------------------
    // removeQueryString edge cases
    // -----------------------------------------------------------------------

    public function testDispatchStripsQueryString(): void
    {
        Router::get('/search', ['controller' => 'Search', 'action' => 'index']);
        $this->assertTrue(BaseRouter::match('/search'));
    }

    // -----------------------------------------------------------------------
    // Route with hash (fragment)
    // -----------------------------------------------------------------------

    public function testRouteWithVariousSpecialCharsInParam(): void
    {
        Router::get('/item/{code}', ['controller' => 'Item', 'action' => 'show']);
        $this->assertTrue(BaseRouter::match('/item/ABC-123_xyz'));
        $params = BaseRouter::getParams();
        $this->assertSame('ABC-123_xyz', $params['code']);
    }

    // -----------------------------------------------------------------------
    // Exact parameter value fidelity
    // -----------------------------------------------------------------------

    public function testParamValueMatchesExactly(): void
    {
        Router::get('/tag/{tag}', ['controller' => 'Tag', 'action' => 'show']);
        $this->assertTrue(BaseRouter::match('/tag/php8'));
        $params = BaseRouter::getParams();
        $this->assertSame('php8', $params['tag']);
    }

    public function testParamWithDots(): void
    {
        Router::get('/file/{path}', ['controller' => 'File', 'action' => 'show']);
        $this->assertTrue(BaseRouter::match('/file/photo.jpg'));
        $params = BaseRouter::getParams();
        $this->assertSame('photo.jpg', $params['path']);
    }

    // -----------------------------------------------------------------------
    // Multiple routes with same pattern, different methods
    // -----------------------------------------------------------------------

    public function testSamePathDifferentMethods(): void
    {
        Router::get('/resource', ['controller' => 'Resource', 'action' => 'index']);
        Router::post('/resource', ['controller' => 'Resource', 'action' => 'store']);
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $this->assertTrue(BaseRouter::match('/resource'));
    }

    // -----------------------------------------------------------------------
    // ControllerDispatcher — parameter resolution
    // -----------------------------------------------------------------------

    public function testRouteParamInjectedByName(): void
    {
        Router::get('/user/{id}', ['controller' => 'Param', 'action' => 'show']);
        $_SERVER['REQUEST_METHOD'] = 'GET';
        ob_start();
        BaseRouter::dispatch('/user/42');
        $output = ob_get_clean();
        $this->assertSame('id:42', $output);
    }

    public function testDefaultValuePreservedWhenParamMissing(): void
    {
        Router::get('/items', ['controller' => 'Param', 'action' => 'optionalParam']);
        $_SERVER['REQUEST_METHOD'] = 'GET';
        ob_start();
        BaseRouter::dispatch('/items');
        $output = ob_get_clean();
        $this->assertSame('page:1', $output);
    }

    public function testDefaultValueOverriddenByRouteParam(): void
    {
        Router::get('/items/{page}', ['controller' => 'Param', 'action' => 'optionalParam']);
        $_SERVER['REQUEST_METHOD'] = 'GET';
        ob_start();
        BaseRouter::dispatch('/items/5');
        $output = ob_get_clean();
        $this->assertSame('page:5', $output);
    }

    public function testRequestInjectedByTypeHint(): void
    {
        Router::get('/inject-request', ['controller' => 'Param', 'action' => 'requestInjection']);
        $_SERVER['REQUEST_METHOD'] = 'GET';
        ob_start();
        BaseRouter::dispatch('/inject-request');
        $output = ob_get_clean();
        $this->assertSame('request:injected', $output);
    }

    public function testMixedRequestAndRouteParams(): void
    {
        Router::get('/mixed/{id}', ['controller' => 'Param', 'action' => 'mixedParams']);
        $_SERVER['REQUEST_METHOD'] = 'GET';
        ob_start();
        BaseRouter::dispatch('/mixed/99');
        $output = ob_get_clean();
        $this->assertSame('id:99', $output);
    }

    public function testServiceWithDefaultValueSkipsMissingParam(): void
    {
        Router::get('/show-default', ['controller' => 'Param', 'action' => 'showWithDefault']);
        $_SERVER['REQUEST_METHOD'] = 'GET';
        ob_start();
        BaseRouter::dispatch('/show-default');
        $output = ob_get_clean();
        $this->assertSame('id:,sort:asc', $output);
    }

    public function testAutoWireSimpleService(): void
    {
        Router::get('/auto-service', ['controller' => 'AutoWire', 'action' => 'autoService']);
        $_SERVER['REQUEST_METHOD'] = 'GET';
        ob_start();
        BaseRouter::dispatch('/auto-service');
        $output = ob_get_clean();
        $this->assertSame('hello from service', $output);
    }

    public function testAutoWireNestedService(): void
    {
        Router::get('/nested-service', ['controller' => 'AutoWire', 'action' => 'nestedService']);
        $_SERVER['REQUEST_METHOD'] = 'GET';
        ob_start();
        BaseRouter::dispatch('/nested-service');
        $output = ob_get_clean();
        $this->assertSame('hello from service', $output);
    }

    public function testFormRequestSubclassInjected(): void
    {
        Router::get('/form-request', ['controller' => 'FormRequest', 'action' => 'store']);
        $_SERVER['REQUEST_METHOD'] = 'GET';
        ob_start();
        BaseRouter::dispatch('/form-request');
        $output = ob_get_clean();
        $this->assertStringContainsString('test', $output);
    }

    public function testFormRequestValidPasses(): void
    {
        $_GET['name'] = 'John';
        Router::get('/form-valid', [
            'controller' => 'FormRequestIntegration',
            'action' => 'store'
        ]);
        $_SERVER['REQUEST_METHOD'] = 'GET';
        ob_start();
        BaseRouter::dispatch('/form-valid');
        $output = ob_get_clean();
        $this->assertSame('validated', $output);
    }

    public function testFormRequestValidationFails(): void
    {
        $request = new \App\Controllers\TestFormRequest(
            ['name' => '123'], [], [], [], [], []
        );
        $this->expectException(ValidationException::class);
        $request->validate();
    }
}

<?php

namespace Simple\Routing;

use Illuminate\Pagination\Paginator;
use ReflectionClass;
use ReflectionMethod;
use ReflectionParameter;
use Simple\Request;
use Simple\Session;
use Simple\Validation\FormRequest;
use Simple\Validation\ValidationException;

class ControllerDispatcher
{
    private array $routeParams;
    private static ?Request $request = null;

    public function __construct(array $routeParams)
    {
        $this->routeParams = $routeParams;
    }

    public function dispatch(BaseController $controller, string $action): void
    {
        $method = $this->resolveMethod($controller, $action);
        $params = $this->resolveParameters($method);

        $this->setupPaginator();

        echo $controller->$action(...$params);
    }

    private function resolveMethod(BaseController $controller, string $action): ReflectionMethod
    {
        $refClass = new ReflectionClass($controller);

        if (!$refClass->hasMethod($action)) {
            $action .= 'Action';
        }

        return $refClass->getMethod($action);
    }

    private function resolveParameters(ReflectionMethod $method): array
    {
        $params = [];

        foreach ($method->getParameters() as $param) {
            $params[$param->getName()] = $this->resolveParameter($param);
        }

        return $params;
    }

    private function resolveParameter(ReflectionParameter $param): mixed
    {
        $type = $param->getType();

        if ($type !== null && !$type->isBuiltin()) {
            return $this->resolveClass($type->getName());
        }

        if (isset($this->routeParams[$param->getName()])) {
            return $this->routeParams[$param->getName()];
        }

        if ($param->isDefaultValueAvailable()) {
            return $param->getDefaultValue();
        }

        if ($param->allowsNull()) {
            return null;
        }

        return null;
    }

    private function resolveClass(string $className): object
    {
        if ($className === Request::class) {
            return $this->resolveRequest();
        }

        if (is_subclass_of($className, Request::class)) {
            $request = new $className(
                $_GET, $_POST, [], $_COOKIE, $_FILES, $_SERVER
            );
            $request->bootstrap();

            if ($request instanceof FormRequest) {
                try {
                    $request->validate();
                } catch (ValidationException $e) {
                    Session::init();
                    Session::set('_errors', $e->errors());
                    Session::preserveInput($request->all());

                    if ($request->isXmlHttpRequest()) {
                        http_response_code(422);
                        header('Content-Type: application/json');
                        echo json_encode(['errors' => $e->errors()]);
                        exit;
                    }

                    $referer = $_SERVER['HTTP_REFERER'] ?? '/';
                    header('location: ' . $referer, true, 303);
                    exit;
                }
            }

            return $request;
        }

        $refClass = new ReflectionClass($className);
        $constructor = $refClass->getConstructor();

        if ($constructor === null) {
            return $refClass->newInstance();
        }

        $params = [];
        foreach ($constructor->getParameters() as $param) {
            $params[] = $this->resolveConstructorParameter($param);
        }

        return $refClass->newInstanceArgs($params);
    }

    private function resolveConstructorParameter(ReflectionParameter $param): mixed
    {
        $type = $param->getType();

        if ($type !== null && !$type->isBuiltin()) {
            return $this->resolveClass($type->getName());
        }

        if ($param->isDefaultValueAvailable()) {
            return $param->getDefaultValue();
        }

        if ($param->allowsNull()) {
            return null;
        }

        throw new \RuntimeException(sprintf(
            'Cannot auto-resolve parameter $%s for %s — no type hint, no default, and not nullable.',
            $param->getName(),
            $param->getDeclaringClass()?->getName()
        ));
    }

    private function resolveRequest(): Request
    {
        if (self::$request === null) {
            self::$request = new Request(
                $_GET, $_POST, [], $_COOKIE, $_FILES, $_SERVER
            );
            self::$request->bootstrap();
        }

        return self::$request;
    }

    private function setupPaginator(): void
    {
        $currentPage = self::$request?->get('page') ?? $this->resolveRequest()->get('page');

        Paginator::currentPageResolver(function () use ($currentPage) {
            return $currentPage;
        });
    }

    public static function reset(): void
    {
        self::$request = null;
    }
}

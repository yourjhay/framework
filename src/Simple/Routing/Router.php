<?php

namespace Simple\Routing;

class Router Extends BaseRouter
{
    /**
     * Fluent middleware setter for the current route
     */
    public function middleware($middleware): Router
    {
        $params = parent::getCurrentParam();
        if (!isset($params['middleware'])) {
            $params['middleware'] = [];
        }
        $middlewareList = is_array($middleware) ? $middleware : [$middleware];
        $params['middleware'] = array_merge($params['middleware'], $middlewareList);
        parent::updateCurrentRoute($params);
        return $this;
    }
    /**
     * SET Route to accept only POST method
     * @param $route string URL of your route
     * @param mixed $params Parameters like controller and action
     * @return Router
     */
    public static function post(string $route, $params = []): Router
    {
        parent::set($route, $params, 'POST');
        return new static;
    }

    /**
     * SET Route to accept only GET method
     * @param $route string URL of your route
     * @param mixed $params Parameters like controller and action
     * @return Router
     */
    public static function get(string $route, $params = []): Router
    {
        parent::set($route, $params, 'GET');
        return new static;
    }

    /**
     * SET Route to accept only PUT method
     * @param $route string URL of your route
     * @param mixed $params Parameters like controller and action
     * @return Router
     */
    public static function put(string $route, $params = []): Router
    {
        parent::set($route, $params, 'PUT');
        return new static;
    }

    /**
     * SET Route to accept only DELETE method
     * @param $route string URL of your route
     * @param mixed $params Parameters like controller and action
     * @return Router
     */
    public static function delete(string $route, $params = []): Router
    {
        parent::set($route, $params, 'DELETE');
        return new static;
    }

    /**
     * SET Route to accept only PATCH method
     * @param $route string URL of your route
     * @param mixed $params Parameters like controller and action
     * @return Router
     */
    public static function patch(string $route, $params = []): Router
    {
        parent::set($route, $params, 'PATCH');
        return new static;
    }

    /**
     * SET Route to accept only ANY method
     * @param $route string URL of your route
     * @param mixed $params Parameters like controller and action
     * @return Router
     */
    public static function any(string $route, $params = []): Router
    {
        parent::set($route, $params, 'ANY');
        return new static;
    }

    /**
     * SET Route to accept only HEAD method
     * @param $route string URL of your route
     * @param mixed $params Parameters like controller and action
     * @return Router
     */
    public static function head(string $route, $params = []): Router
    {
        parent::set($route, $params, 'HEAD');
        return new static;
    }

    /**
     * @param string|array $prefix Route prefix or array of options
     * @param callable $routes routes callable
     */
    public static function group($prefix, callable $routes)
    {
        if (is_string($prefix)) {
            $options = ['prefix' => $prefix];
        } else {
            $options = $prefix;
            $prefix = $options['prefix'] ?? '';
        }

        $prevGroupPrefix = parent::$currentGroupPrefix;
        $prefix = '/' . trim($prefix, '/');
        parent::$currentGroupPrefix = $prevGroupPrefix . $prefix;

        $groupMiddleware = $options['middleware'] ?? [];
        if (!empty($groupMiddleware)) {
            array_push(parent::$currentGroupMiddleware, ...(is_array($groupMiddleware) ? $groupMiddleware : [$groupMiddleware]));
        }

        try {
            call_user_func($routes);
        } finally {
            if (!empty($groupMiddleware)) {
                $count = is_array($groupMiddleware) ? count($groupMiddleware) : 1;
                array_splice(parent::$currentGroupMiddleware, -$count);
            }
            parent::$currentGroupPrefix = $prevGroupPrefix;
        }
    }

    /**
     *  Declare a resource route
     * @param string $route  route URL
     * @param string $controller  Controller name
     */
    public static function resource(string $route, string $controller)
    {
        $route = '/' . ltrim($route, '/');
        self::get($route, ['controller' => $controller, 'action' => 'index'])
            ->alias("$controller.index");
        self::post("$route/store", ['controller' => $controller, 'action' => 'store'])
            ->alias("$controller.store");
        self::get("$route/create", ['controller' => $controller, 'action' => 'create'])
            ->alias("$controller.create");
        self::get("$route/edit/{id:\w+}", ['controller' => $controller, 'action' => 'edit'])
            ->alias("$controller.edit");
        self::patch("$route/update/{id:\w+}", ['controller' => $controller, 'action' => 'update'])
            ->alias("$controller.update");
        self::get("$route/show/{id:\w+}", ['controller' => $controller, 'action' => 'show'])
            ->alias("$controller.show");
        self::delete("$route/destroy/{id:\w+}", ['controller' => $controller, 'action' => 'destroy'])
            ->alias("$controller.delete");
    }

    /**
     * Declare the authentication routes
     */
    public static function auth()
    {
        self::group(['prefix' => 'auth', 'middleware' => [\Simple\Middleware\Csrf::class]], function(){
            self::get('/login','Auth\Auth@index')
            ->alias('auth.index');

            self::get('/logout','Auth\Auth@logout')
                ->alias('auth.logout');

            self::post('/authenticate','Auth\Auth@authenticate')
                ->alias('auth.authenticate');

            self::get('/signup','Auth\Signup@signup')
                ->alias('auth.signup');

            self::post('/signup-new','Auth\Signup@signup-new')
                ->alias('auth.signup-new');
        });
    }
}

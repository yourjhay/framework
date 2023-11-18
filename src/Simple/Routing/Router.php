<?php

namespace Simple\Routing;

class Router Extends BaseRouter
{
    /**
     * SET Route to accept only POST method
     * @param $route string URL of your route
     * @param mixed $params Paramaters like controller and action
     * @return Router
     */
    public static function post($route, $params = [])
    {
        parent::set($route, $params, 'POST');
        return new static;
    }

    /**
     * SET Route to accept only GET method
     * @param $route string URL of your route
     * @param mixed $params Paramaters like controller and action
     * @return Router
     */
    public static function get($route, $params = [])
    {
        parent::set($route, $params, 'GET');
        return new static;
    }

    /**
     * SET Route to accept only PUT method
     * @param $route string URL of your route
     * @param mixed $params Paramaters like controller and action
     * @return Router
     */
    public static function put($route, $params = [])
    {
        parent::set($route, $params, 'PUT');
        return new static;
    }

    /**
     * SET Route to accept only DELETE method
     * @param $route string URL of your route
     * @param mixed $params Paramaters like controller and action
     * @return Router
     */
    public static function delete($route, $params = [])
    {
        parent::set($route, $params, 'DELETE');
        return new static;
    }

    /**
     * SET Route to accept only PATCH method
     * @param $route string URL of your route
     * @param mixed $params Paramaters like controller and action
     * @return Router
     */
    public static function patch($route, $params = [])
    {
        parent::set($route, $params, 'PATCH');
        return new static;
    }

    /**
     * SET Route to accept only ANY method
     * @param $route string URL of your route
     * @param mixed $params Paramaters like controller and action
     * @return Router
     */
    public static function any($route, $params = [])
    {
        parent::set($route, $params, 'ANY');
        return new static;
    }

    /**
     * SET Route to accept only HEAD method
     * @param $route string URL of your route
     * @param mixed $params Paramaters like controller and action
     * @return Router
     */
    public static function head($route, $params = [])
    {
        parent::set($route, $params, 'HEAD');
        return new static;
    }

    /**
     * @param string $prefix Route prefix
     * @param callable $routes routes callable
     */
    public static function group(string $prefix, callable $routes)
    {
        $prevGroupPrefix = parent::$currentGroupPrefix;
        parent::$currentGroupPrefix = $prefix . $prevGroupPrefix;
        call_user_func($routes);
        parent::$currentGroupPrefix='';
    }

    /**
     *  Declare a resource route
     * @param string $route  route URL
     * @param string $controller  Controller name
     */
    public static function resource($route, $controller)
    {
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
        self::group('auth', function(){
            self::get('/','Auth\Auth@index')
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
        //self::set('{controller:password}/{action:\breset|\bemail}/{token?}');
    }
}
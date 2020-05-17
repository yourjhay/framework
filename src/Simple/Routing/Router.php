<?php
namespace Simple\Routing;

class Router Extends BaseRouter
{
    /**
     * SET Route to accept only POST method
     */
    public static function post($route, $params = [])
    {
        parent::set($route, $params, 'POST');
        return new static;
    }

    /**
     * SET Route to accept only GET method
     */
    public static function get($route, $params = [])
    {     
        parent::set($route, $params, 'GET');
        return new static;
    }

    /**
     * SET Route to accept only PUT method
     */
    public static function put($route, $params = [])
    {
        parent::set($route, $params, 'PUT');
        return new static;
    }

    /**
     * SET Route to accept only DELETE method
     */
    public static function delete($route, $params = [])
    {
        parent::set($route, $params, 'DELETE');
        return new static;
    }

    /**
     * SET Route to accept only PATCH method
     */
    public static function patch($route, $params = [])
    {
        parent::set($route, $params, 'PATCH');
        return new static;
    }

    /**
     * SET Route to accept only PATCH method
     */
    public static function any($route, $params = [])
    {
        parent::set($route, $params, 'ANY');
        return new static;
    }

    /**
     * SET Route to accept only PUT method
     */
    public static function head($route, $params = [])
    {
        parent::set($route, $params, 'HEAD');
        return new static;
    }

    public static function group(string $prefix, callable $routes)
    {
        $prevGroupPrefix = parent::$currentGroupPrefix;
        parent::$currentGroupPrefix = $prefix . $prevGroupPrefix;
        call_user_func($routes);
    }

    /**
     * @param string $route  route URL
     * @param string $controller  Controller name
     */
    public static function resource($route, $controller)
    {
        self::get($route, ['controller' => $controller, 'action' => 'index']);
        self::post("$route/store", ['controller' => $controller, 'action' => 'store']);
        self::get("$route/create", ['controller' => $controller, 'action' => 'create']);
        self::get("$route/edit/{id:\w+}", ['controller' => $controller, 'action' => 'edit']);
        self::put("$route/update/{id:\w+}", ['controller' => $controller, 'action' => 'update']);
        self::get("$route/show/{id:\w+}", ['controller' => $controller, 'action' => 'show']);
        self::delete("$route/destroy/{id:\w+}", ['controller' => $controller, 'action' => 'destroy']);
    }

    public static function auth()
    {
        self::get('auth/','Auth\Auth@index')
            ->alias('auth.index');

        self::get('auth/logout','Auth\Auth@logout')
            ->alias('auth.logout');

        self::post('auth/authenticate','Auth\Auth@authenticate')
            ->alias('auth.authenticate');

        self::get('auth/signup','Auth\Signup@signup')
            ->alias('auth.signup');

        self::post('auth/signup-new','Auth\Signup@signup-new')
            ->alias('auth.signup-new');

        //self::set('{controller:password}/{action:\breset|\bemail}/{token?}');
    }
}
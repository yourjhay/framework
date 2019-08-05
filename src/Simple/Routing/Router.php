<?php
/*----------------------------------------------------------------
|
| The Simple PHP Framework
| @reyjhonbaquirin
| *** BASE ROUTER Class ***
------------------------------------------------------------------*/
namespace Simple\Routing;

class Router
{
    /**
     * Associative array of register routes
     * @var array
     */
    protected static $routes = [];

    /**
     * Parameters from matched routes
     * @var array
     */
    protected static $params = [];

    /**
     * register routes
     * @param string $route - Route URL
     * @param array $params Parameters (controller, action, etc.)
     */
    public static function set($route, $params = [])
    {

        //convert the route to a regular exp. escape forward slashes
        $route = preg_replace('/\//','\\/', $route);

        //convert var like {controller}
        $route = preg_replace('/\{([a-z]+)\}/','(?P<\1>[a-z-]+)', $route);

        //convert optional variable
        $route = preg_replace('/\{([a-z]+)([?]+)\}/', '(?P<\1>\w+)\2', $route);

        //convert variables with custom regex eg: {id: \d+}
        $route = preg_replace('/\{([a-z]+):([^\}]+)\}/', '(?P<\1>\2)', $route);
        //add start and end delimeters, case insensitive flag
        $route = '/^' . $route . '$/i';
        self::$routes[$route] = $params;
    }

    /**
     * @param string $route  route URL
     * @param string $controller  Controller name
     */
    public static function resource($route, $controller)
    {
        self::set($route, ['controller' => $controller, 'action' => 'index']);
        self::set("$route/store", ['controller' => $controller, 'action' => 'store']);
        self::set("$route/create", ['controller' => $controller, 'action' => 'create']);
        self::set("$route/edit/{id:\w+}", ['controller' => $controller, 'action' => 'edit']);
        self::set("$route/update/{id:\w+}", ['controller' => $controller, 'action' => 'update']);
        self::set("$route/show/{id:\w+}", ['controller' => $controller, 'action' => 'show']);
        self::set("$route/destroy/{id:\w+}", ['controller' => $controller, 'action' => 'destroy']);
    }

    public static function auth()
    {
        self::set('auth/{action:\bindex|\bauthenticate|\blogout}',[
            'controller' => 'AuthController',
            'namespace' => 'Auth'
        ]);

        self::set('auth/{action:\bSignup|\bsignup-new}',[
            'controller' => 'SignupController',
            'namespace' => 'Auth'
        ]);

        self::set('{controller:password}/{action:\breset|\bemail}/{token?}');
    }

    /**
     * Return all available routes
     * @return array
     */
    public static function getRoutes()
    {
        return self::$routes;
    }

    /**
     * Match the route to $routes setting $params property
     * if a route is found
     * @param string $url - The route URL
     * @return boolean true if match found else false
     */
    public static function match($url)
    {
        foreach(self::$routes as $route => $params){
            if(preg_match($route, $url, $matches)){
                foreach($matches as $key => $match){
                    if(is_string($key)){
                        $params[$key] = $match;
                    }
                }
                self::$params = $params;
                return true;
            }
        }
        return false;
    }

    /**
     * Get matched parameters
     */
    public static function getParams()
    {
        return self::$params;
    }

    /**
     * Dispatch route parameter to URL
     *
     */
    public static function dispatch($url)
    {
        $url_orig = $url;
        $retry = 0;

        $url = self::removeQueryString($url);

        tryagain:

        if(self::match($url)) {
            if (preg_match('/controller$/i', self::$params['controller']) == 0) {
                $controller = self::$params['controller'].'Controller';
            } else {
                $controller = self::$params['controller'];
            }
            $controller = self::convertToStudlyCaps($controller);
            $controller = self::getNamespace() . $controller;

            if(class_exists($controller)){

                $controller_object = new $controller(self::$params);
                $action = self::$params['action'];
                $action = self::convertToCamelCase($action);

                if (preg_match('/action$/i', $action) == 0) {

                    echo $controller_object->$action(new \Simple\Request);

                } else {
                    throw new \Exception("Method [$action] (in Controller [$controller] ) can't be called explicitly. Remove Action suffix instead");
                }
            } else {
                throw new \Exception("Controller class [$controller] not found");
            }
        } else {
            if($retry > 0){
                throw new \Exception("INVALID ROUTE [$url]", 404);
            } else {

                if(substr($url,-1) == '/' && $retry==0) {
                    $url = substr($url,0,-1);
                    $retry+=1;
                } else {
                    $url = $url_orig.'/';
                    $retry+=1;
                }
                goto tryagain;
            }
        }
    }

    /**
     * convert string into Studly Case format
     * @var string
     * @return string
     */
    private static function convertToStudlyCaps($string)
    {
        return str_replace(' ','',ucwords(str_replace('-',' ', $string)));
    }

    /**
     * convert string into Camel Case format
     * @var string
     * @return string
     */
    private static function convertToCamelCase($string)
    {
        return lcfirst(self::convertToStudlyCaps($string));
    }

    /**
     * Remove query string from url like:
     * ?page=1&id=1...... etc.
     * @param string $url
     * @return array or string URL
     */
    protected static function removeQueryString($url)
    {
        if($url != ''){
            $parts = explode('&', $url, 2);
            if(strpos($parts[0], '=') === false){
                $url = $parts[0];
            } else {
                $url = explode('?',$parts[0]);
            }
        }
        return is_array($url)?$url[0]:$url;
    }

    /**
     * Getnamespace in route params to specify where it has to be called
     * @return string $namespace
     */
    protected static function getNamespace()
    {
        $namespace = 'App\Controllers\\';
        if(array_key_exists('namespace', self::$params)) {
            $namespace .= self::$params['namespace'] . '\\';
        }
        return $namespace;
    }

}
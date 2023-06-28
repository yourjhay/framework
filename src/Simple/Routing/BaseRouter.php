<?php

namespace Simple\Routing;

class BaseRouter
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

    private static $current_route;
    private static $current_param;
    protected static $raw_current_route;
    protected static $compiled_routes =[];
    protected static $currentGroupPrefix = '';

    /**
     * register routes
     * @param string $route - Route URL
     * @param array $params Parameters (controller, action, etc.)
     * @param string $http_method - Request method
     */
    protected static function set($route, $params = [], $http_method="ANY")
    {
        //Assign group to route
        $route = self::$currentGroupPrefix . $route;

        if (is_string($params)){            
            $param = explode('@', $params);
            $params = [];
            $params['controller'] = $param[0];
            $params['action'] = $param[1];

        } 
        $params['request_method'] = $http_method;
        self::$raw_current_route = $route;

        $r = null;
        /**
         * Check if there's a optional parameter that is set
         * store it and create a route without the optional 
         * so it will not result in 404
         */
        if (preg_match('/\{([a-z]+)([?]+)\}/', $route)) {
            $r = preg_replace('/\/{([a-z]+)([?]+)\}/','', $route);
        }
        
        //convert the route to a regular exp. escape forward slashes
        $route = preg_replace('/\//','\\/', $route);        

        //convert var like {controller}
        $route = preg_replace('/\{([a-z]+)\}/','(?P<\1>[a-z-]+)', $route);     
        
        //convert variables with custom regex eg: {id: \d+}
        $route = preg_replace('/\{([a-z]+):([^\}]+)\}/', '(?P<\1>\2)', $route);

        //this convert {:all?} to accept any url pass through
        $route = preg_replace('/\{([:]+)([all]+)([?]+)\}/', '(?P<\2>([a-z0-9\/-]+))\3', $route);
        
        //convert optional variable
        $route = preg_replace('/\{([a-z]+)([?]+)\}/', '(?P<\1>\w+)\2', $route);

        //add start and end delimiters, case insensitive flag
        $route = '/^' . $route . '$/i';

        // this set the variable value to be use in alias()
        self::$current_route = $route;
        self::$current_param = $params;

        self::$routes[$route] = $params;
        if ($r) {
            self::set($r, $params, $http_method);
        }
    }    

    /**
     * Set an alias to a route 
     * @param string 
     * @return void
     */
    public static function alias($alias)
    {       
        self::routeCompiler($alias, '/'.self::$raw_current_route, self::$current_param); 
        self::$current_param['alias'] = $alias;
        self::$routes[self::$current_route] =self::$current_param;        
    }

    /**
     * Routes compiler 
     * @param string $name route alias
     * @param string $route url
     * @param array $params url parameters
     * @return void
     */
    public static function routeCompiler($name, $route, $params=[])
    {
        $params['url'] = $route;
        self::$compiled_routes[$name] = $params;
    }

    /**
     * Return Compiled routes that has an alias
     * @return array
     */
    public static function compiledRoutes()
    {
        return self::$compiled_routes;
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
            if (preg_match($route, $url, $matches)){
                foreach($matches as $key => $match){
                    if (is_string($key)){
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
        $url = self::removeQueryString($url);

        if (self::match($url)) {
            if (preg_match('/controller$/i', self::$params['controller']) == 0) {
                $controller = self::$params['controller'].'Controller';
            } else {
                $controller = self::$params['controller'];
            }
            
            $controller = self::convertToStudlyCaps($controller);
            $controller = self::getNamespace() . $controller;
            
            if (class_exists($controller)){
                $controller_object = new $controller(self::$params);
                $action = self::$params['action'];
                $action = self::convertToCamelCase($action);

                if (preg_match('/action$/i', $action) == 0) {
                    $request = $_SERVER['REQUEST_METHOD'];
                    if (isset($_POST['_method'])){
                        $request = $_POST['_method'];
                    }           
                    $user_request_method = strtoupper(self::$params['request_method']);
                    if ($request === $user_request_method
                        || $user_request_method === 'ANY'
                    ) {
                        echo $controller_object->$action(new \Simple\Request);
                    } else {
                        throw new \Exception("$request Method not allowed", 405);
                    } 
                } else {
                    throw new \Exception("Method [$action] (in Controller [$controller] ) can't be called explicitly. Remove Action suffix instead");
                }
            } else {
                throw new \Exception("Controller class [$controller] not found");
            }
        } else {
            throw new \Exception("INVALID ROUTE [$url]", 404);
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
        if ($url != ''){
            $parts = explode('&', $url, 2);
            if (strpos($parts[0], '=') === false){
                $url = $parts[0];
            } else {
                if (strpos($parts[0], '?') === false){
                    $url = '';
                } else {
                    $url = explode('?', $parts[0]);
                }
            }
        } else {
            $url = "/";
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
        if (array_key_exists('namespace', self::$params)) {
            $namespace .= self::$params['namespace'] . '\\';
        }        
        return $namespace;
    }
}
<?php

namespace Simple\Routing;

class BaseRouter
{
    /**
     * Associative array of register routes
     * @var array
     */
    protected static array $routes = [];

    /**
     * Parameters from matched routes
     * @var array
     */
    protected static array $params = [];

    private static string $current_route;
    private static array $current_param;
    protected static string $raw_current_route;
    protected static array $compiled_routes =[];
    protected static string $currentGroupPrefix = '';


    /**
     * register routes
     * @param string $route - Route URL
     * @param array $params Parameters (controller, action, etc.)
     * @param string $http_method - Request method
     */
    protected static function set(string $route, $params = [], $http_method="ANY")
    {
        //Assign group to route
        $route = self::$currentGroupPrefix . $route;

        if (is_string($params)){
            $param = explode('@', $params);
            $params = [];
            $params['controller'] = $param[0];
            $params['action'] = $param[1];
        }
        if(($params instanceof \Closure)){
            $closure = $params;
            $params=[];
            $params['closure'] = $closure;
        }

        $params['request_method'] = $http_method;
        self::$raw_current_route = $route;

        $twin_route = null;
        /**
         * Check if there's a optional parameter that is set
         * store it and create a route without the optional
         * so it will not result in 404
         */
        if (preg_match('/{([a-z]+)(\?)}/', $route)) {
            $twin_route = preg_replace('/\/{([a-z]+)(\?)}/','', $route);
        }
        // optional parameter with a regex
        if (preg_match('/{([a-z]+)(\?):([^}]+)}/', $route)) {
            $twin_route = preg_replace('/\/{([a-z]+)(\?):([^}]+)}/','', $route);
        }

        //convert the route to a regular exp. escape forward slashes
        $route = preg_replace('/\//','\\/', $route);

        //convert var like {controller} with accept case insensitive letters and numbers
        $route = preg_replace('/{([a-z]+)}/','(?P<\1>[a-zA-Z0-9]+)', $route);

        //convert variables with custom regex eg: {id: \d+}
        $route = preg_replace('/{([a-z]+):([^}]+)}/', '(?P<\1>\2)', $route);

        //this convert {:all?} to accept any url pass through
        $route = preg_replace('/{(:all\?)}/', '(?P<all>[a-z0-9\/-]+)', $route);

        //convert optional variable
        $route = preg_replace('/{([a-z]+)(\?)}/', '(?P<$1>[a-zA-Z0-9-]+)', $route);

        //convert optional variables with custom regex eg: {id?:\d+}
        $route = preg_replace('/{([a-z]+)(\?):([^}]+)}/','(?P<\1>\3)', $route);

        //add start and end delimiters, case insensitive flag
        $route = self::addDelimiters($route);

        // this set the variable value to be use in alias()
        self::$current_route = $route;
        self::$current_param = $params;

        self::$routes[$route] = $params;
        if ($twin_route) {
            $route = self::addDelimiters($twin_route);
            self::$routes[$route] = $params;
        }
    }

    public static function addDelimiters(string $route): string
    {
        return '/^' . $route . '$/i';
    }

    /**
     * Set an alias to a route
     * @param string
     * @return void
     */
    public static function alias($alias)
    {
        self::routeCompiler($alias, self::$raw_current_route, self::$current_param);
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
    public static function routeCompiler(string $name, string $route, $params=[])
    {
        $params['url'] = $route;
        self::$compiled_routes[$name] = $params;
    }

    /**
     * Return Compiled routes that has an alias
     * @return array
     */
    public static function compiledRoutes(): array
    {
        return self::$compiled_routes;
    }

    /**
     * Return all available routes
     * @return array
     */
    public static function getRoutes(): array
    {
        return self::$routes;
    }

    /**
     * Match the route to $routes setting $params property
     * if a route is found
     * @param string $url - The route URL
     * @return boolean true if match found else false
     */
    public static function match(string $url): bool
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
    public static function getParams(): array
    {
        return self::$params;
    }

    /**
     * Dispatch route parameter to URL
     *
     * @throws \Exception
     */
    public static function dispatch($url)
    {
        $url =  self::removeQueryString($url);

        if (self::match($url)) {
            /**
             * If route parameter is a Closure
             */
            if(isset(self::$params['closure'])){
                $closure = call_user_func(self::$params['closure']) ;
                echo $closure;
                return;
            }

            if (preg_match('/controller$/i', self::$params['controller']) == 0) {
                $controller = self::$params['controller'].'Controller';
            } else {
                $controller = self::$params['controller'];
            }

            $controller = self::convertToStudlyCaps($controller);
            $controller = self::getNamespace() . $controller;

            if (class_exists($controller)){
                $controller_class = new $controller(self::$params);
                $action = self::convertToCamelCase(self::$params['action']);

                if (preg_match('/action$/i', $action) == 0) {
                    $request = $_SERVER['REQUEST_METHOD'];
                    if (isset($_POST['_method'])){
                        $request = $_POST['_method'];
                    }
                    $user_request_method = strtoupper(self::$params['request_method']);
                    if ($request === $user_request_method
                        || $user_request_method === 'ANY'
                    ) {
                        $dispatcher = new ControllerDispatcher(static::$params);
                        $dispatcher->dispatch($controller_class, $action);
                    } else {
                        throw new \Exception("$request Method not allowed", 405);
                    }
                } else {
                    throw new \Exception("Method [$action] (in Controller [$controller] ) can't be called explicitly. Remove Action suffix instead", 500);
                }
            } else {
                throw new \Exception("Controller class [$controller] not found", 500);
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
    private static function convertToStudlyCaps($string): string
    {
        return str_replace(' ','',ucwords(str_replace('-',' ', $string)));
    }

    /**
     * convert string into Camel Case format
     * @var string
     * @return string
     */
    private static function convertToCamelCase($string): string
    {
        return lcfirst(self::convertToStudlyCaps($string));
    }

    /**
     * Remove query string from url like:
     * ?page=1&id=1...... etc.
     * @param string $url
     * @return array or string URL
     */
    protected static function removeQueryString(string $url)
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
     * Get namespace in route params to specify where it has to be called
     * @return string $namespace
     */
    protected static function getNamespace(): string
    {
        $namespace = 'App\Controllers\\';
        if (array_key_exists('namespace', self::$params)) {
            $namespace .= self::$params['namespace'] . '\\';
        }
        return $namespace;
    }
}

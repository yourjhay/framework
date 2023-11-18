<?php
declare(strict_types=1);
/**
 * Core Functions
 */
namespace Simple;

    if (!function_exists(__NAMESPACE__ . '\example'))
    {
        function example()
        {
            /**
             * to start using this function in your class, include this line outside your class:
             *  use function Simple\example;
             */
        }
    }

    if (!function_exists(__NAMESPACE__ . '\bcrypt'))
    {
        /**
         * @param string $string String to be hashed
         * @param array $option (optional) password_hash option
         * @return string
         */
        function bcrypt(string $string, $option = array()): string
        {
            if ($option) {
                return password_hash($string, PASSWORD_BCRYPT, $option);
            } else {
                return password_hash($string, PASSWORD_BCRYPT);
            }
        }
    }

    if (!function_exists(__NAMESPACE__ . '\bcrypt_verify'))
    {
        /**
         * Verify the hashed string
         * @param string $string String to be verify
         * @param string $hash - Hashed to be verify
         * @return bool
         */
        function bcrypt_verify(string $string, string $hash): bool
        {
            return password_verify($string, $hash);
        }
    }

    if (!function_exists(__NAMESPACE__ . '\url_init'))
    {
        function url_init()
        {
            $addr = array(
                '::1',
                '127.0.0.1'
            );
            $localhost = false;
            if (in_array($_SERVER['REMOTE_ADDR'], $addr))
            {
                $localhost =true;
            }
            $url = $localhost == true ? substr($_SERVER['REQUEST_URI'],1) : $_SERVER['QUERY_STRING'];
            return preg_replace('/\/*$/', '', $url);
        }
    }

    if (!function_exists(__NAMESPACE__ . '\alias'))
    {
        /**
         * @throws \Exception
         */
        function alias($alias, $param=null)
        {
           $compile_routes = Routing\Router::compiledRoutes();
            $url='';
           foreach ($compile_routes as $key => $val)
           {
                if ($alias == $key){
                    $url = preg_replace('/\{([a-z?]+)\}/', '', $val['url']);
                    if ($param!==null) {
                        $url = preg_replace('/\{([a-z?]+)\}/', $param, $val['url']);
                        if(preg_match('/\{([a-z]+):([^\}]+)\}/', $val['url'])) {
                            $url = preg_replace('/\{([a-z]+):([^\}]+)\}/', $param, $val['url']);
                        }
                    }

                return $url;
               }
           }
           throw new \Exception("Route with alias [$alias] not found", 500);
        }
    }

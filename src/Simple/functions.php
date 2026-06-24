<?php
declare(strict_types=1);
/**
 * Core Functions
 */

namespace Simple
{
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
            $url = $_SERVER['REQUEST_URI'];
            $url = parse_url($url, PHP_URL_PATH);
            return rtrim($url, '/') ?: '/';
        }
    }
}

namespace
{
    if (!function_exists('alias'))
    {
        /**
         * @throws \Exception
         */
        function alias($alias, $param=null)
        {
           $compile_routes = \Simple\Routing\Router::compiledRoutes();
            $url='';
           foreach ($compile_routes as $key => $val)
           {
                if ($alias == $key){
                    $url = preg_replace('/{([a-z?]+)}/', '', $val['url']);
                    if ($param!==null) {
                        $url = preg_replace('/{([a-z?]+)}/', $param, $val['url']);
                        if(preg_match('/{([a-z]+):([^\}]+)}/', $val['url'])) {
                            $url = preg_replace('/{([a-z]+):([^\}]+)}/', $param, $val['url']);
                        }
                    }

                return $url ?: '/';
               }
           }
           throw new \Exception("Route with alias [$alias] not found", 500);
        }
    }

    if (!function_exists('env'))
    {
        function env(string $key, mixed $default = null): mixed
        {
            $value = $_ENV[$key] ?? getenv($key);
            if ($value === false || $value === null) {
                return $default;
            }
            return match (strtolower((string) $value)) {
                'true', '(true)' => true,
                'false', '(false)' => false,
                'null', '(null)' => null,
                default => $value,
            };
        }
    }

    if (!function_exists('config'))
    {
        function config(?string $key = null, mixed $default = null): mixed
        {
            if ($key === null) {
                return null;
            }
            return \Simple\Config::get($key, $default);
        }
    }
}

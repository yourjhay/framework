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
        function bcrypt($string, $option = array())
        {
            if($option) {
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
        function bcrypt_verify($string, $hash): bool
        {
            return password_verify($string, $hash);
        }
    }

    if (!function_exists(__NAMESPACE__ . '\view'))
    {
        /**
         * Display the requested view from views folder
         * @param string $view the filename of the view
         * @param array $args array of variable to be pass on the view
         * @param string $engine Option: twig or normal
         * @return void
         */
        function view($view, $args = [], $engine = 'twig')
        {
            if($engine == 'twig') {
                return View::render($view, $args);
            } else {
                return View::renderNormal($view, $args);
            }
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
            if(in_array($_SERVER['REMOTE_ADDR'],$addr))
            {
                $localhost =true;
            }
            $url = $localhost == true ? substr($_SERVER['REQUEST_URI'],1) : $_SERVER['QUERY_STRING'];
            return $url;
        }
    }
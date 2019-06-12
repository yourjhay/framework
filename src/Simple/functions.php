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
        function bcrypt_verify($string, $hash, $method=null): bool 
        {
            return password_verify($string, $hash);
        }
    }
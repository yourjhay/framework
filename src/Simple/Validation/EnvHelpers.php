<?php

declare(strict_types=1);

namespace Simple\Validation;

class EnvHelpers
{
    public static function functionExists($functionName)
    {
        return function_exists($functionName);
    }

    public static function date($format, $timestamp = null)
    {
        if ($timestamp === null) {
            $timestamp = time();
        }

        return date($format, $timestamp);
    }

    public static function checkdnsrr($host, $type = null)
    {
        return checkdnsrr($host, $type);
    }

    public static function gethostbyname($hostname)
    {
        return gethostbyname($hostname);
    }

    public static function file_get_contents(
        $filename, $use_include_path = false, $context = null, $offset = 0, $maxlen = null
    ) {
        return file_get_contents($filename, $use_include_path, $context, $offset, $maxlen);
    }

    public static function file_exists($filename)
    {
        return file_exists($filename);
    }
}

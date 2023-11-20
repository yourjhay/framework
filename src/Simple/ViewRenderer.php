<?php

use Simple\View;

if (!function_exists('\view'))
{
    /**
     * Display the requested view from views folder
     * @param string $view the filename of the view
     * @param array $args array of variable to be pass on the view
     * @param string $engine Option: twig or normal
     * @return string
     *@throws
     */
    function view(string $view, array $args = [], string $engine = 'twig')
    {
        if ($engine == 'twig') {
            return (new View)->render($view, $args);
        } else {
            return View::renderNormal($view, $args);
        }
    }
}

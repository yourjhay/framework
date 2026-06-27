<?php

namespace Simple;

class View
{

    /**
     * Render A view
     * @param string $view - The file my dear
     * @param array $args - Data to be pass in the view
     * @param bool $html - if html only
     * @throws \Exception - if view file not found
     */
    public static function renderNormal(
        string $view,
        array $args = [],
        bool $html = true
    ) {
        extract($args, EXTR_SKIP);
        $view = self::create($view, $html);
        $file = "../app/Views/$view";
        if (is_readable($file)){
            require $file;
        } else {
            throw new \Exception("View [$file] not found!");
        }
    }

    /**
     * Create a path and replace periods with /
     * @param $view - the file name pass
     * @param $html - if plain html
     * @return string - file name
     */
    private static function create($view, $html=false)
    {
        $name = str_replace('.','/', $view);
        $paths = explode('/', $name);
        $file=null;
        foreach ($paths as $key => $path){
            if ($key >0){
                $p='/';
            } else {
                $p=null;
            }
            $file .= $p.$path;
        }
        if ($html==true){
            return $file.'.view.html';
        }

        return $file.'.view.php';

    }

    /**
     * Render A view using twig template Engine
     * @param string $template - View name
     * @param array $args - Data to be pass in the view
     * @return string
     * @throws \Twig\Error\LoaderError
     * @throws \Twig\Error\RuntimeError
     * @throws \Twig\Error\SyntaxError
     */
    public function render(string $template, array $args = []): string
    {
        $views    =  '../app/Views';
        $cache    =  '../storage/framework/cache/views';
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        if (preg_match('/^[a-zA-Z0-9.\-:]+$/', $host) !== 1) {
            $host = 'localhost';
        }
        $url      = $protocol . $host;
        $temp     = self::create($template, true);
        $loader   = new \Twig\Loader\FilesystemLoader($views);

        if (\Simple\Config::get('cache.views', false)) {
            $twig = new \Twig\Environment($loader, [
                'cache' => $cache,
                'autoescape' => 'html',
            ]);
        } else {
            $twig = new \Twig\Environment($loader, [
                'debug' => \Simple\Config::get('security.show_errors', false),
                'autoescape' => 'html',
            ]);
        }
        foreach (glob('../app/Helper/Twig/*.php') as $filename)
        {
            $class = "\App\Helper\Twig\\" . explode('.',basename($filename))[0];
            $twig->addExtension(new $class);
        }
        $twig->addExtension(new \Twig\Extension\DebugExtension());
        $twig->addGlobal('flushable', Session::getFlushable());
        $twig->addGlobal('baseurl', $url);
        $twig->addGlobal('old', Session::get('_old'));
        $twig->addFunction(new \Twig\TwigFunction('csrf_token', function () {
            return \Simple\Session::token();
        }));
        $twig->addFunction(new \Twig\TwigFunction('csrf_field', function () {
            $token = \Simple\Session::token();
            return '<input type="hidden" name="_token" value="' . htmlspecialchars($token, ENT_QUOTES, 'UTF-8') . '">';
        }, ['is_safe' => ['html']]));
        $twig->addFunction(new \Twig\TwigFunction('alias', function ($alias) {
            $routes = \Simple\Routing\BaseRouter::compiledRoutes();
            return $routes[$alias]['url'] ?? '#';
        }));
        if (Session::get('user')) {
            $twig->addGlobal('user', json_decode(Session::get('user'), true));
        }
        Session::unset('_old');
        return $twig->render($temp, $args);
    }
}

<?php

namespace Simple;
class View
{
    

    /**
     * Render A view 
     * @param string $view - The file my dear
     * @param array $args - Data to be pass in the view
     * @param  bool $html - if html only
     * @throws \Exception - if view file not found
     */
    public static function renderNormal($view, $args = [], $html = true)
    {
        extract($args, EXTR_SKIP);
        $view = self::create($view, $html);
        $file = "../app/Views$view";
        if(is_readable($file)){
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
        $name = str_replace('.','/',$view);
        $paths = explode('/',$name);
        $file=null;
        foreach($paths as $path){
            $file .= '/'.$path;
        }
        if($html==true){
            return $file.'.view.html';
        } else {
            return $file.'.view.php';
        }
    }

    /**
     * Render A view using a template Engine
     * @param string $template - View name
     * @param array $args - Data to be pass in the view
     * @return object
     */
    public static function render($template, $args = [])
    {
        $views =  '../app/Views';
        $cache =  '../simply/Cache/Views';
        $protocol = stripos($_SERVER['SERVER_PROTOCOL'],'https') === true ? 'https://' : 'http://';
        $url = $protocol . $_SERVER['HTTP_HOST'];
        $temp = self::create($template, true);
        $loader = new \Twig\Loader\FilesystemLoader($views);
        if(CACHE_VIEWS == true) {
            $twig = new \Twig\Environment($loader, [
                'cache' => $cache,
            ]);
        } else {
            $twig = new \Twig\Environment($loader);
        }
        foreach (glob('../app/Helper/Twig/*.php',GLOB_BRACE) as $filename)
        {
            $class = "\App\Helper\Twig\\" . explode('.',basename($filename))[0];
            $twig->addExtension(new $class);
        }
        $twig->addGlobal('flushable', Session::getFlushable());
        $twig->addGlobal('baseurl', $url);
        $twig->addGlobal('old', $_POST);
        $twig->addGlobal('_get', $_GET);
        $twig->addGlobal('user', json_decode(Session::getSession('user'), true));
        return $twig->render($temp, $args);
    }

}

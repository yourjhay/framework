<?php
/*----------------------------------------------------------------
|
| The Simple PHP Framework
| @reyjhonbaquirin
| *** VIEW Class ***
------------------------------------------------------------------*/
namespace Simple;

Use Simple\Session;

class View
{
    

    /**
     * Render A view 
     * @param string $view - The file my dear
     * @param array $args - Data to be pass in the view
     * @return void
     */
    public static function renderNormal($view, $args = [], $html = true)
    {
        extract($args, EXTR_SKIP);
        $view = self::create($view, $html);
        $file = "../App/Views$view";
        if(is_readable($file)){
            require $file;
        } else {
            throw new \Exception("View [$file] not found!");
        }
        
    }

    /**
     * Create a path and replace periods with /
     * @return file
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
        $views =  '../App/views';
        $cache =  '../Simply/Cache/Views';
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
        $twig->addGlobal('flushable', Session::getFlushable());
        $twig->addGlobal('baseurl', $url);
        $twig->addGlobal('old', $_POST);
        $twig->addGlobal('user', json_decode(Session::getSession('user'), true));
        return $twig->render($temp, $args);
    }

}
<?php
/*----------------------------------------------------------------
|
| The Simple PHP Framework
| @reyjhonbaquirin
| *** VIEW Class ***
------------------------------------------------------------------*/
namespace Simple;

Use Simple\Engine\BladeOne;
Use Simple\Session;

class View 
{

    /**
     * Render A view 
     * @param string $view - The file my dear
     * @param array $args - Data to be pass in the view
     * @return void
     */
    public static function renderNormal($view, $args = [], $html = false)
    {
        extract($args, EXTR_SKIP);
        $view = self::create($view, $html);
        $file = "../App/Views/$view";
        if(is_readable($file)){
            require $file;
        } else {
            throw new \Exception("View [$file] not found!");
        }
        
    }

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
     * @return void
     */
    public static function render($template, $args = [])
    {
        $views =  '../App/views';
        $cache =  '../Simply/Cache/Views';
        $blade = new BladeOne($views,$cache,BladeOne::MODE_AUTO);
        $blade->setIsCompiled(CACHE_VIEWS);
        $blade->share('flushable',isset($_SESSION['flush'])?$_SESSION['flush']:null);
        $protocol = stripos($_SERVER['SERVER_PROTOCOL'],'https') === true ? 'https://' : 'http://';
        $blade->setBaseUrl($protocol . $_SERVER['HTTP_HOST']);
        echo $blade->run($template,$args);
        Session::getFlushable();
    }

}
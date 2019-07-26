<?php
namespace Simple;

class Request
{
    /**
     * Filters a request wether a POST, GET etc..
     * @param $user_request Request Method: POST, GET, DELETE, PUT
     * @return bool
     * @throws \Exception - if Method is not allowed
     */
    public function filterRequest($user_request) 
    {
        $request = $_SERVER['REQUEST_METHOD'];
        if($request == $user_request) {
            return true;
        } else {
            throw new \Exception("$request Method not allowed");
            return false;
        }
    }

    /**
     * Get the dat from $_POST array
     * @param $data - name of the html element input
     * @return string
     */
    public function inputdata($data) 
    { 
        $file = file_get_contents("php://input");
        $file = explode("&", $file);
        for ($i = 0; $i < count($file); $i++) {
          $sub = explode('=', $file[$i]);
          if ($sub[0] == $data) {
            return utf8_decode(urldecode($sub[1]));
          }
        }
    }

    /**
     * Return data from GET, POST $_COOKIES
     * @param $data - name of the html element input
     * @return array 
     */
    public  function input($data = null)
    {
        if($data == null) {
            return $_REQUEST;
        } else {
            $file = file_get_contents("php://input");
            $file = explode("&", $file);
            for ($i = 0; $i < count($file); $i++) {
            $sub = explode('=', $file[$i]);
            if ($sub[0] == $data) {
                return utf8_decode(urldecode($sub[1]));
            }
            }
        }
    }

    /**
     * @param $url - Redirect to given URL
     */
    public static function redirect($url)
    {
        header('location: http://'.$_SERVER['HTTP_HOST'].$url,true,303);
        exit();
    }

    public function __get($name)
    {
            $file = file_get_contents("php://input");
            $file = explode("&", $file);
            for ($i = 0; $i < count($file); $i++) {
            $sub = explode('=', $file[$i]);
            if ($sub[0] == $name) {
                return utf8_decode(urldecode($sub[1]));
            }
            }
    }

    /**
     * Get the variables passed to route as parameters eg: id, name, product_id
     * @param $var Pass variable to route
     * @return string 
     */
    public function route($var)
    {
        $params = \Simple\Routing\Router::getParams();
        return $params[$var];
    }

    protected $filename;

    public function __call($name, $args)
    {
        if($name=='file') {

            if(empty($args)){
                return $_FILES;
            } else {
                $file = new FileUpload($args[0]);
                return $file;
            }
          
        }
    }

    

}
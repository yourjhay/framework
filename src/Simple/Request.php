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
        if($request == strtoupper($user_request)) {
            return true;
        } else {
            throw new \Exception("$request Method not allowed");
        }
    }

    /**
     * Return data from GET, POST $_COOKIES
     * @param $data - name of the html element input
     * @return array
     */
    public function request($key = null)
    {
        if($key == null) {
            return $_REQUEST;
        } else {
            return $_REQUEST[$key];
        }
    }

    /**
     * @param null $key
     * @return mixed
     */
    public function get($key=null)
    {
        if($key == null) {
            return $_GET;
        } else {
            return $_GET[$key];
        }
    }

    /**
     * @param null $key
     * @return mixed
     */
    public function post($key=null)
    {
        if($key == null) {
            return $_POST;
        } else {
            return $_POST[$key];
        }
    }

    /**
     * Redirects to given URL
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
        return isset($params[$var]) ? $params[$var] : null ;
    }


    /**
     * @param $name
     * @param $args
     * @throws \Exception
     */
    public function __call($name, $args)
    {
        throw new \Exception('Method '.$name.' not found');
    }


    /**
     * @param $fieldname: The name of the file upload field
     * @return FileUpload: FileUpload object
     * @throws \Exception
     */
    public function file($fieldname)
    {
        if($fieldname != null) {
            $file = new FileUpload($fieldname);
            return $file;
        }
        throw new \Exception("Please provide the name of the file upload field");
    }
}
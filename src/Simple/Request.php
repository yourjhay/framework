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
    public static function filterRequest($user_request) 
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
    public static function inputdata($data) 
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
    public static function input($data = null)
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
     * @param $url Redirect to given URL
     * @return void
     */
    public static function redirect($url)
    {
        header('location: http://'.$_SERVER['HTTP_HOST'].$url,true,303);
        exit();
    }

}
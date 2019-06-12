<?php
namespace Simple;

class Session {

    public function __construct()
    {
        if(session_status() == PHP_SESSION_NONE){
            session_start();
        }
    }

    public function getSessionId()
    {
        return session_id();
    }

    public function setSession($key , $data=[]) 
    {
        $_SESSION[$key] = $data;
    }
    
    public function getSession($key=null)
    {
        if($key!=null) {
            return isset($_SESSION[$key])?$_SESSION[$key]:null;
        } else {
            return $_SESSION;
        }
    }

    public function flashable($key, $data=[])
    {
        $key = $key.'_flash';
        $_SESSION[$key] = $data;
    }
    
    public static function getFlashable($key)
    {
        self::init();
        $keynew = $key.'_flash';
        return isset($_SESSION[$keynew])?$_SESSION[$keynew]:null;
    }

    public function unsetSession($key)
    {
        if(isset($_SESSION[$key])) {
            unset($_SESSION[$key]);
        } 
    }
    
}
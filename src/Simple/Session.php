<?php
namespace Simple;

class Session 
{

    public function __construct()
    {
        self::init();
    }

    protected static function init()
    {
        if(session_status() == PHP_SESSION_NONE){
            session_start();
        }
    }

    public static function getSessionId()
    {
        return session_id();
    }

    public static function setSession($key , $data=[]) 
    {
        $_SESSION[$key] = $data;
    }
    
    public static function getSession($key=null)
    {
        if($key!=null) {
            return isset($_SESSION[$key])?$_SESSION[$key]:null;
        } else {
            return $_SESSION;
        }
    }

    public static function flush($message)
    {
        if(!isset($_SESSION['flush'])) {
            $_SESSION['flush'] = [];
        }
        $_SESSION['flush'][] = $message;
    }
    
    public static function getFlushable()
    {
        self::init();
        if(isset($_SESSION['flush'])) {
            $flash = $_SESSION['flush'];
            unset($_SESSION['flush']);
            return $flash;
        }
    }

    public static function unsetSession($key)
    {
        if(isset($_SESSION[$key])) {
            unset($_SESSION[$key]);
        } 
    }

    public static function destroy()
    {
        // Unset all of the session variables.
        if(isset($_SESSION)) {
        
            $_SESSION = array();

            // If it's desired to kill the session, also delete the session cookie.
            // Note: This will destroy the session, and not just the session data!
            if (ini_get("session.use_cookies")) {
                $params = session_get_cookie_params();
                setcookie(session_name(), '', time() - 42000,
                    $params["path"], $params["domain"],
                    $params["secure"], $params["httponly"]
                );
            }

            // Finally, destroy the session.
            session_destroy();  
        }       
    }
    
}
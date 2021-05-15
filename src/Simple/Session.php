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
        if (session_status() == PHP_SESSION_NONE){
            session_start();
        }
    }

    /**
     * return session ID
     * @return string
     */
    public static function getSessionId()
    {
        return session_id();
    }

    /**
     *  Set a new session
     * @param $key Session Key
     * @param array $data Session data associated with the key
     */
    public static function setSession($key , $data=[]) 
    {
        $_SESSION[$key] = $data;
    }

    /**
     * Return the session data
     * @param null $key Session to be return
     * @return mixed|null
     */
    public static function getSession($key=null)
    {
        if ($key!=null) {
            return isset($_SESSION[$key]) ? $_SESSION[$key] : null;
        } else {
            return $_SESSION;
        }
    }

    /**
     * Set a flush message on the session
     * @param $message
     */
    public static function flush($message)
    {
        if (!isset($_SESSION['flush'])) {
            $_SESSION['flush'] = [];
        }
        $_SESSION['_old'] = $_POST;
        $_SESSION['flush'][] = $message;
    }

    /**
     *  Return the flushable message from session
     * @return mixed
     */
    public static function getFlushable()
    {
        self::init();
        if (isset($_SESSION['flush'])) {
            $flash = $_SESSION['flush'];
            unset($_SESSION['flush']);
            return $flash[0];
        }
        return null;
    }

    /**
     *  Unset the current session
     * @param $key Session key
     */
    public static function unsetSession($key)
    {
        if (isset($_SESSION[$key])) {
            unset($_SESSION[$key]);
        } 
    }

    /**
     *  Destroy all the save session
     */
    public static function destroy()
    {
        // Unset all of the session variables.
        if (isset($_SESSION)) {
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
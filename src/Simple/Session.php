<?php

namespace Simple;

class Session
{

    public function __construct()
    {
        self::init();
    }

    /**
     * Start new session if none
     * @return void
     */
    public static function init()
    {
        if (session_status() == PHP_SESSION_NONE){
            ini_set('session.cookie_samesite', 'Lax');
            session_start();
        }
        if (!isset($_SESSION['_old'])) {
            $_SESSION['_old'] = $_POST;
        }
    }

    /**
     * return session ID
     * @return string
     */
    public static function id(): string
    {
        return session_id();
    }

    public static function token(): string
    {
        self::init();
        if (empty($_SESSION['_token'])) {
            $_SESSION['_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['_token'];
    }

    /**
     * Set a new session
     * @param string $key Session Key
     * @param array|string $data Session data associated with the key
     */
    public static function set(string $key, $data=[])
    {
        $_SESSION[$key] = $data;
    }

    /**
     * Return the session data
     * @param string|null $key Session to be return
     * @return string|$_SESSION
     */
    public static function get(string $key = null)
    {
        if ($key!=null) {
            return $_SESSION[$key] ?? null;
        }
        
        return $_SESSION;
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
        $_SESSION['flush'][] = $message;
    }

    /**
     * Preserve current input data for repopulating forms
     * @param array|null $data
     */
    public static function preserveInput(array $data = null)
    {
        $_SESSION['_old'] = $data ?? $_POST;
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
     * @param string $key Session key
     */
    public static function unset(string $key)
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

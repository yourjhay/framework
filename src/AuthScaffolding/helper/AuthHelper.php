<?php 
namespace App\Helper\Auth;

use App\Models\User;
use Simple\Session;
use function Simple\bcrypt_verify;

class AuthHelper
{
    public static function attempt($data)
    {
        $user =  User::findByEmail($data['email']);
        if($user) {
            if(bcrypt_verify($data['password'], $user->password_hash))
            {
                Session::setSession('user',json_encode($user));
                return true;
            }
        }

        return false;
    }

    public static function user()
    {
        return Session::getSession('user');
    }

    public static function destroy()
    {
        // Unset all of the session variables.
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
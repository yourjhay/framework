<?php 
namespace App\Helper\Auth;

use App\Models\User;
use Simple\Session;
use function Simple\bcrypt_verify;

class AuthHelper
{
    /**
     * @param array $data - array containing email and password
     * @return bool
     */
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

    /**
     * @return mixed|null
     */
    public static function user()
    {
        return Session::getSession('user');
    }

    /**
     * Destroys a Session
     */
    public static function destroy()
    {
        Session::destroy();
    }

}
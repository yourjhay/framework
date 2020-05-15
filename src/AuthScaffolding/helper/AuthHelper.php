<?php 
namespace App\Helper\Auth;

use App\Models\User;
use Simple\Session;

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
            if(password_verify($data['password'], $user->password_hash))
            {
                $user_data = json_encode($user);
                Session::setSession('user',$user_data);
                return true;
            }
            return false;
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
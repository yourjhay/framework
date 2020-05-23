<?php

namespace App\Controllers\Auth;

use App\Controllers\Controller;
use Simple\{Request, Session};
use App\Models\User;
use App\Helper\Validation\Validator as validate;
use App\Helper\Auth\AuthHelper as auth;
use function Simple\bcrypt;

class SignupController extends Controller
{
    /**
     * @return object|void
     */
    public function signup()
    {
        return view('auth.signup');
    }

    /**
     * @param Request $request
     * @return object|void
     * @throws \Exception
     */
    public function signupNew(Request $request)
    {
        $v = new validate;
        $v->validation_rules(array(
            'name' => 'required|valid_name|min_len,6',
            'email' => 'required|valid_email|unique,users',
            'password' => 'required|min_len,6|alpha_numeric'
        ));
        $validated = $v->run($request->post());

        if($validated) {
            $user = new User();
            $user->name = $request->name;
            $user->email = $request->email;
            $user->password_hash = bcrypt($request->password);
            $user->save();

            if(auth::attempt($request->post())) {
                $request->redirect('/');
            } else {
                $request->redirect('/auth');
            }
        } else {
            Session::flush($v->get_errors_array());            
            return view('auth.signup');
        }
    }

    /*
     * @return view
     */
    public function success()
    {
        return view('signup.index');
    }
}
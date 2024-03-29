<?php

namespace App\Controllers\Auth;

use App\Helper\{
    Auth\AuthHelper as auth,
    Validation\Validator as validate
};
use App\Controllers\Controller;
use App\Models\User;
use Simple\{Request, Session};


class SignupController extends Controller
{
    /**
     * @return string
     */
    public function signup(): string
    {
        return view('auth.signup');
    }

    /**
     * @param Request $request
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

        if ($validated) {
            $user = new User();
            $user->name = $request->name;
            $user->email = $request->email;
            $user->password_hash = bcrypt($request->password);
            $user->save();

            if (auth::attempt($request->post())) {
                $request->redirect('/');
            } else {
                $request->redirect('/auth');
            }
        } else {
            Session::flush($v->get_errors_array());
            $request->redirect(alias('auth.signup'));
        }
    }

    /*
     * @return view
     */
    public function success(): string
    {
        return view('signup.index');
    }
}

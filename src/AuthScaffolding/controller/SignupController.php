<?php
namespace App\Controllers\Auth;

Use App\Controllers\Controller;
Use Simple\View as view;
Use Simple\Request as r;
Use App\Models\User;
Use Simple\Session;

class SignupController extends Controller
{

    public function signup()
    {
        view::render('auth.signup');
    }

    public function signupNew()
    {
        $data = r::input();
        $user = new User();
        $user->save($data);

        r::redirect('/auth/index');
    }

    public function success()
    {
     
        view::render('signup.index');
    }

}
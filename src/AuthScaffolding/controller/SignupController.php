<?php
namespace App\Controllers\Auth;

Use App\Controllers\Controller;
Use Simple\View as view;
Use Simple\Request as r;
Use App\Models\User;
Use Simple\Session;
Use App\Helper\Validation\Validator as validate;

class SignupController extends Controller
{

    public function signup()
    {
        view::render('auth.signup');
    }

    public function signupNew()
    {
        r::filterRequest('POST');
        $v = new validate;
        $v->validation_rules(array(
            'name' => 'required|valid_name|min_len,6',
            'email' => 'required|valid_email',
            'password' => 'required|min_len,6|alpha_numeric'
        ));
        $validated = $v->run(r::input());
        if($validated) {
            $user = new User();
            $user->save(r::input());
            r::redirect('/auth/index');
        } else {
            Session::flush($v->get_errors_array());            
            view::render('auth.signup');
        }
        
    }

    public function success()
    {
        view::render('signup.index');
    }

}
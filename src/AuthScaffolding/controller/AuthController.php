<?php
namespace App\Controllers\Auth;

Use App\Controllers\Controller;
Use Simple\View as view;
Use Simple\Request as r;
Use Simple\Session;
use App\Helper\Auth\AuthHelper as auth;

class AuthController extends Controller
{
    protected static $destination = '/';

    public function index()
    {
        if(Auth::user()) {
            r::redirect(self::$destination);
        } else {
            view::render('auth.index');
        }
    }

    public function authenticate()
    {
        if(auth::attempt(r::input())) {
            r::redirect(self::$destination);
        } else {
            Session::flush('Invalid Username or Password');
            view::render('auth.index');
        }
    }

    public function logoutAction()
    {
        Auth::destroy();
        r::redirect('/auth/index');
    }
}
<?php
namespace App\Controllers\Auth;

Use App\Controllers\Controller;
Use function Simple\view;
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
            return view('auth.index');
        }
    }

    public function authenticate()
    {
        if(auth::attempt(r::input())) {
            r::redirect(self::$destination);
        } else {
            Session::flush('Invalid Username or Password');
            return view('auth.index');
        }
    }

    public function logoutAction()
    {
        Auth::destroy();
        r::redirect('/auth/index');
    }
}
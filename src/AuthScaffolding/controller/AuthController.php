<?php
namespace App\Controllers\Auth;

Use App\Controllers\Controller;
Use Simple\Request;
Use Simple\Session;
use App\Helper\Auth\AuthHelper as auth;

class AuthController extends Controller
{
    /**
     * @var $destination url after successfully login
     */
    protected static $destination = '/';

    /**
     * @param Request $request
     */
    public function index(Request $request)
    {
        if(Auth::user()) {
            $request->redirect(self::$destination);
        } else {
            return view('auth.index');
        }
    }

    /**
     * @param Request $request
     * @return object|void
     */
    public function authenticate(Request $request)
    {
        if(auth::attempt($request->input())) {
            $request->redirect(self::$destination);
        } else {
            Session::flush('Invalid Username or Password');
            return view('auth.index');
        }
    }

    /**
     * logout a currently login user
     * @return void
     */
    public function logoutAction()
    {
        Auth::destroy();
        Request::redirect('/auth/index');
    }
}
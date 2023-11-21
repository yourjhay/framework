<?php

namespace App\Controllers\Auth;

use App\Controllers\Controller;
use Simple\{Request, Session};
use App\Helper\Auth\AuthHelper as auth;

class AuthController extends Controller
{
    /**
     * @var string $destination - url after successfully login
     */
    protected static string $destination = '/';

    /**
     * @param Request $request
     * @return string
     */
    public function index(Request $request): string
    {
        if (Auth::user()) {
            $request->redirect(self::$destination);
        }
        return view('auth.index');
    }

    /**
     * @param Request $request
     * @return string
     * @throws \Exception
     */
    public function authenticate(Request $request): string
    {
        if (auth::attempt($request->post())) {
           $request->redirect(self::$destination);
        }
        Session::flush('Invalid Username or Password');
        return view('auth.index');
    }

    /**
     * logout a currently login user
     * @return void
     */
    public function logoutAction()
    {
        Auth::destroy();
        Request::redirect('/auth');
    }
}

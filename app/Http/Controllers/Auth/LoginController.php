<?php

namespace App\Http\Controllers\Auth;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Http\Request;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;

class LoginController extends BaseController
{
    use AuthenticatesUsers, AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    /**
     * Where to redirect users after login.
     *
     * @var string
     */
    protected $redirectTo = '/';

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('guest')->except('logout');
    }

    protected function authenticated(\Illuminate\Http\Request $request, $user)
    {
        if ($user instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && ! $user->hasVerifiedEmail()) {
            return redirect()
                ->route('verification.notice')
                ->with('status', 'verification-link-sent');
        }

        // Інакше — стандартний редірект після логіну
        return redirect()->intended($this->redirectTo);
    }


    public function showLoginForm()
    {
        return view('auth.login');
    }
}

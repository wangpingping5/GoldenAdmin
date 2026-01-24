<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Providers\RouteServiceProvider;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use \Illuminate\Http\Request;

class LoginController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Login Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles authenticating users for the application and
    | redirecting them to your home screen. The controller uses a trait
    | to conveniently provide its functionality to your applications.
    |
    */

    use AuthenticatesUsers;

    /**
     * Where to redirect users after login.
     *
     * @var string
     */
    protected $redirectTo = '/home';

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('guest')->except('logout');
    }

    public function credentials(Request $request)
    {
        return array_merge($request->only($this->username(), 'password'), ['status' => 'Active']);
    }

    public function username()
    {
        return 'username';
    }

    public function attemptLogin(Request $request)
    {
        $attmpt =  $this->guard()->attempt(
            $this->credentials($request), $request->filled('remember')
        );
        $siteMaintence = env('MAINTENANCE', 0);

        if( $siteMaintence==1 ) 
        {
            \Auth::logout();
            return redirect()->to('backend/login')->withErrors([trans('SiteMaintenance')]);
        }
        if ($attmpt)
        {
            $user = $this->guard()->user();
            if ($user->role_id < \App\Models\User::ROLE_OPERATOR)
            {
                $this->guard()->logout();
                $attmpt = false;
            }
        }
        return $attmpt;
    }
    
}

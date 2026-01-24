<?php

namespace App\Http\Middleware;

use App\Providers\RouteServiceProvider;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RedirectIfAuthenticated
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  string|null  ...$guards
     * @return mixed
     */
    protected $auth = null;
    public function __construct(\Illuminate\Contracts\Auth\Guard $auth)
    {
        $this->auth = $auth;
    }
    public function handle(Request $request, Closure $next, ...$guards)
    {
        // $guards = empty($guards) ? [null] : $guards;

        // foreach ($guards as $guard) {
        //     if (Auth::guard($guard)->check()) {
        //         return redirect(RouteServiceProvider::HOME);
        //     }
        // }

        // return $next($request);
        if( $this->auth->check() ) 
        {
            if($request->is(config('app.slug') . '*')){
                return redirect()->to(argon_route('dashboard'));
            }else{
                return redirect()->to(argon_route('dashboard'));
            }
        }
        return $next($request);
    }
}

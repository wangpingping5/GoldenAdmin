<?php

namespace App\Http\Middleware;

use Illuminate\Auth\Middleware\Authenticate as Middleware;

class Authenticate extends Middleware
{
    /**
     * Get the path the user should be redirected to when they are not authenticated.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return string|null
     */
    // protected function redirectTo($request)
    // {
    //     if (! $request->expectsJson()) {
    //         return route('login');
    //     }
    // }
    protected $auth = null;
    public function __construct(\Illuminate\Contracts\Auth\Guard $auth)
    {
        $this->auth = $auth;
    }
    public function handle($request, \Closure $next)
    {
        // return redirect()->guest('/');
        if( $this->auth->guest() ) 
        {
            if( $request->ajax() || $request->wantsJson() ) 
            {
                return response('Unauthorized.', 401);
            }
            else if( !$request->is('api*') ) 
            {
                // if( $request->is(config('app.slug') . '*') )
                // {
                //     return redirect()->guest(argon_route('auth.login'));
                // }
                return redirect()->guest(argon_route('auth.login')); 
            }
        }
        else if( !$request->is('api*') ) 
        {
            // $backendtheme = ['slot', 'backend'];
            // if (!in_array(config('app.admurl'), $backendtheme))
            // {
            //     if( $request->is(config('app.slug') . '*') && !$this->auth->user()->hasPermission('access.admin.panel')) 
            //     {
            //         return redirect()->to(argon_route('dashboard'));
            //         // return redirect()->to('/');
            //     }
            // }
            // else
            // {
                if( $request->is(config('app.admurl') . '*') && !$this->auth->user()->hasPermission('access.admin.panel') ) 
                {
                    return redirect()->to(argon_route('dashboard'));
                }
            // }
        }
        return $next($request);
    }
}

<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class AdminOnly
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        if (auth()->user()->role_id != \App\Models\User::ROLE_ADMIN)
        {
            return response()->redirect()->withErrors(['No Permission']);
        }
        return $next($request);
    }
}

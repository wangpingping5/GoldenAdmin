<?php

namespace App\Http\Middleware;

use App\Providers\RouteServiceProvider;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;

class authToken
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  string|null  ...$guards
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        $api_token = $request->header('authToken');
        if( !$api_token ) 
        {
            $response = \Response::json(['Error' => 1, 'Description' => 'authToken header is required'], 401, []);
            $response->header('Content-Type', 'application/json');
            return $response;
        }
        $user = User::where(['api_token'=> $api_token,'role_id' => User::ROLE_OPERATOR])->first();
        if( !$user) 
        {
            $response = \Response::json(['Error' => 2, 'Description' => 'authToken is invalid'], 401, []);
            $response->header('Content-Type', 'application/json');
            return $response;
        }

        if ($user->status !== 'Active')
        {
            $response = \Response::json(['Error' => 3, 'Description' => 'this operator is not activated'], 401, []);
            $response->header('Content-Type', 'application/json');
            return $response;
        }

        $request->merge(array("operator" => $user));


        return $next($request);
    }
}

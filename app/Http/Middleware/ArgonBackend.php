<?php 
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class ArgonBackend
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
        $slug = $request->segment(1);
        $site = \App\Models\WebSite::where('backend', $request->root())->get(); //->where('backend',$slug)
        if ($site->count() == 0)
        {
            abort(404);
        }
        return $next($request);
    }
}


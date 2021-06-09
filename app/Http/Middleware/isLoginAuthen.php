<?php
namespace App\Http\Middleware;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
class isLoginAuthen
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
        // if (!Auth::guard('web')->check() )
        if (!Auth::check() && !Auth::viaRemember())
        {
            if ($request->ajax() || $request->wantsJson()) {
                return response('Unauthorized.', 401);
            }else {
                return redirect()->route('ShowFormLogin');// return view('auth.login');
            }
        }
        return $next($request);
    }
}

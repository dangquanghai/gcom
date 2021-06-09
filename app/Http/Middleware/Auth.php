<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\FunctionPermissionAction;
use Illuminate\Support\Facades\Gate;
use App\Models\Action;
class Auth
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
		if(Auth()->user()->is_admin)//nếu là supper admin thì truy cập mọi nơi
			return $next($request); 
			
        // lấy tất cả các route có phân quyền.
		$routesPermissions = FunctionPermissionAction::getRoutesPermissions();
		if (!empty($routesPermissions))
		{
			foreach ($routesPermissions as $key => $route) {
				if (!isset($route['uri']) || !isset($route['permission']) || !isset($route['methods'])) {
					continue;
				}

				// nếu tìm thấy route ...
				if ($request->is($route['uri']) && in_array($request->method(), $route['methods'])) {
					// Kiểm tra user có quyền truy cập không
					$a = Action::where('uri',$route['uri'])->first();
					if (!isset($a) || !Auth()->user()->group->hasPermissionActionTo($a->functions?$a->functions->alias:'',$a->action_alias)) {
						if ($request->ajax() || $request->wantsJson()) {
							return response('unauthorized', 401);
						} else {
							return redirect()->route('home')->with(['error'=>'Xin lỗi! Bạn chưa được cấp quyền.']);
						}
					}
				}
				
			}
		}
        return $next($request);
    }
}

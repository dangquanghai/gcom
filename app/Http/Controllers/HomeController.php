<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Controllers\SYS\SysController;
use DB;
use Auth;

class HomeController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function index()
    {
         
        $User = auth()->user();
        $UserID = $User->id;
        $IsAdmin= $User->is_admin;

        $sql = " select ft.name 
        from sys_roles r inner join sys_role_members rmb on r.id = rmb.role_id
        inner join sys_role_permissions rpmt on r.id = rpmt.role_id
        inner join sys_actions ac on rpmt.action_id = ac.id
        inner join sys_functions ft on ac.function_id = ft.id
        inner join sys_modules mdl on ft.modulle_id = mdl.id
        inner join ms_employees epl on rmb.emp_id = epl.id
        where rmb.emp_id = $UserID and ac.action_no = 1 " ;

        $ds=  DB::connection('mysql')->select($sql);
      
        return view('newhome',compact(['ds','IsAdmin']));


        
    }
    
}

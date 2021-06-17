<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Controllers\SYS\SysController;

use DB;
use Auth;

class HomeController extends SysController
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
         
        $IsAdmin =0;
        $User = auth()->user();
        $UserID = $User->id;
       

        $sql = "  select name from sys_functions where name not in 
        (
        select ft.name 
        from sys_roles r inner join sys_role_members rmb on r.id = rmb.role_id
        inner join sys_role_permissions rpmt on r.id = rpmt.role_id
        inner join sys_actions ac on rpmt.action_id = ac.id
        inner join sys_functions ft on ac.function_id = ft.id
        inner join sys_modules mdl on ft.modulle_id = mdl.id
        inner join ms_employees epl on rmb.emp_id = epl.id
        where rmb.emp_id = $UserID and ac.action_no = 1 and  rpmt.is_active =1 )" ;

        $ds=  DB::connection('mysql')->select($sql);

        $sql = " select r.is_admin  from sys_roles r inner join sys_role_members rmb on r.id = rmb.role_id 
        where rmb.emp_id = $UserID  ";
        $dx=  DB::connection('mysql')->select($sql);
        foreach($dx as $d) { $IsAdmin = $this->iif(is_null($d->is_admin),0,$d->is_admin ); }

        return view('newhome',compact(['ds','IsAdmin']));
    }
    
}

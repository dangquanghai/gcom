<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use App\Http\Controllers\SYS\SysController;
use DB;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Reader\IReadFilter;

use Validator;
use DateTime;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Input;

class RolePermissionController extends SysController
{
    public function LoadRolePermission()
    {
        $MdlID = 1;
        $RolID = 1;

        $sql =" select id,name from sys_modules  ";
        $dsMdl =  DB::connection('mysql')->select($sql);
        
        $sql =" select id,name from sys_functions fnc where  fnc.modulle_id  = $MdlID ";
        $dsFnc =  DB::connection('mysql')->select($sql);
 
        $sql =" select id,name from sys_roles ";
        $dsRol=  DB::connection('mysql')->select($sql);

        $sql =" select e.name  from ms_employees e INNER join sys_role_members rmb on e.id =rmb.emp_id
        where rmb.role_id = $RolID ";
        $dsMember =  DB::connection('mysql')->select($sql);

        
        $sql ="  select rpms.id, rpms.role_id,rpms.action_id, a.name, rpms.is_active
        from sys_actions a inner join sys_role_permissions rpms on a.id = rpms.action_id
        inner join sys_functions fnct on a.function_id = fnct.id
        where 1 = 2 ";
        $RolePermission =  DB::connection('mysql')->select($sql);   

        return view('Admin.RolePermission',compact(['dsMdl','dsFnc','dsRol','RolID','dsMember','RolePermission']));
    }
    // -----------------------------------------------------------------------------
    public function LoadFunction($ModuleID)
    {
       // dd($ModuleID);
        $sql =" select id,name from sys_functions where modulle_id = $ModuleID ";
        $dsFnc =  DB::connection('mysql')->select($sql);
        //  dd( $dsFnc);
        return view('Admin.FunctionInModule-ajax',compact(['dsFnc']));
    }

    // -----------------------------------------------------------------------------
    public function LoadMember($RolID)
    {
        $sql =" select e.id,e.name from ms_employees e inner join sys_role_members  rmb on e.id = rmb.emp_id
        where rmb.role_id = $RolID ";
        $dsMb =  DB::connection('mysql')->select($sql);
        return view('Admin.MemberInRole-ajax',compact(['dsMb']));
    }
    // -----------------------------------------------------------------------------
    public function InsertRestActionsOfFunctionToRolePermission($RolID,$FncID)
    {
        $sql ="  insert into sys_role_permissions(role_id,action_id)
         select $RolID, id from sys_actions  a where a.function_id  = $FncID  and a.id not in
        ( select a.id
        from sys_role_permissions rps inner join sys_actions a on rps.action_id = a.id
        inner join sys_functions fnc on a.function_id = fnc.id
        where fnc.id =  $FncID  and rps.role_id = $RolID) ";
        DB::connection('mysql')->select($sql);   
    }
    // -----------------------------------------------------------------------------
    public function LoadActionsInRolePermissions( $ModuleID, $RolID ,$FncID)
    {
        $this->InsertRestActionsOfFunctionToRolePermission($RolID,$FncID);
        
        $sql ="  select rpms.id, rpms.role_id,rpms.action_id, a.name, rpms.is_active
        from sys_actions a inner join sys_role_permissions rpms on a.id = rpms.action_id
        inner join sys_functions fnct on a.function_id = fnct.id
        where  fnct.id = $FncID and rpms.role_id   = $RolID";
        $dsRolePermissions =  DB::connection('mysql')->select($sql);   
        //dd($dsRolePermissions);
        return view('Admin.RolePermissions-ajax',compact(['dsRolePermissions']));
    }
    // -----------------------------------------------------------------------------
    public function UpdatePerMission( $ID, $Active )
    {
       //dd('Có vào đây không');
        $sql =" update sys_role_permissions set is_active =  $Active  where id =$ID ";
        DB::connection('mysql')->select($sql);  
    }
}

<?php

namespace App\Models\SYS;

use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Route;
use App\Models\FunctionPermissionAction;
use App\Models\Action;
use App\Models\Functions;

class Group extends Model
{
    use HasFactory;

    protected $table = 'sys_roles';
    protected $fillable = ['id','name','is_admin'];
    public $timestamps = false;

    public function users()
    {
        return $this->hasMany('App\Models\SYS\User', 'role_id');
    }

    
    public function funPermissionAction()
    {
        return $this->hasMany('App\Models\SYS\FunctionPermissionAction','role_id');
    }
    /**
     *kiểm tra group user có function nào và action nào
    */
    public function hasPermissionActionTo($function_name,$action_name)
    {
        $defautPermission = FunctionPermissionAction::getStaffPermissions();
        if(in_array($function_alias,$defautPermission))
            return true;
        $f = Functions::where('name',$function_name)->first();
        $a = Action::where('name',$action_name)->first();
        if(!$f || !$a)return false;
        return (boolean) $this->funPermissionAction()->where('function_id',$f->id)->where('action_id',$a->id)->count();
	}
}

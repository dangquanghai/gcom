<?php

namespace App\Models\SYS;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Function extends Model
{
    use HasFactory;

    protected $table = 'sys_functions';
    protected $fillable = ['id','name','url','module_id'];
    public $timestamps = false;

    public function actions()
    {
        return $this->hasMany('App\Models\SYS\Action','function_id');
    }
    public function module()
	{
		return $this->belongsTo('App\Models\SYS\Module','module_id');
	}
}

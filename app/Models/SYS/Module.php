<?php

namespace App\Models\SYS;

use Illuminate\Database\Eloquent\Model;

class Module extends Model
{
    protected $table = 'sys_modules';
    protected $fillable = ['id','name','alias'];
    public $timestamps = false;

    public function functions()
    {
        return $this->hasMany('App\Models\Functions');
    }
}

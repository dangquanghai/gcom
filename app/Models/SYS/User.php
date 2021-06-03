<?php

namespace App\Models\SYS;

use Illuminate\Database\Eloquent\Model;

class User extends Authenticatable
{
    //use Hasfactory;
    protected $table ='ms_employees';
    protected $fillable = ['name','email'];
   // public $timestamps =true;
    protected $hidden = [
        'password', 'remember_token',
    ];
  
}

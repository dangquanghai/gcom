<?php

namespace App\Models\SYS;

use Illuminate\Notifications\Notifiable;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Foundation\Auth\User as Authenticatable;

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

<?php

namespace App\Models\PRD;

use Illuminate\Database\Eloquent\Model;

class ProductLifeCircle extends Model
{
     //
     protected $table ='prd_product_lifecircle';
     protected $fillable = ['id','name'];
     public $timestamps = false;

     public function produtcs()// Khai báo mối quan hệ với model Product
     {
         return $this->hasmany('App\Models\prd\product','life_circle','id');
     }
}

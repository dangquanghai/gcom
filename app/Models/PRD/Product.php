<?php

namespace App\Models\PRD;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
   //use Hasfactory;
   protected $table ='prd_products';
   protected $fillable = ['id','sku','name','width','height','length','url_img','life_circle','description','group_id'];
   public $timestamps =false;

   public function LifeCircle()// Khai báo mối quan hệ với model Product
    {
     return $this->belongto('App\Models\prd\ProductLifeCircle');
    }

    public function Group()// Khai báo mối quan hệ với model Product
    {
     return $this->belongto('App\Models\prd\ProductGroup');
    }
}

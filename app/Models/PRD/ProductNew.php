<?php

namespace App\Models\PRD;

use Illuminate\Database\Eloquent\Model;

class ProductNew extends Model
{
    //use Hasfactory;
    protected $table ='prd_product';
    protected $fillable = ['id','product_sku','title','width','height','length'];
    public $timestamps =false;


     public function LifeCircle()// Khai báo mối quan hệ với model Product
    {
     return $this->belongto('App\Models\prd\ProductLifeCircle');
    }

    public function Group()// Khai báo mối quan hệ với model Product
    {
     return $this->belongto('App\Models\prd\ProductGroup');
    }
    
    public function asin()// Khai báo mối quan hệ với asin
    {
      return $this->hasmany('App\Models\Sales\Asin','product_id','id');
    }

}

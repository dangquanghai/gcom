<?php

namespace App\Models\PRD;

use Illuminate\Database\Eloquent\Model;

class ProductNew extends Model
{
    //use Hasfactory;
    protected $table ='prd_product';
    protected $fillable = ['id','product_sku','title','width','height','length'];
    public $timestamps =false;
    
}

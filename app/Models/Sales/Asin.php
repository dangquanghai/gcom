<?php

namespace App\Models\Sales;

use Illuminate\Database\Eloquent\Model;

class Asin extends Model
{
     //use Hasfactory;
  protected $table ='sal_propduct_asins';
  protected $fillable = ['id','product_id','market_place','store_id','asin'];
  public $timestamps =false;
 
}

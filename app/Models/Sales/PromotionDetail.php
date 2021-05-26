<?php

namespace App\Models\Sales;

use Illuminate\Database\Eloquent\Model;

class PromotionDetail extends Model
{
 // use Hasfactory;
  protected $table ='sal_promotions_dt';
  protected $fillable = ['id','promotion_id','product_id','per_funding','funding','unit_sold','	amount_spent','revenue'];
  public $timestamps =false;

  public function Promotion()// Khai báo mối quan hệ với model promotion
    {
     return $this->belongto('App\Models\Sales\Promotion');
    }

    public function Product()
    {
        return $this->belongsTo('App\Models\inv\ProductMew','product_id');
    }
}

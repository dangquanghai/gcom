<?php

namespace App\Models\Sales;

use Illuminate\Database\Eloquent\Model;

class PromotionDetail extends Model
{
     //use Hasfactory;
  protected $table ='sal_promotions_dt';
  protected $fillable = ['id','promotion_id','asin','sku','per_funding','funding','unit_sold','	amount_spent','revenue'];
  public $timestamps =false;

  public function PromotionMaster()// Khai báo mối quan hệ với model promotion
    {
     return $this->belongto('App\Models\Sales\Promotion');
    }
}

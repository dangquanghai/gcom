<?php

namespace App\Models\Sales;

use Illuminate\Database\Eloquent\Model;

class Promotion extends Model
{
  //use Hasfactory;
  protected $table ='sal_promotions';
  protected $fillable = ['id','promotion_no','promotion_type','promotion_status','from_date','to_date','channel'];
  public $timestamps =false;

  public function PromotionDetail()// Khai báo mối quan hệ với model Promtotipn Detail
  {
    return $this->hasmany('App\Models\Sales\PromotionDetail','promotion_id');
  }
 
}

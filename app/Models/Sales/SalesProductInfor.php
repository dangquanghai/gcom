<?php

namespace App\Models\Sales;
use Illuminate\Database\Eloquent\Model;
class SalesProductInfor extends Model
{
   //use Hasfactory;
   protected $table ='sal_product_informations';
   protected $fillable = ['id','sku ','per_deposit','per_full_payment','per_rev_split_for_partner',
   'con20_capacity','exw_vn','fob_vn','fob_cn','fob_us','cosg_est','per_mkt','per_promotion',
   'per_return','per_duty','per_wh_fee','per_handing_fee','shiping_fee_est','retail_price',
   'per_wholesales_price_min','per_wholesales_price_max'];
   public $timestamps =false;
}

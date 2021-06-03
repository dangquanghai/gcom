<?php

namespace App\Http\Controllers\SYS;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use DB;
class SysController extends Controller
{
    public function iif($condition, $true, $false)
    {
     return ($condition?$true:$false);
    }
    // --------------------------------------------------------------
    public function IsExist($sConnection,$sql)
    {
        $Result = false;
        $MyCount = 0;
        $ds= DB::connection($sConnection)->select($sql);
        foreach($ds as $d) {$MyCount  = $this->iif(is_null($d->MyCount),0,$d->MyCount);    }
        if($MyCount==0){ $Result = false;}
        else{$Result=true;}

        return $Result;
    }
    // --------------------------------------------------------------
    public function GetFirstDateOfMonth($TheYear,$TheMonth)
    {
     // $s = date("Y",strtotime($TheDate)) .'-' .  date("m",strtotime($TheDate)) .'-01';
      $s = $TheYear . '-'. $TheMonth .'-01';
      return date("Y-m-d",strtotime( $s));
    }
    // --------------------------------------------------------------
    public function left($str, $length)
    {
      return substr($str, 0, $length);
    }
     // --------------------------------------------------------------
     public function GetProductIdFromSku($Sku)
     {
      $product_id = 0;
      $sql = "select id from prd_product where product_sku = '$Sku' ";
      $ds= DB::connection('mysql')->select($sql);
      foreach($ds as $d)  { $product_id = $this->iif(is_null($d->id),0,$d->id); }

      return  $product_id;

     }
       // --------------------------------------------------------------
       public function GetProductIdFromAsin($Asin,$MarketPlace)
       {
        $product_id = 0;
        $sql = "select p.id from prd_product p
        inner join sal_product_asins pas on p.id = pas.product_id
        where pas.market_place  = $MarketPlace
        and pas.asin = '$Asin' ";
        $ds= DB::connection('mysql')->select($sql);
        foreach($ds as $d)  { $product_id = $this->iif(is_null($d->id),0,$d->id); }
  
        return  $product_id;
  
       }
}

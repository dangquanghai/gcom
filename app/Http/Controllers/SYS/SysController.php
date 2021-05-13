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
}

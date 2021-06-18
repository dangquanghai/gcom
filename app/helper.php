<?php

function getList($Items,$ItemDefault)
{
    $Result = '';
    if($Items)
    {
        foreach($Items as $Item)
        {
            if($Item->id == $ItemDefault )
                $Result .= '<option selected value = "'. $Item->id.'">'. $Item->name. '</option>';
            else
               $Result .= '<option value = "'. $Item->id.'">'. $Item->name. '</option>';
        }
    }
    return $Result ;
} 

function GetFirstDateOfMonth($TheYear,$TheMonth)
{
    $t =  (string) $TheYear  . '-'. (string)$TheMonth. '-01';
    $x =  date('Y-m-d', strtotime($t));
    return $x;
}

function render_sophieu($ky_hieu_phieu,$table_name,$column)
{
    $m = date('mY',time());
    $f = DB::select(DB::raw("SELECT COUNT(".$column.") as C FROM ".$table_name." WHERE ".$column." LIKE '".$ky_hieu_phieu.$m."%'"));
    if(count($f)>0)
    {
        $c = $f[0]->C;
        $_n = ((int)$c)+1;
        $id_phieu =$ky_hieu_phieu.$m.'_'.$_n;
        $check_exist = DB::select('select * from '.$table_name.' where '.$column.' = ?', [$id_phieu]);
        while($check_exist)
        {
            $_n++;
            $id_phieu =$ky_hieu_phieu.$m.'_'.$_n;
            $check_exist = DB::select('select * from '.$table_name.' where '.$column.' = ?', [$id_phieu]);
        }
    }
    else
        $id_phieu = $ky_hieu_phieu.$m.'_1';

    return $id_phieu;
}

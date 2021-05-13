<?php

namespace App\Models\INV;

use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
  //use Hasfactory;
   protected $table ='inv_transactions';
   protected $fillable = ['id','no','the_date','vendor_id','note'];
   public $timestamps =false;

   public function TransactionDetails()// Khai báo mối quan hệ với model Product
    {
      return $this->hasMany('App\Models\inv\TransactionDetails','transaction_id');
    }
}

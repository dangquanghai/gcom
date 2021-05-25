<?php

namespace App\Models\INV;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TransactionDetails extends Model
{
   // use HasFactory;
    protected $table = 'inv_transaction_dt';
    protected $fillable = ['id','transaction_id','product_id','unit_id','quantity','price','amount','note'];
    public $timestamps = false;

    public function transaction()
    {
        return $this->belongsTo('App\Models\Transaction');
    }

    public function products()
    {
        return $this->belongsTo('App\Models\PRD\Product','product_id');
    }
}

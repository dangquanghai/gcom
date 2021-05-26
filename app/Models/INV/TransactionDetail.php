<?php

namespace App\Models\inv;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
class TransactionDetail extends Model
{
    //use HasFactory;
    protected $table = 'inv_transaction_dt';
    protected $fillable = ['id','transaction_id','product_id','unit_id','quantity','price','amount','note'];
    public $timestamps = false;

    public function transaction()
    {
        return $this->belongsTo('App\Models\inv\Transaction');
    }

    public function products()
    {
        return $this->belongsTo('App\Models\inv\Product','product_id');
    }
}

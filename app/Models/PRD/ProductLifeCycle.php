<?php

namespace App\Models\PRD;

use Illuminate\Database\Eloquent\Model;

class ProductLifeCycle extends Model
{
    //
    protected $table ='prd_product_life_cycle';
    protected $fillable = ['id','name'];
    protected $timestamps =false;

    public function produtcs()// Khai báo mối quan hệ với model Product
    {
        return $this->hasmany('App\Models\prd\product','life_cycle','id');
    }
}

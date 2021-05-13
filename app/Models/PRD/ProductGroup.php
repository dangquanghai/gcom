<?php
namespace App\Models\PRD;
use Illuminate\Database\Eloquent\Model;
class ProductGroup extends Model
{
    protected $table ='prd_product_groups';
    protected $fillable = ['id','name','is_active'];
    public $timestamps =false;

    public function Product()// Khai báo mối quan hệ với model Product
     {
         return $this->hasmany('App\Models\prd\Product');
     }
}

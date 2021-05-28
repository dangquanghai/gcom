<?php

namespace App\Http\Controllers\Prd;
use App\Models\PRD\ProductNew;
use Illuminate\Http\Request;
use DB;
use App\Http\Controllers\SysController;

class ProductControllerNew extends SysController
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
      
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }
    //-------------------------------------------------------------------
    public function autocompleteProduct(Request $request)
    {
        // $key = $request->key;
        $key = $_GET['term'];// trong jquery-ui  đặt tên biến này
        if($key)
        {
          $sql = "  select p.id, pas.asin, p.title 
          from prd_product p  inner join sal_propduct_asins pas 
          on p.id = pas.product_id   
          where  (p.company_id <> 1) and (pas.market_place = 1) and (pas.asin is not null ) and (pas.asin <> '' )
          and (pas.asin like '%$key%' or p.title  like '%$key%' )  order by  pas.asin ";

          $prs = DB::connection('mysql')->select($sql);
         // $prs = Product::where('name','like',"%$key%")->orWhere('sku','like',"%$key%")->get();
          return json_encode($prs) ;
        }
    } 
    //-------------------------------------------------------------------
    //* nhập SKU vào ô search va enter hoặc dùng máy đọc mã vạch
    public function checkAsin($Asin)
    {
        if($Asin)
        {
            $sql = "  select p.id  from  prd_product p   inner join sal_propduct_asins a
            on p.id =a.product_id  where  (p.company_id <> 1)  and (a.asin  = '$Asin') and (market_place =1) " ;
            $prs = DB::connection('mysql')->select($sql);
            if($prs)
                return json_encode($prs->id) ;
            else
                return 0;
        }
    }
    //-------------------------------------------------------------------
    // Select sp để show
    public function selectProduct(Request $request)
    {
        $seq = $request->seq;
        $product_id = $request->id;
        
        $sql = "  select p.id,  a.asin, p.title 
        from  prd_product p  inner join sal_propduct_asins a
        where a.market_place =1 and  p.id =  $product_id ";

        $products = DB::connection('mysql')->select($sql);
        foreach($products as $product )
        return view('SAL.Promotions.ajax-list-product-for-promotion_dt',compact('product','seq'));
        
    }
}

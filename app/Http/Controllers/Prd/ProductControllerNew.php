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
        try
        {
            $data = $request->data;
            $type = $data['type'];

            $promotion_no = $data['promotion_no'];
            $promotion_type = $data['promotion_type'];
            $promotion_status = $data['promotion_status'];
            $channel_id = $data['channel_id'];
            $from_date = $data['from_date'];
            $to_date = $data['to_date'];

            $details = $data['detail_input'];

            DB::beginTransaction();
            $tran = new Transaction();
            $tran->no = $no;
            $tran->the_date = $date;
            $tran->description = $description;
            $tran->vendor_id =$vendor_id;
            $tran->type_id = $type;
            $tran->save();

            if(count($details)>0 && $tran->id)
            {
                foreach($details as $item)
                {
                    $product_id = $item['product_id'];
                    $price = $item['price'];
                    $quantity = $item['quantity'];
                    $note_item = $item['note'];

                    $tran_details = new TransactionDetails();
                    $tran_details->product_id = $product_id;
                    $tran_details->transaction_id = $tran->id;
                    $tran_details->price = $price;
                    $tran_details->quantity = $quantity;
                    $tran_details->amount = $price * $quantity;
                    $tran_details->note = $note_item;
                    $tran_details->save();
                }
            }
            DB::commit();
        }
        catch(Exception $ex)
        {
            dd($ex.message());
            DB::rollback();
            return 0;
        }
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
        //   $sql = " select id, product_sku as sku, title from prd_product where (product_sku like '%$key%' or title  like '%$key%' )
        //   order by product_sku ";

          $sql = "  select p.id, pas.amz_asin as asin, p.title 
          from sal_propduct_asins pas left join prd_product p 
          on pas.sku =  p.product_sku 
          where  (p.company_id <> 1) and (pas.amz_asin <>'') and (pas.amz_asin is not null)
          and (pas.amz_asin<>'x')
          and (pas.amz_asin like '%$key%' or p.title  like '%$key%' )  order by  pas.amz_asin ";

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
            $sql = "  select p.id  from sal_propduct_asins pas left join prd_product p 
            on pas.sku =  p.product_sku     where  (p.company_id <> 1)  and (pas.amz_asin = '$Asin')"   ;
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
        
        $sql = "  select p.id, pas.amz_asin as asin, p.title 
        from sal_propduct_asins pas left join prd_product p 
        on pas.sku =  p.product_sku 
        where p.id =  $product_id ";

        $products = DB::connection('mysql')->select($sql);
        foreach($products as $product )
        return view('SAL.Promotions.ajax-list-product-for-promotion_dt',compact('product','seq'));
        
    }
}

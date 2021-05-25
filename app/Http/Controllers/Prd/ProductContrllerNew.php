<?php

namespace App\Http\Controllers\Prd;
use App\Models\PRD\ProductNew;
use Illuminate\Http\Request;

use App\Http\Controllers\SysController;

class ProductContrllerNew extends SysController
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
        //
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
     
    
    // Select sp để show
    public function selectProduct(Request $request)
    {
        $product_id = $request->id;
        $product = ProductNew::find($product_id);
        //dd($request);
        return view('SAL.Promotions.ajax-list-product-for-promotion_dt',compact('product'));
    }

    //* nhập SKU vào ô search va enter hoặc dùng máy đọc mã vạch
    public function checkSku($sku)
    {
        if($sku)
        {
            $prs = ProductNew::Where('sku',$sku)->first();
            if($prs)
                return json_encode($prs->id) ;
            else
                return 0;
        }
    }
}

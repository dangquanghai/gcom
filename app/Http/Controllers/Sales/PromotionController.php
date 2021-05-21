<?php

namespace App\Http\Controllers\Sales;
use Illuminate\Http\Request;
use App\Http\Controllers\SysController;
use DB;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Reader\IReadFilter;
use App\Models\Sales\Promotion;
use Validator;
use DateTime;
use GuzzleHttp\Client;


class PromotionController extends SysController
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $sqlFilter = "";
        $from_date = $this->GetFirstDateOfMonth(now()->year,now()->month);
        $to_date  =  date("Y-m-d");
        if(date("d")==1 ){ $to_date = $this->MoveDate( $to_date,1);}
  
        $sqlFilter =  " and (pro.from_date  <='". $to_date . "' and pro.to_date >= '" . $from_date . "') ";
      
        if($request->has('sku'))
          $sku = $request->input('sku');
        else $sku='';
        
        if($request->has('asin'))
          $asin = $request->input('asin');
        else $asin='';
        
        if($request->has('title'))
          $title = $request->input('title');
        else{$title='';}
        
        if($request->has('promotion_id'))
          $promotion_id = $request->input('promotion_id');
        else{$promotion_id='';}
        
        if($request->has('promotion_type'))
          $promotion_type = $request->input('promotion_type');
        else $promotion_type = 0;
        
        if($request->has('promotion_status'))
          $promotion_status = $request->input('promotion_status');
        else $promotion_status= 0;
        
        if($request->has('channel'))
          $channel = $request->input('channel');
        else $channel = 0;
  
        if($request->has('brand'))
          $brand = $request->input('brand');
        else $brand = 0;
         
      $sql = " select pro.id, prodt.asin, prodt.sku, p.title,protype.name as promotion_type,prostatus.name as status,
      pro.promotion_no, pro.from_date,pro.to_date, prodt.unit_sold, prodt.amount_spent,prodt.revenue,c.name as channel
      from sal_promotions pro 
      left join sal_promotions_dt prodt on pro.id = prodt.promotion_id
      left join  prd_product p  on p.product_sku = prodt.sku
      left join sal_promotion_types protype on pro.Promotion_type = protype.id
      left join sal_promotion_status prostatus on pro.promotion_status = prostatus.id
      left join prd_brands on p.brand_id = prd_brands.id
      left join sal_channels c on pro.channel = c.id
      where (1 = 1)  ";
  
      if($sku <>'') {$sqlFilter =   $sqlFilter .  " and (prodt.sku like '%". $sku. "%')";}
      if($asin <>'') {$sqlFilter =   $sqlFilter .  " and (prodt.asin like '%". $asin . "%') ";}
      if($title <>''){$sqlFilter =   $sqlFilter .  " and (p.title like '%". $title . "%') ";}
      if($promotion_id <>''){ $sqlFilter =   $sqlFilter .  " and (pro.promotion_no like'%". $promotion_id. "%') ";}
      if($promotion_type <> 0 ){ $sqlFilter =   $sqlFilter .  " and (pro.promotion_type = " . $promotion_type ." )";}
      if($promotion_status <> 0 ){ $sqlFilter =   $sqlFilter .  " and (pro.promotion_status = " .  $promotion_status. ")";}
      if($channel <> 0){ $sqlFilter =   $sqlFilter .  " and (c.id = " . $channel .")";}
      if($brand <> 0){ $sqlFilter =   $sqlFilter .  " and (prd_brands.id = " . $brand .")";}
  
      $sqlOrder = " order by pro.from_date, pro.promotion_no,  prodt.asin";
    
      $sql = $sql . $sqlFilter . $sqlOrder;
     
      $dsPromotions = DB::connection('mysql')->select($sql);
     
      $sql = " select 0 as id, 'All' as name union  select id, name from sal_promotion_status ";
      $PromotionStatuses = DB::connection('mysql')->select($sql);
  
      $sql = " select 0 as id, 'All' as name union select id, name from sal_promotion_types ";
      $PromotionTypes = DB::connection('mysql')->select($sql);
  
      $sql = " select 0 as id, 'All' as name union select id, name from sal_channels ";
      $Channels = DB::connection('mysql')->select($sql);
  
      $sql = " select 0 as id, 'All' as name union select id, brand_name as name from prd_brands ";
      $Brands = DB::connection('mysql')->select($sql);
  
      return view('SAL.Promotions.list',compact(['dsPromotions','PromotionStatuses','PromotionTypes','Channels','Brands','sku','asin',
      'title','promotion_id','promotion_type','promotion_status','channel','brand','from_date','to_date']));
     
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
      $sql = " select id, name from sal_promotion_types ";
      $dsTypes = DB::connection('mysql')->select($sql);

      $sql = " select id, name from sal_promotion_status ";
      $dsStatuses = DB::connection('mysql')->select($sql);

      $sql = " select id, name from sal_channels ";
      $dsChannels = DB::connection('mysql')->select($sql);

      return view('SAL.Promotions.add',compact(['dsTypes','dsStatuses','dsChannels']));
      //return view('SAL.Promotions.add');
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
      $dsPromotion = Promotion::find($id);
      return view('SAL.SalesProductInfor.edit',compact(['id','dsPromotion','ProductCostPrice','dsChannels']));
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
}

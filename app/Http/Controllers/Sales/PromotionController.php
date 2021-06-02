<?php

namespace App\Http\Controllers\Sales;
use Illuminate\Http\Request;
use App\Http\Controllers\SysController;
use DB;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Reader\IReadFilter;
use App\Models\Sales\Promotion;
use App\Models\Sales\PromotionDetail;
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
  
        // $sqlFilter =  " and (pro.from_date  <='". $to_date . "' and pro.to_date >= '" . $from_date . "') ";
      
        if($request->has('sku'))
          $sku = $request->input('sku');
        else $sku='';
        
        if($request->has('asin'))
          $asin = $request->input('asin');
        else $asin='';
        
        if($request->has('title'))
          $title = $request->input('title');
        else{$title='';}
        
        if($request->has('promotion_no'))
          $promotion_no = $request->input('promotion_no');
        else{$promotion_no='';}
        
        if($request->has('promotion_type'))
          $promotion_type = $request->input('promotion_type');
        else $promotion_type = 0;
        
        if($request->has('promotion_status'))
          $promotion_status = $request->input('promotion_status');
        else $promotion_status= 0;
        
        if($request->has('channel_id'))
          $channel = $request->input('channel_id');
        else $channel = 0;
  
        if($request->has('brand'))
          $brand = $request->input('brand');
        else $brand = 0;
         
      $sql = " select pro.id , GetAsin(p.id,c.id,cs.id) as asin, p.product_sku as sku, p.title, protype.name as promotion_type,
      prostatus.name as status, pro.promotion_no, pro.from_date,pro.to_date, prodt.unit_sold, prodt.amount_spent,
      prodt.revenue,c.name as channel
      from sal_promotions pro 
      left join sal_promotions_dt prodt on pro.id = prodt.promotion_id
      left join  prd_product p  on p.id = prodt.product_id
      left join sal_promotion_types protype on pro.Promotion_type = protype.id
      left join sal_promotion_status prostatus on pro.promotion_status = prostatus.id
      left join prd_brands on p.brand_id = prd_brands.id
      left join sal_channels c on pro.channel_id = c.id
      left join sal_channel_stores cs on c.id = cs.sales_channel 
      where (1 = 1)  ";
  
      if($sku <>'') {$sqlFilter =   $sqlFilter .  " and (prodt.sku like '%". $sku. "%')";}
      if($asin <>'') {$sqlFilter =   $sqlFilter .  " and (GetAsin(p.id,c.id,cs.id) like '%". $asin . "%') ";}
      if($title <>''){$sqlFilter =   $sqlFilter .  " and (p.title like '%". $title . "%') ";}
      if($promotion_no <>''){ $sqlFilter =   $sqlFilter .  " and (pro.promotion_no like'%". $promotion_no. "%') ";}
      if($promotion_type <> 0 ){ $sqlFilter =   $sqlFilter .  " and (pro.promotion_type = " . $promotion_type ." )";}
      if($promotion_status <> 0 ){ $sqlFilter =   $sqlFilter .  " and (pro.promotion_status = " .  $promotion_status. ")";}
      if($channel <> 0){ $sqlFilter =   $sqlFilter .  " and (c.id = " . $channel .")";}
      if($brand <> 0){ $sqlFilter =   $sqlFilter .  " and (prd_brands.id = " . $brand .")";}
  
      $sqlOrder = " order by pro.from_date, pro.promotion_no ";
    
      $sql = $sql . $sqlFilter . $sqlOrder;
     // dd($sql);
     
      $dsPromotions = DB::connection('mysql')->select($sql);
      //dd($dsPromotions);
     
      $sql = " select 0 as id, 'All' as name union  select id, name from sal_promotion_status ";
      $PromotionStatuses = DB::connection('mysql')->select($sql);
  
      $sql = " select 0 as id, 'All' as name union select id, name from sal_promotion_types ";
      $PromotionTypes = DB::connection('mysql')->select($sql);
  
      $sql = " select 0 as id, 'All' as name union select id, name from sal_channels ";
      $Channels = DB::connection('mysql')->select($sql);
  
      $sql = " select 0 as id, 'All' as name union select id, brand_name as name from prd_brands ";
      $Brands = DB::connection('mysql')->select($sql);
  
      return view('SAL.Promotions.list',compact(['dsPromotions','PromotionStatuses','PromotionTypes','Channels','Brands','sku','asin',
      'title','promotion_no','promotion_type','promotion_status','channel','brand','from_date','to_date']));
     
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

     // $sql = " select id, product_sku as sku from prd_product where company_id <>1
     //  order by product_sku ";
     // $dsSkus = DB::connection('mysql')->select($sql);

      return view('SAL.Promotions.add',compact(['dsTypes','dsStatuses','dsChannels']));
      
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
     
      $data = 0;
      try
      {
          $data = $request->data;
          $promotion_no = $data['promotion_no'];
          $promotion_type = $data['promotion_type'];
          $promotion_status = $data['promotion_status'];
          $channel_id = $data['channel_id'];
          $from_date = $data['from_date'];
          $to_date = $data['to_date'];

          $details = $data['detail_input'];

          DB::beginTransaction();
          $pro = new Promotion();
          $pro->promotion_no = $promotion_no;
          $pro->promotion_type = $promotion_type;
          $pro->promotion_status = $promotion_status;
          $pro->channel_id =$channel_id;
          $pro->from_date = $from_date;
          $pro->to_date =$to_date;
          $pro->save();

          if(count($details)>0 && $pro->promotion_no)
          {
              foreach($details as $item)
              {
                  $product_id = $item['product_id'];
                  $per_funding = $item['per_funding'];
                  $funding = $item['funding'];
                  $unit_sold = $item['unit_sold'];
                  $amount_spent = $item['amount_spent'];
                  $revenue = $item['revenue'];

                  $pro_details = new PromotionDetail();
                  $pro_details->promotion_id =$pro->id;
                  $pro_details->product_id = $product_id;
                  $pro_details->per_funding = $per_funding ;
                  $pro_details->funding = $funding ;
                  $pro_details->unit_sold = $unit_sold;
                  $pro_details->amount_spent = $amount_spent;
                  $pro_details->revenue = $revenue;
                  $pro_details->save();
              }
          }
          DB::commit();
      }
      catch(Exception $ex)
      {
          DB::rollback();
          echo ("0");
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
      $sql = " select id, name from sal_promotion_types ";
      $dsTypes = DB::connection('mysql')->select($sql);

      $sql = " select id, name from sal_promotion_status ";
      $dsStatuses = DB::connection('mysql')->select($sql);

      $sql = " select id, name from sal_channels ";
      $dsChannels = DB::connection('mysql')->select($sql);

      $dsProm = Promotion::find($id);

      $sql = " select pdt.id, pdt.promotion_id,pdt.product_id, pas.asin, p.title,
      per_funding, funding,	unit_sold,amount_spent,revenue
      from sal_promotions_dt pdt 
      inner join prd_product p on pdt.product_id = p.id
      inner join sal_product_asins pas on p.id = pas.product_id
      where pas.market_place = 1 and pas.store_id = 0  and  pdt.promotion_id = $id ";
      
      $dsPromotionDT = DB::connection('mysql')->select($sql);

     
      return view('SAL.promotions.edit',compact(['id','dsProm','dsTypes','dsStatuses','dsChannels','dsPromotionDT']));
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
     //dd($id);
    
     $data = $request->data;
     $promotion_no = $data['promotion_no'];
     $promotion_type = $data['promotion_type'];
     $promotion_status = $data['promotion_status'];
     $channel_id = $data['channel_id'];
     $from_date = $data['from_date'];
     $to_date = $data['to_date'];
     $details = $data['detail_input'];

     $pro = Promotion::find($id);
   
      try
      {
          DB::beginTransaction();
        
          $pro->promotion_no = $promotion_no;
          $pro->promotion_type = $promotion_type;
          $pro->promotion_status = $promotion_status;
          $pro->channel_id =$channel_id;
          $pro->from_date = $from_date;
          $pro->to_date = $to_date;
          $pro->save();

          if(count($details)>0 && $pro->promotion_no)
          {
              foreach($details as $item)
              {
                $idDetail = 0;

                $idDetail = $item['id']??0;
                  
                $product_id = $item['product_id'];
                $per_funding = $item['per_funding'];
                $funding = $item['funding'];
                $unit_sold = $item['unit_sold'];
                $amount_spent = $item['amount_spent'];
                $revenue = $item['revenue'];

                if($idDetail >0 )
                  $pro_details =  PromotionDetail::find($idDetail);
                else
                  $pro_details =   new PromotionDetail();

                //dd($idDetail);
                $pro_details->promotion_id = $id;
                $pro_details->product_id = $product_id;
                $pro_details->per_funding = $per_funding ;
                $pro_details->funding = $funding;
                $pro_details->unit_sold = $unit_sold;
                $pro_details->amount_spent = $amount_spent;
                $pro_details->revenue = $revenue;
                $pro_details->save();
                
              }
          }
          DB::commit();
      }
      catch(Exception $ex)
      {
          DB::rollback();
          echo ("0");
      }

    }
    /**
     * Remove the specified resource from storage.
     *s
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }

    public function destroyPromotionDetail($idDetail)
    {
   
      try
      {
        DB::beginTransaction();
        $sql = " delete from sal_promotions_DT where id =$idDetail  ";
        DB::connection('mysql')->select($sql);
        DB::commit();
      }   
      catch(Exception $ex)
      {
          DB::rollback();
          echo ("0");
      }
    }
}

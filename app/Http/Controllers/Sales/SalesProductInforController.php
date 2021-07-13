<?php

namespace App\Http\Controllers\Sales;
use Illuminate\Http\Request;
use App\Http\Controllers\SYS\SysController;
use DB;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Reader\IReadFilter;
use App\Models\Sales\SalesProductInfor;
use App\Http\Controllers\SYS\ZaloController;
use Validator;
use DateTime;
use GuzzleHttp\Client;
use App\Mail\SalNotifyChangeCostPrice;
use Illuminate\Support\Facades\Mail;
use Auth;

class SalesProductInforController extends SysController
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */

    private $FunctionName ="Sales Product Infor";
      // --------------------------------------------------------------------
    public function LoadFileProductSalesInfor()
    {
     return view('SAL.SalesProductInfor.LoadFileProductSalesInfor');
    }
  // --------------------------------------------------------------------
  public function UpdateCostPrice(Request $request)
  {
    $EffectFrom = date('Y-m-d H:i:s');
    $UserName='';
    $Sku='';
    $ProductName ='';
    $ChannelName='';
    $OldCost= 0;
    $OldPrice= 0;
    $NewCost =0;
    $NewPrice =0;

      $sql = " select id as MyCount from sal_product_channel_price where  id  = $request->id 
      and retail_price = $request->retail_price and per_cost =  $request->per_cost " ;
      if(!$this->IsExist('mysql',$sql))// Có thay đổi khác mới lưu
      {
        // Lưu lại giá cũ
        $sql = " select cost,retail_price from sal_product_channel_price where  id  = $request->id ";
        $ds=  DB::connection('mysql')->select($sql);
        foreach($ds as $d){
          $OldCost= $d->cost;
          $OldPrice= $d->retail_price;
        }
     
        // Cập nhật lại giá trên bảng sal_product_channel_price khi có thay đổi giá
        $data = array(
        'retail_price'=>$request->retail_price,
        'per_cost'=>$request->per_cost,
        'cost'=>$request->retail_price * $request->per_cost /100
        );
        //dd(  $data );

        DB::table('sal_product_channel_price')->where('id',$request->id)->update($data) ;

        $NewCost = $request->retail_price * $request->per_cost /100;
        $NewPrice =$request->retail_price;
        
        // Lưu lại historry khi có cập nhật giá ghi history lên bảng  bảng sal_product_channel_price_his
      
        $User = auth()->user();
        $UserID = $User->id;
        $UserName= $User->name;
        
        $dataForHis = array(
          'prd_channel_price_id'=>$request->id,
          'price'=>$request->retail_price,
          'cost'=>$request->retail_price * $request->per_cost /100,
          'effect_from'=>$EffectFrom,
          'update_by'=>$UserID
          );
      
          
          DB::table('sal_product_channel_price_his')->insert($dataForHis) ;
        //dd($dataForHis);
      
        $sql= " select p.product_sku as sku, p.title, sc.name as ChannelName from  prd_product p 
        inner join sal_product_channel_price pp on p.id = pp.product_id
        inner join sal_channels sc on pp.channel_id = sc.id
        where pp.id = $request->id  ";

        $ds=  DB::connection('mysql')->select($sql);
        foreach($ds as $d){
          $Sku= $d->sku;
          $ProductName = $d->title;
          $ChannelName = $d->ChannelName;
        }
        //Mail::to('dangquanghai123@gmail.com')
       $z = new ZaloController();
        Mail::to('haidang@behmd.com')
        ->send(new SalNotifyChangeCostPrice($EffectFrom, $UserName,$Sku, $ProductName,$OldCost,$NewCost,$OldPrice,$NewPrice,$ChannelName));
        echo '<div class = "alert  alert-success"> Data Updated </div>';
      } 
  }
  // --------------------------------------------------------------------
  public function LoadPromotionsDefault(Request $request)
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

    
        
    $sql = " select prodt.asin, prodt.sku, p.title,protype.name as promotion_type,prostatus.name as status, pro.promotion_no, pro.from_date, 
    pro.to_date, prodt.unit_sold, prodt.amount_spent,prodt.revenue,c.name as channel
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
    // --------------------------------------------------------------------
  public function LoadPromotions(Request $request)
    {
     $sqlFilter = "";
     $from_date = $request->input('from_date');
     $to_date = $request->input('to_date');
          
     $sqlFilter =  " and (pro.from_date  <='". $to_date . "' and pro.to_date >= '" . $from_date . "') ";


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

      $request->flash();
        
    $sql = " select prodt.asin, prodt.sku, p.title,protype.name as promotion_type,prostatus.name as status, pro.promotion_no, pro.from_date, 
    pro.to_date, prodt.unit_sold, prodt.amount_spent,prodt.revenue,c.name as channel
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
 
    // --------------------------------------------------------------------
 
    public function LoadSalesProductInforDetail($id)
    {
      $SPIs = SalesProductInfor::find($id);
      dd($SPIs);
      //return view('SAL.SalesProductInfor.edit',compact(['id','SPIs']));
    }
    //----------------------------------------------------
    public function index(Request $request)
    {
      $PerSellingInvoice =2;// Tiền thu về sớm thfi chịu mất 2%
      $PerSalesCommission = 1.5;// Tiền sales commission chiếm 1.5 %

      if($request->has('sku'))
        $Sku = $request->input('sku');
      else
        $Sku='';

    if($request->has('title'))
      $Title  = $request->input('title');
    else
      $Title ='';

    if($request->has('brand'))
      $Brand  = $request->input('brand');
    else
      $Brand = 0;
    $request->flash();

    $sql = " select pi.id, pi.title, pi.sku, br.brand_name,
    pi.the_length, pi.the_width, pi.the_height,pi.the_weight, round(pi.the_length * pi.the_width * pi.the_height) as cubic,
    pi.per_deposit,pi.per_full_payment,pi.per_rev_split_for_partner,
    pi.con20_capacity,pi.exw_vn,pi.fob_vn,pi.fob_cn, pi.fob_us, pi.cosg_est,
    pi.per_mkt, per_promotion,per_return, 2 as selling_invoice,
    pi.per_duty,1.5 as sales_commision, pi.per_wh_fee,pi.per_handing_fee,

    round(( pi.fob_us/pi.retail_price + pi.per_mkt + pi.per_promotion + pi.per_return + 2 + pi.per_duty
    + 1.5 + pi.per_wh_fee +	pi.per_handing_fee),2) as per_total_cost,

    round(
     ( pi.per_wholesales_price_min  -
     round(pi.fob_us/pi.retail_price + pi.per_mkt + pi.per_promotion + pi.per_return + 2 + pi.per_duty + 1.5 + pi.per_wh_fee + pi.per_handing_fee,2)
     )
   ,2) as per_wholesales_gp_min,

   round(
                 ( pi.per_wholesales_price_max  -
                 round(pi.fob_us/pi.retail_price + pi.per_mkt + pi.per_promotion + pi.per_return + 2 + pi.per_duty +1.5+ pi.per_wh_fee + pi.per_handing_fee,2)
                 )
   ,2) as per_wholesales_gp_max,
   pi.shiping_fee_est,
   pi.fba_shipping_est,
   round(
     (round(( pi.fob_us/pi.retail_price + pi.per_mkt + pi.per_promotion + pi.per_return + 2 + pi.per_duty
     + 1.5 + pi.per_wh_fee +	pi.per_handing_fee),2)* pi.retail_price - pi.shiping_fee_est)/ pi.retail_price
     ,2) as per_retail_profit,

   pi.per_wholesales_price_min,
   pi.per_wholesales_price_max,

   round(pi.shiping_fee_est*0.85,2) as dsv_shipping_fee,

   round((pi.retail_price - (pi.per_wholesales_price_max* pi.retail_price) - round(pi.shiping_fee_est*0.85,2))/pi.retail_price ,2)
   as  price_profit_min,

   round((pi.retail_price - (pi.per_wholesales_price_min* pi.retail_price) - round(pi.shiping_fee_est*0.85,2))/pi.retail_price ,2)
   price_profit_max,

   pi.shiping_fee_est,pi.retail_price,

   round(GetSalesChannelFee(1)*100,2) as per_avcwh_fee,
   round(GetSalesChannelFee(2)*100,2) as per_avcds_fee,
   round(GetSalesChannelFee(3)*100,2) as per_avcdi_fee,
   round(GetSalesChannelFee(4)*100,2) as per_wmdsv_fee,
   round(GetSalesChannelFee(5)*100,2) as per_wmmkp_fee,
   round(GetSalesChannelFee(6)*100,2) as per_ebay_fee,
   round(GetSalesChannelFee(7)*100,2) as per_local_fee,
   round(GetSalesChannelFee(8)*100,2) as per_website_fee,
   round(GetSalesChannelFee(9)*100,2) as per_fba_fee,
   round(GetSalesChannelFee(10)*100,2) as per_fbm_fee,
   round(GetSalesChannelFee(12)*100,2) as per_way_fee,

    

  round( GetCostPrice(1,p.product_sku) * GetSalesChannelFee(1),2) as avcwh_fee,
  round( GetCostPrice(2,p.product_sku) * GetSalesChannelFee(2),2) as avcds_fee,
  round( GetCostPrice(3,p.product_sku) * GetSalesChannelFee(2),2) as avcdi_fee,
  round( GetCostPrice(4,p.product_sku) * GetSalesChannelFee(4),2) as wmdsv_fee,
  round( GetRetailPrice(5,p.product_sku) * GetSalesChannelFee(5),2) as wmmkp_fee,
  round( GetRetailPrice(6,p.product_sku) * GetSalesChannelFee(6),2) as ebay_fee,
  round( GetRetailPrice(7,p.product_sku) * GetSalesChannelFee(7),2) as local_fee,
  round( GetRetailPrice(8,p.product_sku) * GetSalesChannelFee(8),2) as website_fee,
  round( GetRetailPrice(9,p.product_sku) * GetSalesChannelFee(9),2) as fba_fee,
  round( GetRetailPrice(10,p.product_sku) * GetSalesChannelFee(10),2) as fbm_fee ,
  round( GetCostPrice(12,p.product_sku) * GetSalesChannelFee(12),2) as way_fee ,


  GetRetailPrice(1,p.product_sku) as avcwh_retail_price,
  GetRetailPrice(2,p.product_sku) as avcds_retail_price,
  GetRetailPrice(3,p.product_sku) as avcdi_retail_price,
  GetRetailPrice(4,p.product_sku) as wmdsv_retail_price,
  GetRetailPrice(5,p.product_sku) as wmmkp_retail_price,
  GetRetailPrice(6,p.product_sku) as ebay_retail_price,
  GetRetailPrice(7,p.product_sku) as local_retail_price,
  GetRetailPrice(8,p.product_sku) as website_retail_price,
  GetRetailPrice(9,p.product_sku) as fba_retail_price,
  GetRetailPrice(10,p.product_sku) as fbm_retail_price,
  GetRetailPrice(12,p.product_sku) as wayfair_retail_price,

  GetCostPrice(1,p.product_sku) as avcwh_cost,
  GetCostPrice(2,p.product_sku) as avcds_cost,
  GetCostPrice(3,p.product_sku) as avcdi_cost,
  GetCostPrice(4,p.product_sku) as wmdsv_cost,
  GetCostPrice(5,p.product_sku) as wmmkp_cost,
  GetCostPrice(6,p.product_sku) as ebay_cost,
  GetCostPrice(7,p.product_sku) as local_cost,
  GetCostPrice(8,p.product_sku) as website_cost,
  GetCostPrice(9,p.product_sku) as fba_cost,
  GetCostPrice(10,p.product_sku) as fbm_cost,
  GetCostPrice(12,p.product_sku) as wayfair_cost,

  case when  GetCostPrice(1,p.product_sku) > 0 then
  round(
          GetCostPrice(1,p.product_sku) - pi.fob_us
          -( pi.per_mkt + pi.per_promotion + pi.per_return + 2 + pi.per_duty +1.5 + pi.per_wh_fee + pi.per_handing_fee + GetSalesChannelFee(1) * 100) * GetCostPrice(1,p.product_sku)/100  
        ,2) else 0 end as avcwh_profit ,

   case when GetCostPrice(2,p.product_sku)> 0 then
   round (
           GetCostPrice(2,p.product_sku) - pi.fob_us 
           -(pi.per_mkt + pi.per_promotion + pi.per_return + 2 + pi.per_duty + 1.5 + pi.per_wh_fee + pi.per_handing_fee +  GetSalesChannelFee(2) * 100 ) * GetCostPrice(2,p.product_sku)/100
           ,2) else 0 end as avcds_profit ,
           
    case when GetCostPrice(3,p.product_sku)> 0 then
    round (
            GetCostPrice(3,p.product_sku) - GetMaxFOBChinaOrVN(p.product_sku)
            -(pi.per_mkt + pi.per_promotion + pi.per_return + 2 + pi.per_duty + 1.5 + pi.per_wh_fee + pi.per_handing_fee +  GetSalesChannelFee(2) * 100 ) * GetCostPrice(3,p.product_sku)/100
            ,2) else 0 end as avcdi_profit,
   
   case when GetCostPrice(4,p.product_sku) > 0 then
   round(
          GetCostPrice(4,p.product_sku) -pi.fob_us 
          -( pi.per_mkt + pi.per_promotion + pi.per_return +2+ pi.per_duty +1.5  + pi.per_wh_fee + pi.per_handing_fee +  GetSalesChannelFee(4) * 100) * GetCostPrice(4,p.product_sku)/100
         ,2) else 0 end as wmdsv_profit ,

   case when GetRetailPrice(5,p.product_sku) > 0 then
   round(
         GetRetailPrice(5,p.product_sku) -pi.fob_us -  pi.shiping_fee_est
         - (pi.per_mkt + pi.per_promotion + pi.per_return + 2 + pi.per_duty + 1.5 + pi.per_wh_fee + pi.per_handing_fee + GetSalesChannelFee(5) * 100) * GetRetailPrice(5,p.product_sku)/100
        ,2) else 0 end as wmmkp_profit ,

   case when GetRetailPrice(6,p.product_sku) > 0 then
   round(
         GetRetailPrice(6,p.product_sku) - (pi.fob_us + pi.shiping_fee_est)
         -( pi.per_mkt + pi.per_promotion + pi.per_return + 2 + pi.per_duty + 1.5 + pi.per_wh_fee + pi.per_handing_fee +  GetSalesChannelFee(6) * 100)* GetRetailPrice(6,p.product_sku)/100 
       ,2) else 0 end as ebay_profit ,

   case when GetRetailPrice(7,p.product_sku)> 0 then
   round(
       GetRetailPrice(7,p.product_sku) -  pi.fob_us 
       - (pi.per_mkt + pi.per_promotion + pi.per_return + 2 + pi.per_duty + 1.5 + pi.per_wh_fee + pi.per_handing_fee + GetSalesChannelFee(7) *100) * GetRetailPrice(7,p.product_sku)/100
       ,2) else 0 end  as local_profit ,

   case when GetRetailPrice(8,p.product_sku) > 0 then
   round(
       GetRetailPrice(8,p.product_sku) - pi.fob_us 
       - ( pi.per_mkt + pi.per_promotion + pi.per_return +2 + pi.per_duty + 1.5  + pi.per_wh_fee + pi.per_handing_fee + GetSalesChannelFee(8) * 100) * GetRetailPrice(8,p.product_sku)/100 
       ,2) else 0 end as website_profit ,

  case when GetRetailPrice(9,p.product_sku)> 0 then
  round(
    GetRetailPrice(9,p.product_sku) -  (pi.fob_us + pi.	fba_shipping_est)
    - (pi.per_mkt + pi.per_promotion + pi.per_return + 2 + pi.per_duty + 1.5 + pi.per_wh_fee + pi.per_handing_fee + GetSalesChannelFee(9) *100) * GetRetailPrice(9,p.product_sku)/100
    ,2) else 0 end  as fba_profit ,

  case when GetRetailPrice(10,p.product_sku)> 0 then
  round(
          GetRetailPrice(10,p.product_sku) - (pi.fob_us + pi.shiping_fee_est) 
          -( 
            pi.per_mkt + pi.per_promotion + pi.per_return +2 + pi.per_duty + 1.5  + pi.per_wh_fee + pi.per_handing_fee  + GetSalesChannelFee(10) * 100 ) * GetRetailPrice(10,p.product_sku)/100  
      ,2) else 0 end  as fbm_profit ,

   case when GetCostPrice(12,p.product_sku) > 0 then
   round(
        GetCostPrice(12,p.product_sku) - pi.fob_us
        - (pi.per_mkt + pi.per_promotion + pi.per_return +2  + pi.per_duty + 1.5 + pi.per_wh_fee + pi.per_handing_fee +  GetSalesChannelFee(12) * 100 ) * GetCostPrice(12,p.product_sku)/100
        ,2) else 0 end as wayfair_profit

    from sal_product_informations pi 
    left join prd_product p on pi.sku = p.product_sku
    left join prd_brands br on p.brand_id = br.id
    where ( 1 = 1 )";
    
     $sqlFilter = '';
     if($Sku <> ''){ $sqlFilter =  $sqlFilter. " and p.product_sku = '$Sku' " ;}
     if($Title <> ''){ $sqlFilter =  $sqlFilter. " and p.title like  '%$Title%' " ;}
     if($Brand <> 0 ){ $sqlFilter =  $sqlFilter. " and br.id =  $Brand " ;}

     $sql = $sql . $sqlFilter . "order by br.brand_name,p.product_sku ";
     $SalesProductInfors =  DB::connection('mysql')->select($sql);
    // print_r($sql);
     //dd( $SalesProductInfors);

      $Sku = $request->input('sku');
      $Title  = $request->input('title');
      $Brand  = $request->input('brand');

    

    $sql= "  select  p.id , p.title, p.product_sku as sku ,GetAsin(p.product_sku,1,0) as amz_asin ,
    GetAsin(product_sku,3,1) as ebay_infidealz, GetAsin(product_sku,3,2) as ebay_inc,
    GetAsin(p.product_sku,3,3) as ebay_fitness,GetAsin(p.product_sku,2,0) as wm_item_id ,
    GetAsin(p.product_sku,6,0) as wayfair_asin 
    from prd_product p inner join prd_brands br on p.brand_id = br.id
    where company_id <> 1 ";

     $sql = $sql .   $sqlFilter ;
     $Asins = DB::connection('mysql')->select($sql);

     $sql = " select 0 as id, 'All' as name union select id, brand_name as name from  prd_brands ";
     $Brands = DB::connection('mysql')->select($sql);
     $UserID = Auth::user()->id;
     $sPermission = $this->GetPermissionOnFunction( $UserID,$this->FunctionName);

     $sql = " select r.is_admin  from sys_roles r inner join sys_role_members rmb on r.id = rmb.role_id 
     where rmb.emp_id = $UserID  ";
     $dx=  DB::connection('mysql')->select($sql);
     foreach($dx as $d) { $IsAdmin = $this->iif(is_null($d->is_admin),0,$d->is_admin ); }
  
     //dd($sPermission);
     return view('SAL.SalesProductInfor.index',compact(['SalesProductInfors','Brands','Sku','Title','Brand','Asins','sPermission','IsAdmin']));

  }
  //----------------------------------------------------
    public function LoadSalesProductList(Request $request)
    {
      $PerSellingInvoice =2;// Tiền thu về sớm thfi chịu mất 2%
      $PerSalesCommission = 1.5;// Tiền sales commission chiếm 1.5 %

      if($request->has('sku'))
        $Sku = $request->input('sku');
      else
        $Sku='';

      if($request->has('title'))
        $ProductName  = $request->input('title');
      else
        $ProductName ='';

      if($request->has('brand'))
        $Brand  = $request->input('');
      else
        $Brand = 0;

        $sql = " select pi.id, pi.title, pi.sku, br.brand_name,
        pi.the_length, pi.the_width, pi.the_height,pi.the_weight, round(pi.the_length * pi.the_width * pi.the_height) as cubic,
        pi.per_deposit,pi.per_full_payment,pi.per_rev_split_for_partner,
        pi.con20_capacity,pi.exw_vn,pi.fob_vn,pi.fob_cn, pi.fob_us, pi.cosg_est,
        pi.per_mkt, per_promotion,per_return, 2 as selling_invoice,
        pi.per_duty,1.5 as sales_commision, pi.per_wh_fee,pi.per_handing_fee,
    
        round(( pi.fob_us/pi.retail_price + pi.per_mkt + pi.per_promotion + pi.per_return + 2 + pi.per_duty
        + 1.5 + pi.per_wh_fee +	pi.per_handing_fee),2) as per_total_cost,
    
        round(
         ( pi.per_wholesales_price_min  -
         round(pi.fob_us/pi.retail_price + pi.per_mkt + pi.per_promotion + pi.per_return + 2 + pi.per_duty + 1.5 + pi.per_wh_fee + pi.per_handing_fee,2)
         )
       ,2) as per_wholesales_gp_min,
    
       round(
                     ( pi.per_wholesales_price_max  -
                     round(pi.fob_us/pi.retail_price + pi.per_mkt + pi.per_promotion + pi.per_return + 2 + pi.per_duty +1.5+ pi.per_wh_fee + pi.per_handing_fee,2)
                     )
       ,2) as per_wholesales_gp_max,
       pi.shiping_fee_est,
       pi.fba_shipping_est,
       round(
         (round(( pi.fob_us/pi.retail_price + pi.per_mkt + pi.per_promotion + pi.per_return + 2 + pi.per_duty
         + 1.5 + pi.per_wh_fee +	pi.per_handing_fee),2)* pi.retail_price - pi.shiping_fee_est)/ pi.retail_price
         ,2) as per_retail_profit,
    
       pi.per_wholesales_price_min,
       pi.per_wholesales_price_max,
    
       round(pi.shiping_fee_est*0.85,2) as dsv_shipping_fee,
    
       round((pi.retail_price - (pi.per_wholesales_price_max* pi.retail_price) - round(pi.shiping_fee_est*0.85,2))/pi.retail_price ,2)
       as  price_profit_min,
    
       round((pi.retail_price - (pi.per_wholesales_price_min* pi.retail_price) - round(pi.shiping_fee_est*0.85,2))/pi.retail_price ,2)
       price_profit_max,
    
       pi.shiping_fee_est,pi.retail_price,
    
       round(GetSalesChannelFee(1)*100,2) as per_avcwh_fee,
       round(GetSalesChannelFee(2)*100,2) as per_avcds_fee,
       round(GetSalesChannelFee(3)*100,2) as per_avcdi_fee,
       round(GetSalesChannelFee(4)*100,2) as per_wmdsv_fee,
       round(GetSalesChannelFee(5)*100,2) as per_wmmkp_fee,
       round(GetSalesChannelFee(6)*100,2) as per_ebay_fee,
       round(GetSalesChannelFee(7)*100,2) as per_local_fee,
       round(GetSalesChannelFee(8)*100,2) as per_website_fee,
       round(GetSalesChannelFee(9)*100,2) as per_fba_fee,
       round(GetSalesChannelFee(10)*100,2) as per_fbm_fee,
       round(GetSalesChannelFee(12)*100,2) as per_way_fee,
    
        
    
      round( GetCostPrice(1,p.product_sku) * GetSalesChannelFee(1),2) as avcwh_fee,
      round( GetCostPrice(2,p.product_sku) * GetSalesChannelFee(2),2) as avcds_fee,
      round( GetCostPrice(3,p.product_sku) * GetSalesChannelFee(2),2) as avcdi_fee,
      round( GetCostPrice(4,p.product_sku) * GetSalesChannelFee(4),2) as wmdsv_fee,
      round( GetRetailPrice(5,p.product_sku) * GetSalesChannelFee(5),2) as wmmkp_fee,
      round( GetRetailPrice(6,p.product_sku) * GetSalesChannelFee(6),2) as ebay_fee,
      round( GetRetailPrice(7,p.product_sku) * GetSalesChannelFee(7),2) as local_fee,
      round( GetRetailPrice(8,p.product_sku) * GetSalesChannelFee(8),2) as website_fee,
      round( GetRetailPrice(9,p.product_sku) * GetSalesChannelFee(9),2) as fba_fee,
      round( GetRetailPrice(10,p.product_sku) * GetSalesChannelFee(10),2) as fbm_fee ,
      round( GetCostPrice(12,p.product_sku) * GetSalesChannelFee(12),2) as way_fee ,
    
    
      GetRetailPrice(1,p.product_sku) as avcwh_retail_price,
      GetRetailPrice(2,p.product_sku) as avcds_retail_price,
      GetRetailPrice(3,p.product_sku) as avcdi_retail_price,
      GetRetailPrice(4,p.product_sku) as wmdsv_retail_price,
      GetRetailPrice(5,p.product_sku) as wmmkp_retail_price,
      GetRetailPrice(6,p.product_sku) as ebay_retail_price,
      GetRetailPrice(7,p.product_sku) as local_retail_price,
      GetRetailPrice(8,p.product_sku) as website_retail_price,
      GetRetailPrice(9,p.product_sku) as fba_retail_price,
      GetRetailPrice(10,p.product_sku) as fbm_retail_price,
      GetRetailPrice(12,p.product_sku) as wayfair_retail_price,
    
      GetCostPrice(1,p.product_sku) as avcwh_cost,
      GetCostPrice(2,p.product_sku) as avcds_cost,
      GetCostPrice(3,p.product_sku) as avcdi_cost,
      GetCostPrice(4,p.product_sku) as wmdsv_cost,
      GetCostPrice(5,p.product_sku) as wmmkp_cost,
      GetCostPrice(6,p.product_sku) as ebay_cost,
      GetCostPrice(7,p.product_sku) as local_cost,
      GetCostPrice(8,p.product_sku) as website_cost,
      GetCostPrice(9,p.product_sku) as fba_cost,
      GetCostPrice(10,p.product_sku) as fbm_cost,
      GetCostPrice(12,p.product_sku) as wayfair_cost,
    
      case when  GetCostPrice(1,p.product_sku) > 0 then
      round(
              GetCostPrice(1,p.product_sku) - pi.fob_us
              -( pi.per_mkt + pi.per_promotion + pi.per_return + 2 + pi.per_duty +1.5 + pi.per_wh_fee + pi.per_handing_fee + GetSalesChannelFee(1) * 100) * GetCostPrice(1,p.product_sku)/100  
            ,2) else 0 end as avcwh_profit ,
    
       case when GetCostPrice(2,p.product_sku)> 0 then
       round (
               GetCostPrice(2,p.product_sku) - pi.fob_us 
               -(pi.per_mkt + pi.per_promotion + pi.per_return + 2 + pi.per_duty + 1.5 + pi.per_wh_fee + pi.per_handing_fee +  GetSalesChannelFee(2) * 100 ) * GetCostPrice(2,p.product_sku)/100
               ,2) else 0 end as avcds_profit ,
               
        case when GetCostPrice(3,p.product_sku)> 0 then
        round (
                GetCostPrice(3,p.product_sku) - GetMaxFOBChinaOrVN(p.product_sku)
                -(pi.per_mkt + pi.per_promotion + pi.per_return + 2 + pi.per_duty + 1.5 + pi.per_wh_fee + pi.per_handing_fee +  GetSalesChannelFee(2) * 100 ) * GetCostPrice(3,p.product_sku)/100
                ,2) else 0 end as avcdi_profit,
       
       case when GetCostPrice(4,p.product_sku) > 0 then
       round(
              GetCostPrice(4,p.product_sku) -pi.fob_us 
              -( pi.per_mkt + pi.per_promotion + pi.per_return +2+ pi.per_duty +1.5  + pi.per_wh_fee + pi.per_handing_fee +  GetSalesChannelFee(4) * 100) * GetCostPrice(4,p.product_sku)/100
             ,2) else 0 end as wmdsv_profit ,
    
       case when GetRetailPrice(5,p.product_sku) > 0 then
       round(
             GetRetailPrice(5,p.product_sku) -pi.fob_us -  pi.shiping_fee_est
             - (pi.per_mkt + pi.per_promotion + pi.per_return + 2 + pi.per_duty + 1.5 + pi.per_wh_fee + pi.per_handing_fee + GetSalesChannelFee(5) * 100) * GetRetailPrice(5,p.product_sku)/100
            ,2) else 0 end as wmmkp_profit ,
    
       case when GetRetailPrice(6,p.product_sku) > 0 then
       round(
             GetRetailPrice(6,p.product_sku) - (pi.fob_us + pi.shiping_fee_est)
             -( pi.per_mkt + pi.per_promotion + pi.per_return + 2 + pi.per_duty + 1.5 + pi.per_wh_fee + pi.per_handing_fee +  GetSalesChannelFee(6) * 100)* GetRetailPrice(6,p.product_sku)/100 
           ,2) else 0 end as ebay_profit ,
    
       case when GetRetailPrice(7,p.product_sku)> 0 then
       round(
           GetRetailPrice(7,p.product_sku) -  pi.fob_us 
           - (pi.per_mkt + pi.per_promotion + pi.per_return + 2 + pi.per_duty + 1.5 + pi.per_wh_fee + pi.per_handing_fee + GetSalesChannelFee(7) *100) * GetRetailPrice(7,p.product_sku)/100
           ,2) else 0 end  as local_profit ,
    
       case when GetRetailPrice(8,p.product_sku) > 0 then
       round(
           GetRetailPrice(8,p.product_sku) - pi.fob_us 
           - ( pi.per_mkt + pi.per_promotion + pi.per_return +2 + pi.per_duty + 1.5  + pi.per_wh_fee + pi.per_handing_fee + GetSalesChannelFee(8) * 100) * GetRetailPrice(8,p.product_sku)/100 
           ,2) else 0 end as website_profit ,
    
      case when GetRetailPrice(9,p.product_sku)> 0 then
      round(
        GetRetailPrice(9,p.product_sku) -  (pi.fob_us + pi.	fba_shipping_est)
        - (pi.per_mkt + pi.per_promotion + pi.per_return + 2 + pi.per_duty + 1.5 + pi.per_wh_fee + pi.per_handing_fee + GetSalesChannelFee(9) *100) * GetRetailPrice(9,p.product_sku)/100
        ,2) else 0 end  as fba_profit ,
    
      case when GetRetailPrice(10,p.product_sku)> 0 then
      round(
              GetRetailPrice(10,p.product_sku) - (pi.fob_us + pi.shiping_fee_est) 
              -( 
                pi.per_mkt + pi.per_promotion + pi.per_return +2 + pi.per_duty + 1.5  + pi.per_wh_fee + pi.per_handing_fee  + GetSalesChannelFee(10) * 100 ) * GetRetailPrice(10,p.product_sku)/100  
          ,2) else 0 end  as fbm_profit ,
    
       case when GetCostPrice(12,p.product_sku) > 0 then
       round(
            GetCostPrice(12,p.product_sku) - pi.fob_us
            - (pi.per_mkt + pi.per_promotion + pi.per_return +2  + pi.per_duty + 1.5 + pi.per_wh_fee + pi.per_handing_fee +  GetSalesChannelFee(12) * 100 ) * GetCostPrice(12,p.product_sku)/100
            ,2) else 0 end as wayfair_profit
    
        from sal_product_informations pi 
        left join prd_product p on pi.sku = p.product_sku
        left join prd_brands br on p.brand_id = br.id
        where ( 1 = 1 )";
    

       $sqlFilter = '';
       if($Sku <> ''){ $sqlFilter =  $sqlFilter. " and p.product_sku = '$Sku' " ;}
       if($Title <> ''){ $sqlFilter =  $sqlFilter. " and p.title like  '%$Title%' " ;}
       if($Brand <> 0 ){ $sqlFilter =  $sqlFilter. " and br.id =  $Brand " ;}
  
       $sql = $sql . $sqlFilter . "order by br.brand_name,p.product_sku ";

      
       $SalesProductInfors =  DB::connection('mysql')->select($sql);
  
        $Sku = $request->input('sku');
        $Title  = $request->input('title');
        $Brand  = $request->input('brand');
  
        
        $sql= "  select  p.id , p.title, p.product_sku as sku ,GetAsin(p.product_sku,1,0) as amz_asin ,
        GetAsin(p.product_sku,3,1) as ebay_infidealz, GetAsin(p.product_sku,3,2) as ebay_inc,
        GetAsin(p.product_sku,3,3) as ebay_fitness,GetAsin(p.product_sku,2,0) as wm_item_id ,
         GetAsin(p.id,6,0) as wayfair_asin from prd_product p 
        inner join prd_brands br on p.brand_id = br.id
        where company_id <> 1 ";
  
        $sql =$sql .   $sqlFilter ;
        $Asins = DB::connection('mysql')->select($sql);
        
  
        $sql = " select 0 as id, 'All' as name union select id, brand_name as name from  prd_brands ";
        $Brands = DB::connection('mysql')->select($sql);
  
        return view('SAL.SalesProductInfor.listnew',compact(['SalesProductInfors','Brands','Sku','Title','Brand','Asins']));

      }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
     return view('SAL.SalesProductInfor.add');
    }
    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
      if(SalesProductInfor::create($request->all()))
      {
        $id = 0;
        $sql = " SELECT id FROM sal_product_informations ORDER BY id DESC LIMIT 1" ;
        
        $ids = DB::connection('mysql')->select($sql);
        foreach($ids as $id ){$id = $id->id;}
        //dd($id);

        DB::connection('mysql')->select('call SAL_CreateChannelCostAndPrice(?)',[$id]);

        $dsProduct = SalesProductInfor::find($id);
        $Sku  = $dsProduct->sku;

        $sql = " select pp.id, channel_id,sc.name, cost,retail_price
        from sal_product_channel_price pp inner join sal_channels sc
        on pp.channel_id = sc.id
        where sku = '$Sku'";
        $ProductCostPrices = DB::connection('mysql')->select($sql);
        foreach($ProductCostPrices as $ProductCostPrice )


        $sql = " select id, name from sal_channels " ;
        $dsChannels = DB::connection('mysql')->select($sql);
        
        return view('SAL.SalesProductInfor.edit',compact(['id','dsProduct','ProductCostPrice','dsChannels']));
        
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
      $dsProduct = SalesProductInfor::find($id);
      $Sku  = $dsProduct->sku;
      
      
      $sql = " select pp.id, channel_id,sc.name, cost,retail_price
      from sal_product_channel_price pp inner join sal_channels sc
      on pp.channel_id = sc.id
      where sku = '$Sku'";
      $ProductCostPrices = DB::connection('mysql')->select($sql);
      foreach($ProductCostPrices as $ProductCostPrice )

      $sql = " select id, name from sal_channels " ;
      $dsChannels = DB::connection('mysql')->select($sql);
      
      return view('SAL.SalesProductInfor.show',compact(['id','dsProduct','ProductCostPrice','dsChannels']));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    //nó vô hàm này trước phải ko anh uh
  
    // ------------------------------------------------------------------------
    public function edit($id)
    {
       
        $dsProduct = SalesProductInfor::find($id);
        $Sku  = $dsProduct->sku;
        
        
        $sql = " select pp.id, channel_id,sc.name, cost,retail_price
        from sal_product_channel_price pp inner join sal_channels sc
        on pp.channel_id = sc.id
        where sku = '$Sku'";
        $ProductCostPrices = DB::connection('mysql')->select($sql);
        foreach($ProductCostPrices as $ProductCostPrice )

        $sql = " select id, name from sal_channels " ;
        $dsChannels = DB::connection('mysql')->select($sql);
        
        return view('SAL.SalesProductInfor.edit',compact(['id','dsProduct','ProductCostPrice','dsChannels']));
        
    }

    public function SaveNewChannelCostAndPrice(Request $request)
    {
      $sql = " select id as MyCount from sal_product_channel_price where channel_id  = $request->channel_id and sku ='$request->sku'";
      if(!$this->IsExist('mysql', $sql))
      {
          //dd($request);
        $data = array(
          'sku'=> $request->sku,
          'channel_id'=> $request->channel_id
        );
        $id = DB::table('sal_product_channel_price')->insert($data);
        if($id>0)
        {
          echo "<div class = 'alert alert-success'> Insert OK </div>";
        }
        else
        {
          echo "<div class = 'alert alert-danger'> Loi insert</div>";
        }
     }else
     {
      echo "<div class='alert alert-danger'> SKU này đã có giá trên Sales channel </div>";
     }
    
    }

    public function LoadCostAndPriceOnAllChannel($sku)
    {
      
      DB::connection('mysql')->select('call SAL_PrepareEstprofit(?)',[$sku]);
      
      $sql = " select id, name from sal_channels " ;
      $dsChannels = DB::connection('mysql')->select($sql);

      $sql = " select pp.id, sc.name as channel_name,channel_id,retail_price, per_cost, cost,some_fee,per_other_fee,est_profit
      from sal_product_channel_price pp inner join sal_channels sc
      on pp.channel_id = sc.id
      where  sku = '$sku' ";
      $data = DB::connection('mysql')->select($sql);
      
      return view('SAL.SalesProductInfor.ajax_list_channel',compact(['data','dsChannels']));
      //echo json_encode($data);
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
      $SalesProductInfor = SalesProductInfor::find($id);
      $SalesProductInfor->update($request->all()); 
      return redirect()->route('SalesProductInforController.index')->with(['success'=>'Update sản phẩm thành công']);
      /*
     $spi = SalesProductInfor::find($id);
     if( $spi)
     {
      $spi->sku = $request->input('sku');
      $spi->title = $request->input('title');
      $spi->the_height = $request->input('the_height');
      $spi->the_width = $request->input('the_width');
      $spi->the_length = $request->input('the_length');
      $spi->the_weight = $request->input('the_weight');
      

      $spi->per_deposit = $request->input('per_deposit');
      $spi->per_full_payment = $request->input('per_full_payment');
      $spi->per_rev_split_for_partner = $request->input('per_rev_split_for_partner');
      $spi->con20_capacity = $request->input('con20_capacity');
      $spi->exw_vn = $request->input('exw_vn');
      $spi->fob_vn = $request->input('fob_vn');
      $spi->fob_cn = $request->input('fob_cn');
      $spi->fob_us = $request->input('fob_us'); 
      $spi->cosg_est = $request->input('cosg_est'); 
      $spi->per_mkt = $request->input('per_mkt'); 
      $spi->per_promotion = $request->input('per_promotion'); 
      $spi->per_return = $request->input('per_return'); 
      $spi->per_duty = $request->input('per_duty'); 
      $spi->per_wh_fee = $request->input('per_wh_fee'); 
      $spi->per_handing_fee = $request->input('per_handing_fee'); 
      $spi->shiping_fee_est = $request->input('shiping_fee_est'); 
      $spi->retail_price = $request->input('retail_price'); 
      $spi->per_wholesales_price_min = $request->input('per_wholesales_price_min'); 
      $spi->per_wholesales_price_max = $request->input('per_wholesales_price_max'); 
      $spi->fba_shipping_est = $request->input('fba_shipping_est'); 


      $spi->save();
      
      return redirect()->route('SalesProductInforController.index')->with(['success'=>'Update sản phẩm thành công']);
      }else
      {
       return redirect()->route('SalesProductInforController.index')->with(['error'=>'Update sản phẩm không thành công']);
      }
     */ 
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
     //------------------------------------------
     public function ImportProductSalesInfor(Request $request)
     {
       print_r('Bắt đầu lúc : '.date('Y-m-d H:i:s'));
       print_r('<br>');

       ini_set('memory_limit','2548M');
       set_time_limit(15000);

       $RowBegin = 5;
       $RowEnd = 0;

       $validator = Validator::make($request->all(),['file'=>'required|max:45000|mimes:xlsx,xls,csv']);

       if($validator->passes())
       {
         $file = $request->file('file');
         $reader = new \PhpOffice\PhpSpreadsheet\Reader\Xlsx();

          $RowBegin = 5;
          $reader->setLoadSheetsOnly(["product", "product"]);
          $spreadsheet = $reader->load($file);
          $RowEnd = $spreadsheet->getActiveSheet()->getHighestRow();
          print_r ('last record of produtcs '.$RowEnd );
          print_r ( '<br>');
          for($i=$RowBegin; $i <= $RowEnd; $i++)
          {
           $title = $spreadsheet->getActiveSheet()->getCellByColumnAndRow(1,$i)->getValue();
           $sku = $spreadsheet->getActiveSheet()->getCellByColumnAndRow(2,$i)->getValue();
           $product_id = $this->GetProductIdFromSku( $sku );
           $the_length= $spreadsheet->getActiveSheet()->getCellByColumnAndRow(4,$i)->getValue();
           if($the_length == ''){$the_length=0;}
           $the_width= $spreadsheet->getActiveSheet()->getCellByColumnAndRow(5,$i)->getValue();
           if($the_width == ''){$the_width=0;}
           $the_height= $spreadsheet->getActiveSheet()->getCellByColumnAndRow(6,$i)->getValue();
           if($the_height == ''){$the_height=0;}
           $the_weight = $spreadsheet->getActiveSheet()->getCellByColumnAndRow(7,$i)->getValue();
           if($the_weight == ''){$the_weight=0;}

           $per_deposit = 100 * $spreadsheet->getActiveSheet()->getCellByColumnAndRow(9,$i)->getValue();
           if($per_deposit == ''){$per_deposit=0;}
           $per_full_payment= 100 * $spreadsheet->getActiveSheet()->getCellByColumnAndRow(10,$i)->getValue();
           if( $per_full_payment== ''){ $per_full_payment=0;}
           $per_rev_split_for_partner=  100 *$spreadsheet->getActiveSheet()->getCellByColumnAndRow(11,$i)->getValue();
           if($per_rev_split_for_partner == ''){$per_rev_split_for_partner = 0;}
           $con20_capacity= $spreadsheet->getActiveSheet()->getCellByColumnAndRow(12,$i)->getValue();
           if($con20_capacity == ''){ $con20_capacity = 0;}
           $exw_vn = $spreadsheet->getActiveSheet()->getCellByColumnAndRow(13,$i)->getValue();
           if($exw_vn ==''){$exw_vn = 0;}
           $fob_vn= $spreadsheet->getActiveSheet()->getCellByColumnAndRow(14,$i)->getValue();
           if($fob_vn ==''){$fob_vn = 0;}
           $fob_cn= $spreadsheet->getActiveSheet()->getCellByColumnAndRow(15,$i)->getValue();
           if($fob_cn ==''){$fob_cn = 0;}
           $fob_us= $spreadsheet->getActiveSheet()->getCellByColumnAndRow(16,$i)->getValue();
           if($fob_us ==''){$fob_us = 0;}
           $cosg_est = $spreadsheet->getActiveSheet()->getCellByColumnAndRow(17,$i)->getValue();
           if($cosg_est==''){$cosg_est=0;}
           $per_mkt =  100 *$spreadsheet->getActiveSheet()->getCellByColumnAndRow(19,$i)->getValue();
           if($per_mkt==''){$per_mkt=0;}
           $per_promotion =  100 * $spreadsheet->getActiveSheet()->getCellByColumnAndRow(20,$i)->getValue();
           if($per_promotion==''){$per_promotion=0;}
           $per_return =  100 *$spreadsheet->getActiveSheet()->getCellByColumnAndRow(21,$i)->getValue();
           if($per_return==''){$per_return=0;}
           $per_duty=  100 *$spreadsheet->getActiveSheet()->getCellByColumnAndRow(23,$i)->getValue();
           if($per_duty==''){$per_duty=0;}
           $per_wh_fee= 100 * $spreadsheet->getActiveSheet()->getCellByColumnAndRow(25,$i)->getValue();
           if($per_wh_fee ==''){$per_wh_fee=0;}
           $per_handing_fee=  100 *$spreadsheet->getActiveSheet()->getCellByColumnAndRow(26,$i)->getValue();
           if($per_handing_fee== ''){$per_handing_fee=0;}
           $shiping_fee_est= $spreadsheet->getActiveSheet()->getCellByColumnAndRow(30,$i)->getValue();
           if( $shiping_fee_est==''){ $shiping_fee_est=0;}
           $per_wholesales_price_min=  100 *$spreadsheet->getActiveSheet()->getCellByColumnAndRow(33,$i)->getValue();
           if($per_wholesales_price_min ==''){$per_wholesales_price_min = 0;}
           $per_wholesales_price_max=  100 *$spreadsheet->getActiveSheet()->getCellByColumnAndRow(34,$i)->getValue();
           if($per_wholesales_price_max ==''){$per_wholesales_price_max = 0;}
           $retail_price= $spreadsheet->getActiveSheet()->getCellByColumnAndRow(38,$i)->getValue();
           if($retail_price==''){$retail_price=0;}

           $fbm_retail_price = $spreadsheet->getActiveSheet()->getCellByColumnAndRow(60,$i)->getValue();
           if($fbm_retail_price==''){$fbm_retail_price=0;}

           $avcds_retail_price = $spreadsheet->getActiveSheet()->getCellByColumnAndRow(61,$i)->getValue();
           if($avcds_retail_price==''){$avcds_retail_price=0;}

           $avcwh_retail_price = $spreadsheet->getActiveSheet()->getCellByColumnAndRow(62,$i)->getValue();
           if($avcwh_retail_price==''){$avcwh_retail_price=0;}

           $wmdsv_retail_price = $spreadsheet->getActiveSheet()->getCellByColumnAndRow(63,$i)->getValue();
           if($wmdsv_retail_price==''){$wmdsv_retail_price=0;}

           $wmmkp_retail_price = $spreadsheet->getActiveSheet()->getCellByColumnAndRow(64,$i)->getValue();
           if($wmmkp_retail_price==''){$wmmkp_retail_price=0;}

           $ebay_retail_price = $spreadsheet->getActiveSheet()->getCellByColumnAndRow(65,$i)->getValue();
           if($ebay_retail_price==''){$ebay_retail_price=0;}

           $local_retail_price = $spreadsheet->getActiveSheet()->getCellByColumnAndRow(66,$i)->getValue();
           if($local_retail_price==''){$local_retail_price=0;}

           $website_retail_price = $spreadsheet->getActiveSheet()->getCellByColumnAndRow(67,$i)->getValue();
           if($website_retail_price==""){$website_retail_price=0;}

           $wayfair_retail_price = $spreadsheet->getActiveSheet()->getCellByColumnAndRow(68,$i)->getValue();
           if($wayfair_retail_price==0){$wayfair_retail_price=0;}

            $per_avc_ds= 100 * $spreadsheet->getActiveSheet()->getCellByColumnAndRow(71,$i)->getValue();
            if($per_avc_ds==0){$per_avc_ds=0;}

            $per_avc_wh= 100 * $spreadsheet->getActiveSheet()->getCellByColumnAndRow(72,$i)->getValue();
            if($per_avc_wh==0){$per_avc_wh=0;}

            $per_wm_dsv= 100 * $spreadsheet->getActiveSheet()->getCellByColumnAndRow(73,$i)->getValue();
            if($per_wm_dsv==0){$per_wm_dsv=0;}

            $per_wayfair= 100 * $spreadsheet->getActiveSheet()->getCellByColumnAndRow(78,$i)->getValue();
            if($per_wayfair==0){$per_wayfair=0;}


           $avc_ds_cost= $spreadsheet->getActiveSheet()->getCellByColumnAndRow(81,$i)->getValue();
           if($avc_ds_cost==''){$avc_ds_cost=0;}
           $avc_wh_cost= $spreadsheet->getActiveSheet()->getCellByColumnAndRow(82,$i)->getValue();
           if($avc_wh_cost==''){$avc_wh_cost=0;}
           $wm_dsv_cost= $spreadsheet->getActiveSheet()->getCellByColumnAndRow(83,$i)->getValue();
           if($wm_dsv_cost==''){$wm_dsv_cost=0;}
           $way_fair_cost = $spreadsheet->getActiveSheet()->getCellByColumnAndRow(88,$i)->getValue();
           if($way_fair_cost==''){$way_fair_cost = 0;}

           $fba_retail = $spreadsheet->getActiveSheet()->getCellByColumnAndRow(100,$i)->getValue();
           if($fba_retail==''){$fba_retail = 0;}

           if($sku<>'')
           {
             $sql = " select id as MyCount from sal_product_informations where sku ='$sku'";
             if(!$this->IsExist('mysql', $sql)) //
             {
               DB::connection('mysql')->table('sal_product_informations')->insert(
               [
                 'sku'=>$sku,
                 'title'=>$title, 
                 'the_length'=>$the_length,
                 'the_width'=>$the_width,
                 'the_height'=>$the_height,
                 'the_weight'=>$the_weight,
                 'per_deposit' =>$per_deposit,
                 'per_full_payment' =>$per_full_payment,
                 'per_rev_split_for_partner' =>$per_rev_split_for_partner,
                 'con20_capacity' =>$con20_capacity,
                 'exw_vn' => $exw_vn,
                 'fob_vn' =>$fob_vn,
                 'fob_cn' =>$fob_cn,
                 'fob_us' => $fob_us,
                 'cosg_est' => $cosg_est,
                 'per_mkt' =>$per_mkt,
                 'per_promotion' => $per_promotion,
                 'per_return' =>$per_return,
                 'per_duty' => $per_duty,
                 'per_wh_fee' =>$per_wh_fee,
                 'per_handing_fee' => $per_handing_fee,
                 'shiping_fee_est' =>$shiping_fee_est,
                 'retail_price' => $retail_price,
                 'per_wholesales_price_min' => $per_wholesales_price_min,
                 'per_wholesales_price_max' => $per_wholesales_price_max
               ]);
             }else
             {
               $sql = " update sal_product_informations
               set per_deposit = $per_deposit,
               per_full_payment =$per_full_payment,
               per_rev_split_for_partner =$per_rev_split_for_partner,
               con20_capacity =$con20_capacity,
               exw_vn = $exw_vn,
               fob_vn =$fob_vn,
               fob_cn =$fob_cn,
               fob_us = $fob_us,
               cosg_est = $cosg_est,
               per_mkt =$per_mkt,
               per_promotion = $per_promotion,
               per_return =$per_return,
               per_duty = $per_duty,
               per_wh_fee =$per_wh_fee,
               per_handing_fee = $per_handing_fee,
               shiping_fee_est =$shiping_fee_est,
               retail_price = $retail_price,
               per_wholesales_price_min = $per_wholesales_price_min,
               per_wholesales_price_max = $per_wholesales_price_max
               where sku ='$sku'";
               DB::connection('mysql')->select($sql);
             }// end if
 

             // cập nhật giá cost,price vào các kênh
             // 1. Kênh avc -wh
             $sql = " select count(id)as MyCount from  sal_product_channel_price
             where sku = '$sku' and channel_id = 1 ";
             if(!$this->IsExist('mysql', $sql)) {
               $sql= " insert into sal_product_channel_price(product_id,sku,channel_id,cost,retail_price,per_cost)
               values($product_id,'$sku',1,$avc_wh_cost,$avcwh_retail_price,$per_avc_wh)";
             }else{
               $sql= " update sal_product_channel_price set cost = $avc_wh_cost,
               retail_price = $avcwh_retail_price,per_cost = $per_avc_wh
               where sku = '$sku'  and channel_id = 1";
             }DB::connection('mysql')->select($sql);

             // 2. Kênh avc-ds
             $sql = " select count(id)as MyCount from  sal_product_channel_price
             where sku = '$sku' and channel_id = 2 ";
             if(!$this->IsExist('mysql', $sql)) {
               $sql= " insert into sal_product_channel_price(product_id,sku,channel_id,cost,retail_price,per_cost)
               values($product_id,'$sku',2,$avc_ds_cost,$avcds_retail_price,$per_avc_ds)";
             }else{
               $sql= " update sal_product_channel_price set cost = $avc_ds_cost,
               retail_price = $avcds_retail_price,per_cost =$per_avc_ds
               where sku = '$sku'  and channel_id = 2";
             }DB::connection('mysql')->select($sql);

             // 4. Kênh wm-dsv
             $sql = " select count(id)as MyCount from  sal_product_channel_price
             where sku = '$sku' and channel_id = 4 ";
             if(!$this->IsExist('mysql', $sql)) {
               $sql= " insert into sal_product_channel_price(product_id,sku,channel_id,cost,retail_price,per_cost)
               values($product_id,'$sku',4, $wm_dsv_cost,$wmdsv_retail_price, $per_wm_dsv)";
             }else{
               $sql= " update sal_product_channel_price set cost = $wm_dsv_cost,
               retail_price = $wmdsv_retail_price, per_cost = $per_wm_dsv
               where sku = '$sku'  and channel_id = 4";
             }DB::connection('mysql')->select($sql);

              // 5. Kênh wm-mkp
              $sql = " select count(id)as MyCount from  sal_product_channel_price
              where sku = '$sku' and channel_id = 5 ";
              if(!$this->IsExist('mysql', $sql)) {
                $sql= " insert into sal_product_channel_price(product_id,sku,channel_id,cost,retail_price)
                values($product_id,'$sku',5,0,$wmmkp_retail_price)";
              }else{
                $sql= " update sal_product_channel_price set cost = 0,
                retail_price = $wmmkp_retail_price
                where sku = '$sku'  and channel_id = 5";
              }DB::connection('mysql')->select($sql);

              // 6. Kênh Ebay
              $sql = " select count(id)as MyCount from  sal_product_channel_price
              where sku = '$sku' and channel_id = 6 ";
              if(!$this->IsExist('mysql', $sql)) {
                $sql= " insert into sal_product_channel_price(product_id,sku,channel_id,cost,retail_price)
                values($product_id,'$sku',6,0,$ebay_retail_price)";
              }else{
                $sql= " update sal_product_channel_price set cost = 0,
                retail_price = $ebay_retail_price
                where sku = '$sku'  and channel_id = 6";
              }DB::connection('mysql')->select($sql);

              // 7. Loclal
              $sql = " select count(id)as MyCount from  sal_product_channel_price
              where sku = '$sku' and channel_id = 7 ";
              if(!$this->IsExist('mysql', $sql)) {
                $sql= " insert into sal_product_channel_price(product_id,sku,channel_id,cost,retail_price)
                values($product_id,'$sku',7,0,$local_retail_price)";
              }else{
                $sql= " update sal_product_channel_price set cost = 0,
                retail_price = $local_retail_price
                where sku = '$sku'  and channel_id = 7 ";
              }DB::connection('mysql')->select($sql);

              // 8. Website
              $sql = " select count(id)as MyCount from  sal_product_channel_price
              where sku = '$sku' and channel_id = 8 ";
              if(!$this->IsExist('mysql', $sql)) {
                $sql= " insert into sal_product_channel_price(product_id,sku,channel_id,cost,retail_price)
                values($product_id,'$sku',8,0,$website_retail_price)";
              }else{
                $sql= " update sal_product_channel_price set cost = 0,
                retail_price = $local_retail_price
                where sku = '$sku'  and channel_id = 8 ";
              }DB::connection('mysql')->select($sql);

              // 9. FBA
              $sql = " select count(id)as MyCount from  sal_product_channel_price
              where sku = '$sku' and channel_id = 9";
              if(!$this->IsExist('mysql', $sql)) {
                $sql= " insert into sal_product_channel_price(product_id,sku,channel_id,cost,retail_price)
                values($product_id,'$sku',9,0,$fba_retail)";
              }else{
                $sql= " update sal_product_channel_price set cost = 0,
                retail_price = $fba_retail
                where sku = '$sku'  and channel_id = 9 ";
              }DB::connection('mysql')->select($sql);

              // 10. FBM
              $sql = " select count(id)as MyCount from  sal_product_channel_price
              where sku = '$sku' and channel_id = 10 ";
              if(!$this->IsExist('mysql', $sql)) {
                $sql= " insert into sal_product_channel_price(product_id,sku,channel_id,cost,retail_price)
                values($product_id,'$sku',10,0,$fbm_retail_price)";
              }else{
                $sql= " update sal_product_channel_price set cost = 0,
                retail_price = $fbm_retail_price
                where sku = '$sku'  and channel_id = 10 ";
              }DB::connection('mysql')->select($sql);
              // 12. way_fair
              $sql = " select count(id)as MyCount from  sal_product_channel_price
              where sku = '$sku' and channel_id = 12 ";
              if(!$this->IsExist('mysql', $sql)) {
                $sql= " insert into sal_product_channel_price(product_id,sku,channel_id,cost,retail_price,per_cost )
                values($product_id,'$sku',12, $way_fair_cost,$wayfair_retail_price,$per_wayfair)";
              }else{
                $sql= " update sal_product_channel_price set cost = $way_fair_cost,
                retail_price = $wayfair_retail_price, per_cost  = $per_wayfair
                where sku = '$sku'  and channel_id = 12";
              }DB::connection('mysql')->select($sql);
           }// end if sku <>''
         }// End for

         // Insert ASIN
         $RowBegin = 3;
         $reader->setLoadSheetsOnly(["asin", "asin"]);
         $spreadsheet = $reader->load($file);
         $RowEnd = $spreadsheet->getActiveSheet()->getHighestRow();
         print_r ('last record of asin '.$RowEnd );
         print_r ( '<br>');
         $ProductID = 0;
         $ChannelID = 0;
         $StoreID = 0;

         for($i=$RowBegin; $i <= $RowEnd; $i++)
         {
          $sku = trim($spreadsheet->getActiveSheet()->getCellByColumnAndRow(2,$i)->getValue(),' ');
          $amz_asin = trim($spreadsheet->getActiveSheet()->getCellByColumnAndRow(5,$i)->getValue(),' ');
          print_r('chiều dài asin:'.strlen($amz_asin));          
          print_r('<br>');          
          $ebay_infidealz= trim($spreadsheet->getActiveSheet()->getCellByColumnAndRow(6,$i)->getValue(),' ');
          $ebay_inc= trim($spreadsheet->getActiveSheet()->getCellByColumnAndRow(7,$i)->getValue(),' ');
          $ebay_fitness= trim($spreadsheet->getActiveSheet()->getCellByColumnAndRow(8,$i)->getValue(),' ');
          $wm_item_id = trim($spreadsheet->getActiveSheet()->getCellByColumnAndRow(9,$i)->getValue(),' ');
          $wayfair_asin= trim($spreadsheet->getActiveSheet()->getCellByColumnAndRow(11,$i)->getValue(),' ');
            
            if($sku<>'')
            {
              $sql = " select id from prd_product where 	product_sku = '$sku'"; 
              $ds = DB::connection('mysql')->select($sql);
              foreach( $ds as $d ) { $ProductID= $d->id; }

              DB::connection('mysql')->table('sal_product_asins')->insert(
              ['product_id'=>$ProductID,'market_place'=>1,'store_id'=>0,'asin'=>$amz_asin]);

              DB::connection('mysql')->table('sal_product_asins')->insert(
              ['product_id'=>$ProductID,'market_place'=>3,'store_id'=>1,'asin'=>$ebay_infidealz]);

              DB::connection('mysql')->table('sal_product_asins')->insert(
              ['product_id'=>$ProductID,'market_place'=>3,'store_id'=>2,'asin'=>$ebay_inc]);

              DB::connection('mysql')->table('sal_product_asins')->insert(
              ['product_id'=>$ProductID,'market_place'=>3,'store_id'=>3,'asin'=>$ebay_fitness]);

              DB::connection('mysql')->table('sal_product_asins')->insert(
              ['product_id'=>$ProductID,'market_place'=>2,'store_id'=>0,'asin'=>$wm_item_id]);

              DB::connection('mysql')->table('sal_product_asins')->insert(
              ['product_id'=>$ProductID,'market_place'=>6,'store_id'=>0,'asin'=>$wayfair_asin]);
              
            }
          }//For

          $RowBegin = 3;
          $reader->setLoadSheetsOnly(["promotion", "promotion"]);
          $spreadsheet = $reader->load($file);
          $RowEnd = $spreadsheet->getActiveSheet()->getHighestRow();
          print_r ('last record of promotion '.$RowEnd );
          print_r ( '<br>');
          for($i=$RowBegin; $i <= $RowEnd; $i++)
          {
           $AmzAsin = $spreadsheet->getActiveSheet()->getCellByColumnAndRow(1,$i)->getValue();
           $PromotionTypeName = $spreadsheet->getActiveSheet()->getCellByColumnAndRow(2,$i)->getValue();
           $PromotionNo = $spreadsheet->getActiveSheet()->getCellByColumnAndRow(3,$i)->getValue();
           $StatusName = $spreadsheet->getActiveSheet()->getCellByColumnAndRow(4,$i)->getValue();
           $PerFunding = $spreadsheet->getActiveSheet()->getCellByColumnAndRow(5,$i)->getValue();
           $StartDate = $spreadsheet->getActiveSheet()->getCellByColumnAndRow(6,$i)->getFormattedValue();
           $StartDate = date("Y-m-d", strtotime( $StartDate));  
           $EndDate = $spreadsheet->getActiveSheet()->getCellByColumnAndRow(7,$i)->getFormattedValue();
           $EndDate = date("Y-m-d", strtotime( $EndDate));  
           $Funding = $spreadsheet->getActiveSheet()->getCellByColumnAndRow(8,$i)->getValue();
           $UnitSold = $spreadsheet->getActiveSheet()->getCellByColumnAndRow(10,$i)->getValue();
           $AmountSpent= $spreadsheet->getActiveSheet()->getCellByColumnAndRow(11,$i)->getFormattedValue();
           $Revenue = $spreadsheet->getActiveSheet()->getCellByColumnAndRow(12,$i)->getValue();
           $Channel= $spreadsheet->getActiveSheet()->getCellByColumnAndRow(13,$i)->getValue();

           
           if( $PerFunding =='') { $PerFunding =0;}
           if( $Funding =='') { $Funding =0;}
           if( $UnitSold  =='') { $UnitSold  = 0;}
           if( $AmountSpent =='') {  $AmountSpent  =0;}
           if( $Revenue =='') {  $Revenue  =0;}
           
           $AmountSpent =   $Funding  * $UnitSold ;

           $ProductID = 0;
           $PromotionTypeID = 0;
           $StatusID = 0;
           $Channel= 0;
           $sql = " select p.id from prd_product p inner join sal_product_asins pa on p.id = pa.product_id
           where pa.market_place =1 and pa.asin ='$AmzAsin' ";

           $ds = DB::connection('mysql')->select($sql);
           foreach( $ds as $d ){$ProductID  = $d->id;}
           
           if($PromotionTypeName =='Best Deals'){$PromotionTypeID = 1;}
           else if($PromotionTypeName =='Price Discount'){$PromotionTypeID = 2;} 
           else if($PromotionTypeName=='Promo Code'){$PromotionTypeID = 3;} 
           else if($PromotionTypeName =='Lightning Deal'){$PromotionTypeID = 4;} 


           if( $StatusName =='Init'){$StatusID =1;}
           if( $StatusName =='Approved'){$StatusID =2;}
           if( $StatusName =='Running'){$StatusID =3;}
           if( $StatusName =='Finished'){$StatusID =4;}
           if( $StatusName =='Canceled'){$StatusID =5;}
           if( $StatusName =='ERROR'){$StatusID =6;}
           
                     
           if($Channel=='AVC-DS'){$Channel=2;}
           if( $PromotionNo <> '' )
           {
            $sql = " select count(id)as MyCount from  sal_promotions where  promotion_no = '$PromotionNo'";
            if(!$this->IsExist('mysql', $sql)) // Master promtion chưa tồn tại
            {
              $id = DB::table('sal_promotions')->insertGetId(
              [ 'promotion_no' => $PromotionNo,'Promotion_type'=>$PromotionTypeID,'promotion_status'=>$StatusID,
              'from_date'=>$StartDate, 'to_date'=>$EndDate,'channel_id' =>$Channel ]);
            
              DB::table('sal_promotions_dt')->insert(
              ['promotion_id' =>$id,'product_id'=>$ProductID,'per_funding'=>$PerFunding, 'funding'=>$Funding,'unit_sold' =>$UnitSold,'amount_spent'=>$AmountSpent,'revenue'=>$Revenue ]);
            }
            else// master đã tồn tại
            {
              $sql = " select id  from  sal_promotions where  promotion_no = '$PromotionNo'";
              $ds = DB::connection('mysql')->select($sql);
              foreach( $ds as $d ){ $id = $d->id;}
                    
              DB::table('sal_promotions_dt')->insert(
              ['promotion_id' =>$id,'product_id'=>$ProductID,'per_funding'=>$PerFunding, 'funding'=>$Funding,'unit_sold' =>$UnitSold,'amount_spent'=>$AmountSpent,'revenue'=>$Revenue ]);
               
            }
          }
          
        }//For

        
        $RowBegin = 3;
        $reader->setLoadSheetsOnly(["order", "order"]);
        $spreadsheet = $reader->load($file);
        $RowEnd = $spreadsheet->getActiveSheet()->getHighestRow();
        print_r ('last record of order '.$RowEnd );
        print_r ( '<br>');
        $channel_id=0;
        $store_id = 0;
        $product_id=0;
        $id = 0;
        for($i=$RowBegin; $i <= $RowEnd; $i++)
        {
          $order_date = date('Y-m-d', time());
          $store_name = $spreadsheet->getActiveSheet()->getCellByColumnAndRow(1,$i)->getValue();
          $store_order_id	= $spreadsheet->getActiveSheet()->getCellByColumnAndRow(3,$i)->getValue();
          $order_status	= $spreadsheet->getActiveSheet()->getCellByColumnAndRow(4,$i)->getValue();
          $order_date	= $spreadsheet->getActiveSheet()->getCellByColumnAndRow(5,$i)->getFormattedValue();
          //$order_date	= $spreadsheet->getActiveSheet()->getCellByColumnAndRow(5,$i)->getValue();

          $order_date = date("Y-m-d h:i:s", strtotime( $order_date));  
         // print_r('The order date is: ' . $order_date	);
         // print_r('<br>'	);

        // $sql = " insert into test_ne(the_date) values('$order_date')";
        // print_r('sql is ' .$sql 	);
        // print_r('<br>'	);
         DB::connection('mysql')->select ($sql);


          $shipping_address	= $spreadsheet->getActiveSheet()->getCellByColumnAndRow(6,$i)->getValue();
          $shipto_state	= $spreadsheet->getActiveSheet()->getCellByColumnAndRow(7,$i)->getValue();
          $shipto_zipcode	= $spreadsheet->getActiveSheet()->getCellByColumnAndRow(8,$i)->getValue();
          $product_sku =  $spreadsheet->getActiveSheet()->getCellByColumnAndRow(9,$i)->getValue();
          $quantity= $spreadsheet->getActiveSheet()->getCellByColumnAndRow(12,$i)->getValue();
          if($quantity=='') $quantity = 0;
          $revenue= $spreadsheet->getActiveSheet()->getCellByColumnAndRow(13,$i)->getValue();
          if($revenue=='') $revenue = 0;

          $product_sku = $this->left($product_sku,4);

          if($revenue== 0 ||  $quantity==0 )
            $price =0;
          elseif( $quantity>0)
            $price = $revenue/ $quantity;
          else
            $price =0;

          switch ( $store_name) {
            case 'Amazon - Infideals - MFN':
              {
              $channel_id	= 9;
              $store_id = 0;
              break;
              }
            case 'Amazon - Infideals - AFN':
              {
              $channel_id	= 10;
              $store_id = 0;
              break;
              }
            case 'AVC DS':
              {
              $channel_id	= 2;
              $store_id = 0;
              break;
              }
            case 'Walmart DSV':
              {
              $channel_id	= 4;
              $store_id = 0;
              break;
              }
            case 'Walmart MKP':
              {
              $channel_id	= 5;
              $store_id = 0;
              break;
              }
            case 'Ebay - Yes4All_ Fitness':
              {
               $channel_id	= 6;
               $store_id = 1;
               break;
              }
            case 'Ebay - Infideals':
              {
               $channel_id	= 6; 
               $store_id = 3;
               break;
              }
            case 'Ebay - Yes4All_ inc':
              {
               $channel_id	= 6;
               $store_id = 2;
               break;
              }
            case 'Wayfair':
              $channel_id	= 12;
              break;
          }

          $sql = "select id from prd_product where product_sku = '$product_sku' ";
          $ds= DB::connection('mysql')->select($sql);
          foreach($ds as $d)  { $product_id = $d->id;}
          $id =0;
          if($channel_id == 9 ||$channel_id ==10 ) // ghi vào sal_amz_seller_orders
           {
            // Kiểm tra xem đã có order đó trong database chưa
            $sql = " select id  from sal_amz_seller_orders where order_no = '$store_order_id' 
            and channel_id  = $channel_id  ";
            $ds= DB::connection('mysql')->select($sql);

            foreach($ds as $d)  {  $id = $this->iif(is_null($d->id),0,$d->id); }

            if( $id == 0 ){// chua ton tai order nay trong database  
             
             // insert to master
              $id = DB::connection('mysql')->table('sal_amz_seller_orders')->insertGetId(
              ['order_no'=>$store_order_id,'order_date'=>$order_date,'channel_id'=>$channel_id,'status_name'=>$order_status, 
              'ship_to_address'=>$shipping_address,'ship_to_state'=>$shipto_state,'ship_to_zip'=>$shipto_zipcode]);

              // Kiem tra trong detail cua phieu nau da co hang nay chua
              $sql = " select id as MyCount from sal_amz_seller_order_dt where amz_order_id  = $id and product_id = $product_id ";
              if(!$this->IsExist('mysql',$sql)){// chua ton tai detail product nay
                // insert to detail
                DB::connection('mysql')->table('sal_amz_seller_order_dt')->insert(
                ['amz_order_id'=>$id,'product_id'=>$product_id,'quantity'=>$quantity,
                'price'=>$price,'amount'=>$revenue]);
              }
            }else{// da ton tai master
               // Kiem tra trong detail cua phieu nau da co hang nay chua
               $sql = " select id as MyCount from sal_amz_seller_order_dt where amz_order_id  = $id and product_id = $product_id ";
               if(!$this->IsExist('mysql',$sql)){// chua ton tai detail product nay
                 // insert to detail
                 DB::connection('mysql')->table('sal_amz_seller_order_dt')->insertGetId(
                 ['amz_order_id'=>$id,'product_id'=>$product_id,'quantity'=>$quantity,
                 'price'=>$price,'amount'=>$revenue]);
               }

            }
          }// end if channel = 9 or 10
          elseif($channel_id<=3 ) // avc
          {
            // Kiểm tra xem đã có order đó trong database chưa
            $id =0;
            $sql = " select id  from sal_amz_vendor_orders where order_no = '$store_order_id' 
            and channel_id  = $channel_id  ";
            $ds= DB::connection('mysql')->select($sql);

            foreach($ds as $d)  {  $id = $this->iif(is_null($d->id),0,$d->id); }

            if( $id == 0 ){// chua ton tai order nay trong database  
             
             // insert to master
              $id = DB::connection('mysql')->table('sal_amz_vendor_orders')->insertGetId(
              ['order_no'=>$store_order_id,'order_date'=>$order_date,'channel_id'=>$channel_id,'status_name'=>$order_status, 
              'ship_to_address'=>$shipping_address,'ship_to_state'=>$shipto_state,'ship_to_zip'=>$shipto_zipcode]);

              // Kiem tra trong detail cua phieu nau da co hang nay chua
              $sql = " select id as MyCount from sal_amz_vendor_order_dt where amz_order_id  = $id and product_id = $product_id ";
              if(!$this->IsExist('mysql',$sql)){// chua ton tai detail product nay
                // insert to detail
                 DB::connection('mysql')->table('sal_amz_vendor_order_dt')->insert(
                ['amz_order_id'=>$id ,'product_id'=>$product_id,'quantity'=>$quantity,
                'price'=>$price,'amount'=>$revenue]);
              }

            }else{// da ton tai master
               // Kiem tra trong detail cua phieu nau da co hang nay chua
               $sql = " select id as MyCount from sal_amz_vendor_order_dt where amz_order_id  = $id and product_id = $product_id ";
               if(!$this->IsExist('mysql',$sql)){// chua ton tai detail product nay
                 // insert to detail
                 DB::connection('mysql')->table('sal_amz_vendor_order_dt')->insert(
                 ['amz_order_id'=>$id,'product_id'=>$product_id,'quantity'=>$quantity,
                 'price'=>$price,'amount'=>$revenue]);
               }

            }
          }
          else// Các kênh còn lại không phải của amazon
          {
             // Kiểm tra xem đã có order đó trong database chưas
             $sql = " select id  from sal_orders where order_no = '$store_order_id' 
             and channel_id  = $channel_id  and store_id =$store_id ";
             $ds= DB::connection('mysql')->select($sql);
 
             foreach($ds as $d)  {  $id = $this->iif(is_null($d->id),0,$d->id); }
 
             if( $id == 0 ){// chua ton tai order nay trong database  
              
              // insert to master
               $id = DB::connection('mysql')->table('sal_orders')->insertGetId(
               ['order_no'=>$store_order_id,'order_date'=>$order_date,'channel_id'=>$channel_id,'store_id'=>$store_id,
               'status_name'=>$order_status, 
               'ship_to_address'=>$shipping_address,'ship_to_state'=>$shipto_state,'ship_to_zip'=>$shipto_zipcode]);
 
               // Kiem tra trong detail cua phieu nau da co hang nay chua
               $sql = " select id as MyCount from sal_order_dt where order_id   = $id and product_id = $product_id  ";
               if(!$this->IsExist('mysql',$sql)){// chua ton tai detail product nay
                 // insert to detail
                 DB::connection('mysql')->table('sal_order_dt')->insert(
                 ['order_id'=>$id,'product_id'=>$product_id,'quantity'=>$quantity,
                 'price'=>$price,'amount'=>$revenue]);
               }
 
             }else{// da ton tai master
                // Kiem tra trong detail cua phieu nau da co hang nay chua
                $sql = " select id as MyCount from sal_order_dt where order_id   = $id and product_id = $product_id ";
                if(!$this->IsExist('mysql',$sql)){// chua ton tai detail product nay
                  // insert to detail
                  DB::connection('mysql')->table('sal_order_dt')->insert(
                  ['order_id'=>$id,'product_id'=>$product_id,'quantity'=>$quantity,
                  'price'=>$price,'amount'=>$revenue]);
                }
 
             }

          }//end if Phân bổ vào các bảng dựa vào sales channel

        }// for
  
        $RowBegin = 3;
        $reader->setLoadSheetsOnly(["avcds_order", "avcds_order"]);
        $spreadsheet = $reader->load($file);
        $RowEnd = $spreadsheet->getActiveSheet()->getHighestRow();
        print_r ('last record of avcds_order '.$RowEnd );
        print_r ( '<br>');
        $channel_id=2;
        $store_id=0;
        $product_id=0;
        $id = 0;
        for($i=$RowBegin; $i <= $RowEnd; $i++)
        {
          $store_order_id = $spreadsheet->getActiveSheet()->getCellByColumnAndRow(1,$i)->getValue();
          $order_status	= $spreadsheet->getActiveSheet()->getCellByColumnAndRow(3,$i)->getValue();
          $WarehouseCode = $spreadsheet->getActiveSheet()->getCellByColumnAndRow(4,$i)->getValue();
         // $order_date	= $spreadsheet->getActiveSheet()->getCellByColumnAndRow(5,$i)->getFormattedValue();
          $order_date	= $spreadsheet->getActiveSheet()->getCellByColumnAndRow(5,$i)->getValue();
          $order_date = date("Y-m-d h:i:s", strtotime($order_date));  

          $RequiredShipDate = $spreadsheet->getActiveSheet()->getCellByColumnAndRow(6,$i)->getValue();
          $RequiredShipDate = date("Y-m-d h:i:s", strtotime($RequiredShipDate));  
          $ShipMethod = $spreadsheet->getActiveSheet()->getCellByColumnAndRow(7,$i)->getValue();

          $ShipMethodCode= $spreadsheet->getActiveSheet()->getCellByColumnAndRow(8,$i)->getValue();
          $ShipToName= $spreadsheet->getActiveSheet()->getCellByColumnAndRow(9,$i)->getValue();
          $shipping_address= $spreadsheet->getActiveSheet()->getCellByColumnAndRow(10,$i)->getValue();


          $ship_to_city = $spreadsheet->getActiveSheet()->getCellByColumnAndRow(13,$i)->getValue();
          $shipto_state	= $spreadsheet->getActiveSheet()->getCellByColumnAndRow(14,$i)->getValue();
          $shipto_zipcode= $spreadsheet->getActiveSheet()->getCellByColumnAndRow(15,$i)->getValue();

          $PhoneNumber= $spreadsheet->getActiveSheet()->getCellByColumnAndRow(17,$i)->getValue();
          $ItemCost	= $spreadsheet->getActiveSheet()->getCellByColumnAndRow(19,$i)->getValue();
          $SKU	= $spreadsheet->getActiveSheet()->getCellByColumnAndRow(20,$i)->getValue();
          $SKU = $this->left($SKU,4);
          $product_id =$this->GetProductIdFromSku($SKU);
          $ASIN	= $spreadsheet->getActiveSheet()->getCellByColumnAndRow(21,$i)->getValue();
          $ItemQuantity	= $spreadsheet->getActiveSheet()->getCellByColumnAndRow(23,$i)->getValue();
         // $Commission= $spreadsheet->getActiveSheet()->getCellByColumnAndRow(25,$i)->getValue();
          $TrackingID= $spreadsheet->getActiveSheet()->getCellByColumnAndRow(26,$i)->getValue();
          $ShippedDate= $spreadsheet->getActiveSheet()->getCellByColumnAndRow(27,$i)->getValue();
          $ShippedDate = date("Y-m-d h:i:s", strtotime($ShippedDate));  

           // Kiểm tra xem đã có order đó trong database chưa
           $id =0;
           $sql = " select id  from sal_amz_vendor_orders where order_no = '$store_order_id' 
           and channel_id  = $channel_id  ";
           $ds= DB::connection('mysql')->select($sql);

           foreach($ds as $d)  {  $id = $this->iif(is_null($d->id),0,$d->id); }

           if( $id == 0 ){// chua ton tai order nay trong database  
            
            // insert to master
             $id = DB::connection('mysql')->table('sal_amz_vendor_orders')->insertGetId(
             ['order_no'=>$store_order_id,'order_date'=>$order_date,'channel_id'=>$channel_id,
             'status_name'=>$order_status, 'wh_code'=>$WarehouseCode,'cus_name'=> $ShipToName,
             'ship_to_phone'=> $PhoneNumber,'ship_to_city'=>$ship_to_city,
             'shiped_method'=>$ShipMethod,'ship_method_code'=>$ShipMethodCode,
             'ship_to_address'=>$shipping_address,'ship_to_state'=>$shipto_state,'ship_to_zip'=>$shipto_zipcode,
             'must_ship_date'=>$RequiredShipDate,'shiped_date'=>$ShippedDate]);

             // Kiem tra trong detail cua phieu nau da co hang nay chua
             $sql = " select id as MyCount from sal_amz_vendor_order_dt where amz_order_id  = $id and product_id = $product_id ";
             if(!$this->IsExist('mysql',$sql)){// chua ton tai detail product nay
               // insert to detail
                DB::connection('mysql')->table('sal_amz_vendor_order_dt')->insert(
               ['amz_order_id'=>$id ,'product_id'=>$product_id,'quantity'=>$ItemQuantity,
               'price'=>$ItemCost,'amount'=>$ItemCost * $ItemQuantity,'tracking_id'=> $TrackingID ]);
             }

           }else{// da ton tai master
              // Kiem tra trong detail cua phieu nau da co hang nay chua
              $sql = " select id as MyCount from sal_amz_vendor_order_dt where amz_order_id  = $id and product_id = $product_id ";
              if(!$this->IsExist('mysql',$sql)){// chua ton tai detail product nay
                // insert to detail
                DB::connection('mysql')->table('sal_amz_vendor_order_dt')->insert(
                ['amz_order_id'=>$id,'product_id'=>$product_id,'quantity'=>$ItemQuantity,
                'price'=>$ItemCost,'amount'=>$ItemCost *$ItemQuantity,'tracking_id'=> $TrackingID ]);
              }

           }

        }// for

        $RowBegin = 4;
        $reader->setLoadSheetsOnly(["SumSalesOnAVC", "SumSalesOnAVC"]);
        $spreadsheet = $reader->load($file);
        $RowEnd = $spreadsheet->getActiveSheet()->getHighestRow();
        print_r ('last record of SumSalesOnAVC '.$RowEnd );
        print_r ( '<br>');
        
        $product_id=0;
        $id = 0;
        for($i=$RowBegin; $i <= $RowEnd; $i++)
        {
          $Asin = $spreadsheet->getActiveSheet()->getCellByColumnAndRow(1,$i)->getValue();
          $TotalOrder	= $spreadsheet->getActiveSheet()->getCellByColumnAndRow(7,$i)->getValue();
          $TheMonth = $spreadsheet->getActiveSheet()->getCellByColumnAndRow(22,$i)->getValue();
          $TheYear = $spreadsheet->getActiveSheet()->getCellByColumnAndRow(23,$i)->getValue();
          $MarketPlace =1;// Amazon
          $product_id = $this->GetProductIDFromAsin($Asin, $MarketPlace);
          $id =0;
          $sql = " select id  from sal_sum_vendor_order where product_id  = $product_id 
          and the_month  = $TheMonth and the_year =  $TheYear  ";

          $ds= DB::connection('mysql')->select($sql);
          foreach($ds as $d)  {  $id = $this->iif(is_null($d->id),0,$d->id); }
          if( $id ==0 && $product_id != 0)
          {
            DB::connection('mysql')->table('sal_sum_vendor_order')->insert(
            ['product_id'=>$product_id ,'quantity'=>$TotalOrder,'the_month'=>$TheMonth,'the_year'=>$TheYear]);
          }elseif($product_id == 0)
          {
            print_r('Asin'.$Asin );
            print_r('<br>' );
          }

        }
       }//  if($validator->passes())
     }
}

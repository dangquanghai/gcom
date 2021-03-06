<?php

namespace App\Http\Controllers\Sales;
use Illuminate\Http\Request;
use App\Http\Controllers\SysController;
use DB;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Reader\IReadFilter;
use App\Models\Sales\SalesProductInfor;
use Validator;
use DateTime;
use GuzzleHttp\Client;
class SalesProductInforController extends SysController
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function GetFirstDateOfMonth($TheYear,$TheMonth)
    {
     // $s = date("Y",strtotime($TheDate)) .'-' .  date("m",strtotime($TheDate)) .'-01';
      $s = $TheYear . '-'. $TheMonth .'-01';
      return date("Y-m-d",strtotime( $s));
    }
    public function UpdateCostPrice(Request $request)
    {
        // Cập nhật lại giá trên bảng sal_product_channel_price khi có thay đổi giá
         $data = array(
         $request->column_name=>$request->column_value
         );
       // dd( $request);

         DB::table('sal_product_channel_price')->where('id',$request->id)->update($data) ;
        
         // Lưu lại historry khi có cập nhật giá ghi history lên bảng  bảng sal_product_channel_price_his
         $effect_from = date('Y-m-d H:i:s');
         $User = auth()->user();
         $UserID = $User->id;
         
         $dataForHis = array(
          'prd_channel_price_id'=>$request->id,
          $request->column_name=>$request->column_value,
          'effect_from'=> $effect_from,
          'update_by'=> $UserID
          );
       
         DB::table('sal_product_channel_price_his')->insert($dataForHis) ;
         //dd($dataForHis);
         echo '<div class = "alert  alert-success"> Data Updated </div>';
    }
  // --------------------------------------------------------------------
  public function UpdateCostPriceNew(Request $request)
  {
      // Cập nhật lại giá trên bảng sal_product_channel_price khi có thay đổi giá
       $data = array(
       'retail_price'=>$request->retail_price,
       'per_cost'=>$request->per_cost,
       'cost'=>$request->retail_price * $request->per_cost /100
       );
      //dd(  $data );

       DB::table('sal_product_channel_price')->where('id',$request->id)->update($data) ;
      
       // Lưu lại historry khi có cập nhật giá ghi history lên bảng  bảng sal_product_channel_price_his
       $effect_from = date('Y-m-d H:i:s');
       $User = auth()->user();
       $UserID = $User->id;
       
       $dataForHis = array(
        'prd_channel_price_id'=>$request->id,
        'price'=>$request->retail_price,
        'cost'=>$request->retail_price * $request->per_cost /100,
        'effect_from'=> $effect_from,
        'update_by'=> $UserID
        );
     
       DB::table('sal_product_channel_price_his')->insert($dataForHis) ;
       //dd($dataForHis);
       echo '<div class = "alert  alert-success"> Data Updated </div>';

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
    public function LoadFileProductSalesInfor()
    {
     return view('SAL.SalesProductInfor.LoadFileProductSalesInfor');
    }
    // --------------------------------------------------------------------
 
    public function LoadSalesProductInforDetail($id)
    {
      $SPIs = SalesProductInfor::find($id);
      $ProductName ='';
      $sql =" select 	title from prd_product  p
      inner join sal_product_informations  spi on p.product_sku = spi.sku
      where spi.id = $id ";
      $ds = DB::connection('mysql')->select($sql);
      foreach( $ds as $d ){$ProductName = $d->title;}
      return view('SAL.SalesProductInfor.edit',compact(['id','SPIs','ProductName']));
    }
    //----------------------------------------------------
    public function index(Request $request)
    {
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

      $sql = " select pi.id, p.title, p.product_sku as sku, br.brand_name,
      p.length, p.width, p.height,p.weight, round(p.length * p.width * p.height) as cubic,
      pi.per_deposit,pi.per_full_payment,pi.per_rev_split_for_partner,
      pi.con20_capacity,pi.exw_vn,pi.fob_vn,pi.fob_cn, pi.fob_us, pi.cosg_est,
      pi.per_mkt, per_promotion,per_return, 0.02 as selling_invoice,
      pi.per_duty, 0.015 as sales_commision, pi.per_wh_fee,pi.per_handing_fee,

      round(( pi.fob_us/pi.retail_price + pi.per_mkt + pi.per_promotion + pi.per_return + 0.02 + pi.per_duty
      + 0.015 + pi.per_wh_fee +	pi.per_handing_fee),2) as per_total_cost,

      round(
       ( pi.per_wholesales_price_min  -
       round(pi.fob_us/pi.retail_price + pi.per_mkt + pi.per_promotion + pi.per_return + 0.02 + pi.per_duty + 0.015 + pi.per_wh_fee + pi.per_handing_fee,2)
       )
     ,2) as per_wholesales_gp_min,

     round(
                   ( pi.per_wholesales_price_max  -
                   round(pi.fob_us/pi.retail_price + pi.per_mkt + pi.per_promotion + pi.per_return + 0.02 + pi.per_duty + 0.015 + pi.per_wh_fee + pi.per_handing_fee,2)
                   )
     ,2) as per_wholesales_gp_max,

     pi.shiping_fee_est,
     round(
       (round(( pi.fob_us/pi.retail_price + pi.per_mkt + pi.per_promotion + pi.per_return + 0.02 + pi.per_duty
       + 0.015 + pi.per_wh_fee +	pi.per_handing_fee),2)* pi.retail_price - pi.shiping_fee_est)/ pi.retail_price
       ,2) as per_retail_profit,

     pi.per_wholesales_price_min,
     pi.per_wholesales_price_max,

     round(pi.shiping_fee_est*0.85,2) as dsv_shipping_fee,

     round((pi.retail_price - (pi.per_wholesales_price_max* pi.retail_price) - round(pi.shiping_fee_est*0.85,2))/pi.retail_price ,2)
     as  price_profit_min,

     round((pi.retail_price - (pi.per_wholesales_price_min* pi.retail_price) - round(pi.shiping_fee_est*0.85,2))/pi.retail_price ,2)
     price_profit_max,

     pi.shiping_fee_est,pi.retail_price,

     GetSalesChannelFee(10) as per_fbm_fee,
     GetSalesChannelFee(2) as per_avcds_fee,
     GetSalesChannelFee(1) as per_avcwh_fee,
     GetSalesChannelFee(4) as per_wmdsv_fee,
     GetSalesChannelFee(5) as per_wmmkp_fee,
     GetSalesChannelFee(6) as per_ebay_fee,
     GetSalesChannelFee(7) as per_local_fee,
     GetSalesChannelFee(8) as per_website_fee,
     GetSalesChannelFee(12) as per_way_fee,

      round( GetRetailPrice(10,p.product_sku) * GetSalesChannelFee(10),2) as fbm_fee ,

      round( GetRetailPrice(2,p.product_sku) * GetSalesChannelFee(2),2) as avcds_fee,
      round( GetRetailPrice(1,p.product_sku) * GetSalesChannelFee(1),2) as avcwh_fee,
      round( GetRetailPrice(4,p.product_sku) * GetSalesChannelFee(4),2) as wmdsv_fee,
      round( GetRetailPrice(5,p.product_sku) * GetSalesChannelFee(5),2) as wmmkp_fee,
      round( GetRetailPrice(6,p.product_sku) * GetSalesChannelFee(6),2) as ebay_fee,
      round( GetRetailPrice(7,p.product_sku) * GetSalesChannelFee(7),2) as local_fee,
      round( GetRetailPrice(8,p.product_sku) * GetSalesChannelFee(8),2) as website_fee,
      round( GetRetailPrice(12,p.product_sku) * GetSalesChannelFee(12),2) as way_fee ,

      GetRetailPrice(10,p.product_sku) as fbm_retail_price,
      GetRetailPrice(2,p.product_sku) as avcds_retail_price,
      GetRetailPrice(1,p.product_sku) as avcwh_retail_price,
      GetRetailPrice(4,p.product_sku) as wmdsv_retail_price,
      GetRetailPrice(5,p.product_sku) as wmmkp_retail_price,
      GetRetailPrice(6,p.product_sku) as ebay_retail_price,
      GetRetailPrice(7,p.product_sku) as local_retail_price,
      GetRetailPrice(8,p.product_sku) as website_retail_price,
      GetRetailPrice(12,p.product_sku) as wayfair_retail_price ,

      GetCostPrice(10,p.product_sku) as fbm_cost,
      GetCostPrice(2,p.product_sku) as avcds_cost,
      GetCostPrice(1,p.product_sku) as avcwh_cost,
      GetCostPrice(4,p.product_sku) as wmdsv_cost,
      GetCostPrice(5,p.product_sku) as wmmkp_cost,
      GetCostPrice(6,p.product_sku) as ebay_cost,
      GetCostPrice(7,p.product_sku) as local_cost,
      GetCostPrice(8,p.product_sku) as website_cost,
      GetCostPrice(12,p.product_sku) as wayfair_cost,

      case when GetRetailPrice(10,p.product_sku)> 0 then
      round(
              GetRetailPrice(10,p.product_sku)
              - round(( pi.fob_us/pi.retail_price + pi.per_mkt + pi.per_promotion + pi.per_return + 0.02 + pi.per_duty + 0.015 + pi.per_wh_fee + pi.per_handing_fee),2) 
              *  GetRetailPrice(10,p.product_sku)
              - pi.shiping_fee_est
              - round( GetRetailPrice(10,p.product_sku) * GetSalesChannelFee(10),2)
          ,2) else 0 end  as fbm_profit ,
     case when GetCostPrice(2,p.product_sku)> 0 then
     round (
             GetCostPrice(2,p.product_sku)
             - round(( pi.fob_us/pi.retail_price + pi.per_mkt + pi.per_promotion + pi.per_return + 0.02 + pi.per_duty + 0.015 + pi.per_wh_fee + pi.per_handing_fee),2) *  GetRetailPrice(2,p.product_sku)
             - round(GetCostPrice(2,p.product_sku) * GetSalesChannelFee(2),2)
             ,2) else 0 end as avcds_profit ,
     case when  GetCostPrice(1,p.product_sku) > 0 then
     round(
            GetCostPrice(1,p.product_sku)
            - round(( pi.fob_us/pi.retail_price + pi.per_mkt + pi.per_promotion + pi.per_return + 0.02 + pi.per_duty + 0.015 + pi.per_wh_fee + pi.per_handing_fee),2) *  GetRetailPrice(1,p.product_sku)
            - round(GetCostPrice(1,p.product_sku) * GetSalesChannelFee(1),2)
            ,2) else 0 end as avcwh_profit ,
     case when GetCostPrice(4,p.product_sku) > 0 then
     round(
            GetCostPrice(4,p.product_sku)
            - round(( pi.fob_us/pi.retail_price + pi.per_mkt + pi.per_promotion + pi.per_return + 0.02 + pi.per_duty + 0.015 + pi.per_wh_fee + pi.per_handing_fee),2) *  GetRetailPrice(4,p.product_sku)
            - round(GetCostPrice(4,p.product_sku) * GetSalesChannelFee(4),2)
           ,2) else 0 end as wmdsv_profit ,

     case when GetRetailPrice(5,p.product_sku) > 0 then
     round(
           GetRetailPrice(5,p.product_sku)
           - round(( pi.fob_us/pi.retail_price + pi.per_mkt + pi.per_promotion + pi.per_return + 0.02 + pi.per_duty + 0.015 + pi.per_wh_fee + pi.per_handing_fee),2) *  GetRetailPrice(5,p.product_sku)
           - round(GetRetailPrice(5,p.product_sku) * GetSalesChannelFee(5),2)
           - pi.shiping_fee_est
          ,2) else 0 end as wmmkp_profit ,

     case when GetRetailPrice(6,p.product_sku) > 0 then
     round(
           GetRetailPrice(6,p.product_sku)
           - round(( pi.fob_us/pi.retail_price + pi.per_mkt + pi.per_promotion + pi.per_return + 0.02 + pi.per_duty + 0.015 + pi.per_wh_fee + pi.per_handing_fee),2) *  GetRetailPrice(6,p.product_sku)
           - round(GetRetailPrice(6,p.product_sku) * GetSalesChannelFee(6),2)
           - pi.shiping_fee_est
         ,2) else 0 end as ebay_profit ,

     case when GetRetailPrice(7,p.product_sku) then
     round(
         GetRetailPrice(7,p.product_sku)
         - round(( pi.fob_us/pi.retail_price + pi.per_mkt + pi.per_promotion + pi.per_return + 0.02 + pi.per_duty + 0.015 + pi.per_wh_fee + pi.per_handing_fee),2) *  GetRetailPrice(7,p.product_sku)
         - round(GetRetailPrice(7,p.product_sku) * GetSalesChannelFee(7),2)
         - pi.shiping_fee_est
         ,2) else 0 end  as local_profit ,

     case when GetRetailPrice(8,p.product_sku) > 0 then
     round(
         GetRetailPrice(8,p.product_sku)
         - round(( pi.fob_us/pi.retail_price + pi.per_mkt + pi.per_promotion + pi.per_return + 0.02 + pi.per_duty + 0.015 + pi.per_wh_fee + pi.per_handing_fee),2) *  GetRetailPrice(8,p.product_sku)
         - round(GetRetailPrice(8,p.product_sku) * GetSalesChannelFee(8),2)
         - pi.shiping_fee_est
         ,2) else 0 end as website_profit ,

     case when GetCostPrice(12,p.product_sku) > 0 then
     round(
          GetCostPrice(12,p.product_sku)
          - round(( pi.fob_us/pi.retail_price + pi.per_mkt + pi.per_promotion + pi.per_return + 0.02 + pi.per_duty + 0.015 + pi.per_wh_fee + pi.per_handing_fee),2) *  GetRetailPrice(12,p.product_sku)
          - round(GetCostPrice(12,p.product_sku) * GetSalesChannelFee(12),2)
          ,2) else 0 end as wayfair_profit,

          case when GetRetailPrice(9,p.product_sku) > 0 then
          round(
            GetRetailPrice(9,p.product_sku)
               - round(( pi.fob_us/pi.retail_price + pi.per_mkt + pi.per_promotion + pi.per_return + 0.02 + pi.per_duty + 0.015 + pi.per_wh_fee + pi.per_handing_fee),2) *  GetRetailPrice(9,p.product_sku)
               - round(GetRetailPrice(9,p.product_sku) * GetSalesChannelFee(9),2)
               - pi.shiping_fee_est
               ,2) else 0 end as fba_profit

     from prd_product p
     inner join prd_brands br on p.brand_id = br.id
     inner join sal_product_informations pi on p.product_sku = pi.sku
     where p.company_id <>1 ";

     $sqlFilter = '';
     if($Sku <> ''){ $sqlFilter =  $sqlFilter. " and p.product_sku = '$Sku' " ;}
     if($Title <> ''){ $sqlFilter =  $sqlFilter. " and p.title like  '%$Title%' " ;}
     if($Brand <> 0 ){ $sqlFilter =  $sqlFilter. " and br.id =  $Brand " ;}

     $sql = $sql . $sqlFilter . "order by br.brand_name,p.product_sku ";
     $SalesProductInfors =  DB::connection('mysql')->select($sql);
    // print_r( $sql);

      $Sku = $request->input('sku');
      $Title  = $request->input('title');
      $Brand  = $request->input('brand');

      
      $sql= "  select  pa.id , p.title, pa.sku,trim(pa.amz_asin) as amz_asin , trim(pa.ebay_infidealz) as ebay_infidealz, trim(pa.ebay_inc) as ebay_inc,
       trim(pa.ebay_fitness) as ebay_fitness,trim(pa.wm_item_id) as wm_item_id ,trim(pa.wayfair_asin) as wayfair_asin , pa.local, pa.di  from prd_product p 
      left join sal_propduct_asins pa on p.product_sku  = pa.sku
      inner join prd_brands br on p.brand_id = br.id
      where company_id <> 1 ";

      $sql = $sql .   $sqlFilter ;
      $Asins = DB::connection('mysql')->select($sql);
      

      $sql = " select 0 as id, 'All' as name union select id, brand_name as name from  prd_brands ";
      $Brands = DB::connection('mysql')->select($sql);

     //dd($Asins);
       return view('SAL.SalesProductInfor.listnew',compact(['SalesProductInfors','Brands','Sku','Title','Brand','Asins']));

  }
  //----------------------------------------------------
    public function LoadSalesProductList(Request $request)
    {
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

        $sql = " select pi.id, p.title, p.product_sku as sku, br.brand_name,
        p.length, p.width, p.height,p.weight, round(p.length * p.width * p.height) as cubic,
        pi.per_deposit,pi.per_full_payment,pi.per_rev_split_for_partner,
        pi.con20_capacity,pi.exw_vn,pi.fob_vn,pi.fob_cn, pi.fob_us, pi.cosg_est,
        pi.per_mkt, per_promotion,per_return, 0.02 as selling_invoice,
        pi.per_duty, 0.015 as sales_commision, pi.per_wh_fee,pi.per_handing_fee,

        round(( pi.fob_us/pi.retail_price + pi.per_mkt + pi.per_promotion + pi.per_return + 0.02 + pi.per_duty
        + 0.015 + pi.per_wh_fee +	pi.per_handing_fee),2) as per_total_cost,

        round(
         ( pi.per_wholesales_price_min  -
         round(pi.fob_us/pi.retail_price + pi.per_mkt + pi.per_promotion + pi.per_return + 0.02 + pi.per_duty + 0.015 + pi.per_wh_fee + pi.per_handing_fee,2)
         )
       ,2) as per_wholesales_gp_min,

       round(
                     ( pi.per_wholesales_price_max  -
                     round(pi.fob_us/pi.retail_price + pi.per_mkt + pi.per_promotion + pi.per_return + 0.02 + pi.per_duty + 0.015 + pi.per_wh_fee + pi.per_handing_fee,2)
                     )
                   ,2) as per_wholesales_gp_max,

       pi.shiping_fee_est,
       round(
         (round(( pi.fob_us/pi.retail_price + pi.per_mkt + pi.per_promotion + pi.per_return + 0.02 + pi.per_duty
         + 0.015 + pi.per_wh_fee +	pi.per_handing_fee),2)* pi.retail_price - pi.shiping_fee_est)/ pi.retail_price
         ,2) as per_retail_profit,

       pi.per_wholesales_price_min,
       pi.per_wholesales_price_max,

       round(pi.shiping_fee_est*0.85,2) as dsv_shipping_fee,

       round((pi.retail_price - (pi.per_wholesales_price_max* pi.retail_price) - round(pi.shiping_fee_est*0.85,2))/pi.retail_price ,2)
       as  price_profit_min,

       round((pi.retail_price - (pi.per_wholesales_price_min* pi.retail_price) - round(pi.shiping_fee_est*0.85,2))/pi.retail_price ,2)
       price_profit_max,

       pi.shiping_fee_est,pi.retail_price,

       GetSalesChannelFee(10) as per_fbm_fee,
       GetSalesChannelFee(2) as per_avcds_fee,
       GetSalesChannelFee(1) as per_avcwh_fee,
       GetSalesChannelFee(4) as per_wmdsv_fee,
       GetSalesChannelFee(5) as per_wmmkp_fee,
       GetSalesChannelFee(6) as per_ebay_fee,
       GetSalesChannelFee(7) as per_local_fee,
       GetSalesChannelFee(8) as per_website_fee,
       GetSalesChannelFee(12) as per_way_fee,

        round( GetRetailPrice(10,p.product_sku) * GetSalesChannelFee(10),2) as fbm_fee ,

        round( GetRetailPrice(2,p.product_sku) * GetSalesChannelFee(2),2) as avcds_fee,
        round( GetRetailPrice(1,p.product_sku) * GetSalesChannelFee(1),2) as avcwh_fee,
        round( GetRetailPrice(4,p.product_sku) * GetSalesChannelFee(4),2) as wmdsv_fee,
        round( GetRetailPrice(5,p.product_sku) * GetSalesChannelFee(5),2) as wmmkp_fee,
        round( GetRetailPrice(6,p.product_sku) * GetSalesChannelFee(6),2) as ebay_fee,
        round( GetRetailPrice(7,p.product_sku) * GetSalesChannelFee(7),2) as local_fee,
        round( GetRetailPrice(8,p.product_sku) * GetSalesChannelFee(8),2) as website_fee,
        round( GetRetailPrice(12,p.product_sku) * GetSalesChannelFee(12),2) as way_fee ,

        GetRetailPrice(10,p.product_sku) as fbm_retail_price,
        GetRetailPrice(2,p.product_sku) as avcds_retail_price,
        GetRetailPrice(1,p.product_sku) as avcwh_retail_price,
        GetRetailPrice(4,p.product_sku) as wmdsv_retail_price,
        GetRetailPrice(5,p.product_sku) as wmmkp_retail_price,
        GetRetailPrice(6,p.product_sku) as ebay_retail_price,
        GetRetailPrice(7,p.product_sku) as local_retail_price,
        GetRetailPrice(8,p.product_sku) as website_retail_price,
        GetRetailPrice(12,p.product_sku) as wayfair_retail_price ,

        GetCostPrice(10,p.product_sku) as fbm_cost,
        GetCostPrice(2,p.product_sku) as avcds_cost,
        GetCostPrice(1,p.product_sku) as avcwh_cost,
        GetCostPrice(4,p.product_sku) as wmdsv_cost,
        GetCostPrice(5,p.product_sku) as wmmkp_cost,
        GetCostPrice(6,p.product_sku) as ebay_cost,
        GetCostPrice(7,p.product_sku) as local_cost,
        GetCostPrice(8,p.product_sku) as website_cost,
        GetCostPrice(12,p.product_sku) as wayfair_cost,

        case when GetRetailPrice(10,p.product_sku)> 0 then
        round(
                GetRetailPrice(10,p.product_sku)
                - round(( pi.fob_us/pi.retail_price + pi.per_mkt + pi.per_promotion + pi.per_return + 0.02 + pi.per_duty + 0.015 + pi.per_wh_fee + pi.per_handing_fee),2) *  GetRetailPrice(10,p.product_sku)
                - pi.shiping_fee_est
                - round( GetRetailPrice(10,p.product_sku) * GetSalesChannelFee(10),2)
            ,2) else 0 end  as fbm_profit ,
       case when GetCostPrice(2,p.product_sku)> 0 then
       round (
               GetCostPrice(2,p.product_sku)
               - round(( pi.fob_us/pi.retail_price + pi.per_mkt + pi.per_promotion + pi.per_return + 0.02 + pi.per_duty + 0.015 + pi.per_wh_fee + pi.per_handing_fee),2) *  GetRetailPrice(2,p.product_sku)
               - round(GetCostPrice(2,p.product_sku) * GetSalesChannelFee(2),2)
               ,2) else 0 end as avcds_profit ,
       case when  GetCostPrice(1,p.product_sku) > 0 then
       round(
              GetCostPrice(1,p.product_sku)
              - round(( pi.fob_us/pi.retail_price + pi.per_mkt + pi.per_promotion + pi.per_return + 0.02 + pi.per_duty + 0.015 + pi.per_wh_fee + pi.per_handing_fee),2) *  GetRetailPrice(1,p.product_sku)
              - round(GetCostPrice(1,p.product_sku) * GetSalesChannelFee(1),2)
              ,2) else 0 end as avcwh_profit ,
       case when GetCostPrice(4,p.product_sku) > 0 then
       round(
              GetCostPrice(4,p.product_sku)
              - round(( pi.fob_us/pi.retail_price + pi.per_mkt + pi.per_promotion + pi.per_return + 0.02 + pi.per_duty + 0.015 + pi.per_wh_fee + pi.per_handing_fee),2) *  GetRetailPrice(4,p.product_sku)
              - round(GetCostPrice(4,p.product_sku) * GetSalesChannelFee(4),2)
             ,2) else 0 end as wmdsv_profit ,

       case when GetRetailPrice(5,p.product_sku) > 0 then
       round(
             GetRetailPrice(5,p.product_sku)
             - round(( pi.fob_us/pi.retail_price + pi.per_mkt + pi.per_promotion + pi.per_return + 0.02 + pi.per_duty + 0.015 + pi.per_wh_fee + pi.per_handing_fee),2) *  GetRetailPrice(5,p.product_sku)
             - round(GetRetailPrice(5,p.product_sku) * GetSalesChannelFee(5),2)
             - pi.shiping_fee_est
            ,2) else 0 end as wmmkp_profit ,

       case when GetRetailPrice(6,p.product_sku) > 0 then
       round(
             GetRetailPrice(6,p.product_sku)
             - round(( pi.fob_us/pi.retail_price + pi.per_mkt + pi.per_promotion + pi.per_return + 0.02 + pi.per_duty + 0.015 + pi.per_wh_fee + pi.per_handing_fee),2) *  GetRetailPrice(6,p.product_sku)
             - round(GetRetailPrice(6,p.product_sku) * GetSalesChannelFee(6),2)
             - pi.shiping_fee_est
           ,2) else 0 end as ebay_profit ,

       case when GetRetailPrice(7,p.product_sku) then
       round(
           GetRetailPrice(7,p.product_sku)
           - round(( pi.fob_us/pi.retail_price + pi.per_mkt + pi.per_promotion + pi.per_return + 0.02 + pi.per_duty + 0.015 + pi.per_wh_fee + pi.per_handing_fee),2) *  GetRetailPrice(7,p.product_sku)
           - round(GetRetailPrice(7,p.product_sku) * GetSalesChannelFee(7),2)
           - pi.shiping_fee_est
           ,2) else 0 end  as local_profit ,

       case when GetRetailPrice(8,p.product_sku) > 0 then
       round(
           GetRetailPrice(8,p.product_sku)
           - round(( pi.fob_us/pi.retail_price + pi.per_mkt + pi.per_promotion + pi.per_return + 0.02 + pi.per_duty + 0.015 + pi.per_wh_fee + pi.per_handing_fee),2) *  GetRetailPrice(8,p.product_sku)
           - round(GetRetailPrice(8,p.product_sku) * GetSalesChannelFee(8),2)
           - pi.shiping_fee_est
           ,2) else 0 end as website_profit ,

       case when GetCostPrice(12,p.product_sku) > 0 then
       round(
            GetCostPrice(12,p.product_sku)
            - round(( pi.fob_us/pi.retail_price + pi.per_mkt + pi.per_promotion + pi.per_return + 0.02 + pi.per_duty + 0.015 + pi.per_wh_fee + pi.per_handing_fee),2) *  GetRetailPrice(12,p.product_sku)
            - round(GetCostPrice(12,p.product_sku) * GetSalesChannelFee(12),2)
            ,2) else 0 end as wayfair_profit,

      case when GetRetailPrice(9,p.product_sku) > 0 then
      round(
        GetRetailPrice(9,p.product_sku)
            - round(( pi.fob_us/pi.retail_price + pi.per_mkt + pi.per_promotion + pi.per_return + 0.02 + pi.per_duty + 0.015 + pi.per_wh_fee + pi.per_handing_fee),2) *  GetRetailPrice(9,p.product_sku)
            - round(GetRetailPrice(9,p.product_sku) * GetSalesChannelFee(9),2)
            - pi.shiping_fee_est
            ,2) else 0 end as fba_profit

       from prd_product p
       inner join prd_brands br on p.brand_id = br.id
       inner join sal_product_informations pi on p.product_sku = pi.sku
       where p.company_id <>1 ";

       
       $sqlFilter = '';
       if($Sku <> ''){ $sqlFilter =  $sqlFilter. " and p.product_sku = '$Sku' " ;}
       if($Title <> ''){ $sqlFilter =  $sqlFilter. " and p.title like  '%$Title%' " ;}
       if($Brand <> 0 ){ $sqlFilter =  $sqlFilter. " and br.id =  $Brand " ;}
  
       $sql = $sql . $sqlFilter . "order by br.brand_name,p.product_sku ";

      //print_r($sql);
      
       $SalesProductInfors =  DB::connection('mysql')->select($sql);
  
        $Sku = $request->input('sku');
        $Title  = $request->input('title');
        $Brand  = $request->input('brand');
  
        
        $sql= "  select  pa.id , p.title, pa.sku, pa.amz_asin, pa.ebay_infidealz, pa.ebay_inc, pa.ebay_fitness,
        pa.wm_item_id , pa.wayfair_asin , pa.local, pa.di  from prd_product p 
        left join sal_propduct_asins pa on p.product_sku  = pa.sku
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

            $per_deposit = $data['per_deposit'];
            $per_full_payment = $data['per_full_payment'];
            $per_rev_split_for_partner = $data['per_rev_split_for_partner'];
            $con20_capacity = $data['con20_capacity'];
            $exw_vn = $data['exw_vn'];

            $fob_vn =$data['fob_vn'];
            $fob_cn = $data['fob_cn'];
            $fob_us = $data['fob_us'];
            $cosg_est = $data['cosg_est'];
            $per_mkt = $data['per_mkt'];
            $per_promotion = $data['per_promotion'];
            $per_return = $data['per_return'];
            $per_duty = $data['per_duty'];
            $per_wh_fee = $data['per_wh_fee'];
            $per_handing_fee = $data['per_handing_fee'];
            $shiping_fee_est = $data['shiping_fee_est'];
            $retail_price = $data['retail_price'];
            $per_wholesales_price_min = $data['per_wholesales_price_min'];
            $per_wholesales_price_max = $data['per_wholesales_price_max'];

            DB::beginTransaction();
            $spi = new SalesProductInfor();
            $spi->per_deposit = $per_deposit;
            $spi->per_full_payment = $per_full_payment;
            $spi->per_rev_split_for_partner = $per_rev_split_for_partner;
            $spi->con20_capacity = $con20_capacity;
            $spi->exw_vn = $exw_vn;
            $spi->fob_vn = $fob_vn;
            $spi->fob_cn = $fob_cn;
            $spi->fob_us = $fob_us;
            $spi->cosg_est = $cosg_est;
            $spi->cosg_est = $per_mkt;
            $spi->per_promotion = $per_promotion;
            $spi->per_return = $per_return;
            $spi->per_duty = $per_duty;
            $spi->per_wh_fee = $per_wh_fee;
            $spi->per_handing_fee = $per_handing_fee;
            $spi->shiping_fee_est = $shiping_fee_est;
            $spi->retail_price = $retail_price;
            $spi->per_wholesales_price_min = $per_wholesales_price_min;
            $spi->per_wholesales_price_max = $per_wholesales_price_max;
            $spi->save();
	    DB::commit();
	}catch(Exception $ex)
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
      $SPIs = SalesProductInfor::find($id);
      $ProductName ='';
      $Sku = '';
      $sql =" select 	p.title, spi.sku  from prd_product  p
      inner join sal_product_informations  spi on p.product_sku = spi.sku
      where spi.id = $id ";
      $ds = DB::connection('mysql')->select($sql);
      foreach( $ds as $d ){
        $ProductName = $d->title;
        $Sku = $d->sku;
      }

      $sql = " select pp.id, channel_id,sc.name, cost,retail_price
      from sal_product_channel_price pp inner join sal_channels sc
      on pp.channel_id = sc.id
      where sku = '$Sku'";

      $ProductCostPrices = DB::connection('mysql')->select($sql);


      return view('SAL.SalesProductInfor.edit',compact(['id','ProductName','SPIs','ProductCostPrices']));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    //nó vô hàm này trước phải ko anh uh
    public function edit($id)
    {
        $Sku = '';

        $sql = "select 	p.title  , p.product_sku as sku , p.length, p.width, p.height  from prd_product  p
        inner join sal_product_informations  spi on p.product_sku = spi.sku
        where spi.id = $id ";
        $dsProducts = DB::connection('mysql')->select($sql);
        foreach($dsProducts as $dsProduct ){$Sku = $dsProduct->sku;}

        $SPIs = SalesProductInfor::find($id);
              
        $sql = " select pp.id, channel_id,sc.name, cost,retail_price
        from sal_product_channel_price pp inner join sal_channels sc
        on pp.channel_id = sc.id
        where sku = '$Sku'";
        $ProductCostPrices = DB::connection('mysql')->select($sql);
        foreach($ProductCostPrices as $ProductCostPrice ){$Sku = $dsProduct->sku;}

        $sql_channel = " select id, name from sal_channels " ;
        $dsChannels = DB::connection('mysql')->select($sql_channel);
        
        return view('SAL.SalesProductInfor.edit',compact(['id','dsProduct','SPIs','ProductCostPrice','dsChannels']));
    }

    public function LoadCostAndPriceOnAllChannel($sku)
    {
      $sql_channel = " select id, name from sal_channels " ;
      $dsChannels = DB::connection('mysql')->select($sql_channel);

      $sql = " select pp.id, channel_id,sc.name as channel_name, retail_price, per_cost, cost
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
     $spi = SalesProductInfor::find($id);
     if( $spi)
     {
   
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

      $spi->save();
      // $spi->update($request->all());
      //return 'Thành công';
      return redirect()->route('SalesProductInforController.index')->with(['success'=>'Update sản phẩm thành công']);
      }else
      {
       return redirect()->route('SalesProductInforController.index')->with(['error'=>'Update sản phẩm không thành công']);
      //return 'Thất bại';
      }
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
          $reader->setLoadSheetsOnly(["produtcs", "produtcs"]);
          $spreadsheet = $reader->load($file);
          $RowEnd = $spreadsheet->getActiveSheet()->getHighestRow();
          print_r ('last record of produtcs '.$RowEnd );
          print_r ( '<br>');
          for($i=$RowBegin; $i <= $RowEnd; $i++)
          {
           $sku = $spreadsheet->getActiveSheet()->getCellByColumnAndRow(2,$i)->getValue();
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
           $per_mkt=  100 *$spreadsheet->getActiveSheet()->getCellByColumnAndRow(19,$i)->getValue();
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
               $sql= " insert into sal_product_channel_price(sku,channel_id,cost,retail_price,per_cost)
               values('$sku',1,$avc_wh_cost,$avcwh_retail_price,$per_avc_wh)";
             }else{
               $sql= " update sal_product_channel_price set cost = $avc_wh_cost,
               retail_price = $avcwh_retail_price,per_cost = $per_avc_wh
               where sku = '$sku'  and channel_id = 1";
             }DB::connection('mysql')->select($sql);

             // 2. Kênh avc-ds
             $sql = " select count(id)as MyCount from  sal_product_channel_price
             where sku = '$sku' and channel_id = 2 ";
             if(!$this->IsExist('mysql', $sql)) {
               $sql= " insert into sal_product_channel_price(sku,channel_id,cost,retail_price,per_cost)
               values('$sku',2,$avc_ds_cost,$avcds_retail_price,$per_avc_ds)";
             }else{
               $sql= " update sal_product_channel_price set cost = $avc_ds_cost,
               retail_price = $avcds_retail_price,per_cost =$per_avc_ds
               where sku = '$sku'  and channel_id = 2";
             }DB::connection('mysql')->select($sql);

             // 4. Kênh wm-dsv
             $sql = " select count(id)as MyCount from  sal_product_channel_price
             where sku = '$sku' and channel_id = 4 ";
             if(!$this->IsExist('mysql', $sql)) {
               $sql= " insert into sal_product_channel_price(sku,channel_id,cost,retail_price,per_cost)
               values('$sku',4, $wm_dsv_cost,$wmdsv_retail_price, $per_wm_dsv)";
             }else{
               $sql= " update sal_product_channel_price set cost = $wm_dsv_cost,
               retail_price = $wmdsv_retail_price, per_cost = $per_wm_dsv
               where sku = '$sku'  and channel_id = 4";
             }DB::connection('mysql')->select($sql);

              // 5. Kênh wm-mkp
              $sql = " select count(id)as MyCount from  sal_product_channel_price
              where sku = '$sku' and channel_id = 5 ";
              if(!$this->IsExist('mysql', $sql)) {
                $sql= " insert into sal_product_channel_price(sku,channel_id,cost,retail_price)
                values('$sku',5,0,$wmmkp_retail_price)";
              }else{
                $sql= " update sal_product_channel_price set cost = 0,
                retail_price = $wmmkp_retail_price
                where sku = '$sku'  and channel_id = 5";
              }DB::connection('mysql')->select($sql);

              // 6. Kênh Ebay
              $sql = " select count(id)as MyCount from  sal_product_channel_price
              where sku = '$sku' and channel_id = 6 ";
              if(!$this->IsExist('mysql', $sql)) {
                $sql= " insert into sal_product_channel_price(sku,channel_id,cost,retail_price)
                values('$sku',6,0,$ebay_retail_price)";
              }else{
                $sql= " update sal_product_channel_price set cost = 0,
                retail_price = $ebay_retail_price
                where sku = '$sku'  and channel_id = 6";
              }DB::connection('mysql')->select($sql);

              // 7. Loclal
              $sql = " select count(id)as MyCount from  sal_product_channel_price
              where sku = '$sku' and channel_id = 7 ";
              if(!$this->IsExist('mysql', $sql)) {
                $sql= " insert into sal_product_channel_price(sku,channel_id,cost,retail_price)
                values('$sku',7,0,$local_retail_price)";
              }else{
                $sql= " update sal_product_channel_price set cost = 0,
                retail_price = $local_retail_price
                where sku = '$sku'  and channel_id = 7 ";
              }DB::connection('mysql')->select($sql);

              // 8. Website
              $sql = " select count(id)as MyCount from  sal_product_channel_price
              where sku = '$sku' and channel_id = 8 ";
              if(!$this->IsExist('mysql', $sql)) {
                $sql= " insert into sal_product_channel_price(sku,channel_id,cost,retail_price)
                values('$sku',8,0,$website_retail_price)";
              }else{
                $sql= " update sal_product_channel_price set cost = 0,
                retail_price = $local_retail_price
                where sku = '$sku'  and channel_id = 8 ";
              }DB::connection('mysql')->select($sql);

              // 9. FBA
              $sql = " select count(id)as MyCount from  sal_product_channel_price
              where sku = '$sku' and channel_id = 9";
              if(!$this->IsExist('mysql', $sql)) {
                $sql= " insert into sal_product_channel_price(sku,channel_id,cost,retail_price)
                values('$sku',9,0,$fba_retail)";
              }else{
                $sql= " update sal_product_channel_price set cost = 0,
                retail_price = $fba_retail
                where sku = '$sku'  and channel_id = 9 ";
              }DB::connection('mysql')->select($sql);

              // 10. FBM
              $sql = " select count(id)as MyCount from  sal_product_channel_price
              where sku = '$sku' and channel_id = 10 ";
              if(!$this->IsExist('mysql', $sql)) {
                $sql= " insert into sal_product_channel_price(sku,channel_id,cost,retail_price)
                values('$sku',10,0,$fbm_retail_price)";
              }else{
                $sql= " update sal_product_channel_price set cost = 0,
                retail_price = $fbm_retail_price
                where sku = '$sku'  and channel_id = 10 ";
              }DB::connection('mysql')->select($sql);
              // 12. way_fair
              $sql = " select count(id)as MyCount from  sal_product_channel_price
              where sku = '$sku' and channel_id = 12 ";
              if(!$this->IsExist('mysql', $sql)) {
                $sql= " insert into sal_product_channel_price(sku,channel_id,cost,retail_price,per_cost )
                values('$sku',12, $way_fair_cost,$wayfair_retail_price,$per_wayfair)";
              }else{
                $sql= " update sal_product_channel_price set cost = $way_fair_cost,
                retail_price = $wayfair_retail_price, per_cost  = $per_wayfair)
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
          $local= trim($spreadsheet->getActiveSheet()->getCellByColumnAndRow(12,$i)->getValue(),' ');
          $di= trim($spreadsheet->getActiveSheet()->getCellByColumnAndRow(13,$i)->getValue(),' ');
          
          if($local == 'x' || $local =='X' || $local =='')  $local='NO';
          else  $local='YES';

          if($di == 'x' || $di =='X' || $di =='')  $di='NO';
          else  $di='YES';
            
            if($sku<>'')
            {
              $sql = " select count(id)as MyCount from  sal_propduct_asins
              where sku = '$sku' ";
              if(!$this->IsExist('mysql', $sql)) 
              {
                $sql= " insert into sal_propduct_asins(sku,amz_asin,ebay_infidealz,ebay_inc,ebay_fitness,wm_item_id,wayfair_asin,local,	di)
                values('$sku','$amz_asin','$ebay_infidealz','$ebay_inc','$ebay_fitness','$wm_item_id','$wayfair_asin','$local','$di')";
                DB::connection('mysql')->select($sql);
              }
            }
          }//For

          $RowBegin = 3;
          $reader->setLoadSheetsOnly(["promotiontracking", "promotiontracking"]);
          $spreadsheet = $reader->load($file);
          $RowEnd = $spreadsheet->getActiveSheet()->getHighestRow();
          print_r ('last record of promotiontracking '.$RowEnd );
          print_r ( '<br>');
          for($i=$RowBegin; $i <= $RowEnd; $i++)
          {
           $AmzAsin = $spreadsheet->getActiveSheet()->getCellByColumnAndRow(1,$i)->getValue();
           $PromotionTypeName = $spreadsheet->getActiveSheet()->getCellByColumnAndRow(2,$i)->getValue();
           $PromotionNo = $spreadsheet->getActiveSheet()->getCellByColumnAndRow(3,$i)->getValue();
           $StatusName = $spreadsheet->getActiveSheet()->getCellByColumnAndRow(4,$i)->getValue();
           $PerFunding = $spreadsheet->getActiveSheet()->getCellByColumnAndRow(5,$i)->getValue();
           $StartDate = $spreadsheet->getActiveSheet()->getCellByColumnAndRow(6,$i)->getFormattedValue();
           $EndDate = $spreadsheet->getActiveSheet()->getCellByColumnAndRow(7,$i)->getFormattedValue();
           $Funding = $spreadsheet->getActiveSheet()->getCellByColumnAndRow(8,$i)->getValue();
           $UnitSold = $spreadsheet->getActiveSheet()->getCellByColumnAndRow(10,$i)->getValue();
           $AmountSpent= 0;//$spreadsheet->getActiveSheet()->getCellByColumnAndRow(11,$i)->getFormattedValue();
           $Revenue = $spreadsheet->getActiveSheet()->getCellByColumnAndRow(12,$i)->getValue();
           $Channel= $spreadsheet->getActiveSheet()->getCellByColumnAndRow(13,$i)->getValue();

           
           if( $PerFunding =='') { $PerFunding =0;}
           if( $Funding =='') { $Funding =0;}
           if( $UnitSold  =='') { $UnitSold  = 0;}
           if( $AmountSpent =='') {  $AmountSpent  =0;}
           if( $Revenue =='') {  $Revenue  =0;}
           
           $AmountSpent =   $Funding  * $UnitSold ;

           $Sku = '';
           $PromotionTypeID = 0;
           $StatusID = 0;
           $Channel= 0;
           $sql = " select sku from sal_propduct_asins where amz_asin = '$AmzAsin' ";
           $ds = DB::connection('mysql')->select($sql);
           foreach( $ds as $d ){$Sku = $d->sku;}
           
           if($PromotionTypeName =='Best Deals'){$PromotionTypeID = 1;}
           else if($PromotionTypeName =='Price Discount'){$PromotionTypeID = 2;} 
           else if($PromotionTypeName=='Promo Code'){$PromotionTypeID = 3;} 
           else if($PromotionTypeName =='Lightning Deal'){$PromotionTypeID = 4;} 


           if( $StatusName =='Init'){$StatusID =1;}
           if( $StatusName =='Approved'){$StatusID =2;}
           if( $StatusName =='Running'){$StatusID =3;}
           if( $StatusName =='Finished'){$StatusID =4;}
           if( $StatusName =='Canceled'){$StatusID =5;}
           if( $StatusName =='Canceled'){$StatusID =6;}
          

           if($Channel=='AVC-DS'){$Channel=1;}
           if( $PromotionNo <> '' )
           {
            $sql = " select count(id)as MyCount from  sal_promotions where  promotion_no = '$PromotionNo'";
            if(!$this->IsExist('mysql', $sql)) // Master promtion chưa tồn tại
            {
              $id = DB::table('sal_promotions')->insertGetId(
              [ 'promotion_no' => $PromotionNo,'Promotion_type'=>$PromotionTypeID,'promotion_status'=>$StatusID,
              'from_date'=>$StartDate, 'to_date'=>$EndDate,'channel' =>$Channel ]);
            
              $id = DB::table('sal_promotions_dt')->insert(
              ['promotion_id' =>$id,'asin'=>$AmzAsin,'sku'=>$Sku,
              'per_funding'=>$PerFunding, 'funding'=>$Funding,'unit_sold' =>$UnitSold,'amount_spent'=>$AmountSpent,'revenue'=>$Revenue ]);

              print_r ('Unit Sold '. $UnitSold . ' amount spent' . $AmountSpent . ' revenue ' . $Revenue );
              print_r ( '<br>');
            }
            else
            {
              $sql = " select count(id)as MyCount from  sal_promotions_dt where  promotion_id = $id and  sku = ' $Sku'";
              if(!$this->IsExist('mysql', $sql)) 
                {
                  print_r ('Unit Sold '. $UnitSold . ' amount spent' . $AmountSpent . ' revenue ' . $Revenue );
                  print_r ( '<br>');
                  DB::table('sal_promotions_dt')->insert(
                  ['promotion_id' =>$id,'asin'=>$AmzAsin,'sku'=>$Sku,
                  'per_funding'=>$PerFunding, 'funding'=>$Funding,'unit_sold' =>$UnitSold,'amount_spent'=>$AmountSpent,'revenue'=>$Revenue ]);
                }
            }
          }
        }//For
       }//  if($validator->passes())
     }
}

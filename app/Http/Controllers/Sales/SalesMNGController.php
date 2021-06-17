<?php

namespace App\Http\Controllers\Sales;
use Illuminate\Http\Request;
use App\Http\Controllers\SYS\SysController;
use DB;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Reader\IReadFilter;

use Validator;
use DateTime;

class SalesMNGController extends SysController
{
    public function __construct()
    {
      $this->middleware('auth');
    }

    //---------------------------------------------------------------
    public function index()
    {
       //dd('Vo day dung không');
        return view('SAL.WMDSV_ItemMNG_Import');
    }
    //---------------------------------------------------------------
    public function WMItemMNGImport(Request $request)
    {
        $validator = Validator::make($request->all(),[
            'file'=>'required|max:45000|mimes:xlsx,xls,csv'
            ]);

        if($validator->passes())
        {
            $file = $request->file('file');
            $reader = new \PhpOffice\PhpSpreadsheet\Reader\Xlsx();

            DB::connection('mysql')->select (" delete from sal_wm_produtc_mng");

            $RowBegin = 2;
            $reader->setLoadSheetsOnly(["ItemReport", "ItemReport"]);
            $spreadsheet = $reader->load($file);
            $RowEnd = $spreadsheet->getActiveSheet()->getHighestRow();
            print_r ('ItemReport: ' .$RowEnd );
            print_r ( '<br>');

            for($i=$RowBegin; $i <= $RowEnd; $i++)
            {
                $Sku = $spreadsheet->getActiveSheet()->getCellByColumnAndRow(2,$i)->getValue();
                $ProductName = $spreadsheet->getActiveSheet()->getCellByColumnAndRow(3,$i)->getValue();//
                $Category  = $spreadsheet->getActiveSheet()->getCellByColumnAndRow(4,$i)->getValue();//
                $Cost = $spreadsheet->getActiveSheet()->getCellByColumnAndRow(7,$i)->getValue();
                $Price = $spreadsheet->getActiveSheet()->getCellByColumnAndRow(8,$i)->getValue();//
                $BuyBoxPrice= $spreadsheet->getActiveSheet()->getCellByColumnAndRow(10,$i)->getValue();//
                $BuyBoxShipingPrice= $spreadsheet->getActiveSheet()->getCellByColumnAndRow(11,$i)->getValue();//
                $PublicStatus = $spreadsheet->getActiveSheet()->getCellByColumnAndRow(12,$i)->getValue();//
                $LifeStaus = $spreadsheet->getActiveSheet()->getCellByColumnAndRow(13,$i)->getValue();//
                $AvailabilityStatus = $spreadsheet->getActiveSheet()->getCellByColumnAndRow(14,$i)->getValue();//
                $ShipMethod = $spreadsheet->getActiveSheet()->getCellByColumnAndRow(15,$i)->getValue();//
                $ItemID = $spreadsheet->getActiveSheet()->getCellByColumnAndRow(17,$i)->getValue();//
                $WMNo = $spreadsheet->getActiveSheet()->getCellByColumnAndRow(18,$i)->getValue();//

                $Gtin = $spreadsheet->getActiveSheet()->getCellByColumnAndRow(19,$i)->getValue();//

               // print_r ('Gtin: ' . $Gtin);
               // print_r ('<br> ');

                $OfferStartDate= $spreadsheet->getActiveSheet()->getCellByColumnAndRow(24,$i)->getValue();//
                $OfferEndDate = $spreadsheet->getActiveSheet()->getCellByColumnAndRow(25,$i)->getValue();//
                $CreatedOn= $spreadsheet->getActiveSheet()->getCellByColumnAndRow(26,$i)->getValue();//
                $LastUpdate= $spreadsheet->getActiveSheet()->getCellByColumnAndRow(27,$i)->getValue();//
                $ReviewCount= $spreadsheet->getActiveSheet()->getCellByColumnAndRow(29,$i)->getValue();//
                $AvgRating= $spreadsheet->getActiveSheet()->getCellByColumnAndRow(30,$i)->getValue();//
                $ProductTaxCode= $spreadsheet->getActiveSheet()->getCellByColumnAndRow(31,$i)->getValue();//
                $ShipingWeight= $spreadsheet->getActiveSheet()->getCellByColumnAndRow(32,$i)->getValue();//
                $ShipingWeightUnit= $spreadsheet->getActiveSheet()->getCellByColumnAndRow(33,$i)->getValue();//
                $StatusShangeReason= $spreadsheet->getActiveSheet()->getCellByColumnAndRow(34,$i)->getValue();//
                $AvlUnitOnWM = $spreadsheet->getActiveSheet()->getCellByColumnAndRow(35,$i)->getValue();//

                $sql =" select count(id) as MyCount from pu_vendor_products where sku = '$Sku'";

                if($this->IsExist('mysql',$sql))// SKU thuộc HMD
                {
                    $AvlUnitInWH = $this->GetAvailableQuantityInWH($Sku);
                    $TheDateTime = date('y-m-d h:m:s');
                    $Cogs =round($this->GetCogs($Sku),0);
                    DB::connection('mysql')->table('sal_wm_produtc_mng')->insert(
                    ['sku'=>$Sku,'product_name'=>$ProductName,'cost'=>$Cost,'price'=>$Price,
                    'buy_box_price'=>$BuyBoxPrice,'buy_box_shipping_price'=>$BuyBoxShipingPrice,
                    'public_status'=>$PublicStatus,'lifecycle_status'=>$LifeStaus,'ship_method'=>$ShipMethod,
                    'item_id'=>$ItemID,'wm_no'=>$WMNo,'gtin'=>$Gtin ,'offer_start_date'=>$OfferEndDate,
                    'offer_end_date'=>$OfferEndDate,'created_on'=>$CreatedOn,'last_update'=>$LastUpdate,
                    'review_count'=>$ReviewCount,'avg_rating'=>$AvgRating,'product_tax_code'=>$ProductTaxCode,
                    'shiping_weight'=>$ShipingWeight,'shiping_weight_unit'=>$ShipingWeight,
                    'status_change_reason'=>$StatusShangeReason,'avl_unit_on_wm'=>$AvlUnitOnWM,
                    'avl_unit_in_wh'=>$AvlUnitInWH,'the_datetime'=>$TheDateTime,'category'=> $Category,
                    'cogs'=> $Cogs,'channel_id'=>4]);
                }// If
            }//For
            $RowBegin = 2;
            $reader->setLoadSheetsOnly(["ItemReportMKP", "ItemReportMKP"]);
            $spreadsheet = $reader->load($file);
            $RowEnd = $spreadsheet->getActiveSheet()->getHighestRow();
            print_r ('ItemReportMKP: ' .$RowEnd );
            print_r ( '<br>');
            for($i=$RowBegin; $i <= $RowEnd; $i++)
            {
                $Sku = $spreadsheet->getActiveSheet()->getCellByColumnAndRow(2,$i)->getValue();
                $ProductName = $spreadsheet->getActiveSheet()->getCellByColumnAndRow(3,$i)->getValue();//
                $Cost = 0;
                $Price = $spreadsheet->getActiveSheet()->getCellByColumnAndRow(5,$i)->getValue();//
                $BuyBoxPrice= $spreadsheet->getActiveSheet()->getCellByColumnAndRow(7,$i)->getValue();//
                $BuyBoxShipingPrice = $spreadsheet->getActiveSheet()->getCellByColumnAndRow(8,$i)->getValue();//
                $PublicStatus = $spreadsheet->getActiveSheet()->getCellByColumnAndRow(9,$i)->getValue();//
                $AvlUnitOnWM = $spreadsheet->getActiveSheet()->getCellByColumnAndRow(12,$i)->getValue();//
                $ItemID = $spreadsheet->getActiveSheet()->getCellByColumnAndRow(15,$i)->getValue();//

                $OfferStartDate= $spreadsheet->getActiveSheet()->getCellByColumnAndRow(21,$i)->getValue();//
                $OfferEndDate = $spreadsheet->getActiveSheet()->getCellByColumnAndRow(22,$i)->getValue();//
                $CreatedOn= $spreadsheet->getActiveSheet()->getCellByColumnAndRow(23,$i)->getValue();//
                $LastUpdate= $spreadsheet->getActiveSheet()->getCellByColumnAndRow(24,$i)->getValue();//
                $ReviewCount= $spreadsheet->getActiveSheet()->getCellByColumnAndRow(26,$i)->getValue();//
                $AvgRating= $spreadsheet->getActiveSheet()->getCellByColumnAndRow(27,$i)->getValue();//
                $ProductTaxCode= $spreadsheet->getActiveSheet()->getCellByColumnAndRow(34,$i)->getValue();//

                $ShipingWeight= $spreadsheet->getActiveSheet()->getCellByColumnAndRow(36,$i)->getValue();//
                $ShipingWeightUnit= $spreadsheet->getActiveSheet()->getCellByColumnAndRow(37,$i)->getValue();//
                $CompetitorPrice= $spreadsheet->getActiveSheet()->getCellByColumnAndRow(31,$i)->getValue();//

                $sql = " select count(id) as MyCount from pu_vendor_products where sku = '$Sku'";

                if($this->IsExist('mysql',$sql))// SKU thuộc HMD
                {
                    $AvlUnitInWH = $this->GetAvailableQuantityInWH($Sku);
                    $TheDateTime = date('y-m-d h:m:s');
                    $Cogs =round($this->GetCogs($Sku),0);
                    DB::connection('mysql')->table('sal_wm_produtc_mng')->insert(
                    ['sku'=>$Sku,'product_name'=>$ProductName,'cost'=>$Cost,'price'=>$Price,
                    'buy_box_price'=>$BuyBoxPrice,'buy_box_shipping_price'=>$BuyBoxShipingPrice,
                    'public_status'=>$PublicStatus,'lifecycle_status'=>$LifeStaus,'ship_method'=>$ShipMethod,
                    'item_id'=>$ItemID, 'offer_start_date'=>$OfferEndDate,
                    'offer_end_date'=>$OfferEndDate,'created_on'=>$CreatedOn,'last_update'=>$LastUpdate,
                    'review_count'=>$ReviewCount,'avg_rating'=>$AvgRating,'product_tax_code'=>$ProductTaxCode,
                    'shiping_weight'=>$ShipingWeight,'shiping_weight_unit'=>$ShipingWeight,
                    'status_change_reason'=>$StatusShangeReason,'avl_unit_on_wm'=>$AvlUnitOnWM,
                    'avl_unit_in_wh'=>$AvlUnitInWH,'the_datetime'=>$TheDateTime,'cogs'=> $Cogs,
                    'competitor_price'=>$CompetitorPrice,'channel_id'=>5]);
                }// If
            }//For
        }// End if
 }// End function
 //---------------------------------------------------------------
 public function GetCogs($Sku)
 {
   $Result = 0 ;
   $sql = " select appotion_price from products where product_sku = '$Sku'";
   $ds = DB::connection('mysql_it')->select($sql);
   foreach( $ds as $d ) { $Result = $d->appotion_price; }
   return $Result;
 }
//---------------------------------------------------------------
 public function LoadProductListSellingOnWMDSVDefault(Request $request)
 {
    if(!$request->has('conditions'))
         $SqlCondition = "  And(public_status='PUBLISHED') And(avl_unit_on_wm<=5) And(avl_unit_in_wh > 0)
         and (channel_id = 4 )";
    else
        $SqlCondition = $request->input('conditions');

    $ToDate  =  date("Y-m-d");
    $FromDate = $this->MoveDate($ToDate,-8);

    $sql ="  SELECT ORDINAL_POSITION AS id ,COLUMN_NAME as name   FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = 'gcom' AND TABLE_NAME = 'sal_wm_produtc_mng' and ORDINAL_POSITION <> 1";
    $dsColumns = DB::connection('mysql')->select($sql);

    $sql =" select id, name from sys_operator where is_active = 1 ";
    $dsOperators = DB::connection('mysql')->select($sql);

    $sql = " select item_id,sku,wm_no,product_name,category,gtin,cogs,cost,price,buy_box_price,
    buy_box_shipping_price,sell_quantity,amount, review_count,avg_rating,avl_unit_in_wh,
    avl_unit_on_wm,the_datetime,offer_start_date,offer_end_date,created_on,
    last_update,shiping_weight,shiping_weight_unit,status_change_reason,product_tax_code,
    public_status,sal_channels.name
    from sal_wm_produtc_mng inner join sal_channels on sal_wm_produtc_mng.channel_id = sal_channels.id
    where (1 = 1) ";
    //and (sku not in (select sku from sal_sku_off_on_wm)) " ;

    if(strlen($SqlCondition)>0)  {$sql = $sql .  $SqlCondition; }
    $ds = DB::connection('mysql')->select($sql);
    return view('SAL.WMDSV_ItemMNG',compact(['ds','SqlCondition','dsColumns','dsOperators','FromDate','ToDate']));
 }
 //---------------------------------------------------------------
 public function MakeSuggetActionOnWMDSVDefault()
 {
    $CurrentCost = 0;
    $PromoValue = 0;

    $sql = " select id,item_id , sku,product_name,cogs,cost,price,avl_unit_in_wh,avl_unit_on_wm
    from sal_wm_produtc_mng  where action_group_id = 1 ";
    $dsOutOfStocks = DB::connection('mysql')->select($sql);

    $sql = " select count(id) as total   from sal_wm_produtc_mng  where action_group_id = 1 ";
    $dsOutOfStockCount = DB::connection('mysql')->select($sql);
    foreach( $dsOutOfStockCount as $d )  {$TotalOutOfStock = $d->total;}

    $sql = " select id,sku,wm_no,item_id,product_name,gtin,cogs,$CurrentCost as CurrentCost ,price,cost as new_cost
    from sal_wm_produtc_mng  where action_group_id = 2 ";
    $dsDownCosts = DB::connection('mysql')->select($sql);

    $sql = " select 'Yes4All' as Vendor,'750778' as SupplierNumber,item_id, product_name,'x' as Campaign,
    'X' as StartDate, 'Y' as EnDate , cost as NormalCost,(cost -". $PromoValue.") as  RollbackCost,
    price as NormalRetail, (price-". $PromoValue ." ) as RollbackRetail," . $PromoValue ." as FundingperUnit
    from sal_wm_produtc_mng  where action_group_id = 3 ";
    $dsPromotions = DB::connection('mysql')->select($sql);

    $sql = " select id,sku,wm_no,item_id,product_name,gtin,cogs,$CurrentCost,price,cost as new_cost
    from sal_wm_produtc_mng  where action_group_id = 4 ";
    $dsUnpublishs = DB::connection('mysql')->select($sql);

    $sql = " select id,sku,wm_no,item_id,avg_rating,product_name from sal_wm_produtc_mng  where action_group_id = 6 ";
    $dsReadReviews = DB::connection('mysql')->select($sql);
    // dd($dsOutOfStocks);

    $OutOfStocks = array(
    "total"=>$TotalOutOfStock,
    "totalNotFiltered"=>2,
    "rows"=>$dsOutOfStocks
    );

    return view('SAL.WMDSV_Actions',compact(['OutOfStocks','dsDownCosts','dsPromotions','dsUnpublishs','dsReadReviews']));
 }
 //---------------------------------------------------------------
// Phân tích data các Product trên WM-DSV để đưa ra gợi ý các hành động
public function MakeSuggetActionOnWMDSV()
{
 $StoreFeePer = 10;
 //1. Make list Out Of Stock
 $sql = " select id,item_id , sku,product_name,cogs,cost,price,avl_unit_in_wh,avl_unit_on_wm
 from sal_wm_produtc_mng  where avl_unit_on_wm = 0 and avl_unit_in_wh > 0 and public_status  = 'PUBLISHED' ";
 $ds = DB::connection('mysql')->select($sql);
 foreach( $ds as $d ){
    $sql = " update sal_wm_produtc_mng set action_group_id = 1 ,action_des = 'Need Inventory' where id = $d->id ";
    DB::connection('mysql')->select($sql);
 }
 $sql = " select id,item_id , sku,product_name,cogs,cost,price,avl_unit_in_wh,avl_unit_on_wm
 from sal_wm_produtc_mng  where action_group_id = 1 ";

 $dsOutOfStocks = DB::connection('mysql')->select($sql);

 $sql = " select count(id) as total   from sal_wm_produtc_mng  where action_group_id = 1 ";
 $dsOutOfStockCount = DB::connection('mysql')->select($sql);
 foreach( $dsOutOfStockCount as $dx )  {$TotalOutOfStock = $dx->total;}

 //2. Make list Down Cost: Cost > Price & Winbybox (WM Losses) => Cần giảm mạnh cost, giữ nguyên Price
 $sql = " select id,sku,wm_no,item_id,product_name,gtin,cogs,cost,price,avl_unit_in_wh,avl_unit_on_wm
 from sal_wm_produtc_mng  where cost >= price and price = buy_box_price  and public_status  = 'PUBLISHED' ";
 $ds = DB::connection('mysql')->select($sql);
 $CurrentCost =$d->cogs;
 foreach( $ds as $d ){
    $NewCost = $d->cogs * 3 ;
    $sql = " update sal_wm_produtc_mng set action_group_id = 2 ,action_des = 'Down Cost' where id = $d->id ";
    DB::connection('mysql')->select($sql);
 }

 $sql = " select id,sku,wm_no,item_id,product_name,gtin,cogs, $CurrentCost  as CurrentCost ,price,cost as new_cost
 from sal_wm_produtc_mng  where action_group_id = 2 ";
 $dsDownCosts = DB::connection('mysql')->select($sql);

  //3. No Order Selling Quantity = 0 & winbybox & Cost < price , check COGS, Fee ,Cost => Make Promotion
  $PromoValue = 0;
  $sql = " select id,item_id, sku,cogs,cost,price,avl_unit_in_wh,avl_unit_on_wm from sal_wm_produtc_mng
  where sell_quantity = 0 and cost < price and price = buy_box_price and public_status  = 'PUBLISHED' ";
  $ds = DB::connection('mysql')->select($sql);
  foreach( $ds as $d ){
     $ProfitPer = ($d->cost - $d->cost - $d->cost *(100 - $StoreFeePer))/$d->cost*100;
     $i = 25;
     while($i >= 5 && $ProfitPer >10)
     {
       $PromoValue = $d->cost * $i/100;
       $ProfitPer = $ProfitPer - $PromoValue/$d->cost * 100 ;
       $i = $i-5;
     }
     $Des = 'Need Promotion ' .  $i;
     $sql = " update sal_wm_produtc_mng set action_group_id = 3, action_des = '$Des' where id = $d->id ";
     DB::connection('mysql')->select($sql);
  }
  $sql = " select 'Yes4All' as Vendor,'750778' as SupplierNumber,item_id, product_name,'x' as Campaign,
'X' as StartDate, 'Y' as EnDate , cost as NormalCost,(cost -". $PromoValue.") as  RollbackCost,
 price as NormalRetail, (price-". $PromoValue ." ) as RollbackRetail," . $PromoValue ." as FundingperUnit
 from sal_wm_produtc_mng  where action_group_id = 3 ";
 $dsPromotions = DB::connection('mysql')->select($sql);

  //4. Unpublic list:Check cost, COGS  => Down cost
  $RetailPrice = 0;
  $sql = " select id,item_id, sku,cogs,cost,price,avl_unit_in_wh,avl_unit_on_wm
  from sal_wm_produtc_mng  where public_status  = 'UNPUBLISHED' ";
  $ds = DB::connection('mysql')->select($sql);
  $CurrentCost = $d->cogs ;
  foreach( $ds as $d ){
    //  $sql = " select retail_price from sal_product_channel_price where sku = ' $d->sku' and channel_id = 2 " ;
    //  $d1s = DB::connection('mysql')->select($sql);
    //  foreach( $d1s as $d1 ){ $RetailPrice = $this->iif(is_null($d1->retail_price),0,$d1->retail_price); }
     $NewCost = $d->cogs * 3;
     $Des = 'Update New Cost ' .  $NewCost;
     $sql = " update sal_wm_produtc_mng set action_group_id = 4, action_des = '$Des' where id = $d->id ";
     DB::connection('mysql')->select($sql);
  }
  $sql = " select id,sku,wm_no,item_id,product_name,gtin,cogs,$CurrentCost,price,cost as new_cost
  from sal_wm_produtc_mng  where action_group_id = 4 ";
  $dsUnpublishs = DB::connection('mysql')->select($sql);

//   // 5.No order even minimum Cost => Tạm bỏ qua case này
//   $RetailPrice = 0;
//   $sql = " select id,item_id, sku,cogs,cost,price,avl_unit_in_wh,avl_unit_on_wm
//   from sal_wm_produtc_mng  where public_status  = 'UNPUBLISHED' ";
//   $ds = DB::connection('mysql')->select($sql);
//   foreach( $ds as $d ){
//     //  $sql = " select retail_price from sal_product_channel_price where sku = ' $d->sku' and channel_id = 2 " ;
//     //  $d1s = DB::connection('mysql')->select($sql);
//     //  foreach( $d1s as $d1 ){ $RetailPrice = $this->iif(is_null($d1->retail_price),0,$d1->retail_price); }
//      $NewCost = $d->cogs * 3;
//      $Des = 'Update New Cost ' .  $NewCost;
//      $sql = " update sal_wm_produtc_mng set action_group_id = 5, action_des = '$Des' where id = $d->id ";
//      DB::connection('mysql')->select($sql);
//   }
  // 6.Low rating
  $RetailPrice = 0;
  $sql = " select id,item_id, sku,cogs,cost,price,avl_unit_in_wh,avl_unit_on_wm
  from sal_wm_produtc_mng  where avg_rating is not null and avg_rating <= 4 ";
  $ds = DB::connection('mysql')->select($sql);
  foreach( $ds as $d ){
    //  $sql = " select retail_price from sal_product_channel_price where sku = ' $d->sku' and channel_id = 2 " ;
    //  $d1s = DB::connection('mysql')->select($sql);
    //  foreach( $d1s as $d1 ){ $RetailPrice = $this->iif(is_null($d1->retail_price),0,$d1->retail_price); }
     $sql = " update sal_wm_produtc_mng set action_group_id = 6, action_des = 'Read review ' where id = $d->id ";
     DB::connection('mysql')->select($sql);
  }
  $sql = " select id,sku,wm_no,item_id,avg_rating,product_name from sal_wm_produtc_mng  where action_group_id = 6 ";
  $dsReadReviews = DB::connection('mysql')->select($sql);

  $jOutOfStocks = array(
    "total"=>$TotalOutOfStock,
    "totalNotFiltered"=>2,
    "rows"=>$dsOutOfStocks
    );
    $OutOfStocks = json_encode($jOutOfStocks);
    return view('SAL.WMDSV_Actions',compact(['OutOfStocks','dsDownCosts','dsPromotions','dsUnpublishs','dsReadReviews']));
}

 //---------------------------------------------------------------
 public function LoadProductListSellingOnWMDSV(Request $request)
 {
    $SqlCondition = $request->input('conditions');

    $FromDate = $request->input('from_date');
    $ToDate = $request->input('to_date');

    $sql ="  SELECT ORDINAL_POSITION AS id ,COLUMN_NAME as name   FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = 'gcom' AND TABLE_NAME = 'sal_wm_produtc_mng' and ORDINAL_POSITION <> 1";
    $dsColumns = DB::connection('mysql')->select($sql);

    $sql =" select id, name from sys_operator where is_active = 1 ";
    $dsOperators = DB::connection('mysql')->select($sql);

    $this->UpdateSellingDataOnWMDSV($FromDate ,$ToDate);

    $sql = " select item_id,sku,wm_no,product_name,category,gtin,cogs,cost,price,buy_box_price,
    buy_box_shipping_price,sell_quantity,amount, review_count,avg_rating,avl_unit_in_wh,
    avl_unit_on_wm,the_datetime,offer_start_date,offer_end_date,created_on,
    last_update,shiping_weight,shiping_weight_unit,status_change_reason,product_tax_code,
    public_status,sal_channels.name
    from sal_wm_produtc_mng inner join sal_channels on sal_wm_produtc_mng.channel_id = sal_channels.id
    where (1 = 1)";
    // and  sku not in (select sku from sal_sku_off_on_wm) " ;
    if(strlen($SqlCondition)>0)  {$sql = $sql .$SqlCondition; }
    $ds = DB::connection('mysql')->select($sql);
    foreach( $ds as $d ){
     $NewBalance = $this->GetAvailableQuantityInWH($d->sku);
     $xql = " update sal_wm_produtc_mng set avl_unit_in_wh =  $NewBalance where sku = '$d->sku' ";
        DB::connection('mysql')->select($xql);
    }
   // print( $sql );

    $ds = DB::connection('mysql')->select($sql);
    return view('SAL.WMDSV_ItemMNG',compact(['ds','SqlCondition','dsColumns','dsOperators','FromDate','ToDate']));
}
 //---------------------------------------------------------------
 public function UpdateSellingDataOnWMDSV($FromDate ,$ToDate)
 {
    $Quantity = 0;
    $sql = " select sku  from sal_wm_produtc_mng where channel_id = 4 ";
    $ds = DB::connection('mysql')->select($sql);
    foreach( $ds as $d )   {
        $sql = " select sum(odt.ordered_quantity) as quantity
        from walmart_dropship_orders o inner join walmart_dropship_order_details odt
        on o.id = odt.order_id inner join products p on odt.product_id = p.id
        where p.company_id <> 1
        and  p.product_sku = '$d->sku'
        and o.order_processing_date >= '$FromDate'
        and o.order_processing_date <= '$ToDate'";
        $d1s = DB::connection('mysql_it')->select($sql);
        foreach( $d1s as $d1 ) {$Quantity = $this->iif(is_null($d1->quantity),0,$d1->quantity); }

        $sql = " select cost from sal_wm_produtc_mng where channel_id = 4 and sku = '$d->sku' ";
        $d2s = DB::connection('mysql')->select($sql);
        foreach( $d2s as $d2 ) {$Cost = $this->iif(is_null($d2->cost),0,$d2->cost); }

        $sql = " update sal_wm_produtc_mng set sell_quantity = $Quantity   where sku ='$d->sku' ";
        DB::connection('mysql')->select($sql);

        $sql = " update tmp_orders set price =  $Cost   where sku ='$d->sku' and channel = 4 ";
        DB::connection('mysql')->select($sql);
    }
 }
 //---------------------------------------------------------------
 public function GetAvailableQuantityInWH($Sku)
    {
        $Y4AStoreID = 46;
        $Result = 0;

        $sql = " SELECT   IFNULL(stock.quantity, 0) AS stock, IFNULL(reserved.quantity, 0) AS reserved,
        IFNULL(producing.quantity, 0) AS producing, IFNULL(pipeline.quantity, 0) AS pipeline
        FROM products
        LEFT JOIN (
            SELECT product_id, quantity FROM productinventory
            WHERE warehouse_id = $Y4AStoreID
        ) AS stock ON stock.product_id = products.id
        LEFT JOIN (
            SELECT product_id, SUM(quantity) AS quantity FROM productinventory_reserved
            GROUP BY product_id
        ) AS reserved ON reserved.product_id = products.id
        LEFT JOIN(
            SELECT shipment_container_details.product_id, SUM(shipment_container_details.quantity) AS quantity
            FROM shipment_containers
            JOIN shipment_container_details ON shipment_container_details.container_id = shipment_containers.id
            WHERE shipment_containers.status = 1
            GROUP BY shipment_container_details.product_id
        ) AS producing ON producing.product_id = products.id
        LEFT JOIN(
            SELECT shipment_container_details.product_id, SUM(shipment_container_details.quantity) AS quantity
            FROM shipment_containers
            JOIN shipment_container_details ON shipment_container_details.container_id = shipment_containers.id
            WHERE shipment_containers.status = 8
            GROUP BY shipment_container_details.product_id
        ) AS pipeline ON pipeline.product_id = products.id
        WHERE products.published = 1 AND products.is_virtual = 0 and products.product_sku = '$Sku'";

        $Stock = 0;
        $Reserved = 0;
        $ds = DB::connection('mysql_it')->select($sql);
        foreach( $ds  as $d ){
            $Stock = $this->iif(is_null($d->stock),0,$d->stock);
            $Reserved = $this->iif(is_null($d->reserved),0,$d->reserved);
            $Result = $Stock-$Reserved;
        }
       return $Result;
    }
 //---------------------------------------------------------------
}

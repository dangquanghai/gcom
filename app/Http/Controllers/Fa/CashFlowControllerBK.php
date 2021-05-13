<?php

namespace App\Http\Controllers\Fa;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use DB;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Reader\IReadFilter;
use Illuminate\Support\Str;
use Validator;
use DateTime;

class CashFlowController extends Controller
{
    private $CurrentDate;
    private $CurrentWeek =0;
    private $CurrentYear = 0;
    //private $MaxWeek = 30;//(52+25);// Số tuần tối đa cho mỗi lần import data FC ()
    private $StartYear = 0;
    private $StartWeek = 0;

    private $WeekCount = 52 ;// Số tuần cần xem data cashflow mặc định
    private $MinLeadTime = 0;// Lead time ngắn nhất trong số các sku của công ty
    private $MaxLeadTime = 0;// Lead time dài nhất trong số các sku của công ty

    private $ArrYear = array();// Mảng chứa năm của tuần hiện tại đến tuần cuối cùng có data FC
    private $ArrWeek = array();// Mảng chứa tuần của tuần hiện tại đến tuần cuối cùng có data FC
    private $ArrFC = array();  // Data FC tương ứng

    // Các số liệu tại thời điểm chạy chức năng này
    private $BalanceOnY4A = 0;
    private $BalanceOnAVC_WH = 0;
    private $BalanceOnFBA = 0;

    private $ForeCastOnY4A = 0;
    private $ForeCastOnAVC_WH = 0;
    private $ForeCastOnFBA = 0;

    private $ForeCastOnY4AInStage=0;
    private $ForeCastOnAVC_WHInStage=0;
    private $ForeCastOnFBAInStage=0;

    private $PipeLineY4A = 0;// Pipeline của kho ở us

    private $MaxOrderInWeek = 2;// Tổng số order của một vendor với 1 lead time tối đa trong một tuần
//=======================================================================================================================
public function __construct()
{
    //date_default_timezone_set('UTC');// Xét giờ mặc định là giờ quốc tế amazon lưu theo giờ này

    $this->CurrentDate = date('Y-m-d', time());
    $this->CurrentDate  = new DateTime($this->CurrentDate);
    $this->CurrentWeek = $this->CurrentDate->format("W");
    $this->CurrentYear = $this->CurrentDate->format("Y");

    $sql = " select min(vdp.lead_time) as lead_time from prd_product p
    left join pu_vendor_products vdp on p.product_sku = vdp.sku
    left join pu_vendors v on vdp.vendor_id = v.id
    where vdp.is_active = 1 and vdp.lead_time > 0";
    $ds = DB::connection('mysql')->select( $sql);
    foreach($ds as  $d){ $this->MinLeadTime = $d->lead_time/7; }

    $sql = " select max(vdp.lead_time) as lead_time from prd_product p
    left join pu_vendor_products vdp on p.product_sku = vdp.sku
    left join pu_vendors v on vdp.vendor_id = v.id
    where vdp.is_active = 1 and vdp.lead_time > 0";
    $ds = DB::connection('mysql')->select( $sql);
    foreach($ds as  $d){ $this->MaxLeadTime = $d->lead_time/7; }

    $this->InitArrayYearWeek($this->CurrentYear, $this->CurrentWeek);
    // print_r('Current Year '.$this->CurrentYear );
    // print_r('<br>');
    // print_r('Current Week '.$this->CurrentWeek );
    // print_r('<br>');
}
// ============================================================================================================
public function InsertToForeCastNew($TheDate,$sku,$TheQuantityPerDay,$Channel)
{

  $sql = " select id as MyCount from sal_forecasts where sku = '$sku' and date(the_date) = date('$TheDate') ";
  $ForeCastID = $this->IsExist('mysql',$sql);

  if($ForeCastID != 0 )// nếu đã tồn tại Forecast master
  {
      // print_r('Đã tồn tại master');
      // print_r('<br>' );

      $sql = " select id as MyCount from sal_forecast_details where 	sales_forecast_id =  $ForeCastID
      and channel_id = $Channel ";
      if($this->IsExist('mysql',$sql) != 0)// Nếu đã tồn tại ở Forcast detail-> xóa đi ghi lại
      {
      //  print_r('Đã tồn tại detail');
      //  print_r('<br>' );

        $sql = " delete from sal_forecast_details where sales_forecast_id = $ForeCastID and channel_id = $Channel ";
        db::connection('mysql')->select($sql);
        //-> ghi vào Forecast detail
        DB::connection('mysql')->table('sal_forecast_details')->insert(
        ['sales_forecast_id' => $ForeCastID,'channel_id'=>$Channel,'quantity'=>$TheQuantityPerDay]);
      }
      else
      {
          // print_r('Chưa tồn tại detail');
          // print_r('<br>' );
        DB::connection('mysql')->table('sal_forecast_details')->insert(
        ['sales_forecast_id' => $ForeCastID,'channel_id'=>$Channel,'quantity'=>$TheQuantityPerDay]);
      }

  }else// Chưa tồn tại ở forcast master -> ghi vào Forecast master-> ghi vào forecast detail
  {
  //   print_r('Chưa tồn tại master');
  //   print_r('<br>' );
    $ForeCastID = DB::connection('mysql')->table('sal_forecasts')->insertGetId(
    ['the_date'=> $TheDate, 'sku'=>$sku]);

    DB::connection('mysql')->table('sal_forecast_details')->insert(
    ['sales_forecast_id' => $ForeCastID,'channel_id'=>$Channel,'quantity'=>$TheQuantityPerDay]);
  }
}
//---------------------------------------------------
public function GetFirtDateOfMonth($Year,$Month)
{
  return  (string)$Year . '-'. (string)$Month. '-01';
}
//---------------------------------------------------
public function GetLastDateOfMonth($Year,$Month)
{
  $Result = date("Y-m-d");
  $sql = " SELECT GetLastDateOfMonth($Year,$Month) as Result ";
  $ds = DB::connection('mysql')->select ( $sql);
  foreach($ds as $d)  {  $Result = $d->Result; }
  return  $Result;
}
// ============================================================================================================
public function CalForecastForNextMonth()
{//$Year,$Month,$Rate,$ForMonths
 $Year = 2020;
 $Month = 6;
 $Rate = 2;
 $ForMonths = 6;// 3 Tháng liên tiếp

 for($i= 0; $i < $ForMonths; $i++ )
 {
  print_r('Tháng thứ :' .$i);
  print_r('<br>');
 if($Month ==  12 ){
   $NextMonth =1;
   $NextYear = $Year +1;
 }else{
   $NextMonth =$Month + 1;
   $NextYear = $Year ;
 }
 $sFirstDay = $this->GetFirtDateOfMonth($NextYear, $NextMonth);
 $sLastDay = $this->GetLastDateOfMonth($NextYear, $NextMonth);
 $FirstDay = new DateTime( $sFirstDay);
 $LastDay = new DateTime($sLastDay);

 $TheDate = $sFirstDay;

 $iDays = $LastDay->format("d");
 $iDay =  $FirstDay->format("d");

// print_r('Ngày đầu tháng:'. $sFirstDay. ' Ngày cuối tháng: ' . $sLastDay . 'The Date ' . $iDay  . ' Last Date ' . $iDays );
 //print_r('<br>');

 $sql =  " select sku, sum(sell_quantity) as sell_quantity, sales_chanel
 from sal_selling_summary_monthlys
 where the_month = $Month and the_year = $Year and hmd_product = 1  and by_invoice = 0
 group by sku,sales_chanel " ;

 $ds = DB::connection('mysql')->select($sql);
 foreach($ds as $d)
 {
   $fcDay = $d->sell_quantity/30 * $Rate;
    while($iDay < $iDays)
    {
     $this->InsertToForeCastNew($TheDate,$d->sku,$fcDay,$d->sales_chanel);
     $TheDate = $this->MoveDate( $TheDate,1);
     $iDay++;
     print_r('Insert FC sku:' .$d->sku .' Ngày '.$TheDate .' Chanel ' . $d->sales_chanel. 'Số lượng:'.$fcDay);
     print_r('<br>');
    }
 }// For
 }// for
}
//=======================================================================================================================
public function InitArrayYearWeek($iTheYear, $iTheWeek)
{
    $TheYear = $iTheYear;
    $TheWeek = $iTheWeek;
    $this->ArrYear = array($this->MaxLeadTime + 1 + $this->WeekCount);
    $this->ArrWeek = array($this->MaxLeadTime+1+$this->WeekCount);
    $this->ArrFC = array($this->MaxLeadTime+1+$this->WeekCount);

    // Gán các giá trị year, week vào nhưng tuần từ hiện tại
    // có chỉ số index =(MaxLeadTime+1) trở về quá khứ đầu mảng có chỉ số index =0
    for($i = $this->MaxLeadTime;$i>=0;$i--)
    {
        $this->ArrWeek[$i]=  $TheWeek;
        $this->ArrYear[$i]= $TheYear;
        $TheWeek = $TheWeek - 1;
        if($TheWeek < 1)
        {
            $TheWeek = 52;
            $TheYear = $TheYear - 1;
        }
    }
    $TheYear = $iTheYear;
    $TheWeek = $iTheWeek;

    // Gán các giá trị year, week vào nhưng tuần từ hiện tại
    // có chỉ số index =(MaxLeadTime+1) đến tương lai có chỉ số index max =
    for($i = $this->MaxLeadTime ;$i<= $this->WeekCount+ $this->MaxLeadTime+1;$i++)
    {
        $this->ArrWeek[$i]=  $TheWeek;
        $this->ArrYear[$i]= $TheYear;
        $TheWeek = $TheWeek + 1;
        if($TheWeek > 52)
        {
            $TheWeek = 1;
            $TheYear = $TheYear + 1;
        }
    }

    // for($j = 0 ;$j <$this->WeekCount ; $j++)
    // {
    //     print_r(' Năm: ' .  $this->ArrYear[$j]. ' Tuần : ' .  $this->ArrWeek[$j]);
    //     print_r('<br>');
    // }

}
//=======================================================================================================================
public function GetBalanceBeginningOnTheDate( $Sku,$TheDate)
{
    $Y4AStoreID = 46;
    $sql = " SELECT   IFNULL(stock.quantity, 0) AS stock, IFNULL(reserved.quantity, 0) AS reserved,
    IFNULL(producing.quantity, 0) AS producing, IFNULL(pipeline.quantity, 0) AS pipeline
    FROM products
    LEFT JOIN (
        SELECT product_id, quantity FROM productinventory
        WHERE warehouse_id = 46
    ) AS stock ON stock.product_id = products.id
    LEFT JOIN (
        SELECT product_id, SUM(quantity) AS quantity FROM productinventory_reserved
        GROUP BY product_id
    ) AS reserved ON reserved.product_id = products.id
    LEFT JOIN(
        SELECT shipment_containers_details.product_id, SUM(shipment_containers_details.quantity) AS quantity
        FROM shipment_containers
        JOIN shipment_containers_details ON shipment_containers_details.container_id = shipment_containers.id
        WHERE shipment_containers.status = 1
        GROUP BY shipment_containers_details.product_id
    ) AS producing ON producing.product_id = products.id
    LEFT JOIN(
        SELECT shipment_containers_details.product_id, SUM(shipment_containers_details.quantity) AS quantity
        FROM shipment_containers
        JOIN shipment_containers_details ON shipment_containers_details.container_id = shipment_containers.id
        WHERE shipment_containers.status = 8
        GROUP BY shipment_containers_details.product_id
    ) AS pipeline ON pipeline.product_id = products.id
    WHERE products.published = 1 AND products.is_virtual = 0 and products.product_sku = '$Sku'";

    $Stock = 0;
    $Reserved = 0;
    $ds = DB::connection('mysql_it')->select($sql);
    $this->BalanceOnY4A = 0;
    foreach( $ds  as $d ){
        $Stock = $this->iif(is_null($d->stock),0,$d->stock);
        $Reserved = $this->iif(is_null($d->reserved),0,$d->reserved);
    }

    // Lấy số pipeline của Y4A
    // smcon.status in (1,8) -- trang thai cua container la start up/pipeline
    $Pipeline = 0;
    $ds  = DB::connection('mysql_it')->select("
    select case when  a.Quantity is null then 0 else a.Quantity end as Quantity from
    ( select sum( smcondt.quantity) as Quantity
    from shipment sm inner join
    shipment_containers smcon on sm.id =smcon.shipment_id  inner JOIN
    shipment_containers_details  smcondt on smcon.id = smcondt.container_id inner JOIN
    products prd on smcondt.product_id =prd.id
    where  smcon.status in (1,8)
    and  Date(sm.expect_stocking_date) < Date('$TheDate')
    and prd.product_sku =  '$Sku' )a where a.Quantity>0 ");

    foreach( $ds  as $d){
     $Pipeline = $this->iif(is_null($d->Quantity),0,$d->Quantity);
    }
    $this->BalanceOnY4A = max($Stock + $Pipeline -$Reserved,0);


       $sql = " SELECT   IFNULL(stock.quantity, 0) AS stock_quantity, IFNULL(fba.quantity, 0) AS fba_quantity,
       IFNULL(fba_pipeline.quantity, 0) AS fba_pipeline_quantity, IFNULL(avc_wh.quantity, 0) AS avc_wh_quantity
       FROM products
       LEFT JOIN (
           SELECT product_id, quantity FROM productinventory
           WHERE warehouse_id = 46
       ) AS stock ON stock.product_id = products.id
       LEFT JOIN (
           SELECT product_id, SUM(sellable) AS quantity
           FROM `fba_inventory`
           WHERE sellable != 0
           GROUP BY product_id
       ) AS fba ON fba.product_id = products.id
       LEFT JOIN (
           SELECT fbashipment_details.product_id, SUM(fbashipment_details.quantityshipped) AS quantity
           FROM fbashipments
           JOIN `fbashipment_details` ON fbashipment_details.fbashipment_id = fbashipments.id
           WHERE fbashipments.status = 3
           GROUP BY fbashipment_details.product_id
       ) AS fba_pipeline ON fba_pipeline.product_id = products.id
       LEFT JOIN (
           SELECT amazon_avc_inventory.product_id, amazon_avc_inventory.sellable_unit AS quantity
           FROM amazon_avc_inventory
           JOIN (
               SELECT product_id, MAX(LEFT(report_date, 10)) AS date
               FROM `amazon_avc_inventory`
               WHERE product_id != 0
               GROUP BY product_id
           ) AS last_avc_inventory ON last_avc_inventory.product_id = amazon_avc_inventory.product_id
           AND last_avc_inventory.date = LEFT(amazon_avc_inventory.report_date, 10)
           WHERE amazon_avc_inventory.sellable_unit != 0
       ) AS avc_wh ON avc_wh.product_id = products.id
       where products.product_sku = '$Sku'";

     $ds = DB::connection('mysql_it')->select($sql);
     $this->BalanceOnFBA = 0;
     $this->BalanceOnAVC_WH = 0;
     foreach( $ds  as $d ){
        $this->BalanceOnFBA  = $d->fba_quantity + $d->fba_pipeline_quantity;
        $this->BalanceOnAVC_WH = $d->avc_wh_quantity;
     }
    // --------Tim so ton cua kenh AVC-WH ----------------
    //$BalanceAVC_WHs = DB::connection('mysql')->select(" select GetBalanceOnAvcWh( '$sku') as quantity ");
    //$this->BalanceOnAVC_WH = 0;
    //foreach($BalanceAVC_WHs as $BalanceAVC_WH ){
    // $this->BalanceOnAVC_WH  = $this->iif(is_null($BalanceAVC_WH->quantity),0,$BalanceAVC_WH->quantity);  ;
    //}
    return $this->BalanceOnY4A;
}
//=======================================================================================================================
public function GetPipeLineQuantityInstage($Sku,$FromDate, $ToDate)
{
    $Result = 0;
    // Lấy số pipeline của Y4A
    // smcon.status in (1,8) -- trang thai cua container la start up/pipeline
    $sql = " select case when  a.Quantity is null then 0 else a.Quantity end as Quantity from
    ( select sum( smcondt.quantity) as Quantity
    from shipment sm inner join
    shipment_containers smcon on sm.id =smcon.shipment_id  inner JOIN
    shipment_containers_details  smcondt on smcon.id = smcondt.container_id inner JOIN
    products prd on smcondt.product_id =prd.id
    where  smcon.status in (1,8)
    and  Date(sm.expect_stocking_date) >= Date('$FromDate')
    and  Date(sm.expect_stocking_date) <= Date('$ToDate')
    and prd.product_sku =  '$Sku' )a where a.Quantity>0 ";

    $ds  = DB::connection('mysql_it')->select($sql);

    foreach($ds as  $d) {  $Result = $this->iif(is_null( $d->Quantity),0, $d->Quantity); }

    //print_r('sql Pipe line nè: ' .$sql);
    //print_r('<br>');


    //print_r('Pipe line nè: ' .$Result);
    //print_r('<br>');
    return  $Result;
}
public function LoadTest1()
{
  $sql = " select id, name, price from test1 " ;
  $ds = DB::connection('mysql')->select($sql);
  return json_encode($ds);
}
//=======================================================================================================================
public function MoveDate($TheDate, $Days)
{
  return  date('Y-m-d',strtotime( $TheDate. '+'.  $Days  .'days'));
// return date('Y-m-d', strtotime($TheDate . $Operator.$Days ." days"));
}
//=======================================================================================================================
public function UpdateLogicBalanceStore()// logic số tồn kho giữa 3 kho
{
    if($this->BalanceOnAVC_WH < 0 )// Giả định lượng hàng tồn trên avc-wh không đủ bán
    {
        // Giả định số tồn kho trên kho chính có thể bù đắp cho sự thiếu hụt ở kho avc-wh
        if( $this->BalanceOnY4A > 0 && $this->BalanceOnY4A >= $this->BalanceOnAVC_WH )
        {
        // Giả định hàng ở kho chính chuyển kịp thời lên kho amazon để bán avc-wh
        $this->BalanceOnY4A = round($this->BalanceOnY4A - (- $this->BalanceOnAVC_WH),0);
        }
    }
    if($this->BalanceOnFBA < 0 )// Giả định lượng hàng tồn trên fba không đủ bán
    {
        // Giả định số tồn kho trên kho chính có thể bù đắp cho sự thiếu hụt ở kho avc-wh
        if( $this->BalanceOnY4A > 0 && $this->BalanceOnY4A >= $this->BalanceOnFBA )
        {
        // Giả định hàng ở kho chính chuyển kịp thời lên kho amazon để bán FBA
        $this->BalanceOnY4A = round($this->BalanceOnY4A - (- $this->BalanceOnFBA),0);
        }
    }
}
//=======================================================================================================================
public function CaculateWeeklyProductDemand($CurrentDate)
{
    $sql = " delete from pu_product_demand_weekly " ;
    DB::connection('mysql')->select( $sql);
    $NeeAddLoi = false;
    // Tính toán nhu cầu hàng cần cung cấp mỗi tuần, mỗi lần tính (MaxWeek - MinLeadTime) tuần thông thường (77-12)
    // Tuần bắt đầu =  (tuần hiện tại + với tuần có sku với số lead time nhỏ nhất)
    // Tuần kết thúc là 51 tuần tiếp theo
    // Ví dụ tuần hiện tại là 31 (năm 2020), có  sku lần lượt có lead time là 12,13 vậy sẽ tính cho tuần
    // đầu tiên là 31 +12 = 43/2020 đến 51 tuần sau: là tuần thứ 42/2021
    // Đầu vào:danh sách sku đi kèm vendor, leadtime kết hợp với  Data FC, Số tồn kho, pipeline
    // Đầu ra: PU plan: là các PO và chi tiết PO giả định hàng tuần

    $StartSellingYear = $CurrentDate->format("Y");
     // Lùi lại 2 ngày trong quá khứ để lấy số tồn của avc wh cho chính xác
    // Câu lệnh này nên vô hiệu lực khi chưa có hàng bán ở kênh avc-wh
    // $CurentDate  = date('Y-m-d',strtotime($CurentDate. '+'. '-2 days'));
    // Tạm lùi 1 ngày
    print_r('Min Lead time(Week):'.$this->MinLeadTime);
    print_r('<br>');

    $CurrentDate = $CurrentDate->format('Y-m-d');
    $CurrentWeek = $this->GetWeekFromDate($CurrentDate);
    $CurrentYear = $this->GetYearFromDate($CurrentDate);

    // $StartSellingWeek = $this->CurrentWeek + $this->MinLeadTime/7;

    // if($StartSellingWeek > 52)
    // {
    //  $StartSellingYear = $StartSellingYear + 1;
    //  $StartSellingWeek = $StartSellingWeek - 52 ;
    // }
    // print_r('StartSellingWeek:'.$StartSellingWeek. '  Năm : '.$StartSellingYear );
    // print_r('<br>');

    $sql = " select p.product_sku as sku,v.id as vendor_id,vdp.lead_time
    from prd_product p left join pu_vendor_products vdp on p.product_sku = vdp.sku
    left join pu_vendors v on vdp.vendor_id = v.id
    where vdp.is_active = 1 and vdp.lead_time >0 and vdp.lead_time is not null";
    //and p.product_sku  ='AX59' ";

    // print_r('Start Selling week: ' . $StartSellingWeek );
    // print_r('<br>');
    // print_r('Start Selling year: ' . $StartSellingYear);
    // print_r('<br>');
    $countSku = 0;
    $ds = DB::connection('mysql')->select( $sql);
    foreach($ds as  $d)
    {
        $WeekLeadTime =  $d->lead_time/7;
        $this->GetBalanceBeginningOnTheDate($d->sku,$CurrentDate);
        // print_r('sku:'. $d->sku. 'Tồn đầu kho Y4A: '.  $this->BalanceOnY4A );
        // print_r('<br>');
        // print_r('Tồn đầu kho WH:'. $this->BalanceOnAVC_WH);
        // print_r('<br>');
        // print_r('Tồn đầu kho FBA:'.  $this->BalanceOnFBA );
        // print_r('<br>');

        $this->UpdateLogicBalanceStore();
         print_r('sku:'. $d->sku. 'Tồn đầu : '.  $this->BalanceOnY4A );

        // Tính lượng FC và Pipeline từ ngày hiên tại đến ngày cuối tuần hiện tại
        $LastDateOfCurentWeek = $this->GetLastDateOfWeek($CurrentYear, $CurrentWeek);
        $PipeLine = round($this->GetPipeLineQuantityInstage($d->sku,$CurrentDate,$LastDateOfCurentWeek),0);
        $Forecast = round($this->GetForecastQuantityInstage($d->sku,$CurrentDate,  $LastDateOfCurentWeek,$NeeAddLoi),0);

        print_r('Tổng số FC từ ngày hiện tại đến ngày cuối tuần: ' . $Forecast);
        print_r('<br>') ;
        print_r('Tổng số Pipeline từ ngày hiện tại đến ngày cuối tuần: ' . $PipeLine );
        print_r('<br>') ;

        $TonDau =  $this->BalanceOnY4A ;
        $Missing = min($this->BalanceOnY4A - $Forecast + $PipeLine,0);
        $this->BalanceOnY4A = max($this->BalanceOnY4A -  $Forecast + $PipeLine,0);
        $NeedToBuy = 0;

        $CurrentIndex = $this->GetIndex($CurrentYear, $CurrentWeek);

        //  print_r(' Tuần: ' . $this->ArrWeek[0]. 'Tồn đầu: '. $TonDau . ' FC: '.$Forecast. ' Pipe line: ' .  $PipeLine. ' Tồn cuối : '. $this->BalanceOnY4A  . ' Hàng thiếu: '.  $Missing );
        //  print_r('<br>');
        //  print_r('Lượng hàng tồn cuối tuần: ' .  $this->BalanceOnY4A. 'Lượng hàng thiếu: ' . $Missing );
        //  print_r('<br>') ;

       // print_r('CurrentIndex: ' . $CurrentIndex);
       // print_r('<br>');

        //  Lưu thông tin Demand của tuần hiện tại
        $this->RecordWeeklyProductDemand($d->sku,
        $this->ArrYear[$CurrentIndex-$WeekLeadTime],$this->ArrWeek[$CurrentIndex-$WeekLeadTime],// Order Week
        $this->ArrYear[$CurrentIndex -6],$this->ArrWeek[$CurrentIndex  -6],//Load Week
        $this->ArrYear[$CurrentIndex -5],$this->ArrWeek[$CurrentIndex  -5],//ETD Week
        $this->ArrYear[$WeekLeadTime-2],$this->ArrWeek[$CurrentIndex -2],//ETA Week
        $this->ArrYear[$CurrentIndex],$this->ArrWeek[$CurrentIndex],// Selling week
        $d->vendor_id, $WeekLeadTime,$NeedToBuy,$Missing);
        //print_r('Tổng số tồn cuối tuần hiện tại: ' .  $this->BalanceOnY4A );
       // print_r('<br>') ;

        // Từ tuần sau tuần hiện tại đến hết tuần cuối của week count
        for($i =  $CurrentIndex +1 ; $i < $this->WeekCount + $this->MaxLeadTime ; $i++)
        {

            $TonDau =  $this->BalanceOnY4A ;
            $Missing = 0;
            $FirstDateOfWeek = $this->GetFirstDateOfWeek($this->ArrYear[$i],$this->ArrWeek[$i]);
            $LastDateOfWeek = $this->GetLastDateOfWeek($this->ArrYear[$i],$this->ArrWeek[$i]);

            $PipeLine = round($this->GetPipeLineQuantityInstage($d->sku,$FirstDateOfWeek,$LastDateOfWeek ),0);

            if($i == $WeekLeadTime +1){ $NeeAddLoi = true; }
            else{$NeeAddLoi = false;}
            $Forecast =round($this->GetForecastQuantityInstage($d->sku,$FirstDateOfWeek,$LastDateOfWeek,$NeeAddLoi),0);

            $Missing = min($this->BalanceOnY4A -  $Forecast + $PipeLine,0);
            $this->BalanceOnY4A = max($this->BalanceOnY4A -  $Forecast + $PipeLine,0);

            // Đang trong giai đoạn lead time
            // k thể bổ sung hàng, chỉ ghi nhận lượng hàng thiếu hụt
           if($i <  $WeekLeadTime + $CurrentIndex +1  )
            {
             $NeedToBuy = 0;
            }elseif($Missing < 0 )
            {
                $NeedToBuy =  -$Missing;
                $Missing = 0;
            }
            print_r(' Tuần: ' . $this->ArrWeek[$i]. 'Tồn đầu: '. $TonDau . ' FC: '.$Forecast. ' Pipe line: ' .  $PipeLine. ' Tồn cuối : '. $this->BalanceOnY4A  . ' Hàng thiếu: '.  $Missing );
            print_r('<br>');

           // if( $NeedToBuy <> 0 ){
                $this->RecordWeeklyProductDemand($d->sku,
                $this->ArrYear[$i-$WeekLeadTime],$this->ArrWeek[$i-$WeekLeadTime],// Order Week
                $this->ArrYear[$i-6],$this->ArrWeek[$i-6],//Load Week
                $this->ArrYear[$i-5],$this->ArrWeek[$i-5],//ETD Week
                $this->ArrYear[$i-2],$this->ArrWeek[$i-2],//ETA Week
                $this->ArrYear[$i],$this->ArrWeek[$i],// Selling week
                $d->vendor_id, $WeekLeadTime,$NeedToBuy,$Missing);
            //}
        }
        $countSku++;
        print_r('HẾT SKU: '. $d->sku. 'thứ:'.$countSku .' ==========================');
        print_r('<br>');

    }// for sku

    // Chuyển data từ table pu_product_demand_weekly sang 2 table
    // pu_product_demands,pu_product_demand_details
    $DemandID = 0;
    $sql = " delete from pu_product_demand_details " ;
    DB::connection('mysql')->select( $sql);

     $sql = " delete from pu_product_demands " ;
     DB::connection('mysql')->select( $sql);

    $sql = " select vendor_id,lead_time, order_year,order_week,load_year,
    load_week, etd_year, etd_week, eta_year, eta_week,selling_year,selling_week,is_off_week
    from pu_product_demand_weekly
    group by order_year,order_week,lead_time,load_year, load_week, etd_year, etd_week,
    eta_year, eta_week,selling_year,selling_week,vendor_id,lead_time,is_off_week
    order by order_year,order_week,lead_time,load_year, load_week, etd_year, etd_week,
    eta_year, eta_week,selling_year,selling_week,vendor_id,lead_time  ";

    $ds = DB::connection('mysql')->select($sql);
    foreach($ds as $d )
    {
     $DemandID = DB::connection('mysql')->table('pu_product_demands')->insertGetId(
     ['vendor_id'=>$d->vendor_id, 'lead_time'=>$d->lead_time,'order_year'=>$d->order_year,
     'order_week'=>$d->order_week ,'load_year'=>$d->load_year,'load_week'=>$d->load_week,
     'etd_year'=>$d->etd_year,'etd_week'=>$d->etd_week,'eta_year'=>$d->eta_year,'eta_week'=>$d->eta_week,
     'selling_year'=>$d->selling_year,'selling_week'=>$d->selling_week]);


     $Case = $this->CheckWeekOff($DemandID);
     print_r('ID ' .$DemandID. 'Case: ' . $Case);
     print_r('<br>');
     $sql = " update pu_product_demands set is_week_off = $Case where id  = $DemandID";
     DB::connection('mysql')->select($sql);

      $sql = " select sku,sum(quantity) as quantity, sum(missing) as missing from pu_product_demand_weekly
      where order_year = $d->order_year and order_week = $d->order_week
      and vendor_id = $d->vendor_id and lead_time = $d->lead_time
      and load_year = $d->load_year and load_week = $d->load_week
      and etd_year = $d->etd_year and etd_week = $d->etd_week
      and eta_year = $d->eta_year and eta_week = $d->eta_week
      and selling_year = $d->selling_year and selling_week = $d->selling_week
      group by sku " ;

      $d1s = DB::connection('mysql')->select($sql);
      foreach($d1s as $d1 )
      {
       DB::connection('mysql')->table('pu_product_demand_details')
       ->insert(['demand_id'=>$DemandID,'sku'=>$d1->sku,'quantity'=>$d1->quantity,'missing'=>$d1->missing]);
       print_r(' Insert vào chi tiết pu_product_demand_details ' );
       print_r('<br>');
      }// for dtail
    }// for master
    // Kết thúc việc chuyển data từ pu_product_demand_weekly-> pu_product_demands,pu_product_demand_details
}
//=======================================================================================================================
public function CreatePOEstimate()
{
    // $ProductListAvailables= DB::select ('call PU_GetProductListAvailable(?)',[$VendorID]);

    // Xóa data PO Estimate
    $sql = " delete from pu_po_estimate_details " ;
    DB::connection('mysql')->select($sql);

     // Xóa những PO tự sinh ở làn trước không được PU can thiệp
     $sql = " delete from pu_po_estimates where pu_adjust = 0 ";
     DB::connection('mysql')->select($sql);


    $sql = " select id, vendor_id, lead_time, order_year, order_week, load_year, load_week,
    etd_year, etd_week, eta_year,eta_week, selling_year, selling_week, is_week_off
    from pu_product_demands ";
    $ds = DB::connection('mysql')->select($sql);
    foreach( $ds as $d )
    {
        // Kiểm tra xem trong po estimate có po nào đang có trùng với thông tin trên Demand mới không
        $sql = " select id as MyCount from pu_po_estimates where vendor_id = $d->vendor_id
        and lead_time = $d->lead_time and the_year = $d->order_year and  the_week = $d->order_week
        and pu_adjust = 1 ";
        $id = $this->IsExist('mysql',$sql) ;

        if($id == 0)  // Nếu chưa có thì tạo PO estimate đó
        {
         $OrderDate = $this->GetLastDateOfWeek($d->order_year,$d->order_week);
         $OrderDate = $this->MoveDate($OrderDate,-1);// Vào ngày thứ 6 hàng tuần

         $LoadDate = $this->GetLastDateOfWeek($d->load_year,$d->load_week);
         $EtdDate = $this->GetLastDateOfWeek($d->etd_year,$d->etd_week);
         $EtaDate = $this->GetLastDateOfWeek($d->eta_year,$d->eta_week);

         // 2 CÁI NÀY CẦN KIỂM TRA LẠI CÓ THỂ CHO THÊM BIẾN VENDOR ID VÀO
         // $StartSellingDate = $this->GetMinStartSelling($d->order_year,$d->order_week,$d->vendor_id,$d->lead_time);
         // $EndSellingDate = $this->GetMaxEndSelling($d->order_year,$d->order_week,$d->vendor_id,$d->lead_time);

         $StartSellingDate = $this->GetLastDateOfWeek($d->selling_year,$d->selling_week);
         // Thêm vào 7 ngày
         $EndSellingDate = $this->MoveDate( $StartSellingDate,7);

         // Tạp master
         $id = DB::connection('mysql')->table('pu_po_estimates')->insertGetId(
         ['the_year'=>$d->order_year,'the_week'=>$d->order_week,'vendor_id'=>$d->vendor_id,
         'lead_time'=>$d->lead_time,'order_date'=>$OrderDate,
         'expect_load_date'=>$LoadDate,'expect_etd_date'=>$EtdDate ,
         'expect_eta_date'=>$EtaDate,'start_selling_date'=>$StartSellingDate,
         'end_selling_date'=>$EndSellingDate]);

         // Tạo Detail
         $sql = " select dmdt.sku, p.fob_price,p.appotion_price, p.life_cycle,
         p.moq, p.sell_type, sum(dmdt.quantity) as quantity
         from pu_product_demand_details  dmdt
         left join prd_product  p on dmdt.sku = p.product_sku
         where dmdt.demand_id =  $d->id ";

         $d1s = DB::connection('mysql')->select($sql);
         foreach( $d1s as $d1 )
         {
            DB::connection('mysql')->table('pu_po_estimate_details')->insert(
            ['po_estimate_id'=>$id,'sku'=>$d1->sku,'quantity'=>$d1->quantity,
            'sell_type'=>$d1->sell_type,'fob_price'=>$d1->fob_price,
            'appotion_price'=>$d1->appotion_price,
            'moq'=>$d1->moq,'life_cycle'=>$d1->life_cycle]);
         }// for
        }
        else
        {
         // Tạo Detail
         $sql = " select dmdt.sku, p.fob_price,p.appotion_price, p.life_cycle,
         p.moq, p.sell_type, sum(dmdt.quantity) as quantity
         from pu_product_demand_details  dmdt
         left join prd_product  p on dmdt.sku = p.product_sku
         where dmdt.demand_id =  $d->id ";

         $d1s = DB::connection('mysql')->select($sql);
         foreach( $d1s as $d1 )
         {
            DB::connection('mysql')->table('pu_po_estimate_details')->insert(
            ['po_estimate_id'=>$id,'sku'=>$d1->sku,'quantity'=>$d1->quantity,
            'sell_type'=>$d1->sell_type,'fob_price'=>$d1->fob_price,
            'appotion_price'=>$d1->appotion_price,
            'moq'=>$d1->moq,'life_cycle'=>$d1->life_cycle]);
         }// for
        }
    }//for
}
//=======================================================================================================================
public function GetMinStartSelling($OrderYear,$OrderWeek,$VendorID,$LeadTime)
{//$OrderYear,$OrderWeek
 //$OrderYear = 2020;
// $OrderWeek = 36;

 $sql = " select min(selling_year) as selling_year from pu_product_demands
 where 	order_year = $OrderYear and  order_week = $OrderWeek
 and vendor_id = $VendorID and lead_time = $LeadTime " ;
 $ds = DB::connection('mysql')->select($sql);
 foreach( $ds as $d ){$SellingYear  = $d->selling_year;}

 $sql = " select min(selling_week) as selling_week from pu_product_demands
 where 	order_year = $OrderYear and  order_week = $OrderWeek and vendor_id = $VendorID
 and  lead_time = $LeadTime and selling_year = $SellingYear " ;
 $ds = DB::connection('mysql')->select($sql);
 foreach( $ds as $d ){$SellingWeek  = $d->selling_week;}
 return $this->GetFirstDateOfWeek($SellingYear,$SellingWeek);

}
//=======================================================================================================================
public function GetMaxEndSelling( $OrderYear,$OrderWeek,$VendorId,$LeadTime)
{
    $sql = " select max(selling_year) as selling_year from pu_product_demands
    where order_year = $OrderYear and  order_week = $OrderWeek
    and vendor_id = $VendorId and lead_time = $LeadTime " ;
    $ds = DB::connection('mysql')->select($sql);
    foreach( $ds as $d ){$SellingYear  = $d->selling_year;}

    $sql = " select max(selling_week) as selling_week from pu_product_demands
    where order_year = $OrderYear and  order_week = $OrderWeek
    and vendor_id = $VendorId and lead_time = $LeadTime  and  selling_year = $SellingYear " ;
    $ds = DB::connection('mysql')->select($sql);
    foreach( $ds as $d ){$SellingWeek  = $d->selling_week;}
    return $this->GetLastDateOfWeek($SellingYear,$SellingWeek);
}
//=======================================================================================================================
public function GetEndSellingDate($sTheYear,$ForOrderWeek)
{
    //substr(string,start,length)
    $sTheYear  = substr($sTheYear,strlen($sTheYear)-5,4);
    $ForOrderWeek  = substr($ForOrderWeek ,strlen($ForOrderWeek)-2,1);

    $iTheYear = (integer)( $sTheYear);
    $iTheWeek = (integer)($ForOrderWeek);
    //print_r('Year:' . $iTheYear);
    //print_r('<br>' );
   // print_r('Week:' . $iTheWeek);
    return $this->GetLastDateOfWeek($iTheYear,$iTheWeek);
}
//=======================================================================================================================
public function ReArranOrderWeekNew()
{
    // Lấy danh sách các order bị trùng với lịch nghỉ là những order phải thực hiện dồn tuần
    $sql = " select id,	vendor_id,lead_time, order_year,order_week
    from pu_product_demands where is_week_off <> 0  ";
    $ds = DB::connection('mysql')->select($sql);
    foreach($ds as $d )
    {
     // Đánh dấu nó trong mảng Year, Week
     $Index = $this->GetIndex($d->order_year,$d->order_week);
     // print_r(' Index: '.$Index);
     // print_r('<br>');
     // Dịch chuyển order week về trước cho tới khi
     // order week, load week, etd week không còn chùng với tuần nghỉ(week off)
     $this->MoveBackNew($d->id,$Index,$d->vendor_id,$d->lead_time,$this->MaxOrderInWeek);
    }
}
//=======================================================================================================================
public function MoveBackNew($id,$Index,$VendorID,$LeadTime,$MaxOrderInWeek)
{
    $Continue =True;
    $Step = 1;
    while($Step <= $Index && $Continue ==true)
    {

            $OrderYear = $this->ArrYear[$Index- $Step];// dịch chuyển order week về một tuần
            $OrderWeek = $this->ArrWeek[$Index-$Step];

            $LoadYear = $this->ArrYear[$Index+ $LeadTime - ($Step + 6)];// dịch chuyển Load  week về 7 tuần
            $LoadWeek = $this->ArrWeek[$Index+  $LeadTime -($Step+ 6)];//

            $EtdYear = $this->ArrYear[$Index+ $LeadTime -($Step + 5)];// dịch chuyển ETD  week về 6 tuần
            $EtdWeek = $this->ArrWeek[$Index+ $LeadTime -($Step+ 5)];//

            $EtaYear = $this->ArrYear[$Index+ $LeadTime -($Step + 2)];// dịch chuyển ETA  week về 6 tuần
            $EtaWeek = $this->ArrWeek[$Index+ $LeadTime -($Step+ 2)];//

            // Kiểm tra ở tuần này tổng số order đã dồn cho tuần này với mỗi vendor,leadtime đã vượt quá limid chưa

            $sql  = " select count(id) as MyCount from pu_product_demands
            where order_year = $OrderYear and order_week = $OrderWeek
            and vendor_id = $VendorID and lead_time = $LeadTime ";

            print_r('sql đếm : '.$sql);
            print_r('<br>');

            $Count =  $this->IsExist('mysql',$sql);
            print_r('Count : '.$Count);
            print_r('<br>');


            if($Count < $MaxOrderInWeek)// Chưa vượt quá mức tối đa
            {
                print_r(' Dồn chưa vượt quá mức cho phép: ' );
                print_r('<br>');

                $sql  = " update pu_product_demands set order_year = $OrderYear, order_week = $OrderWeek ,
                load_year = $LoadYear, load_week =  $LoadWeek, etd_year =  $EtdYear, etd_week =  $EtdWeek,
                eta_year = $EtaYear , eta_week = $EtaWeek where id = $id  ";

                print_r(' sql update : '.  $sql );
                print_r('<br>');


                DB::connection('mysql')->select($sql);
                if($this->CheckWeekOff($id)==0 )
                {
                  $Continue = false;
                  print_r(' Đã kiểm ok id : '.  $id );
                  print_r('<br>');
                }
                else
                {
                 $Step++;
                 print_r(' Đã kiểm không OK, tiếp tục move về phía trước : '.  $id . ' Step = '. $Step . ' Continue =' . $Continue . ' Index =' .$Index   );
                 print_r('<br>');
                }
            }else
            {
                $Step++;
                print_r(' Tuần này đã dồn order vượt quá mức cho phép Count ='.  $Count );
                print_r('<br>');
            }
    }
}

//=======================================================================================================================
public function GetIndex($OrderYear, $OrrderWeek)
{
    $TheIndex = 0;
    $Continue = true;
    $i = 0;

    while($i < $this->WeekCount && $Continue ==true)
    {
        //print_r('ArrYear' . $this->ArrYear[$i]. ' Week: ' .  $this->ArrWeek[$i]);
        // print_r('<br>');
        if($this->ArrYear[$i] == $OrderYear && $this->ArrWeek[$i] == $OrrderWeek)
        {
            $TheIndex = $i;
            $Continue =false;
        }
        $i++;
    }
    return  $TheIndex;
}
//=======================================================================================================================
public function MoveBack($id,$Index,$LeadTime)
{
    $Continue =True;
    $Step = 1;
    while($Step < $this->WeekCount && $Continue ==true)
    {
        if($Continue)
        {
            $OrderYear = $this->ArrYear[$Index- $Step];// dịch chuyển order week về một tuần
            $OrderWeek = $this->ArrWeek[$Index-$Step];

            $LoadYear = $this->ArrYear[$Index+ $LeadTime - ($Step + 6)];// dịch chuyển Load  week về 7 tuần
            $LoadWeek = $this->ArrWeek[$Index+  $LeadTime -($Step+ 6)];//

            $EtdYear = $this->ArrYear[$Index+ $LeadTime -($Step + 5)];// dịch chuyển ETD  week về 6 tuần
            $EtdWeek = $this->ArrWeek[$Index+ $LeadTime -($Step+ 5)];//

            $EtaYear = $this->ArrYear[$Index+ $LeadTime -($Step + 2)];// dịch chuyển ETA  week về 6 tuần
            $EtaWeek = $this->ArrWeek[$Index+ $LeadTime -($Step+ 2)];//

            $sql  = " update pu_product_demands set order_year = $OrderYear, order_week = $OrderWeek ,
            load_year = $LoadYear, load_week =  $LoadWeek, etd_year =  $EtdYear, etd_week =  $EtdWeek,
            eta_year = $EtaYear , eta_week = $EtaWeek where id = $id  ";

            DB::connection('mysql')->select($sql);

            if($this->CheckWeekOff($id)==0 ){$Continue = false;}
            $Step++;
        }
    }
    return $Step;
}
//=======================================================================================================================
public function RecordWeeklyProductDemand($Sku,$OrderYear, $OrderWeek,$LoadYear,$LoadWeek,$EtdYear, $EtdWeek,
$EtaYear,$EtaWeek,$SellingYear,$SellingWeek,$VendorID,$LeadTime,$Quantity,$Missing)
{
 $sql = " delete from pu_product_demand_weekly where sku = '$Sku'
 and order_year = $OrderYear and order_week = $OrderWeek ";
 DB::connection('mysql')->select($sql);

 $id = DB::connection('mysql')->table('pu_product_demand_weekly')
 ->insertGetId(['sku'=>$Sku,'order_year'=>$OrderYear,'order_week'=>$OrderWeek,'load_year'=>$LoadYear,'load_week'=>$LoadWeek,
 'etd_year'=>$EtdYear,'etd_week'=>$EtdWeek,'eta_year'=>$EtaYear, 'eta_week'=>$EtaWeek,
 'selling_year'=>$SellingYear,'selling_week'=>$SellingWeek,
 'vendor_id'=>$VendorID,'lead_time'=>$LeadTime,'quantity'=>$Quantity,'missing'=>$Missing]);

}
//=======================================================================================================================
public function CheckWeekOff($id)
{//$id
   // $id = 12615 ;
    $Result = 0;
    // 0->không trùng, 1 -> tuần order trùng lịch nghỉ,
    // 2 tuần load hàng trung lịch nghỉ,3 tuần chuyển container lên tàu trùng lịch nghỉ (ETD)
    $Continue = true;
    $sql = " select the_year,the_week from pu_off_event_details " ;
    $ds = DB::connection('mysql')->select($sql);
    foreach( $ds as $d )
    {
      if($Continue)
        {
         $TheYear = $d->the_year;
         $TheWeek = $d->the_week;

         $sql = " select count(id) as MyCount from pu_product_demands
         where (id = $id)  and order_year = $TheYear and order_week = $TheWeek ";
         if($this->IsExist('mysql', $sql)> 0)
         {
            $Continue = false;
            $Result = 1;
            break ;
         }
         $sql = " select count(id) as MyCount from pu_product_demands
         where (id = $id) and (load_year = $TheYear and load_week = $TheWeek)";

         if($this->IsExist('mysql', $sql) > 0)
         {
          $Continue = false;
          $Result = 2;
          break;
         }
         $sql = " select count(id) as MyCount from pu_product_demands
         where (id = $id)
         and etd_year = $TheYear and etd_week = $TheWeek ";
        if($this->IsExist('mysql', $sql) > 0)
          {
           $Continue = false;
           $Result = 3;
           break;
          }
        }
    }
   return $Result;
}
//=======================================================================================================================
public function GetPipeLineInStage($sku,$FromDate,$ToDate){
// pipeline của kho y4a
$PipeLines = DB::connection('mysql_it')->select(
" select case when a.Quantity is null then 0 else a.Quantity end as Quantity from
( select sum(smcondt.quantity) as Quantity
from shipment sm inner join
shipment_containers smcon on sm.id =smcon.shipment_id  inner JOIN
shipment_containers_details  smcondt on smcon.id = smcondt.container_id inner JOIN
products prd on smcondt.product_id =prd.id
where  smcon.status in (10,11)
and  Date(smcon.stocking_date)  >= '$FromDate'
and  Date(smcon.stocking_date)  <= '$ToDate'
and prd.product_sku = '$sku'
and smcondt.quantity > 0

UNION

select sum(smcondt.quantity) as Quantity
from shipment sm inner join
shipment_containers smcon on sm.id =smcon.shipment_id  inner JOIN
shipment_containers_details  smcondt on smcon.id = smcondt.container_id inner JOIN
products prd on smcondt.product_id = prd.id
where  smcon.status in (1,8)
and  Date(smcon.stocking_date)  >= '$FromDate'
and  Date(smcon.stocking_date)  <= '$ToDate'
and prd.product_sku =  '$sku'
and smcondt.quantity > 0 )a where a.Quantity > 0 ");

// (10,11) -- trang thai cua container  la impported/ complete
// (1,8)   -- trang thai cua container la start up/pipeline
$this->PipeLineY4A = 0;
foreach( $PipeLines as $PipeLine ){
    $this->PipeLineY4A = $this->iif(is_null( $PipeLine->Quantity),0, $PipeLine->Quantity);
}
}
//=======================================================================================================================
// Tìm số Forecast của 3 kênh trong giai đoạn
public function GetForecastQuantityInstage( $sku, $StartSellingDate, $EndSellingDate, $NeedAdLoi)
{
    $Y4AStoreID = 46;
    $SalesChanelFBA = 9;
    $SalesChanelAVC_WH = 1;
    $SalesChanelAVC_DI = 3;

    // lấy LOI để xác định ngày dự kiến bán hết sky này của PO này  avc-wh
    $avcwh_level = DB::connection('mysql')->table('sal_lois')->where('sku',$sku)->value('avcwh_level');

    if($avcwh_level > 0 &&  $NeedAdLoi == true){// Nếu LOI> 0 thì gia tăng ngày kết thúc bán lên theo level tính bằng tuần
     $EndSellingDateOnAVC_WH = date('Y-m-d',strtotime( $EndSellingDate. '+'. $avcwh_level * 7 .'days'));
     // Tăng end selling date của FBA lên thêm 2 tuần
     $EndSellingDateOnFBA = date('Y-m-d',strtotime($EndSellingDate. '+ 14 days'));
    }else{
     $EndSellingDateOnAVC_WH =  $EndSellingDate;
     $EndSellingDateOnFBA =  $EndSellingDate;
    }
    // ---------------- Tính số FC của kho Y4A -------------------------------
    $ForecastY4As = DB::connection('mysql')->select(
    "select sum(fcdt.quantity) as quantity from sal_forecasts  fc INNER JOIN
    sal_forecast_details fcdt on fc.id = fcdt.sales_forecast_id
    where  date(fc.the_date) >=  date('$StartSellingDate')
    and date(fc.the_date) <=  date('$EndSellingDate')
    and fc.sku = '$sku'
    and fcdt.channel_id not in ($SalesChanelFBA ,$SalesChanelAVC_WH, $SalesChanelAVC_DI)");
    $this->ForeCastOnY4AInStage = 0;
    foreach( $ForecastY4As as $ForecastY4A ){
        $this->ForeCastOnY4AInStage =  $this->iif(is_null($ForecastY4A->quantity),0,$ForecastY4A->quantity);
    }
    // ---------------- Tính số FC của kho FBA -------------------------------

    $ForecastFBAs = DB::connection('mysql')->select(
    " select sum(fcdt.quantity) as quantity from sal_forecasts  fc
    INNER JOIN sal_forecast_details fcdt on fc.id = fcdt.sales_forecast_id
    where date(fc.the_date) >= date('$StartSellingDate')
    and date(fc.the_date) <= date('$EndSellingDateOnFBA')
    and fc.sku = '$sku'
    and fcdt.channel_id in ($SalesChanelFBA)");
    $this->ForeCastOnFBAInStage = 0;
    foreach( $ForecastFBAs as $ForecastFBA ){
    $this->ForeCastOnFBAInStage   =  $this->iif(is_null($ForecastFBA->quantity),0,$ForecastFBA->quantity);
    }

    // ---------------- Tính số FC của kho AVCWH -------------------------------
    $ForecastAVC_WHs = DB::connection('mysql')->select(
    " select sum(fcdt.quantity) as quantity from sal_forecasts  fc
    INNER JOIN sal_forecast_details fcdt on fc.id = fcdt.sales_forecast_id
    where  date(fc.the_date) >=  date('$StartSellingDate')
    and date(fc.the_date) <=  date('$EndSellingDateOnAVC_WH')
    and fc.sku = '$sku'
    and fcdt.channel_id in ($SalesChanelAVC_WH )");
    $this->ForeCastOnAVC_WHInStage = 0;
    foreach( $ForecastAVC_WHs as $ForecastAVC_WH ){
    $this->ForeCastOnAVC_WHInStage =  $this->iif(is_null($ForecastAVC_WH->quantity),0,$ForecastAVC_WH->quantity);
    }
    return  $this->ForeCastOnY4AInStage + $this->ForeCastOnFBAInStage+ $this->ForeCastOnAVC_WHInStage;
}
//=======================================================================================================================
public function index()
{
    // $sql = " select the_year as order_year , the_week as order_week,pu_po_estimates.vendor_id,
    // pu_vendors.vendor_name,pu_po_estimates.lead_time,
    // pu_po_estimates.expect_load_date, pu_po_estimates.expect_etd_date,
    // pu_po_estimates.expect_eta_date, pu_po_estimates.start_selling_date, pu_po_estimates.end_selling_date
    // from pu_po_estimates left join pu_vendors on pu_po_estimates.vendor_id = pu_vendors.id
    // order by the_year,the_week,pu_po_estimates.vendor_id,pu_po_estimates.lead_time " ;

    $sql = " select pu_po_estimates.id, the_year as order_year , the_week as order_week ,
    pu_vendors.vendor_name,pu_po_estimates.lead_time,pu_po_estimates.expect_load_date,
    pu_po_estimates.expect_etd_date, pu_po_estimates.expect_eta_date,
    pu_po_estimates.start_selling_date,pu_po_estimates.end_selling_date
    from pu_po_estimates left join pu_vendors on pu_po_estimates.vendor_id = pu_vendors.id
    order by the_year,the_week " ;

    $dsPuPlan = DB::connection('mysql')->select($sql);
    return view('fa.CashflowImportFile',compact('dsPuPlan'));
}
//=======================================================================================================================
public function CashflowChartDefault()
{
    $WeekNum = 8;
    $CashFlow= array();
    $ArrayType = array('column','column','column','column','column','column','column','spline');
    $ArrWeek = array('2020-33','2020-34','2020-35','2020-36','2020-37','2020-38','2020-39','2020-40');
    $ArrName = array('Begin Balance','Income','Outcome','Management Fee','Other Fee','Share Profit','Capital Invest','Average');
    $ArrData = array();
    $m = 0;
    $n = 0;
    $o = 0;
    for($i = 0 ; $i < $WeekNum ; $i++)
    {
        if($i==0){
          $ArrData[$i] =[700000,500000,150000,50000,25000,25000,15000,500000];
        }
        elseif($i==1){
         $ArrData[$i] =[30000,50000,150000,50000,25500,100000,150000,500000];
        }
        elseif($i==2) {
         $ArrData[$i] =[-60000,-10000,-150000,-50000,-2500,-1000,-15000,-500000];
        }
        elseif($i==6) {
         $ArrData[$i] =[60000,10000,150000,0,0,0,650000,0];
        }
        elseif($i==7) {
         $ArrData[$i] =[160000,210000,150000,0,0,0,650000,0];
        }
        else{
            $ArrData[$i] =[-20000,-10000,-15000,-50000,-2500,-1000,-15000,-12000];
        }
    }

    for($m=0;$m<$WeekNum;$m++)
    {
        for($n=0;$n<$WeekNum;$n++)
        {
            for($o=0;$o<$WeekNum;$o++)
            {
                $item = array(
                'type' => $ArrayType[$m],
                'name' => $ArrName[$n],
                'data' => $ArrData[$o]);
                if( $m == $n && $n == $o){ $CashFlow[] = $item;}
            }
            $o =0;
        }
        $n=0;
    }
   // dd($CashFlow);
  return view('fa.CashflowChart',compact('CashFlow','ArrWeek'));
}
//=======================================================================================================================
public function GetMonthFromDate($TheDate)
{
 return  date("m",strtotime($TheDate));
}
//=======================================================================================================================
public function GetWeekFromDate($TheDate)
{
 return  date("W",strtotime($TheDate));
}
//=======================================================================================================================
public function GetYearFromDate($TheDate)
{
 return  date("Y",strtotime($TheDate));
}
//=======================================================================================================================
public function GetFirstDateOfMonth($TheYear,$TheMonth)
{
 // $s = date("Y",strtotime($TheDate)) .'-' .  date("m",strtotime($TheDate)) .'-01';
  $s = $TheYear . '-'. $TheMonth .'-01';
  return date("Y-m-d",strtotime( $s));
}
//=======================================================================================================================
// Tính số tiền tồn đầu tháng
public function CreateCashBalance($TheYear,$TheMonth,$CashAccountID)
{

    $sql = " select id from fa_cash_accounts order by id ";
    $ds = DB::connection('mysql')->select($sql);
    foreach($ds as $d)
    {
     // Tìm tồn đầu tháng gần nhất

     $sql = " select the_year,the_month,balance_begin  from fa_cashflow_begin
     where id in (select max(id) from fa_cashflow_begin
     where  cash_account = $d->id and is_close = 1 ) " ;

     $d1s = DB::connection('mysql')->select($sql);
     foreach($d1s as $d1)
     {
        $LastYear  = $d1->the_year;
        $LastMonth = $d1->the_month;
        $LastBalance = $d1->balance_begin;
     }
     $FromDate =$this->GetFirstDateOfMonth($LastYear,$LastMonth); // Ngày đầu tiên của tháng có số tồn gần nhất
     $ToDate =$this->GetFirstDateOfMonth($TheYear,$TheMonth);// Ngày đầu của tháng muốn tính số tồn
     //$ToDate =$this->MoveDate($ToDate,-1);

     // Tìm thu chi từ đầu tháng gần nhất đến trước ngày đầu tháng đang muốn tìm tồn đầu
     $sql = " select sum(case when type =1 then amount else  -amount end) as amount
     from fa_cashflow_transactions where action_date >= '$FromDate'
     and action_date < '$ToDate' and cash_account = $d->id ";

     $d2s = DB::connection('mysql')->select($sql);
     foreach($d2s as $d2)  { $LastBalance  = $LastBalance  +  $d2->amount; }

     " delete from fa_cashflow_begin where the_year = $TheYear and the_month = $TheMonth
      and cash_account = $d->id ";
     DB::connection('mysql')->select($sql);

     DB::connection('mysql')->table('fa_cashflow_begin')->insert(
     ['cash_account'=>$d->id,'the_year'=>$TheYear,'the_month'=>$TheMonth,
     'balance_begin'=>$LastBalance ,'is_close'=>0]);
    }
}
//=======================================================================================================================
public function HasBeginBalance($TheYear,$TheMonth,$CashAccountID)
{
 $Tmp = 0;
 $sql = " select count(id) as MyCount from fa_cashflow_begin
 where the_year = $TheYear and the_month = $TheMonth ";
 if($CashAccountID <> 0) {
    $sql =  $sql . " and cash_account = $CashAccountID ";
 }
 $ds = DB::connection('mysql')->select($sql);
 foreach($ds as $d) { $Tmp  = $d->MyCount; }
 // xem tổng thì phải có 2 account
 //print_r('The Year: ' . $TheYear. ' The Month: ' . $TheMonth . ' Cash Account: ' . $CashAccountID . ' TMP: '. $Tmp );
// print_r('<br>');
if($Tmp ==2) { return true;}
elseif($CashAccountID <> 0 && $Tmp ==1){ return true;}
else{return false;}

}
//=======================================================================================================================
// Lấy số tồn đầu tuần
public function GetCashBalanceBeginOfWeek($TheYear,$TheWeek,$CashAccountID)
{
    $CashBeginMonth =0;
    $BeginDateOfWeek = $this->GetFirstDateOfWeek($TheYear,$TheWeek);
    $EndDateOfWeek = $this->GetLastDateOfWeek($TheYear,$TheWeek);

    $TheMonth = $this->GetMonthFromDate($BeginDateOfWeek);
    $TheYear = $this->GetYearFromDate($BeginDateOfWeek);
    $BeginDateOfMonth = $this->GetFirstDateOfMonth($TheYear,$TheMonth);

    if(!$this->HasBeginBalance($TheYear,$TheMonth,$CashAccountID))
    {
    //   print_r('Chưa tồn tại số tồn đầu');
    //   print_r('<br>');
    //   print_r('The Year: ' . $TheYear . ' The Month: ' . $TheMonth. ' Cash Acount: ' . $CashAccountID  );
    //   print_r('<br>');
      $this->CreateCashBalance($TheYear,$TheMonth,$CashAccountID);
    }

    // Lấy số tồn đầu tháng
    $sql = " select sum(balance_begin) as balance_begin from fa_cashflow_begin
    where the_year = $TheYear and the_month = $TheMonth ";
    if($CashAccountID<>0) { $sql = $sql . " and cash_account = $CashAccountID "; }

    $ds=  DB::connection('mysql')->select($sql);
    foreach($ds as $d){
        $CashBeginMonth = $this->iif(is_null($d->balance_begin),0,$d->balance_begin);
    }

    // Lấy số thu chi từ ngày đầu tháng đến trước ngày đầu tuần
    $sql = " select sum(case when type =1 then amount else -amount end) as amount
    from fa_cashflow_transactions
    where date(action_date) >= '$BeginDateOfMonth'and date(action_date) < '$BeginDateOfWeek'";

    if($CashAccountID<>0) { $sql = $sql . " and cash_account = $CashAccountID "; }

    $ds=  DB::connection('mysql')->select($sql);
    foreach($ds as $d){$InOut = $this->iif(is_null($d->amount),0,$d->amount); }

    return  $CashBeginMonth + $InOut;
}
//=======================================================================================================================
public function GetIncomeOfWeek($TheYear,$TheWeek,$IncomeOutComeID,$CashAccount )
{
    $BeginDateOfWeek = $this->GetFirstDateOfWeek($TheYear,$TheWeek);
    $EndDateOfWeek = $this->GetLastDateOfWeek($TheYear,$TheWeek);
    $TheMonth = $this->GetMonthFromDate($BeginDateOfWeek);
    $TheYear = $this->GetYearFromDate($BeginDateOfWeek);

    $BeginDateOfMonth = $this->GetFirstDateOfMonth($TheYear,$TheMonth);

    if($IncomeOutComeID <> 0){
     $sql = " select sum(amount) as amount from fa_cashflow_transactions
     where income_expensive_id = $IncomeOutComeID
     and date(action_date) >= '$BeginDateOfWeek'  and date(action_date) <= '$EndDateOfWeek'";
    }else
    {
     $sql = " select sum(tr.amount) as amount from fa_cashflow_transactions tr
     inner join fa_income_expensives ie on tr.income_expensive_id	 = ie.id
     where ie.type_id = 1
     and date(tr.action_date) >= '$BeginDateOfWeek'  and date(tr.action_date) <= '$EndDateOfWeek'";
    }

    if($CashAccount <> 0){ $sql  =  $sql . " and cash_account = $CashAccount ";}

    $ds=  DB::connection('mysql')->select($sql);
    foreach($ds as $d){ $Result = $this->iif(is_null($d->amount),0,$d->amount); }

    return  $Result ;
}
//=======================================================================================================================
public function GetOutcomeOfWeek($TheYear,$TheWeek,$IncomeOutComeID,$CashAccount)
{
    $BeginDateOfWeek = $this->GetFirstDateOfWeek($TheYear,$TheWeek);
    $EndDateOfWeek = $this->GetLastDateOfWeek($TheYear,$TheWeek);

    $TheMonth = $this->GetMonthFromDate($BeginDateOfWeek);
    $TheYear = $this->GetYearFromDate($BeginDateOfWeek);

    if($IncomeOutComeID <> 0){
        $sql = " select sum(amount) as amount from fa_cashflow_transactions
        where income_expensive_id = $IncomeOutComeID  and 	type = 2
        and date(action_date) >= '$BeginDateOfWeek'  and date(action_date) <= '$EndDateOfWeek'";
    }else
    {
        $sql = " select sum(tr.amount) as amount from fa_cashflow_transactions tr
        where tr.type = 2
        and date(tr.action_date) >= '$BeginDateOfWeek'  and date(tr.action_date) <= '$EndDateOfWeek'";
    }

    if($CashAccount<> 0){$sql  = $sql  . " and cash_account = $CashAccount ";}

    $ds=  DB::connection('mysql')->select($sql);
    foreach($ds as $d){ $Result = $this->iif(is_null($d->amount),0,$d->amount);}
    return  $Result ;
}
//=======================================================================================================================
public function GetIncomeOutcome($TheYear,$TheWeek,$IncomeOutComeID,$CashAccount)
{
    $Result = 0;
    $CashBegin = 0;
    $Income = 0;
    $Outcome = 0;

    if ($IncomeOutComeID == 8) // Tồn đầu
     {
        $Result = $this->GetCashBalanceBeginOfWeek($TheYear,$TheWeek,$CashAccount);
     }elseif($IncomeOutComeID == 0)// Avare
     {
        $CashBegin = $this->GetCashBalanceBeginOfWeek($TheYear,$TheWeek,$CashAccount);
        // print_r('CashBegin '.  $CashBegin);
        // print_r('<br>');

        $Income = $this->GetIncomeOfWeek($TheYear,$TheWeek,$IncomeOutComeID,$CashAccount);
       // print_r('Income '.  $Income);
       // print_r('<br>');
        $Outcome =  $this->GetOutcomeOfWeek($TheYear,$TheWeek,$IncomeOutComeID,$CashAccount);
       // print_r('Out Come '.  $Outcome);
       // print_r('<br>');
        $Result = $CashBegin + $Income - $Outcome;
     }
     elseif($IncomeOutComeID >= 9 && $IncomeOutComeID <= 11 ) // Income - >Số dương
     {
        $Result = $this->GetIncomeOfWeek($TheYear,$TheWeek,$IncomeOutComeID,$CashAccount);
     }
     else // Outcome->Số âm
     {
        $Result = - $this->GetOutcomeOfWeek($TheYear,$TheWeek,$IncomeOutComeID,$CashAccount);
     }

    return round($Result,0);
}
//=======================================================================================================================
public function GetEstimateRevenueNew($sChannel,$TheYear,$TheWeek)
{
    $FromDate = $this->GetFirstDateOfWeek($TheYear,$TheWeek);
    $ToDate = $this->GetLastDateOfWeek($TheYear,$TheWeek);
    // Tìm số FC
    $AllFC =0;// Chứa tổng số FC trên tất cả các kênh
    $sql = " select sum(fcdt.quantity) as quantity
    from  sal_forecasts fc inner join sal_forecast_details fcdt on fc.id = fcdt.sales_forecast_id
    where fc.the_date >= '$FromDate' and  fc.the_date <= '$ToDate'";
    $ds= DB::connection('mysql')->select($sql);
    foreach($ds as $d){$AllFC =  $this->iif(is_null($d->quantity),0,$d->quantity);}

   // print_r('Tổng số FC từ ngày ' . $FromDate . ' đến ngày ' . $ToDate .' là: '. $AllFC );
   // print_r('<br>');

     // Tổng lượng hàng thiếu cho tất cả các kênh
    $sql = " select sum(a.missing) as  missing FROM
    (
    select case when podt.quantity is null then 0 else podt.quantity end as missing
    from pu_po_estimates  po inner join  pu_po_estimate_details podt on po.id = podt.po_estimate_id
    where  year(po.start_selling_date) = $TheYear
    and  week(po.start_selling_date) = $TheWeek
    and po.status_id = 0
    union  all
    select sum(-missing) as missing from pu_product_demand_weekly
    where 	selling_year  = $TheYear and selling_week = $TheWeek
    ) a ";


     $ds = DB::connection('mysql')->select($sql);
     foreach($ds as $d){$Mising = $this->iif(is_null($d->missing),0,$d->missing);}
     // Tỷ lệ thiếu
     if($Mising>0){
        if($Mising >$AllFC) {$RateMissing = 0;}
        else{$RateMissing = 1- $Mising/$AllFC;}
        }
     else{$RateMissing=1;}

    // Doanh số bán hàng ước tính trên số FC
    // * với tỷ lệ thiếu hàng * với giá bán
    // trên chuỗi kênh đưa vào (sChannel)
    $Amount = 0;
    if($RateMissing>0){
        $sql = " select sum(fcdt.quantity * $RateMissing * GetSellingPrice(fc.sku,fcdt.channel_id)) as amount
        from  sal_forecasts fc inner join sal_forecast_details fcdt on fc.id = fcdt.sales_forecast_id
        where fc.the_date >= '$FromDate' and  fc.the_date <= '$ToDate'  and  fcdt.channel_id in $sChannel ";
        $ds= DB::connection('mysql')->select($sql);
        foreach($ds as $d){$Amount =  $this->iif(is_null($d->amount),0,$d->amount);}
        if($TheYear ==2020 && $TheWeek == 44 && $sChannel== '(6,7,8)')
        {
            print_r('Tiền ước tính thu: ' . $Amount .  'Tổng số hàng thiếu: ' . $Mising );
            print_r('<br>');
        }
    }
    return  $Amount;
}
//=======================================================================================================================
public function GetEstimateRevenue($sChannel,$FromDate,$ToDate)
{   //$sChannel,$FromDate,$ToDate
    // $sChannel= '(1,2)';
    // $FromDate = '2020-09-07';
    // $ToDate =   '2020-09-13';
    $Amount = 0;
   // Ví dụ sku single: AX59, sku combo: DPZW
   // Tìm doanh thu dựa trên số liệu hàng có thể có để cung cấp trên những kênh đó
   $sql = " select p.product_sku as sku from prd_product p
   where p.sell_type in (1,4) and p.company_id = 2
   union
   select p2.product_sku as sku from prd_product p
   inner join  productcombo prdc on p.id = prdc.product_id
   inner join prd_product  p2 on prdc.child_id = p2.id
   where p.sell_type in $sChannel and p.company_id = 2  ";

   //and p.product_sku ='AX59'
   $this->CurrentDate = date('Y-m-d', time());


   $ds= DB::connection('mysql')->select($sql);
   foreach($ds as $d)
   {
    $this->BalanceOnY4A = 0;
    $this->BalanceOnFBA = 0;
    $this->BalanceOnAVC_WH = 0;

    $this->GetBalanceBeginningOnTheDate($d->sku,$this->CurrentDate );
    $this->UpdateLogicBalanceStore();
    // print_r('Số tồn hiện tại: '.  $this->BalanceOnY4A);
    // print_r('<br>');

    $NeeAddLoi = false;
    $Forecast = $this->GetForecastQuantityInstage($d->sku,$this->CurrentDate,$FromDate,$NeeAddLoi);
    $PipeLine = $this->GetPipeLineQuantityInstage($d->sku,$this->CurrentDate,$FromDate);

    // print_r('Số FC từ ngày hiện tại đến ngày: ' . $FromDate .' là : '.  $Forecast);
    // print_r('<br>');
    // print_r('Số Pipeline tư hiện tại đến :' . $FromDate.' là: '. $PipeLine);
    // print_r('<br>');

    // Tổng số tồn
    $this->BalanceOnY4A = max($this->BalanceOnY4A -  $Forecast + $PipeLine,0) ;
    // print_r('Số tồn đầu ngày'. $FromDate .'là: '.  $this->BalanceOnY4A);
    // print_r('<br>');

    // Tỷ lệ tổng FC so với tỷ lệ FC của các kênh có trong chuỗi sChannel là
    // Tìm doanh thu dựa trên số liệu FC
    $AllFC =0;// Chứa tổng số FC trên tất cả các kênh
    $sql = " select sum(fcdt.quantity) as quantity
    from  sal_forecasts fc inner join sal_forecast_details fcdt on fc.id = fcdt.sales_forecast_id
    where fc.the_date >= '$FromDate' and  fc.the_date <= '$ToDate' and fc.sku = '$d->sku' ";
    $d1s= DB::connection('mysql')->select($sql);
    foreach($d1s as $d1){$AllFC =  $this->iif(is_null($d1->quantity),0,$d1->quantity);}

    //print_r('Tổng số FC từ ngày ' . $FromDate . ' đến ngày ' . $ToDate .' là: '. $AllFC );
    //print_r('<br>');

    $FC = 0;// Tổng số FC trên chuỗi kênh đưa vào (sChannel)
    $sql = " select sum(fcdt.quantity) as quantity
    from  sal_forecasts fc inner join sal_forecast_details fcdt on fc.id = fcdt.sales_forecast_id
    where fc.the_date >= '$FromDate' and  fc.the_date <= '$ToDate' and fc.sku = '$d->sku'
    and  fcdt.channel_id in $sChannel ";
    $d1s= DB::connection('mysql')->select($sql);
    foreach($d1s as $d1){$FC =  $this->iif(is_null($d1->quantity),0,$d1->quantity);}

    //print_r('Tổng số FC theo chuỗi kênh' . $sChannel .  'từ ngày ' . $FromDate . ' đến ngày ' . $ToDate .' là: '. $FC);
    //print_r('<br>');

    $sChanelBalance = 0;// Số lượng tồn kho tương ứng với chuỗi kênh đã đưa vào (sChannel)

    if($AllFC > 0){ $sChanelBalance =  $this->BalanceOnY4A * $FC / $AllFC ;}
    else{$sChanelBalance = 0;}

    // print_r('Tổng số tồn theo chuỗi kênh '. $sChannel . 'theo tỷ lệ = '.$sChanelBalance );
    // print_r('<br>');

    $sChanelBalance  = min($sChanelBalance ,$FC); // Bằng min FC của giai đoạn đó hoặc Tồn kho tương ứng của các kênh đó
    // Tỷ lệ giữa tồn kho ước tính của các kênh input và dự báo bán
    if($FC>0)
    {
      $Rate = $sChanelBalance/$FC;
      $Amount = $Amount  + $this->CalAmount($d->sku,$Rate,$FromDate,$ToDate,$sChannel);
    }
   }

 //print_r('Tổng doanh thu theo chuỗi kênh'. $sChannel .'trong khoảng thời gian từ ngày ' . $FromDate . ' đến ngày ' . $ToDate . 'là :'. $Amount );
 //print_r('<br>');

 return  $Amount;
}
//=======================================================================================================================
public function CalAmount($Sku,$Rate,$FromDate,$ToDate,$sChannel)
{
    $Amount = 0;
    $sql = " select sum(fcdt.quantity * GetSellingPrice('$Sku',fcdt.channel_id)* $Rate ) as amount,
    fcdt.channel_id
    from  sal_forecasts fc inner join sal_forecast_details fcdt on fc.id = fcdt.sales_forecast_id
    where fc.the_date >= '$FromDate' and  fc.the_date <= '$ToDate' and fc.sku = '$Sku'
    and  fcdt.channel_id in $sChannel group by fcdt.channel_id ";
    $ds= DB::connection('mysql')->select($sql);
    foreach($ds as $d) { $Amount =  $Amount +  $this->iif(is_null($d->amount),0,$d->amount); }
   return $Amount ;
}
//=======================================================================================================================
public function CreateTransactionIncomeFromSellingGoods($TheYear,$TheWeek,$CashAccount)
{
//  print_r('Năm: '.$TheYear. ' Tuần' .  $TheWeek .' đang xét' );
//  print_r('<br>');
 $Index = $this->GetIndex($TheYear,$TheWeek);
//  print_r(' Index tương ứng : '.$Index );
//  print_r('<br>');

 $Amount = 0;
 $FromDate = $this->GetFirstDateOfWeek($TheYear,$TheWeek);
 $OrizinFromDate = $FromDate;
 $ToDate = $this->GetLastDateOfWeek($TheYear,$TheWeek);
 $TheMonth = $this->GetMonthFromDate($FromDate);
 $TheYear = $this->GetYearFromDate($FromDate);

// print_r('FromDate: '.$FromDate. ' Todate' .  $ToDate );
// print_r('<br>');

 // Tính tiền dự kiến thu được từ kênh EBAY,Craiglsit, Website (6,7,8)
 // 1. Tìm trong file thu tiền thực tế
 $sql = " select sum(amount) as amount from fa_cashflow_transactions
 where is_estimate = 0 and action_date >='$FromDate' and  action_date <='$ToDate'
 and sales_channel_id in (6,7,8) ";

 $ds= DB::connection('mysql')->select($sql);
 foreach($ds as $d){$Amount1 =  $this->iif(is_null($d->amount),0,$d->amount) ;}
 // 2. Tìm trong PL report
 if($Amount1 ==0)
 {
  $sql = " select sum(amount) as amount from fa_selling_monthly_detail
  inner join prd_hmd_products on  fa_selling_monthly_detail.sku = prd_hmd_products.sku
  where sales_channel in (6,7,8) and date(invoice_date) >= '$FromDate'
  and date(invoice_date) <= '$ToDate' ";
  $ds= DB::connection('mysql')->select($sql);
  foreach($ds as $d){$Amount1 =  $this->iif(is_null($d->amount),0,$d->amount) ;}
 }
// 3. Tìm trong FC kết hợp với số hàng thiếu để ước tính doanh thu
if($Amount1 ==0)
 {
    $sChannel= '(6,7,8)';
    //$Amount1 = $this->GetEstimateRevenue($sChannel,$FromDate,$ToDate);
    $Amount1 = $this->GetEstimateRevenueNew($sChannel,$TheYear,$TheWeek);
 }
// Tính tiền dự kiến thu được từ kênh Walmart DSV(4)
//$FromDate = $this->MoveDate($FromDate,-7);
//$ToDate = $this->MoveDate($ToDate,-7);

//  if($Index>1){
//   $Index--;// Giảm một tuần
//   $TheYear = $this->ArrYear[$Index];
//   $TheWeek = $this->ArrWeek[$Index];
//   $FromDate = $this->GetFirstDateOfWeek($TheYear,$TheWeek);
//   $ToDate = $this->GetLastDateOfWeek($TheYear,$TheWeek);
//  }

// 1. Tìm trong file thu tiền thực tế
$sql = " select sum(amount) as amount from fa_cashflow_transactions
where is_estimate = 0 and action_date >='$FromDate' and  action_date <='$ToDate'
and sales_channel_id in (4) ";
$ds= DB::connection('mysql')->select($sql);
foreach($ds as $d){$Amount2 =  $this->iif(is_null($d->amount),0,$d->amount) ;}
// 2. Tìm trong PL report
if($Amount2 == 0)
{
 $sql = " select sum(amount) as amount from fa_selling_monthly_detail
 inner join prd_hmd_products on  fa_selling_monthly_detail.sku = prd_hmd_products.sku
 where sales_channel in (4) and date(invoice_date) >= '$FromDate'
 and date(invoice_date) <= '$ToDate' ";
 $ds= DB::connection('mysql')->select($sql);
 foreach($ds as $d){$Amount2 =  $this->iif(is_null($d->amount),0,$d->amount) ;}
}
// 3. Tìm trong data FC
if($Amount2 ==0)
 {
    $sChannel= '(4)';
    //$Amount2 = $this->GetEstimateRevenue($sChannel,$FromDate,$ToDate);
    $Amount2 = $this->GetEstimateRevenueNew($sChannel,$TheYear,$TheWeek);
 }
    // Tính tiền dự kiến thu được từ kênh Walmart MKP, FBA,FBM (5,9,10),
    //$FromDate = $this->MoveDate( $FromDate,-7);
    //$ToDate = $this->MoveDate( $ToDate,-7);

    // if($Index>1){
    //  $Index--;// Giảm một tuần
    //  $TheYear = $this->ArrYear[$Index];
    //  $TheWeek = $this->ArrWeek[$Index];
    //  $FromDate = $this->GetFirstDateOfWeek($TheYear,$TheWeek);
    //  $ToDate = $this->GetLastDateOfWeek($TheYear,$TheWeek);
    // }
    // 1. Tìm trong file thu tiền thực tế
    $sql = " select sum(amount) as amount from fa_cashflow_transactions
    where is_estimate = 0 and action_date >='$FromDate' and  action_date <='$ToDate'
    and sales_channel_id in (5,9,10) ";
    $ds= DB::connection('mysql')->select($sql);
    foreach($ds as $d){$Amount3 =  $this->iif(is_null($d->amount),0,$d->amount) ;}
    // 2. Tìm trong PL report
    if($Amount3 ==0)
    {
     $sql = " select sum(amount) as amount from fa_selling_monthly_detail
     inner join prd_hmd_products on  fa_selling_monthly_detail.sku = prd_hmd_products.sku
     where sales_channel in (5,9,10) and date(invoice_date) >= '$FromDate'
     and date(invoice_date) <= '$ToDate' ";
     $ds= DB::connection('mysql')->select($sql);
     foreach($ds as $d){$Amount3 =  $this->iif(is_null($d->amount),0,$d->amount) ;}
    }
    // 3. Tìm trong data FC
    if($Amount3 == 0)
    {
        $sChannel="(5,9,10)";
        //$Amount3 =  $this->GetEstimateRevenue($sChannel,$FromDate,$ToDate);
        $Amount3 = $this->GetEstimateRevenueNew($sChannel,$TheYear,$TheWeek);
    }

    // Tính tiền dự kiến thu được từ kênh AVC-WH, AVC-DS (1,2),
    // Dịch ngày 60 - 14 = 46
    // $FromDate = $this->MoveDate( $FromDate,-46);
    // $ToDate = $this->MoveDate( $ToDate,-46);
    // if($Index>6){
    //  $Index = $Index -6;// Giảm thêm 7 tuần tương đương 49 ngày
    //  $TheYear = $this->ArrYear[$Index];
    //  $TheWeek = $this->ArrWeek[$Index];
    //  $FromDate = $this->GetFirstDateOfWeek($TheYear,$TheWeek);
    //  $ToDate = $this->GetLastDateOfWeek($TheYear,$TheWeek);
    // }
    // 1. Tìm trong file thu tiền thực tế
    $sql = " select sum(amount) as amount from fa_cashflow_transactions
    where is_estimate = 0 and action_date >='$FromDate' and  action_date <='$ToDate'
    and sales_channel_id in (1,2)";
    $ds= DB::connection('mysql')->select($sql);
    foreach($ds as $d){$Amount4 =  $this->iif(is_null($d->amount),0,$d->amount) ;}
    // 2. Tìm trong PL report
    if($Amount4 ==0)
    {
        $sql = " select sum(amount) as amount from fa_selling_monthly_detail
        inner join prd_hmd_products on  fa_selling_monthly_detail.sku = prd_hmd_products.sku
        where sales_channel in (1,2) and date(invoice_date) >= '$FromDate'
        and date(invoice_date) <= '$ToDate' ";
        $ds= DB::connection('mysql')->select($sql);
        foreach($ds as $d){$Amount4 =  $this->iif(is_null($d->amount),0,$d->amount) ;}
    }

     // 3. Tìm trong data FC
     if($Amount4 ==0)
     {
        $sChannel="(1,2)";
        //$Amount4 =  $this->GetEstimateRevenue($sChannel,$FromDate,$ToDate);
        $Amount4 = $this->GetEstimateRevenueNew($sChannel,$TheYear,$TheWeek);
     }
    $Amount = round($Amount1 + $Amount2 + $Amount3 + $Amount4 ,0);
    if($Amount > 0 ){
     // tiền thu được từ bán hàng luôn chuyển vào accoutn bên us
     DB::connection('mysql')->table('fa_cashflow_transactions')->insert(
     ['income_expensive_id'=>9,'action_date'=>$OrizinFromDate ,'amount'=>$Amount,
     'des'=>'Selling Goods','is_estimate'=>1,'type'=>1,'cash_account'=>1]);
    }
}
//=======================================================================================================================
public function CreateTransactionEstimate($TheYear,$TheWeek,$CashAccount)
{
  $sql = " select id from fa_income_expensives order by the_order ";

//   print_r('SQL : '. $sql );
//   print_r('<br>');

  $ds = DB::connection('mysql')->select($sql);
  $i=0;
  foreach($ds as $d)
  {
   if($d->id == 9)
     {
      // Income from Selling Good dựa trên:
      // 1 thu tiền thực tế từ,2 thu từ PL Report, 3 số bán giả định data FC
      //print_r('id:'.$d->id );
      // print_r('<br>');
      $this->CreateTransactionIncomeFromSellingGoods($TheYear,$TheWeek,$CashAccount);
     }elseif($d->id == 1) // Chi phí mua hàng dựa trên PU Plan
     {
    //   print_r('Tính tiền mùa hàng cho tuần: '.$TheYear . '-'. $TheWeek );
    //   print_r('<br>');
      $this->CreateTransactionBuyingGoods($TheYear,$TheWeek,$CashAccount);
     }elseif($d->id == 7)//  Tạm thời cái này tính luôn MKT,Logistic, Retail shiping fee
     {
      $this->CreateTransactionSellingFee($TheYear,$TheWeek,$CashAccount);
     }elseif($d->id == 4) // Chi phí quản lý, lương
     {
      $this->CreateTransactionManagementFee($TheYear,$TheWeek,$CashAccount);
     }
     $i++;
  }// Foreach
}
//=======================================================================================================================
public function CreateTransactionSellingFee($TheYear ,$TheWeek,$CashAccount)
{
    //$TheYear = 2020;
    //$TheWeek = 35;
    $LogisticFee = 0;
    $SellingFee = 0;
    $MKTFee = 0.0;
    $RetailShippingFee = 0;
    $MKTFeeRate = 8;
    $RetailShippingRate = 30;
    $LogisticRate = 12;

    $PaymentDate = $this->GetFirstDateOfWeek($TheYear ,$TheWeek);
    $FromDate = $PaymentDate;
    $ToDate = $this->GetLastDateOfWeek($TheYear ,$TheWeek);

    $sql = " select sku, channel_id,referal_fee,fob_price, sum(quantity) as quantity
    from sal_forecasts fc inner join sal_forecast_details fcdt on fc.id = fcdt.sales_forecast_id
    inner join sal_channels cn on fcdt.channel_id = cn.id
    inner join prd_product p on fc.sku = p.product_sku
    where date(the_date) >= '$FromDate' and date(the_date) <= '$ToDate'
    group by  sku, channel_id, referal_fee,fob_price ";

    $ds = DB::connection('mysql')->select( $sql);
    foreach($ds as  $d)
    {
        $SellingFee  = $SellingFee + $d->quantity * $this->GetLastSellingPrice($d->sku,$d->channel_id)/100 * $d->referal_fee;
        $MKTFee = $MKTFee +  $d->quantity * $this->GetLastSellingPrice($d->sku,$d->channel_id)/100 * $MKTFeeRate;
        $LogisticFee = $LogisticFee +  $d->quantity * $d->fob_price/100 * $LogisticRate;

        if($d->channel_id == 6 || $d->channel_id == 8 || $d->channel_id == 10)
        {
            $RetailShippingFee = $RetailShippingFee + $d->quantity * $this->GetLastSellingPrice($d->sku,$d->channel_id)/100 *  $RetailShippingRate;
        }
    }

    //  Selling fee
    if($SellingFee>0){
     DB::connection('mysql')->table('fa_cashflow_transactions')->insert(
     ['income_expensive_id'=>7 ,'action_date'=>$PaymentDate ,'amount'=>$SellingFee,
     'des'=>'Selling Fee','is_estimate'=>1,'type'=>2,'cash_account'=>1 ]);
    }
    // print_r('MKTFee: ' . $MKTFee);
    // print_r('<br>' );

    // MKT Fee
    if($MKTFee>0){
     DB::connection('mysql')->table('fa_cashflow_transactions')->insert(
     ['income_expensive_id'=>6 ,'action_date'=>$PaymentDate ,'amount'=>$MKTFee,
     'des'=>'MKT Fee','is_estimate'=>1,'type'=>2,'cash_account'=>1]);
    }
    // Retail ShippingFee
    if($RetailShippingFee>0){
     DB::connection('mysql')->table('fa_cashflow_transactions')->insert(
     ['income_expensive_id'=>3 ,'action_date'=>$PaymentDate ,'amount'=>$RetailShippingFee,
     'des'=>'Retail Shipping Fee','is_estimate'=>1,'type'=>2,'cash_account'=>1]);
    }
}
//=======================================================================================================================
public function GetLastSellingPrice($Sku,$ChannelID)
{
    $Price = 0;
    $sql = " select price from fa_selling_monthly_detail where sku = '$Sku' and sales_channel = $ChannelID
    ORDER BY id DESC LIMIT 1 ";
    $ds = DB::connection('mysql')->select( $sql);
    foreach($ds as  $d){$Price= $d->price;}
    return $Price;
}
//=======================================================================================================================
public function CreateTransactionBuyingGoods($TheYear ,$TheWeek,$CashAccount)
{//$TheYear ,$TheWeek,$CashAccount

    // Tính các chi phí mua hàng, ngoại trừ thuế
    $sql = " select vd.id,vd.nation_id,order_date,expect_etd_date,expect_eta_date, sum(fob_price * quantity) as amount
    from pu_po_estimates po
    inner join pu_po_estimate_details podt on po.id =podt.po_estimate_id
    inner join pu_vendors vd on  po.vendor_id = vd.id
    where po.status_id = 1 and po.the_year = $TheYear and po.the_week = $TheWeek
    group by vd.id,vd.nation_id,order_date,expect_etd_date,expect_eta_date ";

    $ds = DB::connection('mysql')->select( $sql);
    foreach($ds as  $d){
        if( $d->amount >0 )
        {
            $VendorID = $d->id;
            $NationID = $d->nation_id;
            $OrderDate = $d->order_date;
            $ETD = $d->expect_etd_date;
            $ETA = $d->expect_eta_date;
            $Amount = $d->amount;
            $Des =  $TheYear. '-'. $TheWeek ;

            $this->CreateTransactionBuyingGoodsForVendor($VendorID,$NationID,$OrderDate,$ETD, $ETA ,$Amount,$Des,$CashAccount);
        }// if amount
    }// fo

    $sql = " select expect_eta_date, sum(podt.fob_price * quantity*p.tax_rate) as duty
    from pu_po_estimates po
    inner join pu_po_estimate_details podt on po.id =podt.po_estimate_id
    inner join  prd_product  p on podt.sku = p.product_sku
    where  po.the_year = $TheYear and po.the_week =  $TheWeek  group by expect_eta_date " ;
    $ds = DB::connection('mysql')->select( $sql);
    foreach($ds as  $d)
    {
        if( $d->duty > 0 )
        {
          DB::connection('mysql')->table('fa_cashflow_transactions')->insert(
         ['income_expensive_id'=>16 ,'action_date'=>$d->expect_eta_date ,'amount'=>$d->duty,
         'des'=>'Duty','is_estimate'=>1,'cash_account'=>1,'type'=>2]);
        }
    }

}
//=======================================================================================================================
public function CreateTransactionBuyingGoodsForVendor($VendorID,$NationID,$OrderDate,$ETD,$ETA,$Amount,$Des,$CashAccount)
{
    $LocalFee = 0;
    $LocalRate = 2;

    $FreightFee = 0;
    $FreightRate = 6;

    $USBrokerFee = 0;
    $USBrokerRate = 4;

    $NewDes = '';
   // print_r('Amount: ' .$Amount );
   // print_r('<br>');
   // $VendorID,$NationID,$OrderDate,$ETD,$ETA,$Amount,$Des,$CashAccount

    // Tiền $LocalFee
    $LocalFee =round($Amount/100 * $LocalRate,0);
    if($NationID ==3){$CashAccount = 1; }// La US -> Cash Acount = 1
    if($NationID ==1){$CashAccount=2; }// La VN->  Cash Acount == 2

    //$TheDate = $this->MoveDate($ETD,18);// Tiền local trả sau ETD 18 ngày
    $TheDate =$OrderDate;
    if($LocalFee >0){
     DB::connection('mysql')->table('fa_cashflow_transactions')->insert(
     ['income_expensive_id'=>2 ,'action_date'=>$TheDate,'for_to_date'=>$TheDate ,
     'amount'=>$LocalFee,'des'=>'Local Fee','is_estimate'=>1,'type'=>2,'cash_account'=>$CashAccount]);
    }
    // Tiền $FreightFee
    $FreightFee =round($Amount/100 * $FreightRate,0);
    //$TheDate = $this->MoveDate($ETA, 7);// Tiền FreightFee sau ETA 7 ngày,charge vào tk bên US
    $TheDate =$OrderDate;
    if($FreightFee>0){
     DB::connection('mysql')->table('fa_cashflow_transactions')->insert(
     ['income_expensive_id'=>2 ,'action_date'=>$TheDate ,'amount'=> $FreightFee,
     'des'=>'FreightFee','is_estimate'=>1,'type'=>2,'cash_account'=>1]);
    }
    // Tiền $USBrokerFee
    $USBrokerFee =round($Amount/100 * $USBrokerRate,0);
    //$TheDate = $this->MoveDate($ETA, 7);// Tiền USBrokerFee sau ETA 7 ngày, charge vào tk bên US
    $TheDate =$OrderDate;
    if($USBrokerFee>0) {
     DB::connection('mysql')->table('fa_cashflow_transactions')->insert(
     ['income_expensive_id'=>2 ,'action_date'=>$TheDate ,'amount'=>$USBrokerFee,
     'des'=>'USBrokerFee','is_estimate'=>1,'type'=>2,'cash_account'=>1]);
    }

    // Dựa vào rule thanh toán tiền mua hàng của mỗi vendor để xác định thời điểm trả tiền
/*
    $sql = " select milestone_type ,delta_day,pay_type,percent from pu_vendor_payment_rule where vendor_id  = $VendorID ";
    $ds = DB::connection('mysql')->select( $sql);
    foreach($ds as  $d)
    {
     if($d->milestone_type == 1)
     {
        //$TheDate = $this->MoveDate($OrderDate, $d->delta_day);
        $TheDate =$OrderDate;
        $Pay = round($Amount/100*$d->percent,0);
        $NewDes= $Des . ' Deposit';
        if( $Pay > 0)
        {
         DB::connection('mysql')->table('fa_cashflow_transactions')->insert(
         ['income_expensive_id'=>1 ,'action_date'=>$TheDate,'amount'=>$Pay,
         'des'=>$NewDes ,'is_estimate'=>1,'type'=>2,'cash_account'=>$CashAccount]);
        }
     }elseif($d->milestone_type == 2)
     {
        //$TheDate = $this->MoveDate($ETD, $d->delta_day);
        $TheDate =$OrderDate;
        $Pay = round($Amount/100*$d->percent,0);
        $NewDes = $Des . ' Pay for buying goods';
        if( $Pay > 0)
        {
         DB::connection('mysql')->table('fa_cashflow_transactions')->insert(
         ['income_expensive_id'=>1 ,'action_date'=>$TheDate,'amount'=>$Pay,
         'des'=>$NewDes ,'is_estimate'=>1,'type'=>2,'cash_account'=>$CashAccount]);
        }
     }elseif($d->milestone_type == 3)
     {
        //$TheDate = $this->MoveDate($ETA,$d->delta_day);
        $TheDate =$OrderDate;
        $Pay = round($Amount/100*$d->percent,0);
        $NewDes = $Des . ' Pay for buying goods';
        if( $Pay > 0)
        {
         DB::connection('mysql')->table('fa_cashflow_transactions')->insert(
         ['income_expensive_id'=>1 ,'action_date'=>$TheDate,'amount'=>$Pay,
         'des'=>$NewDes ,'is_estimate'=>1,'type'=>2,'cash_account'=>$CashAccount]);
        }
     }
    }
*/
    // print_r('ngày ' .$TheDate. 'Amount ' .  $Amount);
    // print_r('<br>');
    DB::connection('mysql')->table('fa_cashflow_transactions')->insert(
    ['income_expensive_id'=>1 ,'action_date'=>$TheDate,'amount'=>$Amount,
    'des'=>$NewDes ,'is_estimate'=>1,'type'=>2,'cash_account'=>$CashAccount]);
}
//=======================================================================================================================
public function GetLasDateOfMonth($TheYear ,$TheMonth)
{
    if($TheMonth ==12)
    {
        $TheMonth =1;
        $TheYear = $TheYear +1;
    }else
    {
        $TheMonth = $TheMonth + 1;
    }
    $s =  $TheYear  .'-' . $TheMonth . '-01';
    $DateTmp= date("Y-m-d",strtotime($s));

    return  date('Y-m-d',strtotime($DateTmp . '-1 days'));
}
//=======================================================================================================================
public function CreateTransactionManagementFee($TheYear ,$TheWeek)
{
    $FromDate = $this->GetFirstDateOfWeek($TheYear ,$TheWeek);
    $TheMonth = $this->GetMonthFromDate($FromDate);
    $TheYear = $this->GetYearFromDate($FromDate);

    $FromDate = $this->GetFirstDateOfMonth($TheYear ,$TheMonth);
    $ToDate = $this->GetLasDateOfMonth($TheYear ,$TheMonth);

    $sql = " select count(id) as MyCount from fa_cashflow_transactions
    where income_expensive_id = 4 and date(action_date) >=  '$FromDate'
    and date(action_date) <= '$ToDate' ";

    if($this->IsExist('mysql',$sql)== 0 )
    {
        $DayForPayment = 4;
        $TheAmount = 50000;
        $BeginDateOfMonth = $this->GetFirstDateOfMonth($TheYear ,$TheMonth);
        $BeginDateOfMonth = date('Y-m-d',strtotime($BeginDateOfMonth. '+'. $DayForPayment .'days'));

        DB::connection('mysql')->table('fa_cashflow_transactions')->insert(
        ['income_expensive_id'=>4 ,'action_date'=>$BeginDateOfMonth,'amount'=>$TheAmount,
        'des'=>'Management Office','is_estimate'=>1,'cash_account'=>2, 'type'=>2]);
    }
}
//=======================================================================================================================
public function GetCurrentIndex($Year,$Month)
{
 $Continue = true;
 $i = 0;
 while($i< $this->WeekCount  + $this->MaxLeadTime +1 && $Continue == true)
 {
    if($this->ArrYear[$i] == $Year && $this->ArrWeek[$i]==$Month)
    { $Continue = false;}
    else{  $i++;}
 }
 return $i;
}
//=======================================================================================================================

public function CashflowChartNew(Request $request)
{

    //  print_r('Bắt đầu lúc : '.date('Y-m-d H:i:s'));
    //  print_r('<br>');
    ini_set('memory_limit','2048M');
    set_time_limit(2600);

    $FromYear = $request->input('from_year');
    $FromWeek = $request->input('from_week');

    $ToYear = $request->input('to_year');
    $ToWeek = $request->input('to_week');
    $CashAccount = $request->input('cash_account');

    $request->flash();

    $this->WeekCount =$this->CountWeek($FromYear,$ToYear,$FromWeek,$ToWeek);

    $CurrentYear = $this->GetYearFromDate($this->GetCurrentDate());
    $CurrentWeek = $this->GetWeekFromDate($this->GetCurrentDate());

    $TheWeek = $FromWeek;
    $TheYear = $FromYear;
    for($i =0 ;$i < $this->WeekCount;$i++)
    {
        $this->ArrYear[$i]= $TheYear;
        $this->ArrWeek[$i]=  $TheWeek;
        if( $TheYear ==  $CurrentYear && $TheWeek == $CurrentWeek)
        {
          $CurrentIndex = $i;
        }
        $TheWeek = $TheWeek + 1;
        if($TheWeek > 52)
        {
         $TheWeek = 1;
         $TheYear = $TheYear + 1;
        }
    }

    $CurrentIndex=$this->GetIndex($CurrentYear,$CurrentWeek);

    $CashFlow = array();
    $FromDate = $this->GetFirstDateOfWeek($this->ArrYear[0],$this->ArrWeek[0]);
    $ToDate = $this->GetLastDateOfWeek($this->ArrYear[$this->WeekCount-1],$this->ArrWeek[$this->WeekCount -1]);
    // print_r('Final Year: '. $this->ArrYear[$this->WeekCount-1] );
    // print_r('<br>');
    // print_r('Final Week: '. $this->ArrWeek[$this->WeekCount -1] );
    // print_r('<br>');

    // Xóa tất cả các transaction giả định trong thời gian gian này
    // $sql = " delete from fa_cashflow_transactions where date(action_date) >= '$FromDate'
    // and date(action_date) <= '$ToDate' and  is_estimate = 1 ";

    // Xóa hết giao thức ước tính
    $sql = " delete from fa_cashflow_transactions where  is_estimate = 1 ";
    DB::connection('mysql')->select($sql);

    // Xóa hết số tiền tồn đầu tháng chưa chốt sổ
    $sql = " delete from fa_cashflow_begin where  is_close = 0 ";
    DB::connection('mysql')->select($sql);

    // Gán các giá trị cho các mảng $ArrWeek
    for($i = 0 ;$i< $this->WeekCount;$i++)
    {
      $ArrWeek[$i] = $this->ArrYear[$i]. '-' . $this->ArrWeek[$i];
    }

    //Tuần bắt đầu chính là tuần hiện tại
    // Lấy toàn bộ data giả định
    if($CurrentIndex == 0)
    {
        for($i = 0 ;$i< $this->WeekCount ; $i++)
        {
         // Tạo data thu chi giả định cho các tuần này
        //  print_r('Tuần tài chính đang xét: ' . $this->ArrYear[$i] . ' - '. $this->ArrWeek[$i] );
        //  print_r('<br>');
         $this->CreateTransactionEstimate($this->ArrYear[$i],$this->ArrWeek[$i],$CashAccount);
        }
    }
    // Tuần hiện tại chính là tuần cuối hoặc sau tuần cuối
    // Lấy toàn bộ data thực đã diễn ra, nên không cần tạo data giả định
    elseif($CurrentIndex >= $this->WeekCount)
    {
    }
    // Tuần hiện tại ở ở trong tuần đầu và tuần cuối
    elseif($CurrentIndex < $this->WeekCount && $CurrentIndex > 0)
    {
        //1. Từ tuần đầu tới tờ trước tuần hiện tại -> Lấy data thực
        //2. Từ tuần hiện tại đến tuần cuối lấy data giả định
        for($i = $CurrentIndex + 1 ;$i< $this->WeekCount;$i++)
        {
            // print_r(' Year: '.$this->ArrYear[$i] );
            // print_r('<br>');
            // print_r(' Week: '.$this->ArrWeek[$i]);
            // print_r('<br>');
           // Tạo data thu chi giả định cho các tuần này
           $this->CreateTransactionEstimate($this->ArrYear[$i],$this->ArrWeek[$i],$CashAccount);
        }
    }
    // Phần load data lên trình diễn
    $sql = "select id, name from fa_income_expensives order by the_order ";
    $ds=  DB::connection('mysql')->select($sql);
    $Index = 0;
    foreach($ds as $d)
    {
      $ArrID[$Index]= $d->id;
      $ArrName[$Index]= $d->name;
      $ArrayType[$Index] ='column';
      $Index++;
    }
    $ArrName[$Index]='Cash Balance Weekly';
    $ArrayType[$Index] ='spline';
    $ArrID[$Index]= 0;// Average

    // Khai báo mảng hai chiều
    $ArrData = array(
        array()
    );

    $sql= " delete from fa_cashflow_begin where is_close = 0 ";
    DB::connection('mysql')->select($sql);

    $s = '';
    for($i = 0 ; $i <=$Index ; $i++)
    {
        for($j = 0 ; $j <$this->WeekCount ; $j++)
        {
         $ArrData[$i][$j] = $this->GetIncomeOutcome($this->ArrYear[$j],$this->ArrWeek[$j], $ArrID[$i],$CashAccount);
        }
        $j = 0;
    }
    $m = 0;
    $n = 0;
    $o = 0;

    for($m=0;$m <= $Index ;$m++)
    {
      $item = array(
      'type' => $ArrayType[$m],
      'name' => $ArrName[$m],
      'data' => $ArrData[$m]);
      $CashFlow[] = $item;
    }
 return view('fa.CashflowChart',compact('CashFlow','ArrWeek'));
}

///---------------------------------------------------
 public function MoveNewDate($TheDate,$StepType,$Step)
 {
   //$Pos = strpos($TheDate,':');
   //$TheDate = substr($TheDate,0,strlen($TheDate)-9);
   $Time = strtotime($TheDate);
   $Tmp = date('Y-m-d',$Time);
   $Result = date('Y-m-d',strtotime($Tmp.$StepType. $Step.'days'));
   return $Result;
 }
//=======================================================================================================================
public function GetCurrentDate()
{
   //return date('Y-m-d H:i:s');
   return date('Y-m-d');
}
//=======================================================================================================================
public function GetSellType($Sku)
{
  $SellType=0;
   $sql = " select sell_type from prd_product where product_sku = '$Sku'";
   $ds = DB::connection('mysql')->select( $sql);
   foreach( $ds as  $d)
   {
     $SellType = $d->sell_type;
   }
   return  $SellType;
}
//=======================================================================================================================
public function ImportSheet($SheetName,$ChannelID, $StartYear, $StartWeek,$WeekCount,$CurentDate, $file,$reader)
{

$RowStart = 5;
$ColStart = 4;

$reader->setLoadSheetsOnly([$SheetName,$SheetName]);
$spreadsheet = $reader->load($file);
$RowEnd = $spreadsheet->getActiveSheet()->getHighestRow();

for($Row = $RowStart; $Row <=  $RowEnd ; $Row ++)
    {
        $Sku = $spreadsheet->getActiveSheet()->getCellByColumnAndRow(1,$Row)->getValue();//  SKU

        if( $Sku <>'')
        {
            $CurrentIndex = $this->GetIndex($this->CurrentYear ,$this->CurrentWeek);
            for($Col = $ColStart; $Col < $ColStart + $WeekCount ; $Col++)
            {
                $this->ArrFC[$CurrentIndex] = $spreadsheet->getActiveSheet()->getCellByColumnAndRow( $Col,$Row)->getValue();
                $TheYear = $this->ArrYear[$CurrentIndex];
                $TheWeek = $this->ArrWeek[$CurrentIndex];
                $Quantity= $this->ArrFC[$CurrentIndex];
                $CurrentIndex++;

                if(is_numeric($Quantity) && $Quantity>0 ){
                   $SellType = $this->GetSellType($Sku);

                //    print_r('sku:'. $Sku . ' Selltype: ' .$SellType);
                //    print_r('<br>' );

                  if($SellType == 2 || $SellType ==3)// Combo/Multiple-> Tách ra thành single
                  {
                    $sql = " select p2.product_sku as sku, pc.quantity * $Quantity as quantity , p.sell_type
                    from prd_product p inner join  productcombo pc on p.id = pc.product_id
                    inner join prd_product p2 on pc.child_id = p2.id
                    where p.product_sku = '$Sku'";
                    $d1s = DB::connection('mysql')->select( $sql);
                    foreach( $d1s as  $d1)
                    {
                      if(!is_null($d1->sku))
                      {
                        $this->InsertToForeCast($TheYear,$TheWeek,$d1->sku,$d1->quantity ,$ChannelID);
                      }
                      else
                      {
                        // print_r('sku:'. $Sku . ' là combo/Mutilple nhưng không thấy có cấu hình' );
                        // print_r('<br>' );
                      }
                    }
                  }
                  else
                  {
                    // print_r('SKU:' . $Sku . ' Year:'. $TheYear. ' Week:'.$TheWeek. 'Quantity:'. $Quantity);
                    // print_r('<br>');
                   $this->InsertToForeCast($TheYear,$TheWeek,$Sku,$Quantity ,$ChannelID);
                  }
                }
            }// for Col
        }// end if
    }// End for
}
//=======================================================================================================================
public function iif($condition, $true, $false)
{
 return ($condition?$true:$false);
}
//=======================================================================================================================
public function IsExist($sConnection,$sql)
{//$sConnection,$sql
    //$sConnection='mysql';
    //$sql="select count(id) as MyCount from fa_cashflow_begin  where the_year = 2020 and the_month = 9";
    $Result = 0;
    $ds= DB::connection($sConnection)->select($sql);
    foreach($ds as $d) {$Result  = $this->iif(is_null($d->MyCount),0,$d->MyCount);    }

    return $Result;
}
//=======================================================================================================================
public function ImportPuPlanDefault()
{
    $sql = " select pu_po_estimates.id, the_year as order_year , the_week as order_week ,pu_vendors.vendor_name,pu_po_estimates.lead_time ,
    pu_po_estimates.expect_load_date, pu_po_estimates.expect_etd_date, pu_po_estimates.expect_eta_date, pu_po_estimates.start_selling_date,
    pu_po_estimates.end_selling_date
    from pu_po_estimates left join pu_vendors on pu_po_estimates.vendor_id = pu_vendors.id
    order by the_year , the_week " ;
    $dsPuPlan = DB::connection('mysql')->select($sql);
   // dd($dsPuPlan);
    return view('fa.ImportPuPlan',compact('dsPuPlan'));

   // return view('fa.CashflowImportFile',compact('dsPuPlan'));
   // return view('fa.test');
}
//=======================================================================================================================
public function ImportPuPlan(Request $request)
{
    $validator = Validator::make($request->all(),['file'=>'required|max:45000|mimes:xlsx,xls,csv']);

    if($validator->passes())
    {
        $file = $request->file('file');
        $reader = new \PhpOffice\PhpSpreadsheet\Reader\Xlsx();
        $reader->setLoadSheetsOnly(["Sheet1","Sheet1"]);
        $spreadsheet = $reader->load($file);
        $RowStart = 2;
        $RowEnd = $spreadsheet->getActiveSheet()->getHighestRow();
        for($Row = $RowStart; $Row <=  $RowEnd ; $Row ++)
         {

             $PlanID = $spreadsheet->getActiveSheet()->getCellByColumnAndRow(1,$Row)->getValue();
             $OrderYear = $spreadsheet->getActiveSheet()->getCellByColumnAndRow(2,$Row)->getValue();
             $OrderWeek = $spreadsheet->getActiveSheet()->getCellByColumnAndRow(3,$Row)->getValue();
             $LeadTime = $spreadsheet->getActiveSheet()->getCellByColumnAndRow(5,$Row)->getValue();
             $LoadDate = $spreadsheet->getActiveSheet()->getCellByColumnAndRow(6,$Row)->getFormattedValue();
             //$LoadDate = $LoadDate->format('Y-m-d');
             $ETD = $spreadsheet->getActiveSheet()->getCellByColumnAndRow(7,$Row)->getFormattedValue();
             //$ETD = $ETD->format('Y-m-d');
             $ETA = $spreadsheet->getActiveSheet()->getCellByColumnAndRow(8,$Row)->getFormattedValue();
             //$ETA = $ETA->format('Y-m-d');
             $StartSelling = $spreadsheet->getActiveSheet()->getCellByColumnAndRow(9,$Row)->getFormattedValue();
             //$StartSelling=$StartSelling->format('Y-m-d');
             $EndSelling = $spreadsheet->getActiveSheet()->getCellByColumnAndRow(10,$Row)->getFormattedValue();
             //$EndSelling=$EndSelling->format('Y-m-d');
             $CancelSKU = $spreadsheet->getActiveSheet()->getCellByColumnAndRow(11,$Row)->getValue();

             if($CancelSKU =='All' || $CancelSKU =='ALL'){$Status = 0 ;}
             elseif($CancelSKU ==''){
                $CancelSKU ='-';
                $Status = 1 ;
                }
             else{$Status = 1 ;}

             //print_r('Load Date: ' .$LoadDate. ' ETD: ' .  $ETD . ' ETA: ' .  $ETA . ' EndSelling: '. $StartSelling  );
             //print_r('<br>');
             // Tìm PU plan với những thông số từ file import, nếu không tim thấy tức là plan đó đã được PU cập nhật
             $sql = " select id as MyCount from pu_po_estimates
             where the_week = $OrderWeek and the_year = $OrderYear
             and expect_load_date = '$LoadDate' and expect_etd_date ='$ETD'
             and expect_eta_date ='$ETA'  and start_selling_date ='$StartSelling'
             and end_selling_date = '$EndSelling' and id = $PlanID  ";

            //  print_r('sql: ' .$sql);
            //  print_r('<br>');

             $id = $this->IsExist('mysql',$sql);

            //  print_r('ID: ' . $id);
            //  print_r('<br>');

             // $id == 0 ~ không tìm thấy plan theo Plan ID với thông số mới ~ PU đã cập nhật lại
             //$Status = 0 ~ PU đã cancel plan đó
             if($id == 0 || $Status == 0)
               {
                $sql = " update pu_po_estimates set the_week = $OrderWeek,the_year = $OrderYear ,
                expect_load_date = '$LoadDate', expect_etd_date ='$ETD',expect_eta_date ='$ETA',
                start_selling_date ='$StartSelling',cancel_sku = '$CancelSKU',status_id = $Status,
                end_selling_date = '$EndSelling',pu_adjust = 1 where id = $PlanID ";

                // print_r('sql: ' .$sql);
                // print_r('<br>');
                DB::connection('mysql')->select($sql);

               }
         }// For
         $this->LoadPuPlan();
    }
}
//=======================================================================================================================
// Tính lại lượng hàng thiếu trên từng Po estimate,
// chỉ cần tính các PO đặt hàng từ hiện tại về quá khứ là max lead time
public function ReCaculateMissingGood()
{

}
//=======================================================================================================================
public function ImportFile(Request $request)
{
    ini_set('memory_limit','2048M');
    set_time_limit(2600);

   // print_r('Caculated from : '.date('Y-m-d H:i:s'));
   // print_r('<br>');

    $this->StartYear = $request->input('start_year');
    $this->StartWeek = $request->input('start_week');
    $this->WeekCount = $request->input('weeks');
    $request->flash();

    $TheYear = $this->StartYear;
    $TheWeek = $this->StartWeek;
    $this->InitArrayYearWeek($TheYear, $TheWeek);

    $validator = Validator::make($request->all(),['file'=>'required|max:45000|mimes:xlsx,xls,csv']);

    if($validator->passes())
    {
        $file = $request->file('file');
        $reader = new \PhpOffice\PhpSpreadsheet\Reader\Xlsx();

        // $SheetName = "FC-FBM";
        // $ChannelID = 10; //FBM
        // print_r('Bắt đầu import FBM');
        // print_r('<br>');
        // $this->ImportSheet($SheetName,$ChannelID, $this->StartYear , $this->StartWeek ,$this->WeekCount,$this->CurrentDate , $file,$reader);
        // print_r('Kết thúc import FBM');
        // print_r('<br>');

        // $SheetName = "FC-AVC_DS";
        // $ChannelID = 2; //AVC_DS
        // print_r('Bắt đầu import AVC_DS');
        // print_r('<br>');
        // $this->ImportSheet($SheetName,$ChannelID, $this->StartYear , $this->StartWeek,$this->WeekCount,$this->CurrentDate, $file,$reader);

        // $SheetName = "FC-WM_DSV";
        // $ChannelID = 4; //WM_DSV
        // print_r('Bắt đầu import WM_DSV');
        // print_r('<br>');
        // $this->ImportSheet($SheetName,$ChannelID, $this->StartYear , $this->StartWeek,$this->WeekCount, $this->CurrentDate, $file,$reader);

        // $SheetName = "FC-EBAY";
        // $ChannelID = 6; //EBAY
        // print_r('Bắt đầu import EBAY');
        // print_r('<br>');
        // $this->ImportSheet($SheetName,$ChannelID, $this->StartYear , $this->StartWeek,$this->WeekCount, $this->CurrentDate, $file,$reader);

        // $SheetName = "FC-LOCAL";
        // $ChannelID = 7; //LOCAL
        // print_r('Bắt đầu import LOCAL');
        // print_r('<br>');
        // $this->ImportSheet($SheetName,$ChannelID, $this->StartYear,  $this->StartWeek,$this->WeekCount, $this->CurrentDate, $file,$reader);


        //PU PLAN
        //  $reader->setLoadSheetsOnly(["PU-PLAN","PU-PLAN"]);
        //  $spreadsheet = $reader->load($file);
        //  $RowStart = 3;
        //  $RowEnd = $spreadsheet->getActiveSheet()->getHighestRow();
        //  DB::connection('mysql')->select (" delete from pu_plannings ");
        //  for($Row = $RowStart; $Row <=  $RowEnd ; $Row ++)
        //  {
        //      $OrderYear = $spreadsheet->getActiveSheet()->getCellByColumnAndRow(1,$Row)->getValue();
        //      $OrderWeek = $spreadsheet->getActiveSheet()->getCellByColumnAndRow(2,$Row)->getValue();
        //      $VendorID = $spreadsheet->getActiveSheet()->getCellByColumnAndRow(3,$Row)->getValue();
        //      $LeadTime = $spreadsheet->getActiveSheet()->getCellByColumnAndRow(5,$Row)->getValue();
        //      $LoadDate = $spreadsheet->getActiveSheet()->getCellByColumnAndRow(6,$Row)->getFormattedValue();
        //      $ETD = $spreadsheet->getActiveSheet()->getCellByColumnAndRow(7,$Row)->getFormattedValue();
        //      $ETA = $spreadsheet->getActiveSheet()->getCellByColumnAndRow(8,$Row)->getFormattedValue();
        //      $StartSelling = $spreadsheet->getActiveSheet()->getCellByColumnAndRow(9,$Row)->getFormattedValue();
        //      $EndSelling = $spreadsheet->getActiveSheet()->getCellByColumnAndRow(10,$Row)->getFormattedValue();

        //      DB::connection('mysql')->table('pu_plannings')->insert(
        //      ['order_year'=>$Sku,'order_week'=>$OrderWeek,'vendor_id'=>$VendorID,
        //      'lead_time'=>$LeadTime,'load_date'=>$LoadDate,'etd'=>$ETD,'eta'=>$ETA,
        //      'start_selling'=>$StartSelling,'end_selling'=>$EndSelling]);
        //  }

        //VENDOR-SKU
        $reader->setLoadSheetsOnly(["VENDOR-SKU","VENDOR-SKU"]);
        $spreadsheet = $reader->load($file);
        $RowStart = 3;
        $RowEnd = $spreadsheet->getActiveSheet()->getHighestRow();
        DB::connection('mysql')->select (" delete from pu_vendor_products ");
        for($Row = $RowStart; $Row <=  $RowEnd ; $Row ++)
        {
            $Sku = $spreadsheet->getActiveSheet()->getCellByColumnAndRow(1,$Row)->getValue();
            $VendorID = $spreadsheet->getActiveSheet()->getCellByColumnAndRow(3,$Row)->getValue();
            $LeadTime = $spreadsheet->getActiveSheet()->getCellByColumnAndRow(5,$Row)->getValue();
            $Buying = $spreadsheet->getActiveSheet()->getCellByColumnAndRow(7,$Row)->getValue();

            if($Buying <> ''){ $bit = 0;}
            else{$bit = 1;};

            DB::connection('mysql')->table('pu_vendor_products')
            ->insert( ['sku'=>$Sku,'vendor_id'=>$VendorID,'lead_time'=>$LeadTime,'buying'=>$bit]);
        }
        //SKU-SELLING CHANNEL PRICE
        // $reader->setLoadSheetsOnly(["SKU-SELLING CHANNEL PRICE","SKU-SELLING CHANNEL PRICE"]);
        // $spreadsheet = $reader->load($file);
        // $RowStart = 2;
        // $RowEnd = $spreadsheet->getActiveSheet()->getHighestRow();
        // DB::connection('mysql')->select (" delete from sal_product_channel_price ");
        // for($Row = $RowStart; $Row <=  $RowEnd ; $Row ++)
        // {
        //     $Sku = $spreadsheet->getActiveSheet()->getCellByColumnAndRow(1,$Row)->getValue();
        //     $Fbm = $spreadsheet->getActiveSheet()->getCellByColumnAndRow(3,$Row)->getValue();
        //     $Avcds = $spreadsheet->getActiveSheet()->getCellByColumnAndRow(4,$Row)->getValue();
        //     $Wmdsv = $spreadsheet->getActiveSheet()->getCellByColumnAndRow(5,$Row)->getValue();
        //     $Ebay = $spreadsheet->getActiveSheet()->getCellByColumnAndRow(6,$Row)->getValue();
        //     $Local = $spreadsheet->getActiveSheet()->getCellByColumnAndRow(7,$Row)->getValue();
        //     $Website = $spreadsheet->getActiveSheet()->getCellByColumnAndRow(8,$Row)->getValue();
        //     $Wayfair = $spreadsheet->getActiveSheet()->getCellByColumnAndRow(9,$Row)->getValue();

        //     DB::connection('mysql')->table('sal_product_channel_price')
        //     ->insert( ['sku'=>$Sku,'channel_id'=>10,'price'=>$Fbm]);

        //     DB::connection('mysql')->table('sal_product_channel_price')
        //     ->insert( ['sku'=>$Sku,'channel_id'=>2,'price'=>$Avcds]);

        //     DB::connection('mysql')->table('sal_product_channel_price')
        //     ->insert( ['sku'=>$Sku,'channel_id'=>4,'price'=>$Wmdsv]);

        //     DB::connection('mysql')->table('sal_product_channel_price')
        //     ->insert( ['sku'=>$Sku,'channel_id'=>6,'price'=>$Ebay]);

        //     DB::connection('mysql')->table('sal_product_channel_price')
        //     ->insert( ['sku'=>$Sku,'channel_id'=>7,'price'=>$Local]);

        //     DB::connection('mysql')->table('sal_product_channel_price')
        //     ->insert( ['sku'=>$Sku,'channel_id'=>8,'price'=>$Website]);

        //     DB::connection('mysql')->table('sal_product_channel_price')
        //     ->insert( ['sku'=>$Sku,'channel_id'=>12,'price'=>$Wayfair]);
        // }

	//is_estimate

    //CashflowTransaction
    //  $reader->setLoadSheetsOnly(["CashflowTransaction","CashflowTransaction"]);
    //  $spreadsheet = $reader->load($file);
    //  $RowStart=3;
    //  $RowEnd = $spreadsheet->getActiveSheet()->getHighestRow();
    //  $i=0;
    //  for($Row = $RowStart; $Row <=  $RowEnd ; $Row ++)
    //    {
    //     $TheDate = $spreadsheet->getActiveSheet()->getCellByColumnAndRow(2,$Row)->getValue();//  TheDate
    //     $Des = $spreadsheet->getActiveSheet()->getCellByColumnAndRow(3,$Row)->getValue();// Des
    //     $IncomeOrExpensiveID = $spreadsheet->getActiveSheet()->getCellByColumnAndRow(4,$Row)->getValue();//  $IncomeOrExpensiveID
    //     $SalesChannelID = $spreadsheet->getActiveSheet()->getCellByColumnAndRow(6,$Row)->getValue();//
    //     $ForFromDate = $spreadsheet->getActiveSheet()->getCellByColumnAndRow(8,$Row)->getValue();//
    //     $ForToDate = $spreadsheet->getActiveSheet()->getCellByColumnAndRow(9,$Row)->getValue();//
    //     $Amount =  $spreadsheet->getActiveSheet()->getCellByColumnAndRow(10,$Row)->getValue();//  $Amount
    //     $Imported =  $spreadsheet->getActiveSheet()->getCellByColumnAndRow(11,$Row)->getValue();//  Imported
    //     print_r('$Imported'.$Imported );
    //     print_r('<br>');
    //     $i++;
    //     if($Imported == 0 )
    //     {
    //      DB::connection('mysql')->table('fa_cashflow_transactions')->insert(
    //      ['action_date'=>$TheDate ,'des'=>$Des,'income_expensive_id'=>$IncomeOrExpensiveID,
    //      'for_from_date'=>$ForFromDate,'for_to_date'=>$ForToDate,'sales_channel_id'=>$SalesChannelID,
    //      'amount'=>$Amount,'is_estimate'=>0]);

    //      if($IncomeOrExpensiveID == 9){
    //        $this->UpdateCollectMoney($SalesChannelID,$ForFromDate,$ForToDate,$Amount);
    //       }

    //     }
    //    }


    // $reader->setLoadSheetsOnly(["PU-EVENT-OFF","PU-EVENT-OFF"]);
    // $spreadsheet = $reader->load($file);
    // $RowStart=3;
    // $RowEnd = $spreadsheet->getActiveSheet()->getHighestRow();
    // $sql = " delete from pu_off_event_details  ";
    // db::connection('mysql')->select($sql);

    // for($Row = $RowStart; $Row <=  $RowEnd ; $Row ++)
    //   {
    //    $EventID = $spreadsheet->getActiveSheet()->getCellByColumnAndRow(1,$Row)->getValue();//
    //    $TheYear = $spreadsheet->getActiveSheet()->getCellByColumnAndRow(3,$Row)->getValue();//
    //    $TheWeek = $spreadsheet->getActiveSheet()->getCellByColumnAndRow(4,$Row)->getValue();//

    //    if( $EventID<>'' && $TheYear <>'' &&  $TheWeek <>'')
    //    {
    //     DB::connection('mysql')->table('pu_off_event_details')->insert(
    //     ['event_id'=>$EventID ,'the_year'=>$TheYear,'the_week'=> $TheWeek]);
    //    }
    //   }


    // $reader->setLoadSheetsOnly(["MKT","MKT"]);
    // $spreadsheet = $reader->load($file);
    // $RowStart = 2;
    // $RowEnd = $spreadsheet->getActiveSheet()->getHighestRow();

    // for($Row = $RowStart; $Row <=  $RowEnd ; $Row ++)
    //   {
    //    $Sku = $spreadsheet->getActiveSheet()->getCellByColumnAndRow(1,$Row)->getValue();//
    //    $SEM = $spreadsheet->getActiveSheet()->getCellByColumnAndRow(2,$Row)->getValue();//
    //    $Promtion = $spreadsheet->getActiveSheet()->getCellByColumnAndRow(3,$Row)->getValue();//

    //    if($Sku <>'')
    //    {
    //     $sql = " update pu_vendor_products set sem = $SEM , promotion = $Promtion where sku ='$Sku'";
    //     print_r('sql:' .$sql );
    //     print_r('<br>');
    //     $ds = DB::connection('mysql')->select ($sql);
    //    }
    //   }
    //$this->LoadSEMAndPromotionBudget();
    }// end pass

    //$this->CaculateWeeklyProductDemand($this->CurrentDate);
    //$this->ReArranOrderWeekNew();//Sắp xếp lại để tránh tuần nghỉ
   // $this->CreatePOEstimate();//Tạo PO estimtate
   // $this->LoadPuPlan();// Load PU Plan
   //$this->ChartDefault();
   //print_r('Caculated To : '.date('Y-m-d H:i:s'));
   //print_r('<br>');
   //dd($this->CurrentDate);
}
// =======================================================================================
// $FromYear , $ToYear = nhau hoặc 2 năm liên tiếp, to year >= from year
public function LoadSEMAndPromotionBudget()
{
   dd('aaaaaaaâ');
    /*
    $this->CurrentDate = date('Y-m-d', time());
   $sql = " select sku from  pu_vendor_products ";
   $ds = DB::connection('mysql')->select ($sql);
   foreach( $ds as $d )
    {
       $Balance = $this->GetBalanceBeginningOnTheDate($d->sku,$this->CurrentDate);
       print_r(' sku: ' .$d->sku . ' Balance: ' . $Balance );
       print_r('<br>');
       $sql = " update  pu_vendor_products set current_balance = $Balance where sku = '$d->sku'";
       DB::connection('mysql')->select ($sql);
    }

    $sql = " select p.sku,(p.current_balance *p.promotion * pr.price) as PromotionBudget,
    (p.current_balance*p.sem * pr.price) as SemBudget
    from pu_vendor_products p inner join sal_product_channel_price pr on p.sku = pr.sku
    where pr.channel_id  = 10 " ;
    $dsPromotionAndSem = DB::connection('mysql')->select ($sql);
    return view('sal.PromotionAndSemBudget',compact('dsPromotionAndSem'));
    */
}
// =======================================================================================
public function GetSellingDataDefault(Request $request)
{
    $sql = " select sku,p.title,sal_channels.name,order_date,quantity,round(amount)as amount
     from tmp_orders  inner join sal_channels on tmp_orders.channel = sal_channels.id
     inner join prd_product p  on tmp_orders.sku = p.product_sku
     where 1 = 2";
    $ds = DB::connection('mysql')->select ($sql);
    $dsCols = array() ;
    return view('sal.sellingDailyNew',compact(['ds','dsCols']));
}
// =======================================================================================
public function GetSellingData(Request $request)
{
    $Type =  $request->input('type');;
    $FromDate= $request->input('fromDate');
    $ToDate= $request->input('toDate');
    $SalesChannel = $request->input('channel');
    $Sku = $request->input('sku');

    print_r('Type ' . $Type . ' $SalesChannel ' .  $SalesChannel  . ' $Sku ' . $Sku);
    print_r('<br>');


    $request->flash();
    $sql = " delete from tmp_orders ";
    DB::connection('mysql')->select ($sql);

    $sql = " select o.amazon_order_id,date(o.purchased_date) as purchased_date,o.status_text, odt.seller_sku  as sku,
    odt.quantity_ordered, odt.item_price, round(odt.quantity_ordered * odt.item_price) as amount,
    p.appotion_price as cogs_unit, (p.appotion_price * odt.quantity_ordered) AS cogs_total,
    10 as sales_channel from amazon_orders o inner join amazon_order_details
    odt on o.id = odt.amazon_order_id inner join products
    p on odt.product_id = p.id
    where p.company_id <> 1
    and o.purchased_date >= '$FromDate'
    and o.purchased_date <= '$ToDate'

    union

    select o.amazon_order_id,date(o.order_placed_date) ,o.status, p.product_sku  as sku, odt.quantity,
    round(odt.cost/odt.quantity) as price,round(odt.cost) as amount, p.appotion_price as cogs_unit,
    (p.appotion_price * odt.quantity) AS cogs_total, 2 as sales_channel
    from amazon_dropship_orders o inner join amazon_dropship_order_details
    odt on o.id = odt.order_id inner join products p on odt.product_id = p.id
    where p.company_id <> 1
    and o.order_placed_date >= '$FromDate'
    and o.order_placed_date <= '$ToDate'

    union

    select o.ebay_extended_order_id,date(o.order_time) ,o.order_status, p.product_sku  as sku,
    odt.quantity_purchased, odt.transaction_price as price,
    round(odt.quantity_purchased * odt.transaction_price) as amount, p.appotion_price as cogs_unit,
    (p.appotion_price * odt.quantity_purchased) AS cogs_total, 6 as sales_channel
    from ebay_order o inner join ebay_order_detail odt on o.id = odt.ebay_order_id
    inner join products p on odt.product_id = p.id
    where p.company_id <> 1
    and o.order_time >= '$FromDate'
    and o.order_time <= '$ToDate'

    union

    select o.order_id,date(o.order_processing_date) ,o.status , p.product_sku  as sku, odt.ordered_quantity,
    odt.cost as price ,round(odt.cost * odt.ordered_quantity) as amount, p.appotion_price as cogs_unit,
    (p.appotion_price * odt.ordered_quantity) AS cogs_total, 4 as sales_channel
    from walmart_dropship_orders o inner join walmart_dropship_order_details odt
    on o.id = odt.order_id inner join products p on odt.product_id = p.id
    where p.company_id <> 1
    and o.order_processing_date >= '$FromDate'
    and o.order_processing_date <= '$ToDate'

    union

    all select o.purchase_order_id,date(o.order_date) ,odt.status , p.product_sku  as sku,
    odt.order_quantity, odt.charge_amount/odt.order_quantity as price ,
   round(odt.charge_amount) as amount, p.appotion_price as cogs_unit,
    (p.appotion_price * odt.order_quantity) AS cogs_total, 5 as sales_channel
    from walmart_order o inner join walmart_order_details odt on o.id = odt.wm_order_id
    inner join products p on odt.product_id = p.id
    where p.company_id <> 1
    and o.order_date >= '$FromDate'
    and o.order_date <= '$ToDate'

    union

    all select o.magento_order_id,date(o.create_date) ,o.status_value , p.product_sku  as sku ,
    odt.quantity, odt.product_price as price, round(product_price * quantity) as amount,
    p.appotion_price as cogs_unit, (p.appotion_price * odt.quantity) AS cogs_total,
    8 as sales_channel from magento_orders o inner join magento_order_details odt
    ON odt.magento_order_id = o.magento_order_id
    inner join products p on odt.product_id = p.id
    where p.company_id <> 1
    and o.create_date >= '$FromDate'
    and o.create_date <= '$ToDate'

    union

    select o.po_number,date(o.po_date),o.status, p.product_sku  as sku ,odt.quantity,
    odt.wholesale_price as price,round(wholesale_price * quantity) as amount,
    p.appotion_price AS cogs_unit, (p.appotion_price * odt.quantity) AS cogs_total,
    12 as sales_channel from wayfair_orders o inner join wayfair_order_details odt
    ON o.id = odt.order_id inner join products p on odt.product_id = p.id
    where p.company_id <> 1
    and o.po_date >= '$FromDate'
    and o.po_date <= '$ToDate'

    union

    select o.craigslist_order_id,date(o.order_date) ,'Completed' AS status, p.product_sku as sku,
    (odt.quantity - odt.used_quantity) AS quantity, odt.total_price/odt.quantity as price,
    round(((odt.total_price/odt.quantity) * (odt.quantity - odt.used_quantity))) as amount,
    p.appotion_price as cogs_unit, (p.appotion_price * odt.quantity) AS cogs_total,
    7 as sales_channel
    from craigslist_order o inner join craigslist_order_details odt
    ON odt.order_id = o.id inner join products p on odt.product_id = p.id
    where p.company_id <> 1
    and o.order_date >= '$FromDate'
    and o.order_date <= '$ToDate'" ;

    $ds = DB::connection('mysql_it')->select ($sql);
   foreach( $ds as $d )
   {
    if($d->status_text <> 'Canceled'){
    DB::connection('mysql')->table('tmp_orders')->insert(
    ['sku'=>$d->sku,'channel'=>$d->sales_channel,'order_date'=>$d->purchased_date,
    'quantity'=>$d->quantity_ordered,'amount'=>$d->amount,'cogs'=>$d->cogs_total ]);
    }
   }


   $Cols = array();
   $Count = 0;
   $ArrayTitle = array();
   $ArrDate = array();

    while($FromDate <= $ToDate )
    {
        $ArrDate[$Count] = $FromDate;
        $ArrayTitle[$Count] =  $ArrDate[$Count];
        $FromDate = $this->MoveDate($FromDate,1);
        $Count++;
    }

   $CashFlow = array();
   for($m = 0;$m < $Count +2 ; $m++)
    {
      if($m == 0)
      {
        $Cols = array(
        'title' =>'sku',
        'field' =>'sku');
      }elseif($m == 1)
      {
        $Cols = array(
        'title' =>'name',
        'field' =>'name');
      }else
      {
        $Cols = array(
        'title' => $ArrayTitle[$m-2],
        'field' => $ArrDate[$m-2]);
      }
      $dsCols[] =  $Cols ;
    }
   if($Sku==''){$Sku='All';}
   $ds = DB::connection('mysql')->select ('call SumQuantitySalesDaily(?,?,?)',[$Type,$SalesChannel,$Sku]);
   return view('sal.sellingDailyNew',compact(['ds','dsCols']));
}

// =======================================================================================
public function GetSellingDataOld(Request $request)
{
    $FromDate= $request->input('fromDate');
    $ToDate= $request->input('toDate');
    $request->flash();

    $sql = " delete from tmp_orders ";
    DB::connection('mysql')->select ($sql);

    $sql = " select o.amazon_order_id,o.purchased_date,o.status_text, odt.seller_sku  as sku,
    odt.quantity_ordered, odt.item_price, (odt.quantity_ordered * odt.item_price) as amount,
    p.appotion_price as cogs_unit, (p.appotion_price * odt.quantity_ordered) AS cogs_total,
    10 as sales_channel from amazon_orders o inner join amazon_order_details
    odt on o.id = odt.amazon_order_id inner join products
    p on odt.product_id = p.id
    where p.company_id <> 1
    and o.purchased_date >= '$FromDate'
    and o.purchased_date <= '$ToDate'

    union all

    select o.amazon_order_id,o.order_placed_date ,o.status, p.product_sku  as sku, odt.quantity,
    round(odt.cost/odt.quantity) as price, odt.cost as amount, p.appotion_price as cogs_unit,
    (p.appotion_price * odt.quantity) AS cogs_total, 2 as sales_channel
    from amazon_dropship_orders o inner join amazon_dropship_order_details
    odt on o.id = odt.order_id inner join products p on odt.product_id = p.id
    where p.company_id <> 1
    and o.order_placed_date >= '$FromDate'
    and o.order_placed_date <= '$ToDate'

    union all

    select o.ebay_extended_order_id,o.order_time ,o.order_status, p.product_sku  as sku,
    odt.quantity_purchased, odt.transaction_price as price,
    odt.quantity_purchased * odt.transaction_price as amount, p.appotion_price as cogs_unit,
    (p.appotion_price * odt.quantity_purchased) AS cogs_total, 6 as sales_channel
    from ebay_order o inner join ebay_order_detail odt on o.id = odt.ebay_order_id
    inner join products p on odt.product_id = p.id
    where p.company_id <> 1
    and o.order_time >= '$FromDate'
    and o.order_time <= '$ToDate'

    union all

    select o.order_id,o.order_processing_date ,o.status , p.product_sku  as sku, odt.ordered_quantity,
    odt.cost as price , odt.cost * odt.ordered_quantity as amount, p.appotion_price as cogs_unit,
    (p.appotion_price * odt.ordered_quantity) AS cogs_total, 4 as sales_channel
    from walmart_dropship_orders o inner join walmart_dropship_order_details odt
    on o.id = odt.order_id inner join products p on odt.product_id = p.id
    where p.company_id <> 1
    and o.order_processing_date >= '$FromDate'
    and o.order_processing_date <= '$ToDate'

    union

    all select o.purchase_order_id, o.order_date ,odt.status , p.product_sku  as sku,
    odt.order_quantity, odt.charge_amount/odt.order_quantity as price ,
    odt.charge_amount as amount, p.appotion_price as cogs_unit,
    (p.appotion_price * odt.order_quantity) AS cogs_total, 5 as sales_channel
    from walmart_order o inner join walmart_order_details odt on o.id = odt.wm_order_id
    inner join products p on odt.product_id = p.id
    where p.company_id <> 1
    and o.order_date >= '$FromDate'
    and o.order_date <= '$ToDate'

    union

    all select o.magento_order_id, o.create_date ,o.status_value , p.product_sku  as sku ,
    odt.quantity, odt.product_price as price, (product_price * quantity) as amount,
    p.appotion_price as cogs_unit, (p.appotion_price * odt.quantity) AS cogs_total,
    8 as sales_channel from magento_orders o inner join magento_order_details odt
    ON odt.magento_order_id = o.magento_order_id
    inner join products p on odt.product_id = p.id
    where p.company_id <> 1
    and o.create_date >= '$FromDate'
    and o.create_date <= '$ToDate'

    union all

    select o.po_number,o.po_date,o.status, p.product_sku  as sku ,odt.quantity,
    odt.wholesale_price as price, (wholesale_price * quantity) as amount,
    p.appotion_price AS cogs_unit, (p.appotion_price * odt.quantity) AS cogs_total,
    12 as sales_channel from wayfair_orders o inner join wayfair_order_details odt
    ON o.id = odt.order_id inner join products p on odt.product_id = p.id
    where p.company_id <> 1
    and o.po_date >= '$FromDate'
    and o.po_date <= '$ToDate'

    union all

    select o.craigslist_order_id, o.order_date ,'Completed' AS status, p.product_sku as sku,
    (odt.quantity - odt.used_quantity) AS quantity, odt.total_price/odt.quantity as price,
    ((odt.total_price/odt.quantity) * (odt.quantity - odt.used_quantity)) as amount,
    p.appotion_price as cogs_unit, (p.appotion_price * odt.quantity) AS cogs_total,
    7 as sales_channel
    from craigslist_order o inner join craigslist_order_details odt
    ON odt.order_id = o.id inner join products p on odt.product_id = p.id
    where p.company_id <> 1
    and o.order_date >= '$FromDate'
    and o.order_date <= '$ToDate'" ;

    $ds = DB::connection('mysql_it')->select ($sql);
   foreach( $ds as $d )
   {
    if($d->status_text <> 'Canceled'){
    DB::connection('mysql')->table('tmp_orders')->insert(
    ['sku'=>$d->sku,'channel'=>$d->sales_channel,'order_date'=>$d->purchased_date,
    'quantity'=>$d->quantity_ordered,'amount'=>$d->amount,'cogs'=>$d->cogs_total ]);
    }
   }
   $sql = " select sku,p.title,sal_channels.name,order_date,quantity,round(amount)as amount
   from tmp_orders  inner join sal_channels on tmp_orders.channel = sal_channels.id
   inner join prd_product p  on tmp_orders.sku = p.product_sku ";
   $ds = DB::connection('mysql')->select ($sql);
   return view('sal.sellingDaily',compact('ds'));

}
// =======================================================================================
public function LoadSEMAndPromotionBudgetDefault()
{
   $this->CurrentDate = date('Y-m-d', time());
   $sql = " select sku from  pu_vendor_products ";
   $ds = DB::connection('mysql')->select ($sql);
   foreach( $ds as $d )
    {
       $Balance = $this->GetBalanceBeginningOnTheDate($d->sku,$this->CurrentDate);
       $sql = " update  pu_vendor_products set current_balance = $Balance where sku = '$d->sku'";
       DB::connection('mysql')->select ($sql);
    }
    $sql = " select p.sku,(p.current_balance *p.promotion * pr.price) as PromotionBudget ,
    (p.current_balance*p.sem * pr.price) as SemBudget
    from pu_vendor_products p inner join sal_product_channel_price pr on p.sku = pr.sku
    where pr.channel_id  = 10 " ;
    $dsPromotionAndSem = DB::connection('mysql')->select ($sql);
    return view('fa.PromotionAndSemBudget',compact('dsPromotionAndSem'));
}
// =======================================================================================
public function CountWeek($FromYear,$ToYear, $FromWeek, $ToWeek)
{
    if($FromYear == $ToYear){ return $ToWeek -  $FromWeek + 1; }
    else
    {
        return 52 -  $FromWeek  + $ToWeek + 1;
    }
}

// =======================================================================================
public function Test()
{
    $Year = 2020;
    $Week = 39;
    $FromDate =  $this->GetFirstDateOfWeek($Year,$Week);
    $ToDate =   $this->GetLastDateOfWeek($Year,$Week);
    print_r('From Date: '.$FromDate . 'To date : '.$ToDate );
}
// =======================================================================================
public function GetFirstDateOfWeek($Year,$Week)
{
    $dto = new DateTime();
    $dto->setISODate($Year,$Week);
    return $dto->format('Y-m-d');
}
// =======================================================================================
    public function UpdateCollectMoney($SalesChannelID,$ForFromDate,$ForToDate,$Amount)
    {
        $OrzFromDate = $ForFromDate;
        $OrzToDate = $ForToDate;
       //EBAY,Craiglsit, Website (6,7,8). Giữ nguyên ngày From Date to date
        if($SalesChannelID >= 6 && $SalesChannelID <= 8)
        {
            $sql = " update fa_selling_monthly_detail set collected_money = 1
            where and sales_channel in (1,2) date(invoice_date)>= '$OrzFromDate'
            and date(invoice_date)<= '$OrzToDate' and collected_money = 0  ";
            DB::connection('mysql')->select($sql);
        }elseif($SalesChannelID ==4)// Walmart DSV
        {
            $ForFromDate = $this->MoveDate($OrzFromDate,'-',7);// Lùi 7 ngày
            $ForToDate= $this->MoveDate($OrzToDate,'-',7);//  Lùi 7 ngày
            $sql = " update fa_selling_monthly_detail set collected_money = 1
            where and sales_channel in (4) date(invoice_date)>= '$ForFromDate'
            and date(invoice_date)<= '$ForToDate' and collected_money = 0  ";
            DB::connection('mysql')->select($sql);
        }elseif($SalesChannelID == 5 || $SalesChannelID == 9 || $SalesChannelID == 10)// Walmart MKP, FBA,FBM (5,9,10),
        {
            $ForFromDate = $this->MoveDate($OrzFromDate,'-',7);// Lùi thêm 7 ngày
            $ForToDate= $this->MoveDate($OrzToDate,'-',7);//  Lùi 7 thêm ngày
            $sql = " update fa_selling_monthly_detail set collected_money = 1
            where and sales_channel in (5,9,10) date(invoice_date)>= '$ForFromDate'
            and date(invoice_date)<= '$ForToDate' and collected_money = 0  ";
            DB::connection('mysql')->select($sql);
        }elseif($SalesChannelID == 1 || $SalesChannelID == 2 )// AVC-WH/DS
        {
         $ForFromDate = $this->MoveDate($OrzFromDate,'-',89);// từ 60 đến trước 90 ngày để thanh toán cho avc-wh/ds
         $ForToDate = $this->MoveDate($OrzToDate,'-',60);// thanh toán 60 ngày sau ngày invoice

        }elseif($SalesChannelID == 3 )// AVC-DI
        {
         $ForFromDate = $this->MoveDate($OrzFromDate,'-',90);// >= 90 ngày tính từ ngày invoide date để thanh toán cho avc-di
         $ForToDate= $this->MoveDate($OrzToDate,'-',90);//
        }
    }
    // =======================================================================================
    public function GetLastDateOfWeek($Year,$Week)
    {
        $dto = new DateTime();
        $dto->setISODate($Year,$Week);
        $dto->modify('+6 days');
        return $dto->format('Y-m-d');
    }
    public function GetBeginBalaceOfCash($FirstDateOfWeek)
    {
        $BeginBalance = 0;
        $TheDateBefore = date('Y-m-d',strtotime($FirstDateOfWeek. '- 1 days'));
        $TheMonth = date("m", strtotime($TheDateBefore));
        $TheYear = date("Y", strtotime($TheDateBefore));
        $FisrtDateOfMonth = $this->GetFirstDateOfMonth($TheYear,$TheMonth);

        $sql = "select balance_begin from  fa_cashflow_begin
        where capital_account_id =1 and the_month = $TheMonth and the_year = $TheYear ";
        $ds = DB::connection('mysql')->select($sql);
        foreach($ds as $d) { $BeginBalance  = $d->balance_begin; }

        $CurentDate = date('m/d/Y', time());
        $Income = $this->GetAllIncome($FisrtDateOfMonth, $TheDateBefore, $CurentDate );
        $Expensive = $this->GetAllIExpensive($FisrtDateOfMonth, $TheDateBefore, $CurentDate );
        $BeginBalance =$BeginBalance + $Income - $Expensive ;

        return $BeginBalance;
    }
    public function GetDepositForSellingGoods($FirstDateOfWeek,$LastDateOfWeek, $CurentDate)
    {
        $DepositForSellingGoods = 0;

        // Hoàn toàn trong quá khứ -> Tìm trong fa_cashflow_transactions nơi ghi những transaction thực sự đã diễn ra
        if($LastDateOfWeek <= $CurentDate)
        {
            $sql = " select sum(amount) as amount from fa_cashflow_transactions
            where income_expensive_id = 1 and action_date >= '$FirstDateOfWeek' and  action_date <= '$LastDateOfWeek' ";
            $ds = DB::connection('mysql')->select($sql);
            foreach($ds as $d) { $DepositForSellingGoods  = $d->amount; }
        }
        // Hoàn toàn năm trong tương lai -> Tìm trong PU-PLAN -> Chưa có pu plan cho HMD
        elseif($FirstDateOfWeek >= $CurentDate)
        {

        }
        // Nửa quá khứ nửa tương lai -> tính vỡ cả mặt
        else
        {

        }
    }
//     Số tiền khi hàng cập bến
//    Thông tin shipment: shipment`,shipment_containers`,`shipment_containers_details`,
//    thông tin PO , po detail là  purchasingorders_3 và purchasingorderdetails_3
//    GetPaymentRestForSellingGoods($FirstDateOfWeek,$LastDateOfWeek, $CurentDate);
//     // Chi phí vận chuyển hàng đến kho US trong tuần
//    GetLocalTranportationFee($FirstDateOfWeek,$LastDateOfWeek, $CurentDate);// Chi phí vận chuyển hàng từ nơi mua tới kho us
//    GetOceanTranportationFee($FirstDateOfWeek,$LastDateOfWeek, $CurentDate);// Chi phí vận chuyển hàng từ nơi mua tới kho us
//    GetUSTranportationFee($FirstDateOfWeek,$LastDateOfWeek, $CurentDate);// Chi phí vận chuyển hàng từ nơi mua tới kho us
//    // Chi phí bán hàng
//    GetChannelFee($FirstDateOfWeek,$LastDateOfWeek, $CurentDate);
//    GetPromotionFee($FirstDateOfWeek,$LastDateOfWeek, $CurentDate);
//    GetSemFee($FirstDateOfWeek,$LastDateOfWeek, $CurentDate);
//    GetDutyFee($FirstDateOfWeek,$LastDateOfWeek, $CurentDate);
//    GetCommisionFee($FirstDateOfWeek,$LastDateOfWeek, $CurentDate);
//    // OutSource Management Fee
//    GetWareHouseFee($FirstDateOfWeek,$LastDateOfWeek, $CurentDate);
//    GetHandlingFee($FirstDateOfWeek,$LastDateOfWeek, $CurentDate);
//    GetAccoutingFee($FirstDateOfWeek,$LastDateOfWeek, $CurentDate);
//    // Chi phí vận chuyển đến nhà khách hàng
//    GetRetailShipingFee($FirstDateOfWeek,$LastDateOfWeek, $CurentDate);
//    // Tiền thu từ việc bán hàng
//    IncomeFomSellingGoods($FirstDateOfWeek,$LastDateOfWeek, $CurentDate);
//    // Tiền rót vốn đầu tư
//    GetCapitalInvest($FirstDateOfWeek,$LastDateOfWeek, $CurentDate);
//    // Tiền chia lợi nhuận
//    GetSharingProfit($FirstDateOfWeek,$LastDateOfWeek, $CurentDate);


    public function ShowCashFlowInStage($FromYear,$ToYear, $FromWeek, $ToWeek)
    {

    }
    public function CaculateCashFlowInStage($FromYear,$ToYear, $FromWeek, $ToWeek)
    {//$FromYear,$ToYear, $FromWeek, $ToWeek
        // $FromYear =2020;
        // $ToYear=2021;
        // $FromWeek=42;
        // $ToWeek=7;
       // date_default_timezone_set('UTC');

        $WeekCount = $this->CountWeek($FromYear,$ToYear, $FromWeek, $ToWeek);
        // Declare array contain year and week to show CashFlow
        $ArrayYear  = array($WeekCount);
        $ArrayWeek  = array($WeekCount);
        if($FromYear == $ToYear)
        {
            for($i= $FromWeek; $i<= $ToWeek;$i++)
            {
                $ArrayYear[$i-$FromWeek + 1] = $FromYear;
                $ArrayWeek[$i-$FromWeek + 1] = $i;
            }
        }
        else // From year < To year một năm
        {
            $Count = 0;
            for($i= $FromWeek ; $i <= 52; $i++)
            {
                $ArrayYear[$i-$FromWeek +1 ] = $FromYear;
                $ArrayWeek[$i-$FromWeek + 1] = $i;
                $Count++;
            }

            for($i= 1; $i <= $ToWeek; $i++)
            {
                $ArrayYear[$Count+$i] = $ToYear;
                $ArrayWeek[$Count+$i] = $i;
            }
        }

        for($i= 1; $i <= $WeekCount ; $i++)
        {

            $FirstDateOfWeek = $this->GetFirstDateOfWeek($ArrayYear[$i],$ArrayWeek[$i]);
            $LastDateOfWeek = $this->GetLastDateOfWeek($ArrayYear[$i],$ArrayWeek[$i]);
            // tiền tồn đầu tuần
            $Balance = $this->GetBeginBalaceOfCash($FirstDateOfWeek);
            // Chi phí mua hàng trong tuần
            $DepositForSellingGoods = $this->GetDepositForSellingGoods($FirstDateOfWeek,$LastDateOfWeek);
            $PaymentRestForSellingGoods = $this->GetPaymentRestForSellingGoods($FirstDateOfWeek,$LastDateOfWeek);// Số tiền khi hàng cập bến

            // Chi phí vận chuyển hàng đến kho US trong tuần
            $LocalTranportationFee = $this->GetLocalTranportationFee($FirstDateOfWeek,$LastDateOfWeek);// Chi phí vận chuyển hàng từ nơi mua tới kho us
            $OceanTranportationFee = $this->GetOceanTranportationFee($FirstDateOfWeek,$LastDateOfWeek);// Chi phí vận chuyển hàng từ nơi mua tới kho us
            $USTranportationFee= $this->GetUSTranportationFee($FirstDateOfWeek,$LastDateOfWeek);// Chi phí vận chuyển hàng từ nơi mua tới kho us

            // Chi phí bán hàng
            $ChannelFee = $this->GetChannelFee($FirstDateOfWeek,$LastDateOfWeek);
            $PromotionFee = $this->GetPromotionFee($FirstDateOfWeek,$LastDateOfWeek);
            $SemFee = $this->GetSemFee($FirstDateOfWeek,$LastDateOfWeek);
            $DutyFee = $this->GetDutyFee($FirstDateOfWeek,$LastDateOfWeek);
            $CommisionFee = $this->GetCommisionFee($FirstDateOfWeek,$LastDateOfWeek);
            // OutSource Management Fee
            $WareHouseFee = $this->GetWareHouseFee($FirstDateOfWeek,$LastDateOfWeek);
            $HandlingFee = $this->GetHandlingFee($FirstDateOfWeek,$LastDateOfWeek);
            $AccoutingFee = $this->GetAccoutingFee($FirstDateOfWeek,$LastDateOfWeek);
            // Chi phí vận chuyển đến nhà khách hàng
            $RetailShipingFee = $this->GetRetailShipingFee($FirstDateOfWeek,$LastDateOfWeek);
            // Tiền thu từ việc bán hàng
            $IncomeFomSellingGoods = $this->IncomeFomSellingGoods($FirstDateOfWeek,$LastDateOfWeek);
            // Tiền rót vốn đầu tư
            $CapitalInvest =$this->GetCapitalInvest($FirstDateOfWeek,$LastDateOfWeek);
            // Tiền chia lợi nhuận
            $SharingProfit = $this->GetSharingProfit($FirstDateOfWeek,$LastDateOfWeek);
        }// For

        $this->ShowCashFlowInStage($FromYear,$ToYear, $FromWeek, $ToWeek);
    }
// -----------------------------------------------------------------------------------------------
public function LoadPuPlan()
{
    $sql = " select pu_po_estimates.id, the_year as order_year , the_week as order_week ,pu_vendors.vendor_name,pu_po_estimates.lead_time ,
    pu_po_estimates.expect_load_date, pu_po_estimates.expect_etd_date, pu_po_estimates.expect_eta_date, pu_po_estimates.start_selling_date,
    pu_po_estimates.end_selling_date
    from pu_po_estimates left join pu_vendors on pu_po_estimates.vendor_id = pu_vendors.id
    order by the_year,the_week " ;
    $dsPuPlan = DB::connection('mysql')->select($sql);
   // return view('fa.ImportPuPlan.',compact('dsPuPlan'));

    return view('fa.CashflowImportFile',compact('dsPuPlan'));
   // return view('fa.test');
}
// -----------------------------------------------------------------------------------------------
public function InsertToForeCast($TheYear,$TheWeek,$sku,$ForecastNumber,$Channel)
{//$TheYear,$TheWeek,$sku,$ForecastNumber,$Channel
//   $TheYear =2020;
//   $TheWeek =37;
//   $sku ='AX59';
//   $ForecastNumber =100;
//   $Channel = 2;

  $TheQuantityPerDay = $ForecastNumber/7; // chia ra thanh 7 ngay trong tuan

  $DateInWeek = array();
  $StartDate = $this->GetFirstDateOfWeek($TheYear, $TheWeek);

  for($i=0; $i<=6;$i++)
  {
    $TheDate =  $StartDate;

    $sql = " select id as MyCount from sal_forecasts where sku = '$sku' and date(the_date) = date('$TheDate') ";
    $ForeCastID = $this->IsExist('mysql',$sql);

    if($ForeCastID != 0 )// nếu đã tồn tại Forecast master
    {
        // print_r('Đã tồn tại master');
        // print_r('<br>' );

        $sql = " select id as MyCount from sal_forecast_details where 	sales_forecast_id =  $ForeCastID
        and channel_id = $Channel ";
        if($this->IsExist('mysql',$sql) != 0)// Nếu đã tồn tại ở Forcast detail-> xóa đi ghi lại
        {
        //  print_r('Đã tồn tại detail');
        //  print_r('<br>' );

         $sql = " delete from sal_forecast_details where sales_forecast_id = $ForeCastID and channel_id = $Channel ";
         db::connection('mysql')->select($sql);
         //-> ghi vào Forecast detail
         DB::connection('mysql')->table('sal_forecast_details')->insert(
         ['sales_forecast_id' => $ForeCastID,'channel_id'=>$Channel,'quantity'=>$TheQuantityPerDay]);
        }
        else
        {
            // print_r('Chưa tồn tại detail');
            // print_r('<br>' );
         DB::connection('mysql')->table('sal_forecast_details')->insert(
         ['sales_forecast_id' => $ForeCastID,'channel_id'=>$Channel,'quantity'=>$TheQuantityPerDay]);
        }

    }else// Chưa tồn tại ở forcast master -> ghi vào Forecast master-> ghi vào forecast detail
    {
    //   print_r('Chưa tồn tại master');
    //   print_r('<br>' );
      $ForeCastID = DB::connection('mysql')->table('sal_forecasts')->insertGetId(
      ['the_date'=> $TheDate, 'sku'=>$sku]);

      DB::connection('mysql')->table('sal_forecast_details')->insert(
      ['sales_forecast_id' => $ForeCastID,'channel_id'=>$Channel,'quantity'=>$TheQuantityPerDay]);
    }
    $StartDate = date('Y-m-d',strtotime($StartDate . '+'.' 1 days'));
  }// For
}
}

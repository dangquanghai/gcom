<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use DB;
use Carbon\Carbon;
use DateTime;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Reader\IReadFilter;
use Validator;


class pu_po_estimateController extends Controller
{
  private $PlanID =0;
  private $VendorID = 0;

  private $Eta ;

  private $POEstimateID=0;
  private $avcwh_level=0;

  private $StartSellingDate;
  private $EndSellingDate;
  private $EndSellingDateOnFBA;

  // Các số liệu tại thời điểm chạy chức năng này
  private $BalanceOnY4A=0;
  private $BalanceOnAVC_WH=0;
  private $BalanceOnFBA=0;

  private $PipeLine = 0;

  private $ForeCastOnY4A=0;
  private $ForeCastOnAVC_WH=0;
  private $ForeCastOnFBA=0;

  private $ForeCastOnY4AInStage=0;
  private $ForeCastOnAVC_WHInStage=0;
  private $ForeCastOnFBAInStage=0;



  private $POID= 0;
  private $CurrentDate;

  private $TwoDaysBefore;
  private $CurrentWeek,$CurrentYear ;
  private $FromWeek,$FromYear,$ToWeek,$ToYear;

  public $CellValue3 =0;
  public $cellValue5N = 0;
  public $cellValue6N = 0;
// -----------------------------------------------------------------------------------------------
  public function index()
  {
     return view('PU.PUImport');
  }
// -----------------------------------------------------------------------------------------------
  public function puImport(Request $request)
  {
      $this->CurrentDate = date("Y-m-d");

      $SalesChanelFBA = 1;
      $SalesChanelAVC_WH = 3;

      $CurrentWeeks = DB::connection('mysql')->select(" select week(CURRENT_DATE()) as week");
       foreach( $CurrentWeeks as $Week ){
         $CurentWeek  = $Week->week;
       }
       $FromYear =2020;
       $WeekCount = 39;// Số tuần cố định trong sheet data Forecast
       $ArrWeek   = array($WeekCount);
       $ArrYear = array($WeekCount);

       $ArrayFBM  = array($WeekCount);
       $ArrayFBA  = array($WeekCount);
       $ArrayAVC_WH  = array($WeekCount);
       $ArrayAVC_DS  = array($WeekCount);
       $ArrayEbay  = array($WeekCount);
       $ArrayWM  = array($WeekCount);


      $validator = Validator::make($request->all(),[
          'file'=>'required|max:5000|mimes:xlsx,xls,csv'
          ]);
      if($validator->passes()){
         $file = $request->file('file');
         $reader = new \PhpOffice\PhpSpreadsheet\Reader\Xlsx();

         // Import sheet thu nhat

         $reader->setLoadSheetsOnly(["LOI", "sal_loi"]);
         $spreadsheet = $reader->load($file);
         $highestRow = $spreadsheet->getActiveSheet()->getHighestRow();
         DB::table('sal_lois')->delete();
         for($i=2; $i <= $highestRow; $i++){
              $cellValue1 = $spreadsheet->getActiveSheet()->getCellByColumnAndRow( 1,$i)->getValue();
              $cellValue2 = $spreadsheet->getActiveSheet()->getCellByColumnAndRow( 2,$i)->getValue();
              $cellValue3 = $spreadsheet->getActiveSheet()->getCellByColumnAndRow( 3,$i)->getValue();
              $cellValue4 = $spreadsheet->getActiveSheet()->getCellByColumnAndRow( 4,$i)->getValue();

              DB::connection('mysql')->table('sal_lois')->insert(
                 ['sku'=> $cellValue1, 'avcwh_level'=>$cellValue2,'fba_level'=>$cellValue3,'y4a_level'=>$cellValue4]
             );
         }

          // Import sheet PU PLAN
          $reader->setLoadSheetsOnly(["PU PLAN", "pu plan"]);
          $spreadsheet = $reader->load($file);
          $highestRow = $spreadsheet->getActiveSheet()->getHighestRow();
          DB::table('pu_plannings')->delete();
          for($i=2; $i <= $highestRow; $i++){
              $cellValue1 = $spreadsheet->getActiveSheet()->getCellByColumnAndRow( 1,$i)->getValue();
              $cellValue2 = $spreadsheet->getActiveSheet()->getCellByColumnAndRow( 2,$i)->getValue();
              $cellValue3N = $spreadsheet->getActiveSheet()->getCellByColumnAndRow( 3,$i)->getFormattedValue();//order_date
              $cellValue4 = $spreadsheet->getActiveSheet()->getCellByColumnAndRow( 4,$i)->getValue();
              $cellValue5N = $spreadsheet->getActiveSheet()->getCellByColumnAndRow( 6,$i)->getFormattedValue();//eta
              $cellValue6N = $spreadsheet->getActiveSheet()->getCellByColumnAndRow( 7,$i)->getFormattedValue();//end_selling_date

              DB::connection('mysql')->table('pu_plannings')->insert(
                  ['order_week'=> $cellValue1, 'at_year'=>$cellValue2,'order_date'=>$cellValue3N,'vendor_id'=>$cellValue4,
                  'eta'=>$cellValue5N, 'end_selling_date'=>$cellValue6N]
              );
          }
           // Import sales forecast
           DB::connection('mysql')->table('sal_forecasts')->delete();
           DB::connection('mysql')->table('sal_forecast_details')->delete();

           $reader->setLoadSheetsOnly(["FORECAST", "Forecast"]);
           $spreadsheet = $reader->load($file);
           $highestRow = $spreadsheet->getActiveSheet()->getHighestRow();

           $RowOfStartingData = 6;
           $ColOfStartingData = 7;

           // tìm điểm đầu tiên để xác định tuần đầu tiên
           $mystring = $spreadsheet->getActiveSheet()->getCellByColumnAndRow( $ColOfStartingData , $RowOfStartingData-1)->getValue();
           $FirstWeek= (int)substr($mystring,1);
           $TheWeek= $FirstWeek;
           $TheYear = $FromYear;

           for($i =1 ;$i<= $WeekCount;$i++)
           {
              $ArrayWeek[$i]=  $TheWeek;
              $ArrayYear[$i]= $TheYear;
              $TheWeek= $TheWeek+1;
              if($TheWeek == 52)
              {
                $TheWeek=1;
                $TheYear=$TheYear+1;
              }
           }

           // Bắt đầu đọc data từ sheet FC
           for($row=$RowOfStartingData; $row <= $highestRow; $row++)
           {
              $sku = $spreadsheet->getActiveSheet()->getCellByColumnAndRow( 3,$row)->getValue();// 3 là cột chứa sku

              // Ghi vào bảng sal_forecasts FBM=> Channel =2
              $ForecastMasterID =DB::connection('mysql')->table('sal_forecasts')->insertGetId(
                  ['chanel_id'=> 2, 'sku'=>$sku]
              );

              // Đọc data phần FBM và ghi vào bảng sal_forecast_details
              $k=1;
              for($col=$ColOfStartingData ; $col <$ColOfStartingData +  $WeekCount ; $col++){
                  $ArrayFBM[$k] =$spreadsheet->getActiveSheet()->getCellByColumnAndRow( $col,$row)->getValue();
                  if($ArrayFBM[$k]>0){
                    $this->InsertToForeCastDetail($ArrayYear[$k],$ArrayWeek [$k], $ArrayFBM[$k],$ForecastMasterID);
                  }
                 $k++;
              }

              // Ghi vào bảng sal_forecasts FBA=> Channel =1
              $ForecastMasterID =DB::connection('mysql')->table('sal_forecasts')->insertGetId(
                  ['chanel_id'=> 1, 'sku'=>$sku]
              );

              // Đọc data phần FBA và ghi vào bảng sal_forecast_details
              $k=1;
              for($col = $ColOfStartingData + $WeekCount ; $col < $ColOfStartingData +  2* $WeekCount ; $col++){
                  $ArrayFBA[$k] = $spreadsheet->getActiveSheet()->getCellByColumnAndRow( $col,$row)->getValue();
                  if ($ArrayFBA[$k] >0  ){
                     $this->InsertToForeCastDetail($ArrayYear[$k],$ArrayWeek [$k], $ArrayFBA[$k],$ForecastMasterID);
                 }
                 $k++;
              }

              // Ghi vào bảng sal_forecasts AVC-WH=> Channel =3
              $ForecastMasterID =DB::connection('mysql')->table('sal_forecasts')->insertGetId(
                  ['chanel_id'=> 3, 'sku'=>$sku]
              );
              // Đọc data phần FBA và ghi vào bảng sal_forecast_details
              $k=1;
              for($col=$ColOfStartingData +2* $WeekCount ; $col <$ColOfStartingData +  3* $WeekCount ; $col++){
                  $ArrayAVC_WH[$k] = $spreadsheet->getActiveSheet()->getCellByColumnAndRow( $col,$row)->getValue();
                  if ($ArrayAVC_WH[$k] >0  ){
                     $this->InsertToForeCastDetail($ArrayYear[$k],$ArrayWeek [$k], $ArrayAVC_WH[$k],$ForecastMasterID);
                 }
                 $k++;
              }

               // Ghi vào bảng sal_forecasts AVC-DS=> Channel =9
               $ForecastMasterID =DB::connection('mysql')->table('sal_forecasts')->insertGetId(
                  ['chanel_id'=> 9, 'sku'=>$sku]
              );
              // Đọc data phần FBA và ghi vào bảng sal_forecast_details
              $k=1;
              for($col=$ColOfStartingData +3* $WeekCount ; $col <$ColOfStartingData + 4* $WeekCount ; $col++){
                  $ArrayAVC_DS[$k] = $spreadsheet->getActiveSheet()->getCellByColumnAndRow( $col,$row)->getValue();
                  if ($ArrayFBA[$k] >0  ){
                     $this->InsertToForeCastDetail($ArrayYear[$k],$ArrayWeek [$k], $ArrayAVC_DS[$k],$ForecastMasterID);
                 }
                 $k++;
              }

              // Ghi vào bảng sal_forecasts EBAY=> Channel =10
              $ForecastMasterID =DB::connection('mysql')->table('sal_forecasts')->insertGetId(
              ['chanel_id'=> 10, 'sku'=>$sku]
              );
              // Đọc data phần FBA và ghi vào bảng sal_forecast_details
              $k=1;
              for($col=$ColOfStartingData +5* $WeekCount ; $col <$ColOfStartingData + 6* $WeekCount ; $col++){
                  $ArrayAVC_DS[$k] = $spreadsheet->getActiveSheet()->getCellByColumnAndRow( $col,$row)->getValue();
                  if ( $ArrayFBA[$k] >0  ){
                     $this->InsertToForeCastDetail($ArrayYear[$k],$ArrayWeek [$k], $ArrayAVC_DS[$k],$ForecastMasterID);
                 }
                 $k++;
              }

              // Ghi vào bảng sal_forecasts WM=> Channel =11
              $ForecastMasterID =DB::connection('mysql')->table('sal_forecasts')->insertGetId(
                  ['chanel_id'=> 11, 'sku'=>$sku]
                  );
                  // Đọc data phần FBA và ghi vào bảng sal_forecast_details
                  $k=1;
                  for($col=$ColOfStartingData + 6* $WeekCount ; $col <$ColOfStartingData +  7*$WeekCount ; $col++){
                      $ArrayWM[$k] = $spreadsheet->getActiveSheet()->getCellByColumnAndRow( $col,$row)->getValue();
                      if ($ArrayFBA[$k] >0  ){
                         $this->InsertToForeCastDetail($ArrayYear[$k],$ArrayWeek [$k], $ArrayWM[$k],$ForecastMasterID);
                     }
                     $k++;
                  }

          }

      }// pass

     // return redirect()->back()->with(['success'=>'File Upload successfuly.']);
  }
// -----------------------------------------------------------------------------------------------
function is_not_empty_string($str) {
  if (is_string($str) && trim($str, " \t\n\r\0") !== '')
      return true;
  else
      return false;
}

// -----------------------------------------------------------------------------------------------
public function InsertToForeCastDetail($TheYear,$TheWeek,$TheQuantity,$ForeCastID)
{
  $TheQuantityPerDay = $TheQuantity/7; // chia ra thanh 7 ngay trong tuan
  $ret= array();
  $StartDate = new DateTime();
  $StartDate->setISODate($TheYear, $TheWeek);
  $StartDate->setISODate((int)$StartDate->format('o'), $TheWeek, 0);
  $ret[1] = $StartDate->format('Y-m-d');

  DB::connection('mysql')->table('sal_forecast_details')->insert(
    ['sales_forecast_id' => $ForeCastID,'the_date'=> $ret[1],'quantity'=>$TheQuantityPerDay]);

  for($i=2;$i<=7;$i++)
  {
    $ret[$i]= date('Y-m-d',strtotime( $ret[$i-1]. '+'.' 1 days'));
    DB::connection('mysql')->table('sal_forecast_details')->insert(
    ['sales_forecast_id' => $ForeCastID,'the_date'=> $ret[$i],'quantity'=>$TheQuantityPerDay]);
  }
}
// -----------------------------------------------------------------------------------------------
  public  function ShowPODetail($MyPOID)
  {

  $PODetails = DB::connection('mysql')->select("select pu_po_estimate_details.sku, products.title ,
  GetSellType(pu_po_estimate_details.sell_type)as sell_type,
  GetLifeCycle( pu_po_estimate_details.life_cycle) as life_cycle , pu_po_estimate_details.balance,
  pu_po_estimate_details.balance_at_selling,  pu_po_estimate_details.fob_price ,
  pu_po_estimate_details.moq, pu_po_estimate_details.quantity
  from pu_po_estimate_details inner JOIN
  products on pu_po_estimate_details.sku = products.product_sku
  WHERE pu_po_estimate_details.po_estimate_id = $MyPOID ");


  return view('PU.PU-PODetail',compact('PODetails'));

  }

  public function ShowPOList()
  {
    $POs = DB::connection('mysql')->select("select pu_po_estimates.id , vendors.title , pu_po_estimates.the_week,  pu_po_estimates.the_year,order_date,expect_eta,end_selling_date
    from pu_po_estimates INNER JOIN   vendors on pu_po_estimates.vendor_id = vendors.id");
    //return view('PU.PU-PO',compact('POs'));
    return view('PU.PU-POList',compact('POs'));

  }

  public function test()
  {
    //$Products = DB::select("select id from products");
    //dd($Products);
    //$this->CurrentDate = date("Y-m-d");
    //dd($this->CurrentDate);
    $this->GetRangeToGetDataForecast();

  }

  public  function testnew($MyPOID)
  {

  $PODetails = DB::select("select pu_po_estimate_details.sku, products.title ,GetSellType(pu_po_estimate_details.sell_type)as sell_type,
  GetLifeCycle( pu_po_estimate_details.life_cycle) as life_cycle , pu_po_estimate_details.balance, pu_po_estimate_details.balance_at_selling,
  pu_po_estimate_details.fob_price , pu_po_estimate_details.moq, pu_po_estimate_details.quantity
  from pu_po_estimate_details inner JOIN
  products on pu_po_estimate_details.sku = products.product_sku
  WHERE pu_po_estimate_details.po_estimate_id = $MyPOID ");

  return response()->json($PODetails);
  }

// ------------------------------------------------------------------------------
  public function ShowUseContainer() {
    return view('layouts.PUShowContainer');
  }
  // ------------------------------------------------------------------------------
  public function GetRangeToGetDataForecast()// xác định thời gian cần lấy data forecast
  {
    $this->CurrentDate = date("Y-m-d");
    $CurrentWeeks = DB::connection('mysql_it')->select(" select week(CURRENT_DATE()) as week");

    foreach( $CurrentWeeks as $Week ){
       $CurentWeek  = $Week->week;
     }

    $CurentYear = date('Y', strtotime($this->CurrentDate));
    $RuleLeadTime = 1;// set rule tính từ tuần hiện tại thì chỉ lấy data FC của 1 tuần sau
    $RuleLenthOfWeek = 21;// set rule độ dài tuần của data forecast là 21 tuần là tối đa

    // Lùi lại 2 ngày để thống nhất lấy data 2 ngày trước đó vì số tồn kho của avc wh chỉ đúng với số 2 ngày so với ngày hiện tại
    $this->TwoDaysBefore = date('Y-m-d',strtotime($this->CurrentDate. '- 2 days'));

    $this->FromWeek = $CurentWeek;
    $this->FromYear = $CurentYear;

    for($i=1;$i<= $RuleLeadTime;$i++){
      if ($CurentWeek == 52){
        $CurentWeek = 1;
        $CurentYear = $CurentYear+1;
      }
      else{
        $CurentWeek = $CurentWeek +1;
      }
    }



    for($i=1;$i<=$RuleLenthOfWeek;$i++){
      if ($CurentWeek==52){
        $CurentWeek =1;
        $CurentYear++;
      }
      else{
        $CurentWeek ++;
      }
    }
    $this->ToWeek=$CurentWeek;
    $this->ToYear = $CurentYear;

    print_r('CurrentDate:'.$this->CurrentDate );
    print_r('<br>');

    print_r('FromWeek'. $this->FromWeek );
    print_r('<br>');
    print_r('FromYear'.$this->FromYear );
    print_r('<br>');

    print_r('ToWeek'.$this->ToWeek );
    print_r('<br>');
    print_r('ToYear'.$this->ToYear );
    print_r('<br>');

  }

  // -----------------------------------------------------------------------------------------------
/*  public function InsertToForeCastDetail($TheYear,$TheWeek,$TheQuantity,$ForeCastID)
  {
    $ret= array();
    $StartDate = new DateTime();
    $StartDate->setISODate($TheYear, $TheWeek);
    $StartDate->setISODate((int)$StartDate->format('o'), $TheWeek, 0);
    $ret[1] = $StartDate->format('Y-m-d');

    DB::connection('mysql')->table('sal_forecast_details')->insert(
      ['sales_forecast_id' => $ForeCastID,'the_date'=> $ret[1],'quantity'=>$TheQuantity]);

    for($i=2;$i<=7;$i++){
      $ret[$i]= date('Y-m-d',strtotime( $ret[$i-1]. '+'.' 1 days'));
      DB::connection('mysql')->table('sal_forecast_details')->insert(
      ['sales_forecast_id' => $ForeCastID,'the_date'=> $ret[$i],'quantity'=>$TheQuantity]);
    }
  }
*/

  // -----------------------------------------------------------------------------------------------
  public function MoveSomeTablesFromBEToBA()
  {
    // 1. products
     // move Product
     DB::connection('mysql')->select(" delete from prd_product");
     DB::connection('mysql')->select("ALTER TABLE prd_product AUTO_INCREMENT = 1");

     $Products = DB::connection('mysql_it')->select(" select id, parent_id , title ,  product_sku , life_cycle from products ");
     foreach( $Products as  $Product){
       $title = $Product->title;
       if(strpos($title,"'")>0){$title = str_replace("'", "\\'", $title); }

       $sql= " insert into prd_product( id,parent_id,title,product_sku,life_cycle)
       values(". $Product->id . ",". $Product->parent_id .",'".$title ."','"
       . $Product->product_sku ."'," .$Product->life_cycle .")";
       DB::connection('mysql')->select($sql);
     }
    // 2. product_manufactures
    DB::connection('mysql')->select(" delete from product_manufactures");
    DB::connection('mysql')->select("ALTER TABLE product_manufactures AUTO_INCREMENT = 1");

    $ds = DB::connection('mysql_it')->select(" select id, product_id , manufacture_id ,moq from product_manufactures ");
    foreach( $ds as  $d){
      $sql= " insert into product_manufactures( id, product_id , manufacture_id ,  moq )
      values(". $d->id . ",". $d->product_id ."," . $d->manufacture_id ."," .$d->moq.")";
      DB::connection('mysql')->select($sql);
    }

    // 3. manufacturers_3
    DB::connection('mysql')->select(" delete from manufacturers_3");
    DB::connection('mysql')->select("ALTER TABLE manufacturers_3 AUTO_INCREMENT = 1");

    $ds = DB::connection('mysql_it')->select(" select id, vendor_id , title  from manufacturers_3 ");
    foreach( $ds as  $d){
      $Title= str_replace("'","",$d->title);
      $sql= " insert into manufacturers_3( id, vendor_id , title  )
      values(". $d->id . ",". $d->vendor_id .",'" .  $Title ."')";
      DB::connection('mysql')->select($sql);
    }
    // 4.productcombo
    DB::connection('mysql')->select(" delete from productcombo");
    DB::connection('mysql')->select("ALTER TABLE productcombo AUTO_INCREMENT = 1");

    $ds = DB::connection('mysql_it')->select(" select id, 	product_id , child_id , quantity  from productcombo ");
    foreach( $ds as  $d){
      $sql= " insert into productcombo( id, product_id , child_id, quantity )
      values(". $d->id . ",". $d->product_id ."," . $d->child_id ."," .$d->quantity. ")";
      DB::connection('mysql')->select($sql);
    }
    // 5.vendors
    DB::connection('mysql')->select(" delete from vendors");
    DB::connection('mysql')->select("ALTER TABLE vendors AUTO_INCREMENT = 1");

    $ds = DB::connection('mysql_it')->select(" select id,agent_id,title  from vendors ");
    foreach( $ds as  $d){
      $sql= " insert into vendors( id, agent_id , title )
      values(". $d->id . ",". $d->agent_id .",'" . $d->title . "')";
      DB::connection('mysql')->select($sql);
    }
  }
// -----------------------------------------------------------------------------------------------
/*
Đầu vào: Data Forecast của sales, Kế hoạch mua hang của PU
Đầu ra: Tạo ra các PO estimate cho các tuần chưa tạo, mỗi tuần, mỗi vendor nếu có trong plan sẽ tạo một PO   */
public function CreateAllPOEstimate()
{
  ini_set('memory_limit','2048M');
  set_time_limit(52000);
  // Chuyển một số data cần thiết từ Backend qua
  $this->MoveSomeTablesFromBEToBA();
  // xóa toàn bộ po estimate
  DB::connection('mysql')->table('pu_po_estimates')->delete();
  DB::connection('mysql')->table('pu_po_estimate_details')->delete();

  // Load toàn bộ plan của PU những tuần chưa tạo PO estimate
  //$Plans =   DB::table('pu_plannings') ->select ('id','vendor_id','order_week','order_date','eta','end_selling_date')
  $Plans =   DB::connection('mysql')->select('select id,order_week,order_date,at_year,vendor_id,eta,end_selling_date
  from pu_plannings where id = 197');
  $i=1;
  $this->CurrentDate = date("Y-m-d");
  $this->TwoDaysBefore = date('Y-m-d',strtotime($this->CurrentDate. '- 2 days'));

  foreach ($Plans as $plan) {
    // Tính tồn kho cuối ngày trước ngày dự tính bán một ngày
    // $this->CreatePOForOneWeek($plan->id, $plan->vendor_id, $plan->order_week,$plan->order_date,$plan->at_year,$plan->eta, $plan->end_selling_date);
    print_r('Bắt đầu tạo po:'.date('Y-m-d H:i:s'));
    print_r('<br>');
    print_r('------------------Tạp PO thứ' .$i );
    print_r('<br>');
    $this->CreatePOForOneWeekNew($plan->id, $plan->vendor_id, $plan->order_week,$plan->order_date,$plan->at_year,$plan->eta, $plan->end_selling_date);
    print_r('-------------------Kết thúc một PO '.$i) ;
    print_r('<br>');
    print_r('Bắt đầu tạo po:'.date('Y-m-d H:i:s'));
    print_r('<br>');
    $i++;
  }
  $sql = "  pu_po_estimates set status_id = 2 where status_id = 1 ";
  DB::connection('mysql')->select($sql);

}
  public function CreatePOForOneWeekNew($PlanID, $VendorID,$TheWeek,$OrderDate,$TheYear,$eta,$EndSellingDate)
  {
    // Tạo PO master
    // Gia tăng ngày cuối cho kênh FBA thêm  2 tuần tương đương 3 tuần
    $StartSellingDate = date('Y-m-d',strtotime($eta. '+ 8 days'));// 7 ngày từ cảng về kho, 1 ngày vì ngày hôm sau mới bán
    // Create PO master
    $StatusID = 1;
    $this->POID = DB::connection('mysql')->table('pu_po_estimates')->insertGetId(
    ['vendor_id' =>  $VendorID,'the_week'=>$TheWeek,'order_date'=>$OrderDate,'expect_eta'=>$eta,
    'end_selling_date'=>$EndSellingDate ,'the_year' => $TheYear,'plan_id'=>$PlanID, 'status_id'=>$StatusID]);

    // Load danh sách product hợp lệ có forecast tương ứng với vendor đã được break ra một lần
    $ProductListAvailables= DB::connection('mysql')->select ("
      select products.product_sku  as sku, products.sell_type, products.sell_status,
      products.fob_price,products.appotion_price,product_manufactures.moq , products.life_cycle  from products INNER JOIN
      product_manufactures on products.id = product_manufactures.product_id INNER JOIN
      manufacturers_3 on product_manufactures.manufacture_id = manufacturers_3.id INNER JOIN
      vendors on manufacturers_3.vendor_id =vendors.id
      where manufacturers_3.vendor_id = $VendorID
      and products.published = 1
      and products.purchasing = 1
      and products.product_sku = 'SX2G'
      and products.sell_type in (1,4)
      and products.product_sku in
      (
      select DISTINCT(sal_forecasts.sku)as product_sku  from sal_forecasts INNER JOIN
      sal_forecast_details on sal_forecasts.id = sal_forecast_details.sales_forecast_id
      where date(sal_forecast_details.the_date) >= date('$StartSellingDate')
      and date(sal_forecast_details.the_date) <= date('$EndSellingDate')
      )
union
      select p2.product_sku  as sku, p2.sell_type, p2.sell_status,
      p2.fob_price,p2.appotion_price ,product_manufactures.moq,p2.life_cycle
      from productcombo INNER JOIN
      products on productcombo.product_id = products.id  INNER JOIN
      products p2 on productcombo.child_id = p2.id INNER JOIN
      product_manufactures on p2.id = product_manufactures.product_id INNER JOIN
      manufacturers_3 on product_manufactures.manufacture_id = manufacturers_3.id INNER JOIN
      vendors on manufacturers_3.vendor_id =vendors.id
      where manufacturers_3.vendor_id = $VendorID
      and products.published = 1
      and products.purchasing = 1
      and p2.product_sku = 'SX2G'
      and products.sell_type in( 2,3)
      and p2.product_sku in
      (
      select DISTINCT(sal_forecasts.sku) as product_sku  from sal_forecasts INNER JOIN
      sal_forecast_details on sal_forecasts.id = sal_forecast_details.sales_forecast_id
      where date(sal_forecast_details.the_date) >= date('$StartSellingDate')
      and date(sal_forecast_details.the_date) <= date('$EndSellingDate')
     )
");
//  --and p2.product_sku = 'SX2G'
  foreach($ProductListAvailables as $ProductListAvailable){
    $EndBalance=0;
    // Bắt đầu vòng lặp từ ngày đó đến ngày dự kiến hàng về để tính số tồn đến hết ngày đó thành tồn đầu của ngày bắt đầu bán

    $myDate = $this->TwoDaysBefore;
    print_r('Bắt đầu tính số tồn kho ban đầu ');
    print_r('<br>');
    print_r('time'.date('Y-m-d H:i:s'));
    print_r('<br>');
    // Tính số tồn kho đầu ngày cuả 3 kênh

    $this->GetBalanceBeginningOnTheDate( $ProductListAvailable->sku, $myDate );
    print_r('Tính xong tồn kho ban đầu');
    print_r('<br>');
    print_r('time'.date('Y-m-d H:i:s'));
    print_r('<br>');
    $TotalBalanceNow = $this->BalanceOnY4A + $this->BalanceOnAVC_WH + $this->BalanceOnFBA;

    print_r('Bắt đầu vào vòng để tính ra số tồn cuối của ngày  ');
    print_r('<br>');
    print_r('time'.date('Y-m-d H:i:s'));
    print_r('<br>');
    while($myDate <  $StartSellingDate ){

      // Tính số FC của ngày đó trên 3 kênh
      $this->GetForecastQuantityOnTheDate($ProductListAvailable->sku,$myDate);

      // Tính số Pipeline của ngày hôm trước
      $DateForePipeLine = date('Y-m-d',strtotime($myDate. '+'. '-1 days'));
      $this->GetPipeLineOnTheDate($ProductListAvailable->sku,$DateForePipeLine);


      $this->BalanceOnFBA =round( max($this->BalanceOnFBA   - $this->ForeCastOnFBA,0),0);
      $this->BalanceOnAVC_WH =round(max($this->BalanceOnAVC_WH - $this->ForeCastOnAVC_WH,0),0);
      $this->BalanceOnY4A = round(max( $this->BalanceOnY4A- $this->ForeCastOnY4A,0),0);

      $EndBalance =  $this->BalanceOnFBA + $this->BalanceOnAVC_WH +$this->BalanceOnY4A +$this->PipeLine;

      $myDate = date('Y-m-d',strtotime($myDate. '+ 1 days'));
    }
    print_r('Kết thúc tính tồn cuối vào ngày trước ngày dự kiến bán 1 ngày chính là ngày ETA');
    print_r('<br>');
    print_r('time'.date('Y-m-d H:i:s'));
    print_r('<br>');

    // Tính số Forecast cho giai đoạn từ ngày dự kiến bắt đầu bán đến ngày dự kiến kết thúc bán
    $this->GetForecastQuantityInstage( $ProductListAvailable->sku, $myDate,$EndSellingDate );

    print_r('Kết thúc tính tồn cuối vào ngày trước ngày dự kiến bán 1 ngày chính là ngày ETA');
    print_r('<br>');
    print_r('time'.date('Y-m-d H:i:s'));
    print_r('<br>');

      // Tính số đã đưa vào estimate
      $Estimateds= DB::connection('mysql')->select (
      "select sum(if(pu_po_estimate_product_details.quantity is null ,0,pu_po_estimate_product_details.quantity)) as quantity
      from pu_po_estimates INNER JOIN pu_po_estimate_product_details on pu_po_estimates.id = pu_po_estimate_product_details.id
      where pu_po_estimates.id < $this->POID and pu_po_estimates = 1 ");
      foreach($Estimateds as $Estimated){
        $EstimatedQuantity = $Estimated->quantity;
      }
      $SuggestOrder = max( $EndBalance -($this->ForeCastOnY4AInStage + $this->ForeCastOnAVC_WHInStage + $this->ForeCastOnFBAInStage +$EstimatedQuantity ),0);

      if( $SuggestOrder>0){
        // Lay toi thieu 30, lam tron va lay theo moq
        $SuggestOrder=max($SuggestOrder,30);
        $SuggestOrder=max($SuggestOrder,$ProductListAvailable->moq);
        $SuggestOrder=(int)$SuggestOrder;
        $mystring = (string)$SuggestOrder;
        $myNumber = (int)substr($mystring, -1);
        if ($myNumber!=0 ){
          if($myNumber>=5){
            $NewNum =10-$myNumber;
            $SuggestOrder = $SuggestOrder + $NewNum;
          }else{
            $SuggestOrder= $SuggestOrder- $myNumber;
          }
        }
      }//if $SuggestOrder>0


      if( $SuggestOrder >=30 ){
        // insert to detail PO
        DB::connection('mysql')->table('pu_po_estimate_details')->insert(
        ['po_estimate_id' => $this->POID,
        'sku' => $ProductListAvailable->sku,
        'quantity'=>$SuggestOrder,
        'sell_type'=>$ProductListAvailable->sell_type,
        'sell_status'=>$ProductListAvailable->sell_status,
        'fob_price'=>$ProductListAvailable->fob_price,
        'appotion_price'=>$ProductListAvailable->appotion_price,
        'moq'=> $ProductListAvailable->moq,
        'balance'=>$TotalBalanceNow ,
        'balance_at_selling'=>$EndBalance,
        'life_cycle'=>$ProductListAvailable->life_cycle
        ] );
        $SuggestOrder = 0;
        $this->BalanceOnAVC_WH=0;
        $this->BalanceOnFBA=0;
        $this->BalanceOnY4A=0;
        $this->PipeLine = 0;
      }
    }
  }

// timf soos Forecast cua 3 kênh trong giai đoạn
  public function GetForecastQuantityInstage( $sku, $StartSellingDate, $EndSellingDate)
  {

    $Y4AStoreID = 46;
    $SalesChanelFBA = 1;
    $SalesChanelAVC_WH = 3;

    // lấy LOI để xác định ngày dự kiến bán hết sky này của PO này  avc-wh
    $avcwh_level = DB::connection('mysql')->table('sal_lois')->where('sku',$sku)->value('avcwh_level');
    if($avcwh_level > 0){// Nếu LOI> 0 thì gia tăng ngày kết thúc bán lên theo level tính bằng tuần
      $EndSellingDateOnAVC_WH = date('Y-m-d',strtotime( $EndSellingDate. '+'. $avcwh_level * 7 .'days'));
    }else{
      $EndSellingDateOnAVC_WH =  $EndSellingDate;
    }

     // ---------------- Tính số FC của kho Y4A -------------------------------

      $ForecastY4As = DB::connection('mysql')->select(
      "select sum(fcdt.quantity) as quantity from sal_forecasts  fc INNER JOIN
      sal_forecast_details fcdt on fc.id = fcdt.sales_forecast_id INNER JOIN
      products p on  p.product_sku = fc.sku
      where  date(fcdt.the_date) <=  date('$StartSellingDate')
      and date(fcdt.the_date) >=  date('$EndSellingDate')
      and fc.sku = '$sku'
      and fc.chanel_id not in ($SalesChanelFBA ,$SalesChanelAVC_WH)");
      foreach( $ForecastY4As as $ForecastY4A ){
        $this->ForeCastOnY4AInStage = $ForecastY4A->quantity;
      }
     // print_r('ForeCastOnY4AInStage:'.$this->ForeCastOnY4AInStage );
     // print_r('<br>' );

    // ---------------- Tính số FC của kho FBA -------------------------------
    $EndSellingDateOnFBA = date('Y-m-d',strtotime($EndSellingDate. '+ 14 days'));
    $ForecastFBAs = DB::connection('mysql')->select(
    " select sum(fcdt.quantity) as quantity from sal_forecasts  fc
    INNER JOIN sal_forecast_details fcdt on fc.id = fcdt.sales_forecast_id
    INNER JOIN products prd on fc.sku = prd.product_sku
    where date(fcdt.the_date) <= date('$StartSellingDate')
    and date(fcdt.the_date) >= date('$EndSellingDateOnFBA')
    and fc.sku = '$sku'
    and fc.chanel_id in ($SalesChanelFBA)");

    foreach( $ForecastFBAs as $ForecastFBA ){
      $this->ForeCastOnFBAInStage   = $ForecastFBA->quantity;
    }

  // ---------------- Tính số FC của kho AVCWH -------------------------------
    $ForecastAVC_WHs = DB::connection('mysql')->select(
    " select sum(fcdt.quantity) as quantity from sal_forecasts  fc
    INNER JOIN sal_forecast_details fcdt on fc.id = fcdt.sales_forecast_id
    INNER JOIN products p on   fc.sku = p.product_sku
    where  date(fcdt.the_date) <=  date('$StartSellingDate')
    and date(fcdt.the_date) <=  date('$EndSellingDateOnAVC_WH')
    and fc.sku = '$sku'
    and fc.chanel_id in ($SalesChanelAVC_WH )");
    foreach( $ForecastAVC_WHs as $ForecastAVC_WH ){
      $this->ForeCastOnAVC_WHInStage = $ForecastAVC_WH->quantity;
    }
  }

  // -----Tính số tồn kho đầu ngày tại một thời điểm của 3 kho y4a,avc-wh, fba trong quá khư hoặc ngày hiện tại

  public function GetBalanceBeginningOnTheDate( $sku, $TheDate )
  {
    $Y4AStoreID = 46;

    $BalanceY4As = DB::connection('mysql_it')->select("select sum(if(a.quantity is not null , a.quantity,0)) as  quantity
    from
    (
      SELECT sum(opening_stock) as quantity FROM inventory_general_report inner JOIN
      products on inventory_general_report.product_id = products.id
      WHERE   inventory_general_report.warehouse_id = $Y4AStoreID
      and products.sell_type in (1,4)
      AND date(inventory_general_report.date)= date('$TheDate')
      AND products.product_sku =  '$sku'
      union
      SELECT sum(opening_stock * prdc.quantity) as quantity FROM inventory_general_report inner JOIN
      products on inventory_general_report.product_id = products.id
      inner join  productcombo prdc on products.id = prdc.product_id
      inner join products as p2 on prdc.child_id = p2.id
      WHERE   inventory_general_report.warehouse_id = $Y4AStoreID
      and products.sell_type in (2,3)
      AND date(inventory_general_report.date) = date('$TheDate')
      AND p2.product_sku = '$sku'
      and p2.published =1
    )a");


    foreach( $BalanceY4As as $BalanceY4A ){
      $this->BalanceOnY4A  = $BalanceY4A->quantity;
    }


    // --------------Tính so tồn kho của FBA------------------

    $BalanceFBAs = DB::connection('mysql_it')->select("
    select sum(if(a.quantity is null,0,a.quantity)) as quantity  FROM
    (
      SELECT SUM(sellable) as quantity
      FROM fba_inventory_trace AS inv
      LEFT JOIN products AS p ON inv.product_id = p.id
      WHERE  p.published = 1
      and  p.product_sku = '$sku'
      and p.sell_type in (2,4)
      AND date(inv.date) = date('$TheDate')

      union

      SELECT  SUM(sellable* prdc.quantity ) as quantity
      FROM fba_inventory_trace AS inv
      LEFT JOIN products AS p ON inv.product_id = p.id
      inner join  productcombo prdc on p.id = prdc.product_id
      inner join products as p2 on prdc.child_id = p2.id
      WHERE p2.product_sku = '$sku'
      and  p2.published = 1
      and p.sell_type in (2,3)
      AND date(inv.date)= date('$TheDate')
    )a");
    foreach( $BalanceFBAs as $BalanceFBA ){
      $this->BalanceOnFBA   =  $BalanceFBA->quantity;
    }

    // --------Tim so ton cua kenh AVC-WH ----------------
    $BalanceAVC_WHs = DB::connection('mysql_it')->select("
    select sum(if(a.quantity is null,0,a.quantity)) as quantity FROM

    (
    SELECT sum(inv.sellable_unit)  as quantity
    FROM amazon_avc_inventory AS inv
    LEFT JOIN products AS p ON inv.product_id = p.id
    WHERE date(report_date) = date('$TheDate')
    and p.sell_type in (1,4) -- single
    and  p.product_sku  =  '$sku'
    and  inv.sellable_unit > 0

    union

    SELECT sum(inv.sellable_unit * prdc.quantity) as quantity
    FROM amazon_avc_inventory AS inv
    LEFT JOIN products AS p ON inv.product_id = p.id

    inner join  productcombo prdc on p.id = prdc.product_id
    inner join products as p2 on prdc.child_id = p.id

    WHERE date(report_date) = date('$TheDate')
    and p.sell_type in(2,3) -- combo/mutilple
    and  p2.product_sku  =  '$sku'
    and  inv.sellable_unit >0
    )a");

    foreach($BalanceAVC_WHs as $BalanceAVC_WH ){
      $this->BalanceOnAVC_WH  = $BalanceAVC_WH->quantity ;
    }

    // số pipeline của kênh DI đã được Sales confirm với Amazon nhưng chưa về kho của amazon
    $DiPipeLines = DB::connection('mysql')->select("
    select sum(if(a.quantity is null,0,a.quantity)) as quantity FROM
    (
    SELECT sum(quantity)  as quantity
    FROM pu_di_pipeline AS dipl
    LEFT JOIN products AS p ON dipl.sku = p.product_sku
    WHERE  p.sell_type in (1,4)
    and  p.product_sku  =  '$sku'

    union

    SELECT sum(dipl.quantity * prdc.quantity) as quantity
    FROM pu_di_pipeline AS dipl
    LEFT JOIN products AS p ON dipl.sku = p.product_sku

    inner join  productcombo prdc on p.id = prdc.product_id
    inner join products as p2 on prdc.child_id = p.id

    WHERE  p.sell_type in(2,3)
    and dipl.quantity > 0
    and  p2.product_sku  =  '$sku'
    )a");
    $DiPipeLine = 0;
    foreach($DiPipeLines as $ds ){  $DiPipeLine  = $ds->quantity ;}

    $this->BalanceOnAVC_WH  = $this->BalanceOnAVC_WH - $DiPipeLine;
}

// --------------------------- Lấy số Forecast của 3 kênh tại một ngày cụ thể
public function GetForecastQuantityOnTheDate($sku,$TheDate)
  {
    $Y4AStoreID = 46;
    $SalesChanelFBA = 1;
    $SalesChanelAVC_WH = 3;
     // ---------------- Tính số FC của kho Y4A -------------------------------

      $ForecastY4As = DB::connection('mysql')->select(
      "select sum(if( a.quantity is null,0,a.quantity )) as quantity FROM
      (
      select sum(fcdt.quantity) as quantity from sal_forecasts  fc INNER JOIN
      sal_forecast_details fcdt on fc.id = fcdt.sales_forecast_id INNER JOIN
      products p on  p.product_sku = fc.sku
      where  date(fcdt.the_date) =  date('$TheDate')
      and fc.sku = '$sku'
      and fc.chanel_id not in ($SalesChanelFBA ,$SalesChanelAVC_WH)
      and p.sell_type = 1
      union
      select sum(fcdt.quantity * prdc.quantity) as quantity from sal_forecasts  fc
      INNER JOIN sal_forecast_details fcdt on fc.id = fcdt.sales_forecast_id
      INNER JOIN products p on fc.sku = p.product_sku
      inner join  productcombo prdc on p.id = prdc.product_id
      inner join products as p2 on prdc.child_id = p2.id
      inner join prd_sell_types st on p.sell_type = st .id
      where  fcdt.quantity > 0
      and  date(fcdt.the_date) = date('$TheDate')
      and p2.product_sku = '$sku'
      and fc.chanel_id not in ($SalesChanelFBA,$SalesChanelAVC_WH)
      and p.sell_type in (2,3)
      ) a");
      //- da di qua
      foreach( $ForecastY4As as $ForecastY4A ){
        $this->ForeCastOnY4A  = $ForecastY4A->quantity;
      }


    // ---------------- Tính số FC của kho FBA -------------------------------

    $ForecastFBAs = DB::connection('mysql')->select(
    " select sum(if(a.quantity is null,0,a.quantity)) as quantity from
    (
    select sum(fcdt.quantity) as quantity from sal_forecasts  fc
    INNER JOIN sal_forecast_details fcdt on fc.id = fcdt.sales_forecast_id
    INNER JOIN products prd on fc.sku = prd.product_sku
    where  prd.sell_type = 1
    and  date(fcdt.the_date) =  date('$TheDate')
    and fc.sku = '$sku'
    and fc.chanel_id in ($SalesChanelFBA)

    union

    select sum(fcdt.quantity * prdc.quantity ) as quantity from sal_forecasts  fc
    INNER JOIN sal_forecast_details fcdt on fc.id = fcdt.sales_forecast_id
    INNER JOIN products prd on fc.sku = prd.product_sku
    INNER JOIN productcombo prdc on prd.id = prdc.product_id
    INNER JOIN products p2 on prdc.child_id = p2.id

    where  prd.sell_type in(2,3)
    and  date(fcdt.the_date) =  date('$TheDate')
    and p2.product_sku  = '$sku'
    and fc.chanel_id in ($SalesChanelFBA )
    )a");

    foreach( $ForecastFBAs as $ForecastFBA ){
      $this->ForeCastOnFBA   = $ForecastFBA->quantity;
    }


  // ---------------- Tính số FC của kho AVCWH -------------------------------
    $ForecastAVC_WHs = DB::connection('mysql')->select(
    " select sum(if(a.quantity is null,0,a.quantity)) as quantity FROM
    (
    select sum(fcdt.quantity) as quantity from sal_forecasts  fc
    INNER JOIN sal_forecast_details fcdt on fc.id = fcdt.sales_forecast_id
    INNER JOIN products p on   fc.sku = p.product_sku
    where  date(fcdt.the_date) =  date('$TheDate')
    and fc.sku = '$sku'
    and fc.chanel_id in ($SalesChanelAVC_WH )
    and p.sell_type =1
    )a");
    foreach( $ForecastAVC_WHs as $ForecastAVC_WH ){
      $this->ForeCastOnAVC_WH = $ForecastAVC_WH->quantity;
    }

  }

  //  ----------------------- GetPipeline On the Date -----------------------
  public function GetPipeLineOnTheDate($sku,$TheDate){
    $PipeLines = DB::connection('mysql_it')->select("
    select sum( if(smcon.status = 10 or smcon.status = 11,smcondt.quantity,0 )) as Quantity
    from shipment sm inner join
    shipment_containers smcon on sm.id =smcon.shipment_id  inner JOIN
    shipment_containers_details  smcondt on smcon.id = smcondt.container_id inner JOIN
    products prd on smcondt.product_id =prd.id
    where  smcon.status in (10,11) -- trang thai cua container  la impported/ complete
    and  Date(smcon.stocking_date)  = Date('$TheDate')
    and prd.product_sku = '$sku'

    UNION

    select sum( if(smcon.status = 1 or smcon.status = 8,smcondt.quantity,0 )) as Quantity
    from shipment sm inner join
    shipment_containers smcon on sm.id =smcon.shipment_id  inner JOIN
    shipment_containers_details  smcondt on smcon.id = smcondt.container_id inner JOIN
    products prd on smcondt.product_id =prd.id
    where  smcon.status in (1,8) -- trang thai cua container la start up/pipeline
    and  Date(sm.expect_stocking_date)  = Date('$TheDate')
    and prd.product_sku =  '$sku'
    ");

    foreach( $PipeLines as $PipeLine ){
      if (is_null($PipeLine->Quantity)){ $this->PipeLine=0;}
      else{$this->PipeLine = $PipeLine->Quantity;}
    }

  }
}



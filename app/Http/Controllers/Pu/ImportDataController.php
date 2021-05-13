<?php

namespace App\Http\Controllers\PU;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Reader\IReadFilter;
use Validator;
use DB;
use Carbon\Carbon;
use DateTime;

class ImportDataController extends Controller
{
    public $CellValue3=0;
    public $cellValue5N = 0;
    public $cellValue6N = 0;
    private $CurrentDate;

    public function index()
    {
       return view('PU.PUImport');

    }

    public function puImport(Request $request)
    {
        ini_set('memory_limit','2048M');
        set_time_limit(2600);

        $FromYear = $request->input('the_year');
        $StartWeek= $request->input('start_week');
        $WeekCount= $request->input('weeks');
        $request->flash();


        $this->CurrentDate = date("Y-m-d");

        $SalesChanelFBA = 9;
        $SalesChanelAVC_WH = 1;

        $CurrentWeeks = DB::connection('mysql')->select(" select week(CURRENT_DATE()) as week");
         foreach( $CurrentWeeks as $Week ){
           $CurentWeek  = $Week->week;
         }

         $ArrWeek   = array($WeekCount);
         $ArrYear = array($WeekCount);

         $ArrayFBM  = array($WeekCount);
         $ArrayFBA  = array($WeekCount);
         $ArrayAVC_WH  = array($WeekCount);
         $ArrayAVC_DS  = array($WeekCount);
         $ArrayAVC_DI  = array($WeekCount);

         $ArrayWM_DSV  = array($WeekCount);
         $ArrayWM_MKP  = array($WeekCount);
         $ArrayEbay  = array($WeekCount);
         $ArrayCraigList  = array($WeekCount);
         $ArrayWebsite  = array($WeekCount);


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
                    'expect_import_date'=>$cellValue5N, 'end_selling_date'=>$cellValue6N]
                );
            }

              // Import Status of DI order
              $reader->setLoadSheetsOnly(["Status of DI order", "Status of DI order"]);
              $spreadsheet = $reader->load($file);
              $FirstRow = 9;
              $highestRow = $spreadsheet->getActiveSheet()->getHighestRow();
              print_r('Status of DI order' );
              print_r('<br>' );
              print_r('Last Row'.$highestRow);
              print_r('<br>' );

              $StartCol = 5; // mỗi lần import kiểm tra sheet Status of DI order
              $LastCol = 65; // mỗi lần import kiểm tra sheet Status of DI order

              $DICommingDate  = array($LastCol);
              DB::table('pu_di_pipeline')->delete();
              for($col= $StartCol ; $col <=  $LastCol ; $col++)
               { $DICommingDate[$col]= $spreadsheet->getActiveSheet()->getCellByColumnAndRow( $col,8)->getFormattedValue();}//Comming Date

              for($i= $FirstRow; $i <= $highestRow; $i++)
              {
                $sku = $spreadsheet->getActiveSheet()->getCellByColumnAndRow( 1,$i)->getValue();//sku
                  for($k = $StartCol ; $k <=  $LastCol ; $k++)
                  {
                    $quantity = $spreadsheet->getActiveSheet()->getCellByColumnAndRow( $k,$i)->getValue();//Quantity
                    if($quantity > 0)
                    {
                      DB::connection('mysql')->table('pu_di_pipeline')->insert(
                        ['sku'=> $sku ,'comming_date'=>$DICommingDate[$k], 'quantity'=>$quantity]);
                    }
                  }
              }

              DB::table('pu_avcwh_balance_tmp')->delete();
              // Import Manufacturing
              $reader->setLoadSheetsOnly(["Manufacturing", "Manufacturingr"]);
              $spreadsheet = $reader->load($file);
              $FirstRow = 5;
              $highestRow = $spreadsheet->getActiveSheet()->getHighestRow();
              print_r('Manufacturing' );
              print_r('<br>' );
              print_r('Last Row'.$highestRow);

              for($i= $FirstRow; $i <= $highestRow; $i++){
                  $cellValue1 = $spreadsheet->getActiveSheet()->getCellByColumnAndRow( 1,$i)->getValue();//ASIN
                  $cellValue2 = $spreadsheet->getActiveSheet()->getCellByColumnAndRow( 6,$i)->getValue();//Purchase
                  $cellValue3 = $spreadsheet->getActiveSheet()->getCellByColumnAndRow( 9,$i)->getValue();//On hand
                  if($cellValue1!='')
                  {
                    DB::connection('mysql')->table('pu_avcwh_balance_tmp')->insert(
                    ['asin'=> $cellValue1, 'opening_balance'=>$cellValue2 + $cellValue3,'is_facturing'=>1]
                  );
                }
              }

               // Import Sourcing
               $reader->setLoadSheetsOnly(["Sourcing", "Sourcing"]);
               $spreadsheet = $reader->load($file);
               $FirstRow = 5;
               $highestRow = $spreadsheet->getActiveSheet()->getHighestRow();
               print_r('Sourcing' );
               print_r('<br>' );
               print_r('Last Row'.$highestRow);
               for($i= $FirstRow; $i <= $highestRow; $i++){
                   $cellValue1 = $spreadsheet->getActiveSheet()->getCellByColumnAndRow( 1,$i)->getValue();//ASIN
                   $cellValue2 = $spreadsheet->getActiveSheet()->getCellByColumnAndRow( 6,$i)->getValue();// Purchase
                   $cellValue3 = $spreadsheet->getActiveSheet()->getCellByColumnAndRow( 9,$i)->getValue();// On hand
                   if($cellValue1!='' && $cellValue2 > 0)
                   {
                     DB::connection('mysql')->table('pu_avcwh_balance_tmp')->insert(
                     ['asin'=> $cellValue1, 'opening_balance'=>$cellValue2 + $cellValue3,'is_facturing'=>0]
                   );
                 }
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

                // Đọc data phần FBM và ghi vào bảng sal_forecast_details
                $k=1;
                for($col = $ColOfStartingData ; $col <$ColOfStartingData +  $WeekCount ; $col++)
                {
                    $ArrayFBM[$k] =$spreadsheet->getActiveSheet()->getCellByColumnAndRow( $col,$row)->getValue();
                    if($ArrayFBM[$k]>0)
                    {
                      $this->InsertToForeCast($ArrayYear[$k],$ArrayWeek[$k],$sku, $ArrayFBM[$k],10);
                    }
                   $k++;
                }
                // Đọc data phần FBA và ghi vào bảng sal_forecast_details
                $k=1;
                for($col = $ColOfStartingData + 1*$WeekCount ; $col < $ColOfStartingData +  2* $WeekCount ; $col++){
                    $ArrayFBA[$k] = $spreadsheet->getActiveSheet()->getCellByColumnAndRow( $col,$row)->getValue();
                    if ($ArrayFBA[$k] >0  ){
                       $this->InsertToForeCast($ArrayYear[$k],$ArrayWeek[$k],$sku ,$ArrayFBA[$k],9);
                    }
                    $k++;
                }
                // Đọc data phần AVC-WH và ghi vào bảng sal_forecast_details
                $k=1;
                for($col=$ColOfStartingData + 2* $WeekCount ; $col <$ColOfStartingData +  3* $WeekCount ; $col++){
                    $ArrayAVC_WH[$k] = $spreadsheet->getActiveSheet()->getCellByColumnAndRow( $col,$row)->getValue();
                    if ($ArrayAVC_WH[$k] >0  ){
                       $this->InsertToForeCast($ArrayYear[$k],$ArrayWeek[$k],$sku, $ArrayAVC_WH[$k],1);
                   }
                   $k++;
                }

                // Đọc data phần AVC-DS và ghi data FC
                $k=1;
                for($col=$ColOfStartingData +3* $WeekCount ; $col <$ColOfStartingData + 4* $WeekCount ; $col++){
                    $ArrayAVC_DS[$k] = $spreadsheet->getActiveSheet()->getCellByColumnAndRow( $col,$row)->getValue();
                    if ($ArrayAVC_DS[$k] >0  ){
                       $this->InsertToForeCast($ArrayYear[$k],$ArrayWeek[$k],$sku, $ArrayAVC_DS[$k],2);
                   }
                   $k++;
                }
                // Đọc data phần AVC-DI và ghi data FC
                $k=1;
                for($col=$ColOfStartingData + 4 * $WeekCount ; $col <$ColOfStartingData + 5* $WeekCount ; $col++){
                    $ArrayAVC_DI[$k] = $spreadsheet->getActiveSheet()->getCellByColumnAndRow( $col,$row)->getValue();
                    if ( $ArrayAVC_DI[$k] >0  ){
                      $this->InsertToForeCast($ArrayYear[$k],$ArrayWeek[$k], $sku,$ArrayAVC_DI[$k],3);
                   }
                   $k++;
                }
                // Đọc data phần Walmart DSV và ghi data FC
                $k=1;
                for($col=$ColOfStartingData + 5* $WeekCount ; $col <$ColOfStartingData +  6*$WeekCount ; $col++){
                    $ArrayWM_DSV[$k] = $spreadsheet->getActiveSheet()->getCellByColumnAndRow( $col,$row)->getValue();
                    if ($ArrayWM_DSV[$k] >0  ){
                        $this->InsertToForeCast($ArrayYear[$k],$ArrayWeek[$k],$sku, $ArrayWM_DSV[$k],4);
                    }
                    $k++;
                }
                // Đọc data phần Walmart MKP và ghi data FC
                $k=1;
                for($col = $ColOfStartingData + 6* $WeekCount ; $col <$ColOfStartingData +  6*$WeekCount ; $col++){
                    $ArrayWM_MKP[$k] = $spreadsheet->getActiveSheet()->getCellByColumnAndRow( $col,$row)->getValue();
                    if ($ArrayWM_MKP[$k] >0  ){
                        $this->InsertToForeCast($ArrayYear[$k],$ArrayWeek[$k], $sku, $ArrayWM_MKP[$k],5);
                    }
                    $k++;
                }
                // Đọc data phần EBAY và ghi data FC
                $k=1;
                for($col=$ColOfStartingData + 7* $WeekCount ; $col <$ColOfStartingData +  7 * $WeekCount ; $col++){
                    $ArrayEbay[$k] = $spreadsheet->getActiveSheet()->getCellByColumnAndRow( $col,$row)->getValue();
                    if ($ArrayEbay[$k] >0  ){
                        $this->InsertToForeCast($ArrayYear[$k],$ArrayWeek[$k],$sku, $ArrayEbay[$k],6);
                    }
                    $k++;
                }

                // Đọc data phần CRAILIST/Local và ghi data FC
                $k=1;
                for($col=$ColOfStartingData + 8*$WeekCount ; $col <$ColOfStartingData +  8 * $WeekCount ; $col++){
                    $ArrayCraigList[$k] = $spreadsheet->getActiveSheet()->getCellByColumnAndRow( $col,$row)->getValue();
                    if ($ArrayCraigList[$k] >0  ){
                        $this->InsertToForeCast($ArrayYear[$k],$ArrayWeek[$k],$sku, $ArrayCraigList[$k],7);
                    }
                    $k++;
                }

                // Đọc data phần Website và ghi data FC
                $k=1;
                for($col=$ColOfStartingData + 9*$WeekCount ; $col <$ColOfStartingData +  9 * $WeekCount ; $col++){
                  $ArrayWebsite[$k] = $spreadsheet->getActiveSheet()->getCellByColumnAndRow( $col,$row)->getValue();
                    if ($ArrayWebsite[$k] >0  ){
                        $this->InsertToForeCast($ArrayYear[$k],$ArrayWeek[$k], $sku, $ArrayWebsite[$k],8);
                    }
                    $k++;
                }

              }
        }// pass

      $this->MoveSomeTableFromBEToBPD();
      $this->UpdateSkuForAvcWhBalance();
     // return redirect()->back()->with(['success'=>'File Upload successfuly.']);
    }
// -----------------------------------------------------------------------------------------------
public function UpdateSkuForAvcWhBalance()
{// Xử lý với data tồn kho của kênh avc wh + di
 //1. Cập nhật SKU tương đương với asin
 $sql  = "select id, GetSkuFromAsin(asin) as sku  from pu_avcwh_balance_tmp";
 $ds = DB::connection('mysql')->select($sql);
 foreach( $ds as  $d){
    $sql = " update pu_avcwh_balance_tmp set sku = $d->sku where id = $d->id ";
    DB::connection('mysql')->select($sql);
 }
 //2. Convert to single với số tồn kho của avc -wh
 //$sql = "select "

}
// -----------------------------------------------------------------------------------------------
public function InsertToForeCast($TheYear,$TheWeek,$sku,$ForecastNumber,$Channel)
{
  $TheQuantityPerDay = $ForecastNumber/7; // chia ra thanh 7 ngay trong tuan
  $ret= array();
  $StartDate = new DateTime();
  $StartDate->setISODate($TheYear, $TheWeek);
  $StartDate->setISODate((int)$StartDate->format('o'), $TheWeek, 0);
  $ret[1] = $StartDate->format('Y-m-d');

  


  $ForeCastID =DB::connection('mysql')->table('sal_forecasts')->insertGetId(
  ['the_date'=> $ret[1], 'sku'=>$sku]);

  DB::connection('mysql')->table('sal_forecast_details')->insert(
    ['sales_forecast_id' => $ForeCastID,'channel_id'=>$Channel,'quantity'=>$TheQuantityPerDay]);

  for($i=2; $i<=7;$i++){
    $ret[$i]= date('Y-m-d',strtotime( $ret[$i-1]. '+'.' 1 days'));
    DB::connection('mysql')->table('sal_forecast_details')->insert(
    ['sales_forecast_id' => $ForeCastID,'channel_id'=>$Channel,'quantity'=>$TheQuantityPerDay]);
  }
}
// -----------------------------------------------------------------------------------------------
public function MoveSomeTableFromBEToBPD()
{
    // move enumeration
    DB::connection('mysql')->select(" delete from ms_enumerations");
    DB::connection('mysql')->select("ALTER TABLE ms_enumerations AUTO_INCREMENT = 1");

    $Enumerations = DB::connection('mysql_it')->select(" select  id , table_name , field_name from enumeration ");
    foreach( $Enumerations as  $En){
      $sql = " insert into ms_enumerations(id , table_name , field_name  )";
      $sql = $sql . " select " .  $En->id .",'" . $En->table_name ."','".  $En->field_name ."'" ;
      DB::connection('mysql')->select($sql);
      $sql="";
    }

     // move enumeration_value
     DB::connection('mysql')->select(" delete from ms_enumeration_value");
     DB::connection('mysql')->select("ALTER TABLE ms_enumeration_value AUTO_INCREMENT = 1");

     $Enumeration_values = DB::connection('mysql_it')->select(" select  id , enumeration_id , value, title  from enumeration_value");
     foreach( $Enumeration_values as  $Env){
      $sql = " insert into ms_enumeration_value(id , enumeration_id , value, title  )
      select " .  $Env->id ."," . $Env->enumeration_id .",'".  $Env->value ."','".$Env->title ."'"  ;

      DB::connection('mysql')->select($sql);
     }
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
        $sql= " insert into vendors( id, agent_id , title ) values(". $d->id . ",". $d->agent_id .",'" . $d->title . "')";
        DB::connection('mysql')->select($sql);
      }
      // 6.amazon_products
      DB::connection('mysql')->select(" delete from prd_amazon");
      DB::connection('mysql')->select("ALTER TABLE prd_amazon AUTO_INCREMENT = 1");

      $ds = DB::connection('mysql_it')->select(" select id,product_id ,asin_id  from amazon_products ");
      foreach( $ds as  $d){
        $sql= " insert into prd_amazon( id, product_id , asin_id ) values(". $d->id . ",". $d->product_id ."," . $d->asin_id . ")";
        DB::connection('mysql')->select($sql);
      }

      // 7.Asin
      DB::connection('mysql')->select(" delete from prd_asin");
      DB::connection('mysql')->select("ALTER TABLE prd_asin AUTO_INCREMENT = 1");
      $ds = DB::connection('mysql_it')->select(" select id,asin ,product_id  from asin ");
      foreach( $ds as  $d){
        $sql= " insert into prd_asin( id, asin , product_id ) values(". $d->id . ",'". $d->asin ."'," . $d->product_id . ")";
        DB::connection('mysql')->select($sql);
      }

}
// -----------------------------------------------------------------------------------------------
public function is_not_empty_string($str) {
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

   for($i=2;$i<=7;$i++){
     $ret[$i]= date('Y-m-d',strtotime( $ret[$i-1]. '+'.' 1 days'));
     DB::connection('mysql')->table('sal_forecast_details')->insert(
     ['sales_forecast_id' => $ForeCastID,'the_date'=> $ret[$i],'quantity'=>$TheQuantityPerDay]);
   }
 }
// -----------------------------------------------------------------------------------------------
public function ShowUseContainer()
 {
    return view('layouts.PUShowContainer');
 }
}

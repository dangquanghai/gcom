<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Reader\IReadFilter;
use Validator;
use DB;
use Carbon\Carbon;
use DateTime;

class sal_loiController extends Controller
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

              // Import Status of DI order
              $reader->setLoadSheetsOnly(["Status of DI order", "Status of DI order"]);
              $spreadsheet = $reader->load($file);
              $FirstRow = 6;
              $highestRow = $spreadsheet->getActiveSheet()->getHighestRow();
              print_r('Status of DI order' );
              print_r('<br>' );
              print_r('Last Row'.$highestRow);


              DB::table('pu_di_pipeline')->delete();
              for($i= $FirstRow; $i <= $highestRow; $i++){
                  $cellValue1 = $spreadsheet->getActiveSheet()->getCellByColumnAndRow( 2,$i)->getValue();//sku
                  $cellValue2 = $spreadsheet->getActiveSheet()->getCellByColumnAndRow( 8,$i)->getValue();// Quantity
                  if($cellValue1!='' && $cellValue2 > 0)
                  {
                    DB::connection('mysql')->table('pu_di_pipeline')->insert(
                    ['sku'=> $cellValue1, 'quantity'=>$cellValue2]
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

                // Ghi vào bảng sal_forecasts FBM=> Channel =2
                $ForecastMasterID =DB::connection('mysql')->table('sal_forecasts')->insertGetId(
                    ['chanel_id'=> 2, 'sku'=>$sku]
                );

                // Đọc data phần FBM và ghi vào bảng sal_forecast_details
                $k=1;
                for($col=$ColOfStartingData ; $col <$ColOfStartingData +  $WeekCount ; $col++){
                    $ArrayFBM[$k] =$spreadsheet->getActiveSheet()->getCellByColumnAndRow( $col,$row)->getValue();
                    if($ArrayFBM[$k]>0){
                      $this->InsertToForeCastDetail($ArrayYear[$k],$ArrayWeek[$k], $ArrayFBM[$k],$ForecastMasterID);
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
                       $this->InsertToForeCastDetail($ArrayYear[$k],$ArrayWeek[$k], $ArrayFBA[$k],$ForecastMasterID);
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
                       $this->InsertToForeCastDetail($ArrayYear[$k],$ArrayWeek[$k], $ArrayAVC_WH[$k],$ForecastMasterID);
                   }
                   $k++;
                }

                 // Ghi vào bảng sal_forecasts AVC-DS=> Channel =9
                 $ForecastMasterID =DB::connection('mysql')->table('sal_forecasts')->insertGetId(
                    ['chanel_id'=> 9, 'sku'=>$sku]
                );
                // Đọc data phần AVC-DS và ghi vào bảng sal_forecast_details
                $k=1;
                for($col=$ColOfStartingData +3* $WeekCount ; $col <$ColOfStartingData + 4* $WeekCount ; $col++){
                    $ArrayAVC_DS[$k] = $spreadsheet->getActiveSheet()->getCellByColumnAndRow( $col,$row)->getValue();
                    if ($ArrayAVC_DS[$k] >0  ){
                       $this->InsertToForeCastDetail($ArrayYear[$k],$ArrayWeek[$k], $ArrayAVC_DS[$k],$ForecastMasterID);
                   }
                   $k++;
                }

                // Ghi vào bảng sal_forecasts EBAY=> Channel = 10
                $ForecastMasterID =DB::connection('mysql')->table('sal_forecasts')->insertGetId(
                ['chanel_id'=> 10, 'sku'=>$sku]
                );
                // Đọc data phần FBA và ghi vào bảng sal_forecast_details
                $k=1;
                for($col=$ColOfStartingData +5* $WeekCount ; $col <$ColOfStartingData + 6* $WeekCount ; $col++){
                    $ArrayAVC_DS[$k] = $spreadsheet->getActiveSheet()->getCellByColumnAndRow( $col,$row)->getValue();
                    if ( $ArrayAVC_DS[$k] >0  ){
                       $this->InsertToForeCastDetail($ArrayYear[$k],$ArrayWeek[$k], $ArrayAVC_DS[$k],$ForecastMasterID);
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
                        if ($ArrayWM[$k] >0  ){
                           $this->InsertToForeCastDetail($ArrayYear[$k],$ArrayWeek[$k], $ArrayWM[$k],$ForecastMasterID);
                       }
                       $k++;
                    }

            }

        }// pass
       // return redirect()->back()->with(['success'=>'File Upload successfuly.']);
      $this->MoveSomeTableFromBEToBPD();
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

public function ShowUseContainer() {
    return view('layouts.PUShowContainer');
}



}

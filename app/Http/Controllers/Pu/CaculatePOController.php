<?php

namespace App\Http\Controllers\Pu;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use DB;
use Carbon\Carbon;
use DateTime;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Reader\IReadFilter;
use Validator;

class CaculatePOController extends Controller
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

    public $NewBalanceDate; // ngày ghi nhận tồn đầu của một sku
    public $NewBalanceNumber; // Số lượng tồn đầu  của một sku

    public $PipeLineY4A; // Số hàng dự kiến về trong ngày
    public $PipeLineDI; // Số hàng dự kiến về trong ngày
    public $Missing;// Số hàng thiếu không kịp mua đáp ứng
// ============================================================================================================
public function iif($condition, $true, $false) {
  return ($condition?$true:$false);
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
public function LoadPOList()
{
  //dd('asdfads');
  $sql = " select po.id , the_year as order_year , the_week as order_week ,vd.vendor_name,
  po.lead_time,po.expect_load_date,po.expect_etd_date,po.expect_eta_date, po.start_selling_date,
  po.end_selling_date
  from pu_po_estimates po left join pu_vendors vd on po.vendor_id = vd.id
  order by the_year , the_week " ;
  $dsPOs = DB::connection('mysql')->select($sql);

  $sql= "  select po.id , podt.sku,p.title, getSellType(podt.sell_type) as sell_type,
  podt.quantity, podt.fob_price,round(podt.quantity * podt.fob_price,0) as amount
  from pu_po_estimates po inner join pu_po_estimate_details  podt on po.id = podt.po_estimate_id
  inner join prd_product p on  podt.sku = p.product_sku  where podt.quantity > 0 ";
  $dsPoDetails = DB::connection('mysql')->select($sql);
  return view('pu.polist',compact(['dsPOs','dsPoDetails']));

}
public function LoadPoDetail($PoID)
{
    $sql= " select po.id, podt.sku,p.title, getSellType(podt.sell_type) as sell_type,
    podt.quantity, podt.fob_price,round(podt.quantity * podt.fob_price,0) as amount
    from pu_po_estimates po inner join pu_po_estimate_details  podt on po.id = podt.po_estimate_id
    inner join prd_product p on  podt.sku = p.product_sku
    where podt.quantity >0 and po.id = $PoID   order by  podt.sku ";

    $dsPoDetails = DB::connection('mysql')->select($sql);
    return view('pu.polist',compact('$dsPoDetails'));

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
    return view('fa.CashflowImportFile',compact('PODetails'));
    }

    public function ShowPOList()
    {
      $POs = DB::connection('mysql')->select("select pu_po_estimates.id , vendors.title , pu_po_estimates.the_week,  pu_po_estimates.the_year,order_date,expect_eta,end_selling_date
      from pu_po_estimates INNER JOIN   vendors on pu_po_estimates.vendor_id = vendors.id");
      //return view('PU.PU-PO',compact('POs'));
      return view('PU.PU-POList',compact('POs'));

    }
  // ------------------------------------------------------------------------------
    public function ShowUseContainer() {
      return view('PU.PUShowContainer');
    }
    // ------------------------------------------------------------------------------
    public function GetRangeToGetDataForecast()// xác định thời gian cần lấy data forecast
    {
      $CurrentDates = DB::connection('mysql_it')->select(" select CURRENT_DATE() as CurentDate");
      foreach( $CurrentDates as $CurrentDate ){  $this->CurrentDate  = $CurrentDates->CurentDate; }

      $CurrentWeeks = DB::connection('mysql_it')->select(" select week(CURRENT_DATE()) as week");
      foreach( $CurrentWeeks as $Week ){  $CurentWeek  = $Week->week;    }
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
  /* Đầu vào: Data Forecast của sales, Kế hoạch mua hang của PU
  Đầu ra: Tạo ra các PO estimate cho các tuần chưa tạo, mỗi tuần, mỗi vendor nếu có trong plan sẽ tạo một PO   */
  public function CreateAllPOEstimate()
  {
    ini_set('memory_limit','2048M');
    set_time_limit(52000);
    date_default_timezone_set('UTC');// Xét giờ mặc định là giờ quốc tế amazon lưu theo giờ này
    // Xóa toàn bộ log theo dõi
    DB::connection('mysql')->table('pu_caculate_po_record')->delete();
    // xóa toàn bộ po estimate
    DB::connection('mysql')->table('pu_po_estimates')->delete();
    DB::connection('mysql')->table('pu_po_estimate_details')->delete();
    // Xóa số tồn kho thời điểm xa nhất cho mỗi sku
    DB::connection('mysql')->table('pu_product_balance')->delete();

    // Load toàn bộ plan của PU những tuần chưa tạo PO estimate
    //$Plans =   DB::table('pu_plannings') ->select ('id','vendor_id','order_week','order_date','eta','end_selling_date')
    $Plans =   DB::connection('mysql')->select('select id,order_week,order_date,at_year,vendor_id,expect_import_date,end_selling_date
    from pu_plannings where id = 2213');
    $i=1;
    $this->CurrentDate = date("Y-m-d");
    $this->TwoDaysBefore = date('Y-m-d',strtotime($this->CurrentDate. '- 2 days'));

    foreach ($Plans as $plan) {
      // Tính tồn kho cuối ngày trước ngày dự tính bán một ngày
      // $this->CreatePOForOneWeek($plan->id, $plan->vendor_id, $plan->order_week,$plan->order_date,$plan->at_year,$plan->eta, $plan->end_selling_date);
      print_r('Bắt đầu tạo po thứ:'. $i .' lúc : '.date('Y-m-d H:i:s'));
      print_r('<br>');
      print_r('------------------Tạp PO thứ' .$i );
      print_r('<br>');
      $this->CreatePOForOneWeekNew($plan->id, $plan->vendor_id, $plan->order_week,$plan->order_date,$plan->at_year,$plan->expect_import_date, $plan->end_selling_date);
      print_r('-------------------Kết thúc một PO '.$i) ;
      print_r('<br>');
      print_r('Kết thúc tạo po thứ'. $i.'lúc:' .date('Y-m-d H:i:s'));
      print_r('<br>');
      $i++;
    }
    $sql = " update pu_po_estimates set status_id = 2 where status_id = 1 ";
    DB::connection('mysql')->select($sql);
  }
   // -----------------------------------------------------------------------------------------------
   public function IsExistingBalance($sku)
   {
     $sql = "select the_date, opening_balance from  pu_product_balance where sku = '" . $sku . "'" ;
     $ds = DB::connection('mysql')->select ( $sql);
     foreach($ds as $d)
     {
      $this->NewBalanceDate =  $d->the_date;
      $this->NewBalanceNumber  =  $d->opening_balance;
     }
    if( $this->NewBalanceNumber == 0)   {  return false;  }
    else{ return true; }
   }
   // -----------------------------------------------------------------------------------------------
    public function CreatePOForOneWeekNew($PlanID, $VendorID,$TheWeek,$OrderDate,$TheYear,$ExpectImportDate,$EndSellingDate)
    {
      // Tạo PO master
      // + 1 ngày vì giả định hàng nhập kho hôm nay ngàn mai mới bánhàng ngày hôm sau mới bán
      $StartSellingDate = date('Y-m-d',strtotime($ExpectImportDate. '+ 1 days'));
      // Create PO master
      $StatusID = 1;
      $this->POID = DB::connection('mysql')->table('pu_po_estimates')->insertGetId(
      ['vendor_id' =>  $VendorID,'the_week'=>$TheWeek,'order_date'=>$OrderDate,'start_selling_date'=>$StartSellingDate ,
      'end_selling_date'=>$EndSellingDate ,'the_year' => $TheYear,'plan_id'=>$PlanID, 'status_id'=>$StatusID]);

      // Load danh sách product hợp lệ có forecast tương ứng với vendor mặc định sku đã được xử lý ra tới sku single hoặc child
      $sql = "select products.product_sku  as sku, products.sell_type, products.sell_status,
      products.fob_price,products.appotion_price,product_manufactures.moq , products.life_cycle
      from products INNER JOIN  product_manufactures
      on products.id = product_manufactures.product_id
      INNER JOIN  manufacturers_3 on product_manufactures.manufacture_id = manufacturers_3.id
      INNER JOIN vendors on manufacturers_3.vendor_id = vendors.id
       where manufacturers_3.vendor_id = $VendorID
      and products.published = 1
      and products.purchasing = 1
      and products.product_sku in ('EDYU')
      and products.life_cycle  in (4,3,10)
      and  products.product_sku in
      (
        select distinct(sku) from sal_forecasts where date(sal_forecasts.the_date) >= date('$StartSellingDate')
        and date(sal_forecasts.the_date) <= date('$EndSellingDate')
      )";
      //and products.product_sku in ('EDYU','SDX1','YVHF')
      //--(4,3,10) Normal, first year, local

      $ds = DB::connection('mysql')->select ( $sql);
      foreach($ds as $d)
      {
        $sku = $d->sku;
        if(!$this->IsExistingBalance($sku))
        {
         $EndBalance = 0;
         // Bắt đầu vòng lặp từ ngày đó đến ngày dự kiến hàng về để tính số tồn đến hết ngày đó thành tồn đầu của ngày bắt đầu bán
         $myDate = $this->TwoDaysBefore;
         // Tính số tồn kho đầu ngày cuả 3 kênh
         $this->GetBalanceBeginningOnTheDate($sku, $myDate );
         print_r('Tồn kho Y4A ngày: '. $myDate . "= ". $this->BalanceOnY4A );
         print_r('<br>');

         print_r('PipeLineY4A trước ngày: '. $myDate . "= ". $this->PipeLineY4A );
         print_r('<br>');

         print_r('Tồn kho avc-wh ngày: '. $myDate . "= ". $this->BalanceOnAVC_WH );
         print_r('<br>');

         print_r('PipeLine avc(di) trước ngày: '. $myDate . "= ". $this->PipeLineDI );
         print_r('<br>');

         print_r('Tồn kho fba ngày: '. $myDate . "= ". $this->BalanceOnFBA );
         print_r('<br>');

/*
        // Ghi record
        $sql = " insert into pu_caculate_po_record(sku,the_content,the_date,quantity)
        values('$sku','Tồn kho ban đầu ở Y4A','$myDate' ,$this->BalanceOnY4A)";
        DB::connection('mysql')->select( $sql );

        // Ghi record
        $sql = " insert into pu_caculate_po_record(sku,the_content,the_date,quantity)
        values('$sku','Số pipeline của Y4A','$myDate' ,$this->PipeLineY4A)";
        DB::connection('mysql')->select( $sql );

        // Ghi record
        $sql = " insert into pu_caculate_po_record(sku,the_content,the_date,quantity)
        values('$sku','Số tồn kho ban đầu của FBA ','$myDate' , $this->BalanceOnFBA )";
        DB::connection('mysql')->select( $sql );

        // Ghi record
        $sql = " insert into pu_caculate_po_record(sku,the_content,the_date,quantity)
        values('$sku','Số tồn kho AVC -WH','$$myDate' , $this->BalanceOnAVC_WH )";
        DB::connection('mysql')->select( $sql );

        // Ghi record
        $sql = " insert into pu_caculate_po_record(sku,the_content,the_date,quantity)
        values('$sku','Số  pipeline của DI','$$myDate' , $this->PipeLineDI)";
        DB::connection('mysql')->select( $sql );
*/
        $this->BalanceOnY4A = $this->BalanceOnY4A  + $this->PipeLineY4A;
        $this->BalanceOnAVC_WH = $this->BalanceOnAVC_WH + $this->PipeLineDI;

        print_r('tồn kho y4a đã bổ sung pipeline : '. $myDate . "= ". $this->BalanceOnY4A );
        print_r('<br>');

        print_r('Tồn kho avc wh đã bổ sung di: '. $myDate . "= ". $this->BalanceOnAVC_WH );
        print_r('<br>');


        $TotalBalanceNow = $this->BalanceOnY4A + $this->BalanceOnAVC_WH + $this->BalanceOnFBA;
         $myDate ++;
        } //if(!$this->IsExistingBalance($sku))
        else
        {
          $myDate = $this->NewBalanceDate;
          $EndBalance = $this->NewBalanceNumber;
        }

        while($myDate < $StartSellingDate )
        {
          // Tính số Pipeline của ngày hôm trước
          $DateForePipeLine = date('Y-m-d',strtotime($myDate. '+'. '-1 days'));
          // tính số pipe line của kho y4a và pipe line di
          $this->GetPipeLineOnTheDate($sku,$DateForePipeLine);

          print_r('------Vào vòng lặp--------');
          print_r('<br>');

          print_r('Số pipe line của DI trong ngày hôm trước'. $DateForePipeLine. '='. $this->PipeLineDI );
          print_r('<br>');

/*
          // Ghi record
          $sql = " insert into pu_caculate_po_record(sku,the_content)
          values('$sku','-----Trong vòng lặp --------')";
          DB::connection('mysql')->select( $sql );

          $sql = " insert into pu_caculate_po_record(sku,the_content,the_date,quantity)
          values('$sku','Số pipe line của Y4A trong ngày hôm trước','$DateForePipeLine' , $this->PipeLineY4A)";
          DB::connection('mysql')->select( $sql );

          $sql = " insert into pu_caculate_po_record(sku,the_content,the_date,quantity)
          values('$sku','Số pipe line của DI trong ngày hôm trước','$DateForePipeLine' , $this->PipeLineDI)";
          DB::connection('mysql')->select( $sql );
*/
          // Bổ sung vào số tồn đầu của Y4A
          $this->BalanceOnY4A = $this->BalanceOnY4A + $this->PipeLineY4A;
          // Bổ sung vào số tồn đầu của avc-wh
          $this->BalanceOnAVC_WH = $this->BalanceOnAVC_WH + $this->PipeLineDI;

          print_r('Số tồn y4a đã bổ sung pipeline'. $myDate. '='. $this->BalanceOnY4A );
          print_r('<br>');

          print_r('Số tồn avc-wh đã bổ sung pipeline'. $myDate. '='. $this->BalanceOnAVC_WH );
          print_r('<br>');
/*
          $sql = " insert into pu_caculate_po_record(sku,the_content,the_date,quantity)
          values('$sku','Số tồn đầu của Y4A(đã bổ sung pipeline)','$DateForePipeLine' , $this->BalanceOnY4A)";
          DB::connection('mysql')->select( $sql );


          $sql = " insert into pu_caculate_po_record(sku,the_content,the_date,quantity)
          values('$sku','Số tồn đầu của AVC-WH(đã bổ sung DI pipeline)','$DateForePipeLine' ,  $this->BalanceOnAVC_WH)";
          DB::connection('mysql')->select( $sql );
  */
          // Tính số FC của ngày đó trên 3 kênh
          $this->GetForecastQuantityOnTheDate($sku,$myDate);

          // Ghi record
   /*
          $sql = " insert into pu_caculate_po_record(sku,the_content,the_date,quantity)
          values('$sku','Số FC trong ngày của avc','$myDate' , $this->ForeCastOnAVC_WH)";
          DB::connection('mysql')->select( $sql );
          $sql = " insert into pu_caculate_po_record(sku,the_content,the_date,quantity)
          values('$sku','Số FC trong ngày của fba','$myDate' ,  $this->ForeCastOnFBA)";
          DB::connection('mysql')->select( $sql );
          $sql = " insert into pu_caculate_po_record(sku,the_content,the_date,quantity)
          values('$sku','Số FC trong ngày của Y4a','$myDate' ,  $this->ForeCastOnY4A)";
          DB::connection('mysql')->select( $sql );
   */

          print_r('Số FC trong ngày của avc'. $myDate. '='. $this->ForeCastOnAVC_WH);
          print_r('<br>');
          print_r('Số FC trong ngày của fba'. $myDate. '='. $this->ForeCastOnFBA);
          print_r('<br>');
          print_r('Số FC trong ngày của Y4a'. $myDate. '='. $this->ForeCastOnY4A);
          print_r('<br>');

          // Số tồn đầu ngày tiếp theo = số tồn hiện tại trừ đi số FC
          $this->BalanceOnAVC_WH = $this->BalanceOnAVC_WH - $this->ForeCastOnAVC_WH ;
          $this->BalanceOnFBA = $this->BalanceOnFBA   - $this->ForeCastOnFBA;
          $this->BalanceOnY4A = $this->BalanceOnY4A- $this->ForeCastOnY4A;



          // Nếu số tồn(đã bao gồm pipeline) không kham nổi số FC nhưng số tồn trên y4a kham nổi phần còn thiếu
          if($this->BalanceOnAVC_WH < 0 && -$this->BalanceOnAVC_WH <=  $this->BalanceOnY4A  )
          {
            $this->BalanceOnY4A = $this->BalanceOnY4A - $this->BalanceOnAVC_WH;
            $this->BalanceOnAVC_WH =0;
          }
          // Nếu số tồn(đã bao gồm pipeline) không khamn nổi số FC và số tồn trên y4a cũng không kham nổi phần còn thiếu
          elseif(-$this->BalanceOnAVC_WH >  $this->BalanceOnY4A )
          {
            $this->Missing = -$this->BalanceOnAVC_WH - $this->BalanceOnY4A;
            $this->BalanceOnY4A = 0;
            $this->BalanceOnAVC_WH = 0;
          }

          if( $this->BalanceOnFBA < 0 && - $this->BalanceOnFBA <=  $this->BalanceOnY4A  )
          {
            $this->BalanceOnY4A = $this->BalanceOnY4A - $this->BalanceOnFBA;
            $this->BalanceOnFBA =0;
          }elseif(- $this->BalanceOnFBA >  $this->BalanceOnY4A )
          {
            $this->Missing = - $this->BalanceOnFBA  - $this->BalanceOnY4A;
            $this->BalanceOnY4A = 0;
            $this->BalanceOnAVC_WH = 0;
          }
/*
          print_r('Số tồn cuối ngày y4a '. $myDate. '='. $this->BalanceOnY4A );
          print_r('<br>');

          print_r('Số tồn cuối ngày avc-wh'. $myDate. '='. $this->BalanceOnAVC_WH );
          print_r('<br>');

          print_r('Số tồn cuối ngày fba'. $myDate. '='. $this->BalanceOnFBA );
          print_r('<br>');

        // ghi record
          $sql = " insert into pu_caculate_po_record(sku,the_content,the_date,quantity)
          values('$sku','Số tồn cuối ngày của avc-wh','$myDate', $this->BalanceOnAVC_WH )";
          DB::connection('mysql')->select( $sql );

          $sql = " insert into pu_caculate_po_record(sku,the_content,the_date,quantity)
          values('$sku','Số tồn cuối ngày của FBA','$myDate', $this->BalanceOnFBA )";
          DB::connection('mysql')->select( $sql );

          $sql = " insert into pu_caculate_po_record(sku,the_content,the_date,quantity)
          values('$sku','Số tồn cuối ngày của Y4a','$myDate', $this->BalanceOnY4A)";
          DB::connection('mysql')->select( $sql );

          $EndBalance =  $this->BalanceOnFBA + $this->BalanceOnAVC_WH + $this->BalanceOnY4A ;
          // ghi record
          $sql = " insert into pu_caculate_po_record(sku,the_content,the_date,quantity)
          values('$sku','Tổng tồn cuối ngày','$myDate', $EndBalance )";
          DB::connection('mysql')->select( $sql );
     */

          $myDate = date('Y-m-d',strtotime($myDate. '+ 1 days'));
        }// end while

        //print_r('Kết thúc tính tồn cuối vào ngày trước ngày dự kiến bắt đầu bán chính là ngày ');
        //print_r('<br>');
        //print_r('time'.date('Y-m-d H:i:s'));
        //print_r('<br>');

        print_r('--------------- My date: '.$myDate . '  Và End selling Date '.$EndSellingDate .'------------'  );
        print_r('<br>');

        while($myDate <  $EndSellingDate )
        {
          $sql = " select comming_date from pu_di_pipeline    where  comming_date >= '$myDate'
          and comming_date <= '$EndSellingDate' and sku = '$sku'  order by comming_date ";
          $CommingDates = DB::connection('mysql')->select($sql);
          foreach( $CommingDates as $CommingDate )
          {
            // Tính pipe line của DI
            $this->PipeLineDI = $this->GetDiPipelineInstage( $sku, $myDate,$CommingDate->comming_date);
            // ghi record
            // $sql = " insert into pu_caculate_po_record(sku,the_content,the_date,quantity)
            // values('$sku','Pipeline DI từ '. $myDate . 'Đến '.$d->comming_date ,'$d->comming_date',$this->PipeLineDI )";
            // DB::connection('mysql')->select( $sql );
            print_r('Pipe line DI từ'. $myDate. ' đến ' . $CommingDate->comming_date );
            print_r('<br>');

            $NextDay = date('Y-m-d',strtotime($CommingDate->comming_date. '+ 1 days'));
            $this->GetForecastQuantityInstage( $sku, $myDate, $NextDay,$EndSellingDate );// Lấy FC từ my date đến ngày sau comming date 1 ngày

            $TotalFCInstage = $this->ForeCastOnY4AInStage + $this->ForeCastOnAVC_WHInStage ;
            print_r('Tổng FC từ'. $myDate. ' đến ' . $NextDay );
            print_r('<br>');

             // ghi record
            // $sql = " insert into pu_caculate_po_record(sku,the_content,the_date,quantity)
            //values('$sku','Tổng FC từ '. $myDate . 'Đến '.$d->comming_date ,'$d->comming_date', $TotalFCInstage )";
            //DB::connection('mysql')->select( $sql );

            $EndBalance =  $EndBalance +  $this->PipeLineDI - $TotalFCInstage;
            print_r('Tồn cuối sau khi thêm DI Pipeline bớt FC của ngày'. $NextDay  );
            print_r('<br>');

            $myDate = $CommingDate->comming_date;
          }// end for

          if($myDate < $EndSellingDate )// đoạn này không còn pipe line nữa
          {
            $TempDate = $myDate;
            $this->GetForecastQuantityInstage( $sku, $myDate,$EndSellingDate,$EndSellingDate );
            $myDate = $EndSellingDate ;
          }
        }// while lần 2

       // print_r('Kết thúc tính tồn cuối vào ngày trước ngày dự kiến bán 1 ngày chính là ngày ETA');
       // print_r('<br>');
      //  print_r('time'.date('Y-m-d H:i:s'));
      //  print_r('<br>');

        // Tính số đã đưa vào estimate
        $Estimateds= DB::connection('mysql')->select (
        "select sum(if(pu_po_estimate_product_details.quantity is null ,0,pu_po_estimate_product_details.quantity)) as quantity
        from pu_po_estimates INNER JOIN pu_po_estimate_product_details on pu_po_estimates.id = pu_po_estimate_product_details.id
        INNER JOIN products on pu_po_estimate_product_details.product = products.id
        where pu_po_estimates.id < $this->POID and pu_po_estimates = 1  and products.product_sku = '$sku' ");
        $EstimatedQuantity=0;
        foreach($Estimateds as $Estimated){ $EstimatedQuantity = $Estimated->quantity;  }

        print_r('Tổng hàng đã đặt vào PO: '. $EstimatedQuantity);
        print_r('<br>');

        $TotalFCInstage = $this->ForeCastOnY4AInStage + $this->ForeCastOnAVC_WHInStage + $this->ForeCastOnFBAInStage;

        // ghi record
        //$sql = " insert into pu_caculate_po_record(sku,the_content,the_date,quantity)
        //values('$sku','Tổng FC từ  myDate . Đến ' ,'$EndSellingDate', $TotalFCInstage )";
        //DB::connection('mysql')->select( $sql );
        print_r('Tổng FC từ'.  $TempDate. ' đến ' . $EndSellingDate. '= '. $TotalFCInstage );
        print_r('<br>');

        $SuggestOrder = max( $EndBalance -($TotalFCInstage + $EstimatedQuantity ),0);

        print_r('Tổng số đề xuất mua: '. $SuggestOrder );
        print_r('<br>');



        // ghi record
        //$sql = " insert into pu_caculate_po_record(sku,the_content,the_date,quantity)
        //values('$sku','Đề xuất mua' ,'$EndSellingDate', $SuggestOrder )";
        //DB::connection('mysql')->select( $sql );

        if( $SuggestOrder>0)
        {
          // Lay toi thieu 30, lam tron va lay theo moq
          $SuggestOrder = max($SuggestOrder,30);
          $SuggestOrder = max($SuggestOrder,$d->moq);
          $SuggestOrder = (int)$SuggestOrder;
          $mystring = (string)$SuggestOrder;
          $myNumber = (int)substr($mystring, -1);
          if ($myNumber!=0 )
          {
            if($myNumber>=5)
            {
              $NewNum = 10 - $myNumber;
              $SuggestOrder = $SuggestOrder + $NewNum;
            }
            else
            {
              $SuggestOrder= $SuggestOrder- $myNumber;
            }
          }
        }//if $SuggestOrder>0

        if( $SuggestOrder >= 30 )
        {
          // insert to detail PO
          DB::connection('mysql')->table('pu_po_estimate_details')->insert(
          ['po_estimate_id' => $this->POID, 'sku' => $sku, 'quantity'=>$SuggestOrder,
          'sell_type'=>$d->sell_type,  'sell_status'=>$d->sell_status,   'fob_price'=>$d->fob_price,
          'appotion_price'=>$d->appotion_price,'moq'=> $d->moq,
          'balance'=>$TotalBalanceNow , 'balance_at_selling'=>$EndBalance, 'life_cycle'=>$d->life_cycle
          ] );

          // Ngày ngay sau ngày end selling date
          $OpenBalanceDate = date('Y-m-d',strtotime($EndSellingDate. '+ 1 days'));

          // Ghi Balance mới giả định cho một sku sau khi tính được số mua
          DB::connection('mysql')->table('pu_product_balance')->insert(
          ['sku' =>$sku, 'the_date'=>$OpenBalanceDate,
          'opening_balance'=> max( $EndBalance + $SuggestOrder - $TotalFCInstage - $EstimatedQuantity,0)
          ] );

          $SuggestOrder = 0;
          $this->BalanceOnAVC_WH = 0;
          $this->BalanceOnFBA = 0;
          $this->BalanceOnY4A = 0;
          $this->PipeLineY4A = 0;
        }//end if $SuggestOrder >= 30
      }// For ngoài cùng
    }
//----------------------------------------------------------------------------------------------------------------------
    public function GetDiPipelineInstage( $sku, $FromDate,$ToDate)
    {
      $Result = 0;
      $sql = " select sum(quantity) as Quantity from pu_di_pipeline
      where  comming_date >= '$FromDate' and comming_date <= '$ToDate' and sku = '$sku' ";
      $ds = DB::connection('mysql')->select($sql);
      foreach( $ds as $d ){
        $Result = $this->iif(is_null($d->Quantity),0,$d->Quantity);
      }
      return $Result;
    }

    // Tìm số Forecast của 3 kênh trong giai đoạn
    public function GetForecastQuantityInstage( $sku, $StartSellingDate,$EndSatge, $EndSellingDate)
    {

      $Y4AStoreID = 46;
      $SalesChanelFBA = 9;
      $SalesChanelAVC_WH = 1;
      $SalesChanelAVC_DI = 3;

      if($EndSatge < $EndSellingDate )// Nếu ngày cuối của giai đoạn nhỏ hơn ngày dự kiến bán hết hàng
      {
        $avcwh_level = 0;
        $EndSellingDate = $EndSatge ;
      }
      elseif($EndSatge == $EndSellingDate )
      {
       // lấy LOI để xác định ngày dự kiến bán hết sky này của PO này  avc-wh
        $avcwh_level = DB::connection('mysql')->table('sal_lois')->where('sku',$sku)->value('avcwh_level');
      }

      print_r('Loi = '.$avcwh_level);
      print_r('<br>');
      if($avcwh_level > 0){// Nếu LOI> 0 thì gia tăng ngày kết thúc bán lên theo level tính bằng tuần
        $EndSellingDateOnAVC_WH = date('Y-m-d',strtotime( $EndSellingDate. '+'. $avcwh_level * 7 .'days'));
      }else{
        $EndSellingDateOnAVC_WH =  $EndSellingDate;
      }

      print_r('EndSellingDateOnAVC_WH= '. $EndSellingDateOnAVC_WH);
      print_r('<br>');
       // ---------------- Tính số FC của kho Y4A -------------------------------

        $ForecastY4As = DB::connection('mysql')->select(
        "select sum(fcdt.quantity) as quantity from sal_forecasts  fc INNER JOIN
        sal_forecast_details fcdt on fc.id = fcdt.sales_forecast_id
        where  date(fc.the_date) >=  date('$StartSellingDate')
        and date(fc.the_date) <=  date('$EndSellingDate')
        and fc.sku = '$sku'
        and fcdt.channel_id not in ($SalesChanelFBA ,$SalesChanelAVC_WH, $SalesChanelAVC_DI)");
        $this->ForeCastOnY4AInStage =0;
        foreach( $ForecastY4As as $ForecastY4A ){
          $this->ForeCastOnY4AInStage =  $this->iif(is_null($ForecastY4A->quantity),0,$ForecastY4A->quantity);
        }
       // print_r('ForeCastOnY4AInStage:'.$this->ForeCastOnY4AInStage );
       // print_r('<br>' );

      // ---------------- Tính số FC của kho FBA -------------------------------
      // Tăng endselling date của FBA lên thêm 2 tuần
      $EndSellingDateOnFBA = date('Y-m-d',strtotime($EndSellingDate. '+ 14 days'));
      $ForecastFBAs = DB::connection('mysql')->select(
      " select sum(fcdt.quantity) as quantity from sal_forecasts  fc
      INNER JOIN sal_forecast_details fcdt on fc.id = fcdt.sales_forecast_id
      where date(fc.the_date) >= date('$StartSellingDate')
      and date(fc.the_date) <= date('$EndSellingDateOnFBA')
      and fc.sku = '$sku'
      and fcdt.channel_id in ($SalesChanelFBA)");
      $this->ForeCastOnFBAInStage =0;
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

      $this->ForeCastOnAVC_WHInStage=0;
      foreach( $ForecastAVC_WHs as $ForecastAVC_WH ){
        $this->ForeCastOnAVC_WHInStage =  $this->iif(is_null($ForecastAVC_WH->quantity),0,$ForecastAVC_WH->quantity);
      }
    }

    // -----Tính số tồn kho đầu ngày tại một thời điểm của 3 kho y4a,avc-wh, fba trong quá khư hoặc ngày hiện tại

    public function GetBalanceBeginningOnTheDate( $sku, $TheDate )
    {
      $Y4AStoreID = 46;
      // Lấy số tồn kho Y4A
      $BalanceY4As = DB::connection('mysql_it')->select("
      select sum(if(a.quantity is not null , a.quantity,0)) as  quantity
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
        and p2.published = 1
      )a");

      $this->BalanceOnY4A = 0;
      foreach( $BalanceY4As as $BalanceY4A ){
        $this->BalanceOnY4A  =  $this->iif(is_null($BalanceY4A->quantity),0,$BalanceY4A->quantity);
      }


      // Lấy số pipeline của Y4A
       $PipeLines = DB::connection('mysql_it')->select("
       select case when  a.Quantity is null then 0 else a.Quantity end as Quantity from
       ( select sum( smcondt.quantity) as Quantity
       from shipment sm inner join
       shipment_containers smcon on sm.id =smcon.shipment_id  inner JOIN
       shipment_containers_details  smcondt on smcon.id = smcondt.container_id inner JOIN
       products prd on smcondt.product_id =prd.id
       where  smcon.status in (1,8)
       and  Date(sm.expect_stocking_date)  < Date('$TheDate')
       and prd.product_sku =  '$sku' )a where a.Quantity>0 ");

       // smcon.status in (1,8) -- trang thai cua container la start up/pipeline
       $this->PipeLineY4A=0;
       foreach( $PipeLines as $PipeLine ){
         if (is_null($PipeLine->Quantity)){ $this->PipeLineY4A = 0;}
         else{ $this->PipeLineY4A = $this->iif(is_null($PipeLine->Quantity),0,$PipeLine->Quantity); }
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
      $this->BalanceOnFBA = 0;
      foreach( $BalanceFBAs as $BalanceFBA ){
        $this->BalanceOnFBA   = $this->iif(is_null($BalanceFBA->quantity),0,$BalanceFBA->quantity);
      }

      // --------Tim so ton cua kenh AVC-WH ----------------
      $BalanceAVC_WHs = DB::connection('mysql')->select(" select GetBalanceOnAvcWh( '$sku') as quantity ");
      $this->BalanceOnAVC_WH =0;
      foreach($BalanceAVC_WHs as $BalanceAVC_WH ){
        $this->BalanceOnAVC_WH  = $this->iif(is_null($BalanceAVC_WH->quantity),0,$BalanceAVC_WH->quantity);  ;
      }

      // số pipeline của kênh DI đã được Sales confirm với Amazon nhưng chưa về kho của amazon
      $DiPipeLines = DB::connection('mysql')->select("SELECT sum(quantity)  as quantity
      FROM pu_di_pipeline   WHERE  comming_date < $TheDate and sku  =  '$sku' ");

      $this->PipeLineDI = 0;
      foreach($DiPipeLines as $ds ){  $this->PipeLineDI  = $this->iif(is_null($ds->quantity),0,$ds->quantity); }

  }

  // --------------------------- Lấy số Forecast của 3 kênh tại một ngày cụ thể
  public function GetForecastQuantityOnTheDate($sku,$TheDate)
    {
      $Y4AStoreID = 46;
      $SalesChanelFBA = 9;
      $SalesChanelAVC_WH = 2;
      $SalesChanelAVC_DI = 3;
      // ---------------- Tính số FC của kho Y4A -------------------------------
      $ForecastY4As = DB::connection('mysql')->select(
      "select sum(fcdt.quantity) as quantity from sal_forecasts  fc INNER JOIN
      sal_forecast_details fcdt on fc.id = fcdt.sales_forecast_id
      where  date(fc.the_date) =  date('$TheDate')
      and fc.sku = '$sku'
      and fcdt.channel_id not in ($SalesChanelFBA ,$SalesChanelAVC_WH, $SalesChanelAVC_DI )" );
      //- da di qua
      foreach( $ForecastY4As as $ForecastY4A ){
        $this->ForeCastOnY4A  =  $this->iif(is_null($ForecastY4A->quantity),0,$ForecastY4A->quantity);
      }
    // ---------------- Tính số FC của kho FBA -------------------------------

      $ForecastFBAs = DB::connection('mysql')->select(
        "select sum(fcdt.quantity) as quantity from sal_forecasts  fc INNER JOIN
        sal_forecast_details fcdt on fc.id = fcdt.sales_forecast_id
        where  date(fc.the_date) =  date('$TheDate')
        and fc.sku = '$sku'
        and fcdt.channel_id in ($SalesChanelFBA)");

      foreach( $ForecastFBAs as $ForecastFBA ){
        $this->ForeCastOnFBA   = $this->iif(is_null( $ForecastFBA->quantity),0, $ForecastFBA->quantity);
      }

    // ---------------- Tính số FC của kho AVCWH -------------------------------
      $ForecastAVC_WHs = DB::connection('mysql')->select(
        "select sum(fcdt.quantity) as quantity from sal_forecasts  fc INNER JOIN
        sal_forecast_details fcdt on fc.id = fcdt.sales_forecast_id
        where  date(fc.the_date) =  date('$TheDate')
        and fc.sku = '$sku'
        and fcdt.channel_id in ($SalesChanelAVC_WH)" );
        $this->ForeCastOnAVC_WH=0;
      foreach( $ForecastAVC_WHs as $ForecastAVC_WH ){
        $this->ForeCastOnAVC_WH = $this->iif(is_null($ForecastAVC_WH->quantity),0,$ForecastAVC_WH->quantity) ;
      }
    }
 //  ------------------------------------------------------------------------------

    //  ----------------------- GetPipeline On the Date -----------------------
    public function GetPipeLineOnTheDate($sku,$TheDate){
      // pipeline của kho y4a
      $PipeLines = DB::connection('mysql_it')->select(
        " select case when a.Quantity is null then 0 else a.Quantity end as Quantity from
      ( select sum(smcondt.quantity) as Quantity
      from shipment sm inner join
      shipment_containers smcon on sm.id =smcon.shipment_id  inner JOIN
      shipment_containers_details  smcondt on smcon.id = smcondt.container_id inner JOIN
      products prd on smcondt.product_id =prd.id
      where  smcon.status in (10,11)
      and  Date(smcon.stocking_date)  = Date('$TheDate')
      and prd.product_sku = '$sku'
      and smcondt.quantity > 0

      UNION

      select sum(smcondt.quantity) as Quantity
      from shipment sm inner join
      shipment_containers smcon on sm.id =smcon.shipment_id  inner JOIN
      shipment_containers_details  smcondt on smcon.id = smcondt.container_id inner JOIN
      products prd on smcondt.product_id = prd.id
      where  smcon.status in (1,8)

      and  Date(sm.expect_stocking_date)  = Date('$TheDate')
      and prd.product_sku =  '$sku'
      and smcondt.quantity > 0 )a where a.Quantity > 0 ");

      // (10,11) -- trang thai cua container  la impported/ complete
      // (1,8)   -- trang thai cua container la start up/pipeline
      $this->PipeLineY4A = 0;
      foreach( $PipeLines as $PipeLine ){
         $this->PipeLineY4A = $this->iif(is_null( $PipeLine->Quantity),0, $PipeLine->Quantity);
      }
      // pipeline của DI để tính vào tồn kho của avc-wh
      $sql = "select sum(quantity) as Quantity from pu_di_pipeline where  comming_date = $TheDate and sku = '$sku' ";
      $this->PipeLineDI = 0;
      $ds = DB::connection('mysql')->select($sql);
      foreach( $ds as $d ){
         $this->PipeLineDI = $this->iif(is_null($d->Quantity),0,$d->Quantity);
      }

    }
}

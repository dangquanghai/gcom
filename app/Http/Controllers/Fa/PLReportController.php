<?php
namespace App\Http\Controllers\Fa;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use DB;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Reader\IReadFilter;
use Validator;
use DateTime;

class PLReportController extends Controller
{
  private $gReturn =0;
  private $gRefund =0;
  private $gCOGS = 0;

  private $gReturnEBAY =0;
  private $gRefundEBAY =0;

  private $gEbayFinalFee = 0.0;
  private $gEbayPaypalFee =0.0; // Chỉ tính cho EBAY store inc


  // khai báo một số biến để tính tỷ lệ giữa fob,Freight Cost - CBM, Freight Cost - Weigh, Duties, Pallet Fee
  private $gFOB = 0;
  private $gFreightCost = 0;
  private $gDuties = 0;
  private $gPalletFee = 0;

  // Một số biến dùng để xử lý sheet Paypal Order
  private $IsEbay = true;
  private $IsOrder = true;
  private $EbayStore = 0;

  public function __construct()
  {
      $this->middleware('auth');
  }
// ============================================================================================================
public function CaculateScale($Year,$Month)
{
 // $Year = 2020;
 // $Month = 5;
  $this->gFOB = 0;
  $sql =" select fob,freight_cost,duties,pallet_fee from fa_scale_monthly where the_month = $Month and the_year = $Year " ;
  $ds= DB::connection('mysql')->select($sql);
  foreach($ds as $d)
  {
    $this->gFOB = $this->iif(is_null($d->fob),0,$d->fob ) ;
    $this->gFreightCost =  round($d->freight_cost,2);
    $this->gDuties = round($d->duties,2);
    $this->gPalletFee  =  round($d->pallet_fee,2);
  }

  if($this->gFOB == 0 )
  {
    $sql = " select sum(fob) as fob,sum(cmb) as cmb,sum(weight) as weight,
    sum(duties)as duties ,sum(pallet)as pallet, sum(fob + cmb + weight + duties+pallet) as total
    from fa_unit_costs where the_year = $Year and the_month	 = $Month  " ;

    $ds= DB::connection('mysql')->select($sql);
    foreach($ds as $d)
    {
      if($d->total<>0)
      {
        $this->gFOB = round($d->fob/$d->total*100,2);
        $this->gFreightCost =  round(($d->cmb + $d->weight)/$d->total*100,2);
        $this->gDuties = round($d->duties/$d->total*100,2);
        $this->gPalletFee  =  round($d->pallet/$d->total*100,2);
      }
      else
      {
        $this->gFOB =0;
        $this->gFreightCost = 0 ;
        $this->gDuties = 0;
        $this->gPalletFee  = 0;
      }
    }
  }
  /*
  if($Month==5)
  {
    print_r('fob'. $this->gFOB);
    print_r('<br>');
    print_r('gFreightCost'. $this->gFreightCost);
    print_r('<br>');

    print_r('gDuties'.  $this->gDuties);
    print_r('<br>');

    print_r('gPalletFee'. $this->gPalletFee);
    print_r('<br>');
  }
  */
}

// ============================================================================================================
public function MakePLReport($Year,$ByInvoice)
{
  // Dell all datas in PL report for year = $Year if exist
  $sql = " delete  from fa_pl_reports where the_year = $Year ";
  DB::connection('mysql')->select($sql);

  // Move all Articles to PL Report
  $sql = " insert into fa_pl_reports(article,des,account,the_year) select id, name, account_no, $Year from fa_account_pl_report_articles ";
  DB::connection('mysql')->select($sql);
  $this->CreateDataForArticleExcepEbay($Year,$ByInvoice);


  $this->CreateDataForArticleEbay($Year);

  $FromActicle=1;
  $ToArticle = 215;
  $this->UpdateLogicBetweenCol($Year,$FromActicle,$ToArticle);

  // II. Total net revenue
  // Update Gross amazon
  $articlesTo ="(10)";
  $articlesFrom ="(14,24,34, 44,54)";
  $this->UpdateLogicBetweenRow($articlesFrom, $articlesTo, $Year);

  // Update Net Revenue amazon
  $articlesTo ="(9)";
  $articlesFrom ="(11,21,31,41,51)";
  $this->UpdateLogicBetweenRow($articlesFrom, $articlesTo, $Year);


  // Update Gross Ebay
  $articlesTo ="(62)";
  $articlesFrom ="(66,76,86, 96)";
  $this->UpdateLogicBetweenRow($articlesFrom, $articlesTo, $Year);

  // Update Net Ebay
  $articlesTo ="(61)";
  $articlesFrom ="(63,73,83, 93)";
  $this->UpdateLogicBetweenRow($articlesFrom, $articlesTo, $Year);

  // WM

  // Update Gross WM
  $articlesTo ="(104)";
  $articlesFrom ="(108,118)";
  $this->UpdateLogicBetweenRow($articlesFrom, $articlesTo, $Year);

  // Update Net WM
  $articlesTo ="(103)";
  $articlesFrom ="(105,115)";
  $this->UpdateLogicBetweenRow($articlesFrom, $articlesTo, $Year);

 // Update TOTAL GROSS REVENUE
 $articlesTo ="(8)";
 $articlesFrom ="(10,62,104,126, 137)";
 $this->UpdateLogicBetweenRow($articlesFrom, $articlesTo, $Year);

 // Update TOTAL NET REVENUE
 $articlesTo ="(7)";
 $articlesFrom ="(9,61,103, 125,136)";
 $this->UpdateLogicBetweenRow($articlesFrom, $articlesTo, $Year);


  // III. Total Gross
  // COGS - Amz FBA
  $articlesTo ="(149)";
  $articlesFrom ="(150,151,152, 153)";
  $this->UpdateLogicBetweenRow($articlesFrom, $articlesTo, $Year);

  // COGS - Amz FBM
  $articlesTo ="(154)";
  $articlesFrom ="(155,156,157, 158)";
  $this->UpdateLogicBetweenRow($articlesFrom, $articlesTo, $Year);

  //COGS - Amz AVC WH
  $articlesTo ="(159)";
  $articlesFrom ="(160,161,162, 163)";
  $this->UpdateLogicBetweenRow($articlesFrom, $articlesTo, $Year);

  //Amz AVC Dropship
  $articlesTo ="(164)";
  $articlesFrom ="(165,166,167, 168)";
  $this->UpdateLogicBetweenRow($articlesFrom, $articlesTo, $Year);

  //COGS - Amz DI
  $articlesTo ="(169)";
  $articlesFrom ="(170,171,172, 173)";
  $this->UpdateLogicBetweenRow($articlesFrom, $articlesTo, $Year);


  // COGS - Ebay Fitness
  $articlesTo ="(175)";
  $articlesFrom ="(176,177,178, 179)";
  $this->UpdateLogicBetweenRow($articlesFrom, $articlesTo, $Year);

  // COGS - Ebay Inc
  $articlesTo ="(180)";
  $articlesFrom ="(181,182,183, 184)";
  $this->UpdateLogicBetweenRow($articlesFrom, $articlesTo, $Year);

  // COGS -  Ebay Infideals
  $articlesTo ="(185)";
  $articlesFrom ="(186,187,188, 189)";
  $this->UpdateLogicBetweenRow($articlesFrom, $articlesTo, $Year);

  // COGS - Ebay Idzo
  $articlesTo ="(190)";
  $articlesFrom ="(191,192,193, 194)";
  $this->UpdateLogicBetweenRow($articlesFrom, $articlesTo, $Year);

  // COGS - Walmart DSV
  $articlesTo ="(196)";
  $articlesFrom ="(197,198,199, 200)";
  $this->UpdateLogicBetweenRow($articlesFrom, $articlesTo, $Year);

  // 3.2 COGS - Walmart Marketplace
  $articlesTo ="(201)";
  $articlesFrom ="(202,203,204, 205)";
  $this->UpdateLogicBetweenRow($articlesFrom, $articlesTo, $Year);

  // 4. COGS - Website
  $articlesTo ="(206)";
  $articlesFrom ="(207,208,209, 210)";
  $this->UpdateLogicBetweenRow($articlesFrom, $articlesTo, $Year);

  //5.COGS - Craiglist/Local
  $articlesTo ="(211)";
  $articlesFrom ="(212,213,214, 215)";
  $this->UpdateLogicBetweenRow($articlesFrom, $articlesTo, $Year);

  //COGS -Walmart
  $articlesTo ="(195)";
  $articlesFrom ="(196,201)";
  $this->UpdateLogicBetweenRow($articlesFrom, $articlesTo, $Year);

  //COGS - Ebay
  $articlesTo ="(174)";
  $articlesFrom ="(175,180,185,190)";
  $this->UpdateLogicBetweenRow($articlesFrom, $articlesTo, $Year);

  //COGS - Amazon
  $articlesTo ="(148)";
  $articlesFrom ="(149,154,159, 164,169)";
  $this->UpdateLogicBetweenRow($articlesFrom, $articlesTo, $Year);

  //Total COGS -
  $articlesTo ="(147)";
  $articlesFrom ="(148,174,195, 206, 211)";
  $this->UpdateLogicBetweenRow($articlesFrom, $articlesTo, $Year);

  // Cập nhật dât cho mục IV. Profit
  //1.1 Amz - FBA
  $ArticlesNetRevenue="(11)";
  $ArticlesCogs="(149)";
  $ArticlesProfit="(218)";
  $this->CaculateProfit($ArticlesNetRevenue,$ArticlesCogs, $ArticlesProfit, $Year);
  //1.2 Amz - FBM
  $ArticlesNetRevenue="(21)";
  $ArticlesCogs="(154)";
  $ArticlesProfit="(219)";
  $this->CaculateProfit($ArticlesNetRevenue,$ArticlesCogs, $ArticlesProfit, $Year);
  //1.3 Amz - AVC WH
  $ArticlesNetRevenue="(31)";
  $ArticlesCogs="(159)";
  $ArticlesProfit="(220)";
  $this->CaculateProfit($ArticlesNetRevenue,$ArticlesCogs, $ArticlesProfit, $Year);
  //1.4 Amz - AVC Dropship
  $ArticlesNetRevenue="(41)";
  $ArticlesCogs="(164)";
  $ArticlesProfit="(221)";
  $this->CaculateProfit($ArticlesNetRevenue,$ArticlesCogs, $ArticlesProfit, $Year);
  //1.5 Amz - AVC DI
  $ArticlesNetRevenue="(51)";
  $ArticlesCogs="(169)";
  $ArticlesProfit="(222)";
  $this->CaculateProfit($ArticlesNetRevenue,$ArticlesCogs, $ArticlesProfit, $Year);

  //1. GP - Amazon
  $articlesTo ="(217)";
  $articlesFrom ="(218,219,220, 221,222)";
  $this->UpdateLogicBetweenRow($articlesFrom, $articlesTo, $Year);

  //2.1 Ebay - Fitness
  $ArticlesNetRevenue="(63)";
  $ArticlesCogs="(175)";
  $ArticlesProfit="(224)";
  $this->CaculateProfit($ArticlesNetRevenue,$ArticlesCogs, $ArticlesProfit, $Year);

  //2.2 Ebay - Inc
  $ArticlesNetRevenue="(73)";
  $ArticlesCogs="(180)";
  $ArticlesProfit="(225)";
  $this->CaculateProfit($ArticlesNetRevenue,$ArticlesCogs, $ArticlesProfit, $Year);

  //2.3 Ebay - Infideals
  $ArticlesNetRevenue="(83)";
  $ArticlesCogs="(185)";
  $ArticlesProfit="(226)";
  $this->CaculateProfit($ArticlesNetRevenue,$ArticlesCogs, $ArticlesProfit, $Year);

  //2.4 Ebay - Idzo
  $ArticlesNetRevenue="(93)";
  $ArticlesCogs="(190)";
  $ArticlesProfit="(227)";
  $this->CaculateProfit($ArticlesNetRevenue,$ArticlesCogs, $ArticlesProfit, $Year);

  //2.4 Ebay - Idzo
  $ArticlesNetRevenue="(93)";
  $ArticlesCogs="(190)";
  $ArticlesProfit="(227)";
  $this->CaculateProfit($ArticlesNetRevenue,$ArticlesCogs, $ArticlesProfit, $Year);

  //2. GP - Ebay
  $articlesTo ="(223)";
  $articlesFrom ="(224,225,226, 227)";
  $this->UpdateLogicBetweenRow($articlesFrom, $articlesTo, $Year);

  //3.1 Walmart - DSV
  $ArticlesNetRevenue="(105)";
  $ArticlesCogs="(196)";
  $ArticlesProfit="(229)";
  $this->CaculateProfit($ArticlesNetRevenue,$ArticlesCogs, $ArticlesProfit, $Year);

  //3.2 Walmart - Marketplace
  $ArticlesNetRevenue="(115)";
  $ArticlesCogs="(201)";
  $ArticlesProfit="(230)";
  $this->CaculateProfit($ArticlesNetRevenue,$ArticlesCogs, $ArticlesProfit, $Year);

  //3. GP - Walmart
  $articlesTo ="(228)";
  $articlesFrom ="(229,230)";
  $this->UpdateLogicBetweenRow($articlesFrom, $articlesTo, $Year);

  //4. GP - Website
  $ArticlesNetRevenue="(125)";
  $ArticlesCogs="(206)";
  $ArticlesProfit="(231)";
  $this->CaculateProfit($ArticlesNetRevenue,$ArticlesCogs, $ArticlesProfit, $Year);

  //5. GP- Craiglist/Local
  $ArticlesNetRevenue="(136)";
  $ArticlesCogs="(211)";
  $ArticlesProfit="(232)";
  $this->CaculateProfit($ArticlesNetRevenue,$ArticlesCogs, $ArticlesProfit, $Year);

   //IV TOTAL GROSS PROFIT
   $articlesTo ="(216)";
   $articlesFrom ="(217,223,228, 2312,232)";
   $this->UpdateLogicBetweenRow($articlesFrom, $articlesTo, $Year);

   $FromActicle=216;
   $ToArticle = 232;
   $this->UpdateLogicBetweenCol($Year,$FromActicle,$ToArticle);
}

// ============================================================================================================
private function CreateDataForArticleExcepEbay($Year,$InputByInvoice){
  // Declare Sales team
   $XL = 337;
   $ThuongChau = 336;
   $Wel = 307;
   $SalesTeams = array(0,$XL,$ThuongChau,$Wel);
   $sSalesTeams = array('ngongan','xl','tc','wel');
   $Months = array('khong','mot','hai','ba','bon','nam','sau','bay','tam','chin','muoi','mmot','mhai');
   $Channels= array(0,1,2,3,4,5,6,7,8,9,10);
   $Quaters =array('q1','q2','q3','q4');

   $UnitShipments= array(0,0,0,0);
   $GrossRevenues = array(0,0,0,0);// tổng doanh thu
   $Refunds = array(0,0,0,0);
   $Revenues= array(0,0,0,0); // doanh thu sau khi đã trừ đi tiền refund
   $Promotions = array(0,0,0,0);
   $SEMs = array(0,0,0,0);
   $Shippings = array(0,0,0,0);
   $OtherFees  = array(0,0,0,0);
   $TotalFees  = array(0,0,0,0);
   $NetRevenues  = array(0,0,0,0);// doanh thu trừ đi tất cả chi phí nhưng chưa trừ giá vốn

   $Cogs = array(0,0,0,0);
   $Purchasings  = array(0,0,0,0);
   $Freights   = array(0,0,0,0);
   $Duties  = array(0,0,0,0);
   $Pallets  = array(0,0,0,0);

   $UnitShipmentSql = '';
   $RevenueSql ='';
   $GrossSql ='';
   $RefundSql='';
   $TotalFeeSql = '';
   $ProSql = '';
   $SEMSql = '';
   $ShipSql ='';
   $OtherFeeSql ='';
   $NetRevenueSql ='';

   $CogsSql = '';
   $PurchasingSql  = '';
   $FreightSql   = '';
   $DutieSql  = '';
   $PalletSql  ='';
   //print_r('By Invoice'.$ByInvoice) ;

   $ByInvoice = $InputByInvoice;

  // Duyệt từ tháng 1 đến tháng 12 của năm
  for($Month = 1 ;$Month <= 12; $Month++)
    {
      $this->CaculateScale($Year,$Month);// Lấy tỷ lệ của từng tháng
      $this->AllocateInsuranceAndShippingCalProfitAndOtherSellingFee($Year,$Month,$InputByInvoice);//
      for($Channel = 1 ;$Channel <= 10 ; $Channel++)
        {
          if(($Channel == 1 ||  $Channel == 3) &&  $InputByInvoice ==1 ){ $ByInvoice =1; }
          else{ $ByInvoice = 0; }

          if($Channel != 6)
            {
            for($Department =1; $Department <= 3; $Department++ )
              {
                // 1. UnitShipment
                $sql = " select sum(sell_quantity - return_quantity) as UnitShipment from sal_selling_summary_monthlys
                where the_month = $Month   and the_year = $Year and by_invoice = $ByInvoice and sales_chanel = " . $Channels[$Channel] .
                " and department_id = ". $SalesTeams[$Department];

                //print_r('sql Unit Shipment: ' .  $sql );
               // print_r('<br>');

                $ds= DB::connection('mysql')->select($sql);
                foreach($ds as $d){$UnitShipments[$Department] = $this->iif(is_null( $d->UnitShipment),0, $d->UnitShipment);  }

                // 2. GrossRevenue
                $sql = " select sum(revenue) as revenue from sal_selling_summary_monthlys where the_month = $Month
                and the_year = $Year and by_invoice = $ByInvoice and sales_chanel = " . $Channels[$Channel].
                " and department_id = ". $SalesTeams[$Department];

                //print_r(' GrossRevenue sql : '.  $sql);
                //print_r('<br>'  );

                $ds= DB::connection('mysql')->select($sql);
                foreach($ds as $d){$GrossRevenues[$Department] = $this->iif(is_null( $d->revenue),0, $d->revenue);  }

                // 3. Refund
                $sql = " select sum(refund) as refund from sal_selling_summary_monthlys where the_month = $Month
                and the_year = $Year and by_invoice = $ByInvoice and sales_chanel =  $Channels[$Channel].
                and department_id = ". $SalesTeams[$Department] ;

                $ds= DB::connection('mysql')->select($sql);
                foreach($ds as $d){  $Refunds[$Department] =  $this->iif(is_null( $d->refund),0, $d->refund);   }

                // 5. Revenue = GrossRevenue -  Refund

                $Revenues[$Department] = $GrossRevenues[$Department] -  $Refunds[$Department] ;

                // 5. $Promotion
                $sql = " select sum(promotion) as promotion from sal_selling_summary_monthlys where the_month = $Month
                and the_year = $Year and by_invoice = $ByInvoice and sales_chanel = " . $Channels[$Channel].
                " and department_id = ". $SalesTeams[$Department];

                $ds= DB::connection('mysql')->select($sql);
                foreach($ds as $d){$Promotions[$Department] =  $this->iif(is_null($d->promotion),0, $d->promotion); }

                // 6. SEM
                $sql = " select sum(seo_sem) as seo_sem from sal_selling_summary_monthlys where the_month = $Month
                and the_year = $Year and by_invoice = $ByInvoice and sales_chanel = " . $Channels[$Channel].
                " and department_id = ". $SalesTeams[$Department] ;

                $ds= DB::connection('mysql')->select($sql);
                foreach($ds as $d){ $SEMs[$Department] =  $this->iif(is_null($d->seo_sem),0,$d->seo_sem);  }

                // 7. Shipping
                $sql = " select sum(shiping_fee) as shiping_fee from sal_selling_summary_monthlys where the_month = $Month
                and the_year = $Year and by_invoice = $ByInvoice and sales_chanel = " . $Channels[$Channel] .
                " and department_id = ". $SalesTeams[$Department];

                $ds= DB::connection('mysql')->select($sql);
                foreach($ds as $d){ $Shippings[$Department] =  $this->iif(is_null( $d->shiping_fee),0, $d->shiping_fee);  }

                // 8. OtherFee
                $sql = " select sum(other_selling_expensives) as OtherFee from sal_selling_summary_monthlys where the_month = $Month
                and the_year = $Year and by_invoice = $ByInvoice  and sales_chanel = " . $Channels[$Channel] .
                " and department_id = ". $SalesTeams[$Department];

                $ds= DB::connection('mysql')->select($sql);
                foreach($ds as $d){  $OtherFees[$Department] = $this->iif(is_null( $d->OtherFee),0, $d->OtherFee); }

                //9. Total Fee
                $TotalFees[$Department] =  $SEMs[$Department] + $Promotions[$Department] +  $Shippings[$Department]+ $OtherFees[$Department] ;

                //10. Net Revenue
                $NetRevenues[$Department] =   $Revenues[$Department]-  $TotalFees[$Department];

                //11. cogs
                $sql = " select sum(cogs) as cogs from sal_selling_summary_monthlys where the_month = $Month
                and the_year = $Year  and by_invoice = $ByInvoice and sales_chanel = " . $Channels[$Channel] .
                " and department_id = ". $SalesTeams[$Department];

                $ds= DB::connection('mysql')->select($sql);
                foreach($ds as $d){ $Cogs[$Department] = $this->iif(is_null($d->cogs),0, $d->cogs); }

                //12.Purchasings
                $Purchasings[$Department] =  $Cogs[$Department]/100 * $this->gFOB;
                //13.Freights
                $Freights[$Department] =  $Cogs[$Department]/100 * $this->gFreightCost ;
                //14.Duties
                $Duties[$Department]  =  $Cogs[$Department]/100 * $this->gDuties ;
                //15.Pallets
                $Pallets[$Department]  =  $Cogs[$Department] /100 *  $this->gPalletFee;


                if($Department == 1)
                  {
                    $UnitShipmentSql = $Months[$Month]. "_" . $sSalesTeams[$Department]  . "=" . $UnitShipments[$Department] . "," ;
                    $RevenueSql = $Months[$Month]. "_" . $sSalesTeams[$Department]  . "=" . $Revenues[$Department] . "," ;
                    $GrossSql = $Months[$Month]. "_" . $sSalesTeams[$Department]  . "=" . $GrossRevenues[$Department] . "," ;
                    $RefundSql  = $Months[$Month]. "_" . $sSalesTeams[$Department]  . "=" . $Refunds[$Department] . "," ;

                    $TotalFeeSql  = $Months[$Month]. "_" . $sSalesTeams[$Department]  . "=" . $TotalFees[$Department] . "," ;
                    $ProSql  = $Months[$Month]. "_" . $sSalesTeams[$Department]  . "=" . $Promotions [$Department] . "," ;
                    $SEMSql  = $Months[$Month]. "_" . $sSalesTeams[$Department]  . "=" . $SEMs[$Department] . "," ;
                    $ShipSql  = $Months[$Month]. "_" . $sSalesTeams[$Department]  . "=" .  $Shippings[$Department] . "," ;
                    $OtherFeeSql  = $Months[$Month]. "_" . $sSalesTeams[$Department]  . "=" . $OtherFees [$Department] . "," ;
                    $NetRevenueSql  = $Months[$Month]. "_" . $sSalesTeams[$Department]  . "=" .  $NetRevenues[$Department] . "," ;

                    $PurchasingSql  =  $Months[$Month]. "_" . $sSalesTeams[$Department]  . "=" .   $Purchasings[$Department] . "," ;
                    $FreightSql  =  $Months[$Month]. "_" . $sSalesTeams[$Department]  . "=" .    $Freights[$Department] . "," ;
                    $DutieSql =  $Months[$Month]. "_" . $sSalesTeams[$Department]  . "=" .    $Duties[$Department]  . "," ;
                    $PalletSql =  $Months[$Month]. "_" . $sSalesTeams[$Department]  . "=" .   $Pallets[$Department] . "," ;

                  }
                else
                  {
                    $UnitShipmentSql =  $UnitShipmentSql . $Months[$Month]. "_" . $sSalesTeams[$Department]  . "=" . $UnitShipments[$Department] . "," ;
                    $RevenueSql =  $RevenueSql. $Months[$Month]. "_" . $sSalesTeams[$Department]  . "=" . $Revenues[$Department] . "," ;
                    $GrossSql = $GrossSql . $Months[$Month]. "_" . $sSalesTeams[$Department]  . "=" . $GrossRevenues[$Department] . "," ;
                    $RefundSql = $RefundSql . $Months[$Month]. "_" . $sSalesTeams[$Department]  . "=" . $Refunds[$Department] . "," ;

                    $TotalFeeSql  = $TotalFeeSql. $Months[$Month]. "_" . $sSalesTeams[$Department]  . "=" . $TotalFees[$Department] . "," ;
                    $ProSql =  $ProSql . $Months[$Month]. "_" . $sSalesTeams[$Department]  . "=" . $Promotions [$Department] . "," ;
                    $SEMSql =  $SEMSql . $Months[$Month]. "_" . $sSalesTeams[$Department]  . "=" . $SEMs[$Department] . "," ;
                    $ShipSql = $ShipSql . $Months[$Month]. "_" . $sSalesTeams[$Department]  . "=" .  $Shippings[$Department] . "," ;
                    $OtherFeeSql = $OtherFeeSql . $Months[$Month]. "_" . $sSalesTeams[$Department]  . "=" . $OtherFees [$Department] . "," ;
                    $NetRevenueSql  =  $NetRevenueSql . $Months[$Month]. "_" . $sSalesTeams[$Department]  . "=" .  $NetRevenues[$Department] . "," ;

                    $PurchasingSql  = $PurchasingSql . $Months[$Month]. "_" . $sSalesTeams[$Department]  . "=" .   $Purchasings[$Department] . "," ;
                    $FreightSql  =  $FreightSql. $Months[$Month]. "_" . $sSalesTeams[$Department]  . "=" .    $Freights[$Department] . "," ;
                    $DutieSql = $DutieSql.  $Months[$Month]. "_" . $sSalesTeams[$Department]  . "=" .    $Duties[$Department]  . "," ;
                    $PalletSql = $PalletSql. $Months[$Month]. "_" . $sSalesTeams[$Department]  . "=" .   $Pallets[$Department] . "," ;
                  }
                }// For Department
                  $UnitShipmentSql = rtrim($UnitShipmentSql, ",");
                  //print_r('sql Unit Shipment: ' . $UnitShipmentSql );
                  //print_r('<br>');
                  $RevenueSql= rtrim($RevenueSql, ",");
                  $GrossSql = rtrim($GrossSql, ",");
                  $RefundSql = rtrim($RefundSql, ",");

                  $TotalFeeSql= rtrim($TotalFeeSql, ",");
                  $ProSql = rtrim($ProSql, ",");
                  $SEMSql = rtrim($SEMSql, ",");
                  $ShipSql = rtrim($ShipSql, ",");
                  $OtherFeeSql = rtrim($OtherFeeSql, ",");
                  $NetRevenueSql= rtrim($NetRevenueSql, ",");

                  $PurchasingSql = rtrim($PurchasingSql, ",");
                  $FreightSql = rtrim($FreightSql, ",");
                  $DutieSql = rtrim($DutieSql, ",");
                  $PalletSql= rtrim($PalletSql, ",");

                  //print_r('Gross sql:'. $GrossSql);
                 //print_r('<br>');

                  $this->CaculateForBasicArticlesExceptEbay($Year,$Month,$Channel,$UnitShipmentSql, $RevenueSql,$GrossSql, $RefundSql ,
                  $TotalFeeSql,$ProSql, $SEMSql , $ShipSql , $OtherFeeSql, $NetRevenueSql,
                  $PurchasingSql ,$FreightSql , $DutieSql , $PalletSql );
                }// end if channel != 6
      }// For chanel
    }// For Month
}// end function
// ============================================================================================================
// ============================================================================================================
private function CreateDataForArticleEbay($Year){
  // Declare Sales team
   $XL = 337;
   $ThuongChau = 336;
   $Wel = 307;
   $SalesTeams = array(0,$XL,$ThuongChau,$Wel);

   $sSalesTeams = array('ngongan','xl','tc','wel');
   $Months = array('khong','mot','hai','ba','bon','nam','sau','bay','tam','chin','muoi','mmot','mhai');
   $Quaters =array('q1','q2','q3','q4');

   $UnitShipments= array(0,0,0,0);
   $GrossRevenues = array(0,0,0,0);
   $Refunds = array(0,0,0,0);

   $Promotions = array(0,0,0,0);
   $SEMs = array(0,0,0,0);
   $Shippings = array(0,0,0,0);
   $OtherFees  = array(0,0,0,0);
   $NetRevenues  = array(0,0,0,0);

   $Cogs = array(0,0,0,0);
   $Purchasings  = array(0,0,0,0);
   $Freights   = array(0,0,0,0);
   $Duties  = array(0,0,0,0);
   $Pallets  = array(0,0,0,0);

   $UnitShipmentSql = '';
   $GrossSql ='';
   $RefundSql='';
   $ProSql = '';
   $SEMSql = '';
   $ShipSql ='';
   $OtherFeeSql ='';
   $NetRevenueSql ='';

   $CogsSql = '';
   $PurchasingSql  = '';
   $FreightSql   = '';
   $DutieSql  = '';
   $PalletSql  ='';

  // Duyệt từ tháng 1 đến tháng 12 của năm
  for($Month = 1 ;$Month <= 12 ; $Month++)
    {
      $this->CaculateScale($Year,$Month);// Lay ty le của từng tháng
    //  $this->AllocateInsuranceAndShippingCalProfitAndOtherSellingFee($Year,$Month,$InputByInvoice);//
      for($Store = 1 ;$Store <= 4 ; $Store++)
        {
          for($Department = 1; $Department <= 3; $Department++ )
            {
              // 1. UnitShipment
              $sql = " select sum(sell_quantity - return_quantity) as UnitShipment from sal_selling_summary_monthlys
               where the_year = $Year and the_month = $Month and sales_chanel = 6 and store = $Store ".
               " and  department_id =". $SalesTeams[$Department] ;

              $ds= DB::connection('mysql')->select($sql);
              foreach($ds as $d){$UnitShipments[$Department] = $this->iif(is_null( $d->UnitShipment),0, $d->UnitShipment);  }

              // 2. GrossRevenue
              $sql = " select sum(revenue) as revenue from sal_selling_summary_monthlys
              where the_year = $Year and the_month = $Month and sales_chanel = 6 and store = $Store ".
              " and  department_id =". $SalesTeams[$Department] ;

              $ds= DB::connection('mysql')->select($sql);
              foreach($ds as $d){$GrossRevenues[$Department] = $this->iif(is_null( $d->revenue),0, $d->revenue);  }

              // 3. Refund
              $sql = " select sum(refund) as refund from sal_selling_summary_monthlys
              where the_year = $Year and the_month = $Month and sales_chanel = 6 and store = $Store ".
              " and  department_id =". $SalesTeams[$Department] ;

              $ds= DB::connection('mysql')->select($sql);
              foreach($ds as $d){  $Refunds[$Department] =  $this->iif(is_null( $d->refund),0, $d->refund);   }

              // 4. Revenue = GrossRevenue - Refund
              $Revenues[$Department] =$GrossRevenues[$Department] -  $Refunds[$Department] ;

              // 5. $Promotion
              $sql = " select sum(promotion) as promotion from sal_selling_summary_monthlys
              where the_year = $Year and the_month = $Month and sales_chanel = 6 and store = $Store ".
              " and  department_id =". $SalesTeams[$Department] ;

              $ds= DB::connection('mysql')->select($sql);
              foreach($ds as $d){ $Promotions[$Department] =  $this->iif(is_null($d->promotion),0, $d->promotion); }

              // 6. SEM
              $sql = " select sum(seo_sem) as seo_sem from sal_selling_summary_monthlys
              where the_year = $Year and the_month = $Month and sales_chanel = 6 and store = $Store ".
              " and  department_id =". $SalesTeams[$Department] ;

              $ds= DB::connection('mysql')->select($sql);
              foreach($ds as $d){   $SEMs[$Department] =  $this->iif(is_null($d->seo_sem),0,$d->seo_sem);  }

              // 7. Shipping
              $sql = " select sum(shiping_fee) as shiping_fee from sal_selling_summary_monthlys
              where the_year = $Year and the_month = $Month and sales_chanel = 6 and store = $Store ".
              " and  department_id =". $SalesTeams[$Department] ;

              $ds= DB::connection('mysql')->select($sql);
              foreach($ds as $d){ $Shippings[$Department] =  $this->iif(is_null( $d->shiping_fee),0, $d->shiping_fee);  }

              // 8. OtherFee
              $sql = " select sum(other_selling_expensives) as OtherFee from sal_selling_summary_monthlys
              where the_year = $Year and the_month = $Month and sales_chanel = 6 and store = $Store ".
              " and  department_id =". $SalesTeams[$Department] ;

              $ds= DB::connection('mysql')->select($sql);
              foreach($ds as $d){  $OtherFees[$Department] = $this->iif(is_null( $d->OtherFee),0, $d->OtherFee); }

              // 9. Total Fee  = Promotion + SEM+ Shiping + OtherFee
              $TotalFees[$Department] =   $SEMs[$Department] +  $Promotions[$Department]  + $Shippings[$Department]+ $OtherFees[$Department] ;

              //10. Net Revenue
              $NetRevenues[$Department] = $Revenues[$Department]-  $TotalFees[$Department];

              //11. cogs
              $sql = " select sum(cogs) as cogs from sal_selling_summary_monthlys
              where the_year = $Year and the_month = $Month and sales_chanel = 6 and store = $Store ".
              " and  department_id =". $SalesTeams[$Department] ;

              $ds= DB::connection('mysql')->select($sql);
              foreach($ds as $d) {  $Cogs[$Department] = $this->iif(is_null( $d->cogs),0, $d->cogs);}

              //12.Purchasings
              $Purchasings[$Department] =  $Cogs[$Department] /100 * $this->gFOB;
              //13.Freights
              $Freights[$Department] =  $Cogs[$Department]/100* $this->gFreightCost ;
              //14.Duties
              $Duties[$Department]  =  $Cogs[$Department]/100* $this->gDuties ;
              //15.Pallets
              $Pallets[$Department]  = $Cogs[$Department]/100*  $this->gPalletFee ;


            if($Department == 1)
              {
                $UnitShipmentSql = $Months[$Month]. "_" . $sSalesTeams[$Department]  . "=" . $UnitShipments[$Department] . "," ;
                $RevenueSql = $Months[$Month]. "_" . $sSalesTeams[$Department]  . "=" . $Revenues[$Department] . "," ;
                $GrossSql = $Months[$Month]. "_" . $sSalesTeams[$Department]  . "=" . $GrossRevenues[$Department] . "," ;
                $RefundSql  = $Months[$Month]. "_" . $sSalesTeams[$Department]  . "=" . $Refunds[$Department] . "," ;
                $TotalFeeSql  = $Months[$Month]. "_" . $sSalesTeams[$Department]  . "=" . $TotalFees[$Department] . "," ;
                $ProSql  = $Months[$Month]. "_" . $sSalesTeams[$Department]  . "=" . $Promotions [$Department] . "," ;
                $SEMSql  = $Months[$Month]. "_" . $sSalesTeams[$Department]  . "=" . $SEMs[$Department] . "," ;
                $ShipSql  = $Months[$Month]. "_" . $sSalesTeams[$Department]  . "=" .  $Shippings[$Department] . "," ;
                $OtherFeeSql  = $Months[$Month]. "_" . $sSalesTeams[$Department]  . "=" . $OtherFees [$Department] . "," ;
                $NetRevenueSql  = $Months[$Month]. "_" . $sSalesTeams[$Department]  . "=" .  $NetRevenues[$Department] . "," ;

                $PurchasingSql  =  $Months[$Month]. "_" . $sSalesTeams[$Department]  . "=" .   $Purchasings[$Department] . "," ;
                $FreightSql  =  $Months[$Month]. "_" . $sSalesTeams[$Department]  . "=" .    $Freights[$Department] . "," ;
                $DutieSql =  $Months[$Month]. "_" . $sSalesTeams[$Department]  . "=" .    $Duties[$Department]  . "," ;
                $PalletSql =  $Months[$Month]. "_" . $sSalesTeams[$Department]  . "=" .   $Pallets[$Department] . "," ;
              }
            else
              {
                $UnitShipmentSql =  $UnitShipmentSql . $Months[$Month]. "_" . $sSalesTeams[$Department]  . "=" . $UnitShipments[$Department] . "," ;
                $RevenueSql =  $RevenueSql. $Months[$Month]. "_" . $sSalesTeams[$Department]  . "=" . $Revenues[$Department] . "," ;
                $GrossSql = $GrossSql . $Months[$Month]. "_" . $sSalesTeams[$Department]  . "=" . $GrossRevenues[$Department] . "," ;
                $RefundSql = $RefundSql . $Months[$Month]. "_" . $sSalesTeams[$Department]  . "=" . $Refunds[$Department] . "," ;
                $TotalFeeSql  = $TotalFeeSql. $Months[$Month]. "_" . $sSalesTeams[$Department]  . "=" . $TotalFees[$Department] . "," ;
                $ProSql =  $ProSql . $Months[$Month]. "_" . $sSalesTeams[$Department]  . "=" . $Promotions [$Department] . "," ;
                $SEMSql =  $SEMSql . $Months[$Month]. "_" . $sSalesTeams[$Department]  . "=" . $SEMs[$Department] . "," ;
                $ShipSql = $ShipSql . $Months[$Month]. "_" . $sSalesTeams[$Department]  . "=" .  $Shippings[$Department] . "," ;
                $OtherFeeSql = $OtherFeeSql . $Months[$Month]. "_" . $sSalesTeams[$Department]  . "=" . $OtherFees [$Department] . "," ;
                $NetRevenueSql  =  $NetRevenueSql . $Months[$Month]. "_" . $sSalesTeams[$Department]  . "=" .  $NetRevenues[$Department] . "," ;

                $PurchasingSql  = $PurchasingSql . $Months[$Month]. "_" . $sSalesTeams[$Department]  . "=" .   $Purchasings[$Department] . "," ;
                $FreightSql  =  $FreightSql. $Months[$Month]. "_" . $sSalesTeams[$Department]  . "=" .    $Freights[$Department] . "," ;
                $DutieSql = $DutieSql.  $Months[$Month]. "_" . $sSalesTeams[$Department]  . "=" .    $Duties[$Department]  . "," ;
                $PalletSql = $PalletSql. $Months[$Month]. "_" . $sSalesTeams[$Department]  . "=" .   $Pallets[$Department] . "," ;
              }
        }// For Department
        $UnitShipmentSql = rtrim($UnitShipmentSql, ",");
        $RevenueSql= rtrim($RevenueSql, ",");

        $GrossSql = rtrim($GrossSql, ",");

        $RefundSql = rtrim($RefundSql, ",");
        $TotalFeeSql = rtrim($TotalFeeSql, ",");
        $ProSql = rtrim($ProSql, ",");
        $SEMSql = rtrim($SEMSql, ",");
        $ShipSql = rtrim($ShipSql, ",");
        $OtherFeeSql = rtrim($OtherFeeSql, ",");
        $NetRevenueSql= rtrim($NetRevenueSql, ",");

        $PurchasingSql = rtrim($PurchasingSql, ",");
        $FreightSql = rtrim($FreightSql, ",");
        $DutieSql = rtrim($DutieSql, ",");
        $PalletSql= rtrim($PalletSql, ",");

        $this->CaculateForBasicArticlesForEbay($Year,$Month,$Store, $UnitShipmentSql, $RevenueSql,$GrossSql,$RefundSql , $TotalFeeSql,
        $ProSql, $SEMSql , $ShipSql , $OtherFeeSql,$NetRevenueSql, $PurchasingSql,$FreightSql ,    $DutieSql,  $PalletSql );


      }// For Store
    }// For Month
}// end function
// ============================================================================================================
public function CaculateProfit($ArticlesNetRevenue,$ArticlesCogs, $ArticlesProfit, $Year)
{
  $sql = " select  sum(grant_total_total) as grant_total_total ,  sum(grant_total_xl) as grant_total_xl ,
  sum(grant_total_tc) as grant_total_tc, sum(grant_total_wel) as grant_total_wel  , sum(mot_total) as mot_total ,
  sum(mot_xl) as mot_xl , sum(mot_tc) as mot_tc,sum(mot_wel)as mot_wel , sum(hai_total) as hai_total ,
  sum(hai_xl) as hai_xl , sum(hai_tc) as hai_tc ,  sum(hai_wel) as hai_wel, sum(ba_total) as ba_total, sum(ba_xl) as ba_xl ,
  sum(ba_tc) as ba_tc ,  sum(ba_wel) as ba_wel  ,  sum(q1_total) q1_total ,sum(q1_xl) as q1_xl , sum(q1_tc) as q1_tc ,
  sum(q1_wel) as q1_wel , sum(bon_total) as bon_total , sum(bon_xl) as bon_xl  , sum(bon_tc) as bon_tc  ,
  sum(bon_wel) as bon_wel  , sum(nam_total) as nam_total  ,  sum(nam_xl) as nam_xl , sum(nam_tc) as nam_tc ,
  sum(nam_wel) as nam_wel,  sum(sau_total) as sau_total,  sum(sau_xl) as sau_xl  ,  sum(sau_tc) as sau_tc  ,
  sum(sau_wel) as sau_wel , sum(q2_total) as q2_total , sum(q2_xl) as q2_xl  ,sum(q2_tc) as q2_tc ,
  sum(q2_wel) as q2_wel  , sum(bay_total) as bay_total , sum(bay_xl) as bay_xl , sum(bay_tc) as bay_tc ,
  sum(bay_wel) as bay_wel  ,sum(tam_total) as tam_total  , sum(tam_xl) tam_xl , sum(tam_tc) as tam_tc , sum(tam_wel) as tam_wel ,
  sum(chin_total) as chin_total,sum(chin_xl) as chin_xl , sum(chin_tc) as chin_tc ,  sum(chin_wel)as chin_wel , sum(q3_total) as q3_total,
  sum(q3_xl) as q3_xl , sum(q3_tc) as q3_tc,sum(q3_wel) as q3_wel ,sum(muoi_total) as muoi_total , sum(muoi_xl) as muoi_xl , sum(muoi_tc) as muoi_tc,
  sum(muoi_wel) as muoi_wel , sum(mmot_total) as mmot_total , sum(mmot_xl)  as mmot_xl, sum(mmot_tc) as mmot_tc ,sum(mmot_wel) as mmot_wel ,
  sum(mhai_total) as mhai_total ,sum(mhai_xl) as mhai_xl  , sum(mhai_tc) as mhai_tc , sum(mhai_wel) as mhai_wel ,
  sum(q4_total)  as q4_total,  sum(q4_xl) as q4_xl , sum(q4_tc) as q4_tc ,sum(q4_wel)  as q4_wel
  from fa_pl_reports  where article in " . $ArticlesNetRevenue . " and  the_year =" . $Year;

  $ds= DB::connection('mysql')->select($sql);
  foreach($ds as $d)
  {
    $grant_total_total =  $d->grant_total_total;
    $grant_total_xl =  $d->grant_total_xl;
    $grant_total_tc  = $d->grant_total_tc;
    $grant_total_wel  = $d->grant_total_wel;
    $mot_total  = $d->mot_total ;
    $mot_xl  = $d->mot_xl ;
    $mot_tc = $d->mot_tc;
    $mot_wel = $d->mot_wel;
    $hai_total = $d->hai_total;
    $hai_xl  = $d->hai_xl;
    $hai_tc = $d->hai_tc;
    $hai_wel = $d->hai_wel;
    $ba_total = $d->ba_total;
    $ba_xl = $d->ba_xl;
    $ba_tc = $d->ba_tc;
    $ba_wel = $d->ba_wel;
    $q1_total = $d->q1_total ;
    $q1_xl = $d->q1_xl ;
    $q1_tc = $d->q1_tc ;
    $q1_wel = $d->q1_wel ;
    $bon_total  = $d->bon_total;
    $bon_xl  = $d->bon_xl ;
    $bon_tc  = $d->bon_tc;
    $bon_wel  = $d->bon_wel ;
    $nam_total = $d->nam_total ;
    $nam_xl  = $d->nam_xl;
    $nam_tc = $d->nam_tc ;
    $nam_wel = $d->nam_wel ;
    $sau_total  = $d->sau_total;
    $sau_xl  = $d->sau_xl ;
    $sau_tc  = $d->sau_tc ;
    $sau_wel  = $d->sau_wel ;
    $q2_total = $d->q2_total ;
    $q2_xl  = $d->q2_xl ;
    $q2_tc  = $d->q2_tc;
    $q2_wel = $d->q2_wel;
    $bay_total = $d->bay_total ;
    $bay_xl = $d->bay_xl ;
    $bay_tc = $d->bay_tc ;
    $bay_wel  = $d->bay_wel ;
    $tam_total = $d->tam_total ;
    $tam_xl  = $d->tam_xl;
    $tam_tc  = $d->tam_tc;
    $tam_wel  = $d->tam_wel;
    $chin_total  = $d->chin_total;
    $chin_xl  = $d->chin_xl;
    $chin_tc = $d->chin_tc ;
    $chin_wel  = $d->chin_wel;
    $q3_total = $d->q3_total;
    $q3_xl  = $d->q3_xl;
    $q3_tc  = $d->q3_tc;
    $q3_wel  = $d->q3_wel ;
    $muoi_total = $d->muoi_total;
    $muoi_xl  = $d->muoi_xl;
    $muoi_tc  = $d->muoi_tc;
    $muoi_wel  = $d->muoi_wel;
    $mmot_total  = $d->mmot_total ;
    $mmot_xl  = $d->mmot_xl;
    $mmot_tc  = $d->mmot_tc;
    $mmot_wel  = $d->mmot_wel;
    $mhai_total   = $d->mhai_total;
    $mhai_xl   = $d->mhai_xl;
    $mhai_tc  = $d->mhai_tc ;
    $mhai_wel = $d->mhai_wel;
    $q4_total  = $d->q4_total;
    $q4_xl  = $d->q4_xl;
    $q4_tc  = $d->q4_tc;
    $q4_wel = $d->q4_wel;
  }


  $sql = " select  sum(grant_total_total) as grant_total_total ,  sum(grant_total_xl) as grant_total_xl ,
  sum(grant_total_tc) as grant_total_tc, sum(grant_total_wel) as grant_total_wel  , sum(mot_total) as mot_total ,
  sum(mot_xl) as mot_xl , sum(mot_tc) as mot_tc,sum(mot_wel)as mot_wel , sum(hai_total) as hai_total ,
  sum(hai_xl) as hai_xl , sum(hai_tc) as hai_tc ,  sum(hai_wel) as hai_wel, sum(ba_total) as ba_total, sum(ba_xl) as ba_xl ,
  sum(ba_tc) as ba_tc ,  sum(ba_wel) as ba_wel  ,  sum(q1_total) q1_total ,sum(q1_xl) as q1_xl , sum(q1_tc) as q1_tc ,
  sum(q1_wel) as q1_wel , sum(bon_total) as bon_total , sum(bon_xl) as bon_xl  , sum(bon_tc) as bon_tc  ,
  sum(bon_wel) as bon_wel  , sum(nam_total) as nam_total  ,  sum(nam_xl) as nam_xl , sum(nam_tc) as nam_tc ,
  sum(nam_wel) as nam_wel,  sum(sau_total) as sau_total,  sum(sau_xl) as sau_xl  ,  sum(sau_tc) as sau_tc  ,
  sum(sau_wel) as sau_wel , sum(q2_total) as q2_total , sum(q2_xl) as q2_xl  ,sum(q2_tc) as q2_tc ,
  sum(q2_wel) as q2_wel  , sum(bay_total) as bay_total , sum(bay_xl) as bay_xl , sum(bay_tc) as bay_tc ,
  sum(bay_wel) as bay_wel  ,sum(tam_total) as tam_total  , sum(tam_xl) tam_xl , sum(tam_tc) as tam_tc , sum(tam_wel) as tam_wel ,
  sum(chin_total) as chin_total,sum(chin_xl) as chin_xl , sum(chin_tc) as chin_tc ,  sum(chin_wel)as chin_wel , sum(q3_total) as q3_total,
  sum(q3_xl) as q3_xl , sum(q3_tc) as q3_tc,sum(q3_wel) as q3_wel ,sum(muoi_total) as muoi_total , sum(muoi_xl) as muoi_xl , sum(muoi_tc) as muoi_tc,
  sum(muoi_wel) as muoi_wel , sum(mmot_total) as mmot_total , sum(mmot_xl)  as mmot_xl, sum(mmot_tc) as mmot_tc ,sum(mmot_wel) as mmot_wel ,
  sum(mhai_total) as mhai_total ,sum(mhai_xl) as mhai_xl  , sum(mhai_tc) as mhai_tc , sum(mhai_wel) as mhai_wel ,
  sum(q4_total)  as q4_total,  sum(q4_xl) as q4_xl , sum(q4_tc) as q4_tc ,sum(q4_wel)  as q4_wel
  from fa_pl_reports  where article in " . $ArticlesCogs . " and  the_year =" . $Year;

  $ds= DB::connection('mysql')->select($sql);
  foreach($ds as $d)
  {
    $grant_total_total1 =  $d->grant_total_total;
    $grant_total_xl1 =  $d->grant_total_xl;
    $grant_total_tc1  = $d->grant_total_tc;
    $grant_total_wel1  = $d->grant_total_wel;
    $mot_total1  = $d->mot_total ;
    $mot_xl1  = $d->mot_xl ;
    $mot_tc1 = $d->mot_tc;
    $mot_wel1 = $d->mot_wel;
    $hai_total1 = $d->hai_total;
    $hai_xl1  = $d->hai_xl;
    $hai_tc1 = $d->hai_tc;
    $hai_wel1 = $d->hai_wel;
    $ba_total1 = $d->ba_total;
    $ba_xl1 = $d->ba_xl;
    $ba_tc1 = $d->ba_tc;
    $ba_wel1 = $d->ba_wel;
    $q1_total1 = $d->q1_total ;
    $q1_xl1 = $d->q1_xl ;
    $q1_tc1 = $d->q1_tc ;
    $q1_wel1 = $d->q1_wel ;
    $bon_total1  = $d->bon_total;
    $bon_xl1  = $d->bon_xl ;
    $bon_tc1  = $d->bon_tc;
    $bon_wel1  = $d->bon_wel ;
    $nam_total1 = $d->nam_total ;
    $nam_xl1  = $d->nam_xl;
    $nam_tc1 = $d->nam_tc ;
    $nam_wel1 = $d->nam_wel ;
    $sau_total1  = $d->sau_total;
    $sau_xl1  = $d->sau_xl ;
    $sau_tc1  = $d->sau_tc ;
    $sau_wel1  = $d->sau_wel ;
    $q2_total1 = $d->q2_total ;
    $q2_xl1  = $d->q2_xl ;
    $q2_tc1  = $d->q2_tc;
    $q2_wel1 = $d->q2_wel;
    $bay_total1 = $d->bay_total ;
    $bay_xl1 = $d->bay_xl ;
    $bay_tc1 = $d->bay_tc ;
    $bay_wel1  = $d->bay_wel ;
    $tam_total1 = $d->tam_total ;
    $tam_xl1  = $d->tam_xl;
    $tam_tc1  = $d->tam_tc;
    $tam_wel1  = $d->tam_wel;
    $chin_total1  = $d->chin_total;
    $chin_xl1  = $d->chin_xl;
    $chin_tc1 = $d->chin_tc ;
    $chin_wel1  = $d->chin_wel;
    $q3_total1 = $d->q3_total;
    $q3_xl1  = $d->q3_xl;
    $q3_tc1  = $d->q3_tc;
    $q3_wel1  = $d->q3_wel ;
    $muoi_total1 = $d->muoi_total;
    $muoi_xl1  = $d->muoi_xl;
    $muoi_tc1  = $d->muoi_tc;
    $muoi_wel1  = $d->muoi_wel;
    $mmot_total1  = $d->mmot_total ;
    $mmot_xl1  = $d->mmot_xl;
    $mmot_tc1  = $d->mmot_tc;
    $mmot_wel1  = $d->mmot_wel;
    $mhai_total1   = $d->mhai_total;
    $mhai_xl1   = $d->mhai_xl;
    $mhai_tc1  = $d->mhai_tc ;
    $mhai_wel1 = $d->mhai_wel;
    $q4_total1 = $d->q4_total;
    $q4_xl1  = $d->q4_xl;
    $q4_tc1  = $d->q4_tc;
    $q4_wel1 = $d->q4_wel;
  }


  $sql = " update  fa_pl_reports set grant_total_total =  $grant_total_total - $grant_total_total1,
  grant_total_xl = $grant_total_xl - $grant_total_xl1 , grant_total_tc = $grant_total_tc -$grant_total_tc1 ,
  grant_total_wel= $grant_total_wel - $grant_total_wel1, mot_total =  $mot_total - $mot_total,
  mot_xl= $mot_xl - $mot_xl1 , mot_tc = $mot_tc - $mot_tc1, mot_wel = $mot_wel - $mot_wel1,hai_total = $hai_total - $hai_total1,
  hai_xl  = $hai_xl - $hai_xl1, hai_tc = $hai_tc - $hai_tc1 ,  hai_wel = $hai_wel - $hai_wel1, ba_total = $ba_total - $ba_total1,
  ba_xl = $ba_xl -$ba_xl1, ba_tc = $ba_tc - $ba_tc1, ba_wel = $ba_wel -$ba_wel1, q1_total = $q1_total -$q1_total1,
  q1_xl = $q1_xl - $q1_xl1 , q1_tc = $q1_tc -$q1_tc1,q1_wel = $q1_wel - $q1_wel1, bon_total  = $bon_total - $bon_total1,
  bon_xl  = $bon_xl - $bon_xl1,  bon_tc  = $bon_tc - $bon_tc1,  bon_wel  = $bon_wel -$bon_wel1,  nam_total = $nam_total -$nam_total1,
  nam_xl  = $nam_xl -$nam_xl1, nam_tc = $nam_tc - $nam_tc1, nam_wel = $nam_wel - $nam_wel1,   sau_total  = $sau_total -$sau_total1,
  sau_xl  = $sau_xl -$sau_xl1 , sau_tc  = $sau_tc-$sau_tc1 ,  sau_wel  = $sau_wel - $sau_wel1, q2_total = $q2_total -$q2_total1 ,
  q2_xl  = $q2_xl -$q2_xl1 , q2_tc  = $q2_tc -$q2_tc1,  q2_wel = $q2_wel -$q2_wel1,  bay_total = $bay_total -$bay_total1 ,
  bay_xl = $bay_xl-$bay_xl1 , bay_tc = $bay_tc -$bay_tc1 , bay_wel  = $bay_wel -$bay_wel1 , tam_total = $tam_total -$tam_total1 ,
  tam_xl  = $tam_xl -$tam_xl1, tam_tc  = $tam_tc -$tam_tc1,  tam_wel  = $tam_wel -$tam_wel1,  chin_total  = $chin_total -$chin_total1,
  chin_xl  = $chin_xl -$chin_xl1,  chin_tc = $chin_tc -$chin_tc1, chin_wel  = $chin_wel -$chin_wel1, q3_total = $q3_total -$q3_total1,
  q3_xl  = $q3_xl -$q3_xl1,  q3_tc  = $q3_tc -$q3_tc1, q3_wel  = $q3_wel -$q3_wel1 , muoi_total = $muoi_total -$muoi_total1,
  muoi_xl  = $muoi_xl -$muoi_xl1,   muoi_tc  = $muoi_tc -$muoi_tc1, muoi_wel  = $muoi_wel -$muoi_wel1,  mmot_total  = $mmot_total -$mmot_total1,
  mmot_xl  = $mmot_xl -$mmot_xl,    mmot_tc  = $mmot_tc -$mmot_tc1, mmot_wel  = $mmot_wel -$mmot_wel1, hai_total   = $mhai_total -$mhai_total1,
  mhai_xl   = $mhai_xl -$mhai_xl, mhai_tc  = $mhai_tc -$mhai_tc1 , mhai_wel = $mhai_wel -$mhai_wel1, q4_total  = $q4_total -$q4_total1,
  q4_xl  = $q4_xl -$q4_xl1, q4_tc  = $q4_tc-$q4_tc1, q4_wel = $q4_wel -$q4_wel1 where the_year = $Year and article in  $ArticlesProfit " ;

  $ds= DB::connection('mysql')->select($sql);
}
// ============================================================================================================
public function UpdateLogicBetweenRow($articlesFrom, $articlesTo, $Year)
{
  $sql = " select  sum(grant_total_total) as grant_total_total ,  sum(grant_total_xl) as grant_total_xl ,
  sum(grant_total_tc) as grant_total_tc, sum(grant_total_wel) as grant_total_wel  , sum(mot_total) as mot_total ,
  sum(mot_xl) as mot_xl , sum(mot_tc) as mot_tc,sum(mot_wel)as mot_wel , sum(hai_total) as hai_total ,
  sum(hai_xl) as hai_xl , sum(hai_tc) as hai_tc ,  sum(hai_wel) as hai_wel, sum(ba_total) as ba_total, sum(ba_xl) as ba_xl ,
  sum(ba_tc) as ba_tc ,  sum(ba_wel) as ba_wel  ,  sum(q1_total) q1_total ,sum(q1_xl) as q1_xl , sum(q1_tc) as q1_tc ,
  sum(q1_wel) as q1_wel , sum(bon_total) as bon_total , sum(bon_xl) as bon_xl  , sum(bon_tc) as bon_tc  ,
  sum(bon_wel) as bon_wel  , sum(nam_total) as nam_total  ,  sum(nam_xl) as nam_xl , sum(nam_tc) as nam_tc ,
  sum(nam_wel) as nam_wel,  sum(sau_total) as sau_total,  sum(sau_xl) as sau_xl  ,  sum(sau_tc) as sau_tc  ,
  sum(sau_wel) as sau_wel , sum(q2_total) as q2_total , sum(q2_xl) as q2_xl  ,sum(q2_tc) as q2_tc ,
  sum(q2_wel) as q2_wel  , sum(bay_total) as bay_total , sum(bay_xl) as bay_xl , sum(bay_tc) as bay_tc ,
  sum(bay_wel) as bay_wel  ,sum(tam_total) as tam_total  , sum(tam_xl) tam_xl , sum(tam_tc) as tam_tc , sum(tam_wel) as tam_wel ,
  sum(chin_total) as chin_total,sum(chin_xl) as chin_xl , sum(chin_tc) as chin_tc ,  sum(chin_wel)as chin_wel , sum(q3_total) as q3_total,
  sum(q3_xl) as q3_xl , sum(q3_tc) as q3_tc,sum(q3_wel) as q3_wel ,sum(muoi_total) as muoi_total , sum(muoi_xl) as muoi_xl , sum(muoi_tc) as muoi_tc,
  sum(muoi_wel) as muoi_wel , sum(mmot_total) as mmot_total , sum(mmot_xl)  as mmot_xl, sum(mmot_tc) as mmot_tc ,sum(mmot_wel) as mmot_wel ,
  sum(mhai_total) as mhai_total ,sum(mhai_xl) as mhai_xl  , sum(mhai_tc) as mhai_tc , sum(mhai_wel) as mhai_wel ,
  sum(q4_total)  as q4_total,  sum(q4_xl) as q4_xl , sum(q4_tc) as q4_tc ,sum(q4_wel)  as q4_wel
  from fa_pl_reports  where article in " . $articlesFrom . " and  the_year =  $Year " ;

  $ds= DB::connection('mysql')->select($sql);
  foreach($ds as $d)
  {
    $grant_total_total =  $d->grant_total_total;
    $grant_total_xl =  $d->grant_total_xl;
    $grant_total_tc  = $d->grant_total_tc;
    $grant_total_wel  = $d->grant_total_wel;
    $mot_total  = $d->mot_total ;
    $mot_xl  = $d->mot_xl ;
    $mot_tc = $d->mot_tc;
    $mot_wel = $d->mot_wel;
    $hai_total = $d->hai_total;
    $hai_xl  = $d->hai_xl;
    $hai_tc = $d->hai_tc;
    $hai_wel = $d->hai_wel;
    $ba_total = $d->ba_total;
    $ba_xl = $d->ba_xl;
    $ba_tc = $d->ba_tc;
    $ba_wel = $d->ba_wel;
    $q1_total = $d->q1_total ;
    $q1_xl = $d->q1_xl ;
    $q1_tc = $d->q1_tc ;
    $q1_wel = $d->q1_wel ;
    $bon_total  = $d->bon_total;
    $bon_xl  = $d->bon_xl ;
    $bon_tc  = $d->bon_tc;
    $bon_wel  = $d->bon_wel ;
    $nam_total = $d->nam_total ;
    $nam_xl  = $d->nam_xl;
    $nam_tc = $d->nam_tc ;
    $nam_wel = $d->nam_wel ;
    $sau_total  = $d->sau_total;
    $sau_xl  = $d->sau_xl ;
    $sau_tc  = $d->sau_tc ;
    $sau_wel  = $d->sau_wel ;
    $q2_total = $d->q2_total ;
    $q2_xl  = $d->q2_xl ;
    $q2_tc  = $d->q2_tc;
    $q2_wel = $d->q2_wel;
    $bay_total = $d->bay_total ;
    $bay_xl = $d->bay_xl ;
    $bay_tc = $d->bay_tc ;
    $bay_wel  = $d->bay_wel ;
    $tam_total = $d->tam_total ;
    $tam_xl  = $d->tam_xl;
    $tam_tc  = $d->tam_tc;
    $tam_wel  = $d->tam_wel;
    $chin_total  = $d->chin_total;
    $chin_xl  = $d->chin_xl;
    $chin_tc = $d->chin_tc ;
    $chin_wel  = $d->chin_wel;
    $q3_total = $d->q3_total;
    $q3_xl  = $d->q3_xl;
    $q3_tc  = $d->q3_tc;
    $q3_wel  = $d->q3_wel ;
    $muoi_total = $d->muoi_total;
    $muoi_xl  = $d->muoi_xl;
    $muoi_tc  = $d->muoi_tc;
    $muoi_wel  = $d->muoi_wel;
    $mmot_total  = $d->mmot_total ;
    $mmot_xl  = $d->mmot_xl;
    $mmot_tc  = $d->mmot_tc;
    $mmot_wel  = $d->mmot_wel;
    $mhai_total   = $d->mhai_total;
    $mhai_xl   = $d->mhai_xl;
    $mhai_tc  = $d->mhai_tc ;
    $mhai_wel = $d->mhai_wel;
    $q4_total  = $d->q4_total;
    $q4_xl  = $d->q4_xl;
    $q4_tc  = $d->q4_tc;
    $q4_wel = $d->q4_wel;

    $sql = " update  fa_pl_reports set grant_total_total =  $grant_total_total,grant_total_xl =  $grant_total_xl,
    grant_total_tc = $grant_total_tc , grant_total_wel= $grant_total_wel,mot_total =  $mot_total,
    mot_xl= $mot_xl, mot_tc = $mot_tc, mot_wel = $mot_wel,hai_total = $hai_total,  hai_xl  = $hai_xl,
    hai_tc = $hai_tc,  hai_wel = $hai_wel, ba_total = $ba_total, ba_xl = $ba_xl, ba_tc = $ba_tc, ba_wel = $ba_wel,
    q1_total = $q1_total, q1_xl = $q1_xl, q1_tc = $q1_tc,q1_wel =$q1_wel,bon_total  = $bon_total, bon_xl  = $d->bon_xl,
    bon_tc  = $bon_tc,  bon_wel  = $bon_wel,  nam_total = $nam_total ,    nam_xl  = $nam_xl,    nam_tc = $nam_tc ,
    nam_wel = $nam_wel,   sau_total  = $sau_total,     sau_xl  = $sau_xl ,    sau_tc  = $sau_tc ,    sau_wel  = $sau_wel ,
    q2_total = $q2_total ,    q2_xl  = $q2_xl ,    q2_tc  = $q2_tc,    q2_wel = $q2_wel,    bay_total = $bay_total ,
    bay_xl = $bay_xl ,    bay_tc = $bay_tc ,    bay_wel  = $bay_wel ,    tam_total = $tam_total ,    tam_xl  = $tam_xl,
    tam_tc  = $tam_tc,    tam_wel  = $tam_wel,    chin_total  = $chin_total,    chin_xl  = $chin_xl,    chin_tc = $chin_tc ,
    chin_wel  = $chin_wel,    q3_total = $q3_total,    q3_xl  = $q3_xl,    q3_tc  = $q3_tc,    q3_wel  = $q3_wel ,
    muoi_total = $muoi_total,    muoi_xl  = $muoi_xl,    muoi_tc  = $muoi_tc,    muoi_wel  = $muoi_wel,  mmot_total  = $mmot_total ,
    mmot_xl  = $mmot_xl,    mmot_tc  = $mmot_tc,    mmot_wel  = $mmot_wel,    mhai_total   = $mhai_total,    mhai_xl   = $mhai_xl,
    mhai_tc  = $mhai_tc ,    mhai_wel = $mhai_wel,    q4_total  = $q4_total,    q4_xl  = $q4_xl,    q4_tc  = $q4_tc,
    q4_wel = $q4_wel where the_year = $Year and article in  $articlesTo " ;

    $ds= DB::connection('mysql')->select($sql);
  }
}
// ============================================================================================================
// Cập nhật logic giữa các cột trong báo cáo PL
public function UpdateLogicBetweenCol($Year,$FromArticle,$ToArticle)
{
  $XL = 337;
  $ThuongChau = 336;
  $Wel = 307;
  $SalesTeams = array(0,$XL,$ThuongChau,$Wel);
  $sSalesTeams = array('ngongan','xl','tc','wel');
  $Months = array('khong','mot','hai','ba','bon','nam','sau','bay','tam','chin','muoi','mmot','mhai');
  $Quaters =array('q0','q1','q2','q3','q4');

  // Update so lieu cua 3 team vao cột tổng của mỗi tháng
  for($Month =1; $Month<=12;$Month++)
  {
    $sql = ' update fa_pl_reports set '. $Months[$Month]. '_total = ' . $Months[$Month]. '_xl + '.
    $Months[$Month]. '_tc +' . $Months[$Month].'_wel'. ' where the_year ='. $Year ;
    DB::connection('mysql')->select($sql);
  }

  //update Số liệu mỗi 3 tháng vào một quí
  $sql = ' update fa_pl_reports set   q1_xl = mot_xl + hai_xl + ba_xl,   q2_xl =  bon_xl + nam_xl + sau_xl ,
  q3_xl = bay_xl + tam_xl + chin_xl,   q4_xl =  muoi_xl + mmot_xl + mhai_xl,  q1_tc = mot_tc + hai_tc + ba_tc,
  q2_tc =  bon_tc + nam_tc + sau_tc ,  q3_tc = bay_tc + tam_tc + chin_tc,   q4_tc =  muoi_tc + mmot_tc + mhai_tc,
  q1_wel = mot_wel + hai_wel + ba_wel,   q2_wel =  bon_wel + nam_wel + sau_wel ,  q3_wel = bay_wel + tam_wel + chin_wel,
  q4_wel =  muoi_wel + mmot_wel + mhai_wel   where the_year = '. $Year.
  " and article >= ". $FromArticle . " and  article <= ". $ToArticle ;

  DB::connection('mysql')->select($sql);

  //update con số quí của mỗi team vào tổng qúi
  for($Quater =1; $Quater <= 4 ; $Quater++ )
  {
    $sql = ' update fa_pl_reports set '. $Quaters[$Quater]. '_total = ' . $Quaters[$Quater]. '_xl + '.
    $Quaters[$Quater]. '_tc +' . $Quaters[$Quater].'_wel'. ' where the_year ='. $Year .
    " and article >= ". $FromArticle . " and  article <= ". $ToArticle ;
    DB::connection('mysql')->select($sql);
  }

  //update con số 4 quí của ra cả năm
  $sql = ' update fa_pl_reports set   grant_total_xl = q1_xl + q2_xl + q3_xl+ q4_xl,    grant_total_tc = q1_tc + q2_tc + q3_tc+ q4_tc,
  grant_total_wel = q1_wel + q2_wel + q3_wel + q4_wel   where the_year = '. $Year .
  " and article >= ". $FromArticle . " and  article <= ". $ToArticle ;
  DB::connection('mysql')->select($sql);

  //update con số cả năm của các team sales ra con số tổng
  $sql = ' update fa_pl_reports set   grant_total_total = grant_total_xl + grant_total_tc + grant_total_wel  where the_year = '. $Year .
  " and article >= ". $FromArticle . " and  article <= ". $ToArticle ;
  DB::connection('mysql')->select($sql);

}
// ============================================================================================================
public function CaculateForBasicArticlesExceptEbay($Year,$Month,$Channel, $UnitShipmentSql, $RevenueSql,$GrossSql,$RefundSql ,
$TotalFeeSql,$ProSql, $SEMSql , $ShipSql , $OtherFeeSql,$NetRevenueSql, $PurchasingSql ,$FreightSql , $DutieSql , $PalletSql)

{
  switch($Channel)
    {
      case 9: //FBA

        $sql =" update fa_pl_reports set " .  $NetRevenueSql . " where article = 11 and the_year = $Year ";
        DB::connection('mysql')->select($sql);
        //print_r('net fba:'. $sql);
        //print_r('<br>');

        $sql =" update fa_pl_reports set " .  $UnitShipmentSql . " where article = 12 and the_year = $Year ";
        DB::connection('mysql')->select($sql);

       // print_r('unit shipment fba:'. $sql);
       // print_r('<br>');

        $sql =" update fa_pl_reports set " .  $RevenueSql . " where article = 13 and the_year = $Year ";
        DB::connection('mysql')->select($sql);
        //print_r('Revenue fba:'. $sql);
        //print_r('<br>');


        $sql =" update fa_pl_reports set " .  $GrossSql . " where article = 14 and the_year = $Year ";
        DB::connection('mysql')->select($sql);

        $sql =" update fa_pl_reports set " .  $RefundSql . " where article = 15 and the_year = $Year ";
        DB::connection('mysql')->select($sql);

        $sql =" update fa_pl_reports set " .  $TotalFeeSql . " where article = 16 and the_year = $Year ";
        DB::connection('mysql')->select($sql);


        $sql =" update fa_pl_reports set " .  $ProSql . " where article = 17 and the_year = $Year ";
        DB::connection('mysql')->select($sql);

        $sql =" update fa_pl_reports set " .  $SEMSql . " where article = 18 and the_year = $Year ";
        DB::connection('mysql')->select($sql);

        $sql =" update fa_pl_reports set " .  $ShipSql . " where article = 19 and the_year = $Year ";
        DB::connection('mysql')->select($sql);

        $sql =" update fa_pl_reports set " .  $OtherFeeSql . " where article = 20 and the_year = $Year ";
        DB::connection('mysql')->select($sql);


        $sql =" update fa_pl_reports set " .  $PurchasingSql . " where article = 150 and the_year = $Year ";
        DB::connection('mysql')->select($sql);

        $sql =" update fa_pl_reports set " .  $FreightSql  . " where article = 151  and the_year = $Year ";
        DB::connection('mysql')->select($sql);

        $sql =" update fa_pl_reports set " .  $DutieSql  . " where article = 152  and the_year = $Year ";
        DB::connection('mysql')->select($sql);

        $sql =" update fa_pl_reports set " .  $PalletSql . " where article = 153  and the_year = $Year ";
        DB::connection('mysql')->select($sql);


        break;

      case 10: //FBM
        $sql =" update fa_pl_reports set " .  $NetRevenueSql . " where article = 21 and the_year = $Year ";
        DB::connection('mysql')->select($sql);

        $sql =" update fa_pl_reports set " .  $UnitShipmentSql . " where article = 22 and the_year = $Year ";
        DB::connection('mysql')->select($sql);

        $sql =" update fa_pl_reports set " .  $RevenueSql . " where article = 23 and the_year = $Year ";
        DB::connection('mysql')->select($sql);

        $sql =" update fa_pl_reports set " .  $GrossSql . " where article = 24 and the_year = $Year ";
        DB::connection('mysql')->select($sql);

        $sql =" update fa_pl_reports set " .  $RefundSql . " where article = 25 and the_year = $Year ";
        DB::connection('mysql')->select($sql);


        $sql =" update fa_pl_reports set " .  $TotalFeeSql . " where article = 26 and the_year = $Year ";
        DB::connection('mysql')->select($sql);

        $sql =" update fa_pl_reports set " .  $ProSql . " where article = 27 and the_year = $Year ";
        DB::connection('mysql')->select($sql);

        $sql =" update fa_pl_reports set " .  $SEMSql . " where article = 28 and the_year = $Year ";
        DB::connection('mysql')->select($sql);

        $sql =" update fa_pl_reports set " .  $ShipSql . " where article = 29 and the_year = $Year ";
        DB::connection('mysql')->select($sql);

        $sql =" update fa_pl_reports set " .  $OtherFeeSql . " where article = 30 and the_year = $Year ";
        DB::connection('mysql')->select($sql);


        $sql =" update fa_pl_reports set " .  $PurchasingSql . " where article = 155 and the_year = $Year ";
        DB::connection('mysql')->select($sql);

        $sql =" update fa_pl_reports set " .  $FreightSql  . " where article = 156  and the_year = $Year ";
        DB::connection('mysql')->select($sql);

        $sql =" update fa_pl_reports set " .  $DutieSql  . " where article = 157  and the_year = $Year ";
        DB::connection('mysql')->select($sql);

        $sql =" update fa_pl_reports set " .  $PalletSql . " where article = 158  and the_year = $Year ";
        DB::connection('mysql')->select($sql);

        break;
      case 1: //AVC WH
        $sql =" update fa_pl_reports set " .  $NetRevenueSql . " where article = 31 and the_year = $Year ";
        DB::connection('mysql')->select($sql);

        $sql =" update fa_pl_reports set " .  $UnitShipmentSql . " where article = 32 and the_year = $Year ";
        DB::connection('mysql')->select($sql);

        $sql =" update fa_pl_reports set " .  $RevenueSql . " where article = 33 and the_year = $Year ";
        DB::connection('mysql')->select($sql);

        $sql =" update fa_pl_reports set " .  $GrossSql . " where article = 34 and the_year = $Year ";
        DB::connection('mysql')->select($sql);

       // print_r('Gross Rev avc_wh'.$sql);
       // print_r('<br>');


        $sql =" update fa_pl_reports set " .  $RefundSql . " where article = 35 and the_year = $Year ";
        DB::connection('mysql')->select($sql);

        $sql =" update fa_pl_reports set " .  $TotalFeeSql . " where article = 36 and the_year = $Year ";
        DB::connection('mysql')->select($sql);

        $sql =" update fa_pl_reports set " .  $ProSql . " where article = 37 and the_year = $Year ";
        DB::connection('mysql')->select($sql);

        $sql =" update fa_pl_reports set " .  $SEMSql . " where article = 38 and the_year = $Year ";
        DB::connection('mysql')->select($sql);

        $sql =" update fa_pl_reports set " .  $ShipSql . " where article = 39 and the_year = $Year ";
        DB::connection('mysql')->select($sql);

        $sql =" update fa_pl_reports set " .  $OtherFeeSql . " where article = 40 and the_year = $Year ";
        DB::connection('mysql')->select($sql);


        $sql =" update fa_pl_reports set " .  $PurchasingSql . " where article = 160 and the_year = $Year ";
        DB::connection('mysql')->select($sql);

        $sql =" update fa_pl_reports set " .  $FreightSql  . " where article = 161  and the_year = $Year ";
        DB::connection('mysql')->select($sql);

        $sql =" update fa_pl_reports set " .  $DutieSql  . " where article = 162  and the_year = $Year ";
        DB::connection('mysql')->select($sql);

        $sql =" update fa_pl_reports set " .  $PalletSql . " where article = 163  and the_year = $Year ";
        DB::connection('mysql')->select($sql);

        break;
      case 2: //AVC DS
        $sql =" update fa_pl_reports set " .  $NetRevenueSql . " where article = 41 and the_year = $Year ";
        DB::connection('mysql')->select($sql);

        $sql =" update fa_pl_reports set " .  $UnitShipmentSql . " where article = 42 and the_year = $Year ";
        DB::connection('mysql')->select($sql);

        $sql =" update fa_pl_reports set " .  $RevenueSql . " where article = 43 and the_year = $Year ";
        DB::connection('mysql')->select($sql);

        $sql =" update fa_pl_reports set " .  $GrossSql . " where article = 44 and the_year = $Year ";
        DB::connection('mysql')->select($sql);

        $sql =" update fa_pl_reports set " .  $RefundSql . " where article = 45 and the_year = $Year ";
        DB::connection('mysql')->select($sql);

        $sql =" update fa_pl_reports set " .  $TotalFeeSql . " where article = 46 and the_year = $Year ";
        DB::connection('mysql')->select($sql);

        $sql =" update fa_pl_reports set " .  $ProSql . " where article = 47 and the_year = $Year ";
        DB::connection('mysql')->select($sql);

        $sql =" update fa_pl_reports set " .  $SEMSql . " where article = 48 and the_year = $Year ";
        DB::connection('mysql')->select($sql);

        $sql =" update fa_pl_reports set " .  $ShipSql . " where article = 49 and the_year = $Year ";
        DB::connection('mysql')->select($sql);

        $sql =" update fa_pl_reports set " .  $OtherFeeSql . " where article = 50 and the_year = $Year ";
        DB::connection('mysql')->select($sql);


        $sql =" update fa_pl_reports set " .  $PurchasingSql . " where article = 165 and the_year = $Year ";
        DB::connection('mysql')->select($sql);

        $sql =" update fa_pl_reports set " .  $FreightSql  . " where article = 166  and the_year = $Year ";
        DB::connection('mysql')->select($sql);

        $sql =" update fa_pl_reports set " .  $DutieSql  . " where article = 167  and the_year = $Year ";
        DB::connection('mysql')->select($sql);

        $sql =" update fa_pl_reports set " .  $PalletSql . " where article = 168  and the_year = $Year ";
        DB::connection('mysql')->select($sql);

        break;

      case 3: //AVC DI
        $sql =" update fa_pl_reports set " .  $NetRevenueSql . " where article = 51 and the_year = $Year ";
        DB::connection('mysql')->select($sql);

        $sql =" update fa_pl_reports set " .  $UnitShipmentSql . " where article = 52 and the_year = $Year ";
        DB::connection('mysql')->select($sql);

        $sql =" update fa_pl_reports set " .  $RevenueSql . " where article = 53 and the_year = $Year ";
        DB::connection('mysql')->select($sql);

        $sql =" update fa_pl_reports set " .  $GrossSql . " where article = 54 and the_year = $Year ";
        DB::connection('mysql')->select($sql);

        $sql =" update fa_pl_reports set " .  $RefundSql . " where article = 55 and the_year = $Year ";
        DB::connection('mysql')->select($sql);

        $sql =" update fa_pl_reports set " .  $TotalFeeSql . " where article = 56 and the_year = $Year ";
        DB::connection('mysql')->select($sql);

        $sql =" update fa_pl_reports set " .  $ProSql . " where article = 57 and the_year = $Year ";
        DB::connection('mysql')->select($sql);

        $sql =" update fa_pl_reports set " .  $SEMSql . " where article = 58 and the_year = $Year ";
        DB::connection('mysql')->select($sql);

        $sql =" update fa_pl_reports set " .  $ShipSql . " where article = 59 and the_year = $Year ";
        DB::connection('mysql')->select($sql);

        $sql =" update fa_pl_reports set " .  $OtherFeeSql . " where article = 60 and the_year = $Year ";
        DB::connection('mysql')->select($sql);


        $sql =" update fa_pl_reports set " .  $PurchasingSql . " where article = 170 and the_year = $Year ";
        DB::connection('mysql')->select($sql);

        $sql =" update fa_pl_reports set " .  $FreightSql  . " where article = 171  and the_year = $Year ";
        DB::connection('mysql')->select($sql);

        $sql =" update fa_pl_reports set " .  $DutieSql  . " where article = 172  and the_year = $Year ";
        DB::connection('mysql')->select($sql);

        $sql =" update fa_pl_reports set " .  $PalletSql . " where article = 173  and the_year = $Year ";
        DB::connection('mysql')->select($sql);

        break;
      // Xong amazon

      case 4: //WM DSV
        $sql =" update fa_pl_reports set " .  $NetRevenueSql . " where article = 105 and the_year = $Year ";
        DB::connection('mysql')->select($sql);

        $sql =" update fa_pl_reports set " .  $UnitShipmentSql . " where article = 106 and the_year = $Year ";
        DB::connection('mysql')->select($sql);

        $sql =" update fa_pl_reports set " .  $RevenueSql . " where article = 107 and the_year = $Year ";
        DB::connection('mysql')->select($sql);

        $sql =" update fa_pl_reports set " .  $GrossSql . " where article = 108 and the_year = $Year ";
        DB::connection('mysql')->select($sql);

        $sql =" update fa_pl_reports set " .  $RefundSql . " where article = 109 and the_year = $Year ";
        DB::connection('mysql')->select($sql);

        $sql =" update fa_pl_reports set " .  $TotalFeeSql . " where article = 110 and the_year = $Year ";
        DB::connection('mysql')->select($sql);

        $sql =" update fa_pl_reports set " .  $ProSql . " where article = 111 and the_year = $Year ";
        DB::connection('mysql')->select($sql);

        $sql =" update fa_pl_reports set " .  $SEMSql . " where article = 112 and the_year = $Year ";
        DB::connection('mysql')->select($sql);

        $sql =" update fa_pl_reports set " .  $ShipSql . " where article = 113 and the_year = $Year ";
        DB::connection('mysql')->select($sql);

        $sql =" update fa_pl_reports set " .  $OtherFeeSql . " where article = 114 and the_year = $Year ";
        DB::connection('mysql')->select($sql);

        $sql =" update fa_pl_reports set " .  $PurchasingSql . " where article = 197 and the_year = $Year ";
        DB::connection('mysql')->select($sql);

        $sql =" update fa_pl_reports set " .  $FreightSql  . " where article = 198  and the_year = $Year ";
        DB::connection('mysql')->select($sql);

        $sql =" update fa_pl_reports set " .  $DutieSql  . " where article = 199  and the_year = $Year ";
        DB::connection('mysql')->select($sql);

        $sql =" update fa_pl_reports set " .  $PalletSql . " where article = 200  and the_year = $Year ";
        DB::connection('mysql')->select($sql);

        break;

      case 5: //WM MKP
        $sql =" update fa_pl_reports set " .  $NetRevenueSql . " where article = 115 and the_year = $Year ";
        DB::connection('mysql')->select($sql);

        $sql =" update fa_pl_reports set " .  $UnitShipmentSql . " where article = 116 and the_year = $Year ";
        DB::connection('mysql')->select($sql);

        $sql =" update fa_pl_reports set " .  $RevenueSql . " where article = 117 and the_year = $Year ";
        DB::connection('mysql')->select($sql);

        $sql =" update fa_pl_reports set " .  $GrossSql . " where article = 118 and the_year = $Year ";
        DB::connection('mysql')->select($sql);

        $sql =" update fa_pl_reports set " .  $RefundSql . " where article = 119 and the_year = $Year ";
        DB::connection('mysql')->select($sql);

        $sql =" update fa_pl_reports set " .  $TotalFeeSql . " where article = 120 and the_year = $Year ";
        DB::connection('mysql')->select($sql);

        $sql =" update fa_pl_reports set " .  $ProSql . " where article = 121 and the_year = $Year ";
        DB::connection('mysql')->select($sql);

        $sql =" update fa_pl_reports set " .  $SEMSql . " where article = 122 and the_year = $Year ";
        DB::connection('mysql')->select($sql);

        $sql =" update fa_pl_reports set " .  $ShipSql . " where article = 123 and the_year = $Year ";
        DB::connection('mysql')->select($sql);

        $sql =" update fa_pl_reports set " .  $OtherFeeSql . " where article = 124 and the_year = $Year ";
        DB::connection('mysql')->select($sql);


        $sql =" update fa_pl_reports set " .  $PurchasingSql . " where article = 202 and the_year = $Year ";
        DB::connection('mysql')->select($sql);

        $sql =" update fa_pl_reports set " .  $FreightSql  . " where article = 203  and the_year = $Year ";
        DB::connection('mysql')->select($sql);

        $sql =" update fa_pl_reports set " .  $DutieSql  . " where article = 204  and the_year = $Year ";
        DB::connection('mysql')->select($sql);

        $sql =" update fa_pl_reports set " .  $PalletSql . " where article = 205  and the_year = $Year ";
        DB::connection('mysql')->select($sql);


        break;
      case 8: //website
        $sql =" update fa_pl_reports set " .  $NetRevenueSql . " where article = 125 and the_year = $Year ";
        DB::connection('mysql')->select($sql);

        $sql =" update fa_pl_reports set " .  $UnitShipmentSql . " where article = 127 and the_year = $Year ";
        DB::connection('mysql')->select($sql);

        $sql =" update fa_pl_reports set " .  $RevenueSql . " where article = 128 and the_year = $Year ";
        DB::connection('mysql')->select($sql);

        // Với website 2 dòng có articlae 126 =128
        $sql =" update fa_pl_reports set " .  $RevenueSql . " where article = 126 and the_year = $Year ";
        DB::connection('mysql')->select($sql);


        $sql =" update fa_pl_reports set " .  $GrossSql . " where article = 129 and the_year = $Year ";
        DB::connection('mysql')->select($sql);

        $sql =" update fa_pl_reports set " .  $RefundSql . " where article = 130 and the_year = $Year ";
        DB::connection('mysql')->select($sql);

        $sql =" update fa_pl_reports set " .  $TotalFeeSql . " where article = 131 and the_year = $Year ";
        DB::connection('mysql')->select($sql);

        $sql =" update fa_pl_reports set " .  $ProSql . " where article = 132 and the_year = $Year ";
        DB::connection('mysql')->select($sql);

        $sql =" update fa_pl_reports set " .  $SEMSql . " where article = 133 and the_year = $Year ";
        DB::connection('mysql')->select($sql);

        $sql =" update fa_pl_reports set " .  $ShipSql . " where article = 134 and the_year = $Year ";
        DB::connection('mysql')->select($sql);

        $sql =" update fa_pl_reports set " .  $OtherFeeSql . " where article = 135 and the_year = $Year ";
        DB::connection('mysql')->select($sql);


        $sql =" update fa_pl_reports set " .  $PurchasingSql . " where article = 207 and the_year = $Year ";
        DB::connection('mysql')->select($sql);

        $sql =" update fa_pl_reports set " .  $FreightSql  . " where article = 208  and the_year = $Year ";
        DB::connection('mysql')->select($sql);

        $sql =" update fa_pl_reports set " .  $DutieSql  . " where article = 209  and the_year = $Year ";
        DB::connection('mysql')->select($sql);

        $sql =" update fa_pl_reports set " .  $PalletSql . " where article = 210  and the_year = $Year ";
        DB::connection('mysql')->select($sql);

        break;

      case 7: //Craiglist/Local
        $sql =" update fa_pl_reports set " .  $NetRevenueSql . " where article = 136 and the_year = $Year ";
        DB::connection('mysql')->select($sql);


        $sql =" update fa_pl_reports set " .  $UnitShipmentSql . " where article = 138 and the_year = $Year ";
        DB::connection('mysql')->select($sql);

        $sql =" update fa_pl_reports set " .  $RevenueSql . " where article = 139 and the_year = $Year ";
        DB::connection('mysql')->select($sql);

        // crailist có dòng giống nhau đó là article 137 =139
        $sql =" update fa_pl_reports set " .  $RevenueSql . " where article = 137 and the_year = $Year ";
        DB::connection('mysql')->select($sql);


        $sql =" update fa_pl_reports set " .  $GrossSql . " where article = 140 and the_year = $Year ";
        //print_r($sql);
        //print_r('<br>');
        DB::connection('mysql')->select($sql);

        $sql =" update fa_pl_reports set " .  $RefundSql . " where article = 141 and the_year = $Year ";
        DB::connection('mysql')->select($sql);

        $sql =" update fa_pl_reports set " .  $TotalFeeSql . " where article = 142 and the_year = $Year ";
        DB::connection('mysql')->select($sql);

        $sql =" update fa_pl_reports set " .  $ProSql . " where article = 143 and the_year = $Year ";
        DB::connection('mysql')->select($sql);

        $sql =" update fa_pl_reports set " .  $SEMSql . " where article = 144 and the_year = $Year ";
        DB::connection('mysql')->select($sql);

        $sql =" update fa_pl_reports set " .  $ShipSql . " where article = 145 and the_year = $Year ";
        DB::connection('mysql')->select($sql);

        $sql =" update fa_pl_reports set " .  $OtherFeeSql . " where article = 146 and the_year = $Year ";
        DB::connection('mysql')->select($sql);


        $sql =" update fa_pl_reports set " .  $PurchasingSql . " where article = 212 and the_year = $Year ";
        DB::connection('mysql')->select($sql);

        $sql =" update fa_pl_reports set " .  $FreightSql  . " where article = 213  and the_year = $Year ";
        DB::connection('mysql')->select($sql);

        $sql =" update fa_pl_reports set " .  $DutieSql  . " where article = 214  and the_year = $Year ";
        DB::connection('mysql')->select($sql);

        $sql =" update fa_pl_reports set " .  $PalletSql . " where article = 215  and the_year = $Year ";
        DB::connection('mysql')->select($sql);

        break;
  }// switch channel

}// end function
// ============================================================================================================
public function CaculateForBasicArticlesForEbay($Year,$Month,$Store, $UnitShipmentSql, $RevenueSql,$GrossSql,$RefundSql ,
$TotalFeeSql,$ProSql, $SEMSql , $ShipSql , $OtherFeeSql, $NetRevenueSql , $PurchasingSql,$FreightSql , $DutieSql,$PalletSql  )
{
  switch($Store)
  {
    case 1: // Ebay Fitness
      $sql =" update fa_pl_reports set " .  $NetRevenueSql  . " where article = 63 and the_year = $Year ";
      DB::connection('mysql')->select($sql);

      $sql =" update fa_pl_reports set " .  $UnitShipmentSql . " where article = 64 and the_year = $Year ";
      DB::connection('mysql')->select($sql);

      $sql =" update fa_pl_reports set " .  $RevenueSql . " where article = 65 and the_year = $Year ";
      DB::connection('mysql')->select($sql);

      $sql =" update fa_pl_reports set " .  $GrossSql . " where article = 66 and the_year = $Year ";
      DB::connection('mysql')->select($sql);


      $sql =" update fa_pl_reports set " .  $RefundSql . " where article = 67 and the_year = $Year ";
      DB::connection('mysql')->select($sql);

      $sql =" update fa_pl_reports set " .  $TotalFeeSql . " where article = 68 and the_year = $Year ";
      DB::connection('mysql')->select($sql);

      $sql =" update fa_pl_reports set " .  $ProSql . " where article = 69 and the_year = $Year ";
      DB::connection('mysql')->select($sql);

      $sql =" update fa_pl_reports set " .  $SEMSql . " where article = 70 and the_year = $Year ";
      DB::connection('mysql')->select($sql);

      $sql =" update fa_pl_reports set " .  $ShipSql . " where article = 71 and the_year = $Year ";
      DB::connection('mysql')->select($sql);

      $sql =" update fa_pl_reports set " .  $OtherFeeSql . " where article = 72 and the_year = $Year ";
      DB::connection('mysql')->select($sql);

      $sql =" update fa_pl_reports set " .  $PurchasingSql . " where article = 176 and the_year = $Year ";
      DB::connection('mysql')->select($sql);

      $sql =" update fa_pl_reports set " .  $FreightSql . " where article = 177 and the_year = $Year ";
      DB::connection('mysql')->select($sql);

      $sql =" update fa_pl_reports set " .  $DutieSql. " where article = 178 and the_year = $Year ";
      DB::connection('mysql')->select($sql);

      $sql =" update fa_pl_reports set " .  $PalletSql . " where article = 179 and the_year = $Year ";
      DB::connection('mysql')->select($sql);


      break;
    case 2: //Ebay Inc
      $sql =" update fa_pl_reports set " .  $NetRevenueSql  . " where article = 73 and the_year = $Year ";
      DB::connection('mysql')->select($sql);

      $sql =" update fa_pl_reports set " .  $UnitShipmentSql . " where article = 74 and the_year = $Year ";
      DB::connection('mysql')->select($sql);

      $sql =" update fa_pl_reports set " .  $RevenueSql . " where article = 75 and the_year = $Year ";
      DB::connection('mysql')->select($sql);

      $sql =" update fa_pl_reports set " .  $GrossSql . " where article = 76 and the_year = $Year ";
      DB::connection('mysql')->select($sql);

      $sql =" update fa_pl_reports set " .  $RefundSql . " where article = 77 and the_year = $Year ";
      DB::connection('mysql')->select($sql);

      $sql =" update fa_pl_reports set " .  $TotalFeeSql . " where article = 78 and the_year = $Year ";
      DB::connection('mysql')->select($sql);

      $sql =" update fa_pl_reports set " .  $ProSql . " where article = 79 and the_year = $Year ";
      DB::connection('mysql')->select($sql);

      $sql =" update fa_pl_reports set " .  $SEMSql . " where article = 80 and the_year = $Year ";
      DB::connection('mysql')->select($sql);

      $sql =" update fa_pl_reports set " .  $ShipSql . " where article = 81 and the_year = $Year ";
      DB::connection('mysql')->select($sql);

      $sql =" update fa_pl_reports set " .  $OtherFeeSql . " where article = 82 and the_year = $Year ";
      DB::connection('mysql')->select($sql);

      $sql =" update fa_pl_reports set " .  $PurchasingSql. " where article = 181 and the_year = $Year ";
      DB::connection('mysql')->select($sql);

      $sql =" update fa_pl_reports set " .  $FreightSql  . " where article = 182 and the_year = $Year ";
      DB::connection('mysql')->select($sql);

      $sql =" update fa_pl_reports set " .  $DutieSql . " where article = 183 and the_year = $Year ";
      DB::connection('mysql')->select($sql);

      $sql =" update fa_pl_reports set " .  $PalletSql . " where article = 184 and the_year = $Year ";
      DB::connection('mysql')->select($sql);

      break;
    case 3: // Ebay Infideals
      $sql =" update fa_pl_reports set " .  $NetRevenueSql  . " where article = 83 and the_year = $Year ";
      DB::connection('mysql')->select($sql);

      $sql =" update fa_pl_reports set " .  $UnitShipmentSql . " where article = 84 and the_year = $Year ";
      DB::connection('mysql')->select($sql);

      $sql =" update fa_pl_reports set " .  $RevenueSql . " where article = 85 and the_year = $Year ";
      DB::connection('mysql')->select($sql);

      $sql =" update fa_pl_reports set " .  $GrossSql . " where article = 86 and the_year = $Year ";
      DB::connection('mysql')->select($sql);

      $sql =" update fa_pl_reports set " .  $RefundSql . " where article = 87 and the_year = $Year ";
      DB::connection('mysql')->select($sql);

      $sql =" update fa_pl_reports set " .  $TotalFeeSql . " where article = 88 and the_year = $Year ";
      DB::connection('mysql')->select($sql);

      $sql =" update fa_pl_reports set " .  $ProSql . " where article = 89 and the_year = $Year ";
      DB::connection('mysql')->select($sql);

      $sql =" update fa_pl_reports set " .  $SEMSql . " where article = 90 and the_year = $Year ";
      DB::connection('mysql')->select($sql);

      $sql =" update fa_pl_reports set " .  $ShipSql . " where article = 91 and the_year = $Year ";
      DB::connection('mysql')->select($sql);

      $sql =" update fa_pl_reports set " .  $OtherFeeSql . " where article = 92 and the_year = $Year ";
      DB::connection('mysql')->select($sql);


      $sql =" update fa_pl_reports set " .  $PurchasingSql . " where article = 186 and the_year = $Year ";
      DB::connection('mysql')->select($sql);

      $sql =" update fa_pl_reports set " .  $FreightSql  . " where article = 187 and the_year = $Year ";
      DB::connection('mysql')->select($sql);

      $sql =" update fa_pl_reports set " .  $DutieSql. " where article = 188 and the_year = $Year ";
      DB::connection('mysql')->select($sql);

      $sql =" update fa_pl_reports set " .  $PalletSql. " where article = 189 and the_year = $Year ";
      DB::connection('mysql')->select($sql);


      break;

    case 4: //Ebay Idzo
      $sql =" update fa_pl_reports set " .  $NetRevenueSql  . " where article = 93 and the_year = $Year ";
      DB::connection('mysql')->select($sql);

      $sql =" update fa_pl_reports set " .  $UnitShipmentSql . " where article = 94 and the_year = $Year ";
      DB::connection('mysql')->select($sql);

      $sql =" update fa_pl_reports set " .  $RevenueSql . " where article = 95 and the_year = $Year ";
      DB::connection('mysql')->select($sql);


      $sql =" update fa_pl_reports set " .  $GrossSql . " where article = 96 and the_year = $Year ";
      DB::connection('mysql')->select($sql);

      $sql =" update fa_pl_reports set " .  $RefundSql . " where article = 97 and the_year = $Year ";
      DB::connection('mysql')->select($sql);

      $sql =" update fa_pl_reports set " .  $TotalFeeSql . " where article = 98 and the_year = $Year ";
      DB::connection('mysql')->select($sql);

      $sql =" update fa_pl_reports set " .  $ProSql . " where article = 99 and the_year = $Year ";
      DB::connection('mysql')->select($sql);

      $sql =" update fa_pl_reports set " .  $SEMSql . " where article = 100 and the_year = $Year ";
      DB::connection('mysql')->select($sql);

      $sql =" update fa_pl_reports set " .  $ShipSql . " where article = 101 and the_year = $Year ";
      DB::connection('mysql')->select($sql);

      $sql =" update fa_pl_reports set " .  $OtherFeeSql . " where article = 102 and the_year = $Year ";
      DB::connection('mysql')->select($sql);


      $sql =" update fa_pl_reports set " .  $PurchasingSql . " where article = 191 and the_year = $Year ";
      DB::connection('mysql')->select($sql);

      $sql =" update fa_pl_reports set " .  $FreightSql . " where article = 192 and the_year = $Year ";
      DB::connection('mysql')->select($sql);

      $sql =" update fa_pl_reports set " .  $DutieSql. " where article = 193 and the_year = $Year ";
      DB::connection('mysql')->select($sql);

      $sql =" update fa_pl_reports set " .  $PalletSql . " where article = 194 and the_year = $Year ";
      DB::connection('mysql')->select($sql);

      break;
  }// switch store
}
// ============================================================================================================
public function BoNhayDon($title)
{
  if(strpos($title,"'")>0)
  { $title = str_replace("'", "\\'", $title);  }
  return $title;
}
// ============================================================================================================
public function MoveEbayOrder()// Move ABY order from BE to gcom
{
  $LastID = 0;// ID cuối cùng trên BE đã move qua gcom
  $sql = " select case when max(id_on_be) >0 then max(id_on_be) else 0 end as id_on_be from sal_ebay_order ";
  $ds = DB::connection('mysql')->select($sql);
  foreach( $ds  as  $d ){$LastID =  $d->id_on_be;  }

  // Move những ID mới phát sinh
  // Move Master
  $sql = " select id , ebay_order_id,  store_id,  shipping,tax,shipping_address,name_ship,street1_ship,street2_ship,
  city_ship,state_ship,buyer_name,buyer_email,purchased_price, external_transaction,paypal_transaction, shipment_tracking_number,
  transaction_status, order_status,	order_time,
  is_gift, is_priority_shipment,  warehouse_code,    order_placed_date,  required_shipdate,
  ship_address,  ship_to_state,   shipped_date  from amazon_dropship_orders where  id > $LastID ";

  $ds = DB::connection('mysql_it')->select($sql);
  foreach( $ds as $d)
  {
    $sql = " insert into sal_amazon_dropship_orders(amazon_order_id,  order_id,  status,  is_multiple, is_pslip_required,
    is_gift, is_priority_shipment,  warehouse_code,  order_placed_date, required_shipdate  ,   ship_address,  ship_to_state,
    shipped_date,id_on_be )"
    ."'". $d->amazon_order_id ."',' " . $d->order_id ."','". $d->status ."',". $d->is_multiple. "," . $d->is_pslip_required
    .",". $d->is_gift. ",'".$d->required_shipdate_timestamp. "','".$d->warehouse_code."','" . $d->order_placed_date. "',"
    ."','". $d->required_shipdate."','" . $d->ship_address ."','" .  $d->ship_to_state . "','" .   $d->shipped_date ."',".$d->id ;
    DB::connection('mysql')->select($sql);
  }

}
// ============================================================================================================
public function MoveAvcDropshipOrder()// Move AVC order from BE to gcom
{
  ini_set('memory_limit','1548M');
  set_time_limit(10000);
  $LastID = 0 ;
  $sql = " select case when max(id)>0 then max(id) else 0 end as id_on_be from sal_amazon_dropship_orders  ";
  $ds = DB::connection('mysql')->select($sql);
  foreach( $ds  as  $d ){$LastID =  $d->id_on_be;  }

  if($LastID < 583784){$LastID = 583784;}// ID nhỏ nhất của data order tư 1/1/2020
  // Move những ID mới phát sinh
  // Move Master
  $sql = " select id , amazon_order_id,  order_id,  status,  is_multiple, is_pslip_required,
  is_gift, is_priority_shipment,  warehouse_code, order_placed_date,  required_shipdate,
  ship_address,  ship_to_state, shipped_date
  from amazon_dropship_orders where  id > $LastID and status = 'SHIPPED' ";

  $ds = DB::connection('mysql_it')->select($sql);
  foreach( $ds as $d)
  {
    $sql = " insert into sal_amazon_dropship_orders(id,amazon_order_id,order_id,  status, is_multiple, is_pslip_required,
    is_gift,order_placed_date, ship_to_state,shipped_date )
    values($d->id,'$d->amazon_order_id','$d->order_id','$d->status', $d->is_multiple, $d->is_pslip_required,
    $d->is_gift,'$d->order_placed_date',' $d->ship_to_state','$d->shipped_date')";
    DB::connection('mysql')->select($sql);
  }

  // Move Detail: Order ID bảng này nối với id_on_be bảng trên
  $sql = " select id, order_id ,  asin, sku , product_id,unit_cost, cost,status, quantity
  from  amazon_dropship_order_details where order_id > $LastID ";
  $ds = DB::connection('mysql_it')->select($sql);
  foreach( $ds as $d)
  {
    $sql = " insert into sal_amazon_dropship_order_details(
    id,order_id, asin,sku , product_id ,unit_cost,  cost, status,quantity)
    values($d->id,'$d->order_id','$d->asin','$d->sku',$d->product_id,$d->unit_cost,$d->cost,'$d->status',$d->quantity)" ;
    DB::connection('mysql')->select($sql);
  }
}
// ============================================================================================================
public function MoveAvcWHAndDIOrder()// Move AVC order WH /DI from BE to gcom
{
  $LastID = 0;// ID cuối cùng trên BE đã move qua gcom
  $sql = " select case when max(id_on_be) >0 then max(id_on_be) else 0 end as id_on_be from sal_amazon_avc_orders ";
  $ds = DB::connection('mysql')->select($sql);
  foreach( $ds  as  $d ){$LastID =  $d->id_on_be;  }

  // Move những ID mới phát sinh
  // Move Master
  $sql = " id, edi_id, avc_order_group_id, po_title,  vendor,  ship_to,  ordered_on,  ship_start,  ship_end ,
  expected_ship_date,  invoice_create_date,  invoice_amount,  invoice_status,  status ,  id_on_be
  from amazon_avc_orders where  id > $LastID ";

  $ds = DB::connection('mysql_it')->select($sql);
  foreach( $ds as $d)
  {
    $sql = " insert into sal_amazon_avc_orders(edi_id, avc_order_group_id, po_title,  vendor,  ship_to,  ordered_on,
    ship_start,  ship_end , expected_ship_date,  invoice_create_date,  invoice_amount,  invoice_status,  status ,id_on_be)"
    . $d->edi_id ."," . $d->avc_order_group_id .",'". $d->po_title ."','". $d->vendor. "','" . $d->ship_to
    ."','". $d->ordered_on. "','".$d->ship_start. "','".$d->ship_end."','" . $d->expected_ship_date. "',"
    ."','". $d->invoice_create_date."','" . $d->invoice_create_date ."'," .  $d->invoice_amount . ","
    . $d->invoice_status .",".$d->status. ",". $d->id ;
    DB::connection('mysql')->select($sql);
  }
  // Move Detail: Order ID bảng này nối với id_on_be bảng trên
  $sql = "select  id, amazon_avc_id,  model_number, asin ,  sku ,  title ,  product_id, submitted_quantity ,  system_confirm ,
  confirm_quantity,  wh_confirm ,  accepted_quantity,received_quantity,  unit_cost,  status ,  print_date,
  print_number ,  back_order ,  is_shipped,  process_profit,add_reserved,salesman_id,  salesleader_id,  comments,
  system_calculated,  expected_ship_date where sal_amazon_avc_id > $LastID ";
  $ds = DB::connection('mysql_it')->select($sql);
  foreach( $ds as $d)
  {
    $sql = " insert into sal_amazon_avc_order_details(id, sal_amazon_avc_id,  model_number, asin ,  sku ,  title ,
    product_id, submitted_quantity ,  system_confirm , confirm_quantity,  wh_confirm ,  accepted_quantity,
    received_quantity,  unit_cost,status)"
    . $d->sal_amazon_avc_id.",'".$d->model_number.",'" . $d->asin ."','". $d->sku ."','". $d->title . "',"
    . $d->product_id. "," . $d->submitted_quantity .","  . $d->system_confirm. ",".$d->confirm_quantity. ",".$d->wh_confirm.","
    . $d->accepted_quantity. ",". $d->received_quantity.",". $d->unit_cost. ",'".$d->status."','" . $d->status ;
    DB::connection('mysql')->select($sql);
  }
}
// ============================================================================================================
public function MoveDataFromBE3ToBPDAndUpdateDepartment($Year,$Month)
{

  // move Product Group
  DB::connection('mysql')->select(" delete from prd_groups");
  DB::connection('mysql')->select("ALTER TABLE prd_groups AUTO_INCREMENT = 1");

  $ProductGroups = DB::connection('mysql_it')->select("
  select id,title,parent_product_group ,product_id , is_parent, product_group_sku from productgroups ");
  foreach( $ProductGroups as  $ProductGroup){
    $sql= " insert into prd_groups( id,title,parent_product_group,product_id,is_parent, product_group_sku)
    values(". $ProductGroup->id . ",'".$ProductGroup->title."',". $ProductGroup->parent_product_group.",'"
    .$ProductGroup->product_id."',".$ProductGroup->is_parent.",'".$ProductGroup->product_group_sku."')";
    DB::connection('mysql')->select($sql);
  }

  // move Product
  DB::connection('mysql')->select(" delete from prd_product");
  DB::connection('mysql')->select("ALTER TABLE prd_product AUTO_INCREMENT = 1");

  $Products = DB::connection('mysql_it')->select(" select id, parent_id , title ,  product_sku , life_cycle
  from products ");
  foreach( $Products as  $Product){
    $title = $Product->title;
    if(strpos($title,"'")>0){$title = str_replace("'", "\\'", $title); }

    $sql= " insert into prd_product( id,parent_id,title,product_sku,life_cycle)
    values(". $Product->id . ",". $Product->parent_id .",'".$title ."','"
    . $Product->product_sku ."'," .$Product->life_cycle .")";

    DB::connection('mysql')->select($sql);
  }

  // Move chi tiết group và các product trong group-> Cấu trúc này sai nhưng phải lấy từ backend 3 đưa qua
  // vì đúng thì product_group và product là quan hệ một nhiều
  //product_group
  DB::connection('mysql')->select(" delete from prd_group_products");
  DB::connection('mysql')->select("ALTER TABLE prd_group_products AUTO_INCREMENT = 1");

  $PrdGrps = DB::connection('mysql_it')->select(" select id, product_id, group_id from product_group ");
  foreach( $PrdGrps as  $pg){

    $sql = " insert into prd_group_products(id,product_group_id,product_id) select "
    .$pg->id .",".  $pg->product_id . "," . $pg->group_id ;

    DB::connection('mysql')->select( $sql);
  }


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


  // move product amazon
  DB::connection('mysql')->select(" delete from prd_amazons");
  DB::connection('mysql')->select("ALTER TABLE prd_amazons AUTO_INCREMENT = 1");
  $AmzProducts= DB::connection('mysql_it')->select(" select id,product_id,asin from amazon_products ");

  foreach(  $AmzProducts as   $AmzProduct){
    $sql = " insert into prd_amazons(id , product_id , asin)
    select " .  $AmzProduct->id ."," . $AmzProduct->product_id .",'".  $AmzProduct->asin ."'" ;
    DB::connection('mysql')->select($sql);
   }

  // move avc_ds_order
  DB::connection('mysql')->select(" delete from sal_orders");
  DB::connection('mysql')->select("ALTER TABLE sal_orders AUTO_INCREMENT = 1");
  $Others= DB::connection('mysql_it')->select(" select id,order_id,status,ship_to_state from amazon_dropship_orders
  where order_placed_date >= '2020-01-01'");

  foreach(  $Others as   $Other){
    $sql = " insert into sal_orders(id , order_id , status,ship_to_state,sales_channel)
    select " .  $Other->id .",'" . $Other->order_id ."','".  $Other->status ."','". $Other->ship_to_state ."',2" ;
    DB::connection('mysql')->select($sql);
   }

   // move avc_ds_order_detail
  DB::connection('mysql')->select(" delete from sal_order_detail");
  DB::connection('mysql')->select("ALTER TABLE sal_order_detail AUTO_INCREMENT = 1");
  $Others= DB::connection('mysql_it')->select(" select id,order_id,asin,sku,product_id, quantity, unit_cost,cost
   from amazon_dropship_order_details  where actual_ship_date >= '2020-01-01' or  actual_ship_date  is null");

  foreach(  $Others as   $Other){
    $sql = " insert into sal_order_detail(id , order_id , asin,sku,product_id,quantity,unit_cost,cost)
    select " .  $Other->id ."," . $Other->order_id .",'".  $Other->asin ."','"
    . $Other->sku ."'," .$Other->product_id .",". $Other->quantity ."," . $Other->unit_cost ."," . $Other->cost . "" ;
    DB::connection('mysql')->select($sql);
   }

  // move user đã bao gồm phòng ở trong này(type = 2 là phòng)
  DB::connection('mysql')->select(" delete from ms_users ");
  DB::connection('mysql')->select("ALTER TABLE ms_users AUTO_INCREMENT = 1");

  //$users= DB::connection('mysql_it')->select(" select * from user where gender is not null and birthday  '0000-00-00'");
  $users= DB::connection('mysql_it')->select(" select * from user ");
  foreach( $users as  $user){
    $id = $user->id;
    $name = $user->name;
    $email= $user->email;
    $username= $user->username;
    $password= $user->password;
    $phone= $user->phone;
    $address= $user->address;
    $gender= $user->gender;
    $birthday= $user->birthday;
    $avatar= $user->avatar;
    $skype= $user->skype;
    $workday= $user->workday;
    $contractday= $user->contractday;
    $description= $user->description;
    $updatetime = $user->updatetime ;
    $usergroup_id= $user->usergroup_id;
    $manager_id= $user->manager_id;
    $is_dev= $user->is_dev;
    $type= $user->type;
    $is_manager= $user->is_manager;
    $sentri_id = $user->sentri_id;
    $openproject_userid= $user->openproject_userid;
    $openproject_apitoken= $user->openproject_apitoken;
    $code= $user->code;
    $publish_dms= $user->publish_dms;
    $published= $user->published;
    $is_change_pass= $user->is_change_pass;
    $gdrive_url= $user->gdrive_url;
    $cover= $user->cover;

    $sql= " insert into ms_users( id , name , email , username , password , phone, address, gender,
    skype,   usergroup_id,  manager_id,  is_dev,  type, is_manager) ";

    $sql = $sql. "select ". $id .",'". $name ."','". $email ."','". $username ."','". $password ."','". $phone ."','". $address ."','". $gender ."','"
    . $skype . "',". $usergroup_id . "," . $manager_id  ."," . $is_dev ."," . $type .",". $is_manager ;

    DB::connection('mysql')->select($sql);
  }

  // Move group_users to ms_group_user_tmp
  DB::connection('mysql')->select(" delete from ms_group_user_tmp ");
  DB::connection('mysql')->select("ALTER TABLE ms_group_user_tmp AUTO_INCREMENT = 1");

  $ds = DB::connection('mysql_it')->select("select group_id,user_id from group_users ");
  foreach( $ds as  $d){
    $sql= "insert into ms_group_user_tmp(group_id,user_id) select " .$d->group_id .",". $d->user_id ;
    DB::connection('mysql')->select($sql);
  }

 // Move ASIN
 DB::connection('mysql')->select(" delete from prd_asin ");
 DB::connection('mysql')->select("ALTER TABLE prd_asin AUTO_INCREMENT = 1");

 $Asins = DB::connection('mysql_it')->select("select asin,product_id, published from asin ");
 foreach( $Asins as  $Asin){
   $sql= "insert into prd_asin(asin,product_id,published) select '" .$Asin->asin ."',". $Asin->product_id . ",".$Asin->published ;
   DB::connection('mysql')->select($sql);
 }

 // Move Product assigned
 DB::connection('mysql')->select(" delete from prd_product_assigns ");
 DB::connection('mysql')->select("ALTER TABLE prd_product_assigns AUTO_INCREMENT = 1");


 // Tìm kiếm cập nhật phòng sales vào từng sku trong bảng sum
 $sProductGroupID = "";
    // Tìm chuỗi nhóm sản phẩm theo từng user phụ trách
 $ds = DB::connection('mysql_it')->select("select user_id , group_id as ProductGroupID from product_assigned where group_id <>'[]'");
 foreach( $ds  as  $d)
 {
    $sProductGroupID = str_replace("[","(",$d->ProductGroupID);
    $sProductGroupID = str_replace("]",")",$sProductGroupID);
    $sProductGroupID = str_replace('"','',$sProductGroupID);

    //print_r ('Nhóm Sản phẩm: '.  $sProductGroupID );
    //print_r ('<br>');

    // Tim phong ban từ user id
    $DepartmentID = 0;

    $sql = " select group_id from ms_group_user_tmp where user_id = $d->user_id and group_id in (336,337,527)";

    $grs = DB::connection('mysql')->select($sql);
    foreach( $grs  as $gr) { $DepartmentID = $gr->group_id; }
    if($DepartmentID == 0){$DepartmentID =337;}

    // Tìm danh sách các sku từ danh sách các nhóm sản phẩm
    $sSku ="";

    $sql = " select DISTINCT(products.product_sku) as sku  from product_group
    INNER join  products on product_group.product_id= products.id
    INNER join  productgroups on product_group.group_id= productgroups.id
    where productgroups.id in $sProductGroupID  ";

    $prds = DB::connection('mysql')->select($sql);
    foreach( $prds  as $prd)
      {
        if($sSku=="")  { $sSku ="'". $prd->sku ; }
        else  { $sSku = $sSku .  "','". $prd->sku ; }
      }
    $sSku = "(" . $sSku . "')";


    $sql = " update sal_selling_summary_monthlys set department_id = $DepartmentID where  the_month = $Month
    and the_year = $Year and sku in " . $sSku;

   // print_r ('sql:'. $sql );
   // print_r ('<br>' );

    DB::connection('mysql')->select($sql);

    $sSku = "";
 }
  // Team Wel phụ trách các kênh 6 (EBAY,Website) danh riêng cho team wel
  $sql = " update sal_selling_summary_monthlys set department_id = 527
  where sales_chanel in (6,8) and the_month = $Month and the_year = $Year ";
  DB::connection('mysql')->select($sql);

 // Team Wel phụ trách các kênh 7( Local/Crailist/Website)
 $sql = " update sal_selling_summary_monthlys set department_id = 527
 where sales_chanel in (7) and the_month = $Month and the_year = $Year and department_id is null  and sku <> 'TEBT' ";
 DB::connection('mysql')->select($sql);

// Những sku không có department phụ trách sẽ được gán cho team XL
$sql = " update sal_selling_summary_monthlys set department_id = 337 where department_id is null and the_month = $Month and the_year = $Year ";
DB::connection('mysql')->select($sql);

}
// ============================================================================================================
// Chuyển data thô từ các bảng import được vào bảng sal_selling_summary_monthlys
  public function ConvertDataToSummary($Month,$Year)
  {

    // xóa hết data tổng hợp bán hàng tất cả các kênh của tháng năm
    DB::connection('mysql')->select(" delete from sal_selling_summary_monthlys where the_month = $Month and the_year = $Year ");

    $SalesChannel =1;// AVC-WH -> FA
    DB::connection('mysql')->select("insert into sal_selling_summary_monthlys
    (sku,sell_quantity,revenue,sales_chanel,by_invoice,life_cycle,the_month,the_year)
    select sku,sum(avc_wh_rs), sum(avc_wh_rev),$SalesChannel  as channel,0,prd_product.life_cycle,$Month as month,$Year as year
    from fa_amazon_real_sales
    left join prd_product on fa_amazon_real_sales.sku = prd_product.product_sku
    where  the_month = $Month and the_year = $Year and avc_wh_rs> 0 and avc_wh_rev > 0
    and sku  is not null and sku <>''
    group by sku,life_cycle ");


    $SalesChannel = 1;// AVC-WH -> AC
    DB::connection('mysql')->select("insert into sal_selling_summary_monthlys
    (sku,sell_quantity,revenue,sales_chanel,life_cycle,the_month,the_year,by_invoice)
    select sku,sum(quantity), sum(amount),$SalesChannel  as channel,life_cycle,$Month as month,$Year as year, 1 as by_invoice
    from fa_selling_monthly_detail
    left join prd_product on fa_selling_monthly_detail.sku = prd_product.product_sku
    where  the_month = $Month and the_year = $Year
    and sales_channel = $SalesChannel and by_invoice = 1
    group by sku,life_cycle,by_invoice ");

    $SalesChannel = 2;// AVC-DS->FA/AC
    DB::connection('mysql')->select(" insert into sal_selling_summary_monthlys
    (sku,sell_quantity,revenue,sales_chanel,life_cycle,the_month,the_year)
    select sku,sum(quantity), sum(amount),sales_channel,life_cycle,the_month,the_year
    from fa_selling_monthly_detail
    left join prd_product on fa_selling_monthly_detail.sku = prd_product.product_sku
    where  the_month = $Month and the_year = $Year
    and sales_channel =  $SalesChannel and sku  is not null
    group by sku,sales_channel,life_cycle,the_month,the_year ");

     $SalesChannel = 3;// AVC-DI -> FA
    DB::connection('mysql')->select(" insert into sal_selling_summary_monthlys
    (sku,sell_quantity,revenue,sales_chanel,life_cycle,the_month,the_year)
    select sku, sum(di_rs) , sum(di_rev) , $SalesChannel  as channel,life_cycle,$Month as month,$Year as year
    from fa_amazon_real_sales
    left join prd_product on fa_amazon_real_sales.sku = prd_product.product_sku
    where  the_month = $Month and the_year = $Year and di_rs >0 and di_rev > 0 and sku  is not null and sku <>''
    group by sku,channel,life_cycle,the_month,the_month");

    $SalesChannel = 3;// AVC-DI -> AC
    DB::connection('mysql')->select("insert into sal_selling_summary_monthlys(
      sku,sell_quantity,revenue,sales_chanel,life_cycle,the_month,the_year,by_invoice)
    select sku,sum(quantity), sum(amount),$SalesChannel  as channel,life_cycle,$Month as month,$Year as year, 1 as by_invoice
    from fa_selling_monthly_detail
    left join prd_product on fa_selling_monthly_detail.sku = prd_product.product_sku
    where  the_month = $Month and the_year = $Year and quantity > 0
    and sales_channel = $SalesChannel and sku  is not null and sku <>''
    group by sku,channel,life_cycle,month,year, by_invoice ");


    // $SalesChannel = 4 ;//WM-DSV
    // DB::connection('mysql')->select(" insert into sal_selling_summary_monthlys
    // (sku,sell_quantity,revenue,sales_chanel,life_cycle,the_month,the_year)
    // select sku,sum(quantity), sum(amount),sales_channel ,life_cycle,the_month,the_year
    // from fa_selling_monthly_detail
    // left join prd_product on fa_selling_monthly_detail.sku = prd_product.product_sku
    // where  the_month = $Month and the_year = $Year and sales_channel =  $SalesChannel
    // group by sku,sales_channel,life_cycle,the_month,the_year ");

    $SalesChannel = 4 ;//WM-DSV
    DB::connection('mysql')->select(" insert into sal_selling_summary_monthlys
    (sku,sell_quantity,revenue,sales_chanel,life_cycle,the_month,the_year)
    select sku,sum(quantity), sum(quantity * price ),4 ,life_cycle,the_month,the_year
    from fa_dsv_promotion_sem_actual
    left join prd_product on fa_dsv_promotion_sem_actual.sku = prd_product.product_sku
    where  the_month = $Month and the_year = $Year
    group by sku,life_cycle,the_month,the_year ");



    $SalesChannel = 5;// WM-MKP
     DB::connection('mysql')->select(" insert into sal_selling_summary_monthlys
     (sku,sell_quantity,revenue,sales_chanel,life_cycle,the_month,the_year)
     select sku, sum(quantity) , sum(amount) ,$SalesChannel  as channel,life_cycle,$Month as month,$Year as year
     from fa_walmart_market
     left join prd_product on fa_walmart_market.sku = prd_product.product_sku
     where  quantity >0 and  the_month = $Month and the_year = $Year
     and (transaction_type like'%SALE%' or transaction_type like'%Sale%') group by sku,channel,life_cycle,month,year ");

    $SalesChannel = 6 ;//EBAY
    DB::connection('mysql')->select(" insert into sal_selling_summary_monthlys
    (sku,sell_quantity,revenue,sales_chanel,store,life_cycle,the_month,the_year)
    select sku,sum(quantity), sum(amount),sales_channel , store,life_cycle,the_month,the_year
    from fa_selling_monthly_detail
    left join prd_product on fa_selling_monthly_detail.sku = prd_product.product_sku
    where  the_month = $Month and the_year = $Year and sales_channel =  $SalesChannel
    and sku  is not null and sku <>''
    and ( type like '%Order%' or type like '%eBay Auction Payment%' or type like '%Oder%')
    group by sku,sales_channel,store,life_cycle,the_month,the_year");

    $SalesChannel = 7 ;//Craiglist/Local
    DB::connection('mysql')->select(" insert into sal_selling_summary_monthlys
    (sku,sell_quantity,selling_price,revenue,sales_chanel,life_cycle,the_month,the_year)
    select sku,sum(quantity),sum(price), sum(amount),sales_channel,life_cycle,the_month,the_year
    from fa_selling_monthly_detail
    left join prd_product on fa_selling_monthly_detail.sku = prd_product.product_sku
    where  the_month = $Month and the_year = $Year and sales_channel =  $SalesChannel and wo = 0
    and sku  is not null and sku <>''
    group by sku,sales_channel,life_cycle,the_month,the_year ");

    // Xử lý xả hàng của kênh craiglist ở đây
    $sql = " select id,sku,selling_price from sal_selling_summary_monthlys
    where sales_chanel =  $SalesChannel  and the_month = $Month and the_year = $Year ";
    $ds= DB::connection('mysql')->select($sql);
    foreach($ds as $d) {
      $sql = " select cogs from fa_unit_costs where sku ='$d->sku' and the_month = $Month and the_year = $Year";
      $d1s= DB::connection('mysql')->select($sql);
      foreach($d1s as $d1) { $Cogs = $d1->cogs; }
      if($d->selling_price <  $Cogs){
        $sql = " delete from sal_selling_summary_monthlys where id  = $d->id " ;
        DB::connection('mysql')->select($sql);
      }
    }

    $SalesChannel = 8 ;//Website
    DB::connection('mysql')->select(" insert into sal_selling_summary_monthlys
    (sku,sell_quantity,revenue,sales_chanel,life_cycle,the_month,the_year)
    select sku,sum(quantity), sum(amount),sales_channel ,life_cycle,the_month,the_year
    from fa_selling_monthly_detail
    left join prd_product on fa_selling_monthly_detail.sku = prd_product.product_sku
    where  the_month = $Month and the_year = $Year and sales_channel =  $SalesChannel
     and sku  is not null and sku <>''
    group by sku,sales_channel,life_cycle,the_month,the_year ");

    $SalesChannel = 9;// FBA
    // Thực hiện chuyển data import thô từ table fa_walmart_market vào sal_selling_summary_monthlys
    DB::connection('mysql')->select(" insert into sal_selling_summary_monthlys
    (sku,sell_quantity,revenue,sales_chanel,life_cycle,the_month,the_year)
    select sku,sum(quantity) , sum(product_sales),  $SalesChannel,life_cycle,$Month , $Year
    from fa_amazon_idzo
    left join prd_product on fa_amazon_idzo.sku = prd_product.product_sku
    where the_year = $Year and the_month = $Month and  quantity >0 and sku  is not null and sku <>''
    and  type like '%Order%' and (fulfillment like'%Amazon%' or fulfillment like'%AMAZON%')
    group by sku,life_cycle ");

    $SalesChannel = 10;// FBM
    DB::connection('mysql')->select(" insert into sal_selling_summary_monthlys
    (sku,sell_quantity,revenue,sales_chanel,life_cycle,the_month,the_year)
    select sku,sum(quantity) , sum(product_sales + shipping_credits ),  $SalesChannel ,life_cycle,$Month ,$Year
    from fa_amazon_idzo
    left join prd_product on fa_amazon_idzo.sku = prd_product.product_sku
    where the_year = $Year and the_month = $Month and  quantity >0 and sku  is not null and sku <>''
    and ( type like '%order%' or type like '%Order%')  and  (fulfillment like '%Seller%' or fulfillment like '%SELLER%')
    group by sku ,life_cycle ");


    $this->AddSkuToSumTableNotAraisingSellButAraisingCost($Month,$Year);

    // cập nhật số Promotion và clip cho tất cả các sku tất cả các kênh
    $this->CaculatePromotionAndClip($Month,$Year);
    // cập nhật số SEM cho tất cả các sku tất cả các kênh
    $this->CaculateSEM($Month,$Year);
    $this->UpdateUnitCostDipMsf($Month,$Year);

    $this->CaculateShipingFee($Month,$Year);
    //$this->CaculateShipingFeeNew($Month,$Year);

    // Update UpdateReferal cho kênh FBA,FBM
    $this->UpdateReferalFullfillment($Month,$Year);

    for( $SalesChannel = 1; $SalesChannel <=10; $SalesChannel ++)
    {

      if($SalesChannel == 6 )// EBAY
      {
       //print_r('Sales Channel' .$SalesChannel);
      // print_r('<br>');
       for($TheStore = 1; $TheStore <= 4 ; $TheStore++ ){ $this->UpdateDetailOnStoreForEbay($Month,$Year,$TheStore);}
      }
      else // Những kênh khác EBAY
      {
       // print_r('Sales Channel ' .$SalesChannel);
       // print_r('<br>');
        // Cập nhật một số thông tin bán hàng cơ bản trên các kênh
        $this->UpdateBasicSellingInforOnSalesChannel($SalesChannel,$Month,$Year);
        // Thực hiện phân bổ Chargeback
        $this->UpdateChargebackFreightHandlingReturnCostOtherFee($SalesChannel,$Month,$Year);
      }
    }
    $this->GetEbayFinalFeeOrPaypalFeeNew($Month,$Year);

    $this->ReAllocateAmountFromChannelToSku($Month,$Year);//
}
// ============================================================================================================
public function AllocateInsuranceAndShippingCalProfitAndOtherSellingFee($Year,$Month,$ByInvoice){
  //$Year,$Month,$ByInvoice
  //$Year = 2020;
  //$Month = 5;
  //$ByInvoice = 1;
  // 1. Phân bổ tiền bảo hiểm theo tất cả các kênh
  $sql = " select  sum(liability_insurance) as liability_insurance from fa_summary_chargeback_monthly
  where  sales_channel = 0 and  the_month = $Month and the_year = $Year  ";
  $ds = DB::connection('mysql')->select($sql);
  $LiabilityInsurance =0;
  foreach( $ds as $d) { $LiabilityInsurance = $d->liability_insurance; }

  // Khai báo mảng chứa net sales cuả các kênh -> Cần bổ sung cho phần AC
  $ArrayNetSales  = array(10);
  $TotalNetSales = 0;

  // Lấy tổng net sales
  $sql = " select sum(nest_sales) as net_sales  from sal_selling_summary_monthlys
  where the_month = $Month and the_year = $Year  and nest_sales > 0  ";
  if( $ByInvoice == 0)
  {
    $sql = $sql . " and 	by_invoice = $ByInvoice ";
  }
  else
  {
    $sql = $sql . " and ( (by_invoice = 0 and sales_chanel in (2,4,5,6,7,8,9,10)) or (by_invoice = 1 and sales_chanel in (1,3)))";
  }

  $ds = DB::connection('mysql')->select($sql);
  foreach( $ds as $d)  {   $TotalNetSales = $d->net_sales; }

  // tính tỷ lệ bảo hiểm trên net sales
  if($TotalNetSales > 0){ $LiabilityInsurancePerNetSales = $LiabilityInsurance /  $TotalNetSales;}
  else{ $LiabilityInsurancePerNetSales = 0;}

  // if($Month==5 && $Year ==2020){
  //   print_r('Tỷ lệ bảo hiểm:'.$LiabilityInsurancePerNetSales );
  //   print_r('<br>');
  // }

  // Cập nhật tiền bảo hiểm vào từng sku
  if($ByInvoice == 0)
  {
    $sql = " update sal_selling_summary_monthlys set liability_insurance = nest_sales * $LiabilityInsurancePerNetSales
    where the_month = $Month and the_year = $Year and nest_sales > 0 and 	by_invoice = $ByInvoice " ;
    DB::connection('mysql')->select($sql);
  }
  else// invoice =1
  {
    $sql = " update sal_selling_summary_monthlys set liability_insurance = nest_sales * $LiabilityInsurancePerNetSales
    where the_month = $Month and the_year = $Year and nest_sales > 0 and sales_chanel in (1,3) and 	by_invoice = $ByInvoice " ;
    DB::connection('mysql')->select($sql);

    $sql = " update sal_selling_summary_monthlys set liability_insurance = nest_sales * $LiabilityInsurancePerNetSales
    where the_month = $Month and the_year = $Year and nest_sales > 0 and sales_chanel in (2,4,5,6,7,8,9,10)
    and 	by_invoice = 0 " ;
    DB::connection('mysql')->select($sql);
  }



  //2. Phân bổ tiền quảng cáo Vine cho các kênh AVC và chỉ cho các sku là introduction và 1 year grow
  $sql = " select  vine from fa_summary_chargeback_monthly
  where  sales_channel = 0 and  the_month = $Month and the_year = $Year  ";
  $ds = DB::connection('mysql')->select($sql);
  $Vine = 0;
  foreach( $ds as $d) { $Vine = $d->vine; }
  // Lấy tổng lượng hàng bán ra trên các kênh AVC với life_cycle = 2,3 introduction, first year
  $sql = " select sum(sell_quantity)as sell_quantity from sal_selling_summary_monthlys
  where   the_month = $Month and the_year = $Year and life_cycle in (2,3) ";
  if($ByInvoice == 1 )
  {
    $sql =  $sql . " and
    (
      ( sales_chanel = 2 and by_invoice = 0)
   or (sales_chanel in(1,3) and (by_invoice = 1))
    )";
  }
  else
  {
    $sql =  $sql . "  and sales_chanel in(1,2,3) and by_invoice = 0 ";
  }
  $ds = DB::connection('mysql')->select($sql);
  $SellQuantity = 0;
  foreach( $ds as $d) { $SellQuantity = $d->sell_quantity; }
  if($SellQuantity>0){  $VineRate = $Vine / $SellQuantity;}
  else{$VineRate =0;}

  $sql = " update sal_selling_summary_monthlys set vine = sell_quantity * $VineRate
  where  the_month = $Month and the_year = $Year  and sales_chanel = 2 and life_cycle in (2,3) ";
  DB::connection('mysql')->select($sql);

  $sql = " update sal_selling_summary_monthlys set vine = sell_quantity * $VineRate
  where  the_month = $Month and the_year = $Year and by_invoice = $ByInvoice and sales_chanel in(1,3) and life_cycle in (2,3) ";
  DB::connection('mysql')->select($sql);

  //print_r('sql:'. $sql);
  //print_r('<br>');
/*
  //Lấy tổng tiền shipping theo tổng số tất cả các kênh ở sheet ChargebackAndOther
  $TotalShippingFee = 0;
  $sql = " select shipping_fee from fa_summary_chargeback_monthly
  where 	sales_channel = 0 and the_month = $Month  and the_year = $Year ";
  $ds = DB::connection('mysql')->select($sql);
  foreach( $ds as $d)  { $TotalShippingFee = $d->shipping_fee; }
  //$TotalShippingFee = 290216.73;
  //Lấy tổng tiền shipping của một số kênh đã sum theo kênh  ở sheet ChargebackAndOther => EBAY
  $sql = " select sum(shipping_fee) as shipping_fee from fa_summary_chargeback_monthly
  where shipping_fee > 0	 and the_month = $Month and the_year = $Year ";
  $ds = DB::connection('mysql')->select($sql);
  foreach( $ds as $d)  { $TotalShippingFeeAllocated = $d->shipping_fee; }

  // Tổng tiền shipping fee cần phần bổ sau khi trừ đi số tiền đã phân bổ từ kênh xuống sku
  $TotalShippingFee = $TotalShippingFee -$TotalShippingFeeAllocated;
  // Tổng số tiền shipping đã tính cho các sku cho các kênh khác ebay
  $sql = " select sum(shiping_fee) as shiping_fee from  sal_selling_summary_monthlys
  where sales_chanel <>6 and 	by_invoice = $ByInvoice
  and the_month = $Month  and the_year = $Year ";
  foreach( $ds as $d)  { $TotalShippingFeeLocated = $d->shipping_fee; }

  if($TotalShippingFee>0)
  {
    // Tỷ lệ phân bổ
    $AllocateRate = $TotalShippingFeeLocated/ $TotalShippingFee;
    $sql = " update sal_selling_summary_monthlys set shiping_fee = shiping_fee * $AllocateRate
    where sales_chanel <>6 and 	by_invoice = $ByInvoice
    and the_month = $Month  and the_year = $Year ";
    DB::connection('mysql')->select($sql);
  }
*/
  $sql = " select id from sal_selling_summary_monthlys  where  the_month = $Month and the_year = $Year  ";
  $ids = DB::connection('mysql')->select($sql);
    foreach( $ids as $id)
    {
      // Vì clip đã nằm trong promotion nên other_selling_expensives không + clip vào(đã cộng promotion)
      $sql = " update sal_selling_summary_monthlys
      set profit  = nest_sales - cogs - promotion - seo_sem - shiping_fee -
      (dip + msf + selling_fees + fullfillment + chargeback
      + coop +  freight_cost   + freight_handling_return_cost + ebay_final_fee + paypal_fee + discount
      + liability_insurance  +  commission  + vine + other_fee),

      other_selling_expensives = dip + msf + selling_fees + fullfillment + chargeback
      + coop +  freight_cost   + freight_handling_return_cost + ebay_final_fee + paypal_fee + discount
      +  liability_insurance  +  commission  + vine + other_fee

      where id = $id->id ";

      // if($Month==5 && $Year ==2020){
      //   print_r('sql:'. $sql );
      //   print_r('<br>');
      // }
      DB::connection('mysql')->select($sql);
    }
}
// ============================================================================================================
// Thực hiện phân bổ các tổng số chi phí theo kênh xuống từng sku của kênh đó
private function ReAllocateAmountFromChannelToSku($Month,$Year) {
  $TotalOtherFee = 0;
  $TotalMSF = 0;
  $OtherFeePerNetSales = 0;
  $MSFPerNetSales = 0;
  //Phân bổ mọi chi phí (nếu có số tổng) theo từng kênh
  //1. Phân bổ theo số real sales
  for($Channel = 1 ; $Channel <= 10; $Channel++)
  {
    $sql = " select sales_channel, store, chargeback_fee,freight_handling_return_cost,other_fee,
    sem_fee,ebay_final_fee,	paypal_fee,referal_fee,msf
    from fa_summary_chargeback_monthly
    where  the_month = $Month and the_year = $Year and sales_channel = $Channel
    and not (chargeback_fee =0 and freight_handling_return_cost = 0 and other_fee = 0
    and	sem_fee = 0 and ebay_final_fee = 0 and paypal_fee = 0 and referal_fee = 0 and msf = 0 )";
    $ds = DB::connection('mysql')->select($sql);
    foreach( $ds  as $d)
    {
      // 1. Phân bổ Charge Back
      if($d->chargeback_fee<> 0)
        {
          $sql = " select sum(chargeback) as chargeback from  sal_selling_summary_monthlys
          where the_month =  $Month and the_year = $Year and sales_chanel = $d->sales_channel
          and store = $d->store and by_invoice = 0 ";
          $d1s = DB::connection('mysql')->select($sql);
          foreach( $d1s as $d1){$Sum = $d1->chargeback;}
          $Rate = $d->chargeback_fee/$Sum;

          $sql = " update sal_selling_summary_monthlys set chargeback = chargeback  *  $Rate
          where the_month = $Month and the_year = $Year and sales_chanel =  $d->sales_channel
          and store = $d->store and by_invoice = 0 ";
          DB::connection('mysql')->select($sql);
        }
      // 2. Phân bổ freight_handling_return_cost
      if($d->freight_handling_return_cost	<> 0)
        {
          $sql = " select sum(freight_handling_return_cost) as fhr_cost from  sal_selling_summary_monthlys
          where the_month =  $Month and the_year = $Year and sales_chanel = $d->sales_channel
          and store = $d->store and by_invoice = 0  ";
          $d1s = DB::connection('mysql')->select($sql);
          foreach( $d1s as $d1){$Sum = $d1->fhr_cost;}
          $Rate = $d->freight_handling_return_cost/$Sum;

          $sql = " update sal_selling_summary_monthlys set freight_handling_return_cost = freight_handling_return_cost  *  $Rate
          where the_month = $Month and the_year = $Year and sales_chanel =  $d->sales_channel
          and store = $d->store and by_invoice = 0 ";
          DB::connection('mysql')->select($sql);
       }
      // 3. Phân bổ other_fee
      if($d->other_fee	<> 0)
        {
          $sql = " select sum(other_fee) as other_fee from  sal_selling_summary_monthlys
          where the_month =  $Month and the_year = $Year and sales_chanel = $d->sales_channel
          and store = $d->store and by_invoice = 0  ";
          $d1s = DB::connection('mysql')->select($sql);
          foreach( $d1s as $d1){$Sum = $d1->other_fee;}
          if($Sum <> 0)
          {
            $Rate = $d->other_fee/$Sum;
            $sql = " update sal_selling_summary_monthlys set other_fee = other_fee  *  $Rate
            where the_month = $Month and the_year = $Year and sales_chanel =  $d->sales_channel
            and store = $d->store and by_invoice = 0 ";
            DB::connection('mysql')->select($sql);
          }
          else
          {
            $sql = " select sum(nest_sales) as nest_sales from  sal_selling_summary_monthlys
            where the_month =  $Month and the_year = $Year and sales_chanel = $d->sales_channel
            and store = $d->store and by_invoice = 0  ";
            $d1s = DB::connection('mysql')->select($sql);
            foreach( $d1s as $d1){$Sum = $d1->nest_sales;}
            $Rate = $d->other_fee/$Sum;
            $sql = " update sal_selling_summary_monthlys set other_fee = nest_sales  *  $Rate
            where the_month = $Month and the_year = $Year and sales_chanel =  $d->sales_channel
            and store = $d->store and by_invoice = 0 ";
            DB::connection('mysql')->select($sql);
          }
        }
      // 4. Phân bổ sem_fee
      if($d->sem_fee	<> 0)
        {
          $sql = " select sum(seo_sem) as 	sem_fee from  sal_selling_summary_monthlys
          where the_month =  $Month and the_year = $Year and sales_chanel = $d->sales_channel
          and store = $d->store and by_invoice = 0  ";
          $d1s = DB::connection('mysql')->select($sql);
          foreach( $d1s as $d1){$Sum = $d1->sem_fee;}
          $Rate = $d->sem_fee/$Sum;

          $sql = " update sal_selling_summary_monthlys set seo_sem = seo_sem  *  $Rate
          where the_month = $Month and the_year = $Year and sales_chanel =  $d->sales_channel
          and store = $d->store and by_invoice = 0 ";
          DB::connection('mysql')->select($sql);
        }
      // 5. Phân bổ ebay_final_fee
      if($d->ebay_final_fee	<> 0)
       {
        $sql = " select sum(ebay_final_fee) as 	ebay_final_fee from  sal_selling_summary_monthlys
        where the_month =  $Month and the_year = $Year and sales_chanel = $d->sales_channel
        and store = $d->store and by_invoice = 0 ";
        $d1s = DB::connection('mysql')->select($sql);
        foreach( $d1s as $d1){$Sum = $d1->ebay_final_fee;}
        $Rate = $d->ebay_final_fee/$Sum;

        $sql = " update sal_selling_summary_monthlys set ebay_final_fee = ebay_final_fee  *  $Rate
        where the_month = $Month and the_year = $Year and sales_chanel =  $d->sales_channel
        and store = $d->store and by_invoice = 0 ";
        DB::connection('mysql')->select($sql);
       }

      // 6. Phân bổ paypal_fee
      if($d->paypal_fee	<> 0)
       {
        $sql = " select sum(nest_sales) as 	nest_sales  from  sal_selling_summary_monthlys
        where the_month =  $Month and the_year = $Year and sales_chanel = $d->sales_channel
        and store = $d->store and by_invoice = 0  ";
        $d1s = DB::connection('mysql')->select($sql);
        foreach( $d1s as $d1){$Sum = $d1->nest_sales;}
        $Rate = $d->paypal_fee/$Sum;

        print_r(' Paypal Fee'. $d->paypal_fee);
        print_r('<br>');

        $sql = " update sal_selling_summary_monthlys set paypal_fee  = nest_sales  *  $Rate
        where the_month = $Month and the_year = $Year and sales_chanel =  $d->sales_channel
        and store = $d->store and by_invoice = 0 ";
        DB::connection('mysql')->select($sql);
       }

      // 7. Phân bổ referal_fee
      if($d->referal_fee	<> 0)
       {
        $sql = " select sum(selling_fees) as 	selling_fees  from  sal_selling_summary_monthlys
        where the_month =  $Month and the_year = $Year and sales_chanel = $d->sales_channel
        and store = $d->store and by_invoice = 0 ";
        $d1s = DB::connection('mysql')->select($sql);
        foreach( $d1s as $d1){$Sum = $d1->selling_fees;}
        $Rate = $d->referal_fee /$Sum;

        $sql = " update sal_selling_summary_monthlys set selling_fees  = selling_fees  *  $Rate
        where the_month = $Month and the_year = $Year and sales_chanel =  $d->sales_channel
        and store = $d->store and by_invoice = 0 ";
        DB::connection('mysql')->select($sql);
      }
      // 7. Phân bổ msf
      if($d->msf	<> 0)
       {
        $sql = " select sum(msf) as 	msf  from  sal_selling_summary_monthlys
        where the_month =  $Month and the_year = $Year and sales_chanel = $d->sales_channel
        and store = $d->store and by_invoice = 0 ";
        $d1s = DB::connection('mysql')->select($sql);
        foreach( $d1s as $d1){$Sum = $d1->msf;}
        $Rate = $d->msf /$Sum;

        $sql = " update sal_selling_summary_monthlys set msf  = msf  *  $Rate
        where the_month = $Month and the_year = $Year and sales_chanel =  $d->sales_channel
        and store = $d->store and by_invoice = 0 ";
        DB::connection('mysql')->select($sql);
       }
    }// Foreach
  }// For channel

//2. Phân bổ theo số PO(chỉ cho kênh 1,3)
for($Channel = 1 ; $Channel <= 3; $Channel++)
{
  $sql = " select sales_channel, store, chargeback_fee,freight_handling_return_cost,other_fee,
  sem_fee,ebay_final_fee,	paypal_fee,referal_fee,msf
  from fa_summary_chargeback_monthly
  where  the_month = $Month and the_year = $Year and sales_channel in (1,3)
  and not (chargeback_fee =0 and freight_handling_return_cost = 0 and other_fee = 0
  and	sem_fee = 0 and ebay_final_fee = 0 and paypal_fee = 0 and referal_fee = 0 and msf = 0 )";
  $ds = DB::connection('mysql')->select($sql);
  foreach( $ds  as $d)
  {
    // 1. Phân bổ Charge Back
    if($d->chargeback_fee<> 0)
      {
        $sql = " select sum(chargeback) as chargeback from  sal_selling_summary_monthlys
        where the_month =  $Month and the_year = $Year and sales_chanel = $d->sales_channel
        and store = $d->store and by_invoice = 1 ";
        $d1s = DB::connection('mysql')->select($sql);
        foreach( $d1s as $d1){$Sum = $d1->chargeback;}
        $Rate = $d->chargeback_fee/$Sum;

        $sql = " update sal_selling_summary_monthlys set chargeback = chargeback  *  $Rate
        where the_month = $Month and the_year = $Year and sales_chanel =  $d->sales_channel
        and store = $d->store and by_invoice = 1 ";
        DB::connection('mysql')->select($sql);
      }
    // 2. Phân bổ freight_handling_return_cost
    if($d->freight_handling_return_cost	<> 0)
      {
        $sql = " select sum(freight_handling_return_cost) as fhr_cost from  sal_selling_summary_monthlys
        where the_month =  $Month and the_year = $Year and sales_chanel = $d->sales_channel
        and store = $d->store and by_invoice = 1 ";
        $d1s = DB::connection('mysql')->select($sql);
        foreach( $d1s as $d1){$Sum = $d1->fhr_cost;}
        $Rate = $d->freight_handling_return_cost/$Sum;

        $sql = " update sal_selling_summary_monthlys set freight_handling_return_cost = freight_handling_return_cost  *  $Rate
        where the_month = $Month and the_year = $Year and sales_chanel =  $d->sales_channel
        and store = $d->store and by_invoice = 1 ";
        DB::connection('mysql')->select($sql);
     }
     // 3. Phân bổ other_fee
     if($d->other_fee	<> 0)
     {
       $sql = " select sum(other_fee) as other_fee from  sal_selling_summary_monthlys
       where the_month =  $Month and the_year = $Year and sales_chanel = $d->sales_channel
       and store = $d->store and by_invoice = 1  ";
       $d1s = DB::connection('mysql')->select($sql);
       foreach( $d1s as $d1){$Sum = $d1->other_fee;}
       if($Sum <> 0)
       {
         $Rate = $d->other_fee/$Sum;
         $sql = " update sal_selling_summary_monthlys set other_fee = other_fee  *  $Rate
         where the_month = $Month and the_year = $Year and sales_chanel =  $d->sales_channel
         and store = $d->store and by_invoice = 1 ";
         DB::connection('mysql')->select($sql);
       }
       else
       {
         $sql = " select sum(nest_sales) as nest_sales from  sal_selling_summary_monthlys
         where the_month =  $Month and the_year = $Year and sales_chanel = $d->sales_channel
         and store = $d->store and by_invoice = 1  ";
         $d1s = DB::connection('mysql')->select($sql);
         foreach( $d1s as $d1){$Sum = $d1->nest_sales;}

         $sql = " update sal_selling_summary_monthlys set other_fee = nest_sales  *  $Rate
         where the_month = $Month and the_year = $Year and sales_chanel =  $d->sales_channel
         and store = $d->store and by_invoice = 1 ";
         DB::connection('mysql')->select($sql);
       }
     }
    // 4. Phân bổ sem_fee
    if($d->sem_fee	<> 0)
      {
        $sql = " select sum(seo_sem) as 	sem_fee from  sal_selling_summary_monthlys
        where the_month =  $Month and the_year = $Year and sales_chanel = $d->sales_channel
        and store = $d->store and by_invoice = 1  ";
        $d1s = DB::connection('mysql')->select($sql);
        foreach( $d1s as $d1){$Sum = $d1->sem_fee;}
        $Rate = $d->sem_fee/$Sum;

        $sql = " update sal_selling_summary_monthlys set seo_sem = seo_sem  *  $Rate
        where the_month = $Month and the_year = $Year and sales_chanel =  $d->sales_channel
        and store = $d->store and by_invoice = 1 ";
        DB::connection('mysql')->select($sql);
      }
    // 5. Phân bổ ebay_final_fee
    if($d->ebay_final_fee	<> 0)
     {
      $sql = " select sum(ebay_final_fee) as 	ebay_final_fee from  sal_selling_summary_monthlys
      where the_month =  $Month and the_year = $Year and sales_chanel = $d->sales_channel
      and store = $d->store and by_invoice = 1 ";
      $d1s = DB::connection('mysql')->select($sql);
      foreach( $d1s as $d1){$Sum = $d1->ebay_final_fee;}
      $Rate = $d->ebay_final_fee/$Sum;

      $sql = " update sal_selling_summary_monthlys set ebay_final_fee = ebay_final_fee  *  $Rate
      where the_month = $Month and the_year = $Year and sales_chanel =  $d->sales_channel
      and store = $d->store and by_invoice = 1 ";
      DB::connection('mysql')->select($sql);
     }

    // 6. Phân bổ paypal_fee
    if($d->paypal_fee	<> 0)
     {
      $sql = " select sum(paypal_fee ) as 	paypal_fee  from  sal_selling_summary_monthlys
      where the_month =  $Month and the_year = $Year and sales_chanel = $d->sales_channel
      and store = $d->store and by_invoice = 1 ";
      $d1s = DB::connection('mysql')->select($sql);
      foreach( $d1s as $d1){$Sum = $d1->paypal_fee;}
      $Rate = $d->paypal_fee /$Sum;

      $sql = " update sal_selling_summary_monthlys set paypal_fee  = paypal_fee  *  $Rate
      where the_month = $Month and the_year = $Year and sales_chanel =  $d->sales_channel
      and store = $d->store and by_invoice = 1 ";
      DB::connection('mysql')->select($sql);
     }

    // 7. Phân bổ referal_fee
    if($d->referal_fee	<> 0)
     {
      $sql = " select sum(selling_fees) as 	selling_fees  from  sal_selling_summary_monthlys
      where the_month =  $Month and the_year = $Year and sales_chanel = $d->sales_channel
      and store = $d->store and by_invoice = 1 ";
      $d1s = DB::connection('mysql')->select($sql);
      foreach( $d1s as $d1){$Sum = $d1->selling_fees;}
      $Rate = $d->referal_fee /$Sum;

      $sql = " update sal_selling_summary_monthlys set selling_fees  = selling_fees  *  $Rate
      where the_month = $Month and the_year = $Year and sales_chanel =  $d->sales_channel
      and store = $d->store and by_invoice = 1 ";
      DB::connection('mysql')->select($sql);
    }
    // 7. Phân bổ msf
    if($d->msf	<> 0)
     {
      $sql = " select sum(msf) as 	msf  from  sal_selling_summary_monthlys
      where the_month =  $Month and the_year = $Year and sales_chanel = $d->sales_channel
      and store = $d->store and by_invoice = 1 ";
      $d1s = DB::connection('mysql')->select($sql);
      foreach( $d1s as $d1){$Sum = $d1->msf;}
      $Rate = $d->msf /$Sum;

      $sql = " update sal_selling_summary_monthlys set msf  = msf  *  $Rate
      where the_month = $Month and the_year = $Year and sales_chanel =  $d->sales_channel
      and store = $d->store and by_invoice = 1 ";
      DB::connection('mysql')->select($sql);
     }
  }// Foreach
}// For channel


}
// ============================================================================================================
private function UpdateReferalFullfillment($Month,$Year) {
  $sql = "select id, sku, sales_chanel from  sal_selling_summary_monthlys where  the_month = $Month
   and the_year = $Year and sales_chanel in (9,10)";
   $SellingFee = 0;
   $Fullfillment =0;
   $ids = DB::connection('mysql')->select($sql);
   foreach( $ids as $id){
    // Lấy Referal
    if ($id->sales_chanel == 9){
      $sql =" select -sum(selling_fees) as SellingFees ,
      (-sum(fba_fees) - sum(shipping_credits) + sum(-promotional_rebates)) as Fullfillment
      from fa_amazon_idzo where  the_month = $Month and the_year = $Year
      and (fulfillment like '%Amazon%' or fulfillment like '%AMAZON%') and sku = ". "'" . $id->sku ."'" ;
     }
     else{//sales_chanel == 10
      $sql =" select -sum(selling_fees) as SellingFees, 0 as Fullfillment
      from fa_amazon_idzo where  the_month = $Month and the_year = $Year
      and (fulfillment like '%Seller%' or fulfillment like '%SELLER%') and sku = ". "'" . $id->sku ."'" ;
     }

     $ds = DB::connection('mysql')->select($sql);
     foreach( $ds as $d){
      $SellingFee = $this->iif(is_null($d->SellingFees),0, $d->SellingFees);
      $Fullfillment = $this->iif(is_null( $d->Fullfillment),0,  $d->Fullfillment);
     }
    $sql =" update sal_selling_summary_monthlys set selling_fees =  $SellingFee, 	fullfillment = $Fullfillment where id = $id->id ";
    DB::connection('mysql')->select($sql);
   }
}
// ============================================================================================================
  public function UpdateUnitCostDipMsf($Month,$Year)
   {
    $sql = "select id, sku,sales_chanel from  sal_selling_summary_monthlys
    where  the_month = $Month and the_year = $Year ";
    $dsSkus = DB::connection('mysql')->select($sql);
    foreach($dsSkus as $dsSku)
    {
      // lấy giá cogs,fob
      $sql = " Select  cogs, fob from fa_unit_costs  where  the_month = $Month and the_year = $Year and sku = '$dsSku->sku '" ;
      $DsUnitCosts = DB::connection('mysql')->select($sql);
      foreach($DsUnitCosts as $DsUnitCost){
        $cogs =  $this->iif(is_null($DsUnitCost->cogs),0,$DsUnitCost->cogs);
        // Tìm giá vốn trong DI booking trước
        $fob =  $this->GetDI_COGS($dsSku->sku,$Year, $Month);
        // Nếu không có thì lấy giá FOB
        if($fob == 0){$fob =$this->iif(is_null($DsUnitCost->fob),0,$DsUnitCost->fob);}
        // Vẫn không thấy thì lấy giá cogs
        if($fob == 0){$fob =$this->iif(is_null($DsUnitCost->cogs),0,$DsUnitCost->cogs);}
      }
      $NewCogs = 0;

      if($dsSku->sales_chanel == 3 ) {  $NewCogs = $fob;  } // DI
      else  { $NewCogs =  $cogs; }

      $Dip = 0.00;
      $MSF = 0.0000;
      if($dsSku->sales_chanel == 9 )// FBA
      {
        // Lấy chi phi DIP
        $sql = " select sum(dip) as dip from fa_dip_monthly where  the_month = $Month and the_year = $Year and sku = '" .  $dsSku->sku . "'";
        $DsDips = DB::connection('mysql')->select($sql);
        foreach($DsDips as $DsDip){    $Dip =  $this->iif(is_null($DsDip->dip),0,$DsDip->dip);  }
        // Lấy chi phi MSF
        $sql= " select sum(fa_fba_monthly_store_fee.amount) as msf
        from fa_fba_monthly_store_fee where the_month =  $Month and the_year = $Year
        and fa_fba_monthly_store_fee.sku =  '" .  $dsSku->sku . "'";

        $dsMSFs  = DB::connection('mysql')->select($sql);
        foreach($dsMSFs as $dsMSF){ $MSF =  $this->iif(is_null($dsMSF->msf),0,$dsMSF->msf);   }
      }

      $sql = " update sal_selling_summary_monthlys set unit_cogs = $NewCogs,  dip = $Dip, msf = $MSF     where id = $dsSku->id ";
     // print_r('sql '. $sql );
     // print_r('<br>' );
      DB::connection('mysql')->select($sql);
    }
  }

  // ============================================================================================================
    public function iif($condition, $true, $false) {
    return ($condition?$true:$false);
    }

  // ============================================================================================================
  // Cập nhật những thông tin bán hàng cơ bản trên các kênh không phải EBAY
  private function UpdateBasicSellingInforOnSalesChannel($SalesChannel,$Month,$Year){

    $sql = "select id, sku,sell_quantity,revenue,unit_cogs  from sal_selling_summary_monthlys
    where the_month = $Month and the_year = $Year and sales_chanel = $SalesChannel ";

   // print_r('sql'.  $sql );
   // print_r('<br>');

    $ds = DB::connection('mysql')->select($sql);
    foreach($ds as $d)
    {
     $id = $d->id;
     $sku = $d->sku;
     $Quantity = $this->iif(is_null($d->sell_quantity),0, $d->sell_quantity);

     $this->GetReturnRefund($sku,$SalesChannel,$Month,$Year);
     $Return = $this->gReturn;
     $Refund = $this->gRefund;

     $this->gReturn=0;
     $this->gRefund=0;

     $Revenue = $this->iif(is_null($d->revenue),0,$d->revenue);

     $UnitCogs =  $this->iif(is_null($d->unit_cogs),0,$d->unit_cogs);

     $Cogs =  ($Quantity - $Return ) * $UnitCogs;

    // print_r('sku: '. $sku . 'Sell Quantity: ' . $Quantity. 'Return: '. $Return . 'Unit Cogs: '.  $UnitCogs . 'Cogs: ' . $Cogs   );
    // print_r('<br>');

     $NetSales = $Revenue - $Refund ;

    // if(($SalesChannel == 1 ||$SalesChannel == 2 ) && ( $NetSales > 0)){ // AVC-WH, AVC-DS
    if(($SalesChannel == 1 ||$SalesChannel == 2 ) ){ // AVC-WH, AVC-DS
        $Coop =  $NetSales/100 * 8 ;
     }
     else{$Coop = 0;}

     //if($SalesChannel == 1 && $NetSales > 0) { // AVC-WH
     if($SalesChannel == 1) { // AVC-WH
       $FreightCost = $NetSales/100 * 11.7 ;
      }
     else{ $FreightCost = 0;}

     if($SalesChannel == 5){// wm mkp
        $Commission = $this->GetCommission($sku,$Month,$Year);
      }
      else{ $Commission =0;  }

      if($SalesChannel == 7){// Craiglist
        $Discount = $this->GetDiscount($sku,$Month,$Year);
      }
      else{  $Discount = 0;  }

      // website
      if($SalesChannel == 8){
        $PaypalFee = $this->GetPaypalFee($sku,$SalesChannel,$Month,$Year);
      }
      else{$PaypalFee=0; }

     DB::connection('mysql')->select(" update sal_selling_summary_monthlys
     set return_quantity = $Return, refund = $Refund,nest_sales = $NetSales, coop = $Coop, cogs=  $Cogs ,
     freight_cost = $FreightCost, paypal_fee = $PaypalFee, commission = $Commission, discount = $Discount  where id = $id");
   }
  }

  // ============================================================================================================
  private function GetDiscount($sku,$Month,$Year)
  {
    $Discount = 0;
    $sql = " select sum(discount_value) as discount  from fa_selling_monthly_detail
    where the_month = $Month and the_year = $Year and sales_channel = 7 and  sku ='". $sku ."'";
    $ds= DB::connection('mysql')->select($sql);
    foreach($ds as $d){
      $Discount = $this->iif(is_null($d->discount),0,$d->discount);
    }
    return $Discount;
  }
  // ============================================================================================================
  private function GetPaypalFee($sku,$SalesChannel,$Month,$Year){// chỉ áp dụng cho Website
    $PaypalFee =0.0;
    $sql = "Select sum(paypal_fee) as paypal_fee from fa_selling_monthly_detail
    where the_month = $Month and the_year = $Year and sales_channel = $SalesChannel and sku ='". $sku ."'";
    $ds = DB::connection('mysql')->select($sql);
    foreach($ds as $d){
      $PaypalFee = $this->iif(is_null($d->paypal_fee),0,$d->paypal_fee);
    }
    return $PaypalFee;
  }
 // ============================================================================================================
 private function GetCommission($sku,$Month,$Year){
  $Commission = 0;

  $sql = " select sum(commission_from_sale) as commission  from fa_walmart_market
  where the_month = $Month and the_year = $Year and  sku ='". $sku ."'";

  $ds = DB::connection('mysql')->select($sql);
  foreach($ds as $d){
    $Commission = $this->iif(is_null($d->commission),0,$d->commission);
  }
  return $Commission;

 }
 // ============================================================================================================
   private function UpdateDetailOnStoreForEbay($Month,$Year,$Store){
    $x = 0;
    $ds = DB::connection('mysql')->select("select id, sku,sell_quantity,revenue,unit_cogs, chargeback,sales_chanel,the_month,the_year
    from sal_selling_summary_monthlys  where the_month = $Month and the_year = $Year and sales_chanel = 6  and store= $Store ");
   foreach($ds as $d)
   {
     $id = $d->id;
     $sku = $d->sku;
     $Quantity = $this->iif(is_null( $d->sell_quantity),0, $d->sell_quantity);
     $Revenue = $this->iif(is_null($d->revenue),0, $d->revenue);
     $Cogs =  $this->iif(is_null($d->unit_cogs),0, $d->unit_cogs);

     $this->GetReturnRefundForEBay($sku, $Month,$Year,$Store);

     $Return = $this->gReturnEBAY;
     $Refund = $this->gRefundEBAY;

     $NetSales = $Revenue - $Refund;

     $Cogs = ($Quantity - $Return) * $Cogs;

     $Promotion = 0;
     $SEM = 0;
     $Chargeback = 0;
     $Coop = 0;
     $FreightCost =0;
     //if($d->sell_quantity > 0 ){ $this->GetEbayFinalFeeOrPaypalFee($sku,$Month,$Year,$Store);   }

     DB::connection('mysql')->select(" update sal_selling_summary_monthlys
     set return_quantity = $Return, refund = $Refund,nest_sales = $NetSales,cogs = $Cogs ,promotion= $Promotion,
     seo_sem = $SEM , chargeback = $Chargeback , coop = $Coop,freight_cost = $FreightCost  where id = $id");

     $this->gEbayPaypalFee =0;
     $this->gEbayFinalFee =0;
   }
  }
   // ============================================================================================================
   public function GetEbayFinalFeeOrPaypalFeeNew($Month,$Year)
    {
     // Lấy tổng số EbayFinal Fee và EnayPaypal Fee từ sheet ChargebackAndOther
     $EbayFinalPerNetSales = 1;
     $EbayPaypal_feePerNetSales = 1;

     for($Store =1;$Store<=4;$Store++ )
     {
      $sql = " select ebay_final_fee , paypal_fee from fa_summary_chargeback_monthly
      where the_month = $Month and the_year = $Year and 	sales_channel = 6 and store = $Store " ;
      $ds=  DB::connection('mysql')->select($sql);
      foreach($ds as $d)
      {
       $TotalEbayFinalFeeInvoice = $d->ebay_final_fee;
       $TotalEbayPaypalFeeInvoice= $d->paypal_fee;

       // Lấy tổng  net sales của Ebay
       $TotalEbayNetSales = 0;
       $sql = " select sum(nest_sales) as Totalnest_sales from sal_selling_summary_monthlys
       where the_month= $Month and the_year = $Year and sales_chanel = 6 and store = $Store ";
       $ds1=  DB::connection('mysql')->select($sql);
       foreach($ds1 as $d1) {  $TotalEbayNetSales = $d1->Totalnest_sales; }

       // Tính trung bình $EbayFinal trên một đơn vị net sales
      if($TotalEbayNetSales>0)
      {
       $EbayFinalPerNetSales = $TotalEbayFinalFeeInvoice/ $TotalEbayNetSales;
       $EbayPaypal_feePerNetSales = $TotalEbayPaypalFeeInvoice/$TotalEbayNetSales;
       $sql = " select id, nest_sales from sal_selling_summary_monthlys
       where the_month= $Month and the_year = $Year and sales_chanel = 6 and  store = $Store";
       $ds2=  DB::connection('mysql')->select($sql);
       foreach($ds2 as $d2)
       {
        $sql = " update sal_selling_summary_monthlys set ebay_final_fee = nest_sales * $EbayFinalPerNetSales,
        paypal_fee = nest_sales * $EbayPaypal_feePerNetSales     where id= $d2->id ";
        DB::connection('mysql')->select($sql);
       }
      }
      }
     }
   }
  // ============================================================================================================
  private function GetEbayFinalFeeOrPaypalFee($sku,$Month,$Year,$Store)
  {
   // $sql = " select sum(payment_processing_fee) as FinalFee from fa_selling_monthly_detail
   // where the_month = $Month and the_year = $Year
   // and sales_channel = 6 and store = $Store and sku = " ."'" .$sku ."'" ;


    $sql = " select sum( when payment_processing_fee > 0 then payment_processing_fee
    else - payment_processing_fee end) as FinalFee from fa_selling_monthly_detail
      where the_month = $Month and the_year = $Year
      and sales_channel = 6 and store = $Store and sku = " ."'" .$sku ."'" ;

      $ids = DB::connection('mysql')->select($sql);
      foreach($ids as $id){ $result = $this->iif(is_null( $id->FinalFee),0, $id->FinalFee);    }

      if($Store != 2)
      {
        $this->gEbayFinalFee = $result;
        $this->gEbayPaypalFee = 0;
      }
      else
      {
        $this->gEbayFinalFee = 0;
        $this->gEbayPaypalFee = $result;
      }
      // Phân bổ lại tiền  ebay_final_fee , paypal_fee theo hóa đơn
      $sql = " select ebay_final_fee , paypal_fee from fa_summary_chargeback_monthly
      where the_month = $Month and the_year = $Year and 	sales_channel = 6  " ;
      $ds=  DB::connection('mysql')->select($sql);
      foreach($ds as $d)
      {
       $TotalFinalFeeInvoice = $d->ebay_final_fee;
       $TotalPaypalFeeInvoice= $d->paypal_fee;
      }

      $sql = " select sum(ebay_final_fee) as ebay_final_fee,sum(paypal_fee) as paypal_fee
      from sal_selling_summary_monthlys where the_month = $Month and the_year = $Year and 	sales_chanel = 6  ";
      $ds=  DB::connection('mysql')->select($sql);
      foreach($ds as $d)
      {
       $TotalFinalFeeTheo = $d->ebay_final_fee;
       $TotalPaypalFeeTheo = $d->paypal_fee;
      }
      if($TotalFinalFeeTheo!=0) { $FinalFeeRate =  $TotalFinalFeeInvoice / $TotalFinalFeeTheo; }
      else{$FinalFeeRate=1;}
      if($TotalPaypalFeeTheo!=0){ $PaypalFeeRate =   $TotalPaypalFeeInvoice/ $TotalPaypalFeeTheo;}
      else{$PaypalFeeRate=1;}

      $sql = " update sal_selling_summary_monthlys set ebay_final_fee = ebay_final_fee * $FinalFeeRate
      where the_month = $Month and the_year = $Year and 	sales_chanel = 6 and store <> 2  ";
      DB::connection('mysql')->select($sql);

      $sql = " update sal_selling_summary_monthlys set paypal_fee = paypal_fee * $PaypalFeeRate
      where the_month = $Month and the_year = $Year and 	sales_chanel = 6 and store = 2  ";
      DB::connection('mysql')->select($sql);

  }
  // ============================================================================================================
  private function AddSkuToSumTableNotAraisingSellButAraisingCost($Month,$Year)
  {
     //I. Bổ sung những sku không có phát sinh bán hàng nhưng có phát sinh return, refund vào bảng sum
      //1. AVC WH
      //1.1 FA
      $sql= " insert into sal_selling_summary_monthlys(sku,	return_quantity,refund,	sales_chanel,	life_cycle,the_month,the_year)
      select sku, sum(quantity), sum(amount),1 ,life_cycle,$Month, $Year from fa_avc_returns
      left join prd_product on fa_avc_returns.sku = prd_product.product_sku
      where the_month =  $Month and the_year = $Year
      and vendor_code  like '%YES4A%' and sku is not null and sku <>''
      and sku not in (select sku from sal_selling_summary_monthlys
      where  the_month = $Month  and the_year =  $Year and sales_chanel = 1 and by_invoice = 0)
      group by sku,life_cycle ";
      DB::connection('mysql')->select($sql);

      //1.2 AC
      $sql= " insert into sal_selling_summary_monthlys
      (sku,	return_quantity,refund,	sales_chanel,life_cycle,the_month,the_year,by_invoice)
      select sku, sum(quantity), sum(amount),1 ,life_cycle, $Month as month, $Year as year, 1  from fa_avc_returns
      left join prd_product on fa_avc_returns.sku = prd_product.product_sku
      where the_month =  $Month and the_year = $Year
      and vendor_code  like '%YES4A%' and sku is not null and sku <>''
      and sku not in (select sku from sal_selling_summary_monthlys
      where  the_month = $Month  and the_year =  $Year and sales_chanel = 1 and by_invoice = 1)
      group by sku,life_cycle,month,year ";
      DB::connection('mysql')->select($sql);


      //2. AVC DS
      //2.1 FA
      $sql= " insert into sal_selling_summary_monthlys(sku,	return_quantity,refund,	sales_chanel,life_cycle,the_month,the_year)
      select sku, sum(quantity), sum(amount),2 ,life_cycle, $Month, $Year from fa_avc_returns
      left join prd_product on fa_avc_returns.sku = prd_product.product_sku
      where the_month =  $Month and the_year = $Year
      and vendor_code  like '%AUYAD%' and sku is not null and sku <>''
      and sku not in (select sku from sal_selling_summary_monthlys
      where  the_month = $Month  and the_year =  $Year and sales_chanel = 2 and by_invoice = 0)
      group by sku,life_cycle ";
      DB::connection('mysql')->select($sql);


      //3. AVC DI
      //3.1 FA
      $sql= " insert into sal_selling_summary_monthlys(sku,	return_quantity,refund,	sales_chanel,life_cycle,by_invoice,the_month,the_year)
      select sku, sum(quantity), sum(amount),3 ,life_cycle,0, $Month, $Year from fa_avc_returns
      left join prd_product on fa_avc_returns.sku = prd_product.product_sku
      where the_month =  $Month and the_year = $Year
      and (vendor_code  like '%YES4U%' or vendor_code  like '%YES4V%')
      and sku not in (select sku from sal_selling_summary_monthlys
      where  the_month = $Month  and the_year =  $Year and sales_chanel = 3 and by_invoice = 0)
      group by sku,life_cycle ";
      DB::connection('mysql')->select($sql);

      //3.2 AC
      $sql= " insert into sal_selling_summary_monthlys(sku,	return_quantity,refund,	sales_chanel,life_cycle,by_invoice,the_month,the_year)
      select sku, sum(quantity), sum(amount),3 , life_cycle,1, $Month, $Year from fa_avc_returns
      left join prd_product on fa_avc_returns.sku = prd_product.product_sku
      where the_month =  $Month and the_year = $Year
      and (vendor_code  like '%YES4U%' or vendor_code  like '%YES4V%')
      and sku not in (select sku from sal_selling_summary_monthlys
      where  the_month = $Month  and the_year =  $Year and sales_chanel = 3 and by_invoice = 1)
      group by sku ,life_cycle";
      DB::connection('mysql')->select($sql);

      //4. WM-DS
      $sql= " insert into sal_selling_summary_monthlys(sku,return_quantity,refund,sales_chanel,life_cycle,the_month,the_year)
      select sku, sum(quantity),sum(amount),4,life_cycle, $Month, $Year  from fa_wm_dsv_returns
      left join prd_product on fa_wm_dsv_returns.sku = prd_product.product_sku
      where the_month =  $Month and the_year = $Year and sku is not null and sku <>''
      and sku not in (select sku from sal_selling_summary_monthlys where  the_month = $Month  and the_year =  $Year and sales_chanel = 4)
      group by sku,life_cycle ";
      DB::connection('mysql')->select($sql);
      //5. WM-MKP
      $sql= " insert into sal_selling_summary_monthlys(sku,	return_quantity,refund,	sales_chanel,life_cycle,the_month,the_year)
      select sku, sum(quantity), sum(amount) ,5,life_cycle, $Month, $Year  from fa_walmart_market
      left join prd_product on fa_walmart_market.sku = prd_product.product_sku
      where the_month =  $Month and the_year = $Year and transaction_type like '%REFUNDED%' and sku is not null and sku <>''
      and sku not in (select sku from sal_selling_summary_monthlys where  the_month = $Month  and the_year =  $Year and sales_chanel = 5)
      group by sku,life_cycle ";
      DB::connection('mysql')->select($sql);

      //6. EBAY
      for($Store = 1;$Store<=4;$Store++)
      {
      $sql= " insert into sal_selling_summary_monthlys(sku,	return_quantity,refund,	sales_chanel,store,life_cycle,the_month,the_year)
      select sku, sum(quantity), sum(amount) ,sales_channel,store,life_cycle, $Month, $Year  from fa_selling_monthly_detail
      left join prd_product on fa_selling_monthly_detail.sku = prd_product.product_sku
      where the_month =  $Month and the_year = $Year and sales_channel = 6 and store = $Store  and sku is not null and sku <>''
      and ( type like'%Refund%')
      and sku not in (
        select sku from sal_selling_summary_monthlys where  the_month = $Month  and the_year =  $Year
        and sales_chanel = 6 and store = $Store
        )
      group by sku,life_cycle ";
      DB::connection('mysql')->select($sql);
      }
      //7. Craiglist/Local
      $sql= " insert into sal_selling_summary_monthlys(sku,	return_quantity,refund,	sales_chanel,life_cycle,the_month,the_year)
      select sku, sum(quantity), sum(amount) ,7,life_cycle, $Month, $Year  from fa_return_refund_craiglist_website
      left join prd_product on fa_return_refund_craiglist_website.sku = prd_product.product_sku
      where the_month =  $Month and the_year = $Year and is_craiglist like '%Yes%' and sku is not null and sku <>''
      and sku not in (select sku from sal_selling_summary_monthlys where  the_month = $Month  and the_year =  $Year and sales_chanel = 7)
      group by sku,life_cycle ";
      DB::connection('mysql')->select($sql);
      // Website
      $sql= " insert into sal_selling_summary_monthlys(sku,	return_quantity,refund,	sales_chanel,life_cycle,the_month,the_year)
      select sku, sum(quantity), sum(amount) ,8,life_cycle, $Month, $Year  from fa_return_refund_craiglist_website
      inner join prd_product on fa_return_refund_craiglist_website.sku = prd_product.product_sku
      where the_month =  $Month and the_year = $Year and is_craiglist is null and sku is not null and sku <>''
      and sku not in (select sku from sal_selling_summary_monthlys where  the_month = $Month  and the_year =  $Year and sales_chanel = 8)
      group by sku,life_cycle ";
      DB::connection('mysql')->select($sql);
      // 9. FBA
      $sql= " insert into sal_selling_summary_monthlys(sku,	return_quantity,refund,	sales_chanel,life_cycle,the_month,the_year)
      Select sku , sum(quantity), -sum(product_sales),9,life_cycle,$Month, $Year from fa_amazon_idzo
      left join prd_product on fa_amazon_idzo.sku = prd_product.product_sku
      where  the_month = $Month and the_year = $Year and sku is not null and sku <>''
      and (type like '%REFUNDED%' or type like '%Refund%') and (fulfillment like '%Amazon%' or fulfillment like '%AMAZON%')
      and sku not in (select sku from sal_selling_summary_monthlys where  the_month = $Month  and the_year =  $Year and sales_chanel = 9)
      group by sku,life_cycle ";
      DB::connection('mysql')->select($sql);
      //10. FBM
      $sql= " insert into sal_selling_summary_monthlys(sku,	return_quantity,refund,	sales_chanel,life_cycle,the_month,the_year)
      Select sku , sum(quantity), -sum(product_sales),10,life_cycle,$Month, $Year from fa_amazon_idzo
      left join prd_product on fa_amazon_idzo.sku = prd_product.product_sku
      where  the_month = $Month and the_year = $Year and sku is not null and sku <>''
      and (type like '%REFUNDED%' or type like '%Refund%')  and (fulfillment like '%Seller%' or fulfillment like '%SELLER%')
      and sku not in (select sku from sal_selling_summary_monthlys where  the_month = $Month  and the_year =  $Year and sales_chanel = 10)
      group by sku,life_cycle ";
      DB::connection('mysql')->select($sql);

    //II. Bổ sung những sku không có phát sinh bán hàng nhưng có phát sinh MSF -. CHi có kênh FBA
      $sql= " insert into sal_selling_summary_monthlys(sku,	msf,sales_chanel,life_cycle,the_month,the_year)
      select sku , sum(fa_fba_monthly_store_fee.amount),9,life_cycle, $Month,$Year   from fa_fba_monthly_store_fee
      left join prd_product on fa_fba_monthly_store_fee.sku = prd_product.product_sku
      where the_month = $Month and the_year = $Year  and sku is not null and sku <>''
      and fa_fba_monthly_store_fee.sku not in
      ( select DISTINCT(sku) as sku from  sal_selling_summary_monthlys where  the_month = $Month
      and the_year = $Year  and sales_chanel = 9 ) group by sku,life_cycle ";
      DB::connection('mysql')->select($sql);

    //III. Bổ sung những sku không có phát sinh bán hàng nhưng có phát sinh DIP -> Chỉ có kênh FBA
    $sql= " insert into sal_selling_summary_monthlys(sku,	dip,sales_chanel,life_cycle,the_month,the_year)
    select sku , sum(fa_dip_monthly.dip),9,life_cycle, $Month, $Year  from fa_dip_monthly
    left join prd_product on fa_dip_monthly.sku = prd_product.product_sku
    where the_month = $Month and the_year = $Year and sku is not null and sku <>''
    and fa_dip_monthly.sku not in
    (  select DISTINCT(sku) as sku from  sal_selling_summary_monthlys   where  the_month = $Month
    and the_year = $Year  and sales_chanel = 9 ) group by sku,life_cycle ";
    DB::connection('mysql')->select($sql);

  }

// ============================================================================================================
private function AddSkuToSumTableNotAraisingSellButAraisingShipping($Month,$Year)
{
     //1. kênh FBM
     $sql = " insert into sal_selling_summary_monthlys(sku,shiping_fee,sales_chanel, the_month,the_year)
     select a.sku, sum(a.shipping_fee), 10, $Month , $Year  FROM
     (
     select sku, sum(shipping_fee) as shipping_fee from fa_amazon_idzo
     where the_month =  $Month and the_year = $Year  and shipping_fee > 0 and fulfillment like '%Seller%' group by sku
     union all
     select sku, sum(shipping_fee) from fa_shipping_record_from_memo
     where  the_month =  $Month  and the_year = $Year and shipping_fee >0  and sales_channel = 10 group by sku
     ) a  where a.sku not IN
     (select sku from sal_selling_summary_monthlys where the_month =  $Month   and the_year = $Year  and sales_chanel = 10 )
     group by a.sku " ;
     DB::connection('mysql')->select($sql);

     // 2. EBAY

      $sql = " insert into sal_selling_summary_monthlys(sku,shiping_fee,sales_chanel,store, the_month,the_year)
      select a.sku,a.shipping_fee, a.sales_channel, a.store, a.TheMonth, a.TheYear from
      (select sku, sum(shipping_fee) as shipping_fee,sales_channel,store, $Month as TheMonth , $Year  as TheYear
      from fa_shipping_record_from_memo
      where  the_month =  $Month  and the_year = $Year and shipping_fee >0 and sku is not null and sku <>''
      and sales_channel = 6  group by sku
      union all
      select sku, sum(shipping_fee),sales_channel,store, $Month as TheMonth , $Year  as TheYear
      from fa_selling_monthly_detail
      where  the_month =  $Month  and the_year = $Year and shipping_fee >0 and sku is not null and sku <>''
      and sales_channel = 6  group by sku
      )a where a.sku not IN
      (select sku from sal_selling_summary_monthlys where the_month =  $Month
      and the_year = $Year  and sales_chanel = 6 ) ";
      DB::connection('mysql')->select($sql);





    // WM DSV
    $sql = " insert into sal_selling_summary_monthlys(sku,shiping_fee,sales_chanel, the_month,the_year)
    select sku, sum(shipping_fee),4, $Month , $Year  from fa_shipping_record_from_memo
    where  the_month =  $Month  and the_year = $Year and shipping_fee >0 and sku is not null and sku <>''
    and sales_channel = 4
    and sku not IN
    ( select sku from sal_selling_summary_monthlys where the_month =  $Month
      and the_year = $Year  and sales_chanel = 4
    )
    group by sku ";
    DB::connection('mysql')->select($sql);

    // WM MKP
    $sql = " insert into sal_selling_summary_monthlys(sku,shiping_fee,sales_chanel, the_month,the_year)
    select sku, sum(shipping_fee),5, $Month , $Year  from fa_shipping_record_from_memo
    where  the_month =  $Month  and the_year = $Year and shipping_fee >0
    and sales_channel = 5
    and sku not IN
    (select sku from sal_selling_summary_monthlys where the_month =  $Month
     and the_year = $Year  and sales_chanel = 5
    )
    group by sku ";
    DB::connection('mysql')->select($sql);

    // Craiglist
    $sql = " insert into sal_selling_summary_monthlys(sku,shiping_fee,sales_chanel, the_month,the_year)
    select sku, sum(shipping_fee),7, $Month , $Year  from fa_shipping_record_from_memo
    where  the_month =  $Month  and the_year = $Year and shipping_fee >0
    and sales_channel = 7
    and sku not IN
    (select sku from sal_selling_summary_monthlys where the_month =  $Month
    and the_year = $Year  and sales_chanel = 7
    )
    group by sku ";
    DB::connection('mysql')->select($sql);

    // Website
    $sql = " insert into sal_selling_summary_monthlys(sku,shiping_fee,sales_chanel, the_month,the_year)
    select sku, sum(shipping_fee),8, $Month , $Year  from fa_shipping_record_from_memo
    where  the_month =  $Month  and the_year = $Year and shipping_fee >0
    and sales_channel = 8
    and sku not IN
    (select sku from sal_selling_summary_monthlys where the_month =  $Month
    and the_year = $Year  and sales_chanel = 8  )
    group by sku  ";
    DB::connection('mysql')->select($sql);

}
// ============================================================================================================
private function AddSkuToSumTableNotAraisingSellButAraisingShippingNew($Month,$Year)
{
     //1. kênh FBM
     $sql = " insert into sal_selling_summary_monthlys(sku,shiping_fee,sales_chanel, the_month,the_year)
      select sku, sum(shipping_fee),10,$Month,$Year from fa_shipping_record_from_memo
     where  the_month =  $Month  and the_year = $Year and shipping_fee >0  and sales_channel = 10
     and sku not IN
     (select sku from sal_selling_summary_monthlys where the_month =  $Month   and the_year = $Year  and sales_chanel = 10 )
     group by sku " ;
     DB::connection('mysql')->select($sql);

    // 2. EBAY
    for($Store =1; $Store <=4; $Store++)
    {
      $sql = " insert into sal_selling_summary_monthlys(sku,shiping_fee,sales_chanel,store, the_month,the_year)
      select sku, sum(shipping_fee) ,sales_channel,store, $Month , $Year
      from fa_shipping_record_from_memo
      where  the_month =  $Month  and the_year = $Year and shipping_fee >0 and sales_channel = 6 and store = $Store
      and sku not IN
      (select sku from sal_selling_summary_monthlys where the_month =  $Month and the_year = $Year  and sales_chanel = 6 and store = $Store )
      group by sku ";
      DB::connection('mysql')->select($sql);
    }

    // WM DSV
    $sql = " insert into sal_selling_summary_monthlys(sku,shiping_fee,sales_chanel, the_month,the_year)
    select sku, sum(shipping_fee),4, $Month , $Year  from fa_shipping_record_from_memo
    where  the_month =  $Month  and the_year = $Year and shipping_fee >0
    and sales_channel = 4
    and sku not IN
    ( select sku from sal_selling_summary_monthlys where the_month =  $Month
      and the_year = $Year  and sales_chanel = 4
    )
    group by sku ";
    DB::connection('mysql')->select($sql);

    // WM MKP
    $sql = " insert into sal_selling_summary_monthlys(sku,shiping_fee,sales_chanel, the_month,the_year)
    select sku, sum(shipping_fee),5, $Month , $Year  from fa_shipping_record_from_memo
    where  the_month =  $Month  and the_year = $Year and shipping_fee >0
    and sales_channel = 5
    and sku not IN
    (select sku from sal_selling_summary_monthlys where the_month =  $Month
     and the_year = $Year  and sales_chanel = 5
    )
    group by sku ";
    DB::connection('mysql')->select($sql);

    // Craiglist
    $sql = " insert into sal_selling_summary_monthlys(sku,shiping_fee,sales_chanel, the_month,the_year)
    select sku, sum(shipping_fee),7, $Month , $Year  from fa_shipping_record_from_memo
    where  the_month =  $Month  and the_year = $Year and shipping_fee >0
    and sales_channel = 7
    and sku not IN
    (select sku from sal_selling_summary_monthlys where the_month =  $Month
    and the_year = $Year  and sales_chanel = 7
    )
    group by sku ";
    DB::connection('mysql')->select($sql);

    // Website
    $sql = " insert into sal_selling_summary_monthlys(sku,shiping_fee,sales_chanel, the_month,the_year)
    select sku, sum(shipping_fee),8, $Month , $Year  from fa_shipping_record_from_memo
    where  the_month =  $Month  and the_year = $Year and shipping_fee >0
    and sales_channel = 8
    and sku not IN
    (select sku from sal_selling_summary_monthlys where the_month =  $Month
    and the_year = $Year  and sales_chanel = 8  )
    group by sku  ";
    DB::connection('mysql')->select($sql);

}
// ============================================================================================================
public function CaculateShipingFee($Month,$Year)
{//

  //ini_set('memory_limit','1548M');
  //set_time_limit(15000);

 // $Month = 6;
 // $Year = 2020;

  $SalesChannelID = 0;
  $StoreID = 0;
  // Xóa hết thông tin về chi phí vận chuyển được ghi từ memo trong l trong tháng
  $sql = " delete from fa_shipping_record_from_memo where the_month = $Month and the_year = $Year";
  DB::connection('mysql')->select($sql);
  // Xóa hết thông tin về chi phí vận chuyển tại bảng gốc khi import rồi tính chi phsi shipping điền vào cho các sku trogn mỗi transaction
  //có thể đã được tính ở những lần chạy trước trong cùng 1 tháng
  $sql = " update  fa_selling_monthly_detail set  shipping_fee = 0 where the_month = $Month and the_year = $Year";
  DB::connection('mysql')->select($sql);

  // Xóa hết thông tin về chi phí vận chuyển có thể đã được tính ở những lần chạy trước trong cùng 1 tháng
  $sql = " update  sal_selling_summary_monthlys set  shiping_fee = 0 where the_month = $Month and the_year = $Year";
  DB::connection('mysql')->select($sql);

  // Load toàn bộ transaction có phát sinh vận chuyển trong tháng
  $sql= " select transaction_id, store_name ,cost,memohai, memoba from  fa_shipment_realtime_reports
  where  the_month = $Month and the_year = $Year ";
  $i = 0;
  $ds = DB::connection('mysql')->select($sql);
  foreach($ds  as $d)
  {
    $i++;
    print_r('Dòng thứ: '. $i);
    print_r('<br>');

    $Channel  = $d->store_name;
    if($Channel == 'Walmart-DSV'|| $Channel =='Walmart-N/A')
    {
     $SalesChannelID = 4;
     $StoreID = 0;
    }
    elseif(strlen(strstr($Channel,'Amazon'))>0)
    {
      $SalesChannelID = 10;
      $StoreID = 0;
     }
    elseif(strlen(strstr($Channel,'Ebay'))>0)
    {
      $SalesChannelID = 6;
      {
        if(strlen(strstr($Channel,'Fitness'))>0){ $StoreID = 1;}
        elseif(strlen(strstr($Channel,'Inc'))>0){ $StoreID  = 2;}
        elseif(strlen(strstr($Channel,'Infideal'))>0){ $StoreID  = 3;}
        else{ $StoreID = 4;}
      }
    }
    elseif(strlen(strstr($Channel,'Yes4All'))>0)// website
    {
      $SalesChannelID = 8;
      $StoreID = 0;
    }
    elseif(strlen(strstr($Channel,'Walmart-MKP'))>0)// Walmart-MKP
    {
      $SalesChannelID = 5;
      $StoreID = 0;
    }


    $this->InsertShipingDetailFromMemo($d->transaction_id, $d->memohai, $d->memoba,$d->cost,$SalesChannelID,$StoreID , $Month,$Year );
  }
  // Đưa lên bảng tổng hợp những sku không có phát sinh bán hàng chỉ có phát sinh chi phí vận chuyển(Đưa sku và chi phí vận chuyển)
  $this->AddSkuToSumTableNotAraisingSellButAraisingShippingNew($Month,$Year);


  // Cập nhật lại chi phí vận chuyển cho từng sku trong tháng cho những sku chưa được tính phí vận chuyển
  $sql = " select id, sku,sales_chanel, store from sal_selling_summary_monthlys
  where  the_month = $Month and the_year = $Year  and sales_chanel in (4,5,6,8,10)";
  $ds = DB::connection('mysql')->select($sql);
  $i=0;
  foreach($ds  as $d)
  {
    $i++;
    print_r('Dòng thứ: '. $i);
    print_r('<br>');
    $ShippingFee = $this->GetShippingFee($d->sku,$d->sales_chanel,$d->store,$Month,$Year);
    print_r('sku:' .$d->sku. ' Channel '. $d->sales_chanel . ' Store '. $d->store. ' Shipping Fee ' .$ShippingFee );
    print_r('<br>');
    $sql = " update sal_selling_summary_monthlys set shiping_fee = GetShippingFee('$d->sku',$d->sales_chanel,$d->store,$Month,$Year) where id = $d->id ";
    DB::connection('mysql')->select($sql);
  }
  // chi phí  shiping các kênh còn lại =0
  $sql = " update sal_selling_summary_monthlys set shiping_fee = 0 where sales_chanel not in (4,5,6,8,10) and the_month = $Month and the_year = $Year  ";
  DB::connection('mysql')->select($sql);
  // Phân bổ lại từ con số tổng chi phí vận chuyển ở mỗi Store của kênh EBAY xuống sku
 // $this->AllocateShippingFeeFromChannelToSkuOnEbay($Month,$Year );

}
// ============================================================================================================
private function GetShippingFee($Sku,$SalesChannel,$Store,$Month,$Year )
{
  $ShippingFee =0;
  $sql = " select GetShippingFee('$Sku',$SalesChannel,$Store,$Month,$Year) as ShippingFee";
  $ds = DB::connection('mysql')->select($sql);
  foreach($ds as $d) { $ShippingFee = $d->ShippingFee; }
  return $ShippingFee;
}
// Phân bổ lại chi phí shipping dựa trên tổng số và số chi tiết đã tính từ transaction
private function AllocateShippingFeeFromChannelToSkuOnEbay($Month,$Year )
{
  $TotalShippingFee = 0;
  $TotalShippingFeeFromDT = 0;
  // Thực hiện phân bổ các kênh EBAY
  for($Store =1; $Store<=4;$Store++)
  {
    $sql = " select shipping_fee from fa_summary_chargeback_monthly
    where the_month = $Month and the_year = $Year and sales_channel = 6 and store = $Store";
    $ds = DB::connection('mysql')->select($sql);
    foreach($ds  as $d){ $TotalShippingFee = $d->shipping_fee;}

    $sql = " select sum(shiping_fee) as TotalShipingFee from sal_selling_summary_monthlys
    where the_month= $Month and the_year = $Year and sales_chanel = 6 and store = $Store ";
    $ds=  DB::connection('mysql')->select($sql);
    foreach($ds as $d) {  $TotalShippingFeeFromDT = $d->TotalShipingFee; }

    // Tính trung bình $EbayFinal trên một đơn vị net sales
    if($TotalShippingFeeFromDT >0 )
    {
      $ShipingFeeRate = $TotalShippingFee/ $TotalShippingFeeFromDT;
      // update lên bảng tổng hợp
      $sql = " update  sal_selling_summary_monthlys set shiping_fee = shiping_fee * $ShipingFeeRate
      where the_month = $Month and the_year = $Year and sales_chanel = 6 and store = $Store ";
      DB::connection('mysql')->select($sql);

      /*
      $sql = " select id from sal_selling_summary_monthlys
      where the_month= $Month and the_year = $Year and sales_chanel = 6 and andstore = $Store";
      $ds=  DB::connection('mysql')->select($sql);
      foreach($ds as $d)
      {
      $sql = " update sal_selling_summary_monthlys set shipping_fee = shipping_fee * $ShipingFeeRate where id= $d->id ";
      DB::connection('mysql')->select($sql);
      }
      */
    }
  }// For Store

  // Thực hiện hân bỏ chi phí shipping cho các kênh còn lại
  // Lấy tổng chi phí shipping của kênh EBAY
  $TotalEbayShippingFee = 0;
  $sql = " select sum(shipping_fee) as shipping_fee from fa_summary_chargeback_monthly
  where the_month = $Month and the_year = $Year and sales_channel = 6 ";
  $ds = DB::connection('mysql')->select($sql);
  foreach($ds  as $d){ $TotalEbayShippingFee = $d->shipping_fee;}

  // Lấy tổng chi phí shipping của tháng
  $TotalShippingFee = 0;
  $sql = " select shipping_fee from fa_summary_chargeback_monthly
  where the_month = $Month and the_year = $Year and sales_channel = 0 ";
  $ds = DB::connection('mysql')->select($sql);
  foreach($ds  as $d){ $TotalShippingFee = $d->shipping_fee;}

  $TotalShippingFeeRest = $TotalShippingFee - $TotalEbayShippingFee ;
}
// ============================================================================================================
private function UpdateShippingFee($Transaction, $Rate,$Month,$Year )
{
  $sql= " select id, sku, quantity from fa_selling_monthly_detail where  transaction_id = '". $Transaction."'";
  foreach($ds  as $d)
  {
    $sql= " update fa_selling_monthly_detail set shipping_fee = quantity * GetLastShippingCostTheo(sku) * $Rate
    where id = $d->id";
    DB::connection('mysql')->select($sql);
  }
  $sql= " select id, sku, quantity from fa_amazon_idzo where  order_number = '". $Transaction."'";
  foreach($ds  as $d)
  {
    $sql= " update fa_amazon_idzo set shipping_fee = quantity * GetLastShippingCostTheo(sku) * $Rate
    where id = $d->id";
    DB::connection('mysql')->select($sql);
  }
}
// ============================================================================================================
private function CaculateRateBetweenActShippingCostAndTheoCost($Transaction, $StoreName,$TotalActCost,$Month,$Year )
{
  $sql = " sum(a.quantity * GetLastShippingCostTheo(a.sku)) as TotalTheoShippingCost from (
  select sku, quantity from fa_amazon_idzo where the_month = $Month and the_year = $Year  and order_number  = '". $Transaction ."'".
  " UNION
  select sku, quantity  from fa_selling_monthly_detail where the_month = $Month and the_year = $Year and transaction_id  = '". $Transaction ."') " ;
  $ds = DB::connection('mysql')->select($sql);
  foreach($ds  as $d){$this->iif(is_null( $d->TotalTheoShippingCost),0, $d->TotalTheoShippingCost);}
  return $TotalActCost/$TotalTheoShippingCost;
}
// ============================================================================================================
private function CheckShippingTheoCost($Transaction)// Kiểm tra các sku trong transaction này có cái nào không có giá ước tính không
{
  $Result = true;
  $sql= " select  GetLastShippingCostTheo(a.sku) as Result from (
  select order_number as transaction, fa_amazon_idzo.sku from fa_amazon_idzo where the_month = $Month and the_year =$Year
  UNION
  select transaction_id as transaction ,fa_selling_monthly_detail.sku from fa_selling_monthly_detail where the_month =$Month and the_year = $Year
  ) where a.as transaction = '". $Transaction ."'";

  $ds = DB::connection('mysql')->select($sql);
  foreach($ds  as $d)
  {
    if($d->Result == 0) {$Result = false;}
  }
}
// ============================================================================================================
private function FindSellingTransationtionInMonthYear($Transaction,$Month,$Year )
{
  $sql= " select FindSellingTransaction('". $Transaction . "," .$Month ."," . $Year .") as Result";
  $ds = DB::connection('mysql')->select($sql);
  foreach($ds  as $d)  { return  $d->Result;}
}
// ============================================================================================================
  public function CaculateShipingFeeNew($Month,$Year )
  {
    //ini_set('memory_limit','2048M');
    //set_time_limit(1600);
    // Xóa hết thông tin về chi phí vận chuyển từ việc l trong tháng
    $sql = " delete from fa_shipping_record_from_memo where the_month =  $Month and the_year = $Year";
    DB::connection('mysql')->select($sql);

    // Xóa hết thông tin về chi phí vận chuyển tại bảng gốc khi import rồi tính chi phsi shipping điền vào cho các sku trong mỗi transaction
    //có thể đã được tính ở những lần chạy trước trong cùng 1 tháng
    $sql = " update  fa_selling_monthly_detail set  shipping_fee = 0 where the_month = $Month and the_year = $Year";
    DB::connection('mysql')->select($sql);

    // Load toàn bộ transaction vận chuyển trong tháng
    $sql= " select transaction_id, store_name as Channel,sum(cost) as TotalCost  from  fa_shipment_realtime_reports
    where  status like '%Shipped%'  and the_month = $Month and the_year = $Year
    group by transaction_id, store_name ";

    // and store_name like '%Amazon%' and transaction_id ='401929082262-851448985027''
    $ds = DB::connection('mysql')->select($sql);

    foreach($ds  as $d)
    {
      $this->UpdateShipingFee($d->transaction_id,$d->Channel,$d->TotalCost,$Month,$Year);
    }
    $this->AddSkuToSumTableNotAraisingSellButAraisingShipping($Month,$Year);

    // chuyển data shipping fee lên bảng tổng hợp
    $sql = " select id, sku,sales_chanel, store from sal_selling_summary_monthlys
    where  the_month = $Month and the_year = $Year and sales_chanel in (4,5,6,7,8,10)";
    $ds = DB::connection('mysql')->select($sql);
    foreach($ds  as $d)
    {
      $sql = " update sal_selling_summary_monthlys set shiping_fee = GetShippingFee('". $d->sku . "',". " $d->sales_chanel,$d->store,$Month,$Year) where id = $d->id ";
      DB::connection('mysql')->select($sql);
    }
    // chi phí  shiping các kênh còn lại =0 lưu ý not in trong câu sql
    $sql = " update sal_selling_summary_monthlys set shiping_fee = 0 where sales_chanel not in (4,5,6,7,8,10) and the_month = $Month and the_year = $Year  ";
    DB::connection('mysql')->select($sql);

    // Phân bổ lại chi phí shipping kênh ebay
    $this->AllocateShippingFeeFromChannelToSkuOnEbay($Month,$Year);

  }
  // ============================================================================================================
  private function UpdateShipingFee($TransactionID,$Channel,$TotalActualShippingCost,$TheMonth,$TheYear)
  {
    $TotalExpectShippingCost = 0;
    if($Channel == 'Walmart-DSV'|| $Channel =='Walmart-N/A')
      {
        // Lấy tổng số chi phí vận chuyển theo lý thuyết đối với $TransactionID
        $sql= " select sum(quantity * GetLastShippingCostTheo(sku)) as TotalExpectShippingCost
        from fa_selling_monthly_detail where the_month = $TheMonth and the_year = $TheYear
        and quantity >0  and  po_id = '". $TransactionID ."' and sales_channel = 4 ";

        $ds = DB::connection('mysql')->select($sql);
        foreach($ds  as $d){$TotalExpectShippingCost = $d->TotalExpectShippingCost; }

        // Tìm thấy Transaction -> Tỷ lệ chi phí vận chuyển thực tế trên chi phí lý thuyết
        if($TotalExpectShippingCost > 0)
          {
            $Rate = $TotalActualShippingCost/$TotalExpectShippingCost;
            // Phân bổ tổng tiền chi phí shipping thực tế cho những sku tham gia trong transaction
            // theo số lượng thực tế, tổng chi phí shipping thực tế và chi phí shipping lý thuyết
            $sql = " select id, sku,  fa_selling_monthly_detail.quantity * GetLastShippingCostTheo(sku) * $Rate as ShippingFee
            from fa_selling_monthly_detail where the_month = $TheMonth and the_year = $TheYear
            and fa_selling_monthly_detail.quantity > 0
            and fa_selling_monthly_detail.po_id = '". $TransactionID ."' and sales_channel = 4 ";

            $ds = DB::connection('mysql')->select($sql);
            foreach($ds  as $d)
            {
              $sql = " update fa_selling_monthly_detail set shipping_fee = $d->ShippingFee where  id = $d->id ";
              DB::connection('mysql')->select($sql);
            }//if($TotalExpectShippingCost > 0)
          }
          else// không tìm thấy transaction thì tìm qua memo
          {
            // Với mỗi Transaction ID của kênh WM DSV xác định những sku liên quan thông qua memo
            $sql = " select  memohai, memoba  from fa_shipment_realtime_reports
            where(store_name like '%Walmart-DSV%' or store_name like '%Walmart-N/A%' ) and transaction_id ='". $TransactionID ."'";

            $ds=  DB::connection('mysql')->select($sql);
            foreach($ds  as $d){ $this->InsertShipingDetailFromMemo($TransactionID, $d->memohai, $d->memoba,$TotalActualShippingCost,4,0, $TheMonth , $TheYear );}
          }
      }
    elseif(strlen(strstr($Channel,'Amazon'))>0)
      {
        // Lấy tổng số chi phí vận chuyển lý thuyết
        $sql = " select sum(quantity * GetLastShippingCostTheo(sku)) as TotalExpectShippingCost
        from fa_amazon_idzo   where the_month = $TheMonth and the_year = $TheYear
        and quantity > 0   and  fa_amazon_idzo.order_number = '". $TransactionID ."'" ;

        $ds = DB::connection('mysql')->select($sql);
        foreach($ds  as $d){ $TotalExpectShippingCost = $this->iif(is_null($d->TotalExpectShippingCost),0, $d->TotalExpectShippingCost); }

        if($TotalExpectShippingCost > 0)
        {
          // tính tỷ lệ giữa thực tế và lý thuyết
         // print_r('Tìm thấy transaction');
          //print_r('<br>');
          $Rate = $TotalActualShippingCost/$TotalExpectShippingCost;
          // Phân bổ tổng tiền chi phí shipping thực tế cho sku theo số lượng và chi phí shipping lý thuyết
          $sql = " select fa_amazon_idzo.id,
          (fa_amazon_idzo.quantity *  GetLastShippingCostTheo(sku) *  $Rate) as ShippingFee from fa_amazon_idzo
          where  the_month = $TheMonth and the_year = $TheYear
          and fa_amazon_idzo.quantity  > 0  and  fa_amazon_idzo.order_number = '". $TransactionID ."'".
          " and fa_amazon_idzo.fulfillment ='Seller'";

          $ds = DB::connection('mysql')->select($sql);
          foreach($ds  as $d)
          {
            $sql = " update fa_amazon_idzo set shipping_fee = $d->ShippingFee where  id = $d->id ";
            DB::connection('mysql')->select($sql);
          }
        }
        else// không tìm thấy transaction thì tìm qua memo
        {
         // print_r('Không tìm thấy transaction:'.  $TransactionID);
         // print_r('<br>');
          $sql = " select  memohai,memoba  from fa_shipment_realtime_reports
          where  the_month = $TheMonth and the_year = $TheYear
          and store_name like '%Amazon%'  and transaction_id ='". $TransactionID ."'";

          $ds=  DB::connection('mysql')->select($sql);
          foreach($ds  as $d){ $this->InsertShipingDetailFromMemo($TransactionID, $d->memohai, $d->memoba,$TotalActualShippingCost,10,0, $TheMonth , $TheYear );}
        }
      }
    elseif(strlen(strstr($Channel,'Ebay'))>0)
      {
        if(strlen(strstr($Channel,'Fitness'))>0){ $Store = 1;}
        elseif(strlen(strstr($Channel,'inc'))>0){$Store = 2;}
        elseif(strlen(strstr($Channel,'Infideals'))>0){$Store = 3;}
        else{$Store = 4;}

        // Lấy tổng số chi phí vận chuyển theo lý thuyết đối với $TransactionID
        $sql= " select sum(quantity *  GetLastShippingCostTheo(sku)) as TotalExpectShippingCost
        from fa_selling_monthly_detail
        where the_month = $TheMonth and the_year = $TheYear and quantity > 0
        and  fa_selling_monthly_detail.transaction_id = '". $TransactionID ."' and fa_selling_monthly_detail.sales_channel = 6 ";


        $ds = DB::connection('mysql')->select($sql);
        foreach($ds  as $d){$TotalExpectShippingCost = $this->iif(is_null($d->TotalExpectShippingCost),0,$d->TotalExpectShippingCost ); }

        // Tỷ lệ chi phí vận chuyển thực tế trên chi phí lý thuyết
        if($TotalExpectShippingCost > 0)
        {
          $Rate = $TotalActualShippingCost/$TotalExpectShippingCost;
          // Phân bổ tổng tiền chi phí shipping thực tế cho những sku tham gia trong transaction
          // theo số lượng thực tế, tổng chi phí shipping thực tế và chi phí shipping lý thuyết
          $sql = " select fa_selling_monthly_detail.id, fa_selling_monthly_detail.sku,
          fa_selling_monthly_detail.quantity * GetLastShippingCostTheo(sku) * $Rate as ShippingFee
          from fa_selling_monthly_detail
          where  the_month = $TheMonth and the_year = $TheYear
          and fa_selling_monthly_detail.quantity > 0
          and fa_selling_monthly_detail.transaction_id = '". $TransactionID ."' and sales_channel = 6 ";

          $ds = DB::connection('mysql')->select($sql);
          foreach($ds  as $d)
          {
            $sql = " update fa_selling_monthly_detail set shipping_fee = $d->ShippingFee where  id = $d->id ";
            DB::connection('mysql')->select($sql);
          }//if($TotalExpectShippingCost > 0)
        }
        else// không tìm thấy transaction thì tìm qua memo
        {
          // Với mỗi Transaction ID của kênh FBM xác định những sku liên quan thông qua memo
          $sql = " select  memohai,memoba  from fa_shipment_realtime_reports
          where  the_month = $TheMonth and the_year = $TheYear and transaction_id ='". $TransactionID ."'";

          $ds=  DB::connection('mysql')->select($sql);
          foreach($ds  as $d){ $this->InsertShipingDetailFromMemo($TransactionID, $d->memohai, $d->memoba,$TotalActualShippingCost,6, $Store, $TheMonth , $TheYear );}
        }
      }
      elseif(strlen(strstr($Channel,'Yes4All'))>0)// website
      {
        // Lấy tổng số chi phí vận chuyển theo lý thuyết đối với $TransactionID
        $sql= " select sum(fa_selling_monthly_detail.quantity *  GetLastShippingCostTheo(sku) ) as TotalExpectShippingCost
        from fa_selling_monthly_detail
        where   the_month = $TheMonth and the_year = $TheYear
        and fa_selling_monthly_detail.transaction_id = '". $TransactionID ."' and fa_selling_monthly_detail.sales_channel = 8 ";

        $ds = DB::connection('mysql')->select($sql);
        foreach($ds  as $d){$TotalExpectShippingCost = $this->iif(is_null($d->TotalExpectShippingCost),0, $d->TotalExpectShippingCost); }

        // Tỷ lệ chi phí vận chuyển thực tế trên chi phí lý thuyết
        if($TotalExpectShippingCost > 0)
          {
            $Rate = $TotalActualShippingCost/$TotalExpectShippingCost;
            // Phân bổ tổng tiền chi phí shipping thực tế cho những sku tham gia trong transaction
            // theo số lượng thực tế, tổng chi phí shipping thực tế và chi phí shipping lý thuyết
            $sql = " select fa_selling_monthly_detail.id, fa_selling_monthly_detail.sku,
            fa_selling_monthly_detail.quantity * GetLastShippingCostTheo(sku) * $Rate as ShippingFee
            from fa_selling_monthly_detail
            where the_month = $TheMonth and the_year = $TheYear
            and fa_selling_monthly_detail.transaction_id = '". $TransactionID ."' and sales_channel = 8 ";

            $ds = DB::connection('mysql')->select($sql);
            foreach($ds  as $d)
            {
              $sql = " update fa_selling_monthly_detail set shipping_fee = $d->ShippingFee where  id = $d->id ";
              DB::connection('mysql')->select($sql);
            }//if($TotalExpectShippingCost > 0)
          }
          else// không tìm thấy transaction thì tìm qua memo
          {
           // Với mỗi Transaction ID của kênh website xác định những sku liên quan thông qua memo
            $sql = " select  memohai , memoba from fa_shipment_realtime_reports
            where   the_month = $TheMonth and the_year = $TheYear and transaction_id ='". $TransactionID ."'";

            $ds=  DB::connection('mysql')->select($sql);
            foreach($ds  as $d){ $this->InsertShipingDetailFromMemo($TransactionID, $d->memohai, $d->memoba,$TotalActualShippingCost,8,0, $TheMonth , $TheYear );}
          }
      }
      elseif(strlen(strstr($Channel,'Walmart-MKP'))>0)// Walmart-MKP
      {
        // Với mỗi Transaction ID của kênh WM MKP xác định những sku liên quan thông qua memo
        $sql = " select  memohai,memoba  from fa_shipment_realtime_reports where the_month =  $TheMonth and the_year = $TheYear
        and store_name like '%Walmart-MKP%'  and transaction_id ='". $TransactionID ."'";

        $ds=  DB::connection('mysql')->select($sql);
        foreach($ds  as $d)
        {
          $this->InsertShipingDetailFromMemo($TransactionID, $d->memohai, $d->memoba,$TotalActualShippingCost,5,0, $TheMonth , $TheYear );
        }
      }
  }
   // ============================================================================================================
   // ============================================================================================================
  public function AddSkuNotSellingButHasShippingFee($TransactionID,$TheMonth, $TheYear, $Channel)
  {
    $sSku ='';
    if($Channel == 'Walmart-DSV'|| $Channel =='Walmart-N/A' || strlen(strstr($Channel,'Ebay'))>0 || strlen(strstr($Channel,'Yes4All'))>0)
    {
     $sql = " select sku, quantity from fa_selling_monthly_detail where fa_selling_monthly_detail.quantity > 0
     and fa_selling_monthly_detail.transaction_id = '". $TransactionID ."' ";
    }
    else
    {
      $sql = " select sku from fa_amazon_idzo   where  quantity > 0   and  fa_amazon_idzo.order_number = '". $TransactionID ."'";
    }
    $ds = DB::connection('mysql')->select($sql);
    foreach($ds  as $d){$sSku = $sSku  . ','. $d ; }

    if(strlen($sSku)>1)
    {
      $sSku = "('" . $sSku . ")";
    }

   // if($Channel == 'Walmart-DSV'|| $Channel =='Walmart-N/A')

  }
  // ============================================================================================================
 private function UpdateShipingFeeNew($TransactionID,$Channel,$TotalActualShippingCost,$TheMonth,$TheYear)
 {
   $TotalExpectShippingCost = 0;
   if($Channel == 'Walmart-DSV'|| $Channel =='Walmart-N/A' || strlen(strstr($Channel,'Ebay'))>0 || strlen(strstr($Channel,'Yes4All'))>0)
     {
       // Lấy tổng số chi phí vận chuyển theo lý thuyết đối với $TransactionID
       $sql= " select sum(quantity * GetLastShippingCostTheo(sku)) as TotalExpectShippingCost
       from fa_selling_monthly_detail where  quantity >0  and  po_id = '". $TransactionID ."' ";

       $ds = DB::connection('mysql')->select($sql);
       foreach($ds  as $d){$TotalExpectShippingCost = $d->TotalExpectShippingCost; }
       // Tìm thấy Transaction -> Tỷ lệ chi phí vận chuyển thực tế trên chi phí lý thuyết
       if($TotalExpectShippingCost > 0)
         {
          // Kiểm tra xem các sku trong Transaction có trong fa_selling_monthly_detail chưa nếu chưa có sku nào thì thêm sku đó
          $this->AddSkuNotSellingButHasShippingFee($TransactionID,$TheMonth, $TheYear, $Channel);
          $Rate = $TotalActualShippingCost/$TotalExpectShippingCost;
           // Phân bổ tổng tiền chi phí shipping thực tế cho những sku tham gia trong transaction
           // theo số lượng thực tế, tổng chi phí shipping thực tế và chi phí shipping lý thuyết
           $sql = " select id, sku,  fa_selling_monthly_detail.quantity * GetLastShippingCostTheo(sku) * $Rate as ShippingFee
           from fa_selling_monthly_detail where fa_selling_monthly_detail.quantity > 0
           and fa_selling_monthly_detail.transaction_id = '". $TransactionID ."' ";

           $ds = DB::connection('mysql')->select($sql);
           foreach($ds  as $d)
           {
           $sql = " update fa_selling_monthly_detail set shipping_fee = $d->ShippingFee where  id = $d->id ";
           DB::connection('mysql')->select($sql);
           }//if($TotalExpectShippingCost > 0)
         }
         else// không tìm thấy transaction thì tìm qua memo
         {
           // Với mỗi Transaction ID của kênh WM DSV xác định những sku liên quan thông qua memo
           $sql = " select  memohai, memoba  from fa_shipment_realtime_reports
           where(store_name like '%Walmart-DSV%' or store_name like '%Walmart-N/A%' ) and transaction_id ='". $TransactionID ."'";

           $ds=  DB::connection('mysql')->select($sql);
           foreach($ds  as $d){ $this->InsertShipingDetailFromMemo($TransactionID, $d->memohai, $d->memoba,$TotalActualShippingCost,4,0, $TheMonth , $TheYear );}
         }
     }
   elseif(strlen(strstr($Channel,'Amazon'))>0)
     {
       // Lấy tổng số chi phí vận chuyển lý thuyết
       $sql = " select sum(quantity * GetLastShippingCostTheo(sku)) as TotalExpectShippingCost
       from fa_amazon_idzo   where  quantity > 0   and  fa_amazon_idzo.order_number = '". $TransactionID ."'" ;

       $ds = DB::connection('mysql')->select($sql);
       foreach($ds  as $d){ $TotalExpectShippingCost = $this->iif(is_null($d->TotalExpectShippingCost),0, $d->TotalExpectShippingCost); }

       if($TotalExpectShippingCost > 0)
       {
         // tính tỷ lệ giữa thực tế và lý thuyết
         // print_r('Tìm thấy transaction');
         //print_r('<br>');
          // Kiểm tra xem các sku trong Transaction có trong fa_amazon_idzo chưa nếu chưa có sku nào thì thêm sku đó
         $this->AddSkuNotSellingButHasShippingFee($TransactionID,$TheMonth, $TheYear, $Channel);
         $Rate = $TotalActualShippingCost/$TotalExpectShippingCost;
         // Phân bổ tổng tiền chi phí shipping thực tế cho sku theo số lượng và chi phí shipping lý thuyết
         $sql = " select fa_amazon_idzo.id,
         (fa_amazon_idzo.quantity *  GetLastShippingCostTheo(sku) *  $Rate) as ShippingFee from fa_amazon_idzo
         where  fa_amazon_idzo.quantity  > 0  and  fa_amazon_idzo.order_number = '". $TransactionID ."'".
         " and fa_amazon_idzo.fulfillment ='Seller'";

         $ds = DB::connection('mysql')->select($sql);
         foreach($ds  as $d)
         {
           $sql = " update fa_amazon_idzo set shipping_fee = $d->ShippingFee where  id = $d->id ";
           DB::connection('mysql')->select($sql);
         }
       }
       else// không tìm thấy transaction thì tìm qua memo
       {
        // print_r('Không tìm thấy transaction:'.  $TransactionID);
        // print_r('<br>');
         $sql = " select  memohai,memoba  from fa_shipment_realtime_reports
         where  the_month = $TheMonth and the_year = $TheYear
         and store_name like '%Amazon%'  and transaction_id ='". $TransactionID ."'";

         $ds=  DB::connection('mysql')->select($sql);
         foreach($ds  as $d){ $this->InsertShipingDetailFromMemo($TransactionID, $d->memohai, $d->memoba,$TotalActualShippingCost,10,0, $TheMonth , $TheYear );}
       }
     }
     elseif(strlen(strstr($Channel,'Walmart-MKP'))>0)// Walmart-MKP
     {
       // Với mỗi Transaction ID của kênh WM MKP xác định những sku liên quan thông qua memo
       $sql = " select  memohai,memoba  from fa_shipment_realtime_reports where the_month =  $TheMonth and the_year = $TheYear
       and store_name like '%Walmart-MKP%'  and transaction_id ='". $TransactionID ."'";
       $ds=  DB::connection('mysql')->select($sql);
       foreach($ds  as $d)
       {
         $this->InsertShipingDetailFromMemo($TransactionID, $d->memohai, $d->memoba,$TotalActualShippingCost,5,0, $TheMonth , $TheYear );
       }
     }
 }
// ============================================================================================================
// Tìm xem có ký tự từ a đến z ngoại trừ x hay không
// Nếu có thì memo này được coi là không chứa sku
public function CheckExistingSkuInMemo($Memo)
  {//$Memo
  //$Memo ='4x Weight - 1.15in - 5lb of DB - 200 lbs - Package 3';
  //$Memo ='CKT4(AYGD) x1';
  $Flag = 0;
  $strTemp ='qwertyuiopasdfghjklzcvbnm';
  $Character='';

  $Memo = str_replace(' ','', $Memo);
  //print_r('$Memo: '. $Memo );


  $Mycount= strlen($Memo);
  $i=0;
  while($i < $Mycount && $Flag == false)
  {
    $Character = substr($Memo,$i,1);
    $pos = strpos($strTemp, $Character);
    if ($pos !== false) { $Flag = 1;}
    $i++;
  }
  return $Flag;
 }


  // ============================================================================================================
  public function InsertShipingDetailFromMemo($TransactionID, $Memo,$MemoBa, $TotalActualShippingCost,$SaleChannel,$Store, $Month , $Year)
  {//$TransactionID, $Memo,$MemoBa, $TotalActualShippingCost,$SaleChannel,$Store, $Month , $Year
/*
    $TransactionID='111-2123602-1091445';
    $Memo ='CKT4(AYGD) x1' ;
    $MemoBa='GL-29-8';
    $TotalActualShippingCost =13;
    $SaleChannel = 10;
    $Store =0;
    $Month =5;
    $Year=2020;
*/
    // print_r('Memo: ' . $Memo );
    // print_r('<br>' );
    // print_r('Memo ba: ' . $MemoBa );
    // print_r('<br>' );

    $HasOneSKU = true; // Defaul coi như 1 tracking id chỉ có 1 sku


    if($this->CheckExistingSkuInMemo($Memo)==1) {  $Memo = $MemoBa; }

    // bỏ chữ PART. nếu có
    $pos = strpos($Memo, 'PART.');
    if ($pos !== false) {
      $Memo =  str_replace('PART.', '', $Memo );
    }

    $pos = strpos($Memo, 'PART');
    if ($pos !== false) {
      $Memo =  str_replace('PART', '', $Memo );
    }

    if(strlen( $Memo)>0 )// nếu còn ký tự
    {
      // Loại bỏ cặp dấu ngoặc và ký tự bên trong nếu có
      while(strpos($Memo, '(')) { $Memo = $this->RemoveReplaceSKU($Memo); }
    }

    if(strlen( $Memo)>0 )// nếu còn ký tự
    {
      // Đếm số sku
      $t = $Memo;
      $Count = 1;

      //print_r('Chuoi T:  '. $t);
     // print_r('<br>');


      while(strpos($t, '+'))
      {
        $t = $this->CountLetterInString('+',$t);
        $Count++;
      }

      if( $Count == 1)// Chỉ có một sku
      {
        if(strlen(trim( $Memo))<= 4 )// Không có điền số lượng -> tự đồng gán = 1
        {
          $sku =  $Memo;
          $Quantity = 1;

          $sql = " insert into fa_shipping_record_from_memo(transaction_id,sku,quantity,sales_channel,store,shipping_fee,the_month,the_year)
          values('$TransactionID','$sku',$Quantity,$SaleChannel,$Store,$TotalActualShippingCost,$Month, $Year )";
          DB::connection('mysql')->select($sql);

          // print_r('sql: ' . $sql);
          // print_r('<br>' );

        }
        else// Có điền số lượng
        {
          if (strpos($Memo, 'x') !== false)
          {
            $dataSKUAndQuantity = explode('x',$Memo);
            $sku = $dataSKUAndQuantity[0];
            $Quantity = $dataSKUAndQuantity[1];

            $sql = " insert into fa_shipping_record_from_memo(transaction_id,sku,quantity,sales_channel,store,shipping_fee,the_month,the_year)
             values('$TransactionID','$sku',$Quantity,$SaleChannel, $Store,$TotalActualShippingCost,$Month, $Year )";
            DB::connection('mysql')->select($sql);

            // print_r('sql: ' . $sql);
            // print_r('<br>' );
          }
        }
      }
      else// Có nhiều hơn một sku ~ $Count >1
      {
       $HasOneSKU = false;
       $data = explode('+', $Memo,$Count);
       for($i = 0;$i < $Count; $i++)
        {
          if (strpos($data[$i], 'x') !== false)
          {
           $dataSKUAndQuantity = explode('x', $data[$i]);
           $sku = $dataSKUAndQuantity[0];
           $Quantity = $dataSKUAndQuantity[1];

           $sql = " insert into fa_shipping_record_from_memo(transaction_id,sku,quantity,sales_channel,store, the_month,the_year)
            values('$TransactionID','$sku',$Quantity,$SaleChannel,$Store,$Month , $Year)" ;
           DB::connection('mysql')->select($sql);

          //  print_r('sql: ' . $sql);
          //  print_r('<br>' );
          }
        }
      }


      if(!$HasOneSKU )
      {
        // Lấy tổng giá vận chuyển theo lý thuyết
        $sql = " select sum(quantity * GetLastShippingCostTheo(sku)) as TotalTheoryShippingFee
        from fa_shipping_record_from_memo where  transaction_id = '". $TransactionID ."'";
        $ds = DB::connection('mysql')->select($sql);
        foreach($ds  as $d)
        {  if($d->TotalTheoryShippingFee>0)
          {
            $Rate =  $TotalActualShippingCost / $d->TotalTheoryShippingFee ;
          }
          else
          {
            $Rate =1;
            print_r('Không tìm được giá lý thuyết, TransactionID: '. $TransactionID);
            print_r('<br>');
          }
        }
        // tính shipping fee cho từng sku trong mỗi transaction
        $sql = " select id, sku, quantity from fa_shipping_record_from_memo
        where the_month = $Month and the_year = $Year and transaction_id = '". $TransactionID ."'";
        $ds = DB::connection('mysql')->select($sql);
        foreach($ds  as $d)
        {
          $sql = " update fa_shipping_record_from_memo set shipping_fee = $d->quantity *  GetLastShippingCostTheo(sku) * $Rate  where id = $d->id ";
          DB::connection('mysql')->select($sql);
        }
      }//!$HasOneSKU )
    }// end if strlen <=0
  }
   // ============================================================================================================

   public function CountLetterInString($Letter, $String)
   {//$Letter, $String
    $StartPost = 0;
    $EndPost = strpos($String, $Letter, 0);
    $sTemp = substr($String,$StartPost,$EndPost+1);
    $String =  str_replace($sTemp, '', $String );
    return  $String ;
   }
  // ============================================================================================================
   private function RemoveReplaceSKU($Memo)
   {
    $StartPost = strpos($Memo, '(', 0);
    $EndPost = strpos($Memo, ')', 0);
    $sTemp = substr($Memo,$StartPost,$EndPost - $StartPost + 1);
    $Memo =  str_replace($sTemp, '', $Memo );
    return  $Memo ;
   }

  // ============================================================================================================
  // Update Chargeback,FreightHandlingReturnCost,OtherFee cho từng sku trong một tháng cho những kênh có 1 trong
  // 3 giá trị này từ việc từ việc phân bổ con số tổng cho netsales

  private function UpdateChargebackFreightHandlingReturnCostOtherFee($SalesChannel,$Month,$Year){
    $fTotalChargeback = 0;
    $fTotalNetSale = 0;

    $sql = " select sum(chargeback_fee) as chargeback_fee from fa_summary_chargeback_monthly where sales_channel = $SalesChannel
    and the_month = $Month and the_year = $Year ";
    $TotalChargebacks = DB::connection('mysql')->select( $sql);
    foreach($TotalChargebacks  as $TotalChargeback ){
      $fTotalChargeback  =  $this->iif(is_null($TotalChargeback->chargeback_fee),0, $TotalChargeback->chargeback_fee);
    }

    $TotalFreightHandlingReturnCost = 0;
    $TotalOtherFee = 0;
    $sql = " select sum(freight_handling_return_cost) as cost, sum(other_fee) as other_fee from fa_summary_chargeback_monthly
    where sales_channel = $SalesChannel  and the_month = $Month and the_year = $Year ";
    $ds = DB::connection('mysql')->select( $sql);
    foreach($ds  as $d ){
      $TotalFreightHandlingReturnCost =  $this->iif(is_null($d->cost),0, $d->cost);
      $TotalOtherFee =  $this->iif(is_null($d->other_fee),0, $d->other_fee);
    }

    // -> FA
    $sql = " select sum(nest_sales)as nest_sales from  sal_selling_summary_monthlys
    where sales_chanel = $SalesChannel   and the_month = $Month and 	the_year = $Year and nest_sales > 0 and by_invoice = 0 ";
    $TotalNetSales = DB::connection('mysql')->select($sql);
    foreach($TotalNetSales  as $TotalNetSale ){
      $fTotalNetSale = $this->iif(is_null( $TotalNetSale->nest_sales),0, $TotalNetSale->nest_sales);
    }
    $ChargebackFeePerNet=0;
    $FreightHandlingReturnCostPerNet=0;
    $OtherFeePerNet=0;
    if($fTotalNetSale>0){
      $ChargebackFeePerNet =  $fTotalChargeback/$fTotalNetSale;
      $FreightHandlingReturnCostPerNet = $TotalFreightHandlingReturnCost / $fTotalNetSale;
      $OtherFeePerNet= $TotalOtherFee / $fTotalNetSale;
      $sql = " Select id, sku, nest_sales from sal_selling_summary_monthlys where sales_chanel = $SalesChannel
      and the_month = $Month and 	the_year = $Year and nest_sales >0 ";
      $ids = DB::connection('mysql')->select($sql);
      foreach($ids  as $id )
      {
        $Chargeback = $ChargebackFeePerNet * $id->nest_sales;
        $FreightHandlingReturnCost = $FreightHandlingReturnCostPerNet * $id->nest_sales;
        $OtherFee = $OtherFeePerNet * $id->nest_sales;
        $sql = " update sal_selling_summary_monthlys set chargeback =  $Chargeback, freight_handling_return_cost	=  $FreightHandlingReturnCost,
        other_selling_expensives = $OtherFee where id = $id->id ";

        DB::connection('mysql')->select($sql);
      }
    }
    // -> AC
    if($SalesChannel == 1 || $SalesChannel == 2 || $SalesChannel == 3)
    {
      $sql = " select sum(nest_sales)as nest_sales from  sal_selling_summary_monthlys
      where sales_chanel = $SalesChannel   and the_month = $Month and 	the_year = $Year and nest_sales > 0 and by_invoice = 1 ";
      $TotalNetSales = DB::connection('mysql')->select($sql);
      foreach($TotalNetSales  as $TotalNetSale ){
        $fTotalNetSale = $this->iif(is_null( $TotalNetSale->nest_sales),0, $TotalNetSale->nest_sales);
      }
      $ChargebackFeePerNet=0;
      $FreightHandlingReturnCostPerNet=0;
      $OtherFeePerNet=0;
      if($fTotalNetSale>0){
        $ChargebackFeePerNet =  $fTotalChargeback/$fTotalNetSale;
        $FreightHandlingReturnCostPerNet = $TotalFreightHandlingReturnCost / $fTotalNetSale;
        $OtherFeePerNet= $TotalOtherFee / $fTotalNetSale;
        $sql = " Select id, sku, nest_sales from sal_selling_summary_monthlys
        where sales_chanel = $SalesChannel  and the_month = $Month and 	the_year = $Year and nest_sales >0 and  by_invoice = 1";
        $ids = DB::connection('mysql')->select($sql);
        foreach($ids  as $id )
        {
          $Chargeback = $ChargebackFeePerNet * $id->nest_sales;
          $FreightHandlingReturnCost = $FreightHandlingReturnCostPerNet * $id->nest_sales;
          $OtherFee = $OtherFeePerNet * $id->nest_sales;
          $sql = " update sal_selling_summary_monthlys set chargeback =  $Chargeback, freight_handling_return_cost	=  $FreightHandlingReturnCost,
          other_selling_expensives = $OtherFee where id = $id->id ";

          DB::connection('mysql')->select($sql);
        }
      }//End if ($SalesChannel == 1 || $SalesChannel == 3)
    }
  }

  // ============================================================================================================
  private function GetReturnRefund($sku,$SalesChannel,$Month,$Year){
    $sql='';
    switch ($SalesChannel){
          case 1: // AVC-WH
            $sql = " Select sum(quantity) as quantity, sum(amount) as amount from fa_avc_returns where sku ='$sku'
             and the_month = $Month and the_year = $Year  and vendor_code like '%YES4A%'";
            break;
          case 2: // AVC-DS
            $sql = "  Select sum(quantity) as quantity, sum(amount) as amount from fa_avc_returns where sku = " . "'" . $sku . "'".
            " and the_month = $Month and the_year = $Year  and vendor_code like '%AUYAD%'";
            break;
          case 3: // AVC-DI
            $sql = "  Select sum(quantity) as quantity, sum(amount) as amount from fa_avc_returns where sku = " . "'" . $sku . "'".
            " and the_month = $Month and the_year = $Year  and (vendor_code like '%YES4U%' or vendor_code like '%YES4V%')";
            break;

          case 4: // WM-DSV
            $sql = " Select sum(quantity) as quantity,  sum(amount) as amount  from fa_wm_dsv_returns where sku = " . "'" . $sku . "'".
            " and the_month = $Month and the_year = $Year ";
            break;

          case 5: // WM-MKP
            $sql = " Select  -sum(quantity) as quantity,  -sum(amount) as amount  from fa_walmart_market where sku = " . "'" . $sku . "'".
            " and the_month = $Month and the_year = $Year  and transaction_type = 'REFUNDED'";
            break;

            //case 6: // EBAY case này sẽ được gọi bằng 1 hàm khác vì còn phân chia theo store

          case 7: // Craiglist/Local
            $sql = " select sum(quantity) as quantity, sum(amount) as amount
            from fa_return_refund_craiglist_website where sku = " . "'" . $sku . "'".
            " and ( is_craiglist like 'Yes' or is_craiglist like 'yes') and the_month = $Month and the_year = $Year ";
            break;

          case 8: // Website
            $sql = " select sum(quantity) as quantity, sum(amount) as amount
            from fa_return_refund_craiglist_website where sku = " . "'" . $sku . "'".
            " and is_craiglist is null and the_month = $Month and the_year = $Year ";
            break;

          case 9: // FBA

            $sql = " Select sum(quantity) as quantity , -sum(product_sales) as amount from fa_amazon_idzo  where sku = " . "'" . $sku . "'".
            " and the_month = $Month and the_year = $Year  and (type like '%REFUNDED%' or type like '%Refund%')
            and (fulfillment like '%Amazon%' or fulfillment like '%AMAZON%')";

            break;

          case 10: // FBM
            $sql = " Select sum(quantity) as quantity,-sum(product_sales) as amount from fa_amazon_idzo  where sku = " . "'" . $sku . "'".
            " and the_month = $Month and the_year = $Year  and (type like '%REFUNDED%' or type like '%Refund%')
            and (fulfillment like '%SELLER%' or fulfillment like '%Seller%')";
            break;
    }
    if($SalesChannel != 6)
    {
      print_r($sql);
      print_r('<br>');
      $ReturnRefunds = DB::connection('mysql')->select($sql);
      foreach( $ReturnRefunds as  $ReturnRefund){
        $this->gReturn = $this->iif(is_null($ReturnRefund->quantity),0,$ReturnRefund->quantity);
        $this->gRefund = $this->iif(is_null($ReturnRefund->amount),0,$ReturnRefund->amount);
      }
    }
  }
  // ============================================================================================================
    private function GetReturnRefundForEBay($sku, $Month,$Year,$StoreID){
    $sql = " Select sum(quantity) as quantity ,sum(amount) as amount " .
    " from fa_selling_monthly_detail  where sku = " . "'" . $sku . "'".
    " and the_month = $Month and the_year = $Year  and store =  $StoreID " .
    " and sales_channel = 6  and ( type like '%Refund%')" ;

    $ReturnRefundFBAs = DB::connection('mysql')->select($sql);

    foreach($ReturnRefundFBAs as $ReturnRefundFBA){
      $this->gReturnEBAY = $this->iif(is_null($ReturnRefundFBA->quantity),0,$ReturnRefundFBA->quantity);
      //$this->gRefundEBAY = $this->iif(is_null($ReturnRefundFBA->amount),0,$ReturnRefundFBA->amount);
      $this->gRefundEBAY = -$this->iif(is_null($ReturnRefundFBA->amount),0,$ReturnRefundFBA->amount);
    }
  }
  // ============================================================================================================
  // ============================================================================================================
  private function CaculatePromotionAndClip($Month,$Year){
    $sql = "";
        //ForAVC
        $InvoiceNo ='';
        $Rebabe = 0;

        // Lấy tổng tiền clip của từng hóa đơn trong tháng
        $sql = " select sum(total_clip) as total_clip, invoice from  fa_promotion_coupon_clips where " .
        " the_month = $Month and the_year = $Year group by  invoice ";
        $Temps = DB::connection('mysql')->select($sql);
        foreach($Temps as $Temp)
        {
          $InvoiceNo = $Temp->invoice;
          $total_clip= $Temp->total_clip;

          // Lấy tổng REBATE của các SKU trong mỗi một hóa đơn promotion ở trên

          $sql = " select sum(rebate) as sum_rebate from  fa_amazon_promotions  where  invoice_number = '" . $InvoiceNo ."'";

          $Temp1s = DB::connection('mysql')->select($sql);
          foreach($Temp1s as $Temp1)
          {
           $Sumrebate = $Temp1->sum_rebate;
          }
          // Chia ra tương ứng với một Rebate thì clip fee là bao nhiêu
          $ClipFeePerRebate = ($Sumrebate - $total_clip)/$Sumrebate;

          // Phân bổ SUM total clip cho từng sku trong một hóa đơn promotion coupon
          $sql = " select id, sku, rebate from  fa_amazon_promotions  where  invoice_number = '" . $InvoiceNo ."'" .
          " and the_month = $Month and the_year = $Year ";
          $ids = DB::connection('mysql')->select($sql);
          foreach($ids as $id)
          {
            $sql = " update fa_amazon_promotions set clip_fee = $ClipFeePerRebate * $id->rebate where id = $id->id " ;
            DB::connection('mysql')->select($sql);
          }
        }// End for each các invoice promotion

        // Bổ sung những sku không phát sinh số liệu bán hàng kênh AVC WH->FA
        // nhưng có số liệu phát sinh của việc promotion vào bảng Summary
        $sql = "  insert into sal_selling_summary_monthlys(sku,promotion,sales_chanel,the_month,the_year)
        select sku, sum(rebate) as rebate ,1,$Month,$Year from fa_amazon_promotions
        WHERE the_month= $Month and the_year = $Year and vendor_code like '%Y4A%'
        and sku not in (select sku from  sal_selling_summary_monthlys
        where sales_chanel = 1 and the_month= $Month and the_year = $Year and by_invoice = 0)
        group by  sku order by sku ";
        DB::connection('mysql')->select($sql);

        // Bổ sung những sku không phát sinh số liệu bán hàng kênh AVC WH->AC
        // nhưng có số liệu phát sinh của việc promotion vào bảng Summary
        $sql = "  insert into sal_selling_summary_monthlys(sku,promotion,sales_chanel,the_month,the_year)
        select sku, sum(rebate) as rebate ,1,$Month,$Year from fa_amazon_promotions
        WHERE the_month= $Month and the_year = $Year and vendor_code like '%Y4A%'
        and sku not in (select sku from  sal_selling_summary_monthlys
        where sales_chanel = 1 and the_month= $Month and the_year = $Year and by_invoice = 1)
        group by  sku order by sku ";
        DB::connection('mysql')->select($sql);

        // Cập nhật data promotion của kênh avc-wh->fa/ac lên bảng tổng hợp
        $sql = " select sum(rebate) as fee, sum(clip_fee) as clip_fee, sku from  fa_amazon_promotions  where " .
        "the_month = $Month and the_year = $Year  and vendor_code like '%Y4A%' and rebate >0 group by sku ";
        $AvcPromotions = DB::connection('mysql')->select($sql);

        foreach($AvcPromotions as $AvcPromotion){
          $sql = " update sal_selling_summary_monthlys
          set promotion =  $AvcPromotion->fee - $AvcPromotion->clip_fee,  clip_fee = $AvcPromotion->clip_fee
          where sku = '". $AvcPromotion->sku ."'". " and the_month = $Month  and the_year = $Year and sales_chanel = 1  ";
          DB::connection('mysql')->select($sql);
        }

        // Bổ sung những sku không phát sinh số liệu bán hàng kênh AVC DS ->FA
        // nhưng có số liệu phát sinh của việc promotion vào bảng Summary
        $sql = "  insert into sal_selling_summary_monthlys(sku,promotion,sales_chanel,the_month,the_year)
        select sku, sum(rebate) as rebate, 2 , $Month,$Year from fa_amazon_promotions
        WHERE the_month= $Month and the_year = $Year and vendor_code like '%Y4B%'
        and sku not in (select sku from  sal_selling_summary_monthlys
        where sales_chanel = 2 and the_month= $Month and the_year = $Year and by_invoice = 0)
        group by  sku order by sku ";
        DB::connection('mysql')->select($sql);

        //  // Bổ sung những sku không phát sinh số liệu bán hàng kênh AVC DS ->AC
        // // nhưng có số liệu phát sinh của việc promotion vào bảng Summary
        // $sql = "  insert into sal_selling_summary_monthlys(sku,promotion,sales_chanel,the_month,the_year)
        // select sku, sum(rebate) as rebate, 2 , $Month,$Year from fa_amazon_promotions
        // WHERE the_month= $Month and the_year = $Year and vendor_code like '%Y4B%'
        // and sku not in (select sku from  sal_selling_summary_monthlys
        // where sales_chanel = 2 and the_month= $Month and the_year = $Year and by_invoice = 1)
        // group by  sku order by sku ";
        // DB::connection('mysql')->select($sql);

        // Cập nhật data promotion của kênh avc-ds lên bảng tổng hợp
        $sql = " select sum(rebate) as fee, sum(clip_fee) as clip_fee ,sku from  fa_amazon_promotions  where " .
        "the_month = $Month and the_year = $Year  and vendor_code like '%Y4B%' and rebate > 0 group by sku ";
        $AvcPromotions = DB::connection('mysql')->select($sql);

        foreach($AvcPromotions as $AvcPromotion){
          $sql = " update sal_selling_summary_monthlys
          set promotion =  $AvcPromotion->fee - $AvcPromotion->clip_fee , clip_fee = $AvcPromotion->clip_fee
          where sku = '". $AvcPromotion->sku ."'". " and the_month = $Month  and the_year = $Year and sales_chanel = 2 ";
          DB::connection('mysql')->select($sql);
        }

        // Bổ sung những sku không phát sinh số liệu bán hàng kênh AVC DI nhưng có số liệu phát sinh của việc promotion vào bảng Summary
        // 1.DI->FA
        $sql = "  insert into sal_selling_summary_monthlys(sku,promotion,sales_chanel,the_month,the_year)
        select sku, sum(rebate) as rebate, 3, $Month,$Year  from fa_amazon_promotions
        WHERE the_month= $Month and the_year = $Year
        and (vendor_code like '%yes69%' or vendor_code like '%DI%' or vendor_code like '%Y4V%' or vendor_code like '%yes63%')
        and sku not in (select sku from  sal_selling_summary_monthlys
        where sales_chanel = 3 and the_month= $Month and the_year = $Year  and by_invoice = 0)
        group by  sku order by sku ";
        DB::connection('mysql')->select($sql);

        // 1.DI->AC
        $sql = "  insert into sal_selling_summary_monthlys(sku,promotion,sales_chanel,the_month,the_year)
        select sku, sum(rebate) as rebate, 3, $Month,$Year  from fa_amazon_promotions
        WHERE the_month= $Month and the_year = $Year
        and (vendor_code like '%yes69%' or vendor_code like '%DI%' or vendor_code like '%Y4V%' or vendor_code like '%yes63%')
        and sku not in (select sku from  sal_selling_summary_monthlys
        where sales_chanel = 3 and the_month= $Month and the_year = $Year and by_invoice = 1)
        group by  sku order by sku ";
        DB::connection('mysql')->select($sql);

        // Cập nhật data promotion của kênh avc-di ->fa/ac lên bảng tổng hợp
        $sql = " select sum(rebate) as fee, sum(clip_fee) as clip_fee, sku from  fa_amazon_promotions  where " .
        "the_month = $Month and the_year = $Year
        and (vendor_code like '%yes69%' or vendor_code like '%DI%' or vendor_code like '%Y4V%' or vendor_code like '%yes63%')
        and rebate >0 group by sku ";
        $AvcPromotions = DB::connection('mysql')->select($sql);
        foreach($AvcPromotions as $AvcPromotion){
          $sql = " update sal_selling_summary_monthlys
          set promotion =  $AvcPromotion->fee -$AvcPromotion->clip_fee, clip_fee = $AvcPromotion->clip_fee
          where sku = '$AvcPromotion->sku' and the_month = $Month  and the_year = $Year and sales_chanel = 3 ";
          DB::connection('mysql')->select($sql);
        }

        // FBA
        $sql = " select DISTINCT(amazon_order_id) as amazon_order_id  from fa_fba_fbm_promotion
        where fa_fba_fbm_promotion.the_month =  $Month  and fa_fba_fbm_promotion.the_year = $Year ";
        $ds= DB::connection('mysql')->select($sql);
        $FullFillment='';
        foreach($ds as $d){
          $sql = " select fulfillment from fa_amazon_idzo where order_number = '$d->amazon_order_id'" ;
          $d1s = DB::connection('mysql')->select($sql);
          foreach($d1s as $d1){$FullFillment = $d1->fulfillment;}
          $sql = " update fa_fba_fbm_promotion set fulfillment ='$FullFillment' where amazon_order_id = '$d->amazon_order_id'" ;
          DB::connection('mysql')->select($sql);
        }

        $sql = " select id, sku from sal_selling_summary_monthlys where the_month =  $Month and the_year  =  $Year
        and sales_chanel = 9 and sell_quantity > 0 ";
        $ds= DB::connection('mysql')->select($sql);
        $TotalPromotion = 0;
        foreach($ds as $d)
        {
          $sql = " select sum(amount) as amount from fa_fba_fbm_promotion where the_month =  $Month and the_year  =  $Year
          and sku = '". $d->sku . "' and fulfillment like '%Amazon%' and description  like '%Save%' " ;
          $dsTotals = DB::connection('mysql')->select($sql);
          foreach( $dsTotals as  $dsTotal) { $TotalPromotion =  $this->iif(is_null( $dsTotal->amount),0, $dsTotal->amount);  }
          $sql = " update sal_selling_summary_monthlys set promotion  = $TotalPromotion where id = $d->id ";
          $dsTotals = DB::connection('mysql')->select($sql);
        }
        //FBM
        $sql = " select id, sku from sal_selling_summary_monthlys where the_month =  $Month and the_year  =  $Year
        and sales_chanel = 10 and sell_quantity > 0 ";
        $ds= DB::connection('mysql')->select($sql);
        $TotalPromotion = 0;
        foreach($ds as $d)
        {
          $sql = " select sum(amount) as amount from fa_fba_fbm_promotion where the_month =  $Month and the_year  =  $Year
          and sku = '". $d->sku . "' and fulfillment like '%Seller%' and description  like '%Save%' " ;
          $dsTotals = DB::connection('mysql')->select($sql);
          foreach( $dsTotals as  $dsTotal) { $TotalPromotion =  $this->iif(is_null( $dsTotal->amount),0, $dsTotal->amount);  }
          $sql = " update sal_selling_summary_monthlys set promotion  = $TotalPromotion where id = $d->id ";
          $dsTotals = DB::connection('mysql')->select($sql);
        }

        //End For FBA/FBM

        // WM DSV
        $sql = " select sku , sum(promotion_fee) as promotion_fee
        from fa_dsv_promotion_sem_actual where the_month =  $Month and the_year= $Year
        and promotion_fee > 0  group by sku " ;

        $ds = DB::connection('mysql')->select($sql);
        foreach($ds as $d){
          $sql = " update sal_selling_summary_monthlys set promotion = $d->promotion_fee ".
          " where the_month =  $Month and the_year= $Year and  sales_chanel = 4 and sku = '".$d->sku ."'" ;
          DB::connection('mysql')->select($sql);
        }

  }

  // ============================================================================================================
  private function CaculateSEM($Month,$Year){
   // Cập nhật SEM cho các kênh avc,fba,fbm
    $sql = " select sku, sem_fee_per_unit  from fa_sem_seo_amazon where the_month = $Month and the_year = $Year and sem_fee_per_unit > 0 ";
    $Sems =  DB::connection('mysql')->select($sql);
    foreach($Sems as $Sem)
    {
      $sql = " update sal_selling_summary_monthlys set seo_sem = sell_quantity * $Sem->sem_fee_per_unit
      where the_month = $Month and the_year = $Year and sales_chanel in (1,2,3,9,10) and by_invoice = 0  and sku = '".  $Sem->sku ."'";
      DB::connection('mysql')->select($sql);

      $sql = " update sal_selling_summary_monthlys set seo_sem = sell_quantity * $Sem->sem_fee_per_unit
      where the_month = $Month and the_year = $Year and sales_chanel in (1,3) and  by_invoice = 1  and sku = '".  $Sem->sku ."'";
      DB::connection('mysql')->select($sql);
     }

    // Cập nhật SEM cho các kênh WM-DSV
    $sql = " select sku, sum(sem_fee) as sem_fee from fa_dsv_promotion_sem_actual where the_month = $Month and the_year = $Year group by sku";
    $WMSems =  DB::connection('mysql')->select($sql);
    foreach($WMSems as $WMSem){
      $sql = " update sal_selling_summary_monthlys set seo_sem =  $WMSem->sem_fee
      where the_month = $Month and the_year = $Year and sales_chanel = 4  and sku = '".  $WMSem->sku ."'";
      DB::connection('mysql')->select($sql);
      }
    // Cập nhật SEM cho các kênh còn lại -> Những kênh khác chưa có SEM
    $sql = "update sal_selling_summary_monthlys set seo_sem = 0 where the_month = $Month and the_year = $Year
     and sales_chanel in (5,6,7,8)  " ;
    DB::connection('mysql')->select($sql);

    // Phân bổ lại tiền SEM FEE cho số realsales
    for($Channel = 1 ; $Channel <= 10;$Channel++)
    {
      $sql = " select (sem_fee) as sem_fee  from fa_summary_chargeback_monthly
      where the_month = $Month and the_year = $Year and sem_fee> 0 and 	sales_channel = $Channel  " ;
      $ds=  DB::connection('mysql')->select($sql);
      foreach($ds as $d){ $TotalSEMAct = round($d->sem_fee,2); }

      $sql = " select sum(seo_sem) as seo_sem from sal_selling_summary_monthlys
      where the_month = $Month and the_year = $Year and by_invoice = 0 and sales_chanel = $Channel  " ;
      $ds=  DB::connection('mysql')->select($sql);
      foreach($ds as $d){ $TotalSEMTheo = $d->seo_sem; }

      if($TotalSEMTheo <>0 ){ $SemRate =  $TotalSEMAct/ $TotalSEMTheo; }
      else{$SemRate =1;}

      $sql = " update sal_selling_summary_monthlys set seo_sem = seo_sem * $SemRate
      where the_month = $Month and the_year = $Year and 	sales_chanel = $Channel  " ;
     // print_r($sql);
     // print_r('<br>');
      $ds=  DB::connection('mysql')->select($sql);
    }

    // Phân bổ lại tiền SEM FEE cho số realsales
    for($Channel = 1 ; $Channel <= 3;$Channel++)
    {
      if($Channel<>2)
      {
        $sql = " select (sem_fee) as sem_fee  from fa_summary_chargeback_monthly
        where the_month = $Month and the_year = $Year and sem_fee> 0 and 	sales_channel = $Channel  " ;
        $ds=  DB::connection('mysql')->select($sql);
        foreach($ds as $d){ $TotalSEMAct = round($d->sem_fee,2); }

        $sql = " select sum(seo_sem) as seo_sem from sal_selling_summary_monthlys
        where the_month = $Month and the_year = $Year and by_invoice = 1 and sales_chanel = $Channel  " ;
        $ds=  DB::connection('mysql')->select($sql);
        foreach($ds as $d){ $TotalSEMTheo = $d->seo_sem; }

        if($TotalSEMTheo <>0 ){ $SemRate =  $TotalSEMAct/ $TotalSEMTheo; }
        else{$SemRate =1;}

        $sql = " update sal_selling_summary_monthlys set seo_sem = seo_sem * $SemRate
        where the_month = $Month and the_year = $Year and 	sales_chanel = $Channel  " ;
      // print_r($sql);
      // print_r('<br>');
        $ds=  DB::connection('mysql')->select($sql);
      }
    }

  }
  // ============================================================================================================
  public function index()
  {
    $plReport = DB::connection('mysql')->select(" select
    article, des,  account,  grant_total_total,  grant_total_xl,  grant_total_tc,  grant_total_wel,  mot_total,  mot_xl,
    mot_tc,  mot_wel,  hai_total,  hai_xl ,  hai_tc,  hai_wel,  ba_total,  ba_xl,  ba_tc,  ba_wel,  q1_total,
    q1_xl,  q1_tc,  q1_wel,  bon_total,  bon_xl,  bon_tc,  bon_wel,  nam_total,  nam_xl,  nam_tc,  nam_wel,
    sau_total,  sau_xl,  sau_tc,  sau_wel,  q2_total,  q2_xl,  q2_tc,  q2_wel,  bay_total,  bay_xl,  bay_tc,
    bay_wel,  tam_total,  tam_xl,  tam_tc,  tam_wel,  chin_total,  chin_xl,  chin_tc,  chin_wel,  q3_total,
    q3_xl,  q3_tc,  q3_wel,  muoi_total,  muoi_xl,  muoi_tc,  muoi_wel,  mmot_total,  mmot_xl,  mmot_tc,
    mmot_wel,  mhai_total,  mhai_xl,  mhai_tc,  mhai_wel,  q4_total,  q4_xl,  q4_tc,  q4_wel   from fa_pl_reports
    where   the_year = 1000");

    return view('FA.PLReport',compact('plReport'));
  }

  public function showPLReport(Request $request)
  {
    $TheYear = $request->input('year');
    $ByInvoice = $request->input('report_type');
    $request->flash();
    //return $ByInvoice;

    $this->MakePLReport($TheYear,  $ByInvoice);

    $plReport = DB::connection('mysql')->select(" select
    article,des,  account,  grant_total_total,  grant_total_xl,  grant_total_tc,  grant_total_wel,  mot_total,  mot_xl,
    mot_tc,  mot_wel,  hai_total,  hai_xl ,  hai_tc,  hai_wel,  ba_total,  ba_xl,  ba_tc,  ba_wel,  q1_total,
    q1_xl,  q1_tc,  q1_wel,  bon_total,  bon_xl,  bon_tc,  bon_wel,  nam_total,  nam_xl,  nam_tc,  nam_wel,
    sau_total,  sau_xl,  sau_tc,  sau_wel,  q2_total,  q2_xl,  q2_tc,  q2_wel,  bay_total,  bay_xl,  bay_tc,
    bay_wel,  tam_total,  tam_xl,  tam_tc,  tam_wel,  chin_total,  chin_xl,  chin_tc,  chin_wel,  q3_total,
    q3_xl,  q3_tc,  q3_wel,  muoi_total,  muoi_xl,  muoi_tc,  muoi_wel,  mmot_total,  mmot_xl,  mmot_tc,
    mmot_wel,  mhai_total,  mhai_xl,  mhai_tc,  mhai_wel,  q4_total,  q4_xl,  q4_tc,  q4_wel
    from fa_pl_reports where   the_year = $TheYear order by article ");
    return view('FA.PLReport',compact('plReport'));

    //return response()->json($plReport);
  }

  public function LoadFileImportForFA()
  {

   return view('FA.ImportDataForPLReport');
  }

  public function LoadFASummaryNull(Request $request)
  {

    $sql  = " select sku,products.title, sell_quantity ,   return_quantity ,    revenue ,     refund ,  nest_sales  , cogs,
    promotion ,    seo_sem ,    shiping_fee,  other_selling_expensives,
    sal_selling_summary_monthlys.dip , sal_selling_summary_monthlys.msf , selling_fees , fullfillment , chargeback
    ,coop ,freight_cost, freight_handling_return_cost , ebay_final_fee , paypal_fee , discount
    ,clip_fee , liability_insurance,  commission ,vine, other_fee, 	profit
    from sal_selling_summary_monthlys left join products on sal_selling_summary_monthlys.sku = products.product_sku
    where the_month = 0 and  the_year = 0 ";

    $dsSummary = DB::connection('mysql')->select( $sql);
    return view('FA.Summary',compact('dsSummary'));

  }

  public function LoadFASummary(Request $request)
  {
    $TheYear  = $request->input('year');
    $TheMonth  = $request->input('month');
    $ByInvoice  = $request->input('report_type');
    $TheChannel  = $request->input('channel');
    $TheStore  = $request->input('store');
    $sku  = $request->input('sku');
    $request->flash();

    $this->CaculateScale( $TheYear,$TheMonth);
    $this->AllocateInsuranceAndShippingCalProfitAndOtherSellingFee($TheYear,$TheMonth, $ByInvoice );//

    $sql  = " select sku, products.title, sell_quantity,return_quantity,revenue,refund ,nest_sales, cogs,
    promotion ,seo_sem , shiping_fee,other_selling_expensives,
    sal_selling_summary_monthlys.dip , sal_selling_summary_monthlys.msf , selling_fees , fullfillment , chargeback
    ,coop ,freight_cost, freight_handling_return_cost , ebay_final_fee , paypal_fee , discount
    ,clip_fee , liability_insurance,  commission ,vine, other_fee , 	profit
    from sal_selling_summary_monthlys left join products on sal_selling_summary_monthlys.sku = products.product_sku
    where the_month = $TheMonth and  the_year = $TheYear  ";
    if( $ByInvoice == 1)//AC
    {
      if($TheChannel ==1 || $TheChannel == 3)
      {
          $sql  =  $sql  . " and sales_chanel = $TheChannel  and by_invoice =  $ByInvoice ";
      }
      elseif($TheChannel <> 0)
      {
        $sql  =  $sql  . " and sales_chanel = $TheChannel ";
      }
      else// $TheChannel = 0
      {
        $sql  =  $sql  . "  and ( (by_invoice = 0 and sales_chanel in (2,4,5,6,7,8,9,10)) or (by_invoice = 1 and sales_chanel in (1,3)))";
      }
   }
   else // fa
   {
    $sql  =  $sql  . " and by_invoice = 0 ";
    if($TheChannel <> 0)
    {
      $sql  =  $sql  . " and  sales_chanel = $TheChannel ";
    }
   }

  if( $TheStore != 0)
  {
    $sql  =  $sql  . " and store =  $TheStore ";
  }

  if( $sku !='')
  {
    $sql  =  $sql  . " and sku = '".$sku."'";
  }
   $dsSummary = DB::connection('mysql')->select( $sql);
   return view('FA.Summary',compact('dsSummary'));
  }

//---------------------------------------------------
  public function importData(Request $request)
   {
    print_r('Bắt đầu lúc : '.date('Y-m-d H:i:s'));
    print_r('<br>');

    ini_set('memory_limit','2548M');
    set_time_limit(15000);

    $TheYear = $request->input('year');
    $TheMonth= $request->input('month');
    $request->flash();

    $DateBeginOfMonth = $this->GetFirtDateOfMonth( $TheYear, $TheMonth);
    $DateEndOfMonth = $this->GetLastDateOfMonth( $TheYear, $TheMonth);

    $RowBegin = 0;
    $RowEnd = 0;
    $SalesChanel = 0;

    $validator = Validator::make($request->all(),[
      'file'=>'required|max:45000|mimes:xlsx,xls,csv'
      ]);

    if($validator->passes())
    {
      $file = $request->file('file');
      $reader = new \PhpOffice\PhpSpreadsheet\Reader\Xlsx();
/*
      // xóa hết data trong tháng
      DB::connection('mysql')->select (" delete from fa_selling_monthly_detail where the_month = $TheMonth and the_year = $TheYear  " );

      // Import sheet thứ 1 AVC-DS-INVOICE, thông tin bán hàng trên kênh avc-ds
        $SalesChanel = 2; // AVC-DS-INVOICE
        $RowBegin = 5;
        $reader->setLoadSheetsOnly(["AVC-DS-INVOICE", "AVC-DS-INVOICE"]);
        $spreadsheet = $reader->load($file);
        $RowEnd = 4;//$spreadsheet->getActiveSheet()->getHighestRow();

        DB::connection('mysql')->select (" delete from fa_selling_monthly_detail
        where the_month = $TheMonth and the_year = $TheYear and sales_channel = $SalesChanel " );

       // $sql = " delete from sal_avcds_order_not_founds where  the_month = $TheMonth and the_year = $TheYear";
       // $ds= DB::connection('mysql')->select($sql);

        if($RowEnd>=5)
        {
          print_r ( 'AVC-DS'.$RowEnd );
          print_r ( '<br>');

          for($i=$RowBegin; $i <= $RowEnd; $i++)
          {
              $OrderID = $spreadsheet->getActiveSheet()->getCellByColumnAndRow( 1,$i)->getValue();// Order ID
              $InvoiceDate = $spreadsheet->getActiveSheet()->getCellByColumnAndRow( 4,$i)->getValue();//Invoice Date
              $Asin = $spreadsheet->getActiveSheet()->getCellByColumnAndRow( 5,$i)->getValue();//Asin
              $Sku = $spreadsheet->getActiveSheet()->getCellByColumnAndRow( 6,$i)->getValue();// sku
              $Quantity = $spreadsheet->getActiveSheet()->getCellByColumnAndRow( 8,$i)->getValue();// Quantity
              $Price = $spreadsheet->getActiveSheet()->getCellByColumnAndRow( 9,$i)->getValue();// Price

              if(strlen($Sku)>4){ $Sku = $this->GetSkuFromAsin( $Asin); }

              DB::connection('mysql')->table('fa_selling_monthly_detail')->insert(
              ['order_id'=>$OrderID,'invoice_date'=>$InvoiceDate, 'asin'=>$Asin,'Sku'=>$Sku,
              'quantity'=>$Quantity,'price'=>$Price,'amount'=>$Quantity* $Price,
              'the_month'=>$TheMonth,'The_year'=>$TheYear,'sales_channel'=>$SalesChanel]);
          }
        }
        else
        {
          $RowBegin = 4;
          $reader->setLoadSheetsOnly(["AVC-DS-ORDER", "AVC-DS-ORDER"]);
          $spreadsheet = $reader->load($file);
          $RowEnd = $spreadsheet->getActiveSheet()->getHighestRow();
          print_r ( 'AVC-DS-ORDER'.$RowEnd );

          for($i=$RowBegin; $i <= $RowEnd; $i++)
          {
            $OrderID = $spreadsheet->getActiveSheet()->getCellByColumnAndRow( 1,$i)->getValue();// Order ID
            $Count = $spreadsheet->getActiveSheet()->getCellByColumnAndRow( 2,$i)->getValue();//$Count
            $ItemCost= $spreadsheet->getActiveSheet()->getCellByColumnAndRow( 19,$i)->getValue();//$ItemCost
            $Sku= $spreadsheet->getActiveSheet()->getCellByColumnAndRow( 20,$i)->getValue();//$Sku
            $Asin= $spreadsheet->getActiveSheet()->getCellByColumnAndRow( 21,$i)->getValue();//$Asin
            $Quantity= $spreadsheet->getActiveSheet()->getCellByColumnAndRow( 23,$i)->getValue();//$Quantity

            if(strlen($Sku)<>4 ) {  $Sku =$this->GetSkuFromAsin($Asin);}

            if( $Count == 1)
            {
              DB::connection('mysql')->table('fa_selling_monthly_detail')->insert(
                ['order_id'=>$OrderID, 'asin'=> $Asin,'Sku'=>$Sku,'quantity'=>$Quantity,
                'price'=>$ItemCost,'amount'=>$Quantity * $ItemCost ,'my_count'=>$Count,
                'the_month'=>$TheMonth,'The_year'=>$TheYear,'sales_channel'=>$SalesChanel]);
            }
            else
            {
             $sql = " select count(id) as MyCount from fa_selling_monthly_detail where order_id = '$OrderID'";

             if($this->IsExist('mysql', $sql)== 0)
              {
                $sql = " select Odt.asin, Odt.sku, Odt.quantity, Odt.cost
                from amazon_dropship_orders as O INNER join amazon_dropship_order_details as Odt on O.id = Odt.order_id
                where O.order_id like '%$OrderID%'";
                $ds= DB::connection('mysql_it')->select($sql);
                  $Mycount = 0;
                  foreach($ds as $d)
                  {
                  DB::connection('mysql')->table('fa_selling_monthly_detail')->insert(
                  ['order_id'=>$OrderID, 'asin'=>$d->asin,'sku'=>$this->left($d->sku,4),
                  'quantity'=>$d->quantity,'price'=>$d->cost/$d->quantity,'amount'=>$d->cost,
                  'the_month'=>$TheMonth,'The_year'=>$TheYear,'sales_channel'=>$SalesChanel]);
                  $Mycount++;
                  }// End for

                if($Mycount==0)// Không tìm thấy Orer
                {
                  $sql = " insert into sal_avcds_order_not_founds(order_id,the_month,the_year)
                  values('$OrderID',$TheMonth,$TheYear)";
                  $ds= DB::connection('mysql')->select($sql);
                }
              } // end if
            }// end else
          }//End For
         }// end else
      // Import sheet thu  2 =-> Amazon-idzo-invoice chứa các thông tin bán hàng và thông tin khác của 2 kênh fbm-fba
      $RowBegin = 4;
      $reader->setLoadSheetsOnly(["Amazon-idzo-invoice", "Amazon-idzo-invoice"]);
      $spreadsheet = $reader->load($file);
      $RowEnd = $spreadsheet->getActiveSheet()->getHighestRow();
      print_r ('Amazon-idzo-invoice'.$RowEnd );
      print_r ( '<br>');
      DB::connection('mysql')->select (" delete from fa_amazon_idzo where the_month = $TheMonth and the_year = $TheYear ");

      for($i=$RowBegin; $i <= $RowEnd; $i++)
      {
          $Type = $spreadsheet->getActiveSheet()->getCellByColumnAndRow( 3,$i)->getValue();// type
          $OrderID = $spreadsheet->getActiveSheet()->getCellByColumnAndRow( 4,$i)->getValue();// Order ID
          $Sku = $spreadsheet->getActiveSheet()->getCellByColumnAndRow( 5,$i)->getValue();//  SKU
          $Quantity = $spreadsheet->getActiveSheet()->getCellByColumnAndRow( 7,$i)->getValue();// Quantity
          $Fullfillment = $spreadsheet->getActiveSheet()->getCellByColumnAndRow( 9,$i)->getValue();//Fullfillment
          $PoductSales = $spreadsheet->getActiveSheet()->getCellByColumnAndRow( 14,$i)->getValue();// product sales -> Amount
          $ShippingCredits = $spreadsheet->getActiveSheet()->getCellByColumnAndRow( 16,$i)->getValue();// shipping credits
          $PromotionalRebates = $spreadsheet->getActiveSheet()->getCellByColumnAndRow( 20,$i)->getValue();// $PromotionalRebates
          $SellingFees = $spreadsheet->getActiveSheet()->getCellByColumnAndRow( 23,$i)->getValue();// selling fees
          $FbaFee = $spreadsheet->getActiveSheet()->getCellByColumnAndRow( 24,$i)->getValue();// FBA fee

          if($Type == "Order" || $Type == "Refund")
          {
            if(strlen($Sku)>4) { $Sku= $this->GetSkuFromAmazonOderID( $OrderID ,$Sku);}
            DB::connection('mysql')->table('fa_amazon_idzo')->insert(
            ['type'=>$Type,  'order_number'=> $OrderID, 'sku'=>$Sku,  'quantity'=> $Quantity ,
            'fulfillment'=>$Fullfillment ,'product_sales'=> $PoductSales,
            'shipping_credits'=>$ShippingCredits,'promotional_rebates'=>$PromotionalRebates,
            'selling_fees'=>$SellingFees, 'fba_fees'=> $FbaFee ,  'sku'=> $Sku,
            'order_number'=>$OrderID ,'the_month'=>$TheMonth,'the_year'=>$TheYear]
            );
          }
      }
      // End Import sheet thu 2 =-> Amazon-idzo

      // Import sheet thu  3  Amazon-Infideal-invoice

      $RowBegin = 5;
      $reader->setLoadSheetsOnly(["Amazon-Infideal-invoice", "Amazon-Infideal-invoice"]);
      $spreadsheet = $reader->load($file);
      $RowEnd = $spreadsheet->getActiveSheet()->getHighestRow();
      print_r ('Amazon-Infideal-invoice'.$RowEnd );
      print_r ( '<br>');


      for($i=$RowBegin; $i <= $RowEnd; $i++)
      {
          $Type = $spreadsheet->getActiveSheet()->getCellByColumnAndRow( 3,$i)->getValue();// type
          $OrderID = $spreadsheet->getActiveSheet()->getCellByColumnAndRow( 4,$i)->getValue();// Order ID
          $Sku = $spreadsheet->getActiveSheet()->getCellByColumnAndRow( 5,$i)->getValue();//  SKU
          $Quantity = $spreadsheet->getActiveSheet()->getCellByColumnAndRow( 7,$i)->getValue();// Quantity
          $Fullfillment = $spreadsheet->getActiveSheet()->getCellByColumnAndRow( 9,$i)->getValue();//Fullfillment
          $PoductSales = $spreadsheet->getActiveSheet()->getCellByColumnAndRow( 14,$i)->getValue();// product sales -> Amount
          $ShippingCredits = $spreadsheet->getActiveSheet()->getCellByColumnAndRow( 16,$i)->getValue();// shipping credits
          $PromotionalRebates = $spreadsheet->getActiveSheet()->getCellByColumnAndRow( 20,$i)->getValue();// $PromotionalRebates
          $SellingFees = $spreadsheet->getActiveSheet()->getCellByColumnAndRow( 23,$i)->getValue();// selling fees
          $FbaFee = $spreadsheet->getActiveSheet()->getCellByColumnAndRow( 24,$i)->getValue();// FBA fee

          if($Type == "Order" || $Type == "Refund"){
            if(strlen($Sku)>4)
            {
               $Sku  = $this->GetSkuFromAmazonOderID( $OrderID ,$Sku);
            }
            DB::connection('mysql')->table('fa_amazon_idzo')->insert(
                ['type'=>$Type,'order_number'=> $OrderID , 'sku'=>$Sku,  'quantity'=> $Quantity ,
                'fulfillment'=>$Fullfillment ,'product_sales'=> $PoductSales,
                'shipping_credits'=>$ShippingCredits,'promotional_rebates'=>$PromotionalRebates,
                'selling_fees'=>$SellingFees, 'fba_fees'=> $FbaFee ,  'sku'=> $Sku,
                'order_number'=>$OrderID,'the_month'=>$TheMonth,'the_year'=>$TheYear]
            );
          }
      }
      // End Import sheet thu 3 Amazon-Infideal-invoice

      // Import sheet thu  4 =-> AVC Return
      $RowBegin = 4;
      $reader->setLoadSheetsOnly(["AVC Return", "AVC Return"]);
      $spreadsheet = $reader->load($file);
      $RowEnd = $spreadsheet->getActiveSheet()->getHighestRow();
      print_r ('AVC Return'.$RowEnd );
      print_r ( '<br>');
      DB::connection('mysql')->select (" delete from fa_avc_returns where the_month = $TheMonth and the_year = $TheYear ");
      for($i=$RowBegin; $i <= $RowEnd; $i++)
      {
          $VendorCode = $spreadsheet->getActiveSheet()->getCellByColumnAndRow( 3,$i)->getValue();// VendorCode
          $Asin = $spreadsheet->getActiveSheet()->getCellByColumnAndRow( 13,$i)->getValue();// ASIN
          $Sku = $this->GetSkuFromAsin($Asin);
          $Quantity = $spreadsheet->getActiveSheet()->getCellByColumnAndRow( 18,$i)->getValue();//Quantity
          $Price = $spreadsheet->getActiveSheet()->getCellByColumnAndRow( 19,$i)->getValue();// Price
          $Amount = $spreadsheet->getActiveSheet()->getCellByColumnAndRow( 20,$i)->getValue();// Amount
          DB::connection('mysql')->table('fa_avc_returns')->insert(
              ['vendor_code'=>$VendorCode, 'asin'=> $Asin, 'sku'=>$Sku,'quantity'=>$Quantity,
              'price'=>$Price,'amount'=>$Amount,'the_month'=>$TheMonth,'The_year'=>$TheYear]
          );
      }
      // End Import sheet thu 4 =-> AVC Return
      // Import sheet thu  5 => Đối chiếu thực nhận DSV
      $RowBegin = 4;
      $reader->setLoadSheetsOnly(["Đối chiếu thực nhận DSV", "Đối chiếu thực nhận DSV"]);
      $spreadsheet = $reader->load($file);
      $RowEnd = $spreadsheet->getActiveSheet()->getHighestRow();
      print_r ( 'Đối chiếu thực nhận DSV RowEnd'.$RowEnd );
      print_r ( '<br>');
      DB::connection('mysql')->select (" delete from fa_dsv_promotion_sem_actual where the_month = $TheMonth and the_year = $TheYear " );

      for($i=$RowBegin; $i <= $RowEnd; $i++)
      {
          $Sku = $spreadsheet->getActiveSheet()->getCellByColumnAndRow(1,$i)->getValue();// sku
          $Quantity = $spreadsheet->getActiveSheet()->getCellByColumnAndRow(4,$i)->getValue();//Quantity
          $Price = $spreadsheet->getActiveSheet()->getCellByColumnAndRow(5,$i)->getValue();//$Price
          $PromotionFee = $spreadsheet->getActiveSheet()->getCellByColumnAndRow(7,$i)->getValue();//Promotion Fee
          $SEMFee = $spreadsheet->getActiveSheet()->getCellByColumnAndRow(9,$i)->getValue();//SEM -fee
          $OrderValue = $spreadsheet->getActiveSheet()->getCellByColumnAndRow(10,$i)->getValue();//Order value
          $ActReceived = $spreadsheet->getActiveSheet()->getCellByColumnAndRow(11,$i)->getValue();//Thực nhận

          DB::connection('mysql')->table('fa_dsv_promotion_sem_actual')->insert(
          ['sku'=> $Sku,'quantity'=>$Quantity,'price'=>$Price,'promotion_fee'=> $PromotionFee,'sem_fee'=>$SEMFee,'the_month'=>$TheMonth,'the_year'=>$TheYear]);
      }

      // End Import sheet thu 5 => Đối chiếu thực nhận DSV

      // Import sheet thu  5 =-> WM DSV Invoice -. KHông thực hiện Import nữa vì đã import lên BE hàng tuần
      $SalesChanel = 4;// WM DSV
      DB::connection('mysql')->select (" delete from fa_selling_monthly_detail
      where the_month = $TheMonth and the_year = $TheYear  and sales_channel = $SalesChanel" );

      $sql = " select O.po_id, Odt.sku, Odt.quantity, Odt.cost as price, O.order_processing_date
      from walmart_dropship_orders as O inner join walmart_dropship_order_details as Odt   on O.id = Odt.order_id
      where Odt.cost > 0
      and date(O.order_processing_date) >= '$DateBeginOfMonth'
      and date(O.order_processing_date) <= '$DateEndOfMonth'";

      print_r('sql get avc-ds-order '. $sql );
      print_r('<br>');

      $ds= DB::connection('mysql_it')->select($sql);
      $i=0;
      foreach($ds as $d)
      {
        DB::connection('mysql')->table('fa_selling_monthly_detail')->insert(
          ['sku'=>$d->sku, 'quantity'=>$d->quantity,'price'=>$d->price,'amount'=>$d->quantity * $d->price,
          'sales_channel'=>$SalesChanel,'po_id'=> $d->po_id,'invoice_date'=>$d->order_processing_date,
          'the_month'=>$TheMonth,'the_year'=>$TheYear ]);
          $i++;
          print_r('Record thứ: '.  $i. 'Sales Channel:'. $SalesChanel  );
          print_r('<br>');
      }

      // End Import sheet thu 5 =->WM DSV Invoice

      // Import sheet thu  6 =-> Walmart Market
      // Import sheet thu  6 =-> Walmart Market
      $RowBegin = 5;
      $SalesChanel = 5;// Walmart Market
      $reader->setLoadSheetsOnly(["Walmart Market", "Walmart Market"]);
      $spreadsheet = $reader->load($file);
      $RowEnd = $spreadsheet->getActiveSheet()->getHighestRow();
      print_r ('Walmart Market'.$RowEnd );
      print_r ( '<br>');
      DB::connection('mysql')->select (" delete from fa_walmart_market where the_month = $TheMonth and the_year = $TheYear " );

      for($i=$RowBegin; $i <= $RowEnd; $i++)
      {
          $TransactionType = $spreadsheet->getActiveSheet()->getCellByColumnAndRow(6,$i)->getValue();// Transaction Type
          $TransactionDate = $spreadsheet->getActiveSheet()->getCellByColumnAndRow(7,$i)->getValue();// Transaction Type
          $Quantity = $spreadsheet->getActiveSheet()->getCellByColumnAndRow( 8,$i)->getValue();//quantity
          $Sku = $spreadsheet->getActiveSheet()->getCellByColumnAndRow( 9,$i)->getValue();//sku
          $PayableToPartnerFromSale = $spreadsheet->getActiveSheet()->getCellByColumnAndRow( 22,$i)->getValue();//Payable to Partner from Sale
          $CommissionFromSale = $spreadsheet->getActiveSheet()->getCellByColumnAndRow( 23,$i)->getValue();//Commission from Sale
          $Amount = $PayableToPartnerFromSale + $CommissionFromSale;


          if( $TransactionDate >= $DateBeginOfMonth && date("Y-m-d",$TransactionDate) <= $DateEndOfMonth){
            DB::connection('mysql')->table('fa_walmart_market')->insert(
                [ 'transaction_type'=> $TransactionType, 'sku'=>$Sku,'quantity'=>$Quantity,
                'amount'=> $Amount,'commission_from_sale'=> $CommissionFromSale ,'the_month'=>$TheMonth,'the_year'=>$TheYear]
            );
          }
      }
      // End Import sheet thu 6 => Walmart Market

      // Import sheet thu  7 =-> WM DSV Return
       $RowBegin = 4;

       $reader->setLoadSheetsOnly(["WM DSV Return", "WM DSV Return"]);
       $spreadsheet = $reader->load($file);
       $RowEnd = $spreadsheet->getActiveSheet()->getHighestRow();
       print_r ('WM DSV Return'.$RowEnd );
       print_r ( '<br>');
       DB::connection('mysql')->select (" delete from fa_wm_dsv_returns where the_month = $TheMonth and the_year = $TheYear " );

       for($i=$RowBegin; $i <= $RowEnd; $i++)
       {
           $InvoiceNo = $spreadsheet->getActiveSheet()->getCellByColumnAndRow(17,$i)->getValue();// InvoiceNo
           $Sku = $spreadsheet->getActiveSheet()->getCellByColumnAndRow( 7,$i)->getValue();// sku
           $Price = $spreadsheet->getActiveSheet()->getCellByColumnAndRow( 8,$i)->getValue();//Price
           $Quantity = $spreadsheet->getActiveSheet()->getCellByColumnAndRow( 9,$i)->getValue();//quantity
           $Amount = $spreadsheet->getActiveSheet()->getCellByColumnAndRow( 11,$i)->getValue();//Amount

           //if($Sku == ""){$Sku = $this->GetSkuFromWMInvoice($InvoiceNo) }
           if($Sku <> ""){
            DB::connection('mysql')->table('fa_wm_dsv_returns')->insert(
            [ 'sku'=>$Sku,'price'=>$Price, 'quantity'=>$Quantity,
            'amount'=> -$Amount,'the_month'=>$TheMonth,'the_year'=>$TheYear]);
          }

       }
      // End Import sheet thu 7 => WM DSV Return

       // Import sheet thu  8 =-> FBA FBM Promotion
       $RowBegin = 4;
       //$SalesChanel = 4;// WM DSV
       $reader->setLoadSheetsOnly(["FBA FBM Promotion", "FBA FBM Promotion"]);
       $spreadsheet = $reader->load($file);
       $RowEnd = $spreadsheet->getActiveSheet()->getHighestRow();
       print_r ('FBA FBM Promotion'.$RowEnd );
       print_r ( '<br>');
       DB::connection('mysql')->select (" delete from fa_fba_fbm_promotion where the_month = $TheMonth and the_year = $TheYear " );

       for($i=$RowBegin; $i <= $RowEnd; $i++)
       {
           $Price = $spreadsheet->getActiveSheet()->getCellByColumnAndRow( 3,$i)->getValue();// price
           $AmazonOrderID = $spreadsheet->getActiveSheet()->getCellByColumnAndRow(7,$i)->getValue();//amazon-order-id
           $Quantity = 1 ; //Quantity
           $Amount = $Price; // Amount
           $Description = $spreadsheet->getActiveSheet()->getCellByColumnAndRow( 5,$i)->getValue();//Description
           $Sku = $this->GetSkuFromFbaFbmPromotion($AmazonOrderID,$Price);

           DB::connection('mysql')->table('fa_fba_fbm_promotion')->insert(
           [ 'sku'=>$Sku, 'amazon_order_id'=> $AmazonOrderID,'quantity'=>$Quantity ,'price'=>$Price,
           'amount'=>$Amount,'description'=> $Description ,'the_month'=>$TheMonth,'the_year'=>$TheYear]
           );

       }
      // End Import sheet thu 8 =-> FBA FBM Promotion

      // Import sheet thu  9 =-> Monthly Store Fee
      $RowBegin = 4;
      //$SalesChanel = 4;// WM DSV
      $reader->setLoadSheetsOnly(["Monthly Store Fee", "Monthly Store Fee"]);
      $spreadsheet = $reader->load($file);
      $RowEnd = $spreadsheet->getActiveSheet()->getHighestRow();
      print_r ( 'Monthly Store Fee'.$RowEnd );
      print_r ( '<br>');
      DB::connection('mysql')->select (" delete from fa_fba_monthly_store_fee where the_month = $TheMonth and the_year = $TheYear " );

      for($i=$RowBegin; $i <= $RowEnd; $i++)
      {
          $Asin = $spreadsheet->getActiveSheet()->getCellByColumnAndRow( 1,$i)->getValue();// ASIN
          $Amount = $spreadsheet->getActiveSheet()->getCellByColumnAndRow( 21,$i)->getValue();//amount
          $Sku = $this->GetSkuFromAsin($Asin);

          DB::connection('mysql')->table('fa_fba_monthly_store_fee')->insert(
              ['asin'=>$Asin,'sku'=> $Sku,'amount'=>$Amount ,'the_month'=>$TheMonth,'the_year'=>$TheYear]
          );

      }
      // End Import sheet thu 9 => Monthly Store Fee

     // Import sheet thu  10 => Ebay Fitness
     $RowBegin = 9;
     $SalesChanel = 6;// Ebay
     $EbayPPFee = 0.027;// Payment Processing fee
     $SalesStore = 1;//Ebay Fitness
     $reader->setLoadSheetsOnly(["Ebay Fitness", "Ebay Fitness"]);
     $spreadsheet = $reader->load($file);
     $RowEnd = $spreadsheet->getActiveSheet()->getHighestRow();

     print_r ( 'Ebay Fitness'.$RowEnd );
     print_r ( '<br>');
     DB::connection('mysql')->select (" delete from fa_selling_monthly_detail where the_month = $TheMonth and the_year = $TheYear
     and sales_channel =  $SalesChanel and store =  $SalesStore " );
     $Rate =1;
     for($i=$RowBegin; $i <= $RowEnd; $i++)
     {
         $Type = $spreadsheet->getActiveSheet()->getCellByColumnAndRow( 2,$i)->getValue();// Type
         $OrderID = $spreadsheet->getActiveSheet()->getCellByColumnAndRow( 4,$i)->getValue();//Order ID
         $OrderTotal = $spreadsheet->getActiveSheet()->getCellByColumnAndRow( 7,$i)->getValue();//OrderTotal
         $ItemID = $spreadsheet->getActiveSheet()->getCellByColumnAndRow(14,$i)->getValue();//ItemID
         $TransactionID = $spreadsheet->getActiveSheet()->getCellByColumnAndRow( 15,$i)->getValue();//TransactionID
         $Quantity = $spreadsheet->getActiveSheet()->getCellByColumnAndRow( 16,$i)->getValue();//Quantity
         $ItemCost = $spreadsheet->getActiveSheet()->getCellByColumnAndRow( 17,$i)->getValue();//Item cost

         $Sku = '';
         if($Type =='Order')
         {
          $Sku = $this->GetSkuFromOrderNumberOnEbay($OrderID,$ItemID);
          $PaymentsProcessing =   $Quantity * $ItemCost* $EbayPPFee;
          DB::connection('mysql')->table('fa_selling_monthly_detail')->insert(
            ['type'=>$Type, 'sku'=> $Sku, 'quantity'=>$Quantity,'price'=>$ItemCost/$Quantity ,'amount'=>$ItemCost ,
            'payment_processing_fee'=> $PaymentsProcessing ,'transaction_id'=>$TransactionID  ,
            'sales_channel'=> $SalesChanel,'store'=>$SalesStore ,'the_month'=>$TheMonth,'the_year'=>$TheYear]);
         }
         elseif($Type =='Refund')// Trả hàng
         {
          $Rate =$this->EqualAmountFromOrderNumberOnEbay($OrderID, $OrderTotal);
          if($Rate ==1)
          {
           $PaymentsProcessing = 0;
           $sql = " select Odt.sku,Odt.quantity_purchased ,Odt.transaction_price
           from ebay_order as O inner join ebay_order_detail as Odt
           on O.id = Odt.ebay_order_id
           where O.ebay_extended_order_id = '$OrderID'" ;
           $ds = DB::connection('mysql_it')->select ($sql);
           foreach( $ds as $d )
           {
            DB::connection('mysql')->table('fa_selling_monthly_detail')->insert(
              ['type'=>$Type, 'sku'=>$d->sku, 'quantity'=>$d->quantity_purchased,'price'=>$d->transaction_price,
              'amount'=>$d->quantity_purchased * $d->transaction_price,
              'payment_processing_fee'=> $PaymentsProcessing  ,'transaction_id'=>$TransactionID ,
              'sales_channel'=> $SalesChanel,'store'=>$SalesStore ,'the_month'=>$TheMonth,'the_year'=>$TheYear]);
           }
          }
          else// Trả một phần tiền
          {
           $PaymentsProcessing = 0;
           $sql = " select Odt.sku,Odt.quantity_purchased ,Odt.transaction_price
           from ebay_order as O inner join ebay_order_detail as Odt
           on O.id = Odt.ebay_order_id
           where O.ebay_extended_order_id = '$OrderID'" ;
           $ds = DB::connection('mysql_it')->select ($sql);
           foreach( $ds as $d )
           {
            DB::connection('mysql')->table('fa_selling_monthly_detail')->insert(
              ['type'=>'Refund-1', 'sku'=>$d->sku, 'quantity'=>0,'price'=>$d->transaction_price * $Rate ,
              'amount'=>$d->quantity_purchased * $d->transaction_price*$Rate,
              'payment_processing_fee'=> $PaymentsProcessing *$Rate ,'transaction_id'=>$TransactionID ,
              'sales_channel'=> $SalesChanel,'store'=>$SalesStore ,'the_month'=>$TheMonth,'the_year'=>$TheYear]);
           }
          }
        }
      }// For

    // End Import sheet thu 10 => Ebay Fitness

   // Import sheet thu  11 => Ebay Inc = Paypal Order Details

   //print_r('Bắt đầu lúc : '.date('Y-m-d H:i:s'));
   // print_r('<br>');


   $RowBegin = 4;
   $SalesChanel = 6;// Ebay
   $SalesStore = 2;//Ebay Inc
   $reader->setLoadSheetsOnly(["Paypal Order Detail", "Paypal Order Detail"]);
   $spreadsheet = $reader->load($file);
   $RowEnd = $spreadsheet->getActiveSheet()->getHighestRow();

   print_r ( 'Paypal Order Detail'.$RowEnd );
   print_r ( '<br>');
   DB::connection('mysql')->select (" delete from fa_selling_monthly_detail where the_month = $TheMonth and the_year = $TheYear
   and sales_channel =  $SalesChanel and store =  $SalesStore " );

   DB::connection('mysql')->select (" delete from fa_return_refund_craiglist_website where the_month = $TheMonth and the_year = $TheYear " );

   DB::connection('mysql')->select (" delete from sal_website_order_tmp where the_month = $TheMonth and the_year = $TheYear " );

   //$RowBegin=46;
   //$RowEnd=47;

   for($i=$RowBegin; $i <= $RowEnd; $i++)
   {
       $Type = $spreadsheet->getActiveSheet()->getCellByColumnAndRow(2,$i)->getValue();// Type
       $TransactionID = $spreadsheet->getActiveSheet()->getCellByColumnAndRow(3,$i)->getValue();//TransactionID
       $Gross = $spreadsheet->getActiveSheet()->getCellByColumnAndRow(8,$i)->getValue();//$Gross
       $Net = $spreadsheet->getActiveSheet()->getCellByColumnAndRow(9,$i)->getValue();// Net
       $PaypalFee = $spreadsheet->getActiveSheet()->getCellByColumnAndRow(10,$i)->getValue();//$PaypalFee
       $ParentTransactionID = $spreadsheet->getActiveSheet()->getCellByColumnAndRow(11,$i)->getValue();//$ParentID

       print_r('Parent:' .   $ParentTransactionID);
       print_r('<br>');
       if( $Type =="eBay Auction Payment")// Khách mua hàng kênh Ebay Store Inc trả tiền qua Paypal
       {
        $this->IsEbay=true;
        $this->IsOrder=true;
        $this->InsertOrderDetailByPaypalTransaction($Type,$TransactionID,$ParentTransactionID,$Gross,$Net,$PaypalFee, $SalesChanel, $SalesStore,$TheMonth,$TheYear,$this->IsOrder,$this->IsEbay);
       }

       elseif( $Type =="Payment Refund")// Trả lại tiền cho khách qua Paypal
       {
        $this->IsOrder = false;
        if($this->FoundRefundTransaction($TransactionID, $ParentTransactionID,$Gross,$Net,$PaypalFee) <> 0)//   $this->IsEbay=true; sẽ được cập nhật trong hàm này
        {
         $this->InsertOrderDetailByPaypalTransaction($Type,$TransactionID,$ParentTransactionID,$Gross,$Net,$PaypalFee, $SalesChanel, $SalesStore,$TheMonth,$TheYear,$this->IsOrder,$this->IsEbay);
        }
        else
        {
          print_r('Không tìm thấy transaction tương ứng');
          print_r('<br>');
        }
       }
       elseif($Type =="Express Checkout Payment")// Khách trả tiền cho hàng mua ở kênh website
       {
        $this->IsEbay=false;
        $this->IsOrder = true;
        $this->InsertOrderDetailByPaypalTransaction($Type,$TransactionID,$ParentTransactionID,$Gross,$Net,$PaypalFee, $SalesChanel, $SalesStore,$TheMonth,$TheYear,$this->IsOrder,$this->IsEbay);
       }
       elseif($Type =="Tax collected by partner")
       {
        // Chưa làm gì
       }

   }// End For
   // print_r('Kết thúc lúc : '.date('Y-m-d H:i:s'));
   // print_r('<br>');
   // End Import sheet thu 11=> Ebay Inc = Paypal Order Details


     // Import sheet thu  12 => Ebay Infideals
     $RowBegin = 4;
     $SalesChanel = 6;// Ebay
     $EbayPPFee = 0.027;// Payment Processing fee
     $SalesStore = 3;//Ebay Infideals
     $reader->setLoadSheetsOnly(["Ebay Infideals", "Ebay Infideals"]);
     $spreadsheet = $reader->load($file);
     $RowEnd = $spreadsheet->getActiveSheet()->getHighestRow();
     print_r ( 'Ebay Infideals'.$RowEnd );
     print_r ( '<br>');
     DB::connection('mysql')->select (" delete from fa_selling_monthly_detail where the_month = $TheMonth and the_year = $TheYear
     and sales_channel =  $SalesChanel and store =  $SalesStore " );

     for($i=$RowBegin; $i <= $RowEnd; $i++)
     {
         $Type = $spreadsheet->getActiveSheet()->getCellByColumnAndRow( 2,$i)->getValue();// Type
         $OrderID = $spreadsheet->getActiveSheet()->getCellByColumnAndRow( 4,$i)->getValue();//Order ID
         $OrderTotal = $spreadsheet->getActiveSheet()->getCellByColumnAndRow( 7,$i)->getValue();//OrderTotal
         $ItemID = $spreadsheet->getActiveSheet()->getCellByColumnAndRow( 14,$i)->getValue();//ItemID
         $TransactionID = $spreadsheet->getActiveSheet()->getCellByColumnAndRow( 15,$i)->getValue();//TransactionID
         $Quantity = $spreadsheet->getActiveSheet()->getCellByColumnAndRow( 16,$i)->getValue();//Quantity
         $ItemCost= $spreadsheet->getActiveSheet()->getCellByColumnAndRow( 17,$i)->getValue();//Item cost
         $Sku = '';
         $Rate = 0;
         if($Type =='Order')
         {
          $Sku = $this->GetSkuFromOrderNumberOnEbay($OrderID,$ItemID);
          $PaymentsProcessing =  $Quantity*$ItemCost * $EbayPPFee;
          DB::connection('mysql')->table('fa_selling_monthly_detail')->insert(
            ['type'=>$Type, 'sku'=> $Sku, 'quantity'=>$Quantity,'price'=>$ItemCost/$Quantity ,'amount'=>$ItemCost ,
            'payment_processing_fee'=> $PaymentsProcessing ,'transaction_id'=>$TransactionID  ,
            'sales_channel'=> $SalesChanel,'store'=>$SalesStore ,'the_month'=>$TheMonth,'the_year'=>$TheYear]);
         }
         elseif($Type =='Refund') // Trả hàng
         {
           $Rate = $this->EqualAmountFromOrderNumberOnEbay($OrderID, $OrderTotal);
           if($Rate==1) // trả hết hàng
           {
            $PaymentsProcessing =  0;
            $sql = " select ebay_order_detail.sku,ebay_order_detail.quantity_purchased ,ebay_order_detail.transaction_price
            from ebay_order inner join ebay_order_detail
            on ebay_order.id = ebay_order_detail.ebay_order_id
            where ebay_order.ebay_extended_order_id = '$OrderID'" ;
            $ds = DB::connection('mysql_it')->select ($sql);
            foreach( $ds as $d )
            {
             DB::connection('mysql')->table('fa_selling_monthly_detail')->insert(
             ['type'=>$Type, 'sku'=>$d->sku, 'quantity'=>$d->quantity_purchased,'price'=>$d->transaction_price,
             'amount'=>  $d->quantity_purchased * $d->transaction_price,
             'payment_processing_fee'=> $PaymentsProcessing  ,'transaction_id'=>$TransactionID ,
             'sales_channel'=> $SalesChanel,'store'=>$SalesStore ,'the_month'=>$TheMonth,'the_year'=>$TheYear]);
            }
           }
           else // trả một phần tiền
           {
            $PaymentsProcessing =  0;
            $sql = " select ebay_order_detail.sku,ebay_order_detail.quantity_purchased ,ebay_order_detail.transaction_price
            from ebay_order inner join ebay_order_detail
            on ebay_order.id = ebay_order_detail.ebay_order_id
            where ebay_order.ebay_extended_order_id = '$OrderID'" ;
            $ds = DB::connection('mysql_it')->select ($sql);
            foreach( $ds as $d )
            {
             DB::connection('mysql')->table('fa_selling_monthly_detail')->insert(
             ['type'=>'Refund-1', 'sku'=>$d->sku, 'quantity'=>0,'price'=>$d->transaction_price * $Rate,
             'amount'=>  $d->quantity_purchased * $d->transaction_price* $Rate,
             'payment_processing_fee'=> $PaymentsProcessing * $Rate ,'transaction_id'=>$TransactionID ,
             'sales_channel'=> $SalesChanel,'store'=>$SalesStore ,'the_month'=>$TheMonth,'the_year'=>$TheYear]);
            }
           }
      }// Trả hàng
    }// For
     // End Import sheet thu 12 => Ebay Infideals

     // Import sheet thu  13=> Ebay Idzo
     $RowBegin = 5;
     $SalesChanel = 6;// Ebay
     $SalesStore = 4;//Ebay Idzo
     $EbayPPFee = 0.027;
     $reader->setLoadSheetsOnly(["Ebay Idzo", "Ebay Idzo"]);
     $spreadsheet = $reader->load($file);
     $RowEnd = $spreadsheet->getActiveSheet()->getHighestRow();
     print_r ( 'Ebay Idzo'.$RowEnd );
     print_r ( '<br>');
     DB::connection('mysql')->select (" delete from fa_selling_monthly_detail where the_month = $TheMonth and the_year = $TheYear
     and sales_channel =  $SalesChanel and store =  $SalesStore " );

     for($i=$RowBegin; $i <= $RowEnd; $i++)
     {
         $Type = $spreadsheet->getActiveSheet()->getCellByColumnAndRow( 2,$i)->getValue();// Type
         $OrderID = $spreadsheet->getActiveSheet()->getCellByColumnAndRow( 4,$i)->getValue();//Order ID
         $OrderTotal = $spreadsheet->getActiveSheet()->getCellByColumnAndRow( 7,$i)->getValue();//OrderTotal
         $ItemID = $spreadsheet->getActiveSheet()->getCellByColumnAndRow( 14,$i)->getValue();//ItemID
         $TransactionID = $spreadsheet->getActiveSheet()->getCellByColumnAndRow( 15,$i)->getValue();//TransactionID
         $Quantity = $spreadsheet->getActiveSheet()->getCellByColumnAndRow( 16,$i)->getValue();//Quantity
         $ItemCost= $spreadsheet->getActiveSheet()->getCellByColumnAndRow( 17,$i)->getValue();//Item cost
         $Sku = '';
         $Rate = 1;
         if(strlen(strstr($Type,'Order'))>0)
         {
          $Sku = $this->GetSkuFromOrderNumberOnEbay($OrderID, $ItemID);
          $PaymentsProcessing = $Quantity*$ItemCost* $EbayPPFee;
          DB::connection('mysql')->table('fa_selling_monthly_detail')->insert(
            ['type'=>$Type, 'sku'=> $Sku, 'quantity'=>$Quantity,'price'=>$ItemCost/$Quantity ,'amount'=>$ItemCost ,
            'payment_processing_fee'=> $PaymentsProcessing ,'transaction_id'=>$TransactionID  ,
            'sales_channel'=> $SalesChanel,'store'=>$SalesStore ,'the_month'=>$TheMonth,'the_year'=>$TheYear]);

         }
         elseif(strlen(strstr($Type,'Refund'))>0)//Trả  hàng
         {
          $Rate = $this->EqualAmountFromOrderNumberOnEbay($OrderID, $OrderTotal);
          if($Rate == 1 )// Trả hết hàng
          {
            $Type ='Full Refund';
            $PaymentsProcessing =0;
            $sql = " select ebay_order_detail.sku,ebay_order_detail.quantity_purchased ,
            ebay_order_detail.transaction_price
            from ebay_order inner join ebay_order_detail
            on ebay_order.id = ebay_order_detail.ebay_order_id
            where ebay_order.ebay_extended_order_id = '$OrderID'" ;
            $ds = DB::connection('mysql_it')->select ($sql);
            foreach( $ds as $d )
            {
              DB::connection('mysql')->table('fa_selling_monthly_detail')->insert(
              ['type'=>$Type, 'sku'=>$d->sku, 'quantity'=>$d->quantity_purchased,'price'=>$d->transaction_price,
              'amount'=>  $d->quantity_purchased * $d->transaction_price,
              'payment_processing_fee'=> $PaymentsProcessing  ,'transaction_id'=>$TransactionID ,
              'sales_channel'=> $SalesChanel,'store'=>$SalesStore ,'the_month'=>$TheMonth,'the_year'=>$TheYear]);
            }
          }
          else // Trả 1 phần tiền hàng
          {
           $Type ='Refund-1';
            $PaymentsProcessing =0;
            $sql = " select ebay_order_detail.sku,ebay_order_detail.quantity_purchased ,
            ebay_order_detail.transaction_price
            from ebay_order inner join ebay_order_detail
            on ebay_order.id = ebay_order_detail.ebay_order_id
            where ebay_order.ebay_extended_order_id = '$OrderID'" ;
            $ds = DB::connection('mysql_it')->select ($sql);
            foreach( $ds as $d )
            {
              DB::connection('mysql')->table('fa_selling_monthly_detail')->insert(
                ['type'=>$Type, 'sku'=>$d->sku, 'quantity'=>0,'price'=>$d->transaction_price * $Rate,
                'amount'=>  $d->quantity_purchased * $d->transaction_price * $Rate,
                'payment_processing_fee'=> $PaymentsProcessing * $Rate ,'transaction_id'=>$TransactionID ,
                'sales_channel'=> $SalesChanel,'store'=>$SalesStore ,'the_month'=>$TheMonth,'the_year'=>$TheYear]);
            }
          }//else Trả 1 phần tiền hàng
         }// Trả hàng
      }// For

    // End Import sheet thu 13 => Ebay Idzo

    // Import sheet thu  14 => UnitCost
    $RowBegin = 3;
    $reader->setLoadSheetsOnly(["Unit Cost", "Unit Cost"]);
    $spreadsheet = $reader->load($file);
    $RowEnd = $spreadsheet->getActiveSheet()->getHighestRow();
    print_r ( 'Unit Cost'.$RowEnd );
    print_r ( '<br>');
    DB::connection('mysql')->select (" delete from fa_unit_costs where the_month = $TheMonth  and the_year = $TheYear " );

    for($i=$RowBegin; $i <= $RowEnd; $i++)
    {
        $Sku = $spreadsheet->getActiveSheet()->getCellByColumnAndRow( 1,$i)->getValue();// sku
        $Cogs = $spreadsheet->getActiveSheet()->getCellByColumnAndRow( 5,$i)->getValue();//COGS

        $Fob = $spreadsheet->getActiveSheet()->getCellByColumnAndRow( 11,$i)->getValue();//FOB
        $Cmb = $spreadsheet->getActiveSheet()->getCellByColumnAndRow( 12,$i)->getValue();//Cmb
        $Weight = $spreadsheet->getActiveSheet()->getCellByColumnAndRow( 13,$i)->getValue();//Weight
        $Duties = $spreadsheet->getActiveSheet()->getCellByColumnAndRow( 14,$i)->getValue();//Duties
        $Pallet = $spreadsheet->getActiveSheet()->getCellByColumnAndRow( 15,$i)->getValue();//Pallet

        DB::connection('mysql')->table('fa_unit_costs')->insert(
        [ 'sku'=> $Sku, 'cogs'=> $Cogs,'fob'=>$Fob,'cmb'=>$Cmb,'weight'=>$Weight,'duties'=>$Duties,
        'pallet'=>$Pallet,'the_month'=>$TheMonth,'the_year'=>$TheYear ]
        );
    }
    // End Import sheet thu 14 => => UnitCost

    // Import sheet thu  15 => SEO-Realsales-SKU = SEO SEM tren amazon
    $RowBegin = 5;
    $reader->setLoadSheetsOnly(["SEO-Realsales-SKU", "SEO-Realsales-SKU"]);
    $spreadsheet = $reader->load($file);
    $RowEnd = $spreadsheet->getActiveSheet()->getHighestRow();
    print_r ( 'SEO-Realsales-SKU'.$RowEnd );
    print_r ( '<br>');
    DB::connection('mysql')->select (" delete from fa_sem_seo_amazon where the_month = $TheMonth  and the_year = $TheYear " );

    for($i=$RowBegin; $i <= $RowEnd; $i++)
    {
        $Sku = $spreadsheet->getActiveSheet()->getCellByColumnAndRow( 1,$i)->getValue();// sku
        $SEOFee = $spreadsheet->getActiveSheet()->getCellByColumnAndRow( 10,$i)->getValue();//SEO Fee

        if($SEOFee >0){
          DB::connection('mysql')->table('fa_sem_seo_amazon')->insert(
              [ 'sku'=> $Sku, 'sem_fee_per_unit'=>$SEOFee,'the_month'=>$TheMonth,'the_year'=>$TheYear ]);
        }
    }
    // End Import sheet thu 15 => SEO-Realsales-SKU = SEO SEM tren amazon

    // Import sheet thu  16 => Promotion Accrual = Promotion on amazon
    $RowBegin =4;
    $reader->setLoadSheetsOnly(["Promotion Accrual", "Promotion Accrual"]);
    $spreadsheet = $reader->load($file);
    $RowEnd = $spreadsheet->getActiveSheet()->getHighestRow();
    print_r ( 'Promotion Accrual RowEnd'.$RowEnd );
    print_r ( '<br>');
    DB::connection('mysql')->select (" delete from fa_amazon_promotions where the_month = $TheMonth  and the_year = $TheYear " );

    for($i=$RowBegin; $i <= $RowEnd; $i++)
    {
      $InvoiceNumber = $spreadsheet->getActiveSheet()->getCellByColumnAndRow( 1,$i)->getValue(); // Invoice Number
      $Rebate = $spreadsheet->getActiveSheet()->getCellByColumnAndRow( 14,$i)->getValue();//Rebate
      $Sku = $spreadsheet->getActiveSheet()->getCellByColumnAndRow( 18,$i)->getValue();// sku
      $Vendor = $spreadsheet->getActiveSheet()->getCellByColumnAndRow( 20,$i)->getValue();//Vendor

      DB::connection('mysql')->table('fa_amazon_promotions')->insert(
      [ 'sku'=> $Sku, 'vendor_code'=>$Vendor,'rebate'=>$Rebate ,'invoice_number'=>$InvoiceNumber,
      'the_month'=>$TheMonth,'the_year'=>$TheYear ]);
    }
    // End Import sheet thu 16 => Promotion Accrual = Promotion on amazon

    // Import sheet thu  17 => Promotion Coupon Clips
    $RowBegin = 3;
    $reader->setLoadSheetsOnly(["Promotion Coupon Clips", "Promotion Coupon Clips"]);
    $spreadsheet = $reader->load($file);
    $RowEnd = $spreadsheet->getActiveSheet()->getHighestRow();
    print_r ( 'Promotion Coupon Clips RowEnd'.$RowEnd );
    print_r ( '<br>');
    DB::connection('mysql')->select (" delete from fa_promotion_coupon_clips where the_month = $TheMonth  and the_year = $TheYear " );

    for($i=$RowBegin; $i <= $RowEnd; $i++)
    {
        $cellValue1 = $spreadsheet->getActiveSheet()->getCellByColumnAndRow( 1,$i)->getValue();// Invoice
        $cellValue2 = $spreadsheet->getActiveSheet()->getCellByColumnAndRow( 9,$i)->getValue();//Total Clip
        if($cellValue1!="" ){
          DB::connection('mysql')->table('fa_promotion_coupon_clips')->insert(
              [ 'invoice'=> $cellValue1, 'total_clip'=>$cellValue2,'the_month'=>$TheMonth,'the_year'=>$TheYear ]
          );
        }
    }
    // End Import sheet thu 17 => Promotion Coupon Clips

     // Import sheet thu  18 => Website
     $RowBegin = 5;
     $SalesChanel = 8;// Website
     $reader->setLoadSheetsOnly(["Website", "Website"]);
     $spreadsheet = $reader->load($file);
     $RowEnd = $spreadsheet->getActiveSheet()->getHighestRow();
     print_r ( 'Website RowEnd'.$RowEnd );
     print_r ( '<br>');
     DB::connection('mysql')->select (" delete from fa_selling_monthly_detail
     where the_month = $TheMonth and the_year = $TheYear  and sales_channel = $SalesChanel" );

     for($i=$RowBegin; $i <= $RowEnd; $i++)
     {
         $OrderID = $spreadsheet->getActiveSheet()->getCellByColumnAndRow( 2,$i)->getValue();// OrrderID
         $Sku = $spreadsheet->getActiveSheet()->getCellByColumnAndRow( 22,$i)->getValue();// sku
         $Quantiy = $spreadsheet->getActiveSheet()->getCellByColumnAndRow( 25,$i)->getValue();//quantity
         $Price = $spreadsheet->getActiveSheet()->getCellByColumnAndRow( 24,$i)->getValue();//Price
         $Revenue = $Quantiy * $Price;
         $PaypalFee = $this->GetPaypalFeeForWebsite($OrderID, $Sku,$TheMonth,$TheYear);
         DB::connection('mysql')->table('fa_selling_monthly_detail')->insert(
         [ 'sku'=>$Sku, 'quantity'=>$Quantiy ,'price'=>$Price,'amount'=> $Revenue ,'paypal_fee'=>  $PaypalFee  ,
         'the_month'=>$TheMonth,'the_year'=>$TheYear,'sales_channel'=> $SalesChanel]  );
     }

     // End Import sheet thu 18 => Website
     // Import sheet thu  19 => Real sale AMZ -> Import thông tin bán hàng kênh avc-WH và avc-DI

     $RowBegin = 6;

     $reader->setLoadSheetsOnly(["Real sale AMZ", "Real sale AMZ"]);
     $spreadsheet = $reader->load($file);
     $RowEnd = $spreadsheet->getActiveSheet()->getHighestRow();
     print_r ( 'Real sale AMZ RowEnd'.$RowEnd );
     print_r ( '<br>');
     DB::connection('mysql')->select (" delete from fa_amazon_real_sales where the_month = $TheMonth and the_year = $TheYear " );

     for($i=$RowBegin; $i <= $RowEnd; $i++)
     {
         $Sku = $spreadsheet->getActiveSheet()->getCellByColumnAndRow(1,$i)->getValue();// sku
         $AVC_WH_RS = $spreadsheet->getActiveSheet()->getCellByColumnAndRow(6,$i)->getValue();// AVC-WH_RS
         //$AVC_DS_RS = $spreadsheet->getActiveSheet()->getCellByColumnAndRow(7,$i)->getValue();// AVC-DS_RS
         $AVC_DI_RS = $spreadsheet->getActiveSheet()->getCellByColumnAndRow(8,$i)->getValue();// AVC_DI_RS

         $AVC_WH_REV = $spreadsheet->getActiveSheet()->getCellByColumnAndRow(12,$i)->getValue();// AVC-WH_REV
         //$AVC_DS_REV = $spreadsheet->getActiveSheet()->getCellByColumnAndRow(13,$i)->getValue();//AVC-DS_REV
         $AVC_DI_REV = $spreadsheet->getActiveSheet()->getCellByColumnAndRow(14,$i)->getValue();//AVC_DI_REV

         $Total= $spreadsheet->getActiveSheet()->getCellByColumnAndRow(15,$i)->getValue();//Total

        if( $Total>0)
        {
          DB::connection('mysql')->table('fa_amazon_real_sales')->insert(
          ['sku'=>$Sku,'avc_wh_rs'=>$AVC_WH_RS, 'di_rs'=>$AVC_DI_RS,'avc_wh_rev'=>$AVC_WH_REV,
          'di_rev'=>$AVC_DI_REV,'the_month'=>$TheMonth,'the_year'=>$TheYear]);
        }
     }
     // End Import sheet thu 19 => Real sale AMZ
      //Import sheet thu  21 => Shipment_realtime_report

      $RowBegin = 4;
      $reader->setLoadSheetsOnly(["Shipment_realtime_report", "Shipment_realtime_report"]);
      $spreadsheet = $reader->load($file);
      $RowEnd = $spreadsheet->getActiveSheet()->getHighestRow();

      print_r ( 'Shipment_realtime_report RowEnd '.$RowEnd );
      print_r ( '<br>');
      DB::connection('mysql')->select (" delete from fa_shipment_realtime_reports where the_month = $TheMonth and the_year = $TheYear " );

      for($i=$RowBegin; $i <= $RowEnd; $i++)
      {
          $TransactionID = $spreadsheet->getActiveSheet()->getCellByColumnAndRow(3,$i)->getValue();// transaction_id
          $TransactionID = str_replace('"','',$TransactionID);
          $TransactionID = str_replace('=','',$TransactionID);
          $TrackingInsurance = $spreadsheet->getActiveSheet()->getCellByColumnAndRow(4,$i)->getValue();// Tracking Insurance
          $TrackingInsurance= str_replace('"','',$TrackingInsurance);
          $TrackingInsurance= str_replace('=','',$TrackingInsurance);
          $StoreName = $spreadsheet->getActiveSheet()->getCellByColumnAndRow(5,$i)->getValue();//Storename
          $Cost = $spreadsheet->getActiveSheet()->getCellByColumnAndRow(6,$i)->getValue();//cost
          $Status = $spreadsheet->getActiveSheet()->getCellByColumnAndRow(8,$i)->getValue();//status
          $MemoHai = $spreadsheet->getActiveSheet()->getCellByColumnAndRow(12,$i)->getValue();//memo 2
          $MemoBa = $spreadsheet->getActiveSheet()->getCellByColumnAndRow(13,$i)->getValue();//memo 3

          $sql = " select count(id) as MyCount from fa_shipment_realtime_reports
          where transaction_id = '$TransactionID' and tracking_insurance = '$TrackingInsurance' ";

          if($this->IsExist('mysql', $sql)== 0)
          {
            DB::connection('mysql')->table('fa_shipment_realtime_reports')->insert(
            ['transaction_id'=> $TransactionID,'tracking_insurance'=>$TrackingInsurance,'store_name'=>$StoreName,'cost'=>$Cost,'status'=>$Status,
            'memohai'=> $MemoHai,'memoba'=> $MemoBa,'the_month'=>$TheMonth,'the_year'=>$TheYear]);
          }
          else
          {
            print_r('TransactionID: '. $TransactionID . 'Isr ID'. $TrackingInsurance );
            print_r('<br>' );
          }
      }

      // if($TheMonth ==1)
      // {
      //   $LastMonth =12 ;
      //   $LastYear = $TheYear - 1 ;
      // }
      // else
      // {
      //   $LastMonth = $TheMonth-1;
      //   $LastYear = $TheYear ;
      // }
      // $DateBeginLastMonth = (string)$LastYear . '-' .  (string)$LastMonth .'-01';

      // DB::connection('mysql')->select (" delete from fa_shipment_realtime_reports where the_month = $TheMonth and the_year = $TheYear " );

      // $sql = " SELECT transaction_id,store_id,store_name_shipworks,cost, status, print_message as memo_1, print_message_2 as memo_2 , print_message_3 as memo_3
      // FROM shipping_invoices_realtime as sh
      // WHERE print_date >= '$DateBeginLastMonth'  and print_date <= '$DateEndOfMonth'
      // GROUP BY transaction_id ";
      // $ds= DB::connection('mysql_it')->select($sql);

      // foreach($ds as $d){
      //   $memo_2 = str_replace('-',' ',$d->memo_2);
      //   DB::connection('mysql')->table('fa_shipment_realtime_reports')->insert(
      //   ['transaction_id'=>$d->transaction_id,'store_name'=>$d->store_name_shipworks,'cost'=>$d->cost,'status'=>$d->status,
      //   'memomot'=>$d->memo_1,'memohai'=>$memo_2,'memoba'=>$d->memo_3,'the_month'=>$TheMonth,'the_year'=>$TheYear]);
      // }

      // End Import sheet thu 21 => Shipment_realtime_report

      // Import sheet thu  22 => DIP
     // $RowBegin = 5;
      //$reader->setLoadSheetsOnly(["DIP", "DIP"]);
     // $spreadsheet = $reader->load($file);
     // $RowEnd = $spreadsheet->getActiveSheet()->getHighestRow();
     // print_r ( 'DIP RowEnd'.$RowEnd );
     // print_r ( '<br>');

      DB::connection('mysql')->select (" delete from fa_dip_monthly where the_month = $TheMonth and the_year = $TheYear " );

      $sql =" SELECT p.product_sku as sku, sum(fd.dip) as dip FROM fbashipments f
      inner join fbashipment_details  fd on  f.id = fd.fbashipment_id  LEFT JOIN products p ON fd.product_id=p.id
      WHERE  f.shipped_date >= '$DateBeginOfMonth' and  date(f.shipped_date) <= '$DateEndOfMonth'
      GROUP BY p.product_sku";

      $ds= DB::connection('mysql_it')->select($sql);
      foreach($ds as $d){
        DB::connection('mysql')->table('fa_dip_monthly')->insert(['sku'=>$d->sku,'dip'=>$d->dip,'the_month'=>$TheMonth,'the_year'=>$TheYear]);
      }
      // End Import sheet thu 22 => DIP

      // Import sheet thu  23 => ChargebackAndOther
      $RowBegin = 3;
      $reader->setLoadSheetsOnly(["ChargebackAndOther", "ChargebackAndOther"]);
      $spreadsheet = $reader->load($file);
      $RowEnd = 16; // $spreadsheet->getActiveSheet()->getHighestRow();
      print_r ( 'ChargebackAndOther RowEnd '.$RowEnd );
      print_r ( '<br>');
      DB::connection('mysql')->select (" delete from fa_summary_chargeback_monthly where the_month = $TheMonth and the_year = $TheYear " );

      for($i=$RowBegin; $i <= $RowEnd; $i++)
      {
          $SalesChannelID = $spreadsheet->getActiveSheet()->getCellByColumnAndRow(1,$i)->getValue();// Sales ID
          $SalesChannelName = $spreadsheet->getActiveSheet()->getCellByColumnAndRow(2,$i)->getValue();// Sales Channel
          $Store = $spreadsheet->getActiveSheet()->getCellByColumnAndRow(3,$i)->getValue();// Store
          $Chargeback = $spreadsheet->getActiveSheet()->getCellByColumnAndRow(4,$i)->getValue();// Chargeback
          $FHReturn = $spreadsheet->getActiveSheet()->getCellByColumnAndRow(5,$i)->getValue();// Freight cost return and handling return
          $EayFinalFee = $spreadsheet->getActiveSheet()->getCellByColumnAndRow(6,$i)->getValue();// ebay_final_fee
          $PaypalFee = $spreadsheet->getActiveSheet()->getCellByColumnAndRow(7,$i)->getValue();// paypal_fee
          $Vine = $spreadsheet->getActiveSheet()->getCellByColumnAndRow(8,$i)->getValue();//Vine
          $MSF = $spreadsheet->getActiveSheet()->getCellByColumnAndRow(9,$i)->getValue();// MSF
          $Insurance = $spreadsheet->getActiveSheet()->getCellByColumnAndRow(15,$i)->getValue();// Product Liability Insurance
          $SEMFee = $spreadsheet->getActiveSheet()->getCellByColumnAndRow(16,$i)->getValue();// SEM fee
          $ShippingFee = $spreadsheet->getActiveSheet()->getCellByColumnAndRow(17,$i)->getValue();// SEM fee
          $OtherFee = $spreadsheet->getActiveSheet()->getCellByColumnAndRow(18,$i)->getValue();// Other Fee
          $ReferalFee = $spreadsheet->getActiveSheet()->getCellByColumnAndRow(19,$i)->getValue();// Referal Fee

          DB::connection('mysql')->table('fa_summary_chargeback_monthly')->insert(
              ['sales_channel'=>$SalesChannelID,'sales_channel_name'=> $SalesChannelName,'store'=>$Store,
              'chargeback_fee'=>$Chargeback,'freight_handling_return_cost'=>$FHReturn,'other_fee'=>$OtherFee,
              'liability_insurance'=> $Insurance,'msf'=>$MSF,'sem_fee'=> $SEMFee,'ebay_final_fee'=>$EayFinalFee,
              'paypal_fee'=> $PaypalFee ,'vine'=> $Vine,'referal_fee'=>$ReferalFee,'the_month'=>$TheMonth,'the_year'=>$TheYear]
          );
      }
      // End Import sheet thu 23 => Chargeback

       // Import sheet thu  25 => Craigslist Orders
       $SalesChanel = 7;// Craiglist/Local
       $RowBegin = 4;
       $reader->setLoadSheetsOnly(["Craigslist Orders", "Craigslist Orders"]);
       $spreadsheet = $reader->load($file);
       $RowEnd = $spreadsheet->getActiveSheet()->getHighestRow();

       print_r ( 'Craigslist Orders'.$RowEnd );
       print_r ( '<br>');
       DB::connection('mysql')->select (" delete from fa_selling_monthly_detail where the_month = $TheMonth and the_year = $TheYear
       and sales_channel = $SalesChanel " );

       for($i=$RowBegin; $i <= $RowEnd; $i++)
       {
           $Sku = $spreadsheet->getActiveSheet()->getCellByColumnAndRow(1,$i)->getValue();// sku
           $Quantity = $spreadsheet->getActiveSheet()->getCellByColumnAndRow(3,$i)->getValue();// quantity
           $Amount = $spreadsheet->getActiveSheet()->getCellByColumnAndRow(7,$i)->getValue();// amount
           $Price = $Amount/$Quantity;
           $Discount = $spreadsheet->getActiveSheet()->getCellByColumnAndRow(8,$i)->getValue();// discount
           $Cogs = $this->GetCOGS($Sku,$TheYear, $TheMonth);
           $wo = 0;
           if($Price <$Cogs ){ $wo =1;}
           DB::connection('mysql')->table('fa_selling_monthly_detail')->insert(
           ['sku'=>$Sku,'quantity'=>$Quantity,'amount'=>$Amount,'price'=>$Price,'discount_value'=>$Discount,'wo'=> $wo,
           'sales_channel'=> $SalesChanel ,'the_month'=>$TheMonth,'the_year'=>$TheYear]);
       }
       // End Import sheet thu 25 => Craigslist Orders

        // Import sheet thu  26 => Shiping Cost
        $RowBegin = 9;
        $reader->setLoadSheetsOnly(["Shiping Cost", "Shiping Cost"]);
        $spreadsheet = $reader->load($file);
        $RowEnd = $spreadsheet->getActiveSheet()->getHighestRow();
        print_r ( 'Shiping Cost'.$RowEnd );
        print_r ( '<br>');
        DB::connection('mysql')->select (" delete from fa_shiping_cost where the_month = $TheMonth and the_year = $TheYear " );

        for($i=$RowBegin; $i <= $RowEnd; $i++)
        {
          $cellValue1 = $spreadsheet->getActiveSheet()->getCellByColumnAndRow(2,$i)->getValue();// sku
          $cellValue2 = $spreadsheet->getActiveSheet()->getCellByColumnAndRow(18,$i)->getValue();// Shiping cost

          if(is_numeric($cellValue2)and  $cellValue1 !=''){
          DB::connection('mysql')->table('fa_shiping_cost')->insert(
              ['sku'=> $cellValue1,'shiping_cost'=>$cellValue2,'the_month'=>$TheMonth,'the_year'=>$TheYear]);
          }
        }

        // End Import sheet  26 => Shiping Cost

        // Import sheet thu  27 => AVC-WH-INVOICE_DT_MS
      //  $SalesChannel = 1;
      //  $ByInvoice = 1;
        $RowBegin = 4;
        $reader->setLoadSheetsOnly(["AVC-WH-INVOICE_DT_MS", "AVC-WH-INVOICE_DT_MS"]);
        $spreadsheet = $reader->load($file);
        $RowEnd = $spreadsheet->getActiveSheet()->getHighestRow();
        print_r ( 'AVC-WH-INVOICE_DT_MS'.$RowEnd );
        print_r ( '<br>');

        DB::connection('mysql')->select (" delete from fa_avc_wh_order_missing   where the_month = $TheMonth and the_year = $TheYear " );

        for($i=$RowBegin; $i <= $RowEnd; $i++)
        {
          $PONO = $spreadsheet->getActiveSheet()->getCellByColumnAndRow(1,$i)->getValue();//PONO
          $Asin = $spreadsheet->getActiveSheet()->getCellByColumnAndRow(4,$i)->getValue();// Asin
          $Sku = $spreadsheet->getActiveSheet()->getCellByColumnAndRow(5,$i)->getValue();// Sku
          $Quantity = $spreadsheet->getActiveSheet()->getCellByColumnAndRow(7,$i)->getValue();// Quantity
          $UnitCost = $spreadsheet->getActiveSheet()->getCellByColumnAndRow(8,$i)->getValue();// UnitCost
          $Amount = $spreadsheet->getActiveSheet()->getCellByColumnAndRow(9,$i)->getValue();// Amount

          if(strlen($Sku)<>4){    $Sku = $this->GetSkuFromAsin($Asin);  }

          DB::connection('mysql')->table('fa_avc_wh_order_missing')->insert(
            ['po_no'=>$PONO ,'asin'=>$Asin,'sku'=>$Sku,'quantity'=>$Quantity,'unit_cost'=>$UnitCost,'amount'=>$Amount ,
            'the_month'=>$TheMonth,'the_year'=>$TheYear]);
        }  // End Import sheet  27 => AVC-WH-INVOICE_DT_MS


        //AVC-WH-INVOICE
        // Import sheet thu  28 => AVC-WH-INVOICE

         $SalesChannel = 1;
         $ByInvoice = 1;
         $RowBegin = 3;
         $reader->setLoadSheetsOnly(["AVC-WH-INVOICE", "AVC-WH-INVOICE"]);
         $spreadsheet = $reader->load($file);
         $RowEnd = $spreadsheet->getActiveSheet()->getHighestRow();
         print_r ( 'AVC-WH-INVOICE'.$RowEnd );
         print_r ( '<br>');

         DB::connection('mysql')->select (" delete from fa_selling_monthly_detail
         where sales_channel = 1 and by_invoice = 1 and the_month = $TheMonth and the_year = $TheYear " );

         for($i=$RowBegin; $i <= $RowEnd; $i++)
         {
           $InvoiceDate = $spreadsheet->getActiveSheet()->getCellByColumnAndRow(2,$i)->getValue();//InvoiceDate
           $InvoiceNo = $spreadsheet->getActiveSheet()->getCellByColumnAndRow(9,$i)->getValue();// InvoiceNo
           $Amount = $spreadsheet->getActiveSheet()->getCellByColumnAndRow(10,$i)->getValue();// Amount
           $this->InsertDetailAvcWhOrderByInvoice($InvoiceNo,$Amount,$SalesChannel,$ByInvoice, $TheYear,$TheMonth);
         }  // End Import sheet  28 => AVC-WH-INVOICE

         // Import sheet thu  29 AVC-DI-INVOICE-DT
         $SalesChannel = 3;
         $ByInvoice = 1;
         $RowBegin = 4;
         $reader->setLoadSheetsOnly(["AVC-DI-INVOICE-DT", "AVC-DI-INVOICE-DT"]);
         $spreadsheet = $reader->load($file);
         $RowEnd = $spreadsheet->getActiveSheet()->getHighestRow();
         //$RowEnd = 4;
         print_r ( 'AVC-DI-INVOICE-DT:'.$RowEnd );
         print_r ( '<br>');

         DB::connection('mysql')->select (" delete from fa_selling_monthly_detail
         where sales_channel = 3 and by_invoice = 1 and the_month = $TheMonth and the_year = $TheYear " );

         for($i=$RowBegin; $i <= $RowEnd; $i++)
         {
           $POID = $spreadsheet->getActiveSheet()->getCellByColumnAndRow(1,$i)->getValue();// POID
           $Asin = $spreadsheet->getActiveSheet()->getCellByColumnAndRow(4,$i)->getValue();// Asin
           $Sku = $spreadsheet->getActiveSheet()->getCellByColumnAndRow(5,$i)->getValue();// Sku
           $Quantity = $spreadsheet->getActiveSheet()->getCellByColumnAndRow(7,$i)->getValue();// Quantity
           $UnitCost = $spreadsheet->getActiveSheet()->getCellByColumnAndRow(8,$i)->getValue();// UnitCost
           $Amount = $spreadsheet->getActiveSheet()->getCellByColumnAndRow(9,$i)->getValue();// Amount

           if(strlen($Sku)<>4){ $Sku = $this->GetSkuFromAsin($Asin);}

           DB::connection('mysql')->table('fa_selling_monthly_detail')->insert(
            ['sku'=>$Sku,'po_id'=>$POID,'asin'=>$Asin,'quantity'=> $Quantity,'price'=> $UnitCost,'amount'=>$Amount,
            'sales_channel'=>$SalesChannel,'by_invoice'=>$ByInvoice,'the_month'=>$TheMonth,'the_year'=>$TheYear]);
         }  // End Import sheet  AVC-DI-INVOICE-DT


         */

        $RowBegin = 3;
        $reader->setLoadSheetsOnly(["AVC-DI-COST", "AVC-DI-COST"]);
        $spreadsheet = $reader->load($file);
        $RowEnd = $spreadsheet->getActiveSheet()->getHighestRow();

        print_r ( 'AVC-DI-COST:'.$RowEnd );
        print_r ( '<br>');

        DB::connection('mysql')->select (" delete from tmp_di_unit_cost" );

        for($i=$RowBegin; $i <= $RowEnd; $i++)
        {
          $Sku = $spreadsheet->getActiveSheet()->getCellByColumnAndRow(4,$i)->getValue();// Sku
          $UnitCost = $spreadsheet->getActiveSheet()->getCellByColumnAndRow(14,$i)->getValue();// Asin
          DB::connection('mysql')->table('tmp_di_unit_cost')->insert(['sku'=>$Sku,'unit_cost'=>$UnitCost,
          'the_month'=>$TheMonth ,'the_year'=> $TheYear]);
        }  // End Import sheet  AVC-DI-INVOICE-DT

        /*

        // Import sheet thu  30 AVC DS CHECK
        //  $RowBegin = 4;
        //  $reader->setLoadSheetsOnly(["AVC DS CHECK", "AVC DS CHECK"]);
        //  $spreadsheet = $reader->load($file);
        //  $RowEnd = $spreadsheet->getActiveSheet()->getHighestRow();

        //  print_r ( 'AVC DS CHECK:'.$RowEnd );
        //  print_r ( '<br>');

        //  DB::connection('mysql')->select (" delete from fa_avc_ds_check
        //  where  the_month = $TheMonth and the_year = $TheYear " );

        //  for($i=$RowBegin; $i <= $RowEnd; $i++)
        //  {
        //    $OrderID = $spreadsheet->getActiveSheet()->getCellByColumnAndRow(1,$i)->getValue();//Order ID
        //    $Asin = $spreadsheet->getActiveSheet()->getCellByColumnAndRow(20,$i)->getValue();// Asin
        //    $Sku = $spreadsheet->getActiveSheet()->getCellByColumnAndRow(19,$i)->getValue();// Sku
        //    $Quantity = $spreadsheet->getActiveSheet()->getCellByColumnAndRow(22,$i)->getValue();// Quantity
        //    $Price = $spreadsheet->getActiveSheet()->getCellByColumnAndRow(18,$i)->getValue();// Price
        //    $Amount = $spreadsheet->getActiveSheet()->getCellByColumnAndRow(27,$i)->getValue();// $Amount

        //    if(strlen($Sku)<>4 ) { $Sku =$this->GetSkuFromAsin( $Asin);}

        //    DB::connection('mysql')->table('fa_avc_ds_check')->insert(
        //    ['sku'=>$Sku,'order_id'=>$OrderID,'asin'=>$Asin,'sku'=>$Sku,
        //    'quantity'=>$Quantity,'price'=> $Price,'amount'=>$Amount ,'the_month'=>$TheMonth,'the_year'=>$TheYear]);
        //   }
*/
      }// end pass
    else
    {
      print_r ( 'Không thực hiện import được ');
    }
    $this->ConvertDataToSummary($TheMonth,$TheYear);
    $this->MoveDataFromBE3ToBPDAndUpdateDepartment($TheYear,$TheMonth);
    // $this->CheckPriceOnWM_DSV($TheYear,$TheMonth);
    //$this->UpdateDepartment($TheMonth,$TheYear);// Cập nhật phòng ban cho từng sku trong bảng tổng hợp
    print_r('Hoàn tất tại : '.date('Y-m-d H:i:s'));
    print_r('<br>');
}// end fucntion
//---------------------------------------------------
public function CheckTongTienTungAVC_DS_Order()
{//$Year,$Month
  ini_set('memory_limit','1548M');
  set_time_limit(15000);
    // Câu sql tìm order id có bên này mà không có bên kia
    // select distinct(order_id),1 as dep from fa_selling_monthly_detail
    // where the_month = 5 and the_year =2020 and sales_channel = 2
    // and order_id not in
    // (
    // select distinct(order_id) from fa_avc_ds_check where the_month =5 and the_year =2020
    // )

    // union all

    // select distinct(order_id),2 as dep  from fa_avc_ds_check where the_month =5 and the_year =2020
    // and order_id not in
    // (
    // select distinct(order_id) from fa_selling_monthly_detail
    // where the_month = 5 and the_year =2020 and sales_channel = 2
    // )

  $Year = 2020;
  $Month = 5;
  $TongTienHoaDonBenBPD=0;
  $TongTienHoaDonBenAC = 0;
  $sql = "select distinct(order_id) as order_id , sum(amount) as amount from fa_selling_monthly_detail
  where the_month = $Month and the_year = $Year and sales_channel = 2 and my_count = 0 group by order_id ";
  $ds = DB::connection('mysql')->select ($sql);
  foreach($ds as $d )
  {
    $sql = " select sum(amount) as amount from fa_avc_ds_check
    where the_month = $Month and the_year = $Year  and order_id = '$d->order_id'";
    $d1s = DB::connection('mysql')->select ($sql);
    foreach($d1s as $d1 ){ $TongTienHoaDonBenAC = round($d1->amount,2);}

    if($TongTienHoaDonBenAC <>$d->amount)
    {
      print_r('Order ID: '. $d->order_id. 'BPD AMount:'. $d->amount. 'AC Amount:'.$TongTienHoaDonBenAC);
      print_r('<br>');
    }
  }
}
public function InsertDetailAvcWhOrderByInvoice($InvoiceNo,$Amount,$SalesChannel,$ByInvoice, $Year,$Month)
{
  // Chưa kiểm tổng số Amount có khớp không
  $PONO= substr($InvoiceNo, 0,8);
  $AmountFromDetail =0;
  $sql = " SELECT sum(amazon_avc_order_details.accepted_quantity * amazon_avc_order_details.unit_cost) as amount
  FROM amazon_avc_orders
  INNER JOIN amazon_avc_order_details  ON amazon_avc_orders.id = amazon_avc_order_details.amazon_avc_id
  WHERE amazon_avc_order_details.accepted_quantity > 0 and amazon_avc_orders.po_title = '$PONO'";

  $ds = DB::connection('mysql_it')->select ($sql);
  foreach($ds as $d )   { $AmountFromDetail  = round($d->amount,2); }

  // if( $AmountFromDetail <>$Amount )
  // {
  //   print_r('Invoive No: ' . $InvoiceNo);
  //   print_r('<br>');
  // }

  if( $AmountFromDetail <>$Amount )
  {
     $sql = " select sku, asin, quantity, unit_cost, amount from fa_avc_wh_order_missing
     where 	the_month =  $Month and the_year = $Year and po_no = '$PONO' ";
     $ds = DB::connection('mysql')->select ($sql);

     foreach($ds as $d )
     {
       DB::connection('mysql')->table('fa_selling_monthly_detail')->insert(
       ['asin'=>$d->asin,'sku'=>$d->sku,'quantity'=>$d->quantity, 'price'=> $d->unit_cost,
       'amount'=>$d->amount,'sales_channel'=>$SalesChannel,
       'by_invoice'=>$ByInvoice,'the_month'=>$Month,'the_year'=>$Year]);
     }
  }
  else
  {
    $sql = " SELECT  amazon_avc_order_details.asin, amazon_avc_order_details.wh_confirm as quantity,
    amazon_avc_order_details.unit_cost,  (amazon_avc_order_details.accepted_quantity * amazon_avc_order_details.unit_cost) as amount
    FROM amazon_avc_orders
    JOIN amazon_avc_order_details  ON amazon_avc_orders.id = amazon_avc_order_details.amazon_avc_id
    WHERE amazon_avc_order_details.accepted_quantity > 0 and amazon_avc_orders.po_title = '$PONO'";
    $ds = DB::connection('mysql_it')->select ($sql);

    $Sku = '';
    foreach($ds as $d )
    {
      $Sku = $this->GetSkuFromAsin($d->asin);
      DB::connection('mysql')->table('fa_selling_monthly_detail')->insert(
      ['asin'=>$d->asin,'sku'=>$Sku,'quantity'=>$d->quantity, 'price'=> $d->unit_cost,'amount'=>$d->amount,'sales_channel'=>$SalesChannel,
      'by_invoice'=>$ByInvoice,'the_month'=>$Month,'the_year'=>$Year]);
    }

  }

}
//---------------------------------------------------


//---------------------------------------------------
public function GetFirtDateOfMonth($Year,$Month)
{
  return  (string)$Year . '-'. (string)$Month. '-01:00:00:00';
}
//---------------------------------------------------
public function GetLastDateOfMonth($Year,$Month)
{
  print_r('The Year'.$Year );
  print_r('<br>' );
  $Result = date("Y-m-d H:i:s");
  $sql = " SELECT GetLastDateOfMonth($Year,$Month) as Result ";
  $ds = DB::connection('mysql')->select ( $sql);
  foreach($ds as $d)  {  $Result = $d->Result; }
  return  $Result;
}

//---------------------------------------------------
public function GetSkuFromAsin($Asin)
{
  $Result = '';
/*
  $sql = " select  products.product_sku as sku from products
  inner join  amazon_products on products.id = amazon_products.product_id
  inner join  asin on amazon_products.asin = asin.asin
  where asin.asin = '$Asin' and LENGTH(products.product_sku) = 4 " ;
*/

$sql = " select  prd_product.product_sku as sku from prd_product
  inner join  prd_amazons on prd_product.id = prd_amazons.product_id
  inner join  prd_asin on prd_amazons.asin = prd_asin.asin
  where prd_asin.asin = '$Asin' and LENGTH(prd_product.product_sku) = 4 " ;

  $ds = DB::connection('mysql')->select($sql);
  foreach($ds as $d){$Result =$this->iif(is_null($d->sku),'',$d->sku);}
  if( strlen($Result)> 4 )
  {
     print_r('Khong tim thay sku tu asin: '. $Asin );
     print_r('<br>');
  }
  return $Result;
}
//---------------------------------------------------
public function GetSkuFromAmazonOderID($OrderID,$SellerSku)
{
  $Result = '';
  $Asin ='';
  $sql = " select Odt.asin
  from amazon_orders as O inner join amazon_order_details as Odt
  on  O.id  =  Odt.amazon_order_id
  where O.amazon_order_id = '$OrderID'
  and Odt.seller_sku = '$SellerSku' " ;

  $ds = DB::connection('mysql_it')->select($sql);
  foreach($ds as $d){  $Asin = $d->asin; }

  $sql = "  select p.product_sku as sku
  from prd_product as p inner join prd_amazons pa on p.id = pa.product_id
  inner join prd_asin pas on pa.asin = pas.asin where pa.asin = '$Asin' limit 1 ";

  $ds =  DB::connection('mysql')->select($sql);
  foreach($ds as $d){  $Result = $d->sku; }
  return $Result;
}
//---------------------------------------------------
public function GetSkuFromOrderNumberOnEbay($OrderID,$ItemID)
{
  $Result='';
  $sql = "  select Odt.sku
  from ebay_order as O inner join ebay_order_detail as Odt   on O.id = Odt.ebay_order_id
  where O.ebay_extended_order_id = '$OrderID'   and Odt.item_id  = '$ItemID'";
  $ds = DB::connection('mysql_it')->select($sql);
  foreach($ds as $d){  $Result = $d->sku; }
  return $Result;
}
//---------------------------------------------------
public function  InsertOrderDetailByPaypalTransaction($Type,$TransactionID,$ParentTransactionID,$Gross,$Net,$PaypalFee, $SalesChanel,$SalesStore,$TheMonth,$TheYear,$IsOrder,$IsEbay)
{
  $SubProductAmount = 0; // Tổng tiền bán hàng thuần (Số lượng * đơn giá)
  $SalesTaxAndOtherFee = 0;// Tổng tiền thuế và các chi phí khác

  $RatePaypalFee = 0; // Tỷ lệ phí cho paypal trên doanh số bán hàng của transaction
  $RareSalesTaxAndOtherFee = 0;// Tỷ lệ thuế và chi phí khác trên doanh số bán hàng của Transaction

  if(($IsEbay)&&($IsOrder))
  {
    print_r('tổng tiền trên hóa đơn'. $Gross);
    print_r('<br>');

    // Tính tổng tiền hàng
    $SubProductAmount = $this->GetAmountOfProductOnEbay($TransactionID);
    print_r('tổng tiền hàng'.$SubProductAmount );
    print_r('<br>');

    // Tính chi phí thuế và các chi phí khác
    $SalesTaxAndOtherFee = $Gross - $SubProductAmount;
    print_r('tổng tiền các chi phí'. $SalesTaxAndOtherFee);
    print_r('<br>');

    // Tỷ lê Thuế và chi phí khác trên tiền hàng
   if($SubProductAmount > 0)
   {
       // Vì paypal fee là số âm
       $Rate = ($SalesTaxAndOtherFee - $PaypalFee)/$SubProductAmount ;
       print_r('Rate'.$Rate  );
       print_r('<br>');
       $this->InsertToEbayOrder($Type,$TransactionID,$ParentTransactionID,$SalesChanel,$Rate ,$IsOrder,1,$TheMonth,$TheYear);
   }
   else
   {
     $Rate =1;
     print_r('Traction: '. $TransactionID );
     print_r('<br>');

   }
    // Ghi chi tiết Order Detail với sku, số lượng, giá bán, chi phí thuế và chi phí khác
  }
  elseif(($IsEbay)&&(!$IsOrder))// Ebay refund
  {


    $ScaleAmoutRefundAndAmountOrder = $this->GetScaleAmoutRefundAndAmountOrder($ParentTransactionID,$TransactionID,$Gross,$IsEbay);

    $SubProductAmount = $this->GetAmountOfProductOnEbay($ParentTransactionID);

    // Tính chi phí thuế và các chi phí khác
    if($ScaleAmoutRefundAndAmountOrder == 1)// Full Refund
    {
      $SalesTaxAndOtherFee = $Gross - $SubProductAmount;
      // Tỷ lê Thuế và chi phí khác trên tiền hàng
      $Rate =  $SalesTaxAndOtherFee  / $SubProductAmount;
      print_r('Là Full Refund');
      print_r('<br>');
      $this->InsertToEbayOrder($Type,$TransactionID,$ParentTransactionID,$SalesChanel,$Rate ,$IsOrder,$ScaleAmoutRefundAndAmountOrder,$TheMonth,$TheYear);
    }
    else
    {
      print_r('Là  Refund 1 phần');
      print_r('<br>');
      $Rate = $ScaleAmoutRefundAndAmountOrder;
      $this->InsertToEbayOrder($Type,$TransactionID,$ParentTransactionID,$SalesChanel,$Rate ,$IsOrder,$ScaleAmoutRefundAndAmountOrder,$TheMonth,$TheYear);
    }
  }
  elseif((!$IsEbay)&&(!$IsOrder))// Website/Crailist refund
  {
    $ScaleAmoutRefundAndAmountOrder = $this->GetScaleAmoutRefundAndAmountOrder($ParentTransactionID,$TransactionID,$Gross,$IsEbay);
    if( $ScaleAmoutRefundAndAmountOrder==1)// Full Refund
    {
      $SubProductAmount = $this->GetAmountOfProductOnWebsite($TransactionID);// Cần check là dung transacion cha hay con
      $SalesTaxAndOtherFee = $Gross - $SubProductAmount;
      // Tỷ lê Thuế và chi phí khác trên tiền hàng
      $Rate =  $SalesTaxAndOtherFee  / $SubProductAmount;
      $this->InsertWebsiteOrderDetail($Type,$TransactionID,$ParentTransactionID,$Rate ,$IsOrder, $ScaleAmoutRefundAndAmountOrder,$PaypalFee,$TheMonth,$TheYear);
    }
    elseif((!$IsEbay)&&($IsOrder))// Là những order nhưng không phải là những order của ebay (của website)
    {
      $IsOrder = true;
      $this->InsertWebsiteOrderDetail($Type,$TransactionID,$ParentTransactionID,$Rate ,$IsOrder, $ScaleAmoutRefundAndAmountOrder,$PaypalFee,$TheMonth,$TheYear);
    }
    else// Là refund của website
    {
      $Rate = $ScaleAmoutRefundAndAmountOrder;
      $this->InsertWebsiteOrderDetail($Type,$TransactionID,$ParentTransactionID,$Rate ,$IsOrder, $ScaleAmoutRefundAndAmountOrder,$PaypalFee,$TheMonth,$TheYear);
    }
  }
}
//---------------------------------------------------
public function InsertWebsiteOrderDetail($Type,$TransactionID,$ParentTransactionID,$Rate ,$IsOrder, $Scale,$PaypalFee,$Month,$Year)
{
  if(!$IsOrder)// KHông phải order -> Refund
  {
    $sql = " select products.product_sku as sku, magento_order_details.quantity,magento_order_details.product_price
    from magento_orders inner join magento_order_details on magento_orders.magento_order_id = magento_order_details.magento_order_id
    inner join products on magento_order_details.product_id = products.id
    where magento_orders.payment_transaction_id	 like '%$ParentTransactionID%'";

    $ds = DB::connection('mysql_it')->select ($sql);
    foreach( $ds as $d )
    {
      $Amount =$d->quantity *  $d->product_price;
      if($Scale==1)
      {
        $Quantity =$d->quantity;
        $Price = $d->product_price;
      }
      else
      {
        $Quantity =0;
        $Price = 0;
      }
      $sql = " insert into fa_return_refund_craiglist_website(sku,quantity,amount,the_month,	the_year)
      values('$d->sku',$Quantity,$Amount,$Month,$Year )";
      $ds = DB::connection('mysql')->select ($sql);
    }
  }
  else// Là website order
  {
    // lấy tổng tiền của order(thuần tiền hàng)
    $sql = " select sum (magento_order_details.quantity * magento_order_details.product_price) as SubTotal,
    from magento_orders inner join magento_order_details on magento_orders.magento_order_id = magento_order_details.magento_order_id
    inner join products on magento_order_details.product_id = products.id
    where magento_orders.payment_transaction_id	 like '%$TransactionID%'";
    $ds = DB::connection('mysql_it')->select ($sql);
    foreach( $ds as $d ){ $SubTotal =$d->SubTotal;}

    $Rate = $PaypalFee/$SubTotal;

    $sql = " select products.product_sku as sku, magento_order_details.quantity,magento_order_details.product_price as price,
    from magento_orders inner join magento_order_details on magento_orders.magento_order_id = magento_order_details.magento_order_id
    inner join products on magento_order_details.product_id = products.id
    where magento_orders.payment_transaction_id	 like '%$TransactionID%'";
    $ds = DB::connection('mysql_it')->select ($sql);
    foreach( $ds as $d )
    {
     $sql ="insert into sal_website_order_tmp(payment_transaction,sku,quantity,price,amount,paypal_fee,the_month,the_year)
     values('$d->sku',$d->quantity,$d->price,$d->quantity*$d->price,$d->quantity*$d->price*$Rate,$Month,$Year ) ";
     DB::connection('mysql')->select ($sql);
    }

  }
}
//---------------------------------------------------
public function  GetAmountOfProductOnWebsite($TransactionID)
{
  $sql = " select sum(Odt.quantity *Odt.product_price)as total
  from magento_orders as O inner join magento_order_details as Odt on O.magento_order_id = Odt.magento_order_id
  where O.payment_transaction_id like '%$TransactionID%'";

  $ds = DB::connection('mysql_it')->select ($sql);
  foreach( $ds as $d ){ $Total = round($d->total,2);}
  return $Total;
}
//---------------------------------------------------
public function InsertToEbayOrder($Type,$TransactionID,$ParentTransactionID,$SalesChanel,$Rate ,$IsOrder,$Scale,$TheMonth,$TheYear)// $Scale = 1 nếu là refund thì full refund
{
  $SalesStore=2; // Tạm thời gán store = 2 tương đương EBAY NNC, sau này làm việc thêm với IT để xác định Store ID trong Ebay_order
  $Amount =0;
  $Count =0;
  if($IsOrder)
  {
    $sql = " select left(odt.sku,4) as sku,odt.quantity_purchased ,odt.transaction_price
    from ebay_order o inner join ebay_order_detail odt
    on o.id = odt.ebay_order_id
    where o.external_transaction like '%$TransactionID%'";
    $ds = DB::connection('mysql_it')->select ($sql);
    foreach( $ds as $d )
    {
      $Amount = $d->quantity_purchased * $d->transaction_price;
      DB::connection('mysql')->table('fa_selling_monthly_detail')->insert(
      [ 'type'=>$Type,'sku'=>$d->sku, 'quantity'=>$d->quantity_purchased,'price'=>$d->transaction_price,
      'payment_processing_fee'=> -$Amount * $Rate ,
      'amount'=> $Amount ,'transaction_id'=>$TransactionID,
      'sales_channel'=>$SalesChanel,'store'=>$SalesStore ,'the_month'=>$TheMonth,'the_year'=>$TheYear]);
      $Count ++;

    }
    if($Count==0)
    {
      print_r('Không tim thấy Transaction : '. $TransactionID . ' Trong ebay order');
      print_r('<br>');
    }
  }
  else// Refund
  {
      $sql = " select left(odt.sku,4) as sku,odt.quantity_purchased ,odt.transaction_price
      from ebay_order o inner join ebay_order_detail odt
      on o.id = odt.ebay_order_id
      where o.external_transaction like '%$ParentTransactionID%'";
      $ds = DB::connection('mysql_it')->select ($sql);
      $RecordCount = 0;
      foreach( $ds as $d )
      {
        if($Scale ==1 )
        {
          $Quantity = $d->quantity_purchased;
          $Price = $d->transaction_price;
        }
        else
        {
          $Quantity = 0;
          $Price = 0;
          $Type='Refund-1';
        }
        $RecordCount ++;
        $Amount = $d->quantity_purchased * $d->transaction_price;
        DB::connection('mysql')->table('fa_selling_monthly_detail')->insert(
        [ 'type'=>$Type,'sku'=>$d->sku, 'quantity'=>$Quantity ,'price'=>$Price,'payment_processing_fee'=> $Amount * $Rate ,
        'amount'=> -$Amount ,'transaction_id'=>$TransactionID,
        'sales_channel'=> $SalesChanel,'store'=>$SalesStore ,'the_month'=>$TheMonth,'the_year'=>$TheYear]);

        print_r('Insert refund: '.  $Type);
        print_r('<br>');
      }// End for
     if( $RecordCount == 0)
     {
      print_r('Không tim thấy Transaction ID '. $ParentTransactionID . ' Trong database' );
      print_r('<br>');
     }
  }
}
//---------------------------------------------------
public function GetAmountOfProductOnEbay($TransactionID)
{
  $SubProductAmount = 0;
  $sql = " select sum(odt.quantity_purchased * odt.transaction_price) as SubProductAmount
  from ebay_order o inner join ebay_order_detail odt
  on o.id = odt.ebay_order_id
  where o.external_transaction like '%$TransactionID%' group by  o.external_transaction " ;

  $ds = DB::connection('mysql_it')->select ($sql);
  foreach( $ds as $d ){ $SubProductAmount = round($d->SubProductAmount,2);}

  return $SubProductAmount ;
}

//---------------------------------------------------
public function GetScaleAmoutRefundAndAmountOrder($ParentTransactionID,$TransactionID,$Gross,$IsEbay)// =1 -> Full Refund
{//$ParentTransactionID,$TransactionID,$Gross,$IsEbay

  //$IsEbay=true;
  //$ParentTransactionID ='74V23747KL4634846';
  //$Gross=-245.43;

  $GorssInTransaction = 0.0;
  $strTemp = '';
  if($IsEbay)
   {
    $sql = " select external_transaction from ebay_order where external_transaction like '%$ParentTransactionID%'";
    $ds = DB::connection('mysql_it')->select ($sql);
    foreach( $ds as $d ){ $strTemp = $d->external_transaction;}

   // print_r('chuoi: '.$strTemp );
   // print_r('<br>');

    $strTemp = strstr($strTemp,'PaymentOrRefundAmount');
    $strTemp =  str_replace('PaymentOrRefundAmount', '',$strTemp);

    $strTemp = substr($strTemp,7,strlen($strTemp)-7);
    $strTemp = strstr($strTemp,'"',true);

    //print_r('chuoi: '.$strTemp );
    $GorssInTransaction = (float)$strTemp;
    return   $GorssInTransaction/-$Gross;
   }
   else
   {
     $sql = " select total from magento_orders where payment_transaction_id	 like '%$TransactionID%'";
     $ds = DB::connection('mysql_it')->select ($sql);
     foreach( $ds as $d ){ $GorssInTransaction = $d->total;}
     return    $GorssInTransaction/-$Gross;
   }
}
//---------------------------------------------------
public function FoundRefundTransaction($TransactionID, $ParentTransactionID,$Gross,$Net,$PaypalFee)
{
  // Tìm trong EBAY
  $sql = " select count(id)as MyCount from ebay_order where external_transaction like '%$ParentTransactionID%'";
  $ds = DB::connection('mysql_it')->select ($sql);
  foreach( $ds as $d ){ $MyCount = $d->MyCount;}

  if($MyCount == 0)
  {
    print_r('Không tìm thấy trong Ebay');
    print_r('<br>');
    $sql = " select count(id) as MyCount from magento_orders where payment_transaction_id	 like '%$ParentTransactionID%'";
    $ds = DB::connection('mysql_it')->select ($sql);
    foreach( $ds as $d ){ $MyCount = $d->MyCount;}

    if( $MyCount >0)
    {
      $this->IsEbay =false;
      return 2;
    }
    else
    {
      print_r('Không tìm thấy trong Magento');
      print_r('<br>');
      return 0;
    }
  }
  else
  {
    $this->IsEbay =true;
    return 1;
  }
}
//---------------------------------------------------
// =1 -> Trả hết hàng, khác 1 chính alf tỷ lệ trả tiền trên tiền hàng
public function EqualAmountFromOrderNumberOnEbay($OrderID, $OrderTotal)
{
  $Result = 1;
  $SubProductAmount = 0;
  $sql = " select sum(ebay_order_detail.quantity_purchased * ebay_order_detail.transaction_price) as SubProductAmount
  from ebay_order inner join ebay_order_detail
  on ebay_order.id = ebay_order_detail.ebay_order_id
  where ebay_order.ebay_extended_order_id ='$OrderID'";
  $ds = DB::connection('mysql_it')->select ($sql);
  foreach( $ds as $d ){ $SubProductAmount = -round($d->SubProductAmount,2);}

  if($SubProductAmount<>0){  $Result = $OrderTotal/-$SubProductAmount; }
  else{$Result=0;}

  return $Result;
}
//---------------------------------------------------
// In ra những Oder ID có những SKU mà giá bán không khớp với
public function CheckPriceOnWM_DSV( $Year,$Month)
  {
    $i = 0;
    $sql = " select sku, price from fa_dsv_promotion_sem_actual where the_month = $Month and the_year = $Year ";
    $ds= DB::connection('mysql')->select($sql);
    foreach($ds as $d)
    {
      $i ++;
     // print_r('Record thứ'.$i );
     // print_r('<br>');
      $sql = "  select po_id,price from fa_selling_monthly_detail
      where the_month = $Month and the_year =  $Year and sales_channel = 4
      and sku = '$d->sku' and  round(price,2) <> $d->price ";

      $ds1= DB::connection('mysql')->select($sql);
      foreach($ds1 as $d1)
      {
        print_r('PO ID: '.$d1->po_id. ' sku: ' . $d->sku. " Giá chuẩn: ". $d->price." Price ghi trong WMDSV order :". $d1->price );
        print_r('<br>');
        // PBP sẽ làm việc với sales để có giá đúng cho từng order rồi cập nhật vào
        // fa_selling_monthly_detail theo kênh, poid, sku tương ứng rồi mới chạy đến convert
      }
    }
  }
  public function left($str, $length)
  {
    return substr($str, 0, $length);
  }

  public function GetSkuFromFbaFbmPromotion($AmazonOrderID,$Price)
  {
    $sku = '';
    $sql = " select sku from fa_amazon_idzo where order_number like '%$AmazonOrderID%'
   and promotional_rebates = -$Price ";
   $ds= DB::connection('mysql')->select($sql);
   foreach($ds as $d)  { $sku = $d->sku;}
   return  $sku;
  }
  public function GetPaypalFeeForWebsite($OrderID, $Sku,$Month,$Year)
  {
    $PaypalFee = 0;
    $sql = "select payment_transaction_id from magento_orders where magento_orders.magento_order_id = $OrderID";
    $ds= DB::connection('mysql_it')->select($sql);
    foreach($ds as $d)  { $Transaction =$d->payment_transaction_id ;}

    $sql = " select paypal_fee from  sal_website_order_tmp
    where sku = '$Sku' and the_month = $Month and 	the_year = $Year
    and payment_transaction	like '%$Transaction%'";

    $ds= DB::connection('mysql')->select($sql);
    foreach($ds as $d)  { $PaypalFee = $this->iif(is_null($d->paypal_fee),0,$d->paypal_fee);}
    return  $PaypalFee;
  }
  public function IsExist($sConnection,$sql)
  {
    $MyCount = 0;
    $ds= DB::connection($sConnection)->select($sql);
    foreach($ds as $d)
     {
      $MyCount  = $this->iif(is_null($d->MyCount),0,$d->MyCount);
     }
     if($MyCount>0){ return 1;}
     else{ return 0 ;}
  }

  public function GetCOGS($Sku,$Year, $Month)
  {
    $Result = 0;
    $sql = " select cogs from fa_unit_costs where the_month = $Month and the_year = $Year and sku = '$Sku' ";
    $ds= DB::connection('mysql')->select($sql);
    foreach($ds as $d)   {    $Result = $this->iif(is_null($d->cogs),0,$d->cogs);     }
    return  $Result ;
  }
  // Lấy giá vốn bán hàng cho kênh DI
  public function GetDI_COGS($Sku,$Year, $Month)
  {
    $Result=0;
    if($Year = 2020 &&  $Month ==5)
    {
      $sql = " select unit_cost as cogs from  tmp_di_unit_cost where 	sku = '$Sku' ";
      $ds= DB::connection('mysql')->select($sql);
    }
    else
    {
      $DateEndOfMonth = $this->GetLastDateOfMonth( $Year, $Month);
      $sql = " select didt.cogs from amazon__di_booking di
      inner join amazon__di_booking_details didt on  di.id = didt.booking_id
      inner join products on didt.product_id = products.id
      where products.product_sku = '$Sku'and didt.cogs > 0
      and date(discharge_eta) <= '$DateEndOfMonth '
      order by didt.id DESC limit 1 ";
      $ds= DB::connection('mysql_it')->select($sql);
    }
    foreach($ds as $d)   {    $Result = $this->iif(is_null($d->cogs),0,$d->cogs);     }
    return  $Result ;
  }
}

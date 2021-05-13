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

  public function __construct()
  {
      $this->middleware('auth');
  }
// ============================================================================================================
private function CaculateScale($Year,$Month)
{
  $sql = " select 	fob,	freight_cost, duties,	pallet_fee from fa_scale_monthly
  where the_year  =$Year and the_month	 = $Month  " ;
  $ds= DB::connection('mysql')->select($sql);
  foreach($ds as $d)
  {
    $this->gFOB = $this->iif(is_null( $d->fob),0, $d->fob);
    $this->gFreightCost = $this->iif(is_null( $d->freight_cost),0, $d->freight_cost);
    $this->gDuties = $this->iif(is_null( $d->duties),0, $d->duties); ;
    $this->gPalletFee  =  $d->pallet_fee;

  }
}
// ============================================================================================================
public function Test()
{
  $Year =2020;
  $this->CreateDataForArticleExcepEbay($Year);
}
// ============================================================================================================
public function MakePLReport($Year)
{
  // Dell all datas in PL report for year = $Year if exist
  $sql = " delete  from fa_pl_reports where the_year = $Year ";
  DB::connection('mysql')->select($sql);

  // Move all Articles to PL Report
  $sql = " insert into fa_pl_reports(article,des,account,the_year) select id, name, account_no, $Year from fa_account_pl_report_articles ";
  DB::connection('mysql')->select($sql);
  $this->CreateDataForArticleExcepEbay($Year);


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
  $articlesFrom ="(149,154,159, 164)";
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
private function CreateDataForArticleExcepEbay($Year){
  // Declare Sales team
   $XL = 337;
   $ThuongChau = 336;
   $Wel = 527;
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

  // Duyệt từ tháng 1 đến tháng 12 của năm
  for($Month = 1 ;$Month <= 12 ; $Month++)
    {
      $this->CaculateScale($Year,$Month);// Lay ty le của từng tháng


      for($Channel = 1 ;$Channel <= 10 ; $Channel++)
        {
          if($Channel != 6)
            {
            for($Department =1; $Department <= 3; $Department++ )
              {
                // 1. UnitShipment
                $sql = " select sum(sell_quantity - return_quantity) as UnitShipment from sal_selling_summary_monthlys
                where the_month = $Month   and the_year = $Year and sales_chanel = " . $Channels[$Channel] .
                " and department_id = ". $SalesTeams[$Department];

                $ds= DB::connection('mysql')->select($sql);
                foreach($ds as $d){$UnitShipments[$Department] = $this->iif(is_null( $d->UnitShipment),0, $d->UnitShipment);  }

                // 2. GrossRevenue
                $sql = " select sum(revenue) as revenue from sal_selling_summary_monthlys where the_month = $Month
                and the_year = $Year and sales_chanel = " . $Channels[$Channel].
                " and department_id = ". $SalesTeams[$Department];

                $ds= DB::connection('mysql')->select($sql);
                foreach($ds as $d){$GrossRevenues[$Department] = $this->iif(is_null( $d->revenue),0, $d->revenue);  }

                // 3. Refund
                $sql = " select sum(refund) as refund from sal_selling_summary_monthlys where the_month = $Month
                and the_year = $Year and sales_chanel = " . $Channels[$Channel].
                " and department_id = ". $SalesTeams[$Department] ;

                $ds= DB::connection('mysql')->select($sql);
                foreach($ds as $d){  $Refunds[$Department] =  $this->iif(is_null( $d->refund),0, $d->refund);   }

                // 5. Revenue = GrossRevenue -  Refund

                $Revenues[$Department] = $GrossRevenues[$Department] -  $Refunds[$Department] ;

                // 5. $Promotion
                $sql = " select sum(promotion) as promotion from sal_selling_summary_monthlys where the_month = $Month
                and the_year = $Year and sales_chanel = " . $Channels[$Channel].
                " and department_id = ". $SalesTeams[$Department];

                $ds= DB::connection('mysql')->select($sql);
                foreach($ds as $d){$Promotions[$Department] =  $this->iif(is_null($d->promotion),0, $d->promotion); }

                // 6. SEM
                $sql = " select sum(seo_sem) as seo_sem from sal_selling_summary_monthlys where the_month = $Month
                and the_year = $Year and sales_chanel = " . $Channels[$Channel].
                " and department_id = ". $SalesTeams[$Department] ;

                $ds= DB::connection('mysql')->select($sql);
                foreach($ds as $d){ $SEMs[$Department] =  $this->iif(is_null($d->seo_sem),0,$d->seo_sem);  }

                // 7. Shipping
                $sql = " select sum(shiping_fee) as shiping_fee from sal_selling_summary_monthlys where the_month = $Month
                and the_year = $Year and sales_chanel = " . $Channels[$Channel] .
                " and department_id = ". $SalesTeams[$Department];

                $ds= DB::connection('mysql')->select($sql);
                foreach($ds as $d){ $Shippings[$Department] =  $this->iif(is_null( $d->shiping_fee),0, $d->shiping_fee);  }

                // 8. OtherFee
                $sql = " select sum(other_selling_expensives) as OtherFee from sal_selling_summary_monthlys where the_month = $Month
                and the_year = $Year  and sales_chanel = " . $Channels[$Channel] .
                " and department_id = ". $SalesTeams[$Department];

                $ds= DB::connection('mysql')->select($sql);
                foreach($ds as $d){  $OtherFees[$Department] = $this->iif(is_null( $d->OtherFee),0, $d->OtherFee); }

                //9. Total Fee
                $TotalFees[$Department] =  $SEMs[$Department] + $Promotions[$Department] +  $Shippings[$Department]+ $OtherFees[$Department] ;

                //10. Net Revenue
                $NetRevenues[$Department] =   $Revenues[$Department]-  $TotalFees[$Department];

                //11. cogs
                $sql = " select sum(cogs) as cogs from sal_selling_summary_monthlys where the_month = $Month
                and the_year = $Year  and sales_chanel = " . $Channels[$Channel] .
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
   $Wel = 527;
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
  $Wel = 527;
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

        $sql =" update fa_pl_reports set " .  $UnitShipmentSql . " where article = 12 and the_year = $Year ";
        DB::connection('mysql')->select($sql);

        $sql =" update fa_pl_reports set " .  $RevenueSql . " where article = 13 and the_year = $Year ";
        DB::connection('mysql')->select($sql);

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

        print_r('avc_wh gross'.$sql);
        print_r('<br>');

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
        print_r($sql);
        print_r('<br>');
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
// ============================================================================================================
public function MoveAvcDropshipOrder()// Move AVC order from BE to gcom
{
  $LastID = 0;// ID cuối cùng trên BE đã move qua gcom
  $sql = " select case when max(id_on_be) >0 then max(id_on_be) else 0 end as id_on_be from sal_amazon_dropship_orders ";
  $ds = DB::connection('mysql')->select($sql);
  foreach( $ds  as  $d ){$LastID =  $d->id_on_be;  }

  // Move những ID mới phát sinh
  // Move Master
  $sql = " select id , amazon_order_id,  order_id,  status,  is_multiple, is_pslip_required,
  is_gift, is_priority_shipment,  warehouse_code,    order_placed_date,  required_shipdate,
  ship_address,  ship_to_state,   shipped_date
  from amazon_dropship_orders where  id > $LastID ";

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

  // Move Detail: Order ID bảng này nối với id_on_be bảng trên
  $sql = " select   order_id ,  asin, sku , product_id , title ,unit_cost, cost, status, actual_ship_date, quantity
  from  amazon_dropship_order_details where order_id > $LastID ";
  $ds = DB::connection('mysql_it')->select($sql);
  foreach( $ds as $d)
  {
    $sql = " insert into sal_amazon_dropship_order_details( order_id, asin, sku , product_id , title ,unit_cost,
    cost, status, actual_ship_date, quantity)"
    . $d->order_id.",'" . $d->asin ."','". $d->sku ."',". $d->product_id. ",'" . $d->title ."',"
    . $d->unit_cost. ",".$d->cost. ",'".$d->status."','" . $d->actual_ship_date. "',"
    ."',". $d->quantity.",'" ;
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
  $sql = " sal_amazon_avc_id,  model_number, asin ,  sku ,  title ,  product_id, submitted_quantity ,  system_confirm ,
  confirm_quantity,  wh_confirm ,  accepted_quantity,received_quantity,  unit_cost,  status ,  print_date,
  print_number ,  back_order ,  is_shipped,  process_profit,add_reserved,salesman_id,  salesleader_id,  comments,
  system_calculated,  expected_ship_date where sal_amazon_avc_id > $LastID ";
  $ds = DB::connection('mysql_it')->select($sql);
  foreach( $ds as $d)
  {
    $sql = " insert into sal_amazon_avc_order_details( sal_amazon_avc_id,  model_number, asin ,  sku ,  title ,
    product_id, submitted_quantity ,  system_confirm , confirm_quantity,  wh_confirm ,  accepted_quantity,
    received_quantity,  unit_cost,status)"
    . $d->sal_amazon_avc_id.",'".$d->model_number.",'" . $d->asin ."','". $d->sku ."','". $d->title . "',"
    . $d->product_id. "," . $d->submitted_quantity .","  . $d->system_confirm. ",".$d->confirm_quantity. ",".$d->wh_confirm.","
    . $d->accepted_quantity. ",". $d->received_quantity.",". $d->unit_cost. ",'".$d->status."','" . $d->status ;
    DB::connection('mysql')->select($sql);
  }
}
// ============================================================================================================
  public function MoveDataFromBE3ToBPDAndUpdateDepartment($Month, $Year)
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
/*
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
*/
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
   $ds = DB::connection('mysql')->select("select user_id , group_id as ProductGroupID from product_assigned where group_id <>'[]'");
   foreach( $ds  as  $d)
   {
      $sProductGroupID = str_replace("[","(",$d->ProductGroupID);
      $sProductGroupID = str_replace("]",")",$sProductGroupID);
      $sProductGroupID = str_replace('"','',$sProductGroupID);

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

      DB::connection('mysql')->select($sql);

      $sSku = "";
   }

   // Team Wel phụ trách các kênh 6,7,8(EBAY, Local/Crailist/Website)
   $sql = " update sal_selling_summary_monthlys set department_id = 527
   where sales_chanel in (6,7,8) and the_month = $Month and the_year = $Year and department_id is null ";
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

    $SalesChannel =1;// AVC-WH
    DB::connection('mysql')->select("insert into sal_selling_summary_monthlys(sku,sell_quantity,revenue,sales_chanel,the_month,the_year)
    select sku,sum(avc_wh_rs), sum(avc_wh_rev),$SalesChannel  as channel,$Month as month,$Year as year
    from fa_amazon_real_sales where  the_month = $Month and the_year = $Year and avc_wh_rs> 0 and avc_wh_rev > 0 and sku  is not null
    group by sku,channel,channel,year ");

    $SalesChannel = 2;// AVC-DS
    DB::connection('mysql')->select(" insert into sal_selling_summary_monthlys(sku,sell_quantity,revenue,sales_chanel,the_month,the_year)
    select sku,sum(quantity), sum(amount),sales_channel ,the_month,the_year
    from fa_selling_monthly_detail where  the_month = $Month and the_year = $Year and sales_channel =  $SalesChannel and sku  is not null
    group by sku,sales_channel,the_month,the_year ");

    $SalesChannel = 3;// AVC-DI
    DB::connection('mysql')->select(" insert into sal_selling_summary_monthlys(sku,sell_quantity,revenue,sales_chanel,the_month,the_year)
    select sku, sum(di_rs) , sum(di_rev),  $SalesChannel  as channel,$Month as month,$Year as year from fa_amazon_real_sales
    where  the_month = $Month and the_year = $Year and di_rs >0 and di_rev > 0 group by sku,channel,channel,year");

    $SalesChannel = 4 ;//WM-DSV
    DB::connection('mysql')->select(" insert into sal_selling_summary_monthlys(sku,sell_quantity,revenue,sales_chanel,the_month,the_year)
    select sku,sum(quantity), sum(amount),sales_channel ,the_month,the_year
    from fa_selling_monthly_detail where  the_month = $Month and the_year = $Year and sales_channel =  $SalesChannel and sku  is not null
    group by sku,sales_channel,the_month,the_year ");

    $SalesChannel = 5;// WM-MKP
     DB::connection('mysql')->select(" insert into sal_selling_summary_monthlys(sku,sell_quantity,revenue,sales_chanel,the_month,the_year)
     select sku, sum(quantity) , sum(amount) ,$SalesChannel  as channel,$Month as month,$Year as year
     from fa_walmart_market where  quantity >0 and  the_month = $Month and the_year = $Year and sku  is not null
     and (transaction_type like'%SALE%' or transaction_type like'%Sale%') group by sku,channel,month,year ");


    $SalesChannel = 6 ;//EBAY
    DB::connection('mysql')->select(" insert into sal_selling_summary_monthlys(sku,sell_quantity,revenue,sales_chanel,store, the_month,the_year)
    select sku,sum(quantity), sum(amount),sales_channel , store,the_month,the_year
    from fa_selling_monthly_detail where  the_month = $Month and the_year = $Year and sales_channel =  $SalesChannel
    and sku  is not null and ( type like '%Order%' or type like '%eBay Auction Payment%' or type like '%Oder%')
    group by sku,sales_channel,store,the_month,the_year");


    $SalesChannel = 7 ;//Craiglist/Local
    DB::connection('mysql')->select(" insert into sal_selling_summary_monthlys(sku,sell_quantity,revenue,sales_chanel,the_month,the_year)
    select sku,sum(quantity), sum(amount),sales_channel ,the_month,the_year
    from fa_selling_monthly_detail where  the_month = $Month and the_year = $Year and sales_channel =  $SalesChannel and sku  is not null
    group by sku,sales_channel,the_month,the_year ");

    $SalesChannel = 8 ;//Website
    DB::connection('mysql')->select(" insert into sal_selling_summary_monthlys(sku,sell_quantity,revenue,sales_chanel,the_month,the_year)
    select sku,sum(quantity), sum(amount),sales_channel ,the_month,the_year
    from fa_selling_monthly_detail where  the_month = $Month and the_year = $Year and sales_channel =  $SalesChannel and sku  is not null
    group by sku,sales_channel,the_month,the_year ");

    $SalesChannel = 9;// FBA
    // Thực hiện chuyển data import thô từ table fa_walmart_market vào sal_selling_summary_monthlys
    DB::connection('mysql')->select(" insert into sal_selling_summary_monthlys(sku,sell_quantity,revenue,sales_chanel,the_month,the_year)
    select sku,sum(quantity) , sum(product_sales),  $SalesChannel  ,$Month , $Year  from fa_amazon_idzo
    where the_year = $Year and the_month = $Month and  quantity >0 and sku  is not null
    and  type like '%Order%' and (fulfillment like'%Amazon%' or fulfillment like'%AMAZON%')
    group by sku ");

    $SalesChannel = 10;// FBM
    DB::connection('mysql')->select(" insert into sal_selling_summary_monthlys(sku,sell_quantity,revenue,sales_chanel,the_month,the_year)
    select sku,sum(quantity) , sum(product_sales + shipping_credits ),  $SalesChannel ,$Month ,$Year    from fa_amazon_idzo
    where the_year = $Year and the_month = $Month and  quantity >0 and sku  is not null
    and ( type like '%order%' or type like '%Order%')  and  (fulfillment like '%Seller%' or fulfillment like '%SELLER%')
    group by sku ");

    $this->AddSkuToSumTableNotAraisingSellButAraisingCost($Month,$Year);

    $this->MoveDataFromBE3ToBPDAndUpdateDepartment($Month,$Year);

    // cập nhật số Promotion và clip cho tất cả các sku tất cả các kênh
    $this->CaculatePromotionAndClip($Month,$Year);
    // cập nhật số SEM cho tất cả các sku tất cả các kênh
    $this->CaculateSEM($Month,$Year);
    $this->UpdateUnitCostDipMsf($Month,$Year);

    $this->CaculateShipingFeeNew($Month,$Year);
    // Update UpdateReferal cho kênh FBA,FBM
    $this->UpdateReferalFullfillment($Month,$Year);

    for( $SalesChannel = 1; $SalesChannel <=10; $SalesChannel ++)
    {
      if($SalesChannel != 6 ){ // Những kênh khác EBAY
        // Cập nhật một số thông tin bán hàng cơ bản trên các kênh
        $this->UpdateBasicSellingInforOnSalesChannel($SalesChannel,$Month,$Year);
        // Thực hiện phân bổ Chargeback
        $this->UpdateChargebackFreightHandlingReturnCostOtherFee($SalesChannel,$Month,$Year);
      }
      else{// Những kênh  EBAY
          for($TheStore = 1; $TheStore <= 4 ; $TheStore++ ){ $this->UpdateDetailOnStoreForEbay($Month,$Year,$TheStore);}
      }

    }
    $this->GetEbayFinalFeeOrPaypalFeeNew($Month,$Year);
    $this->UpdateOtherFee($Month,$Year);
    $this->UpdateProfitAndOtherFee($Month,$Year);

}
// ============================================================================================================
private function UpdateProfitAndOtherFee($Month,$Year){
  // Bỏ hàng xả kênh Crailist
  $sql = " delete from sal_selling_summary_monthlys where	nest_sales < unit_cogs * sell_quantity and sales_chanel = 7 and the_month = $Month and the_year = $Year " ;
  DB::connection('mysql')->select($sql);

  $sql = " select  sum(liability_insurance) as liability_insurance from fa_summary_chargeback_monthly
  where  sales_channel = 0 and  the_month = $Month and the_year = $Year  ";
  $ds = DB::connection('mysql')->select($sql);
  $LiabilityInsurance =0;
  foreach( $ds as $d) { $LiabilityInsurance = $d->liability_insurance; }

  // Khai báo mảng chứa net sales cuả các kênh
  $ArrayNetSales  = array(10);
  $TotalNetSales = 0;

  $sql = " select sum(nest_sales) as net_sales  from sal_selling_summary_monthlys
  where the_month = $Month and the_year = $Year  and nest_sales > 0  ";
  $ds = DB::connection('mysql')->select($sql);
  foreach( $ds as $d)  {   $TotalNetSales = $d->net_sales; }

  $LiabilityInsurancePerNetSales = $LiabilityInsurance /  $TotalNetSales;

  $sql = " update sal_selling_summary_monthlys set liability_insurance = nest_sales * $LiabilityInsurancePerNetSales
  where the_month = $Month and the_year = $Year and nest_sales > 0 " ;
  DB::connection('mysql')->select($sql);


  $sql = " select id, sales_chanel from sal_selling_summary_monthlys where  the_month = $Month and the_year = $Year";
  $ids = DB::connection('mysql')->select($sql);
    foreach( $ids as $id)
    {
      $sql = " update sal_selling_summary_monthlys
      set profit  = nest_sales -(cogs + promotion + seo_sem + chargeback + coop + freight_cost
      + freight_handling_return_cost  + other_fee + ebay_final_fee+ paypal_fee + discount
      + dip + msf + clip_fee + selling_fees + fullfillment + commission + liability_insurance ),
      other_selling_expensives = dip + msf + selling_fees + fullfillment + chargeback
      + coop +  freight_cost   + freight_handling_return_cost + ebay_final_fee + paypal_fee + discount
      + clip_fee + liability_insurance  +  commission + other_fee
      where id = $id->id ";
      DB::connection('mysql')->select($sql);
    }
  }
// ============================================================================================================
private function UpdateOtherFee($Month,$Year) {// chỉ cho kênh FBA\FBM
  $TotalOtherFee = 0;
  // FBA
  $sql = "Select other_fee from fa_summary_chargeback_monthly
   where  the_month = $Month and the_year = $Year and sales_channel = 9 ";
  $ids = DB::connection('mysql')->select($sql);
  foreach( $ids as $id){
    $TotalOtherFee = $id->other_fee;
  }
  $TotalNetSalesOnFBA = 0;
  $sql = " select sum(nest_sales) as TotalNetSalesOnFBA
  from sal_selling_summary_monthlys where  the_month = $Month and the_year = $Year and sales_chanel = 9 ";
  $ids = DB::connection('mysql')->select($sql);
  foreach( $ids as $id){
    $TotalNetSalesOnFBA = $id->TotalNetSalesOnFBA;
  }
  if($TotalNetSalesOnFBA!=0)
  {
    $OtherFeePerNetSales =$TotalOtherFee/$TotalNetSalesOnFBA;

    $sql = " update  sal_selling_summary_monthlys set other_fee = nest_sales * $OtherFeePerNetSales
    where the_month = $Month and the_year = $Year and sales_chanel = 9 ";
    DB::connection('mysql')->select($sql);
  }

  // FBM
  $sql = "Select other_fee from fa_summary_chargeback_monthly
   where  the_month = $Month and the_year = $Year and sales_channel = 10";
  $ids = DB::connection('mysql')->select($sql);
  foreach( $ids as $id){
    $TotalOtherFee = $id->other_fee;
  }
  $TotalNetSalesOnFBM = 0;
  $sql = " select sum(nest_sales) as TotalNetSalesOnFBM
  from sal_selling_summary_monthlys where  the_month = $Month and the_year = $Year and sales_chanel = 10 ";
  $ids = DB::connection('mysql')->select($sql);
  foreach( $ids as $id){
    $TotalNetSalesOnFBM = $id->TotalNetSalesOnFBM;
  }
  if($TotalNetSalesOnFBM!=0)
  {
    $OtherFeePerNetSales =$TotalOtherFee/$TotalNetSalesOnFBM;

    $sql = " update  sal_selling_summary_monthlys set other_fee = nest_sales * $OtherFeePerNetSales
    where the_month = $Month and the_year = $Year and sales_chanel = 10 ";
    DB::connection('mysql')->select($sql);
  }


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
  private function UpdateUnitCostDipMsf($Month,$Year) {
    $sql = "select id, sku,sales_chanel from  sal_selling_summary_monthlys where  the_month = $Month and the_year = $Year";
    $dsSkus = DB::connection('mysql')->select($sql);
    foreach($dsSkus as $dsSku){
      // lấy giá cogs,fob
      $sql = " Select  cogs, fob from fa_unit_costs  where  the_month = $Month and the_year = $Year and sku = '" .  $dsSku->sku . "'" ;
      $DsUnitCosts = DB::connection('mysql')->select($sql);
      foreach($DsUnitCosts as $DsUnitCost){
        $cogs= $DsUnitCost->cogs;
        $fob = $DsUnitCost->fob;
      }

      $Dip=0.00;
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
        foreach($dsMSFs as $dsMSF){       $MSF =  $this->iif(is_null($dsMSF->msf),0,$dsMSF->msf);   }

      }

      if($dsSku->sales_chanel == 3 )  {   $NewCogs = $fob;      } // DI
      else  {   $NewCogs =  $cogs;      }

      $sql = " update sal_selling_summary_monthlys set unit_cogs = $NewCogs,  dip = $Dip, msf = $MSF     where id = $dsSku->id ";
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
    $ds = DB::connection('mysql')->select("select id, sku,sell_quantity,revenue,unit_cogs
    from sal_selling_summary_monthlys  where the_month = $Month and the_year = $Year and sales_chanel = $SalesChannel "); //and sell_quantity > 0

    foreach($ds as $d)
   {
     $id = $d->id;
     $sku = $d->sku;
     $Quantity = $this->iif(is_null( $d->sell_quantity),0, $d->sell_quantity);

     $this->GetReturnRefund($sku,$SalesChannel,$Month,$Year);
     $Return = $this->gReturn;
     $Refund = $this->gRefund;

     $Revenue = $this->iif(is_null($d->revenue),0,$d->revenue);

     $Cogs =  $this->iif(is_null($d->unit_cogs),0,$d->unit_cogs);
     $Cogs =  ($Quantity - $Return ) *  $Cogs;

     $NetSales = $Revenue - $Refund ;

     $this->gReturn=0;
     $this->gRefund=0;


     if(($SalesChannel == 1 ||$SalesChannel == 2 ) && ( $NetSales > 0)){ // AVC-WH, AVC-DS
        $Coop =  $NetSales/100 * 8 ;
     }
     else{$Coop = 0;}

     if($SalesChannel == 1 && $NetSales > 0) { // AVC-WH
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
     set return_quantity = $Return, refund = $Refund,nest_sales = $NetSales, coop = $Coop, cogs= $Cogs,
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
    {//$Month,$Year
     // Lấy tổng số EbayFinal Fee và EnayPaypal Fee từ sheet ChargebackAndOther
     $sql = " select ebay_final_fee , paypal_fee from fa_summary_chargeback_monthly
     where the_month = $Month and the_year = $Year and 	sales_channel = 6  " ;
     $ds=  DB::connection('mysql')->select($sql);
     foreach($ds as $d)
     {
      $TotalEbayFinalFeeInvoice = $d->ebay_final_fee;
      $TotalEbayPaypalFeeInvoice= $d->paypal_fee;
     }

    // Lấy tổng  net sales của Ebay store 1,3,4
    $TotalEbayNetSales=0;
    $sql = " select sum(nest_sales) as Totalnest_sales from sal_selling_summary_monthlys
    where the_month= $Month and the_year = $Year and sales_chanel = 6 and store in(1,3,4)";
    $ds=  DB::connection('mysql')->select($sql);
    foreach($ds as $d) {  $TotalEbayNetSales = $d->Totalnest_sales; }

    // Tính trung bình $EbayFinal trên một đơn vị net sales
    $EbayFinalPerNetSales = $TotalEbayFinalFeeInvoice/ $TotalEbayNetSales;

    $sql = " select id, nest_sales from sal_selling_summary_monthlys
    where the_month= $Month and the_year = $Year and sales_chanel = 6 and store in(1,3,4)";
    $ds=  DB::connection('mysql')->select($sql);
    foreach($ds as $d)
    {
      $sql = " update sal_selling_summary_monthlys set ebay_final_fee = nest_sales * $EbayFinalPerNetSales where id= $d->id ";
      DB::connection('mysql')->select($sql);
    }

    // Lấy tổng  net sales của Ebay store 2
    $EbayPaypalFeePerNetSales = 0;
    $sql = " select sum(nest_sales) as Totalnest_sales from sal_selling_summary_monthlys
    where the_month= $Month and the_year = $Year and sales_chanel = 6 and store in(2)";
    $ds=  DB::connection('mysql')->select($sql);
    foreach($ds as $d) {  $TotalEbayNetSales = $d->Totalnest_sales; }
    //print_r ('TotalEbayNetSales của  1 kênh: ' . $TotalEbayNetSales  );
   // print_r ('<br>'  );

    // Tính trung bình $EbayFinal trên một đơn vị net sales
    $EbayPaypalFeePerNetSales = $TotalEbayPaypalFeeInvoice/ $TotalEbayNetSales;

    //print_r ('Rate: ' . $EbayPaypalFeePerNetSales);
    //print_r ('<br>'  );

    $sql = " select id, nest_sales from sal_selling_summary_monthlys
    where the_month= $Month and the_year = $Year and sales_chanel = 6 and store in(2)";
    $ds=  DB::connection('mysql')->select($sql);
    foreach($ds as $d)
    {
      $sql = " update sal_selling_summary_monthlys set paypal_fee = nest_sales * $EbayPaypalFeePerNetSales where id= $d->id ";
      DB::connection('mysql')->select($sql);
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
      $sql= " insert into sal_selling_summary_monthlys(sku,	return_quantity,refund,	sales_chanel,the_month,the_year)
      select sku, sum(quantity), sum(amount),1 , $Month, $Year from fa_avc_returns
      where the_month =  $Month and the_year = $Year
      and vendor_code  like '%YES4A%'
      and sku not in (select sku from sal_selling_summary_monthlys where  the_month = $Month  and the_year =  $Year and sales_chanel = 1 )
      group by sku ";
      DB::connection('mysql')->select($sql);
      //2. AVC DS
      $sql= " insert into sal_selling_summary_monthlys(sku,	return_quantity,refund,	sales_chanel,the_month,the_year)
      select sku, sum(quantity), sum(amount),2 , $Month, $Year from fa_avc_returns
      where the_month =  $Month and the_year = $Year
      and vendor_code  like '%AUYAD%'
      and sku not in (select sku from sal_selling_summary_monthlys where  the_month = $Month  and the_year =  $Year and sales_chanel = 2 )
      group by sku ";
      DB::connection('mysql')->select($sql);
      //3. AVC DI
      $sql= " insert into sal_selling_summary_monthlys(sku,	return_quantity,refund,	sales_chanel,the_month,the_year)
      select sku, sum(quantity), sum(amount),3 , $Month, $Year from fa_avc_returns
      where the_month =  $Month and the_year = $Year
      and vendor_code  like '%YES4U%'
      and sku not in (select sku from sal_selling_summary_monthlys where  the_month = $Month  and the_year =  $Year and sales_chanel = 3 )
      group by sku ";
      DB::connection('mysql')->select($sql);
      //4. WM-DS
      $sql= " insert into sal_selling_summary_monthlys(sku,	return_quantity,refund,	sales_chanel,the_month,the_year)
      select sku, sum(quantity), sum(amount) ,4, $Month, $Year  from fa_wm_dsv_returns
      where the_month =  $Month and the_year = $Year
      and sku not in (select sku from sal_selling_summary_monthlys where  the_month = $Month  and the_year =  $Year and sales_chanel = 4)
      group by sku ";
      DB::connection('mysql')->select($sql);
      //5. WM-MKP
      $sql= " insert into sal_selling_summary_monthlys(sku,	return_quantity,refund,	sales_chanel,the_month,the_year)
      select sku, sum(quantity), sum(amount) ,5, $Month, $Year  from fa_walmart_market
      where the_month =  $Month and the_year = $Year and transaction_type like '%REFUNDED%'
      and sku not in (select sku from sal_selling_summary_monthlys where  the_month = $Month  and the_year =  $Year and sales_chanel = 5)
      group by sku ";
      DB::connection('mysql')->select($sql);

      //6. EBAY
      $Store = 1;
      $sql= " insert into sal_selling_summary_monthlys(sku,	return_quantity,refund,	sales_chanel,store,the_month,the_year)
      select sku, sum(quantity), sum(amount) ,sales_channel,store, $Month, $Year  from fa_selling_monthly_detail
      where the_month =  $Month and the_year = $Year and sales_channel = 6 and store = 1
      and ( type like'%Refund%')
      and sku not in (
        select sku from sal_selling_summary_monthlys where  the_month = $Month  and the_year =  $Year
        and sales_chanel = 6 and store = 1
        )
      group by sku ";
      DB::connection('mysql')->select($sql);

      $Store = 2;
      $sql= " insert into sal_selling_summary_monthlys(sku,	return_quantity,refund,	sales_chanel,store,the_month,the_year)
      select sku, sum(quantity), sum(amount) ,sales_channel,store, $Month, $Year  from fa_selling_monthly_detail
      where the_month =  $Month and the_year = $Year and sales_channel = 6 and store = 2
      and ( type like'%Refund%')
      and sku not in
      (select sku from sal_selling_summary_monthlys
      where  the_month = $Month  and the_year =  $Year and sales_chanel = 6 and store = 2
      )
      group by sku ";
      DB::connection('mysql')->select($sql);

      $Store = 3;
      $sql= " insert into sal_selling_summary_monthlys(sku,	return_quantity,refund,	sales_chanel,store,the_month,the_year)
      select sku, sum(quantity), sum(amount) ,sales_channel,store, $Month, $Year  from fa_selling_monthly_detail
      where the_month =  $Month and the_year = $Year and sales_channel = 6 and store = 3
      and ( type like'%Refund%')
      and sku not in (select sku from sal_selling_summary_monthlys where  the_month = $Month  and the_year =  $Year
      and sales_chanel = 6 and store = 3)
      group by sku ";
      DB::connection('mysql')->select($sql);

      $Store = 4;
      $sql= " insert into sal_selling_summary_monthlys(sku,	return_quantity,refund,	sales_chanel,store,the_month,the_year)
      select sku, sum(quantity), sum(amount) ,sales_channel,store, $Month, $Year  from fa_selling_monthly_detail
      where the_month =  $Month and the_year = $Year and sales_channel = 6 and store = 4
      and ( type like'%Refund%')
      and sku not in (select sku from sal_selling_summary_monthlys where  the_month = $Month  and the_year =  $Year
      and sales_chanel = 6 and store = 4)
      group by sku ";
      DB::connection('mysql')->select($sql);

      //7. Craiglist/Local
      $sql= " insert into sal_selling_summary_monthlys(sku,	return_quantity,refund,	sales_chanel,the_month,the_year)
      select sku, sum(quantity), sum(amount) ,7, $Month, $Year  from fa_return_refund_craiglist_website
      where the_month =  $Month and the_year = $Year and is_craiglist like '%Yes%'
      and sku not in (select sku from sal_selling_summary_monthlys where  the_month = $Month  and the_year =  $Year and sales_chanel = 7)
      group by sku ";
      DB::connection('mysql')->select($sql);
      // Website
      $sql= " insert into sal_selling_summary_monthlys(sku,	return_quantity,refund,	sales_chanel,the_month,the_year)
      select sku, sum(quantity), sum(amount) ,8, $Month, $Year  from fa_return_refund_craiglist_website
      where the_month =  $Month and the_year = $Year and is_craiglist is null
      and sku not in (select sku from sal_selling_summary_monthlys where  the_month = $Month  and the_year =  $Year and sales_chanel = 8)
      group by sku ";
      DB::connection('mysql')->select($sql);
      // 9. FBA
      $sql= " insert into sal_selling_summary_monthlys(sku,	return_quantity,refund,	sales_chanel,the_month,the_year)
      Select sku , sum(quantity), -sum(product_sales),9,$Month, $Year from fa_amazon_idzo  where  the_month = $Month and the_year = $Year
      and (type like '%REFUNDED%' or type like '%Refund%') and (fulfillment like '%Amazon%' or fulfillment like '%AMAZON%')
      and sku not in (select sku from sal_selling_summary_monthlys where  the_month = $Month  and the_year =  $Year and sales_chanel = 9)
      group by sku ";
      DB::connection('mysql')->select($sql);
      //10. FBM
      $sql= " insert into sal_selling_summary_monthlys(sku,	return_quantity,refund,	sales_chanel,the_month,the_year)
      Select sku , sum(quantity), -sum(product_sales),10,$Month, $Year from fa_amazon_idzo  where  the_month = $Month and the_year = $Year
      and (type like '%REFUNDED%' or type like '%Refund%')  and (fulfillment like '%Seller%' or fulfillment like '%SELLER%')
      and sku not in (select sku from sal_selling_summary_monthlys where  the_month = $Month  and the_year =  $Year and sales_chanel = 10)
      group by sku ";
      DB::connection('mysql')->select($sql);

    //II. Bổ sung những sku không có phát sinh bán hàng nhưng có phát sinh MSF -. CHi có kênh FBA
      $sql= " insert into sal_selling_summary_monthlys(sku,	msf,sales_chanel,the_month,the_year)
      select sku , sum(fa_fba_monthly_store_fee.amount),9, $Month,$Year   from fa_fba_monthly_store_fee
      where the_month = $Month and the_year = $Year and fa_fba_monthly_store_fee.sku not in
      ( select DISTINCT(sku) as sku from  sal_selling_summary_monthlys where  the_month = $Month
      and the_year = $Year  and sales_chanel = 9 ) group by sku ";
      DB::connection('mysql')->select($sql);

    //III. Bổ sung những sku không có phát sinh bán hàng nhưng có phát sinh DIP -> Chỉ có kênh FBA
    $sql= " insert into sal_selling_summary_monthlys(sku,	dip,sales_chanel,the_month,the_year)
    select sku , sum(dip),9, $Month, $Year  from fa_dip_monthly
    where the_month = $Month and the_year = $Year and fa_dip_monthly.sku not in
    (  select DISTINCT(sku) as sku from  sal_selling_summary_monthlys   where  the_month = $Month
    and the_year = $Year  and sales_chanel = 9 ) group by sku ";
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
     union
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
      where  the_month =  $Month  and the_year = $Year and shipping_fee >0
      and sales_channel = 6  group by sku
      union
      select sku, sum(shipping_fee),sales_channel,store, $Month as TheMonth , $Year  as TheYear
      from fa_selling_monthly_detail
      where  the_month =  $Month  and the_year = $Year and shipping_fee >0
      and sales_channel = 6  group by sku
      )a where a.sku not IN
      (select sku from sal_selling_summary_monthlys where the_month =  $Month
      and the_year = $Year  and sales_chanel = 6 ) ";
      DB::connection('mysql')->select($sql);

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

    // WM NKP
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
public function CaculateShipingFee( )
{//$Month,$Year
  $Month = 3;
  $Year = 2020;
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
  $sql= " select transaction_id, store_name ,sum(cost) as TotalActCost  from  fa_shipment_realtime_reports
  where  status like '%Shipped%'  and the_month = $Month and the_year = $Year and transaction_id ='401929082262-851448985027'
  group by transaction_id, store_name ";

  //and transaction_id ='401929082262-851448985027'   and store_name like '%Amazon%'
  $ds = DB::connection('mysql')->select($sql);
  foreach($ds  as $d)
  {
    if($this->FindSellingTransationtionInMonthYear($Transaction,$Month,$Year )==1)
    {
      if($this->CheckShippingTheoCost($Transaction,$Month,$Year))// kiểm tra các sku trong transaction này đều có giá shipping ước tính
      {
        $Rate = $this->CaculateRateBetweenActShippingCostAndTheoCost($Transaction,$d->TotalActCost,$StoreName,$Month,$Year );
        $this->UpdateShippingFee($Transaction, $Rate,$Month,$Year);
      }
      else// Có ít nhất một sku trong  transaction này không có giá chi phí ước tính
      {

      }
    }

  }
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
    // chi phí  shiping các kênh còn lại =0
    $sql = " update sal_selling_summary_monthlys set shiping_fee = 0 where sales_chanel not in (4,5,6,7,8,10) and the_month = $Month and the_year = $Year  ";
    DB::connection('mysql')->select($sql);

  }
  // ============================================================================================================
  private function UpdateShipingFee($TransactionID,$Channel,$TotalActualShippingCost,$TheMonth,$TheYear)
  {
    $TotalExpectShippingCost = 0;
    if($Channel == 'Walmart-DSV'|| $Channel =='Walmart-N/A')
      {
        // Tính tổng chi phí Lấy tổng số chi phí vận chuyển theo lý thuyết đối với $TransactionID
        $sql= " select sum(quantity * GetLastShippingCostTheo(sku)) as TotalExpectShippingCost
        from fa_selling_monthly_detail where the_month = $TheMonth and the_year = $TheYear
        and quantity >0  and  transaction_id = '". $TransactionID ."' and sales_channel = 4 ";

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
            and fa_selling_monthly_detail.transaction_id = '". $TransactionID ."' and sales_channel = 4 ";

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
 public function CheckExistingSkuInMemo($Memo)
 {
  //$Memo ='4x Weight - 1.15in - 5lb of DB - 200 lbs - Package 3';
  //$Memo ='ABCDx5';
  $Flag = false;
  $strTemp ='qwertyuiopasdfghjklzcvbnm';
  $Character='';
  $Memo= str_replace(' ','', $Memo);
  $Mycount= strlen($Memo);
  $i=0;
  while($i < $Mycount && $Flag == false)
  {
    $Character = substr($Memo,$i,1);
    $pos = strpos($strTemp, $Character);
    if ($pos !== false) { $Flag = true;}
    $i++;
  }
  return $Flag;
 }
  // ============================================================================================================
  public function InsertShipingDetailFromMemo($TransactionID, $Memo,$MemoBa, $TotalActualShippingCost,$SaleChannel,$Store, $Month , $Year)
  {//$TransactionID, $Memo,$MemoBa, $TotalActualShippingCost,$SaleChannel,$Store, $Month , $Year
/*
    $TransactionID='112-2238359-8616232';
    $Memo ='JXLX x1' ;
    $MemoBa='FL-28-1 + FL-29-7 + FL-28-7';
    $TotalActualShippingCost =13;
    $SaleChannel = 10;
    $Store =0;
    $Month =4;
    $Year=2020;

    print_r( 'Memo:'. $Memo);
    print_r('<br>');
    print_r( 'Memo3:'. $MemoBa);
    print_r('<br>');
*/
    if($this->CheckExistingSkuInMemo($Memo))
    {
      if($MemoBa==''){ exit; }
      else{$Memo = $MemoBa;}
    }//Memo không chứa sku ~ có ký tự thường khác "x"
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

          $sql = " insert into fa_shipping_record_from_memo(transaction_id,sku,quantity,sales_channel,store,the_month,the_year) ".
          " select "."'". $TransactionID ."','". $sku . "',". $Quantity. ",". $SaleChannel.",". $Store.",". $Month . ",". $Year ;
          DB::connection('mysql')->select($sql);
        }
        else// Có điền số lượng
        {
/*
          $sql = " delete from tmp_record";
          DB::connection('mysql')->select($sql);

          $sql = " insert into tmp_record(memo) select '".$Memo ."'";
          DB::connection('mysql')->select($sql);
*/
          if (strpos($Memo, 'x') !== false)
          {
            $dataSKUAndQuantity = explode('x',$Memo);
            $sku = $dataSKUAndQuantity[0];
            $Quantity = $dataSKUAndQuantity[1];

            $sql = " insert into fa_shipping_record_from_memo(transaction_id,sku,quantity,sales_channel,store,the_month,the_year) ".
            " select "."'". $TransactionID ."','". $sku . "',". $Quantity. ",". $SaleChannel.",". $Store.",". $Month . ",". $Year ;
            DB::connection('mysql')->select($sql);
          }
        }
      }
      else// Có nhiều hơn một sku ~ $Count >1
      {
       $data = explode('+', $Memo,$Count);
       for($i = 0;$i < $Count; $i++)
        {
          if (strpos($data[$i], 'x') !== false)
          {
           $dataSKUAndQuantity = explode('x', $data[$i]);
           $sku = $dataSKUAndQuantity[0];
           $Quantity = $dataSKUAndQuantity[1];

           $sql = " insert into fa_shipping_record_from_memo(transaction_id,sku,quantity,sales_channel,store,the_month,the_year) ".
           " select "."'". $TransactionID ."','". $sku . "',". $Quantity. ",". $SaleChannel.",". $Store.",". $Month . ",". $Year ;
           DB::connection('mysql')->select($sql);
          }
        }
      }

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

    $TotalFreightHandlingReturnCost=0;
    $sql = " select sum(freight_handling_return_cost) as cost from fa_summary_chargeback_monthly where sales_channel = $SalesChannel
    and the_month = $Month and the_year = $Year ";
    $ds = DB::connection('mysql')->select( $sql);
    foreach($ds  as $d ){
      $TotalFreightHandlingReturnCost =  $this->iif(is_null($d->cost),0, $d->cost);
    }

    $TotalOtherFee = 0;
    $sql = " select sum(other_fee) as other_fee from fa_summary_chargeback_monthly where sales_channel = $SalesChannel
    and the_month = $Month and the_year = $Year ";
    $ds = DB::connection('mysql')->select( $sql);
    foreach($ds  as $d ){
      $TotalOtherFee =  $this->iif(is_null($d->other_fee),0, $d->other_fee);
    }

    $sql = " select sum(nest_sales)as nest_sales from sal_selling_summary_monthlys where sales_chanel = $SalesChannel
    and the_month = $Month and 	the_year = $Year and nest_sales > 0 ";
    $TotalNetSales = DB::connection('mysql')->select($sql);

    foreach($TotalNetSales  as $TotalNetSale ){
      $fTotalNetSale = $this->iif(is_null( $TotalNetSale->nest_sales),0, $TotalNetSale->nest_sales);
      //print_r('Channel:'. $SalesChannel. "- Total NetSales:" . $fTotalNetSale );
      //print_r('<br>' );
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

      foreach($ids  as $id ){
        $Chargeback = $ChargebackFeePerNet * $id->nest_sales;
        $FreightHandlingReturnCost = $FreightHandlingReturnCostPerNet * $id->nest_sales;
        $OtherFee = $OtherFeePerNet * $id->nest_sales;
        $sql = " update sal_selling_summary_monthlys set chargeback =  $Chargeback, freight_handling_return_cost	=  $FreightHandlingReturnCost,
        other_selling_expensives = $OtherFee where id = $id->id ";

        DB::connection('mysql')->select($sql);
      }
    }
  }

  // ============================================================================================================
  private function GetReturnRefund($sku,$SalesChannel,$Month,$Year){
    $sql='';
    switch ($SalesChannel){
          case 1: // AVC-WH
            $sql = " Select sum(quantity) as quantity, sum(amount) as amount from fa_avc_returns where sku = " . "'" . $sku . "'".
            " and the_month = $Month and the_year = $Year  and vendor_code like '%YES4A%'";
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
    if($SalesChannel != 6)  {
     // print_r($sql);
     // print_r('<br>');

      $ReturnRefunds = DB::connection('mysql')->select($sql);
      foreach( $ReturnRefunds as  $ReturnRefund){
        $this->gReturn = $this->iif(is_null($ReturnRefund->quantity),0,$ReturnRefund->quantity);
        $this->gRefund = $this->iif(is_null($ReturnRefund->amount),0,$ReturnRefund->amount);
      }
    }
  }
  // ============================================================================================================
    private function GetReturnRefundForEBay($sku, $Month,$Year,$StoreID){
    $sql = " Select sum(quantity) as quantity ,sum(-amount) as amount " .
    " from fa_selling_monthly_detail  where sku = " . "'" . $sku . "'".
    " and the_month = $Month and the_year = $Year  and store =  $StoreID " .
    " and sales_channel = 6  and ( type like '%Refund%')" ;

    $ReturnRefundFBAs = DB::connection('mysql')->select($sql);

    foreach($ReturnRefundFBAs as $ReturnRefundFBA){
      $this->gReturnEBAY = $this->iif(is_null($ReturnRefundFBA->quantity),0,$ReturnRefundFBA->quantity);
      $this->gRefundEBAY = $this->iif(is_null($ReturnRefundFBA->amount),0,$ReturnRefundFBA->amount);
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

          $sql = " select id, sku, rebate from  fa_amazon_promotions  where  invoice_number = '" . $InvoiceNo ."'" .
          " and the_month = $Month and the_year = $Year ";
          $ids = DB::connection('mysql')->select($sql);
          foreach($ids as $id)
          {
           // Phân bổ SUM total clip cho từng sku trong một hóa đơn promotion
            $sql = " update fa_amazon_promotions set clip_fee = $ClipFeePerRebate * $id->rebate where id = $id->id " ;
            DB::connection('mysql')->select($sql);
          }

        }// End for AVC

        // Bổ sung những sku không phát sinh số liệu bán hàng kênh AVC WH
        // nhưng có số liệu phát sinh của việc promotion vào bảng Summary
        $sql = "  insert into sal_selling_summary_monthlys(sku,promotion,sales_chanel,the_month,the_year)
        select sku, sum(rebate) as rebate ,1,$Month,$Year from fa_amazon_promotions WHERE the_month= $Month and the_year = $Year and vendor_code like '%Y4A%'
        and sku not in (select sku from  sal_selling_summary_monthlys where sales_chanel = 1 and the_month= $Month and the_year = $Year )
        group by  sku order by sku ";
        DB::connection('mysql')->select($sql);

        // Cập nhật data promotion của kênh avc-wh lên bảng tổng hợp
        $sql = " select sum(rebate) as fee, sum(clip_fee) as clip_fee, sku from  fa_amazon_promotions  where " .
        "the_month = $Month and the_year = $Year  and vendor_code like '%Y4A%' and rebate >0 group by sku ";
        $AvcPromotions = DB::connection('mysql')->select($sql);

        foreach($AvcPromotions as $AvcPromotion){
          $sql = " update sal_selling_summary_monthlys set promotion =  $AvcPromotion->fee,clip_fee = $AvcPromotion->clip_fee
          where sku = '". $AvcPromotion->sku ."'". " and the_month = $Month  and the_year = $Year and sales_chanel = 1 ";
          DB::connection('mysql')->select($sql);
        }

        // Bổ sung những sku không phát sinh số liệu bán hàng kênh AVC DS
        // nhưng có số liệu phát sinh của việc promotion vào bảng Summary
        $sql = "  insert into sal_selling_summary_monthlys(sku,promotion,sales_chanel,the_month,the_year)
        select sku, sum(rebate) as rebate, 2 , $Month,$Year from fa_amazon_promotions WHERE the_month= $Month and the_year = $Year and vendor_code like '%Y4B%'
        and sku not in (select sku from  sal_selling_summary_monthlys where sales_chanel = 2 and the_month= $Month and the_year = $Year )
        group by  sku order by sku ";
        DB::connection('mysql')->select($sql);

        // Cập nhật data promotion của kênh avc-ds lên bảng tổng hợp
        $sql = " select sum(rebate) as fee, sum(clip_fee) as clip_fee ,sku from  fa_amazon_promotions  where " .
        "the_month = $Month and the_year = $Year  and vendor_code like '%Y4B%' and rebate > 0 group by sku ";
        $AvcPromotions = DB::connection('mysql')->select($sql);

        foreach($AvcPromotions as $AvcPromotion){
          $sql = " update sal_selling_summary_monthlys set promotion =  $AvcPromotion->fee , clip_fee = $AvcPromotion->clip_fee
           where sku = '". $AvcPromotion->sku ."'". " and the_month = $Month  and the_year = $Year and sales_chanel = 2 ";
          DB::connection('mysql')->select($sql);
        }

        // Bổ sung những sku không phát sinh số liệu bán hàng kênh AVC DI
        // nhưng có số liệu phát sinh của việc promotion vào bảng Summary
        $sql = "  insert into sal_selling_summary_monthlys(sku,promotion,sales_chanel,the_month,the_year)
        select sku, sum(rebate) as rebate, 3, $Month,$Year  from fa_amazon_promotions WHERE the_month= $Month and the_year = $Year and vendor_code like '%Y4U%'
        and sku not in (select sku from  sal_selling_summary_monthlys where sales_chanel = 3 and the_month= $Month and the_year = $Year )
        group by  sku order by sku ";
        DB::connection('mysql')->select($sql);

        // Cập nhật data promotion của kênh avc-di lên bảng tổng hợp
        $sql = " select sum(rebate) as fee, sum(clip_fee) as clip_fee, sku from  fa_amazon_promotions  where " .
        "the_month = $Month and the_year = $Year  and vendor_code like '%Y4U%' and rebate >0 group by sku ";
        $AvcPromotions = DB::connection('mysql')->select($sql);
        foreach($AvcPromotions as $AvcPromotion){
          $sql = " update sal_selling_summary_monthlys set promotion =  $AvcPromotion->fee, clip_fee = $AvcPromotion->clip_fee
           where sku = '". $AvcPromotion->sku ."'".
          " and the_month = $Month  and the_year = $Year and sales_chanel = 3 ";
          DB::connection('mysql')->select($sql);
        }

        // FBA
        $sql = " select DISTINCT(amazon_order_id) as amazon_order_id  from fa_fba_fbm_promotion
        where fa_fba_fbm_promotion.the_month =  $Month  and fa_fba_fbm_promotion.the_year = $Year ";
        $ds= DB::connection('mysql')->select($sql);
        $FullFillment='';
        foreach($ds as $d){
          $sql = " select fulfillment from fa_amazon_idzo where order_number = '" . $d->amazon_order_id . "'" ;
          $d1s = DB::connection('mysql')->select($sql);
          foreach($d1s as $d1){$FullFillment = $d1->fulfillment;}
          $sql = " update fa_fba_fbm_promotion set fulfillment ='".$FullFillment . "'". " where amazon_order_id = '" . $d->amazon_order_id . "'" ;
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
    foreach($Sems as $Sem){
      $sql = " update sal_selling_summary_monthlys set seo_sem = sell_quantity * $Sem->sem_fee_per_unit
      where the_month = $Month and the_year = $Year and sales_chanel in (1,2,3,9,10)  and sku = '".  $Sem->sku ."'";
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

    // Phân bổ lại tiền SEM
    for($Channel = 1 ; $Channel <= 10;$Channel++)
    {
      $sql = " select (sem_fee) as sem_fee  from fa_summary_chargeback_monthly
      where the_month = $Month and the_year = $Year and 	sales_channel = $Channel  " ;
      $ds=  DB::connection('mysql')->select($sql);
      foreach($ds as $d){ $TotalSEMAct = $d->sem_fee; }

      $sql = " select sum(seo_sem) as seo_sem from sal_selling_summary_monthlys
      where the_month = $Month and the_year = $Year and 	sales_chanel = $Channel  " ;
      $ds=  DB::connection('mysql')->select($sql);
      foreach($ds as $d){ $TotalSEMTheo = $d->seo_sem; }

      if($TotalSEMTheo!=0){ $SemRate =  $TotalSEMAct/ $TotalSEMTheo; }
      else{$SemRate =1;}

      $sql = " update sal_selling_summary_monthlys set seo_sem = seo_sem * $SemRate
      where the_month = $Month and the_year = $Year and 	sales_chanel = $Channel  " ;
      print_r($sql);
      print_r('<br>');
      $ds=  DB::connection('mysql')->select($sql);
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

    //$theYear = request('year');
    $theYear = $request->input('year');
    $ReportType = $request->input('ReportType');
    $request->flash();

    //if($ReportType == 0)
    //{
      $plReport = DB::connection('mysql')->select(" select
      article,des,  account,  grant_total_total,  grant_total_xl,  grant_total_tc,  grant_total_wel,  mot_total,  mot_xl,
      mot_tc,  mot_wel,  hai_total,  hai_xl ,  hai_tc,  hai_wel,  ba_total,  ba_xl,  ba_tc,  ba_wel,  q1_total,
      q1_xl,  q1_tc,  q1_wel,  bon_total,  bon_xl,  bon_tc,  bon_wel,  nam_total,  nam_xl,  nam_tc,  nam_wel,
      sau_total,  sau_xl,  sau_tc,  sau_wel,  q2_total,  q2_xl,  q2_tc,  q2_wel,  bay_total,  bay_xl,  bay_tc,
      bay_wel,  tam_total,  tam_xl,  tam_tc,  tam_wel,  chin_total,  chin_xl,  chin_tc,  chin_wel,  q3_total,
      q3_xl,  q3_tc,  q3_wel,  muoi_total,  muoi_xl,  muoi_tc,  muoi_wel,  mmot_total,  mmot_xl,  mmot_tc,
      mmot_wel,  mhai_total,  mhai_xl,  mhai_tc,  mhai_wel,  q4_total,  q4_xl,  q4_tc,  q4_wel
      from fa_pl_reports where   the_year = $theYear order by article ");
      return view('FA.PLReport',compact('plReport'));
    //}
    //else
   // {
   //   return 'Under contruct';
   // }
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
    ,clip_fee , liability_insurance,  commission , other_fee, 	profit
    from sal_selling_summary_monthlys left join products on sal_selling_summary_monthlys.sku = products.product_sku
    where the_month = 0 and  the_year = 0 ";

    $dsSummary = DB::connection('mysql')->select( $sql);
    return view('FA.Summary',compact('dsSummary'));

  }

  public function LoadFASummary(Request $request)
  {
    $TheYear  = $request->input('year');
    $TheMonth  = $request->input('month');
    $TheChannel  = $request->input('channel');
    $TheStore  = $request->input('store');
    $sku  = $request->input('sku');
    $request->flash();

    $sql  = " select sku, products.title, sell_quantity ,   return_quantity ,    revenue ,     refund ,  nest_sales  , cogs,
    promotion ,    seo_sem ,    shiping_fee,  other_selling_expensives,

    sal_selling_summary_monthlys.dip , sal_selling_summary_monthlys.msf , selling_fees , fullfillment , chargeback
    ,coop ,freight_cost, freight_handling_return_cost , ebay_final_fee , paypal_fee , discount
    ,clip_fee , liability_insurance,  commission , other_fee , 	profit

    from sal_selling_summary_monthlys left join products on sal_selling_summary_monthlys.sku = products.product_sku
    where the_month = $TheMonth and  the_year = $TheYear ";

    if( $TheChannel != 0)
    {
      $sql  =  $sql  . " and sales_chanel = $TheChannel " ;
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
    ini_set('memory_limit','2048M');
    set_time_limit(10000);

    $TheYear = $request->input('year');
    $TheMonth= $request->input('month');
    $request->flash();
/*
    $RowBegin = 2;
    $RowEnd = 2;
    $SalesChanel = 0;

    $validator = Validator::make($request->all(),[
      'file'=>'required|max:45000|mimes:xlsx,xls,csv'
      ]);

    if($validator->passes())
    {
      $file = $request->file('file');
      $reader = new \PhpOffice\PhpSpreadsheet\Reader\Xlsx();

      // xóa hết data trong tháng
      DB::connection('mysql')->select (" delete from fa_selling_monthly_detail where the_month = $TheMonth and the_year = $TheYear  " );


        // Import sheet thứ 1 AVC-DS, thông tin bán hàng trên kênh avc-ds
        $SalesChanel = 2; // AVC-DS
        $RowBegin = 3;
        $reader->setLoadSheetsOnly(["AVC-DS", "AVC-DS"]);
        $spreadsheet = $reader->load($file);
        $RowEnd = $spreadsheet->getActiveSheet()->getHighestRow();
        print_r ( 'AVC-DS'.$RowEnd );
        print_r ( '<br>');
        DB::connection('mysql')->select (" delete from fa_selling_monthly_detail
        where the_month = $TheMonth and the_year = $TheYear and sales_channel = $SalesChanel " );

        for($i=$RowBegin; $i <= $RowEnd; $i++)
        {
            $cellValue1 = $spreadsheet->getActiveSheet()->getCellByColumnAndRow( 20,$i)->getValue();// Asin
            $cellValue2 = $spreadsheet->getActiveSheet()->getCellByColumnAndRow( 21,$i)->getValue();//SKU
            $cellValue3 = $spreadsheet->getActiveSheet()->getCellByColumnAndRow( 23,$i)->getValue();//Quantity
            $cellValue4 = $spreadsheet->getActiveSheet()->getCellByColumnAndRow( 28,$i)->getValue();// Amount
            $cellValue5 = $spreadsheet->getActiveSheet()->getCellByColumnAndRow( 1,$i)->getValue();// Order ID/ transasction id
            $cellValue6 = $spreadsheet->getActiveSheet()->getCellByColumnAndRow( 13,$i)->getValue();// ship_to_state

            if($cellValue1 != "" && $cellValue3 != 0){
              DB::connection('mysql')->table('fa_selling_monthly_detail')->insert(
                  ['asin'=>$cellValue1,'transaction_id'=>$cellValue5 ,'sku'=> $cellValue2,
                  'quantity'=>$cellValue3,'price'=>$cellValue4/$cellValue3,'amount'=>$cellValue4,
                  'ship_to_state'=>$cellValue6,'the_month'=>$TheMonth,'The_year'=>$TheYear,'sales_channel'=>$SalesChanel]);
            }
        }
      // End Import sheet thu 2 =-> AVC-DS
      // Import sheet thu  3 =-> Amazon-idzo chứa các thông tin bán hàng và thông tin khác của 2 kênh fbm-fba
      $RowBegin = 5;
      $reader->setLoadSheetsOnly(["Amazon-idzo", "Amazon-idzo"]);
      $spreadsheet = $reader->load($file);
      $RowEnd = $spreadsheet->getActiveSheet()->getHighestRow();
      print_r ('Amazon-idzo'.$RowEnd );
      print_r ( '<br>');
      DB::connection('mysql')->select (" delete from fa_amazon_idzo where the_month = $TheMonth and the_year = $TheYear ");

      for($i=$RowBegin; $i <= $RowEnd; $i++)
      {
          $cellValue1 = $spreadsheet->getActiveSheet()->getCellByColumnAndRow( 3,$i)->getValue();//  type
          $cellValue2 = $spreadsheet->getActiveSheet()->getCellByColumnAndRow( 7,$i)->getValue();//  Quantity
          $cellValue3 = $spreadsheet->getActiveSheet()->getCellByColumnAndRow( 9,$i)->getValue();//  Fulfillment
          $cellValue4 = $spreadsheet->getActiveSheet()->getCellByColumnAndRow( 13,$i)->getValue();// Product Sales
          $cellValue5 = $spreadsheet->getActiveSheet()->getCellByColumnAndRow( 14,$i)->getValue();// Shipping Credits
          $cellValue6 = $spreadsheet->getActiveSheet()->getCellByColumnAndRow( 18,$i)->getValue();// Selling Fees
          $cellValue7 = $spreadsheet->getActiveSheet()->getCellByColumnAndRow( 19,$i)->getValue();// FBA fees
          $cellValue8 = $spreadsheet->getActiveSheet()->getCellByColumnAndRow( 23,$i)->getValue();// sku
          $cellValue9 = $spreadsheet->getActiveSheet()->getCellByColumnAndRow( 4,$i)->getValue();// Order Number
          $cellValue10 = $spreadsheet->getActiveSheet()->getCellByColumnAndRow( 16,$i)->getValue();// Prômtion_rebate

          if($cellValue8 != "" && $cellValue2 != 0){
            DB::connection('mysql')->table('fa_amazon_idzo')->insert(
                ['type'=>$cellValue1, 'quantity'=> $cellValue2, 'fulfillment'=>$cellValue3,'product_sales'=>$cellValue4,
                'shipping_credits'=>$cellValue5,'selling_fees'=>$cellValue6, 'fba_fees'=> $cellValue7,  'sku'=> $cellValue8,
                'order_number'=>$cellValue9 ,'promotional_rebates'=>$cellValue10,'the_month'=>$TheMonth,'the_year'=>$TheYear]
            );
          }
      }
      // End Import sheet thu 3 =-> Amazon-idzo

      // Import sheet thu  3  bổ sung=-> Amazon Infideals -> thông tin
      $RowBegin = 3;
      $reader->setLoadSheetsOnly(["Amazon Infideals", "Amazon Infideals"]);
      $spreadsheet = $reader->load($file);
      $RowEnd = $spreadsheet->getActiveSheet()->getHighestRow();
      print_r ('Amazon Infideals'.$RowEnd );
      print_r ( '<br>');
     // DB::connection('mysql')->select (" delete from fa_amazon_idzo  where the_month = $TheMonth and the_year = $TheYear ");
      for($i=$RowBegin; $i <= $RowEnd; $i++)
      {
          $cellValue1 = $spreadsheet->getActiveSheet()->getCellByColumnAndRow( 3,$i)->getValue();// type
          $cellValue2 = $spreadsheet->getActiveSheet()->getCellByColumnAndRow( 8,$i)->getValue();//Quantity
          $cellValue3 = $spreadsheet->getActiveSheet()->getCellByColumnAndRow( 10,$i)->getValue();//Fulfillment
          $cellValue4 = $spreadsheet->getActiveSheet()->getCellByColumnAndRow( 15,$i)->getValue();// Product Sales
          $cellValue5 = $spreadsheet->getActiveSheet()->getCellByColumnAndRow( 17,$i)->getValue();// Shipping Credits
          $cellValue6 = $spreadsheet->getActiveSheet()->getCellByColumnAndRow( 24,$i)->getValue();//Selling Fees
          $cellValue7 = $spreadsheet->getActiveSheet()->getCellByColumnAndRow( 25,$i)->getValue();// FBA fees
          $cellValue8 = $spreadsheet->getActiveSheet()->getCellByColumnAndRow( 6,$i)->getValue();// sku
          $cellValue9 = $spreadsheet->getActiveSheet()->getCellByColumnAndRow( 4,$i)->getValue();// Order Number
          $cellValue10 = $spreadsheet->getActiveSheet()->getCellByColumnAndRow(21,$i)->getValue();// Promotion rebate

          if($cellValue8 != "" && $cellValue2 != 0){
            DB::connection('mysql')->table('fa_amazon_idzo')->insert(
                ['type'=>$cellValue1, 'quantity'=> $cellValue2, 'fulfillment'=>$cellValue3,'product_sales'=>$cellValue4,
                'shipping_credits'=>$cellValue5,'selling_fees'=>$cellValue6, 'fba_fees'=> $cellValue7,  'sku'=> $cellValue8,
                'order_number'=>$cellValue9 ,'promotional_rebates'=>$cellValue10,'the_month'=>$TheMonth,'The_year'=>$TheYear]
            );
          }
      }
      // End Import sheet thu 3 =-> Amazon Infideals

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
          $cellValue1 = $spreadsheet->getActiveSheet()->getCellByColumnAndRow( 13,$i)->getValue();// ASIN
          $cellValue2 = $spreadsheet->getActiveSheet()->getCellByColumnAndRow( 14,$i)->getValue();//SKU
          $cellValue3 = $spreadsheet->getActiveSheet()->getCellByColumnAndRow( 19,$i)->getValue();//Quantity
          $cellValue4 = $spreadsheet->getActiveSheet()->getCellByColumnAndRow( 20,$i)->getValue();// Price
          $cellValue5 = $spreadsheet->getActiveSheet()->getCellByColumnAndRow( 21,$i)->getValue();// Amount
          $cellValue6 = $spreadsheet->getActiveSheet()->getCellByColumnAndRow( 3,$i)->getValue();// VendorCode

          if($cellValue1 !="" && $cellValue3 != 0){
            DB::connection('mysql')->table('fa_avc_returns')->insert(
                ['vendor_code'=>$cellValue6, 'asin'=> $cellValue1, 'sku'=>$cellValue2,'quantity'=>$cellValue3,
                'price'=>$cellValue4,'amount'=>$cellValue5,'the_month'=>$TheMonth,'The_year'=>$TheYear]
            );
          }
      }
      // End Import sheet thu 4 =-> AVC Return

      // Import sheet thu  5 =-> WM DSV
      $RowBegin = 5;
      $SalesChanel = 4;// WM DSV
      $reader->setLoadSheetsOnly(["WM DSV", "WM DSV"]);
      $spreadsheet = $reader->load($file);
      $RowEnd = $spreadsheet->getActiveSheet()->getHighestRow();
      print_r ('WM DSV'.$RowEnd );
      print_r ( '<br>');
      DB::connection('mysql')->select (" delete from fa_selling_monthly_detail
      where the_month = $TheMonth and the_year = $TheYear  and sales_channel = $SalesChanel" );

      for($i=$RowBegin; $i <= $RowEnd; $i++)
      {
          $cellValue1 = $spreadsheet->getActiveSheet()->getCellByColumnAndRow(6,$i)->getValue();// SKU
          $cellValue2 = $spreadsheet->getActiveSheet()->getCellByColumnAndRow(8,$i)->getValue();// Quantity
          $cellValue3 = $spreadsheet->getActiveSheet()->getCellByColumnAndRow(9,$i)->getValue();// Price
          $cellValue4 = $spreadsheet->getActiveSheet()->getCellByColumnAndRow(10,$i)->getValue();//Amount
          $cellValue5 = $spreadsheet->getActiveSheet()->getCellByColumnAndRow(4,$i)->getValue();//POID-> transactionID


          if($cellValue1 != "" && $cellValue2 != 0){
            DB::connection('mysql')->table('fa_selling_monthly_detail')->insert(
             ['sku'=> $cellValue1, 'quantity'=>$cellValue2,'price'=>$cellValue3,'amount'=>$cellValue4,
             'sales_channel'=>$SalesChanel,'transaction_id'=>$cellValue5,'the_month'=>$TheMonth,'the_year'=>$TheYear ]);
          }
      }
      // End Import sheet thu 5 =-> WM DSV

      // Import sheet thu  6 =-> Walmart Market
      $RowBegin = 4;
      $SalesChanel = 5;// Walmart Market
      $reader->setLoadSheetsOnly(["Walmart Market", "Walmart Market"]);
      $spreadsheet = $reader->load($file);
      $RowEnd = $spreadsheet->getActiveSheet()->getHighestRow();
      print_r ('Walmart Market'.$RowEnd );
      print_r ( '<br>');
      DB::connection('mysql')->select (" delete from fa_walmart_market where the_month = $TheMonth and the_year = $TheYear " );

      for($i=$RowBegin; $i <= $RowEnd; $i++)
      {
          $cellValue1 = $spreadsheet->getActiveSheet()->getCellByColumnAndRow( 6,$i)->getValue();// Transaction Type
          $cellValue2 = $spreadsheet->getActiveSheet()->getCellByColumnAndRow( 8,$i)->getValue();//quantity
          $cellValue3 = $spreadsheet->getActiveSheet()->getCellByColumnAndRow( 9,$i)->getValue();//sku
          $cellValue4 = $spreadsheet->getActiveSheet()->getCellByColumnAndRow( 22,$i)->getValue();//Payable to Partner from Sale
          $cellValue5 = $spreadsheet->getActiveSheet()->getCellByColumnAndRow( 23,$i)->getValue();//Commission from Sale
          $cellValue6 = $cellValue4 + $cellValue5;

          if($cellValue1!="" && $cellValue2 !=0){
            DB::connection('mysql')->table('fa_walmart_market')->insert(
                [ 'transaction_type'=> $cellValue1, 'sku'=>$cellValue3,'quantity'=>$cellValue2,
                'amount'=> $cellValue6,'commission_from_sale'=> $cellValue5 ,'the_month'=>$TheMonth,'the_year'=>$TheYear]
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
           $cellValue1 = $spreadsheet->getActiveSheet()->getCellByColumnAndRow( 7,$i)->getValue();// sku
           $cellValue2 = $spreadsheet->getActiveSheet()->getCellByColumnAndRow( 8,$i)->getValue();//Price
           $cellValue3 = $spreadsheet->getActiveSheet()->getCellByColumnAndRow( 9,$i)->getValue();//quantity
           $cellValue4 = - $spreadsheet->getActiveSheet()->getCellByColumnAndRow( 11,$i)->getValue();//Amount


           if($cellValue1!="" && $cellValue3 !=0){
             DB::connection('mysql')->table('fa_wm_dsv_returns')->insert(
                 [ 'sku'=> $cellValue1,'price'=> $cellValue2, 'quantity'=>$cellValue3,
                 'amount'=>$cellValue4,'the_month'=>$TheMonth,'the_year'=>$TheYear]
             );
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
           $cellValue1 = $spreadsheet->getActiveSheet()->getCellByColumnAndRow( 3,$i)->getValue();// price
           $cellValue2 = $spreadsheet->getActiveSheet()->getCellByColumnAndRow( 7,$i)->getValue();//amazon-order-id
           $cellValue3 = $spreadsheet->getActiveSheet()->getCellByColumnAndRow( 11,$i)->getValue();//sku
           $cellValue4 = 1 ; //Quantity
           $cellValue5 = $cellValue1; // Amount
           $cellValue6 = $spreadsheet->getActiveSheet()->getCellByColumnAndRow( 5,$i)->getValue();//Description

           if($cellValue3!="" && $cellValue1 !=0){
             DB::connection('mysql')->table('fa_fba_fbm_promotion')->insert(
                 [ 'sku'=> $cellValue3, 'amazon_order_id'=>$cellValue2,'quantity'=>$cellValue4,'price'=> $cellValue1,
                 'amount'=>$cellValue5,'description'=>$cellValue6 ,'the_month'=>$TheMonth,'the_year'=>$TheYear]
             );
           }
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
          $cellValue1 = $spreadsheet->getActiveSheet()->getCellByColumnAndRow( 1,$i)->getValue();// ASIN
          $cellValue2 = $spreadsheet->getActiveSheet()->getCellByColumnAndRow( 21,$i)->getValue();//amount
          $cellValue3 = $spreadsheet->getActiveSheet()->getCellByColumnAndRow( 25,$i)->getValue();//sku

          if($cellValue3!="" && $cellValue2 !=0){
            DB::connection('mysql')->table('fa_fba_monthly_store_fee')->insert(
                ['asin'=>$cellValue1,'sku'=>$cellValue3,'amount'=>$cellValue2,'the_month'=>$TheMonth,'the_year'=>$TheYear]
            );
          }
      }
      // End Import sheet thu 9 => Monthly Store Fee


     // Import sheet thu  10 => Ebay Fitness
     $RowBegin = 5;
     $SalesChanel = 6;// Ebay
     $SalesStore = 1;//Ebay Fitness
     $reader->setLoadSheetsOnly(["Ebay Fitness", "Ebay Fitness"]);
     $spreadsheet = $reader->load($file);
     $RowEnd = 2264;//$spreadsheet->getActiveSheet()->getHighestRow();

     print_r ( 'Ebay Fitness'.$RowEnd );
     print_r ( '<br>');
     DB::connection('mysql')->select (" delete from fa_selling_monthly_detail where the_month = $TheMonth and the_year = $TheYear
     and sales_channel =  $SalesChanel and store =  $SalesStore " );

     for($i=$RowBegin; $i <= $RowEnd; $i++)
     {
         $cellValue1 = $spreadsheet->getActiveSheet()->getCellByColumnAndRow( 2,$i)->getValue();// Type
         $cellValue2 = $spreadsheet->getActiveSheet()->getCellByColumnAndRow( 15,$i)->getValue();//Quantity
         $cellValue3 = $spreadsheet->getActiveSheet()->getCellByColumnAndRow( 16,$i)->getValue();//amount
         $cellValue4 = $spreadsheet->getActiveSheet()->getCellByColumnAndRow( 28,$i)->getValue();//sku
         $cellValue5 = $spreadsheet->getActiveSheet()->getCellByColumnAndRow( 20,$i)->getValue();//Processing fee
         $cellValue6 = $spreadsheet->getActiveSheet()->getCellByColumnAndRow( 14,$i)->getValue();//TransactionID

         DB::connection('mysql')->table('fa_selling_monthly_detail')->insert(
         ['type'=>$cellValue1, 'sku'=> $cellValue4, 'quantity'=>$cellValue2,'price'=>$cellValue3 ,'amount'=>  $cellValue3,
         'payment_processing_fee'=> $cellValue5 ,'transaction_id'=>$cellValue6 ,
         'sales_channel'=> $SalesChanel,'store'=>$SalesStore ,'the_month'=>$TheMonth,'the_year'=>$TheYear]);
      }
     // End Import sheet thu 10 => Ebay Fitness

    // Import sheet thu  11 => Ebay Inc = Paypal Order Details
    $RowBegin = 4;
    $SalesChanel = 6;// Ebay
    $SalesStore = 2;//Ebay Inc
    $reader->setLoadSheetsOnly(["Paypal Order Details", "Paypal Order Details"]);
    $spreadsheet = $reader->load($file);
    $RowEnd = 395;//$spreadsheet->getActiveSheet()->getHighestRow();
    print_r ( 'Paypal Order Details'.$RowEnd );
    print_r ( '<br>');
    DB::connection('mysql')->select (" delete from fa_selling_monthly_detail where the_month = $TheMonth and the_year = $TheYear
    and sales_channel =  $SalesChanel and store =  $SalesStore " );

    for($i=$RowBegin; $i <= $RowEnd; $i++)
    {
        $cellValue1 = $spreadsheet->getActiveSheet()->getCellByColumnAndRow(2,$i)->getValue();// Type
        $cellValue2 = $spreadsheet->getActiveSheet()->getCellByColumnAndRow(17,$i)->getValue();//Quantity
        $cellValue3 = $spreadsheet->getActiveSheet()->getCellByColumnAndRow(18,$i)->getValue();//Price
        $cellValue4 = $spreadsheet->getActiveSheet()->getCellByColumnAndRow(19,$i)->getValue();//Revenue
        $cellValue5 = -$spreadsheet->getActiveSheet()->getCellByColumnAndRow(14,$i)->getValue();//Fee amount
        $cellValue6 = $spreadsheet->getActiveSheet()->getCellByColumnAndRow(15,$i)->getValue();//sku
        $cellValue7 = $spreadsheet->getActiveSheet()->getCellByColumnAndRow(3,$i)->getValue();//TransactionID



        DB::connection('mysql')->table('fa_selling_monthly_detail')->insert(
        [ 'type'=>$cellValue1,'sku'=> $cellValue6, 'quantity'=>$cellValue2,'price'=>$cellValue3 ,'amount'=> $cellValue4,
        'payment_processing_fee'=> $cellValue5,'transaction_id'=>$cellValue7,
        'sales_channel'=> $SalesChanel,'store'=>$SalesStore ,'the_month'=>$TheMonth,'the_year'=>$TheYear]);

    }
    // End Import sheet thu 11=> Ebay Inc = Paypal Order Details


     // Import sheet thu  12 => Ebay Infideals
     $RowBegin = 5;
     $SalesChanel = 6;// Ebay
     $SalesStore = 3;//Ebay Infideals
     $reader->setLoadSheetsOnly(["Ebay Infideals", "Ebay Infideals"]);
     $spreadsheet = $reader->load($file);
     $RowEnd = 822;//$spreadsheet->getActiveSheet()->getHighestRow();
     print_r ( 'Ebay Infideals'.$RowEnd );
     print_r ( '<br>');
     DB::connection('mysql')->select (" delete from fa_selling_monthly_detail where the_month = $TheMonth and the_year = $TheYear
     and sales_channel =  $SalesChanel and store =  $SalesStore " );



     for($i=$RowBegin; $i <= $RowEnd; $i++)
     {
         $cellValue1 = $spreadsheet->getActiveSheet()->getCellByColumnAndRow( 2,$i)->getValue();// Type
         $cellValue2 = $spreadsheet->getActiveSheet()->getCellByColumnAndRow( 15,$i)->getValue();//Quantity
         $cellValue3 = $spreadsheet->getActiveSheet()->getCellByColumnAndRow( 16,$i)->getValue();//Price
         $cellValue4 = $spreadsheet->getActiveSheet()->getCellByColumnAndRow( 28,$i)->getValue();//sku
         $cellValue5 = $spreadsheet->getActiveSheet()->getCellByColumnAndRow( 20,$i)->getValue();//Payment Processing Fee
         $cellValue6 = $spreadsheet->getActiveSheet()->getCellByColumnAndRow( 14,$i)->getValue();//Transaction ID

         DB::connection('mysql')->table('fa_selling_monthly_detail')->insert(
         [ 'type'=>$cellValue1,'sku'=> $cellValue4, 'quantity'=>$cellValue2,'price'=>$cellValue3 ,'amount'=>  $cellValue3,
         'payment_processing_fee'=>$cellValue5,'transaction_id'=>$cellValue6,'sales_channel'=>$SalesChanel,'store'=>$SalesStore ,
         'the_month'=>$TheMonth,'the_year'=>$TheYear]);

     }
     // End Import sheet thu 12 => Ebay Infideals

     // Import sheet thu  13=> Ebay Idzo
     $RowBegin = 5;
     $SalesChanel = 6;// Ebay
     $SalesStore = 4;//Ebay Idzo
     $reader->setLoadSheetsOnly(["Ebay Idzo", "Ebay Idzo"]);
     $spreadsheet = $reader->load($file);
     $RowEnd = 489;//$spreadsheet->getActiveSheet()->getHighestRow();
     print_r ( 'Ebay Idzo'.$RowEnd );
     print_r ( '<br>');
     DB::connection('mysql')->select (" delete from fa_selling_monthly_detail where the_month = $TheMonth and the_year = $TheYear
     and sales_channel =  $SalesChanel and store =  $SalesStore " );

     for($i=$RowBegin; $i <= $RowEnd; $i++)
     {
         $cellValue1 = $spreadsheet->getActiveSheet()->getCellByColumnAndRow( 2,$i)->getValue();// Type
         $cellValue2 = $spreadsheet->getActiveSheet()->getCellByColumnAndRow( 15,$i)->getValue();//Quantity
         $cellValue3 = $spreadsheet->getActiveSheet()->getCellByColumnAndRow( 16,$i)->getValue();//Price
         $cellValue4 = $spreadsheet->getActiveSheet()->getCellByColumnAndRow( 28,$i)->getValue();//sku
         $cellValue5 = $spreadsheet->getActiveSheet()->getCellByColumnAndRow( 20,$i)->getValue();//Processing Fee
         $cellValue6 = $spreadsheet->getActiveSheet()->getCellByColumnAndRow( 14,$i)->getValue();//TransactionID
         //print_r('type= '. $cellValue1 );
         //print_r('<br>');

         DB::connection('mysql')->table('fa_selling_monthly_detail')->insert(
         ['type'=>$cellValue1,'sku'=> $cellValue4, 'quantity'=>$cellValue2,'price'=>$cellValue3 ,'amount'=>  $cellValue3,'payment_processing_fee'=>$cellValue5,
         'transaction_id'=>$cellValue6,'sales_channel'=> $SalesChanel,'store'=>$SalesStore ,'the_month'=>$TheMonth,'the_year'=>$TheYear]);

     }
    // End Import sheet thu 13 => Ebay Idzo
    // Import sheet thu  14 => UnitCost

    $RowBegin = 2;
    $reader->setLoadSheetsOnly(["Unit Cost", "Unit Cost"]);
    $spreadsheet = $reader->load($file);
    $RowEnd = $spreadsheet->getActiveSheet()->getHighestRow();
    print_r ( 'Unit Cost'.$RowEnd );
    print_r ( '<br>');
    DB::connection('mysql')->select (" delete from fa_unit_costs where the_month = $TheMonth  and the_year = $TheYear " );

    for($i=$RowBegin; $i <= $RowEnd; $i++)
    {
        $cellValue1 = $spreadsheet->getActiveSheet()->getCellByColumnAndRow( 1,$i)->getValue();// sku
        $cellValue2 = $spreadsheet->getActiveSheet()->getCellByColumnAndRow( 3,$i)->getValue();//Replace sku
        $cellValue3 = $spreadsheet->getActiveSheet()->getCellByColumnAndRow( 4,$i)->getValue();//The month text
        $cellValue4 = $spreadsheet->getActiveSheet()->getCellByColumnAndRow( 5,$i)->getValue();//COGS
        $cellValue5 = $spreadsheet->getActiveSheet()->getCellByColumnAndRow( 6,$i)->getValue();//FOB


        if( $cellValue2 ==""){ $cellValue2 = '-';}

        if($cellValue1!="" && $cellValue4 >0){
          DB::connection('mysql')->table('fa_unit_costs')->insert(
              [ 'sku'=> $cellValue1, 'replace_sku'=>$cellValue2,'the_month_text'=>$cellValue3 ,'cogs'=> $cellValue4,
                'fob'=>$cellValue5,'the_month'=>$TheMonth,'the_year'=>$TheYear ]
          );
        }
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
        $cellValue1 = $spreadsheet->getActiveSheet()->getCellByColumnAndRow( 1,$i)->getValue();// sku
        $cellValue2 = $spreadsheet->getActiveSheet()->getCellByColumnAndRow( 10,$i)->getValue();//SEO Fee

        if($cellValue1!="" ){
          DB::connection('mysql')->table('fa_sem_seo_amazon')->insert(
              [ 'sku'=> $cellValue1, 'sem_fee_per_unit'=>$cellValue2,'the_month'=>$TheMonth,'the_year'=>$TheYear ]);
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
        $cellValue1 = $spreadsheet->getActiveSheet()->getCellByColumnAndRow( 1,$i)->getValue(); // Invoice Number
        $cellValue2 = $spreadsheet->getActiveSheet()->getCellByColumnAndRow( 14,$i)->getValue();//Rebate
        $cellValue3 = $spreadsheet->getActiveSheet()->getCellByColumnAndRow( 18,$i)->getValue();// sku
        $cellValue4 = $spreadsheet->getActiveSheet()->getCellByColumnAndRow( 20,$i)->getValue();//Vendor

        if($cellValue3!="" ){
          DB::connection('mysql')->table('fa_amazon_promotions')->insert(
              [ 'sku'=> $cellValue3, 'vendor_code'=>$cellValue4,'rebate'=>$cellValue2 ,'invoice_number'=>$cellValue1
              ,'the_month'=>$TheMonth,'the_year'=>$TheYear ]
          );
        }
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
         $cellValue1 = $spreadsheet->getActiveSheet()->getCellByColumnAndRow( 22,$i)->getValue();// sku
         $cellValue2 = $spreadsheet->getActiveSheet()->getCellByColumnAndRow( 25,$i)->getValue();//quantity
         $cellValue3 = $spreadsheet->getActiveSheet()->getCellByColumnAndRow( 24,$i)->getValue();//Price
         $cellValue4 = $spreadsheet->getActiveSheet()->getCellByColumnAndRow( 26,$i)->getValue();//Revenue

         $cellValue5 = $spreadsheet->getActiveSheet()->getCellByColumnAndRow( 27,$i)->getValue();//Paypal Fee

         if($cellValue1!="" && $cellValue2 !=0){

          DB::connection('mysql')->table('fa_selling_monthly_detail')->insert(
              [ 'sku'=> $cellValue1, 'quantity'=>$cellValue2,'price'=>$cellValue3,'amount'=>$cellValue4,'paypal_fee'=> $cellValue5 ,
                'the_month'=>$TheMonth,'the_year'=>$TheYear,'sales_channel'=> $SalesChanel]  );
        }
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
         $cellValue1 = $spreadsheet->getActiveSheet()->getCellByColumnAndRow(1,$i)->getValue();// sku
         $cellValue2 = $spreadsheet->getActiveSheet()->getCellByColumnAndRow(7,$i)->getValue();// AVC-DS_RS
         $cellValue3 = $spreadsheet->getActiveSheet()->getCellByColumnAndRow(6,$i)->getValue();// AVC-WH_RS
         $cellValue4 = $spreadsheet->getActiveSheet()->getCellByColumnAndRow(13,$i)->getValue();//AVC-DS_REV
         $cellValue5 = $spreadsheet->getActiveSheet()->getCellByColumnAndRow(12,$i)->getValue();// AVC-WH_REV

         $cellValue6 = $spreadsheet->getActiveSheet()->getCellByColumnAndRow(8,$i)->getValue();//DI_RS
         $cellValue7 = $spreadsheet->getActiveSheet()->getCellByColumnAndRow(14,$i)->getValue();//DI_REV

        DB::connection('mysql')->table('fa_amazon_real_sales')->insert(
        ['sku'=> $cellValue1,'avc_ds_rs'=>$cellValue2,'avc_wh_rs'=>$cellValue3 ,'avc_ds_rev'=>$cellValue4,
        'avc_wh_rev'=>$cellValue5,'di_rs'=>$cellValue6,'di_rev'=>$cellValue7,'the_month'=>$TheMonth,'the_year'=>$TheYear]);

     }
     // End Import sheet thu 19 => Real sale AMZ


       // Import sheet thu  20 => Đối chiếu thực nhận DSV
       $RowBegin = 3;
       $reader->setLoadSheetsOnly(["Đối chiếu thực nhận DSV", "Đối chiếu thực nhận DSV"]);
       $spreadsheet = $reader->load($file);
       $RowEnd = $spreadsheet->getActiveSheet()->getHighestRow();
       print_r ( 'Đối chiếu thực nhận DSV RowEnd'.$RowEnd );
       print_r ( '<br>');
       DB::connection('mysql')->select (" delete from fa_dsv_promotion_sem_actual where the_month = $TheMonth and the_year = $TheYear " );

       for($i=$RowBegin; $i <= $RowEnd; $i++)
       {
           $cellValue1 = $spreadsheet->getActiveSheet()->getCellByColumnAndRow(1,$i)->getValue();// sku
           $cellValue2 = $spreadsheet->getActiveSheet()->getCellByColumnAndRow(4,$i)->getValue();//Quantity
           $cellValue3 = $spreadsheet->getActiveSheet()->getCellByColumnAndRow(7,$i)->getValue();//Promotion Fee
           $cellValue4 = $spreadsheet->getActiveSheet()->getCellByColumnAndRow(9,$i)->getValue();//SEM -fee

           if($cellValue1!=""){
            DB::connection('mysql')->table('fa_dsv_promotion_sem_actual')->insert(
                ['sku'=> $cellValue1,'quantity'=>$cellValue2,'promotion_fee'=>$cellValue3,'sem_fee'=>$cellValue4,
                'the_month'=>$TheMonth,'the_year'=>$TheYear]
            );
          }
       }
       // End Import sheet thu 20 => Đối chiếu thực nhận DSV

      // Import sheet thu  21 => Shipment_realtime_report
      $RowBegin = 4;
      $reader->setLoadSheetsOnly(["Shipment_realtime_report", "Shipment_realtime_report"]);
      $spreadsheet = $reader->load($file);
      $RowEnd = $spreadsheet->getActiveSheet()->getHighestRow();
      print_r ( 'Shipment_realtime_report RowEnd '.$RowEnd );
      print_r ( '<br>');
      DB::connection('mysql')->select (" delete from fa_shipment_realtime_reports where the_month = $TheMonth and the_year = $TheYear " );

      for($i=$RowBegin; $i <= $RowEnd; $i++)
      {
          $cellValue1 = $spreadsheet->getActiveSheet()->getCellByColumnAndRow(3,$i)->getValue();// transaction_id
          $cellValue2 = $spreadsheet->getActiveSheet()->getCellByColumnAndRow(5,$i)->getValue();//Storename
          $cellValue3 = $spreadsheet->getActiveSheet()->getCellByColumnAndRow(6,$i)->getValue();//cost
          $cellValue4 = $spreadsheet->getActiveSheet()->getCellByColumnAndRow(8,$i)->getValue();//status
          $cellValue5 = $spreadsheet->getActiveSheet()->getCellByColumnAndRow(11,$i)->getValue();//memo 1
          $cellValue6 = $spreadsheet->getActiveSheet()->getCellByColumnAndRow(12,$i)->getValue();//memo 2
          $cellValue7 = $spreadsheet->getActiveSheet()->getCellByColumnAndRow(13,$i)->getValue();//memo 3

          if($cellValue1!=""){
          DB::connection('mysql')->table('fa_shipment_realtime_reports')->insert(
              ['transaction_id'=> $cellValue1,'store_name'=>$cellValue2,'cost'=>$cellValue3,'status'=>$cellValue4,
              'memomot'=> $cellValue5,'memohai'=> $cellValue6,'memoba'=> $cellValue7,'the_month'=>$TheMonth,'the_year'=>$TheYear]
          );
        }
      }
      // End Import sheet thu 21 => Shipment_realtime_report

      // Import sheet thu  22 => DIP
      $RowBegin = 5;
      $reader->setLoadSheetsOnly(["DIP", "DIP"]);
      $spreadsheet = $reader->load($file);
      $RowEnd = $spreadsheet->getActiveSheet()->getHighestRow();
      print_r ( 'DIP RowEnd'.$RowEnd );
      print_r ( '<br>');
      DB::connection('mysql')->select (" delete from fa_dip_monthly where the_month = $TheMonth and the_year = $TheYear " );

      for($i=$RowBegin; $i <= $RowEnd; $i++)
      {
          $cellValue1 = $spreadsheet->getActiveSheet()->getCellByColumnAndRow(1,$i)->getValue();// sku
          $cellValue2 = $spreadsheet->getActiveSheet()->getCellByColumnAndRow(11,$i)->getValue();//deep


          if($cellValue1!=""){
          DB::connection('mysql')->table('fa_dip_monthly')->insert(
              ['sku'=> $cellValue1,'dip'=>$cellValue2,'the_month'=>$TheMonth,'the_year'=>$TheYear]
          );
        }
      }
      // End Import sheet thu 22 => DIP

      // Import sheet thu  23 => ChargebackAndOther
      $RowBegin = 3;
      $reader->setLoadSheetsOnly(["ChargebackAndOther", "ChargebackAndOther"]);
      $spreadsheet = $reader->load($file);
      $RowEnd = 13;//$spreadsheet->getActiveSheet()->getHighestRow();
      print_r ( 'ChargebackAndOther RowEnd '.$RowEnd );
      print_r ( '<br>');
      DB::connection('mysql')->select (" delete from fa_summary_chargeback_monthly where the_month = $TheMonth and the_year = $TheYear " );

      for($i=$RowBegin; $i <= $RowEnd; $i++)
      {
          $cellValue1 = $spreadsheet->getActiveSheet()->getCellByColumnAndRow(1,$i)->getValue();// Sales ID
          $cellValue2 = $spreadsheet->getActiveSheet()->getCellByColumnAndRow(2,$i)->getValue();// Sales Channel
          $cellValue3 = $spreadsheet->getActiveSheet()->getCellByColumnAndRow(3,$i)->getValue();// Chargeback
          $cellValue4 = $spreadsheet->getActiveSheet()->getCellByColumnAndRow(4,$i)->getValue();// Freight cost return and handling return
          $cellValue5 = $spreadsheet->getActiveSheet()->getCellByColumnAndRow(5,$i)->getValue();// Other Fee
          $cellValue6 = $spreadsheet->getActiveSheet()->getCellByColumnAndRow(15,$i)->getValue();// Product Liability Insurance
          $cellValue7 = $spreadsheet->getActiveSheet()->getCellByColumnAndRow(16,$i)->getValue();// SEM fee

          $cellValue8 = $spreadsheet->getActiveSheet()->getCellByColumnAndRow(6,$i)->getValue();// ebay_final_fee
          $cellValue9 = $spreadsheet->getActiveSheet()->getCellByColumnAndRow(7,$i)->getValue();// paypal_fee

          DB::connection('mysql')->table('fa_summary_chargeback_monthly')->insert(
              ['sales_channel'=>$cellValue1,'sales_channel_name'=> $cellValue2,'chargeback_fee'=>$cellValue3,
              'freight_handling_return_cost'=>$cellValue4,'other_fee'=>$cellValue5,'liability_insurance'=> $cellValue6,
              'sem_fee'=> $cellValue7,'ebay_final_fee'=>$cellValue8,'paypal_fee'=> $cellValue9 ,'the_month'=>$TheMonth,'the_year'=>$TheYear]
          );
      }
      // End Import sheet thu 23 => Chargeback

      // Import sheet thu  24 => Return Refund Craiglist Website
      $RowBegin = 5;
      $reader->setLoadSheetsOnly(["Return Refund Craiglist Website", "Return Refund Craiglist Website"]);
      $spreadsheet = $reader->load($file);
      $RowEnd = $spreadsheet->getActiveSheet()->getHighestRow();
      print_r ( 'Return Refund Craiglist Website RowEnd'.$RowEnd );
      print_r ( '<br>');
      DB::connection('mysql')->select (" delete from fa_return_refund_craiglist_website where the_month = $TheMonth and the_year = $TheYear " );

      for($i=$RowBegin; $i <= $RowEnd; $i++)
      {
          $cellValue1 = $spreadsheet->getActiveSheet()->getCellByColumnAndRow(1,$i)->getValue();// sku
          $cellValue2 = $spreadsheet->getActiveSheet()->getCellByColumnAndRow(2,$i)->getValue();// quantity
          $cellValue3 = $spreadsheet->getActiveSheet()->getCellByColumnAndRow(3,$i)->getValue();// Refund
          $cellValue4 = $spreadsheet->getActiveSheet()->getCellByColumnAndRow(4,$i)->getValue();// Craiglist

          if($cellValue1!=""){
          DB::connection('mysql')->table('fa_return_refund_craiglist_website')->insert(
              ['sku'=> $cellValue1,'quantity'=>$cellValue2,'amount'=>$cellValue3,'is_craiglist'=>$cellValue4,
              'the_month'=>$TheMonth,'the_year'=>$TheYear]);
          }
      }
      // End Import sheet thu 24 => Return Refund Craiglist Website

       // Import sheet thu  25 => Craigslist Orders
       $SalesChanel = 7;// Craiglist/Local
       $RowBegin = 5;
       $reader->setLoadSheetsOnly(["Craigslist Orders", "Craigslist Orders"]);
       $spreadsheet = $reader->load($file);
       $RowEnd = $spreadsheet->getActiveSheet()->getHighestRow();

       print_r ( 'Craigslist Orders'.$RowEnd );
       print_r ( '<br>');
       DB::connection('mysql')->select (" delete from fa_selling_monthly_detail where the_month = $TheMonth and the_year = $TheYear
       and sales_channel = $SalesChanel " );

       for($i=$RowBegin; $i <= $RowEnd; $i++)
       {
           $cellValue1 = $spreadsheet->getActiveSheet()->getCellByColumnAndRow(1,$i)->getValue();// sku
           $cellValue2 = $spreadsheet->getActiveSheet()->getCellByColumnAndRow(3,$i)->getValue();// quantity
           $cellValue3 = $spreadsheet->getActiveSheet()->getCellByColumnAndRow(7,$i)->getValue();// amount
           $cellValue4 = $spreadsheet->getActiveSheet()->getCellByColumnAndRow(8,$i)->getValue();// discount

           if($cellValue1!=""){
           DB::connection('mysql')->table('fa_selling_monthly_detail')->insert(
               ['sku'=> $cellValue1,'quantity'=>$cellValue2,'amount'=>$cellValue3,'discount_value'=>$cellValue4,
               'sales_channel'=> $SalesChanel ,'the_month'=>$TheMonth,'the_year'=>$TheYear]);
           }
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

        // Import sheet thu  27 => Scale
        $RowBegin = 2;
        $reader->setLoadSheetsOnly(["Scale", "Scale"]);
        $spreadsheet = $reader->load($file);
        $RowEnd = $spreadsheet->getActiveSheet()->getHighestRow();
        print_r ( 'Scale'.$RowEnd );
        print_r ( '<br>');
        DB::connection('mysql')->select (" delete from fa_scale_monthly where the_month = $TheMonth and the_year = $TheYear " );

        for($i=$RowBegin; $i <= $RowEnd; $i++)
        {
          $cellValue1 = $spreadsheet->getActiveSheet()->getCellByColumnAndRow(1,$i)->getValue();// fob
          $cellValue2 = $spreadsheet->getActiveSheet()->getCellByColumnAndRow(2,$i)->getValue();// Freight Cost
          $cellValue3 = $spreadsheet->getActiveSheet()->getCellByColumnAndRow(3,$i)->getValue();// Duties($)
          $cellValue4 = $spreadsheet->getActiveSheet()->getCellByColumnAndRow(4,$i)->getValue();// Pallet Fee($)


          if(is_numeric($cellValue2)){
          DB::connection('mysql')->table('fa_scale_monthly')->insert(
              ['fob'=> $cellValue1,'freight_cost'=>$cellValue2, 'duties'=> $cellValue3,'pallet_fee'=> $cellValue4,
              'the_month'=>$TheMonth,'the_year'=>$TheYear]);
          }
        }  // End Import sheet  27 => Scale

      print_r ( 'Hoàn tất việc import data' );
      print_r ( '<br>');
    }// end pass
    else
    {
      print_r ( 'Không thực hiện import được ');
    }
*/
 // $this->ConvertDataToSummary($TheMonth,$TheYear);
  $this->MakePLReport($TheYear);
}// end fucntion

//---------------------------------------------------
public function GetFirtDateOfMonth($Year,$Month)
{
  return  (string)$Year . '-'. (string)$Month. '-01';
}
//---------------------------------------------------
public function GetLastDateOfMonth($Year,$Month)
{
  if($Month ==12)
    {
      $Month =1;
      $Year =$Year +1;
    }
    $FirstDate = (string)$Year . '-'. (string)$Month. '-01';
    return   date('Y-m-d',strtotime( $FirstDate. '- 1 days'));
}

//---------------------------------------------------
//---------------------------------------------------
public function GetSkuFromAsin($Asin)
{
  $Result = '';
  $sql = " select  products.product_sku as sku from products
  inner join  amazon_products on products.id = amazon_products.product_id
  inner join  asin on amazon_products.asin = asin.asin
  where asin.asin = '$Asin' and LENGTH(products.product_sku) = 4 " ;

  $ds = DB::connection('mysql_it')->select($sql);
  foreach($ds as $d){  $Result = $d->sku; }

  return $Result;
}

//---------------------------------------------------

}

<?php

namespace App\Http\Controllers\Sales;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

use App\Http\Controllers\SYS\SysController;
use DB;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Reader\IReadFilter;
use App\Models\Sales\SalesProductInfor;
use Validator;
use DateTime;
use GuzzleHttp\Client;
use Auth;

class ImportSalesProductController extends SysController
{
     // --------------------------------------------------------------------
     public function index()
     {
      return view('SAL.SalesProductInfor.LoadFileProductSalesInfor');
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
/*
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
       }//For promotion

       */
      /*
       //Suppied by IT
       $RowBegin = 2;
       $reader->setLoadSheetsOnly(["Suppied by IT", "Suppied by IT"]);
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
         //$order_date = date('Y-m-d', time());
         $store_name = $spreadsheet->getActiveSheet()->getCellByColumnAndRow(1,$i)->getValue();
         $store_order_id	= $spreadsheet->getActiveSheet()->getCellByColumnAndRow(3,$i)->getValue();
         $order_status	= $spreadsheet->getActiveSheet()->getCellByColumnAndRow(4,$i)->getValue();
         //$order_date	= $spreadsheet->getActiveSheet()->getCellByColumnAndRow(5,$i)->getFormattedValue();
         $order_date	= $spreadsheet->getActiveSheet()->getCellByColumnAndRow(5,$i)->getValue();
         $order_date = date("Y-m-d h:i:s", strtotime( $order_date));  
         $shipping_address	= $spreadsheet->getActiveSheet()->getCellByColumnAndRow(6,$i)->getValue();
         $shipto_state	= $spreadsheet->getActiveSheet()->getCellByColumnAndRow(7,$i)->getValue();
         $shipto_zipcode	= $spreadsheet->getActiveSheet()->getCellByColumnAndRow(8,$i)->getValue();
         $product_sku =  $spreadsheet->getActiveSheet()->getCellByColumnAndRow(9,$i)->getValue();
         $quantity= $spreadsheet->getActiveSheet()->getCellByColumnAndRow(12,$i)->getValue();
         if($quantity=='') $quantity = 0;
         $revenue= $spreadsheet->getActiveSheet()->getCellByColumnAndRow(13,$i)->getValue();
         if($revenue=='') $revenue = 0;

        if( $store_order_id != '') 
          {
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

        //  $sql = "select id from prd_product where product_sku = '$product_sku' ";
        //  $ds= DB::connection('mysql')->select($sql);
        //  foreach($ds as $d)  { $product_id = $d->id;}
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
             $sql = " select id as MyCount from sal_amz_seller_order_dt where amz_order_id  = $id and sku = '$product_sku' ";
             if(!$this->IsExist('mysql',$sql)){// chua ton tai detail product nay
               // insert to detail
               DB::connection('mysql')->table('sal_amz_seller_order_dt')->insert(
               ['amz_order_id'=>$id,'sku'=>$product_sku,'quantity'=>$quantity,
               'price'=>$price,'amount'=>$revenue]);
             }
           }else{// da ton tai master
              // Kiem tra trong detail cua phieu nau da co hang nay chua
              $sql = " select id as MyCount from sal_amz_seller_order_dt where amz_order_id  = $id and sku = '$product_sku' ";
              if(!$this->IsExist('mysql',$sql)){// chua ton tai detail product nay
                // insert to detail
                DB::connection('mysql')->table('sal_amz_seller_order_dt')->insertGetId(
                ['amz_order_id'=>$id,'sku'=>$product_sku,'quantity'=>$quantity,
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
             $sql = " select id as MyCount from sal_amz_vendor_order_dt where amz_order_id  = $id and sku = '$product_sku' ";
             if(!$this->IsExist('mysql',$sql)){// chua ton tai detail product nay
               // insert to detail
                DB::connection('mysql')->table('sal_amz_vendor_order_dt')->insert(
               ['amz_order_id'=>$id ,'sku'=>$product_sku,'quantity'=>$quantity,
               'price'=>$price,'amount'=>$revenue]);
             }

           }else{// da ton tai master
              // Kiem tra trong detail cua phieu nau da co hang nay chua
              $sql = " select id as MyCount from sal_amz_vendor_order_dt where amz_order_id  = $id and sku = '$product_sku' ";
              if(!$this->IsExist('mysql',$sql)){// chua ton tai detail product nay
                // insert to detail
                DB::connection('mysql')->table('sal_amz_vendor_order_dt')->insert(
                ['amz_order_id'=>$id,'sku'=>$product_sku,'quantity'=>$quantity,
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
              $sql = " select id as MyCount from sal_order_dt where order_id   = $id and sku = '$product_sku'  ";
              if(!$this->IsExist('mysql',$sql)){// chua ton tai detail product nay
                // insert to detail
                DB::connection('mysql')->table('sal_order_dt')->insert(
                ['order_id'=>$id,'sku'=>$product_sku,'quantity'=>$quantity,
                'price'=>$price,'amount'=>$revenue]);
              }

            }else{// da ton tai master
               // Kiem tra trong detail cua phieu nau da co hang nay chua
               $sql = " select id as MyCount from sal_order_dt where order_id   = $id and sku = '$product_sku' ";
               if(!$this->IsExist('mysql',$sql)){// chua ton tai detail product nay
                 // insert to detail
                 DB::connection('mysql')->table('sal_order_dt')->insert(
                 ['order_id'=>$id,'sku'=>$product_sku,'quantity'=>$quantity,
                 'price'=>$price,'amount'=>$revenue]);
               }

            }

         }//end if Phân bổ vào các bảng dựa vào sales channel
        }//
       }// for IT suply
 
       $RowBegin = 3;
       $reader->setLoadSheetsOnly(["Real Sales (WH+DI+DS)", "Real Sales (WH+DI+DS)"]);
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
         $TheWeek = $spreadsheet->getActiveSheet()->getCellByColumnAndRow(22,$i)->getValue();
         $TheYear = $spreadsheet->getActiveSheet()->getCellByColumnAndRow(23,$i)->getValue();
         $MarketPlace =1;// Amazon
         $product_id = $this->GetProductIDFromAsin($Asin, $MarketPlace);
         $id =0;
         $sql = " select id  from sal_sum_vendor_order where product_id  = $product_id 
         and the_week  = $TheWeek and the_year =  $TheYear  ";

         $ds= DB::connection('mysql')->select($sql);
         foreach($ds as $d)  {  $id = $this->iif(is_null($d->id),0,$d->id); }
         if( $id ==0 && $product_id != 0)
         {
           DB::connection('mysql')->table('sal_sum_vendor_order')->insert(
           ['product_id'=>$product_id ,'quantity'=>$TotalOrder,'the_week'=>$TheWeek,'the_year'=>$TheYear]);
         }elseif($product_id == 0)
         {
           print_r('Asin'.$Asin );
           print_r('<br>' );
         }

       }


      $RowBegin = 2;
      $reader->setLoadSheetsOnly(["AVC DS", "AVC DS"]);
      $spreadsheet = $reader->load($file);
      $RowEnd = $spreadsheet->getActiveSheet()->getHighestRow();
      print_r ('last record of AVC DS '.$RowEnd );
      print_r ( '<br>');
      $channel_id=2;
      $store_id=0;
      $product_id=0;
      $id = 0;
      for($i=$RowBegin; $i <= $RowEnd; $i++)
      {
        $order_id = $spreadsheet->getActiveSheet()->getCellByColumnAndRow(1,$i)->getValue();
        $status	= $spreadsheet->getActiveSheet()->getCellByColumnAndRow(2,$i)->getValue();
        $WarehouseCode = $spreadsheet->getActiveSheet()->getCellByColumnAndRow(3,$i)->getValue();

        $order_date	= $spreadsheet->getActiveSheet()->getCellByColumnAndRow(4,$i)->getValue();
        $order_date = date("Y-m-d h:i:s", strtotime($order_date));  

        $RequiredShipDate = $spreadsheet->getActiveSheet()->getCellByColumnAndRow(5,$i)->getValue();
        $RequiredShipDate = date("Y-m-d h:i:s", strtotime($RequiredShipDate));  
        $ShipMethod = $spreadsheet->getActiveSheet()->getCellByColumnAndRow(6,$i)->getValue();
        $ShipMethodCode= $spreadsheet->getActiveSheet()->getCellByColumnAndRow(7,$i)->getValue();
        $ShipToName= $spreadsheet->getActiveSheet()->getCellByColumnAndRow(8,$i)->getValue();

        $address1 = $spreadsheet->getActiveSheet()->getCellByColumnAndRow(9,$i)->getValue();
        $address2 = $spreadsheet->getActiveSheet()->getCellByColumnAndRow(10,$i)->getValue();
        $address3 = $spreadsheet->getActiveSheet()->getCellByColumnAndRow(11,$i)->getValue();


        $ship_to_city = $spreadsheet->getActiveSheet()->getCellByColumnAndRow(12,$i)->getValue();
        $shipto_state	= $spreadsheet->getActiveSheet()->getCellByColumnAndRow(13,$i)->getValue();
        $shipto_zipcode= $spreadsheet->getActiveSheet()->getCellByColumnAndRow(14,$i)->getValue();
        $shipto_country= $spreadsheet->getActiveSheet()->getCellByColumnAndRow(15,$i)->getValue();
        $PhoneNumber= $spreadsheet->getActiveSheet()->getCellByColumnAndRow(16,$i)->getValue();

        $is_it_gift	= $spreadsheet->getActiveSheet()->getCellByColumnAndRow(17,$i)->getValue();
        $item_cost	= $spreadsheet->getActiveSheet()->getCellByColumnAndRow(18,$i)->getValue();
        $sku	= $spreadsheet->getActiveSheet()->getCellByColumnAndRow(19,$i)->getValue();
        $sku = $this->left($sku,4);
        $asin	= $spreadsheet->getActiveSheet()->getCellByColumnAndRow(20,$i)->getValue();
        $item_title	= $spreadsheet->getActiveSheet()->getCellByColumnAndRow(21,$i)->getValue();
        $item_title= str_replace("'", "",  $item_title);
        $quantity	= $spreadsheet->getActiveSheet()->getCellByColumnAndRow(22,$i)->getValue();
        $gift_message = $spreadsheet->getActiveSheet()->getCellByColumnAndRow(23,$i)->getValue();

        $tracking_id= $spreadsheet->getActiveSheet()->getCellByColumnAndRow(26,$i)->getValue();
        $shipped_date= $spreadsheet->getActiveSheet()->getCellByColumnAndRow(27,$i)->getValue();
        $shipped_date = date("Y-m-d h:i:s", strtotime($shipped_date));  

        // Kiểm tra xem đã có order đó trong database chưa
        $id =0;
        $sql = " select id  from sal_amz_vendor_orders_z where order_id = '$order_id' 
        and channel_id  = $channel_id  ";
        $ds= DB::connection('mysql')->select($sql);

        foreach($ds as $d)  {  $id = $this->iif(is_null($d->id),0,$d->id); }

        if( $id == 0 ){// chua ton tai order nay trong database  
          
          // insert to master
          $id = DB::connection('mysql')->table('sal_amz_vendor_orders_z')->insertGetId(
          [ 
          'order_id'=>$order_id ,'channel_id'=>$channel_id, 'warehouse_code'=> $WarehouseCode , 'order_date'=> $order_date	 ,
          'required_ship_date'=>$RequiredShipDate , 'ship_method'=>$ShipMethod ,'ship_method_code'=>$ShipMethod ,
          'ship_to_name'=>$ShipToName,'address_1'=>$address1,'address_2'=>$address2 ,'address_3'=>$address3 , 
          'ship_to_city'=> $ship_to_city, 'ship_to_state'=>  $shipto_state	, 'ship_to_zip_code'=> $shipto_zipcode , 
          'ship_to_country'=>$shipto_country,'phone_number' => $PhoneNumber ]);

          // insert to detail 
          DB::connection('mysql')->table('sal_amz_vendor_order_dt_z')->insert(
          ['vendor_order_id'=>$id,'sku'=>$sku,'item_cost'=>$item_cost,
          'is_it_gift'=>$is_it_gift,'asin'=>$asin ,'item_title' =>$item_title,'quantity'=>$quantity,'gift_message'=>$gift_message,
          'tracking_id'=> $tracking_id ,'shipped_date'=>$shipped_date,'status'=>$status ]);
          
        }else{// da ton tai master
            // Kiem tra trong detail cua phieu nau da co sku này với trạng thái này đã tồn tại chưa
            $sql = " select id as MyCount from sal_amz_vendor_order_dt_z where vendor_order_id  = $id and sku = '$sku' and status = '$status' ";
            if(!$this->IsExist('mysql',$sql)){// chua ton tai detail product nay
              // insert to detail
              DB::connection('mysql')->table('sal_amz_vendor_order_dt_z')->insert(
              ['vendor_order_id'=>$id,'sku'=>$sku,'item_cost'=>$item_cost,
              'is_it_gift'=>$is_it_gift,'asin'=>$asin ,'item_title' =>$item_title,'quantity'=>$quantity,'gift_message'=>$gift_message,
              'tracking_id'=> $tracking_id ,'shipped_date'=>$shipped_date,'status'=>$status ]);
            }

        }

      }// for AVC DS

       // FBA FBM
       $RowBegin = 2;
       $reader->setLoadSheetsOnly(["FBA-FBM", "FBA-FBM"]);
       $spreadsheet = $reader->load($file);
       $RowEnd = $spreadsheet->getActiveSheet()->getHighestRow();
       print_r ('FBA-FBM '.$RowEnd );
       print_r ( '<br>');
       $hdm_sku = 0;
       $product_id=0;
       $id = 0;
       $channel_id = 0;
       for($i=$RowBegin; $i <= $RowEnd; $i++)
       {
        $type =  $spreadsheet->getActiveSheet()->getCellByColumnAndRow(3,$i)->getValue();
        $sku	 =  $spreadsheet->getActiveSheet()->getCellByColumnAndRow(5,$i)->getValue();
        if(strlen($sku ) >=4) {
          $sku  = $this->left( $sku,4);
          $sql = " select id as MyCount from sal_product_informations where sku = '$sku'";
         if( $this->IsExist('mysql',$sql)== true ){ $hdm_sku  = 1;}
        }

        if(($hdm_sku == 1 ) && ($type == 'Order' || $type == 'Refund' || $type == 'Cancel'  || $type == 'CANCEL') )
         {
          $order_date	=  $spreadsheet->getActiveSheet()->getCellByColumnAndRow(1,$i)->getValue();
          $order_date = date("Y-m-d h:i:s", strtotime($order_date));  
          $settlement_id =  $spreadsheet->getActiveSheet()->getCellByColumnAndRow(2,$i)->getValue();
          //$type =  $spreadsheet->getActiveSheet()->getCellByColumnAndRow(3,$i)->getValue();
          $order_id	=  $spreadsheet->getActiveSheet()->getCellByColumnAndRow(4,$i)->getValue();

          $marketplace	=  $spreadsheet->getActiveSheet()->getCellByColumnAndRow(8,$i)->getValue();
          $account_type	=  $spreadsheet->getActiveSheet()->getCellByColumnAndRow(9,$i)->getValue();
          $fulfillment=  $spreadsheet->getActiveSheet()->getCellByColumnAndRow(10,$i)->getValue();

          if( $fulfillment =='Amazon'){$channel_id = 9;}
          elseif($fulfillment =='Seller'){$channel_id = 10;}

          $order_city=  $spreadsheet->getActiveSheet()->getCellByColumnAndRow(11,$i)->getValue();
          $order_state=  $spreadsheet->getActiveSheet()->getCellByColumnAndRow(12,$i)->getValue();
          $order_postal	=  $spreadsheet->getActiveSheet()->getCellByColumnAndRow(13,$i)->getValue();
          $tax_collection_model=  $spreadsheet->getActiveSheet()->getCellByColumnAndRow(14,$i)->getValue();

          
         // $sku	 =  $spreadsheet->getActiveSheet()->getCellByColumnAndRow(5,$i)->getValue();
         // $sku  = $this->left( $sku,4);
          $des=  $spreadsheet->getActiveSheet()->getCellByColumnAndRow(6,$i)->getValue();
          $des= str_replace("'", "",  $des);
          $quantity =  $spreadsheet->getActiveSheet()->getCellByColumnAndRow(7,$i)->getValue();

          $product_sales =  $spreadsheet->getActiveSheet()->getCellByColumnAndRow(15,$i)->getValue();
          $product_sales_taxs =  $spreadsheet->getActiveSheet()->getCellByColumnAndRow(16,$i)->getValue();
          $shipping_credits	= $spreadsheet->getActiveSheet()->getCellByColumnAndRow(17,$i)->getValue();
          $shipping_credits_tax =$spreadsheet->getActiveSheet()->getCellByColumnAndRow(18,$i)->getValue();
          $gift_wrap_credits = $spreadsheet->getActiveSheet()->getCellByColumnAndRow(19,$i)->getValue();
          $giftwrap_credits_tax = $spreadsheet->getActiveSheet()->getCellByColumnAndRow(20,$i)->getValue();
          $promotional_rebates = $spreadsheet->getActiveSheet()->getCellByColumnAndRow(21,$i)->getValue();

          $promotional_rebates_tax= $spreadsheet->getActiveSheet()->getCellByColumnAndRow(22,$i)->getValue();
          $marketplace_withheld_tax	= $spreadsheet->getActiveSheet()->getCellByColumnAndRow(23,$i)->getValue();
          $selling_fees	= $spreadsheet->getActiveSheet()->getCellByColumnAndRow(24,$i)->getValue();
          $fba_fees = $spreadsheet->getActiveSheet()->getCellByColumnAndRow(25,$i)->getValue();
          $other_transaction_fees = $spreadsheet->getActiveSheet()->getCellByColumnAndRow(26,$i)->getValue();
          $other = $spreadsheet->getActiveSheet()->getCellByColumnAndRow(27,$i)->getValue();
          $total = $spreadsheet->getActiveSheet()->getCellByColumnAndRow(28,$i)->getValue();
        
          $id =0;
          $sql = " select id  from sal_amz_seller_orders_z where order_id  = $order_id ";
          $ds= DB::connection('mysql')->select($sql);
          foreach($ds as $d)  {  $id = $this->iif(is_null($d->id),0,$d->id); }

          if( $id == 0 && strlen($sku ) ==4)// Chưa lưu order này vào database
          {
            // Lưu vào master
            $id = DB::connection('mysql')->table('sal_amz_seller_orders_z')->insertGetID(
            ['order_id'=>$order_id ,'order_date'=>$order_date,'settlement_id'=>$settlement_id,'marketplace'=>$marketplace,
            'account_type'=>$account_type	, 'fulfillment'=>$fulfillment, 'order_city'=>$order_city,'order_state'=>$order_state,
            'order_postal'=> $order_postal	, 'tax_collection_model'=>$tax_collection_model,'channel_id'=>$channel_id]);

            // Lưu vào detail
            DB::connection('mysql')->table('sal_amz_seller_order_dt_z')->insert(
            ['seller_order_id'=>$id ,'sku'=>$order_id ,'type'=>$type,'sku'=>$sku,'des'=>$des,'quantity'=>$quantity ,
            'product_sales'=>$product_sales ,  'product_sales_taxs'=>$product_sales_taxs,
            'shipping_credits'=> $shipping_credits,  'shipping_credits_tax'=>$shipping_credits_tax ,
            'gift_wrap_credits'=> $gift_wrap_credits , 'giftwrap_credits_tax'=>$giftwrap_credits_tax,
            'promotional_rebates'=>$promotional_rebates ,'promotional_rebates_tax'=>$promotional_rebates_tax,
            'marketplace_withheld_tax'=>$marketplace_withheld_tax	,  'selling_fees'=>$selling_fees	,
            'fba_fees'=>$fba_fees , 'other_transaction_fees' =>$other_transaction_fees ,'other'=> $other ,'total'=>$total]);


          }elseif($id > 0)// Order đó đã tồn tại
          {
           // Kiểm tra xem sku đó và type đó đã tồn tại chưa
           $sql = " select id from sal_amz_seller_order_dt_z where sku = '$sku' and type = '$type'";
           $temp = $this->IsExistNew('mysql',$sql);
           if($temp == 0 )// Chưa tồn tại, thì ghi sku này xuống
           { 
            DB::connection('mysql')->table('sal_amz_seller_order_dt_z')->insert(
            ['seller_order_id'=>$id ,'sku'=>$order_id ,'type'=>$type,'sku'=>$sku,'des'=>$des,'quantity'=>$quantity ,
            'product_sales'=>$product_sales ,  'product_sales_taxs'=>$product_sales_taxs,
            'shipping_credits'=> $shipping_credits,  'shipping_credits_tax'=>$shipping_credits_tax ,
            'gift_wrap_credits'=> $gift_wrap_credits , 'giftwrap_credits_tax'=>$giftwrap_credits_tax,
            'promotional_rebates'=>$promotional_rebates ,'promotional_rebates_tax'=>$promotional_rebates_tax,
            'marketplace_withheld_tax'=>$marketplace_withheld_tax	,  'selling_fees'=>$selling_fees	,
            'fba_fees'=>$fba_fees , 'other_transaction_fees' =>$other_transaction_fees ,'other'=> $other ,'total'=>$total]);
           }else
           {
             print_r($sql);
             print_r('<br>');
           }
          }
        } // type = order, refund, cancel
      }// for fbafbm

       // WM DSV
       $RowBegin = 2;
       $reader->setLoadSheetsOnly(["WM-DSV", "WM-DSV"]);
       $spreadsheet = $reader->load($file);
       $RowEnd = $spreadsheet->getActiveSheet()->getHighestRow();
       print_r ('WM-DSV '.$RowEnd );
       print_r ( '<br>');
       $hdm_sku = 0;
       $product_id=0;
       $id = 0;
       $channel_id = 0;
       for($i=$RowBegin; $i <= $RowEnd; $i++)
       {
        $order_id =  $spreadsheet->getActiveSheet()->getCellByColumnAndRow(1,$i)->getValue();
        $distribution_center_id=  $spreadsheet->getActiveSheet()->getCellByColumnAndRow(2,$i)->getValue();
        $distribution_center=  $spreadsheet->getActiveSheet()->getCellByColumnAndRow(3,$i)->getValue();
        $date_marked_shipped =  $spreadsheet->getActiveSheet()->getCellByColumnAndRow(4,$i)->getValue();
        
        $sYear = $this->left( $date_marked_shipped,4);
        $sDay = $this->left( $date_marked_shipped,-2);
        $sMonth = $this->left( $date_marked_shipped,-4);
        $sMonth = $this->left( $sMonth,2);
        $date_marked_shipped =  $sYear . '-' . $sMonth . '-' .  $sDay;
        $date_marked_shipped = date("Y-m-d", strtotime( $date_marked_shipped));  

        $amount_paid_to_vendor=  $spreadsheet->getActiveSheet()->getCellByColumnAndRow(5,$i)->getValue();
        $report_date=  $spreadsheet->getActiveSheet()->getCellByColumnAndRow(6,$i)->getValue();

        $sql = " select id as MyCount from sal_wm_dsv_orders_z where order_no = '$order_id' ";
        $id = $this->IsExistNew('mysql',$sql);
        if($id == 0)// chưa có master
        {
          // Lưu thông tin master
          $sql  = " select id, order_no,status_name,order_date, required_ship_date , ship_method, ship_method_code,
          ship_to_state,  ship_to_address,  ship_to_zip, shipped_date,  cus_name 
          from sal_orders where order_no  = '$order_id' and channel_id = 4  ";
          $ds = DB::connection('mysql')->select($sql);
          foreach($ds as $d) {
            $masterID = $d->id;
            $order_no = $d->order_no;
            $order_date = $d->order_date;
            $status_name = $d->status_name;
            $required_ship_date = $d->required_ship_date;
            $ship_method = $d->ship_method;
            $ship_method_code = $d->ship_method_code;
            $ship_to_state = $d->ship_to_state;
            $ship_to_address = $d->ship_to_address;
            $ship_to_zip = $d->ship_to_zip;
            $shipped_date = $d->shipped_date;
            $cus_name  = $d->cus_name;
          
            $NewID =DB::connection('mysql')->table('sal_wm_dsv_orders_z')->insertGetID(
            ['order_no'=>$order_no,'order_date'=>$order_date,'status_name'=>$status_name ,
            'required_ship_date'=>$required_ship_date ,'ship_method'=> $ship_method ,
            'ship_method_code'=>$ship_method_code ,'ship_to_state'=>$ship_to_state,
            'ship_to_address'=> $ship_to_address,'cus_name'=>$cus_name]);

            // Lưu thông tin detail
            $sql =  " insert into  sal_wm_dsv_order_dt_z(order_id, product_id, sku, asin,  quantity, price, 
            amount,commission, tracking_id ) 
            select $NewID , product_id, sku, asin,  quantity, price, amount,commission, tracking_id 
            from sal_order_dt where order_id =  $masterID ";
            DB::connection('mysql')->select($sql);
        }
        }
      }// For

       // WM-MKP
       $RowBegin = 3;
       $reader->setLoadSheetsOnly(["WM-MKP", "WM-MKP"]);
       $spreadsheet = $reader->load($file);
       $RowEnd = $spreadsheet->getActiveSheet()->getHighestRow();
       print_r ('WM-MKP '.$RowEnd );
       print_r ( '<br>');
     
       for($i=$RowBegin; $i <= $RowEnd; $i++)
       {
        $order_no =  $spreadsheet->getActiveSheet()->getCellByColumnAndRow(1,$i)->getValue();
        $order_line  =  $spreadsheet->getActiveSheet()->getCellByColumnAndRow(2,$i)->getValue();
        $po_no =  $spreadsheet->getActiveSheet()->getCellByColumnAndRow(3,$i)->getValue();
        $po_line  =  $spreadsheet->getActiveSheet()->getCellByColumnAndRow(4,$i)->getValue();
        $partner_order =  $spreadsheet->getActiveSheet()->getCellByColumnAndRow(5,$i)->getValue();

        $ship_to_state =  $spreadsheet->getActiveSheet()->getCellByColumnAndRow(15,$i)->getValue();
        $ship_to_county =  $spreadsheet->getActiveSheet()->getCellByColumnAndRow(16,$i)->getValue();
        $county_code =  $spreadsheet->getActiveSheet()->getCellByColumnAndRow(17,$i)->getValue();
        $ship_to_city =  $spreadsheet->getActiveSheet()->getCellByColumnAndRow(18,$i)->getValue();
        $zip_code =  $spreadsheet->getActiveSheet()->getCellByColumnAndRow(19,$i)->getValue();
        $shipping_method =  $spreadsheet->getActiveSheet()->getCellByColumnAndRow(20,$i)->getValue();

       
        $sku  =  $spreadsheet->getActiveSheet()->getCellByColumnAndRow(9,$i)->getValue();
        $gtin  =  $spreadsheet->getActiveSheet()->getCellByColumnAndRow(10,$i)->getValue();
        $item_name =  $spreadsheet->getActiveSheet()->getCellByColumnAndRow(11,$i)->getValue();
        $product_tax_code =  $spreadsheet->getActiveSheet()->getCellByColumnAndRow(12,$i)->getValue();

        $transaction_type =  $spreadsheet->getActiveSheet()->getCellByColumnAndRow(6,$i)->getValue();
        $transaction_date_time  =  $spreadsheet->getActiveSheet()->getCellByColumnAndRow(7,$i)->getValue();
        $transaction_date_time = date("Y-m-d", strtotime( $transaction_date_time));  
        $shipped_qty  =  $spreadsheet->getActiveSheet()->getCellByColumnAndRow(8,$i)->getValue();
       
        $shipping_tax_code  =  $spreadsheet->getActiveSheet()->getCellByColumnAndRow(13,$i)->getValue();
        $gift_wrap_tax_code =  $spreadsheet->getActiveSheet()->getCellByColumnAndRow(14,$i)->getValue();


        $total_tender =  $spreadsheet->getActiveSheet()->getCellByColumnAndRow(21,$i)->getValue();
        $payable_to_partner_from_sale =  $spreadsheet->getActiveSheet()->getCellByColumnAndRow(22,$i)->getValue();
        $commission_from_sale =  $spreadsheet->getActiveSheet()->getCellByColumnAndRow(23,$i)->getValue();
        $commission_rate =  $spreadsheet->getActiveSheet()->getCellByColumnAndRow(24,$i)->getValue();
        $gross_sales_revenue =  $spreadsheet->getActiveSheet()->getCellByColumnAndRow(25,$i)->getValue();
        $refunded_retail_sales =  $spreadsheet->getActiveSheet()->getCellByColumnAndRow(26,$i)->getValue();
        $sales_refund_for_escalation =  $spreadsheet->getActiveSheet()->getCellByColumnAndRow(27,$i)->getValue();
        $gross_shipping_revenue =  $spreadsheet->getActiveSheet()->getCellByColumnAndRow(28,$i)->getValue();
        $gross_shipping_refunded =  $spreadsheet->getActiveSheet()->getCellByColumnAndRow(29,$i)->getValue();
        $shipping_refund_for_escalation =  $spreadsheet->getActiveSheet()->getCellByColumnAndRow(30,$i)->getValue();


        $net_shipping_revenue =  $spreadsheet->getActiveSheet()->getCellByColumnAndRow(31,$i)->getValue();
        $gross_fee_revenue =  $spreadsheet->getActiveSheet()->getCellByColumnAndRow(32,$i)->getValue();
        $gross_fee_refunded =  $spreadsheet->getActiveSheet()->getCellByColumnAndRow(33,$i)->getValue();
        $fee_refund_for_escalation =  $spreadsheet->getActiveSheet()->getCellByColumnAndRow(34,$i)->getValue();
        $net_fee_revenue =  $spreadsheet->getActiveSheet()->getCellByColumnAndRow(35,$i)->getValue();
        $gift_wrap_quantity =  $spreadsheet->getActiveSheet()->getCellByColumnAndRow(36,$i)->getValue();
        $gross_gift_wrap_revenue =  $spreadsheet->getActiveSheet()->getCellByColumnAndRow(37,$i)->getValue();
        $gross_gift_wrap_refunded  =  $spreadsheet->getActiveSheet()->getCellByColumnAndRow(38,$i)->getValue();
        $gift_wrap_refund_for_escalation =  $spreadsheet->getActiveSheet()->getCellByColumnAndRow(39,$i)->getValue();
        $net_gift_wrap_revenue =  $spreadsheet->getActiveSheet()->getCellByColumnAndRow(40,$i)->getValue();
        $tax_on_sales_revenue =  $spreadsheet->getActiveSheet()->getCellByColumnAndRow(41,$i)->getValue();
        $tax_on_shipping_revenue =  $spreadsheet->getActiveSheet()->getCellByColumnAndRow(42,$i)->getValue();
        $tax_on_gift_wrap_revenue =  $spreadsheet->getActiveSheet()->getCellByColumnAndRow(43,$i)->getValue();
        $tax_on_fee_revenue =  $spreadsheet->getActiveSheet()->getCellByColumnAndRow(44,$i)->getValue();
        $effective_tax_rate =  $spreadsheet->getActiveSheet()->getCellByColumnAndRow(45,$i)->getValue();
        $tax_on_refunded_sales =  $spreadsheet->getActiveSheet()->getCellByColumnAndRow(46,$i)->getValue();
        $tax_on_shipping_refund =  $spreadsheet->getActiveSheet()->getCellByColumnAndRow(47,$i)->getValue();
        $tax_on_gift_wrap_refund =  $spreadsheet->getActiveSheet()->getCellByColumnAndRow(48,$i)->getValue();
        $tax_on_fee_refund  =  $spreadsheet->getActiveSheet()->getCellByColumnAndRow(49,$i)->getValue();
        $tax_on_sales_refund_for_escalation  =  $spreadsheet->getActiveSheet()->getCellByColumnAndRow(50,$i)->getValue();
        $tax_on_shipping_refund_for_escalation =  $spreadsheet->getActiveSheet()->getCellByColumnAndRow(51,$i)->getValue();
        $tax_on_gift_wrap_refund_for_escalation =  $spreadsheet->getActiveSheet()->getCellByColumnAndRow(52,$i)->getValue();
        $tax_on_fee_refund_for_escalation =  $spreadsheet->getActiveSheet()->getCellByColumnAndRow(53,$i)->getValue();
        $total_net_tax_collected =  $spreadsheet->getActiveSheet()->getCellByColumnAndRow(54,$i)->getValue();
        $tax_withheld =  $spreadsheet->getActiveSheet()->getCellByColumnAndRow(55,$i)->getValue();
        $adjustment_description =  $spreadsheet->getActiveSheet()->getCellByColumnAndRow(56,$i)->getValue();
        $adjustment =  $spreadsheet->getActiveSheet()->getCellByColumnAndRow(57,$i)->getValue();
        $code_original =  $spreadsheet->getActiveSheet()->getCellByColumnAndRow(58,$i)->getValue();
        $item_price =  $spreadsheet->getActiveSheet()->getCellByColumnAndRow(59,$i)->getValue();
        $original_commission_amount =  $spreadsheet->getActiveSheet()->getCellByColumnAndRow(60,$i)->getValue();
        $spec_category =  $spreadsheet->getActiveSheet()->getCellByColumnAndRow(61,$i)->getValue();
        $contract_category  =  $spreadsheet->getActiveSheet()->getCellByColumnAndRow(62,$i)->getValue();
        $product_type =  $spreadsheet->getActiveSheet()->getCellByColumnAndRow(63,$i)->getValue();
        $flex_commission_rule =  $spreadsheet->getActiveSheet()->getCellByColumnAndRow(64,$i)->getValue();
        $return_reason_code =  $spreadsheet->getActiveSheet()->getCellByColumnAndRow(65,$i)->getValue();
        $return_reason_description  =  $spreadsheet->getActiveSheet()->getCellByColumnAndRow(66,$i)->getValue();
        $fee_withheld_flag =  $spreadsheet->getActiveSheet()->getCellByColumnAndRow(67,$i)->getValue();
        $fulfillment_type =  $spreadsheet->getActiveSheet()->getCellByColumnAndRow(68,$i)->getValue();


        $sql = " select id as MyCount from sal_wm_mkp_orders_z where order_no = '$order_no' ";
        $id = $this->IsExistNew('mysql',$sql);
        if($id == 0)// chưa có master
        {
          // Insert Master
          $NewID =DB::connection('mysql')->table('sal_wm_mkp_orders_z')->insertGetID(
          ['order_no'=>$order_no,'order_line'=>$order_line,'po_no'=>$po_no ,
          'po_line'=>$po_line,'partner_order'=> $partner_order ,
          'ship_to_state'=>$ship_to_state ,'ship_to_county'=>$ship_to_county,
          'county_code'=> $county_code,'ship_to_city'=>$ship_to_city,'shipping_method'=> $shipping_method ]);

          // Insert Detail
          DB::connection('mysql')->table('sal_wm_mkp_order_dt_z')->insertGetID(
          [
            'sku'=>$sku  ,'gtin'=>$gtin,'item_name'=>$item_name ,'product_tax_code'=>$product_tax_code ,
            'transaction_type'=>$transaction_type ,'transaction_date_time'=> $transaction_date_time,
            'shipped_qty'=> $shipped_qty,'shipping_tax_code'=>$shipping_tax_code,
            'gift_wrap_tax_code'=>$gift_wrap_tax_code, 'total_tender'=>$total_tender ,
            'total_tender'=>$payable_to_partner_from_sale ,'commission_from_sale'=>$commission_from_sale,
            'commission_rate'=>$commission_rate , 'gross_sales_revenue'=>$gross_sales_revenue ,
            'refunded_retail_sales'=>$refunded_retail_sales , 'sales_refund_for_escalation'=>$sales_refund_for_escalation ,
            'gross_shipping_revenue'=> $gross_shipping_revenue ,'gross_shipping_refunded'=>$gross_shipping_refunded ,
            'shipping_refund_for_escalation'=>$shipping_refund_for_escalation,'net_shipping_revenue'=>$net_shipping_revenue,      
            'gross_fee_revenue'=>$gross_fee_revenue ,'gross_fee_refunded'=>$gross_fee_refunded, 
            'fee_refund_for_escalation'=>$fee_refund_for_escalation , 'net_fee_revenue'=>$net_fee_revenue ,
            'gift_wrap_quantity'=>$gift_wrap_quantity , 'gross_gift_wrap_revenue'=>$gross_gift_wrap_revenue ,
            'gross_gift_wrap_refunded'=>$gross_gift_wrap_refunded , 'gift_wrap_refund_for_escalation'=>$gift_wrap_refund_for_escalation ,
            'net_gift_wrap_revenue'=>$net_gift_wrap_revenue, 'tax_on_sales_revenue'=> $tax_on_sales_revenue,
            'tax_on_shipping_revenue'=> $tax_on_shipping_revenue ,'tax_on_gift_wrap_revenue'=> $tax_on_gift_wrap_revenue ,
            'tax_on_fee_revenue'=> $tax_on_fee_revenue, 'effective_tax_rate'=>$effective_tax_rate ,
            'tax_on_refunded_sales'=>$tax_on_refunded_sales ,'tax_on_shipping_refund'=>$tax_on_shipping_refund ,
            'tax_on_gift_wrap_refund'=>$tax_on_gift_wrap_refund, 'tax_on_fee_refund'=> $tax_on_fee_refund  ,
            'tax_on_sales_refund_for_escalation'=>$tax_on_sales_refund_for_escalation  , 
            'tax_on_shipping_refund_for_escalation'=>$tax_on_shipping_refund_for_escalation ,
            'tax_on_gift_wrap_refund_for_escalation'=> $tax_on_gift_wrap_refund_for_escalation ,
            'tax_on_fee_refund_for_escalation'=>$tax_on_fee_refund_for_escalation ,
            'total_net_tax_collected'=>$total_net_tax_collected ,
            'tax_withheld'=>$tax_withheld ,'adjustment_description'=>$adjustment_description,
            'adjustment'=> $adjustment , 'code_original'=> $code_original ,'item_price'=>$item_price ,
            'original_commission_amount'=>$original_commission_amount ,'spec_category'=>$spec_category ,
            'contract_category'=> $contract_category  , 'product_type'=>$product_type ,
            'flex_commission_rule'=>$flex_commission_rule , 'return_reason_code'=>$return_reason_code,
            'return_reason_description'=>$return_reason_description , 'fee_withheld_flag' => $fee_withheld_flag ,
            'fulfillment_type'=>$fulfillment_type ,'order_id'=>$NewID
           ]);
        }else// đã tồn tại master
        {
          $sql = " select id as MyCount from sal_wm_mkp_order_dt_z where order_id = $id and transaction_type = '$transaction_type' ";
          if($this->IsExistNew('mysql',$sql) == 0)
          {
            // inssert Detail
            DB::connection('mysql')->table('sal_wm_mkp_order_dt_z')->insertGetID(
              [
                'sku'=>$sku  ,'gtin'=>$gtin,'item_name'=>$item_name ,'product_tax_code'=>$product_tax_code ,
                'transaction_type'=>$transaction_type ,'transaction_date_time'=> $transaction_date_time,
                'shipped_qty'=> $shipped_qty,'shipping_tax_code'=>$shipping_tax_code,
                'gift_wrap_tax_code'=>$gift_wrap_tax_code, 'total_tender'=>$total_tender ,
                'total_tender'=>$payable_to_partner_from_sale ,'commission_from_sale'=>$commission_from_sale,
                'commission_rate'=>$commission_rate , 'gross_sales_revenue'=>$gross_sales_revenue ,
                'refunded_retail_sales'=>$refunded_retail_sales , 'sales_refund_for_escalation'=>$sales_refund_for_escalation ,
                'gross_shipping_revenue'=> $gross_shipping_revenue ,'gross_shipping_refunded'=>$gross_shipping_refunded ,
                'shipping_refund_for_escalation'=>$shipping_refund_for_escalation,'net_shipping_revenue'=>$net_shipping_revenue,      
                'gross_fee_revenue'=>$gross_fee_revenue ,'gross_fee_refunded'=>$gross_fee_refunded, 
                'fee_refund_for_escalation'=>$fee_refund_for_escalation , 'net_fee_revenue'=>$net_fee_revenue ,
                'gift_wrap_quantity'=>$gift_wrap_quantity , 'gross_gift_wrap_revenue'=>$gross_gift_wrap_revenue ,
                'gross_gift_wrap_refunded'=>$gross_gift_wrap_refunded , 'gift_wrap_refund_for_escalation'=>$gift_wrap_refund_for_escalation ,
                'net_gift_wrap_revenue'=>$net_gift_wrap_revenue, 'tax_on_sales_revenue'=> $tax_on_sales_revenue,
                'tax_on_shipping_revenue'=> $tax_on_shipping_revenue ,'tax_on_gift_wrap_revenue'=> $tax_on_gift_wrap_revenue ,
                'tax_on_fee_revenue'=> $tax_on_fee_revenue, 'effective_tax_rate'=>$effective_tax_rate ,
                'tax_on_refunded_sales'=>$tax_on_refunded_sales ,'tax_on_shipping_refund'=>$tax_on_shipping_refund ,
                'tax_on_gift_wrap_refund'=>$tax_on_gift_wrap_refund, 'tax_on_fee_refund'=> $tax_on_fee_refund  ,
                'tax_on_sales_refund_for_escalation'=>$tax_on_sales_refund_for_escalation  , 
                'tax_on_shipping_refund_for_escalation'=>$tax_on_shipping_refund_for_escalation ,
                'tax_on_gift_wrap_refund_for_escalation'=> $tax_on_gift_wrap_refund_for_escalation ,
                'tax_on_fee_refund_for_escalation'=>$tax_on_fee_refund_for_escalation ,
                'total_net_tax_collected'=>$total_net_tax_collected ,
                'tax_withheld'=>$tax_withheld ,'adjustment_description'=>$adjustment_description,
                'adjustment'=> $adjustment , 'code_original'=> $code_original ,'item_price'=>$item_price ,
                'original_commission_amount'=>$original_commission_amount ,'spec_category'=>$spec_category ,
                'contract_category'=> $contract_category  , 'product_type'=>$product_type ,
                'flex_commission_rule'=>$flex_commission_rule , 'return_reason_code'=>$return_reason_code,
                'return_reason_description'=>$return_reason_description , 'fee_withheld_flag' => $fee_withheld_flag ,
                'fulfillment_type'=>$fulfillment_type ,'order_id'=>$NewID
               ]);
          }
          
        }
      }// For

        // Wayfair
        $RowBegin = 3;
        $reader->setLoadSheetsOnly(["Wayfair", "Wayfair"]);
        $spreadsheet = $reader->load($file);
        $RowEnd = $spreadsheet->getActiveSheet()->getHighestRow();
        print_r ('Wayfair '.$RowEnd );
        print_r ( '<br>');

        for($i=$RowBegin; $i <= $RowEnd; $i++)
        {
          $po_number =  $spreadsheet->getActiveSheet()->getCellByColumnAndRow(3,$i)->getValue();
          $warehouse_name  =  $spreadsheet->getActiveSheet()->getCellByColumnAndRow(1,$i)->getValue();
          $store_name  =  $spreadsheet->getActiveSheet()->getCellByColumnAndRow(2,$i)->getValue();

          $po_date	  =  $spreadsheet->getActiveSheet()->getCellByColumnAndRow(4,$i)->getValue();
          $po_date = date("Y-m-d", strtotime( $po_date));  
          $must_ship_by	 =  $spreadsheet->getActiveSheet()->getCellByColumnAndRow(5,$i)->getValue();
          $must_ship_by = date("Y-m-d", strtotime( $must_ship_by));  
          $back_order_date =  $spreadsheet->getActiveSheet()->getCellByColumnAndRow(6,$i)->getValue();
          if( $back_order_date <>'' ) { $back_order_date = date("Y-m-d", strtotime( $back_order_date));  }

          $order_status =  $spreadsheet->getActiveSheet()->getCellByColumnAndRow(7,$i)->getValue();

          $ship_method	 =  $spreadsheet->getActiveSheet()->getCellByColumnAndRow(12,$i)->getValue();
          $carrier_name	 =  $spreadsheet->getActiveSheet()->getCellByColumnAndRow(13,$i)->getValue();
          $shipping_account_number	 =  $spreadsheet->getActiveSheet()->getCellByColumnAndRow(14,$i)->getValue();
          $ship_to_name	 =  $spreadsheet->getActiveSheet()->getCellByColumnAndRow(15,$i)->getValue();
          $ship_to_address	 =  $spreadsheet->getActiveSheet()->getCellByColumnAndRow(16,$i)->getValue();
          $ship_to_address2 =  $spreadsheet->getActiveSheet()->getCellByColumnAndRow(17,$i)->getValue();
          $ship_to_city	 =  $spreadsheet->getActiveSheet()->getCellByColumnAndRow(18,$i)->getValue();
          $ship_to_state	 =  $spreadsheet->getActiveSheet()->getCellByColumnAndRow(19,$i)->getValue();
          $ship_to_zip	 =  $spreadsheet->getActiveSheet()->getCellByColumnAndRow(20,$i)->getValue();
          $ship_to_phone =  $spreadsheet->getActiveSheet()->getCellByColumnAndRow(21,$i)->getValue();

          $ship_speed		 =  $spreadsheet->getActiveSheet()->getCellByColumnAndRow(24,$i)->getValue();
          $po_date_time =  $spreadsheet->getActiveSheet()->getCellByColumnAndRow(25,$i)->getValue();
          $po_date_time = date("Y-m-d", strtotime( $po_date_time));  
          $registered_timestamp	 =  $spreadsheet->getActiveSheet()->getCellByColumnAndRow(26,$i)->getValue();
          $registered_timestamp = date("Y-m-d", strtotime( $registered_timestamp));  
          $customization_text	 =  $spreadsheet->getActiveSheet()->getCellByColumnAndRow(27,$i)->getValue();

        
          $item_number =  $spreadsheet->getActiveSheet()->getCellByColumnAndRow(8,$i)->getValue();
          $item_name	 =  $spreadsheet->getActiveSheet()->getCellByColumnAndRow(9,$i)->getValue();
          $quantity	 =  $spreadsheet->getActiveSheet()->getCellByColumnAndRow(10,$i)->getValue();
          $wholesale_price	 =  $spreadsheet->getActiveSheet()->getCellByColumnAndRow(11,$i)->getValue();

          $inventory_at_po_time =  $spreadsheet->getActiveSheet()->getCellByColumnAndRow(22,$i)->getValue();
          $inventory_send_date =  $spreadsheet->getActiveSheet()->getCellByColumnAndRow(23,$i)->getValue();

          $event_name	 =  $spreadsheet->getActiveSheet()->getCellByColumnAndRow(28,$i)->getValue();
          $event_id	 =  $spreadsheet->getActiveSheet()->getCellByColumnAndRow(29,$i)->getValue();
          $event_start_date =  $spreadsheet->getActiveSheet()->getCellByColumnAndRow(30,$i)->getValue();
          $event_end_date	 =  $spreadsheet->getActiveSheet()->getCellByColumnAndRow(31,$i)->getValue();
          $event_type  =  $spreadsheet->getActiveSheet()->getCellByColumnAndRow(32,$i)->getValue();
          $backorder_reason =  $spreadsheet->getActiveSheet()->getCellByColumnAndRow(33,$i)->getValue();
         
          $original_product_id  =  $spreadsheet->getActiveSheet()->getCellByColumnAndRow(34,$i)->getValue();
          $original_product_name  =  $spreadsheet->getActiveSheet()->getCellByColumnAndRow(35,$i)->getValue();
          $event_inventory_source  =  $spreadsheet->getActiveSheet()->getCellByColumnAndRow(36,$i)->getValue();
          $packing_slip_url  =  $spreadsheet->getActiveSheet()->getCellByColumnAndRow(37,$i)->getValue();
          $tracking_number	 =  $spreadsheet->getActiveSheet()->getCellByColumnAndRow(38,$i)->getValue();
          $ready_for_pickup_date	  =  $spreadsheet->getActiveSheet()->getCellByColumnAndRow(39,$i)->getValue();
          $wm_sku =  $spreadsheet->getActiveSheet()->getCellByColumnAndRow(40,$i)->getValue();
          $destination_country =  $spreadsheet->getActiveSheet()->getCellByColumnAndRow(41,$i)->getValue();

          $depot_id	 =  $spreadsheet->getActiveSheet()->getCellByColumnAndRow(42,$i)->getValue();
          $depot_name =  $spreadsheet->getActiveSheet()->getCellByColumnAndRow(43,$i)->getValue();
          $wholesale_event_source	 =  $spreadsheet->getActiveSheet()->getCellByColumnAndRow(44,$i)->getValue();
          $wholesale_event_store_source =  $spreadsheet->getActiveSheet()->getCellByColumnAndRow(45,$i)->getValue();
          $b2b_order =  $spreadsheet->getActiveSheet()->getCellByColumnAndRow(46,$i)->getValue();
          $composite_wood_product =  $spreadsheet->getActiveSheet()->getCellByColumnAndRow(47,$i)->getValue();
          $create_at =   $EndDate = date('Y-m-d H:i:s');

           // inssert 
           DB::connection('mysql')->table('sal_wf_orders_z')->insert(
            ['warehouse_name'=>$warehouse_name , 'store_name'=>$store_name ,    
            'po_number'=>$po_number,'po_date'=>$po_date	, 'must_ship_by'=>$must_ship_by	,
            'back_order_date'=> $back_order_date, 'ship_method'=>$ship_method	,'carrier_name'=> $carrier_name	,
            'shipping_account_number'=> $shipping_account_number	, 'ship_to_name'=>$ship_to_name	,
            'ship_to_address'=>$ship_to_address	, 'ship_to_address2'=> $ship_to_address2,
            'ship_to_city'=>$ship_to_city	, 'ship_to_state'=>$ship_to_state	,
            'ship_to_zip'=> $ship_to_zip	, 'ship_to_phone'=>  $ship_to_phone,'ship_speed'=>$ship_speed,
            'registered_timestamp'=>$registered_timestamp	, 'customization_text'=>  $customization_text	,
          	'destination_country'=>$destination_country, 'depot_id'=>$depot_id	,'depot_name'=> $depot_name,
            'wholesale_event_source'=>$wholesale_event_source	, 'wholesale_event_store_source'=> $wholesale_event_store_source,
            'b2b_order'=>$b2b_order, 'order_status'=>$order_status, 'item_number'=>$item_number, 'item_name'=>$item_name	,
            'quantity'=>$quantity	, 'wholesale_price'=>$wholesale_price	,'inventory_at_po_time'=>$inventory_at_po_time,
            'inventory_send_date'=> $inventory_send_date, 'wm_sku'=>$wm_sku, 'event_name'=> $event_name	,
            'event_id'=>$event_id	, 'event_start_date'=>$event_start_date,'event_end_date'=>$event_end_date, 'event_type'=>$event_type,
            'backorder_reason'=> $backorder_reason, 'tracking_number'=>$tracking_number	, 'original_product_id'=>$original_product_id,
            'original_product_name'=>$original_product_name,'event_inventory_source'=>$event_inventory_source,
            'packing_slip_url'=>$packing_slip_url, 'ready_for_pickup_date'=> $ready_for_pickup_date	, 
            'composite_wood_product'=> $composite_wood_product, 'create_at'=>$create_at
            ]);
        }
*/
        // EBAY
        $RowBegin = 13;
        $reader->setLoadSheetsOnly(["ebay infildeal", "ebay infildeal"]);
        $store_id = 3;
        $spreadsheet = $reader->load($file);
        $RowEnd = $spreadsheet->getActiveSheet()->getHighestRow();
        print_r ('ebay infildeal '.$RowEnd );
        print_r ( '<br>');

        for($i=$RowBegin; $i <= $RowEnd; $i++)
        {
          $transaction_date =  $spreadsheet->getActiveSheet()->getCellByColumnAndRow(1,$i)->getFormattedValue();
         // print_r('Tracsation date: '. $transaction_date );
         // print_r('<br>' );
          $transaction_date = date("Y-m-d", strtotime( $transaction_date));  
         // print_r('Tracsation date: '. $transaction_date );
         // print_r('<br>' );
          
          $type	=  $spreadsheet->getActiveSheet()->getCellByColumnAndRow(2,$i)->getValue();
          $order_number	=  $spreadsheet->getActiveSheet()->getCellByColumnAndRow(3,$i)->getValue();
          $legacy_order_id=  $spreadsheet->getActiveSheet()->getCellByColumnAndRow(4,$i)->getValue();
          $buyer_username	=  $spreadsheet->getActiveSheet()->getCellByColumnAndRow(5,$i)->getValue();
          $buyer_name	=  $spreadsheet->getActiveSheet()->getCellByColumnAndRow(6,$i)->getValue();
          $ship_to_city	=  $spreadsheet->getActiveSheet()->getCellByColumnAndRow(7,$i)->getValue();
          $ship_to_state=  $spreadsheet->getActiveSheet()->getCellByColumnAndRow(8,$i)->getValue();
          $ship_to_zip	=  $spreadsheet->getActiveSheet()->getCellByColumnAndRow(9,$i)->getValue();
          $ship_to_country=  $spreadsheet->getActiveSheet()->getCellByColumnAndRow(10,$i)->getValue();
          $net_amount=  $spreadsheet->getActiveSheet()->getCellByColumnAndRow(11,$i)->getValue();
          $payout_currency=  $spreadsheet->getActiveSheet()->getCellByColumnAndRow(12,$i)->getValue();
          $payout_date =  $spreadsheet->getActiveSheet()->getCellByColumnAndRow(13,$i)->getFormattedValue();
          $payout_date = date("Y-m-d", strtotime( $payout_date));  

          $payout_id =  $spreadsheet->getActiveSheet()->getCellByColumnAndRow(14,$i)->getValue();
          $payout_method	=  $spreadsheet->getActiveSheet()->getCellByColumnAndRow(15,$i)->getValue();
          $status =  $spreadsheet->getActiveSheet()->getCellByColumnAndRow(16,$i)->getValue();
          $reason_for_hold =  $spreadsheet->getActiveSheet()->getCellByColumnAndRow(17,$i)->getValue();
          $item_id	 =  $spreadsheet->getActiveSheet()->getCellByColumnAndRow(18,$i)->getValue();
          $transaction_id	=  $spreadsheet->getActiveSheet()->getCellByColumnAndRow(19,$i)->getValue();
          $item_title	=  $spreadsheet->getActiveSheet()->getCellByColumnAndRow(20,$i)->getValue();
          $item_title= str_replace("'", "",  $item_title);
          $custom_label=  $spreadsheet->getActiveSheet()->getCellByColumnAndRow(21,$i)->getValue(); 
          $sku = $this->left($custom_label,4);
          $quantity	=  $spreadsheet->getActiveSheet()->getCellByColumnAndRow(22,$i)->getValue(); 
          $item_subtotal	=  $spreadsheet->getActiveSheet()->getCellByColumnAndRow(23,$i)->getValue(); 
          $shipping_handling	=  $spreadsheet->getActiveSheet()->getCellByColumnAndRow(24,$i)->getValue(); 
          $seller_collected_tax=  $spreadsheet->getActiveSheet()->getCellByColumnAndRow(25,$i)->getValue(); 
          $ebay_collected_tax	=  $spreadsheet->getActiveSheet()->getCellByColumnAndRow(26,$i)->getValue(); 
          $final_value_fee_fixed	=  $spreadsheet->getActiveSheet()->getCellByColumnAndRow(27,$i)->getValue(); 
          $final_value_fee_variable=  $spreadsheet->getActiveSheet()->getCellByColumnAndRow(28,$i)->getValue(); 
          $very_high_item =  $spreadsheet->getActiveSheet()->getCellByColumnAndRow(29,$i)->getValue(); 
          $below_standard_performance =  $spreadsheet->getActiveSheet()->getCellByColumnAndRow(30,$i)->getValue(); 
          $international_fee	=  $spreadsheet->getActiveSheet()->getCellByColumnAndRow(31,$i)->getValue(); 
          $gross_transaction_amount=  $spreadsheet->getActiveSheet()->getCellByColumnAndRow(32,$i)->getValue(); 
          $transaction_currency=  $spreadsheet->getActiveSheet()->getCellByColumnAndRow(33,$i)->getValue(); 
          $exchange_rate=  $spreadsheet->getActiveSheet()->getCellByColumnAndRow(34,$i)->getValue(); 
          $reference_id=  $spreadsheet->getActiveSheet()->getCellByColumnAndRow(35,$i)->getValue(); 
          $description=  $spreadsheet->getActiveSheet()->getCellByColumnAndRow(36,$i)->getValue(); 

          DB::connection('mysql')->table('sal_ebay_orders_z')->insert(
            [
              'transaction_date'=>$transaction_date, 'type'=>$type,
              'order_number'=>$order_number, 'legacy_order_id'=>$legacy_order_id, 'buyer_username'=>$buyer_username,
              'buyer_name'=>$buyer_name, 'ship_to_city'=>$ship_to_city, 'ship_to_state'=>$ship_to_state,
              'ship_to_state'=>$ship_to_state, 'ship_to_zip'=>$ship_to_zip, 'ship_to_country'=>$ship_to_country,
              'net_amount'=>$net_amount,'payout_currency'=>$payout_currency, 'payout_date'=>$payout_date,
	            'payout_id'=>$payout_id, 'payout_method'=>$payout_method, 'payout_method'=>$payout_method,
              'status'=>$status, 'reason_for_hold'=>$reason_for_hold, 'item_id'=>$item_id, 'transaction_id'=>$transaction_id,
              'item_title'=>$item_title, 'custom_label'=>$custom_label, 'sku'=>$sku, 'quantity'=>$quantity,
              'item_subtotal'=>$item_subtotal, 'shipping_handling'=>$shipping_handling, 
              'seller_collected_tax'=>$seller_collected_tax , 'ebay_collected_tax'=>$ebay_collected_tax,
              'final_value_fee_fixed'=>$final_value_fee_fixed, 'final_value_fee_variable'=>$final_value_fee_variable,
              'very_high_item'=>$very_high_item, 'very_high_item'=>$very_high_item,
              'below_standard_performance'=>$below_standard_performance, 'international_fee'=>$international_fee,
              'gross_transaction_amount'=>$gross_transaction_amount, 'transaction_currency'=>$transaction_currency,
              'exchange_rate'=>$exchange_rate, 'reference_id'=>$reference_id, 'description'=> $description,'store_id'=> $store_id
            ]);
            
        }


      }//  if($validator->passes())
    }
}

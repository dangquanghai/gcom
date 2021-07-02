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
       }//For

       
       $RowBegin = 3;
       $reader->setLoadSheetsOnly(["order", "order"]);
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
         $order_date = date('Y-m-d', time());
         $store_name = $spreadsheet->getActiveSheet()->getCellByColumnAndRow(1,$i)->getValue();
         $store_order_id	= $spreadsheet->getActiveSheet()->getCellByColumnAndRow(3,$i)->getValue();
         $order_status	= $spreadsheet->getActiveSheet()->getCellByColumnAndRow(4,$i)->getValue();
         $order_date	= $spreadsheet->getActiveSheet()->getCellByColumnAndRow(5,$i)->getFormattedValue();
         //$order_date	= $spreadsheet->getActiveSheet()->getCellByColumnAndRow(5,$i)->getValue();

         $order_date = date("Y-m-d h:i:s", strtotime( $order_date));  
        // print_r('The order date is: ' . $order_date	);
        // print_r('<br>'	);

       // $sql = " insert into test_ne(the_date) values('$order_date')";
       // print_r('sql is ' .$sql 	);
       // print_r('<br>'	);
        DB::connection('mysql')->select ($sql);


         $shipping_address	= $spreadsheet->getActiveSheet()->getCellByColumnAndRow(6,$i)->getValue();
         $shipto_state	= $spreadsheet->getActiveSheet()->getCellByColumnAndRow(7,$i)->getValue();
         $shipto_zipcode	= $spreadsheet->getActiveSheet()->getCellByColumnAndRow(8,$i)->getValue();
         $product_sku =  $spreadsheet->getActiveSheet()->getCellByColumnAndRow(9,$i)->getValue();
         $quantity= $spreadsheet->getActiveSheet()->getCellByColumnAndRow(12,$i)->getValue();
         if($quantity=='') $quantity = 0;
         $revenue= $spreadsheet->getActiveSheet()->getCellByColumnAndRow(13,$i)->getValue();
         if($revenue=='') $revenue = 0;

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

         $sql = "select id from prd_product where product_sku = '$product_sku' ";
         $ds= DB::connection('mysql')->select($sql);
         foreach($ds as $d)  { $product_id = $d->id;}
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
             $sql = " select id as MyCount from sal_amz_seller_order_dt where amz_order_id  = $id and product_id = $product_id ";
             if(!$this->IsExist('mysql',$sql)){// chua ton tai detail product nay
               // insert to detail
               DB::connection('mysql')->table('sal_amz_seller_order_dt')->insert(
               ['amz_order_id'=>$id,'product_id'=>$product_id,'quantity'=>$quantity,
               'price'=>$price,'amount'=>$revenue]);
             }
           }else{// da ton tai master
              // Kiem tra trong detail cua phieu nau da co hang nay chua
              $sql = " select id as MyCount from sal_amz_seller_order_dt where amz_order_id  = $id and product_id = $product_id ";
              if(!$this->IsExist('mysql',$sql)){// chua ton tai detail product nay
                // insert to detail
                DB::connection('mysql')->table('sal_amz_seller_order_dt')->insertGetId(
                ['amz_order_id'=>$id,'product_id'=>$product_id,'quantity'=>$quantity,
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
             $sql = " select id as MyCount from sal_amz_vendor_order_dt where amz_order_id  = $id and product_id = $product_id ";
             if(!$this->IsExist('mysql',$sql)){// chua ton tai detail product nay
               // insert to detail
                DB::connection('mysql')->table('sal_amz_vendor_order_dt')->insert(
               ['amz_order_id'=>$id ,'product_id'=>$product_id,'quantity'=>$quantity,
               'price'=>$price,'amount'=>$revenue]);
             }

           }else{// da ton tai master
              // Kiem tra trong detail cua phieu nau da co hang nay chua
              $sql = " select id as MyCount from sal_amz_vendor_order_dt where amz_order_id  = $id and product_id = $product_id ";
              if(!$this->IsExist('mysql',$sql)){// chua ton tai detail product nay
                // insert to detail
                DB::connection('mysql')->table('sal_amz_vendor_order_dt')->insert(
                ['amz_order_id'=>$id,'product_id'=>$product_id,'quantity'=>$quantity,
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
              $sql = " select id as MyCount from sal_order_dt where order_id   = $id and product_id = $product_id  ";
              if(!$this->IsExist('mysql',$sql)){// chua ton tai detail product nay
                // insert to detail
                DB::connection('mysql')->table('sal_order_dt')->insert(
                ['order_id'=>$id,'product_id'=>$product_id,'quantity'=>$quantity,
                'price'=>$price,'amount'=>$revenue]);
              }

            }else{// da ton tai master
               // Kiem tra trong detail cua phieu nau da co hang nay chua
               $sql = " select id as MyCount from sal_order_dt where order_id   = $id and product_id = $product_id ";
               if(!$this->IsExist('mysql',$sql)){// chua ton tai detail product nay
                 // insert to detail
                 DB::connection('mysql')->table('sal_order_dt')->insert(
                 ['order_id'=>$id,'product_id'=>$product_id,'quantity'=>$quantity,
                 'price'=>$price,'amount'=>$revenue]);
               }

            }

         }//end if Phân bổ vào các bảng dựa vào sales channel

       }// for
 
       $RowBegin = 4;
       $reader->setLoadSheetsOnly(["SumSalesOnAVC", "SumSalesOnAVC"]);
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
         $TheMonth = $spreadsheet->getActiveSheet()->getCellByColumnAndRow(22,$i)->getValue();
         $TheYear = $spreadsheet->getActiveSheet()->getCellByColumnAndRow(23,$i)->getValue();
         $MarketPlace =1;// Amazon
         $product_id = $this->GetProductIDFromAsin($Asin, $MarketPlace);
         $id =0;
         $sql = " select id  from sal_sum_vendor_order where product_id  = $product_id 
         and the_month  = $TheMonth and the_year =  $TheYear  ";

         $ds= DB::connection('mysql')->select($sql);
         foreach($ds as $d)  {  $id = $this->iif(is_null($d->id),0,$d->id); }
         if( $id ==0 && $product_id != 0)
         {
           DB::connection('mysql')->table('sal_sum_vendor_order')->insert(
           ['product_id'=>$product_id ,'quantity'=>$TotalOrder,'the_month'=>$TheMonth,'the_year'=>$TheYear]);
         }elseif($product_id == 0)
         {
           print_r('Asin'.$Asin );
           print_r('<br>' );
         }

       }
*/

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
  $ItemCost	= $spreadsheet->getActiveSheet()->getCellByColumnAndRow(18,$i)->getValue();
  $SKU	= $spreadsheet->getActiveSheet()->getCellByColumnAndRow(19,$i)->getValue();
  $SKU = $this->left($SKU,4);
  $asin	= $spreadsheet->getActiveSheet()->getCellByColumnAndRow(20,$i)->getValue();
  $des	= $spreadsheet->getActiveSheet()->getCellByColumnAndRow(21,$i)->getValue();
  $quantity	= $spreadsheet->getActiveSheet()->getCellByColumnAndRow(22,$i)->getValue();
  $gift_message = $spreadsheet->getActiveSheet()->getCellByColumnAndRow(23,$i)->getValue();

  $tracking_id= $spreadsheet->getActiveSheet()->getCellByColumnAndRow(26,$i)->getValue();
  $shipped_date= $spreadsheet->getActiveSheet()->getCellByColumnAndRow(27,$i)->getValue();
  $shipped_date = date("Y-m-d h:i:s", strtotime($shipped_date));  

   // Kiểm tra xem đã có order đó trong database chưa
   $id =0;
   $sql = " select id  from sal_amz_vendor_orders where order_no = '$store_order_id' 
   and channel_id  = $channel_id  ";
   $ds= DB::connection('mysql')->select($sql);

   foreach($ds as $d)  {  $id = $this->iif(is_null($d->id),0,$d->id); }

   if( $id == 0 ){// chua ton tai order nay trong database  
    
    // insert to master
     $id = DB::connection('mysql')->table('sal_amz_vendor_orders_z')->insertGetId(
     [ 
     `order_id`=>$order_id  , `warehouse_code`=> $WarehouseCode , `order_date`=> $order_date	 ,
     `required_ship_date`=>$RequiredShipDate , `ship_method`=>$ShipMethod ,`ship_method_code`=>$ShipMethod ,
     `ship_to_name`=>$ShipToName,`address_1`=>$address1,`address_2`=>$address2 ,`address_3`=>$address3 , 
     `ship_to_city`=> $ship_to_city, `ship_to_state`=>  $shipto_state	, `ship_to_zip_code`=> $shipto_zipcode , 
     `ship_to_country`=>$shipto_country,`phone_number` => $PhoneNumber ]);

      // insert to detail $status	
      DB::connection('mysql')->table('sal_amz_vendor_order_dt')->insert(
      ['amz_order_id'=>$id ,'product_id'=>$product_id,'quantity'=>$ItemQuantity,
      'price'=>$ItemCost,'amount'=>$ItemCost * $ItemQuantity,'tracking_id'=> $TrackingID ]);
    

   }else{// da ton tai master
      // Kiem tra trong detail cua phieu nau da co hang nay chua
      $sql = " select id as MyCount from sal_amz_vendor_order_dt where amz_order_id  = $id and product_id = $product_id ";
      if(!$this->IsExist('mysql',$sql)){// chua ton tai detail product nay
        // insert to detail
        DB::connection('mysql')->table('sal_amz_vendor_order_dt')->insert(
        ['amz_order_id'=>$id,'product_id'=>$product_id,'quantity'=>$ItemQuantity,
        'price'=>$ItemCost,'amount'=>$ItemCost *$ItemQuantity,'tracking_id'=> $TrackingID ]);
      }

   }

}// for

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

      }//  if($validator->passes())
    }
}

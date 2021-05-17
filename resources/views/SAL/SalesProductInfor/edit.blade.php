@extends('layouts.admin')
@section('content')
@if($dsProduct)
<form role="form" method="post" action="{{route('SalesProductInforController.update',$id) }}">
    {{ csrf_field() }}
    <input type="hidden" name ="_method" value ="PUT">
        <!-- left column -->
    <div class="row">
        <div class="col-md-6" style="padding-right:0px">
        <!-- @if($dsProduct) -->
            <!-- general form elements -->
            <div class="card card-primary">
                <div class="card-header">
                    <h3 class="card-title"> Sales Product Informations </h3>
                </div>
                <!-- /.card-header -->
                <!-- form start -->
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="sku">SKU</label>
                                <input type="text" class="form-control" name="sku" id="sku" enable="false" value="{{$dsProduct->sku}}" >
                            </div>
                        </div>
                        <div class="col-md-9">
                            <div class="form-group">
                                <label for="title">Product Name</label>
                                <input type="text" class="form-control" name="title"id="title" value="{{$dsProduct->title}} " >
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-sm-3">
                            <div class="form-group">
                                <label for="height">Height</label>
                                <input type="text" class="form-control" name="height" id="height" value="{{$dsProduct->the_height}} " >
                            </div>
                        </div>
                        <div class="col-sm-3">
                            <div class="form-group">
                                <label for="width">Width</label>
                                <input type="text" class="form-control" name="width" id="width" value="{{$dsProduct->the_width}} "  >
                            </div>
                        </div>
                        
                        <div class="col-sm-3">
                            <div class="form-group">
                                <label for="length">Length</label>
                                <input type="text" class="form-control" name="length" id="length" value="{{$dsProduct->the_length}} " >
                            </div>
                        </div>

                        <div class="col-sm-3">
                            <div class="form-group">
                                <label for="length">Weight</label>
                                <input type="text" class="form-control" name="weight" id="the_weight" value="{{$dsProduct->the_weight}} " >
                            </div>
                        </div>
                    </div>
                    <hr>
                    <div class ="row">
                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="description">%deposit</label>
                                <input type="text" class="form-control" name="per_deposit" id="per_deposit" value ="{{$dsProduct->per_deposit}}">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="description">% full payment</label>
                                <input type="text" class="form-control" name="per_full_payment" id="per_full_payment" value ="{{$dsProduct->per_full_payment}}">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="description">% partner split </label>
                                <input type="text" class="form-control" name="per_rev_split_for_partner" id="per_rev_split_for_partner" value ="{{$dsProduct->per_rev_split_for_partner}}">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="description">con20_capacity </label>
                                <input type="text" class="form-control" name="con20_capacity" id="con20_capacity" value ="{{$dsProduct->con20_capacity}}">
                            </div>
                        </div>
                    </div>
                    <div class ="row">
                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="exw_vn">exw_vn</label>
                                <input type="text" class="form-control" name="exw_vn" id="exw_vn" value ="{{$dsProduct->exw_vn}}">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="description">fob_vn</label>
                                <input type="text" class="form-control" name="fob_vn" id="fob_vn" value ="{{$dsProduct->fob_vn}}">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="description">fob_cn</label>
                                <input type="text" class="form-control" name="fob_cn" id="fob_cn" value ="{{$dsProduct->fob_cn}}">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="description">fob_us</label>
                                <input type="text" class="form-control" name="fob_us" id="fob_us" value ="{{$dsProduct->fob_us}}">
                            </div>
                        </div>
                    </div>
                    <div class ="row">
                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="description">cosg_est</label>
                                <input type="text" class="form-control" name="cosg_est" id="cosg_est" value ="{{$dsProduct->cosg_est}}">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="description">per_mkt</label>
                                <input type="text" class="form-control" name="per_mkt" id="per_mkt" value ="{{$dsProduct->per_mkt}}">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="description">per_promotion</label>
                                <input type="text" class="form-control" name="per_promotion" id="per_promotion" value ="{{$dsProduct->per_promotion}}">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="description">per_duty</label>
                                <input type="text" class="form-control" name="per_duty" id="per_duty" value ="{{$dsProduct->per_duty}}">
                            </div>
                        </div>
                    </div>

                    <div class ="row">
                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="description">per_wh_fee</label>
                                <input type="text" class="form-control" name="per_wh_fee" id="per_wh_fee" value ="{{$dsProduct->per_wh_fee}}">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="description">per_handing_fee</label>
                                <input type="text" class="form-control" name="per_handing_fee" id="per_handing_fee" value ="{{$dsProduct->per_handing_fee}}">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="description">shiping_fee_est</label>
                                <input type="text" class="form-control" name="shiping_fee_est" id="shiping_fee_est" value ="{{$dsProduct->shiping_fee_est}}">
                            </div>
                        </div>
                        <div class="col-sm-3">
                            <div class="form-group">
                                <label for="cubic">cubic</label>
                                <input type="text" class="form-control" name="cubic" id="cubic" value="{{round($dsProduct->the_width *$dsProduct->the_height *$dsProduct->the_length,2 )}} " disabled> 
                            </div>
                        </div>
                    </div>
                </div>
                <!-- /.card-body -->
                <div class="card-footer">
                    <button type="submit" class="btn btn-primary">
                        <i class="fa fa-save"></i>
                    </button>
                </div>
            </div>
            <!-- /.card -->
           <!--  @endif  -->
        </div> <!-- end card bên trái  -->

        <div class="col-md-6"  style="padding-left:0px" >
             <div class="card card-primary">
                 <div class="card-header">
                     <h3 class="card-title"> Cost Price On Sales Channel</h3>
                 </div>
                 <div class="card-body">
                    <div id="message"></div>
                    <table  id="cost_price_list" class="table table-bordered table-hover" >
                        <thead>
                          <tr>
                            <th  style="display:none;">ID</th>
                            <th style="display:none;" >Channel ID</th>
                            <th >Channel Name</th>
                            <th>Retail Price</th>
                            <th>%Cost</th>
                            <th>Cost</th>
                            <th style="display:none;">some_fee</th>
                            <th style="display:none;">per_other_fee</th>
                            <th>est_profit</th>
                            <th>Action</th>
                          </tr>
                        </thead>
                        <tbody>
                         
                        </tbody>
                    </table>
                    {{csrf_field()}}
                 </div>
                 <div class="card-footer">
                 </div>
             </div>
         </div> 
    </div>
</form>
@endif
@endsection
@section('scripts')
<script type= "text/javascript">

$(document).ready(function(){
var _token = $('input[name="_token"]').val();
 

function LoadCostAndPriceOnAllChannel()
{
    $.ajax({
        url:'{{route("SalesProductInforController.LoadCostAndPriceOnAllChannel",$dsProduct->sku)}}',
        //dataType:"json",
        success:function(data)
        {
            $('tbody').html(data);
        }// end fucntion success
    });
}
LoadCostAndPriceOnAllChannel();

/*
 $(document).on('change','select',function(){
        var column_name = "channel_id";
        var column_value =   $(this).val();
        var id = 0;
        id = this.parentNode.id;
        if(column_value != '')
        {
            $.ajax({
                url:'{{route("SalesProductInforController.UpdateCostPrice")}}',//Chỉ update channel ID
                method: "POST",
                data: {column_name:column_name,column_value:column_value,id:id, _token:_token},
                success:function(data)
                {
                   $('#message').html(data);
                }
            });
        }else
        {
            $('#message').html("<div class='alert alert-danger'>Lỗi từ update channel</div>");
        }
    });
  
   */

$(document).on('blur','.column_name',function()
    {
        var retail_price = 0;
        var per_cost = 0;
        var cost = 0;
        var id = $(this).attr("id");
        var column_name = $(this).data('column_name');
        var rest_obj;
      
        if(column_name == "retail_price")
        {
            retail_price = $(this).text();
            rest_obj =  this.nextElementSibling;
            per_cost = $(rest_obj).text();
            cost = $(rest_obj.nextElementSibling).text();

        } else if (column_name == "per_cost")
        {
            per_cost = $(this).text();
            rest_obj = this.previousElementSibling;
            retail_price = $(rest_obj).text();
            rest_obj = this.nextElementSibling;
            cost = $(rest_obj).text();
        }

        if(column_name == "retail_price" || column_name == "per_cost")
        {
            $.ajax({
                url:'{{route("SalesProductInforController.UpdateCostPriceNew")}}',
                method: "POST",
                data: {retail_price:retail_price,per_cost:per_cost,cost:cost,id:id, _token:_token},
                success:function(data)
                {
                   $('#message').html(data);
                }
            });
        }else
        {
            $('#message').html("<div class='alert alert-danger'> Báo lỗi từ blur </div>");
        }
   
    });


    $(document).on('input','#cost_price_list > tbody > tr > td',function()
    {
    
        var data_column_name = $(this).attr("data-column_name");
        var ChannelIDObj ;
        
        var RetailPriceObj ;
        var PerCostObj ;
        var CostObj ;
        var SomeFeeObj ;
        var PerOtherFeeObj ;
        var EstProfitObj ;
        
        var ChannelID ;
        var RetailPrice = 0;
        var PerCost = 0;
        var Cost = 0;
        
        var SomeFee = 0;
        var PerOtherFee = 0;
        var AllExpensive = 0;
        var EstProfit = 0;

        if(data_column_name =='per_cost')
        {

            PerCostObj=$(this);
            PerCost = $(this).text();

            RetailPriceObj = this.previousElementSibling;
            RetailPrice =$(RetailPriceObj).text();

            ChannelIDObj = RetailPriceObj.previousElementSibling;
            ChannelID = $(ChannelIDObj).text();

            CostObj = this.nextElementSibling;
            Cost = Math.round(RetailPrice * PerCost /100,2);
            $(CostObj).text(Cost);  
            
            SomeFeeObj = CostObj.nextElementSibling;
            SomeFee = $(SomeFeeObj).text();

            PerOtherFeeObj =  SomeFeeObj.nextElementSibling;
            PerOtherFee = $(PerOtherFeeObj).text();

            EstProfitObj =  PerOtherFeeObj.nextElementSibling;
            console.log(ChannelIDObj);
            if( ChannelID <= 4 )
               {
                if(Cost > 0)
                    { EstProfit = Math.round( Cost - (SomeFee +   PerOtherFee * 100),2);}
                else
                    { EstProfit = 0;}
               }
            else
               {
                if(RetailPrice > 0)
                    { EstProfit = Math.round( RetailPrice - (SomeFee +   PerOtherFee * 100),2);}
                else
                    { EstProfit = 0;}
               }
           
            $(EstProfitObj).text(EstProfit);

           // console.log(EstProfitObj);
        }
        
        else if(data_column_name =='retail_price')  
        {
            RetailPriceObj = $(this);
            RetailPrice =$(RetailPriceObj).text();

            ChannelIDObj = this.previousElementSibling;
            ChannelID = $(ChannelIDObj).text();

            PerCostObj =this.nextElementSibling;
            PerCost = $(PerCostObj).text();
            
            CostObj = PerCostObj.nextElementSibling;
            Cost = RetailPrice * PerCost/100;
            $(CostObj).text(Cost);  

            SomeFeeObj = CostObj.nextElementSibling;
            SomeFee = $(SomeFeeObj).text();

            PerOtherFeeObj =  SomeFeeObj.nextElementSibling;
            PerOtherFee = $(PerOtherFeeObj).text();

            EstProfitObj =  PerOtherFeeObj.nextElementSibling;
            console.log(ChannelIDObj);
            if( ChannelID <= 4 )
               {
                if(Cost > 0)
                    { EstProfit = Math.round( Cost - (SomeFee +   PerOtherFee * 100),2);}
                else
                    { EstProfit = 0;}
               }
            else
               {
                if(RetailPrice > 0)
                    { EstProfit = Math.round( RetailPrice - (SomeFee +   PerOtherFee * 100),2);}
                else
                    { EstProfit = 0;}
               }
 
            $(EstProfitObj).text(EstProfit);

        }
        
    });
});
</script>
@endsection

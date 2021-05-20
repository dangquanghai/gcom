@extends('layouts.admin')
@section('content')
<form role="form" method="post" action="{{route('SalesProductInforController.store')}}">
    {{ csrf_field() }}
    <div class="row">
        <div class="col-md-6" style="padding-right:0px">
            <div class="card card-primary">
                <div class="card-header">
                    <h3 class="card-title"> Sales Product Informations </h3>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="sku">SKU</label>
                                <input type="text" class="form-control" name="sku" id="sku" enable="false" required >
                            </div>
                        </div>
                        <div class="col-md-9">
                            <div class="form-group">
                                <label for="title">Product Name</label>
                                <input type="text" class="form-control" name="title"id="title" required>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-sm-3">
                            <div class="form-group">
                                <label for="height">Height</label>
                                <input type="text" class="form-control" name="the_height" id="the_height" >
                            </div>
                        </div>
                        <div class="col-sm-3">
                            <div class="form-group">
                                <label for="width">Width</label>
                                <input type="text" class="form-control" name="the_width" id="the_width" >
                            </div>
                        </div>
                        
                        <div class="col-sm-3">
                            <div class="form-group">
                                <label for="length">Length</label>
                                <input type="text" class="form-control" name="the_length" id="the_length"  >
                            </div>
                        </div>

                        <div class="col-sm-3">
                            <div class="form-group">
                                <label for="length">Weight</label>
                                <input type="text" class="form-control" name="the_weight" id="the_weight"  >
                            </div>
                        </div>
                    </div>
                    <hr>
                    <div class ="row">
                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="per_deposit">%deposit</label>
                                <input   type="number" class="form-control" name="per_deposit" id="per_deposit" required>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="per_full_payment">% full payment</label>
                                <input type="number" class="form-control" name="per_full_payment" id="per_full_payment" >
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="per_rev_split_for_partner">% partner split </label>
                                <input type="number" class="form-control" name="per_rev_split_for_partner" id="per_rev_split_for_partner" value = "0">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="con20_capacity">con20_capacity </label>
                                <input type="number" class="form-control" name="con20_capacity" id="con20_capacity" required>
                            </div>
                        </div>
                    </div>
                    <div class ="row">
                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="exw_vn">exw_vn</label>
                                <input type="number" class="form-control" name="exw_vn" id="exw_vn" >
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="fob_vn">fob_vn</label>
                                <input type="number" class="form-control" name="fob_vn" id="fob_vn" >
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="fob_cn">fob_cn</label>
                                <input type="number" class="form-control" name="fob_cn" id="fob_cn" >
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="fob_us">fob_us</label>
                                <input type="number" class="form-control" name="fob_us" id="fob_us" >
                            </div>
                        </div>
                    </div>
                    <div class ="row">
                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="cosg_est">cosg_est</label>
                                <input type="number" class="form-control" name="cosg_est" id="cosg_est" required>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="per_mkt">per_mkt</label>
                                <input type="number" class="form-control" name="per_mkt" id="per_mkt" required>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="per_promotion">per_promotion</label>
                                <input type="number" class="form-control" name="per_promotion" id="per_promotion" required>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="per_duty">per_duty</label>
                                <input type="number" class="form-control" name="per_duty" id="per_duty" required>
                            </div>
                        </div>
                    </div>

                    <div class ="row">
                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="per_wh_fee">per_wh_fee</label>
                                <input type="number" class="form-control" name="per_wh_fee" id="per_wh_fee" value ="1">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="per_handing_fee">per_handing_fee</label>
                                <input type="number" class="form-control" name="per_handing_fee" id="per_handing_fee" value ="1" >
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="shiping_fee_est">shiping_fee_est</label>
                                <input type="number" class="form-control" name="shiping_fee_est" id="shiping_fee_est" value ="0">
                            </div>
                        </div>
                        <div class="col-sm-3">
                            <div class="form-group">
                                <label for="cubic">cubic</label>
                                <input type="number" class="form-control" name="cubic" id="cubic"  disabled> 
                            </div>
                        </div>
                    </div>

                    <div class ="row">
                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="fba_shipping_est">fba_shipping_est</label>
                                <input type="number" class="form-control" name="fba_shipping_est" id="fba_shipping_est" value ="0" >
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
                            <th style="display:none;">ID</th>
                            <th >Channel Name</th>
                            <th style="display:none;">Channel ID</th>
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
                  
                 </div>
                 <div class="card-footer">
                 </div>
             </div>
        </div> 
    </div>
</form>
@endsection

@section('scripts')
<script type= "text/javascript">
</script>
@endsection

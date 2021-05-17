@extends('layouts.admin')

@section('content')
<form role="form" method="post" action="{{route('SalesProductInforController.store') }}">
    {{ csrf_field() }}
    <input type="hidden" name ="_method" value ="PUT">
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
                                <input type="text" class="form-control" name="sku" id="sku" enable="false"  >
                            </div>
                        </div>
                        <div class="col-md-9">
                            <div class="form-group">
                                <label for="title">Product Name</label>
                                <input type="text" class="form-control" name="title"id="title"  >
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-sm-3">
                            <div class="form-group">
                                <label for="height">Height</label>
                                <input type="text" class="form-control" name="height" id="height" >
                            </div>
                        </div>
                        <div class="col-sm-3">
                            <div class="form-group">
                                <label for="width">Width</label>
                                <input type="text" class="form-control" name="width" id="width" >
                            </div>
                        </div>
                        
                        <div class="col-sm-3">
                            <div class="form-group">
                                <label for="length">Length</label>
                                <input type="text" class="form-control" name="length" id="length"  >
                            </div>
                        </div>

                        <div class="col-sm-3">
                            <div class="form-group">
                                <label for="length">Weight</label>
                                <input type="text" class="form-control" name="weight" id="the_weight"  >
                            </div>
                        </div>
                    </div>
                    <div class ="row">
                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="description">%deposit</label>
                                <input type="text" class="form-control" name="per_deposit" id="per_deposit" >
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="description">% full payment</label>
                                <input type="text" class="form-control" name="per_full_payment" id="per_full_payment" >
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="description">% partner split </label>
                                <input type="text" class="form-control" name="per_rev_split_for_partner" id="per_rev_split_for_partner" >
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="description">con20_capacity </label>
                                <input type="text" class="form-control" name="con20_capacity" id="con20_capacity" >
                            </div>
                        </div>
                    </div>
                    <div class ="row">
                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="exw_vn">exw_vn</label>
                                <input type="text" class="form-control" name="exw_vn" id="exw_vn" >
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="description">fob_vn</label>
                                <input type="text" class="form-control" name="fob_vn" id="fob_vn" >
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="description">fob_cn</label>
                                <input type="text" class="form-control" name="fob_cn" id="fob_cn" >
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="description">fob_us</label>
                                <input type="text" class="form-control" name="fob_us" id="fob_us" >
                            </div>
                        </div>
                    </div>
                    <div class ="row">
                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="description">cosg_est</label>
                                <input type="text" class="form-control" name="cosg_est" id="cosg_est" >
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="description">per_mkt</label>
                                <input type="text" class="form-control" name="per_mkt" id="per_mkt" >
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="description">per_promotion</label>
                                <input type="text" class="form-control" name="per_promotion" id="per_promotion" >
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="description">per_duty</label>
                                <input type="text" class="form-control" name="per_duty" id="per_duty" >
                            </div>
                        </div>
                    </div>
                    <div class ="row">
                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="description">per_wh_fee</label>
                                <input type="text" class="form-control" name="per_wh_fee" id="per_wh_fee" >
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="description">per_handing_fee</label>
                                <input type="text" class="form-control" name="per_handing_fee" id="per_handing_fee" >
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="description">shiping_fee_est</label>
                                <input type="text" class="form-control" name="shiping_fee_est" id="shiping_fee_est" >
                            </div>
                        </div>
                        <div class="col-sm-3">
                            <div class="form-group">
                                <label for="cubic">cubic</label>
                                <input type="text" class="form-control" name="cubic" id="cubic"  disabled> 
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card-footer">
                    <button type="submit" class="btn btn-primary">
                        <i class="fa fa-save"></i>
                    </button>
                </div>
            </div>
        </div>
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
                            <th style="display:none;" >Channel ID</th>
                            <th>Channel Name</th>
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
@endsection

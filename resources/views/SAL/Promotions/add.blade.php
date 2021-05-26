@extends('layouts.admin')
@section('content')
  
    <div class="col-md-8" style="padding-right:0px">
    <div class="alert alert-dange ajax-error" role="alert">
            <span style="font-weight: bold; font-size: 18px;">Thông báo!</span><br>
            <div class="ajax-error-ct"></div>
        </div>
        <div class="alert ajax-success" role="alert" style="width: 350px;background: rgba(92,130,79,0.9); display:none; color: #fff;"><span
                style="font-weight: bold; font-size: 18px;">Thông báo!</span>
            <br>
            <div class="ajax-success-ct"></div>
        </div>
        <div class="card card-primary">
            <div class="card-header">
                <h3 class="card-title"> Promotion </h3>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="promotion_no">promotion_no</label>
                            <input type="text" class="form-control" name="promotion_no" id="promotion_no"  required >
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="life_circle">Promotion Type</label>
                            <select class="form-control" name ="promotion_type" id="promotion_type" required>
                                {!! getList($dsTypes,0) !!}
                            </select>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="promotion_status">Promotion Status</label>
                            <select class="form-control" name ="promotion_status" id="promotion_status"required>
                                {!! getList($dsStatuses,0) !!}
                            </select>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="from_date">From Date</label>
                            <input type="date" class="form-control" name="from_date" id="from_date"  required >
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="to_date">To Date</label>
                            <input type="date" class="form-control" name="to_date" id="to_date"  required >
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="promotion_status">Channel</label>
                            <select class="form-control" name ="channel_id" id="channel_id" required>
                                    {!! getList($dsChannels,2) !!}
                            </select>
                        </div>
                    </div>
                </div>
            </div>
            <div class="card-footer">
                </div>
        </div>
    </div>
    <div class="col-md-8"  style="padding-right:0px" >
            <div class="card card-primary">
                <div class="card-header">
                    <h3 class="card-title"> Promotion Detail </h3>
                    <div class="card-tools">
                    <div class="input-group" style="height: 35px;width:550px">
                        <input type="text" id="search-pro-box" name="search"
                            placeholder="Input ASIN or Product name"
                            class="form-control"
                            style="height: 35px;border-radius: 15px 0 0 15px;border-right: 0;background-color: #f2f4f6;"
                        >
                        <div class="input-group-append">
                            <span class="input-group-text"
                                style="background-color: #f2f4f6;border-left: 0px;border-radius: 0px 15px 15px 0px;">
                                <i class="fas fa-search"></i>
                            </span>
                        </div>
                    </div>
                </div>
                </div>
                <div class="card-body">
                <div id="message"></div>
                <table id="table_promotion_dt"
                        class="table table-head-fixed table-hover table-bordered table-striped text-nowrap">
                        <thead>
                            <tr>
                                <th style="width:10px;">#</th>
                                <th style="display:none;">ID</th>
                                <th style="display:none;">Promotion ID</th>
                                <th style="display:none;">Product ID</th>
                                <th  style="width:40px;">ASIN </th>
                                <th style="width:150px;">Product Name </th>
                                <th  style="width:40px;">%Funding</th>
                                <th  style="width:40px;">Funding</th>
                                <th  style="width:40px;">Unit Sold</th>
                                <th style="width:40px;">Amoutn Spend</th>
                                <th  style="width:40px;">Revenue</th>
                                <th  style="width:20px;">Action</th>
                            </tr>
                        </thead>
                        <tbody class="list_promotion_dt" id="list_promotion_dt">
                        </tbody>
                        <tfoot>
                            <tr>
                                <td colspan="7" style="text-align: right;font-weight: bold;">Total:</td>
                                <td colspan="1"><span id="total" class="total">0</span></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
                <div class="card-footer">
                    <button type="submit" class="btn btn-primary" onclick="save_promotion()">
                        <i class="fa fa-save"></i>
                    </button>
                </div>
            </div>
    </div> 
@endsection
@section('scripts')
<script src="{{asset('plugins/jquery-ui/jquery-ui.min.js')}}"></script>
<script src="{{asset('js/Sales/Promotions.js') }}"></script>

<script>
$(document).ready(function() {
    $('.promotion_type').select2({
    theme: 'bootstrap4'
    })
    $('.promotion_status').select2({
    theme: 'bootstrap4'
    })

    $('.channel_id').select2({
    theme: 'bootstrap4'
    })
});
</script>

@endsection

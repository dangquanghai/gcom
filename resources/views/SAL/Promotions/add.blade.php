@extends('layouts.admin')
@section('content')

<form role="form" method="post" action="">
    {{ csrf_field() }}
    <input type="hidden" name ="_method" value ="PUT">
    
    <div class="col-md-8" style="padding-right:0px">
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
                            <select class="form-control" name ="	promotion_type" id="promotion_type" required>
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
                            <select class="form-control" name ="channel" id="channel"required>
                                    {!! getList($dsChannels,0) !!}
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
                </div>
                <div class="card-body">
                <div id="message"></div>
                <table  id="promotion_detail" class="table table-bordered table-hover" >
                    <thead>
                        <tr>
                        <th style="display:none;">ID</th>
                        <th>Promotion ID</th>
                        <th>ASIN</th>
                        <th>SKU</th>
                        <th>%Funding</th>
                        <th>Unit Sold</th>
                        <th>Amount Spent</th>
                        <th>Revenue</th>
                        <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                    </tbody>
                </table>
                </div>
                <div class="card-footer">
                    <button type="submit" class="btn btn-primary">
                        <i class="fa fa-save"></i>
                    </button>
                </div>
            </div>
    </div> 
</form>

@endsection
@section('scripts')
<script type= "text/javascript">
</script>
@endsection

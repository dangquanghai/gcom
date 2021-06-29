@extends('layouts.admin')
@section('content')
<form action="{{ route('fa.CashFlow.Chart') }}" method="POST" >
 @csrf
    <div class="row">
        <div class="col-lg-2"  style="padding-right:0px">
            <div class="card card-primary">
                <div class="card-header">
                    <h3 class="card-title">Search</h3>
                  </div>
                <div class="card-body">
                    <div class="form-group">
                        <label for="from_year">From Year</label>
                        <input type="number" class="form-control" id="from_year" name="from_year"  value="{{ old('from_year')}}">
                     </div>
                     <div class="form-group">
                        <label for="from_week">From Week</label>
                        <input type="number" class="form-control" id="from_week" name="from_week"  value="{{ old('from_week')}}">
                     </div>
                     <div class="form-group">
                        <label for="to_year">To Year</label>
                        <input type="number" class="form-control" id="to_year" name="to_year"  value="{{ old('to_year')}}">
                     </div>
                     <div class="form-group">
                        <label for="to_week">To Week</label>
                        <input type="number" class="form-control" id="to_week" name="to_week"  value="{{ old('to_week')}}">
                     </div>

                     <div class="form-group">
                        <label for="vendor_id">Account In</label>
                        <select class="form-control" name="cash_account" id="cash_account" >
                          {!! getList($dsAccount,'0')!!}
                        </select>
                     </div>
                </div>
                <div class="card-footer">
                    <button type="submit" class="btn btn-primary">
                      <i class="fa fa-search"></i>
                    </button>
                </div>
            </div>
        </div>
        <div class="col-lg-10" style="padding-right:0px">
            <div class="card card-primary">
                <div class="card-header">
                    <h3 class="card-title">Chart</h3>
                  </div>
                <div class="card-body">
                    <figure class="highcharts-figure">
                        <div id="container"></div>
                    </figure>
                </div>
            </div>
        </div>
    </div>
</form>
@endsection

@section('scripts')
<script>
$(document).ready(function(){

$("#from_year").val(new Date().getFullYear());
$("#to_year").val(new Date().getFullYear());

var ds = {!!json_encode($CashFlow)!!};
var dsWeek= {!!json_encode($ArrWeek)!!};

Highcharts.chart('container', {
    title: {
        text: 'Cashflow'
    },
    xAxis: {
        categories: dsWeek
    },
    labels: {
        items: [{
            html: 'Total',
            style: {
                left:'50px',
                top: '1px',
                color: ( // theme
                    Highcharts.defaultOptions.title.style &&
                    Highcharts.defaultOptions.title.style.color
                ) || 'black'
            }
        }]
    },
    series: ds
});
});
</script>
@endsection
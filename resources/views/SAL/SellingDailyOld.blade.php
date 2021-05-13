@extends('layouts.admin')
@section('content')
<div class="container-fluid">
    <!-- Control the column width, and how they should appear on different devices -->
<div class="card-body">
    <form action="{{route('sal.selling.daily')}}" method="POST" >
        @csrf
        <div id="div1" class="row">
            <div class="col-lg-2" style="background-color:whitesmoke;">
                <h5>Sales Daily</h5>
                <div class="form-group">
                          Number Type:
                          <select name="type" id="type" class="form-control" value="{{old('type')}}">
                            <option value="0">Quantity</option>
                            <option value="1">Revenue</option>
                         </select>
                           From Date:
                            <input type="date" class="form-control"  width= "150px"   name="fromDate"   value="{{old('fromDate')}}" required>
                           ToDate:
                            <input type="date" class="form-control"  name="toDate"  value="{{old('toDate')}}" required>
                           Channel:
                           <select name="channel" id="channel" class="form-control" value="{{old('channel')}}">
                            <option value="0">All</option>
                            <option value="2">AVC-DS</option>
                            <option value="4">Walmart DSV </option>
                            <option value="5">Walmart MKP</option>
                            <option value="6">EBAY</option>
                            <option value="7">Craiglist/Local </option>
                            <option value="8">Website</option>
                            <option value="10">FBM</option>
                            <option value="12">Wayfair</option>
                          </select>
                           SKU:
                           <input type = "select" name ="sku" class="form-control" value="{{old('sku')}}" >
                           <button type="submit" class="btn btn-success">View</button>
                </div>
            </div>
            <div class="col-lg-10"  style ="background-color:whitesmoke;">
              <figure class="highcharts-figure">
                <div id="container"></div>
                <p class="highcharts-description">
                    This chart shows how data labels can be added to the data series. This
                    can increase readability and comprehension for small datasets.
                </p>
              </figure>
            </div>
        </div>
    </form>
</div>
</div>
@endsection
@section('styles')
<style>
#toolbar {
  margin: 0;
}

.highcharts-figure, .highcharts-data-table table {
    min-width: 360px;
    max-width: 1350px;
    margin: 1em auto;
}

.highcharts-data-table table {
	font-family: Verdana, sans-serif;
	border-collapse: collapse;
	border: 1px solid #EBEBEB;
	margin: 10px auto;
	text-align: center;
	width: 100%;
	max-width: 500px;
}
.highcharts-data-table caption {
    padding: 1em 0;
    font-size: 1.2em;
    color: #555;
}
.highcharts-data-table th {
	font-weight: 600;
    padding: 0.5em;
}
.highcharts-data-table td, .highcharts-data-table th, .highcharts-data-table caption {
    padding: 0.5em;
}
.highcharts-data-table thead tr, .highcharts-data-table tr:nth-child(even) {
    background: #f8f8f8;
}
.highcharts-data-table tr:hover {
    background: #f1f7ff;
}

</style>
@endsection
@section('scripts')
<script>

var ds = {!! json_encode($dx) !!};

var dsCols = {!! json_encode($ArrCols) !!};

Highcharts.chart('container', {
    chart: {
        type: 'line'
    },
    title: {
        text: 'Sales Daily'
    },
    xAxis: {
        categories: dsCols,
        showLastLabel: false
    },
    yAxis: {
        title: {
            text: 'Sales Daily'
        }
    },
    plotOptions: {
        line: {
            dataLabels: {
                enabled: true
            },
            enableMouseTracking: false
        },

    },
    series:ds
});
</script>
@endsection

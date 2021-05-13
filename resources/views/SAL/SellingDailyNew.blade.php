@extends('layouts.admin')
@section('content')
    <form action="{{route('sal.selling.daily')}}" method="POST" >
        @csrf
        <div id="div1" class="row">
            <div class="col-md-2" style="padding-left:0px">
                <div class="card card-primary">
                    <div class="card-header">
                        <h3 class="card-title">Search</h3>
                    </div>
                    <div class="card-body">
                        <div class="form-group">
                                Number Type:
                                <select name = "type" id="type" class="form-control" >
                                    {!! getList($dsTypes,$Type) !!}
                                </select>
                                From Date:
                                    <input type="date" class="form-control"  width= "150px"   name="fromDate"
                                      value="{{$FromDate}}" required>
                                ToDate:
                                    <input type="date" class="form-control"  name="toDate"
                                    value="{{$ToDate}}" required >
                        </div>
                    </div>
                    <div class="card-footer">
                        <button type="submit" class="btn btn-primary">
                          <i class="fa fa-search"></i>
                        </button>
                    </div>
                </div>
            </div>
            <div class="col-md-10"style="padding-left:0px">
                    <div class="card card-primary">
                        <div class="card-header">
                            <h3 class="card-title">Chart Sales Daily</h3>
                        </div>
                        <div class="card-body" style="height:500px;">
                            <figure class="highcharts-figure">
                                <div id="container"></div>
                                <p class="highcharts-description"></p>
                            </figure>
                        </div>
                    </div>
                    <div class="card card-primary">
                        <div class="card-header">
                            <h3 class="card-title">Sales History Weekly</h3>
                        </div>
                        <div class="card-body" >
                            <div id="toolbar" class="select">
                                <select class="form-control">
                                  <option value="">Export Basic</option>
                                  <option value="all">Export All</option>
                                  <option value="selected">Export Selected</option>
                                </select>
                            </div>
                            <table id ="table"
                                data-show-export="true"
                                data-pagination="true"
                                data-side-pagination="server"
                                data-click-to-select="true"
                                data-toolbar="#toolbar"
                                data-show-toggle="true"
                                data-detail-formatter="detailFormatter"
                                data-show-columns="true"
                                data-search="true"
                                data-show-refresh="true"
                                data-show-fullscreen="true"
                                data-show-columns-toggle-all="true"
                                data-detail-view="true"
                                data-minimum-count-columns="2"
                                data-show-pagination-switch="true"
                                data-pagination="true"
                                data-page-list="[10, 25, 50, 100, all]"
                                data-show-footer="true"
                                data-response-handler="responseHandler">
                            </table>
                        </div>
                    </div>
                </div>
            </div>
    </form>
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

var $table = $('#table')
var dsSalesHistoryWeekly = {!! json_encode($dsSalesHistoryWeekly ) !!}
var TheColumns = {!! json_encode($TheColumns) !!}

$(function() {
  $('#toolbar').find('select').change(function () {
      
    $table.bootstrapTable('destroy').bootstrapTable({
      exportDataType: $(this).val(),
      exportTypes: ['json', 'xml', 'csv', 'txt', 'sql', 'excel', 'pdf'],
      data:dsSalesHistoryWeekly,
      exportOptions: {
            fileName:'Sales History Weekly',
            preventInjection: false
          },
          columns: TheColumns
    })
  }).trigger('change')
})
</script>
@endsection


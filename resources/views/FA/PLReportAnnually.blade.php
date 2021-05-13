@extends('layouts.admin')
@section('content')
<form action="{{route('fa.PLReportAnnually')}}" method="POST" >
@csrf
  <div class ="row">
  <!-- Control the column width, and how they should appear on different devices -->
    <div class="col-md-01" style="padding-right:0px">
      <div class="card card-primary">
        <div class="card-header">
          <h3 class="card-title">Search</h3>
        </div>
        <div class="card-body">
          <div class="form-group">
            <label for="year">Year</label>
            <input type="number" class="form-control" id="year" name="year"
            value="{{$TheYear}}">
          </div>
        </div>
        <div class="card-footer">
          <button type="submit" class="btn btn-primary">
            <i class="fa fa-search"></i>
          </button>
        </div>
      </div>
    </div>

    <div class="col-md-10"  style="padding-left:0px">
      <div class="card card-primary">
        <div class="card-header">
          <h3 class="card-title">Chart</h3>
        </div>
        <div class="card-body">
          <figure class="highcharts-figure">
            <div id="container"></div>
            <p class="highcharts-description">
            </p>
          </figure>
        </div>
      </div>

      <div class="card card-primary">
        <div class="card-header">
          <h3 class="card-title">Data</h3>
        </div>
        <div class="card-body">
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
@endsection
@section('scripts')
<script>

var $table = $('#table')
var ds = {!! json_encode($ds) !!};
//-------------------------
function totalTextFormatter(data) {
     return 'Total Line5:'
  }
//-------------------------
function totalRevenue(data) {
  var table = document.getElementById("table")
  sumVal = 0;
  for(var i = 1; i < table.rows.length; i++ )
  {
    if(table.rows[i].cells[2].innerHTML=='Line - 5')
    {
      sumVal = sumVal + parseInt(table.rows[i].cells[4].innerHTML);
    }
  }
  return sumVal;
}
//-------------------------
$(function() {
  var ArrMonth  = {!! json_encode($ArrMonth) !!};
  var dsExpensives = {!! json_encode($dsExpensives) !!};

  var chart = new Highcharts.Chart({
    chart: {
        renderTo:'container',
        type:'column',
        events: {
          load() {
            this.showHideFlag = true;
      }
    }
    },
    title:{
        text:'EXPENSIVES IN PL REPORT'
    },
    credits:{enabled:false},
    legend:{
    },
    plotOptions: {
    column: {
      events: {
        legendItemClick() {
          let chart = this.chart,
            series = chart.series;
          if (this.index === 0) {
            if (chart.showHideFlag) {
              series.forEach(series => {
                series.hide()
              })
            } else {
              series.forEach(series => {
                series.show()
              })
            }
            chart.showHideFlag = !chart.showHideFlag;
          }
        }
      }
    }
  },
xAxis: {
    categories: ArrMonth,
    crosshair: true
  },
    yAxis:{
        lineColor:'#999',
        lineWidth:1,
        tickColor:'#666',
        tickWidth:1,
        tickLength:3,
        gridLineColor:'#ddd',
        title:{
            text:'',
            rotation:0,
            margin:50,
        }
    },
   series:dsExpensives
});

/*
events: {
        load: function(event) {
            this.series.forEach(function(d,i){
              if( d.options.name=='COSG'
              || d.options.name=='Promotion'
              || d.options.name=='Sem'
              || d.options.name=='Shiping'
              || d.options.name=='MNG Fee'
              || d.options.name=='Storage Fee'
              || d.options.name=='Other Fee'
              || d.options.name=='Profit(Line-3)')
              {
                d.hide()
              }
            })
        }
    }
*/

  $('#toolbar').find('select').change(function () {
    $table.bootstrapTable('destroy').bootstrapTable({
      exportDataType: $(this).val(),
      exportTypes: ['json', 'xml', 'csv', 'txt', 'sql', 'excel', 'pdf'],
      data:ds,
      exportOptions: {
            fileName: 'PL Report Annually',
            preventInjection: false
          },
      columns: [
        {
          field: 'state',
          checkbox: true,
          visible: $(this).val() === 'selected'
        },
        {field:'the_month',title:'Month'},
        {field:'line_name',title:'Line Name',footerFormatter: totalTextFormatter},
        {field: "line_des", title: "Description"},
        {field: "the_value", title: "Revenue",footerFormatter: totalRevenue},
        {field: "the_balance", title: "Balance"},
        {field: "the_ratio", title: "ratio %"}
      ]
    })
  }).trigger('change')
})
</script>
@endsection


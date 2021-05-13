@extends('layouts.admin')
@section('content')
<form action="{{route('fa.plReport.monthly')}}" method="POST" >
@csrf
<div class="row">
  <div class="col-md-01" style="padding-right:0px">
    <div class="card card-primary">
      <div class="card-header">
        <h3 class="card-title">Search</h3>
      </div>
      <div class="card-body">
        <div class="form-group">
          <label for="year">Year</label>
          <input type="number" class="form-control" id="year" name="year"
           value="{{ $TheYear}}">
        </div>
        <div class="form-group">
          <label for="month">Month</label>
          <input type="number" class="form-control" id="month" name="month"
           value="{{ $TheMonth}}">
        </div>
      </div>
      <div class="card-footer">
        <button type="submit" class="btn btn-primary">
          <i class="fa fa-search"></i>
        </button>
      </div>
    </div>
  </div>
  <div class="col-md-10"  style="padding-right:0px">
      <div class="card card-primary">
        <div class="card-header">
          <h3 class="card-title">Data</h3>
        </div>
        <div id="toolbar" class="select">
          <select class="form-control">
            <option value="">Export Basic</option>
            <option value="all">Export All</option>
            <option value="selected">Export Selected</option>
          </select>
        </div>

        <div id="container">
          <div class="loading">
              <i class="icon-spinner icon-spin icon-large"></i>
              Loading data from Google Spreadsheets...
          </div>
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
</form>
@endsection

@section('scripts')
<script>


var $table = $('#table')
var ds = {!! json_encode($ds) !!};
//------------------
Highcharts.data({
    googleSpreadsheetKey: '14632VxDAT-TAL06ICnoLsV_JyvjEBXdVY-J34br5iXY',

    // Custom handler for columns
    parsed: function (columns) {

        /**
         * Event handler for clicking points. Use jQuery UI to pop up
         * a pie chart showing the details for each state.
         */
        function pointClick() {
            var row = this.options.row,
                $div = $('<div></div>')
                    .dialog({
                        title: this.name,
                        width: 320,
                        height: 300
                    });

            window.chart = new Highcharts.Chart({
                chart: {
                    renderTo: $div[0],
                    type: 'pie',
                    width: 290,
                    height: 240
                },
                title: {
                    text: null
                },
                series: [{
                    name: 'Votes',
                    data: [{
                        name: 'Trump',
                        color: '#C40401',
                        y: parseInt(columns[3][row], 10)
                    }, {
                        name: 'Clinton',
                        color: '#0200D0',
                        y: parseInt(columns[2][row], 10)
                    }],
                    dataLabels: {
                        format: '<b>{point.name}</b> {point.percentage:.1f}%'
                    }
                }]
            });
        }

        // Make the columns easier to read
        var keys = columns[0],
            names = columns[1],
            percent = columns[7],
            mapData = Highcharts.maps['countries/us/us-all'],
            // Build the chart options
            options = {
                chart: {
                    type: 'map',
                    map: mapData,
                    renderTo: 'container',
                    borderWidth: 1
                },

                title: {
                    text: 'US presidential election 2016 results'
                },
                subtitle: {
                    text: 'Source: <a href="https://transition.fec.gov/pubrec/fe2016/2016presgeresults.pdf">Federal Election Commission</a>'
                },

                legend: {
                    align: 'right',
                    verticalAlign: 'top',
                    x: -100,
                    y: 70,
                    floating: true,
                    layout: 'vertical',
                    valueDecimals: 0,
                    backgroundColor: ( // theme
                        Highcharts.defaultOptions &&
                        Highcharts.defaultOptions.legend &&
                        Highcharts.defaultOptions.legend.backgroundColor
                    ) || 'rgba(255, 255, 255, 0.85)'
                },

                mapNavigation: {
                    enabled: true,
                    enableButtons: false
                },

                colorAxis: {
                    dataClasses: [{
                        from: -100,
                        to: 0,
                        color: '#0200D0',
                        name: 'Clinton'
                    }, {
                        from: 0,
                        to: 100,
                        color: '#C40401',
                        name: 'Trump'
                    }]
                },

                series: [{
                    data: [],
                    joinBy: 'postal-code',
                    dataLabels: {
                        enabled: true,
                        color: '#FFFFFF',
                        format: '{point.postal-code}',
                        style: {
                            textTransform: 'uppercase'
                        }
                    },
                    name: 'Republicans margin',
                    point: {
                        events: {
                            click: pointClick
                        }
                    },
                    tooltip: {
                        ySuffix: ' %'
                    },
                    cursor: 'pointer'
                }, {
                    name: 'Separators',
                    type: 'mapline',
                    nullColor: 'silver',
                    showInLegend: false,
                    enableMouseTracking: false
                }]
            };
        keys = keys.map(function (key) {
            return key.toUpperCase();
        });
        Highcharts.each(mapData.features, function (mapPoint) {
            if (mapPoint.properties['postal-code']) {
                var postalCode = 'Kaka';//mapPoint.properties['postal-code'],
                    i = $.inArray(postalCode, keys);
                options.series[0].data.push(Highcharts.extend({
                    value: parseFloat(percent[i]),
                    name: names[i],
                    'postal-code': postalCode,
                    row: i
                }, mapPoint));
            }
        });

        // Initiate the chart

        window.chart = new Highcharts.Map(options);
    },

    error: function () {
        $('#container').html('<div class="loading">' +
            '<i class="icon-frown icon-large"></i> ' +
            '<p>Error loading data from Google Spreadsheets</p>' +
            '</div>');
    }
})
//------------------

function totalTextFormatter(data) {
     return 'Total Line 5'
  }
  function totalAmountFormatter(data) {
    var table = document.getElementById("table")
    sumVal = 0;
    for(var i = 1; i < table.rows.length; i++ )
    {
      if(table.rows[i].cells[2].innerHTML=='Line - 5')
      {
       sumVal = sumVal + parseInt(table.rows[i].cells[5].innerHTML);
      }
    }
    return sumVal;
  }
//-------------------------
$(function() {
  $('#toolbar').find('select').change(function () {
    $table.bootstrapTable('destroy').bootstrapTable({
      exportDataType: $(this).val(),
      exportTypes: ['json', 'xml', 'csv', 'txt', 'sql', 'excel', 'pdf'],
      data:ds,
      exportOptions: {
            fileName: 'PL Report Summary',
            preventInjection: false
          },
      columns: [
        {
          field: 'state',
          checkbox: true,
          visible: $(this).val() === 'selected'
        },
        {field:'name',title:'Channel Name'},
        {title: "line_name", field: "line_name" ,footerFormatter: totalTextFormatter},
        {title: "line_des", field: "line_des"},
        {title: "amount", field: "amount" ,footerFormatter: totalAmountFormatter},
        {title: "balance", field: "balance" },
        {title: "ratio", field: "ratio"}
      ]
    })
  }).trigger('change')

})
</script>
@endsection


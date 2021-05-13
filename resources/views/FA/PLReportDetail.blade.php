@extends('layouts.admin')
@section('content')
<form action="{{route('fa.plReport.detail')}}" method="POST" >
@csrf
<div class="row">
  <div class="col-md-2"  style="padding-right:0px">
    <div class="card card-primary">
      <div class="card-header">
          <h3 class="card-title">Search</h3>
        </div>
          <div class="card-body">
            Year:
            <input type="text" class="form-control"  width= "150px"   name="year"   min="2020" max="2050"
             value="{{$TheYear}}" required>
            Month:
            <input type="number" class="form-control"  name="month" min="1" max="12"
            value="{{$TheMonth}}" required>
            Sales Team:
            <select name="SalesTeam" id="SalesTeam" class="form-control" value="{{$SalesTeam}}">
              {!! getList($dsSalesTeams,$SalesTeam) !!}
            </select>
            Channel:
            <select name="channel" id="channel" class="form-control"  >
              {!! getList($dsChannels,$Channel) !!}
            </select>
              Company Product:
              <select name="company_product" id="company_product" class="form-control" >
              <option value="0">All</option>
              <option value="1">HMD Only</option>
              <option value="2">Yes 4 All</option>
            </select>
          </div>
          <div class="card-footer">
            <button type="submit" class="btn btn-primary">
              <i class="fa fa-search"></i>
            </button>
          </div>
        </div>
      </div>

      <div class="col-md-10"   style="padding-right:0px">
        <div class="card card-primary">
         <div class="card-header">
            <h3 class="card-title">Detail</h3>
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
              data-side-pagination="server"
              data-click-to-select="true"
              data-toolbar="#toolbar"
              data-show-toggle="true"
              data-detail-formatter="detailFormatter"
              data-show-columns="true"
              data-show-refresh="true"
              data-show-fullscreen="true"
              data-detail-view="true"
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
#cardSearch {
  margin: 0;
}
</style>
@endsection
@section('scripts')
<script>

var $table = $('#table')
var ds = {!! json_encode($dsDetail) !!}

function SetTotalCaption(data) {
     return 'Total'
  }

function totalProfit(data) {
    var table = document.getElementById("table")
    sumVal = 0;
    for(var i = 1; i < table.rows.length; i++ )
    {
      sumVal = sumVal + parseInt(table.rows[i].cells[30].innerHTML);
    }
    var num = '$' + sumVal.toFixed(0).replace(/(\d)(?=(\d\d\d)+(?!\d))/g, "$1,");
    return num;
  }
//-------------------------
function totalRevenue(data) {
    var table = document.getElementById("table")
    sumVal = 0;
    for(var i = 1; i < table.rows.length; i++ )
    {
      sumVal = sumVal + parseInt(table.rows[i].cells[6].innerHTML);
    }
    var num = '$' + sumVal.toFixed(0).replace(/(\d)(?=(\d\d\d)+(?!\d))/g, "$1,");
    return num;
  }
  //-------------------------
function totalNetSales(data) {
    var table = document.getElementById("table")
    sumVal = 0;
    for(var i = 1; i < table.rows.length; i++ )
    {
      sumVal = sumVal + parseInt(table.rows[i].cells[8].innerHTML);
    }
    var num = '$' + sumVal.toFixed(0).replace(/(\d)(?=(\d\d\d)+(?!\d))/g, "$1,");
    return num;
  }
//-------------------------
$(function() {
  $('#toolbar').find('select').change(function () {
    $table.bootstrapTable('destroy').bootstrapTable({
      exportDataType: $(this).val(),
      exportTypes: ['json', 'xml', 'csv', 'txt', 'sql', 'excel', 'pdf'],
      data:ds,
      height: 850,
      exportOptions: {
            fileName: 'PL Report Detail',
            preventInjection: false
          },
      columns: [
        {
          field: 'state',
          checkbox: true,
          visible: $(this).val() === 'selected'
        },
        {field:'sku',title:'sku',footerFormatter: SetTotalCaption},
        {title: "Title--------------------------------------------------------------------------", field: "title" ,width:350},
        {title: "Channel", field: "channel"},
        {title: "Qty", field: "sell_quantity"},
        {title: "Return", field: "return_quantity"},
        {title: "revenue", field: "revenue", footerFormatter: totalRevenue},
        {title: "refund", field: "refund"},

        {field:'nest_sales',title:'net_sales', footerFormatter: totalNetSales},
        {title: "cogs", field: "cogs" },
        {title: "promotion", field: "promotion"},
        {title: "seo_sem", field: "seo_sem"},
        {title: "shiping_fee", field: "shiping_fee"},
        {title: "other_selling_expensives", field: "other_selling_expensives" },
        {title: "dip", field: "dip"},

        {field:'msf',title:'msf'},
        {title: "selling_fees", field: "selling_fees" },
        {title: "fullfillment", field: "fullfillment"},
        {title: "chargeback", field: "chargeback"},
        {title: "coop", field: "coop"},
        {title: "freight_cost", field: "freight_cost"},
        {title: "freight_handling_return_cost", field: "freight_handling_return_cost" },
        {title: "ebay_final_fee", field: "ebay_final_fee"},

        {field:'paypal_fee',title:'paypal_fee'},
        {title: "discount", field: "discount" },
        {title: "clip_fee", field: "clip_fee"},
        {title: "liability_insurance", field: "liability_insurance"},
        {title: "commission ", field: "commission"},
        {title: "vine", field: "vine" },
        {title: "other_fee", field: "other_fee"},
        {title: "profit", field: "profit",footerFormatter: totalProfit}
      ]
    })
  }).trigger('change')
})

</script>
@endsection


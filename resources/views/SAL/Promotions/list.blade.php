@extends('layouts.admin')
@section('content')
<form action="{{route('Promotion.index')}}" method="POST" >
@csrf
<div class ="row">
  <div class="col-md-2" style="padding-right:0px">
    <!-- general form elements -->
    <div class="card card-primary">
      <div class="card-header">
        <h3 class="card-title">Search</h3>
      </div> <!-- /.card-header -->
      <!-- form start -->
      <form role="form">
        <div class="card-body">
          <div class="form-group">
            <label for="sku">SKU</label>
            <input type="text" class="form-control" id="sku" name="sku" value="{{$sku}}" >
          </div>
          <div class="form-group">
            <label for="asin">ASIN</label>
            <input type="text" class="form-control" id="asin" name="asin" value="{{$asin}}" >
          </div>
          <div class="form-group">
            <label>Title</label>
            <input type="text" id ="title" name ="title" class="form-control" value="{{$title}}">
          </div>
          
          <div class="form-group">
            <label for="promotion_no">Promo ID</label>
            <input type="text" class="form-control" id="promotion_no" name="promotion_no" value="{{$promotion_no}}">
          </div>
        
          <div class="form-group">
            <label for="promotion_type">Promotion Type</label>
            <select class="form-control" name="promotion_type" id="promotion_type" >
              {!! getList($PromotionTypes,$promotion_type) !!}
            </select>
          </div>
          <div class="form-group">
            <label for="promotion_status">Promotion Status</label>
            <select class="form-control" name="promotion_status" id="promotion_status" >
              {!! getList($PromotionStatuses,$promotion_status) !!}
            </select>
          </div>

          <div class="form-group">
            <label for="from_date">From Date</label>
            <input type="date" class="form-control" id="from_date" name="from_date"  value="{{$from_date}}" required>
          </div>

          <div class="form-group">
            <label for="to_date">To Date</label>
            <input type="date" class="form-control" id="to_date" name="to_date"  value="{{$to_date}}" required >
          </div>
          <div class="form-group">
            <label for="channel">Channel</label>
            <select class="form-control" name="channel_id" id="channel_id" >
            {!! getList($Channels,$channel) !!}
          </select>
          </div>
          <div class="form-group">
            <label for="product_group">Brand</label>
            <select class="form-control" name="brand" id="brand" >
            {!! getList($Brands,$brand) !!}
          </select>
          </div>
        </div> <!-- /.card-body -->
        <div class="card-footer">
          <button type="submit" class="btn btn-primary">
            <i class="fa fa-search"></i>
          </button>
        </div><!-- /.card-footer -->
      </form>
    </div><!-- card card-primary -->
  </div>  <!-- /.col-md-2 -->
  
  <div class="col-md-10" style="padding-left:0px">
      <div class="card card-primary">
              <div class="card-header">
              <div class="card-tools">
                  <a href="{{route('Promotion.create')}}"><i class="fa fa-plus-square"></i></a>
              </div>
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
          </div> <!-- /.card-body -->
          <div class="card-footer">
          </div>  <!-- /.card-footer -->
      </div><!-- /.card-primary -->
  </div><!-- /.col-md-10 -->
</div><!-- /.row -->
</form>
@endsection
@section('scripts')
<script>
var $table = $('#table');

var ds = {!! json_encode($dsPromotions) !!};

function SetTotalCaption(data) {
     return 'Total'
  }

function totalUnitSold(data) {
    var table = document.getElementById("table")
    sumVal = 0;
    for(var i = 1; i < table.rows.length; i++ )
    {
      sumVal = sumVal + parseInt(table.rows[i].cells[10].innerHTML);
    }
    var num =  sumVal.toFixed(0).replace(/(\d)(?=(\d\d\d)+(?!\d))/g, "1,");
    return num;
  }
 
  function totalAmountSpent(data) {
    var table = document.getElementById("table")
    sumVal = 0;
    for(var i = 1; i < table.rows.length; i++ )
    {
      sumVal = sumVal + parseInt(table.rows[i].cells[11].innerHTML);
    }
    var num = '$' + sumVal.toFixed(0).replace(/(\d)(?=(\d\d\d)+(?!\d))/g, "$1,");
    return num;
  }


  function totalRevenue(data) {
    var table = document.getElementById("table")
    sumVal = 0;
   
    for(var i = 1; i < table.rows.length; i++ )
    {
      sumVal = sumVal + parseInt(table.rows[i].cells[12].innerHTML);
    }
    var num = '$' + sumVal.toFixed(0).replace(/(\d)(?=(\d\d\d)+(?!\d))/g, "$1,");
    return num;
  }
  
  function EditPromotion(value,row,index)
{
  let url = "{{route('Promotion.edit', ':id') }}";
  url = url.replace(':id', row.id);
  url = '<a href="' + url + '">' + value +  '</a>';
  
  return[
    url
  ].join('')
}


$(function() {
  $('#toolbar').find('select').change(function () {
    $table.bootstrapTable('destroy').bootstrapTable({
      height: 850,
      exportDataType: $(this).val(),
      exportTypes: ['json', 'xml', 'csv', 'txt', 'sql', 'excel', 'pdf'],
      data:ds,
      exportOptions: {
            fileName: 'Promotion mng',
            preventInjection: false
          },
      columns: 
      [
        {field:'id',title:'id',formatter: EditPromotion},
        {field:'asin',title:'asin',footerFormatter: SetTotalCaption},
        {field:'sku',title:'sku' },
        {field:'title',title:'Product Name ---------'},
        {field:'promotion_type',title:'promotion type'},
        {field:'channel',title:'channel'},
        {field:'status',title:'status'},

        {field:'promotion_no',title:'promotion id'},
        {field:'from_date',title:'from_date'},
        {field:'to_date',title:'to_date----'},
        {field:'unit_sold',title:'unit_sold',footerFormatter:totalUnitSold},
        {field:'amount_spent',title:'amount_spent',footerFormatter:totalAmountSpent},
        {field:'revenue',title:'revenue',footerFormatter:totalRevenue}
      ]
    })
  }).trigger('change')
})
</script>
@endsection
@extends('layouts.admin')
@section('content')
<form action="{{route('fa.plReport.summary')}}" method="POST" >
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
          <input type="number" class="form-control" id="year" name="year"  value="{{ old('year')}}">
        </div>
        <div class="form-group">
          <label for="month">Month</label>
          <input type="number" class="form-control" id="month" name="month"  value="{{ old('month')}}">
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

function totalTextFormatter(data) {
     return 'Total Line 5'
  }
  function totalAmountFormatter(data) {
    var table = document.getElementById("table")
    sumVal = 0;
    for(var i = 1; i < table.rows.length; i++ )
    {
      if(table.rows[i].cells[2].innerHTML=='Line-5')
      {
       sumVal = sumVal + parseInt(table.rows[i].cells[6].innerHTML);
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
        {title: "des", field: "des"},
        {title: "amount", field: "amount"},
        {title: "balance", field: "balance" ,footerFormatter: totalAmountFormatter},
        {title: "ratio", field: "ratio"}
      ]
    })
  }).trigger('change')

})
</script>
@endsection


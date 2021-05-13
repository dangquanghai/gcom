@extends('layouts.admin')
@section('content')
<div class="container-fluid">
  <div class="card-body" id ="div1">
    <form action="{{route('fa.po.pending')}}" method="POST" >
     @csrf
     <div class="row">
      <!-- Control the column width, and how they should appear on different devices -->

          <div class="col-md-12"  style ="background-color:whitesmoke;">
            <div class="card card-primary">
              <div class="card-header">
                  <h3 class="card-title">PO PENDING</h3>
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
    </div>
</div>
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
var ds = {!! json_encode($ds) !!}

function SetTotalCaption(data) {
     return 'Total'
  }

function totalAmount(data) {
    var table = document.getElementById("table")
    sumVal = 0;
    for(var i = 1; i < table.rows.length; i++ )
    {
      sumVal = sumVal + parseInt(table.rows[i].cells[3].innerHTML);
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
            fileName: 'PL Report Detail',
            preventInjection: false
          },
      columns: [
        {
          field: 'state',
          checkbox: true,
          visible: $(this).val() === 'selected'
        },
        {field:'po_no',title:'PO No',footerFormatter:SetTotalCaption},
        {title: "Exp Shipdate", field: "exp_shipdate" ,width:350},
        {title: "Est Amount(HMD)", field: "amount" ,width:350,footerFormatter: totalAmount},
      ]
    })
  }).trigger('change')
})
</script>
@endsection

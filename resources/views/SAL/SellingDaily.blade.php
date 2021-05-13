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
                           From Date:
                            <input type="date" class="form-control"  width= "150px"   name="fromDate"   value="<?php echo date('Y-m-d'); ?>" required>
                           ToDate:
                            <input type="date" class="form-control"  name="toDate"  value="<?php echo date('Y-m-d'); ?>" required>
                        <button type="submit" class="btn btn-success">View</button>
                </div>
            </div>
            <div class="col-lg-10"  style ="background-color:whitesmoke;">

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
    </form>
</div>
</div>
@endsection
@section('styles')
<style>
#toolbar {
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
        {field:'sku',title:'sku',footerFormatter: SetTotalCaption},
        {title: "Sale Channel", field: "name" },
        {title: " Product Name", field: "title" },
        {title: "Order date", field: "order_date"},
        {title: "quantity", field: "quantity"},
        {title: "amount", field: "amount"}
      ]
    })
  }).trigger('change')
})
</script>
@endsection


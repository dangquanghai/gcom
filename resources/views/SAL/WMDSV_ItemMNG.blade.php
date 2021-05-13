@extends('layouts.admin')
@section('content')
<form action="{{route('sal.wm.item_mng')}}" method="POST" >
@csrf
<div class="row">
  <div class="col-md-3"  style="padding:0px">
    <div class="card card-primary">
      <div class="card-header">
          <h3 class="card-title">Search</h3>
          <div class="card-tools">
            <a href="" class = "btnAddCondition">And</a> | <a href="" class = "btnAddCondition">Or</a>
           </div>
        </div>
          <div class="card-body">
            <div class ="row">
                <div class="col-md-5" style="padding:0px" >
                    Columns:
                    <select name="column" id="column" class="form-control">
                      {!! GetList($dsColumns,2) !!}
                    </select>
                </div>
                <div class="col-md-3"  style="padding:0px">
                    Operators:
                    <select name="operator" id="operator" class="form-control" >
                        {!! GetList($dsOperators,1) !!}
                    </select>
                </div>
                <div class="col-md-4"  style="padding:0px">
                    Values:
                    <input type="text" class="form-control"  width= "150px" id="value"  name="value" >
                </div>
            </div>
            <div class ="row">
                <textarea id="conditions" name="conditions" rows="4" cols="100%">
                  {!!$SqlCondition!!}
                </textarea>
            </div>
            <div class ="row">
              <div class="col-md-6"  style="padding:0px">
                Selling from:
              <input type="date" id="from_date" name ="from_date" value="{!!$FromDate!!}">
              </div>
              <div class="col-md-6"  style="padding:0px">
                Selling To:
              <input type="date" id="to_date" name ="to_date" value="{!!$ToDate!!}">
            </div>
            </div>
          </div>
          <div class="card-footer">
            <button type="submit" class="btn btn-primary">
              <i class="fa fa-search"></i>
            </button>
            <button type="submit" class="btn btn-primary">
              <i class="fa fa-magic" ></i>
            </button>
          </div>
        </div>
      </div>
      <div class="col-md-9"  style="padding-left:0px">
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
                data-pagination="false"
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
                data-minimum-count-columns="2"
                data-show-pagination-switch="true"
                data-pagination="true"
                data-page-list="[10, 25, 50, 100, all]"
              
                data-response-handler="responseHandler">
              </table>
            </div>
          </div>

    </div>
         
  </div>
        <!-- /.card -->
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
var ds = {!! json_encode($ds) !!}

$(function() {
  $('#toolbar').find('select').change(function () {
    $table.bootstrapTable('destroy').bootstrapTable({
      height: 900,
      exportDataType: $(this).val(),
      exportTypes: ['json', 'xml', 'csv', 'txt', 'sql', 'excel', 'pdf'],
      data:ds,
      exportOptions: {
            fileName: 'WM DSV Product MNG',
            preventInjection: false
          },
      columns: [
        {
          field: 'state',
          checkbox: true,
          visible: $(this).val() === 'selected'
        },
        {field:'item_id',title:'item_id'},
        {field:'sku',title:'sku'},
        {field:'wm_no',title:'wm_no'},
        {field: "product_name", title: "product_name -----------------------------------",formatter:linkName},
        {field: "category", title: "category"},
        {field: "gtin", title: "gtin"},
        {field: "cogs", title: "cogs"},
        {field: "cost", title: "cost"},
        {field: "price", title: "price"},
        {field: "sell_quantity", title: "sell_quantity"},
        {field: "amount", title: "amount"},
        {field: "review_count", title: "review_count"},
        {field: "avg_rating", title: "avg_rating"},
        {field:'avl_unit_in_wh',title:'avl_unit_in_wh'},
        {field:'avl_unit_on_wm',title:'avl_unit_on_wm'},
        {field:'public_status',title:'public_status'},
        {field: "status_change_reason", title: "status_change_reason"},
        {field: "name", title: "Channel"},
      ]
    })
  }).trigger('change')
})

function linkName(value,row,index)
{
  return[
    '<a target="_blank" href="https://www.walmart.com/ip/'+row.item_id+'">',
    value,
    '</a>'
  ].join('')
}
</script>
@endsection

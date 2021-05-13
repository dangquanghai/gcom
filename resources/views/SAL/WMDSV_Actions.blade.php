@extends('layouts.admin')
@section('content')
<form action="{{route('sal.wm.actions')}}" method="POST" >
@csrf
<div class="row">
  <div class="col-12 md ">
    <div class="card card-primary card-tabs">
      <div class="card-header p-0 pt-1">
        <ul class="nav nav-tabs" id="custom-tabs-one-tab" role="tablist">
          <li class="nav-item">
            <a class="nav-link active" id="custom-tabs-one-needinvntory-tab" data-toggle="pill" href="#custom-tabs-one-needinvntory" role="tab" aria-controls="custom-tabs-one-needinvntory" aria-selected="false">Need Inventory</a>
          </li>
          <li class="nav-item">
            <a class="nav-link" id="custom-tabs-one-downcost-tab" data-toggle="pill" href="#custom-tabs-one-downcost" role="tab" aria-controls="custom-tabs-one-downcost" aria-selected="false">Down Cost</a>
          </li>
          <li class="nav-item">
            <a class="nav-link" id="custom-tabs-one-makepromotion-tab" data-toggle="pill" href="#custom-tabs-one-makepromotion" role="tab" aria-controls="custom-tabs-one-makepromotion" aria-selected="false">Make Promotion</a>
          </li>
          <li class="nav-item">
            <a class="nav-link "id="custom-tabs-one-republish-tab" data-toggle="pill" href="#custom-tabs-one-republish" role="tab" aria-controls="custom-tabs-one-republish" aria-selected="true">RePublish</a>
          </li>
          <li class="nav-item">
            <a class="nav-link "id="custom-tabs-one-readreview-tab" data-toggle="pill" href="#custom-tabs-one-readreview" role="tab" aria-controls="custom-tabs-one-readreview" aria-selected="true">Read Review</a>
          </li>
        </ul>
      </div>
      <div class="card-body">
        <div class="tab-content" id="custom-tabs-one-tabContent">
          <div class="tab-pane fade active show" id="custom-tabs-one-needinvntory" role="tab" aria-controls="custom-tabs-one-needinvntory" role="tabpanel" aria-labelledby="custom-tabs-one-home-tab">
            <table id ="TableNeedInventory"
              data-show-export="true"
              data-pagination="true"
              data-side-pagination="server"
              data-click-to-select="true"
              data-toolbar="#toolbar"
              data-show-toggle="true"
              data-show-columns="true"
              data-search="true"
              data-show-refresh="true"
              data-show-fullscreen="true"
              data-show-columns-toggle-all="true"
              data-detail-view="true"
              data-minimum-count-columns="2"
              data-show-pagination-switch="true"
              data-pagination="true"
              data-page-list="[3, 6, 9, 12, all]"
              data-show-footer="true"
              data-response-handler="responseHandler">
            </table>
          </div>
          <div class="tab-pane fade" id="custom-tabs-one-downcost" role="tab" aria-controls="custom-tabs-one-downcost" role="tabpanel" aria-labelledby="custom-tabs-one-profile-tab">
            <table id ="TableDownCost"
              data-show-export="true"
              data-pagination="true"
              data-side-pagination="server"
              data-click-to-select="true"
              data-toolbar="#toolbar"
              data-show-toggle="true"
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
          <div class="tab-pane fade" id="custom-tabs-one-makepromotion" role="tab" aria-controls="custom-tabs-one-makepromotion" role="tabpanel" aria-labelledby="custom-tabs-one-messages-tab">
            <table id ="TablemakePromotion"
              data-show-export="true"
              data-pagination="true"
              data-side-pagination="server"
              data-click-to-select="true"
              data-toolbar="#toolbar"
              data-show-toggle="true"
              data-show-columns="true"
              data-search="true"
              data-show-refresh="true"
              data-show-fullscreen="true"
              data-show-columns-toggle-all="true"
              data-detail-view="true"
              data-minimum-count-columns="2"
              data-show-pagination-switch="true"
              data-pagination="true"
              data-pagination-pre-text="Previous"
              data-pagination-next-text="Next"
              data-page-list="[10, 25, 50, 100, all]"
              data-show-footer="true"
              data-response-handler="responseHandler">
          </table>
          </div>
          <div class="tab-pane fade  " id="custom-tabs-one-republish" role="tabpanel" aria-labelledby="custom-tabs-one-republish">
            <table id ="TableRePublish"
              data-show-export="true"
              data-pagination="true"
              data-side-pagination="server"
              data-click-to-select="true"
              data-toolbar="#toolbar"
              data-show-toggle="true"
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
          <div class="tab-pane fade  " id="custom-tabs-one-readreview" role="tabpanel" aria-labelledby="custom-tabs-one-readreview">
            <table id ="TableReadReview"
              data-show-export="true"
              data-pagination="true"
              data-side-pagination="server"
              data-click-to-select="true"
              data-toolbar="#toolbar"
              data-show-toggle="true"
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
         <table
            id="table"
            data-toggle="table"
            data-show-pagination-switch="true"
            data-pagination="true"
            data-url="json/data1.json">
            <thead>
              <tr>
                <th data-field="id">ID</th>
                <th data-field="name">Item Name</th>
                <th data-field="price">Item Price</th>
              </tr>
            </thead>
       </table>
        </div>
      </div>
      <div class="card-footer">
        <button type="submit" class="btn btn-primary">
          <i class="fa fa-magic" ></i>
        </button>
      </div>
      <!-- /.card -->
    </div>
  </div>
</div>
</form>
@endsection
@section('scripts')
<script>
var $tableNeedInventory = $('#TableNeedInventory')
var dsOutOfStocks = {!! json_encode($OutOfStocks) !!}

var $tableDownCost= $('#TableDownCost')
var dsDownCosts = {!! json_encode($dsDownCosts) !!}

var $TablemakePromotion= $('#TablemakePromotion')
var dsPromotions = {!! json_encode($dsPromotions) !!}

var $TableRePublish= $('#TableRePublish')
var dsUnpublishs = {!! json_encode($dsUnpublishs) !!}

var $TableReadReview= $('#TableReadReview')
var dsReadReviews = {!! json_encode($dsReadReviews) !!}

$(function() {

  $tableNeedInventory.bootstrapTable('destroy').bootstrapTable({
  exportDataType: $(this).val(),
  exportTypes: ['json', 'xml', 'csv', 'txt', 'sql', 'excel', 'pdf'],
  data:dsOutOfStocks,
  exportOptions: {
        fileName: 'Need Inventory',
        preventInjection: false
      },
      columns: [
        {field:'item_id',title:'item_id'},
        {field:'sku',title:'sku'},
        {field:'product_name',title:'product_name',formatter:linkName},
        {field:'cogs',title:'cogs'},
        {field:'cost',title:'cost'},
        {field:'price',title:'price'},
        {field:'avl_unit_in_wh',title:'avl_unit_in_wh'},
        {field:'avl_unit_on_wm',title:'avl_unit_on_wm'}
      ]
})

  $tableDownCost.bootstrapTable('destroy').bootstrapTable({
  exportDataType: $(this).val(),
  exportTypes: ['json', 'xml', 'csv', 'txt', 'sql', 'excel', 'pdf'],
  data:dsDownCosts,
  exportOptions: {
        fileName: 'Down Costs',
        preventInjection: false
      },
      columns: [
        {field:'sku',title:'sku'},
        {field:'wm_no',title:'wm_no'},
        {field:'item_id',title:'item_id'},
        {field:'product_name',title:'product_name',formatter:linkName},
        {field:'gtin',title:'gtin'},
        {field:'CurrentCost',title:'CurrentCost'},
        {field:'cost',title:'New cost'},
        {field:'price',title:'price'},
        {field:'avl_unit_in_wh',title:'avl_unit_in_wh'},
        {field:'avl_unit_on_wm',title:'avl_unit_on_wm'}
      ]
})
$TablemakePromotion.bootstrapTable('destroy').bootstrapTable({
  exportDataType: $(this).val(),
  exportTypes: ['json', 'xml', 'csv', 'txt', 'sql', 'excel', 'pdf'],
  data:dsPromotions,
  exportOptions: {
        fileName: 'Make Promotion',
        preventInjection: false
      },
      columns: [
        {field:'Vendor',title:'Vendor'},
        {field:'SupplierNumber',title:'SupplierNumber'},
        {field:'item_id',title:'item_id'},
        {field:'product_name',title:'product_name',formatter:linkName},
        {field:'Campaign',title:'Campaign'},
        {field:'StartDate',title:'StartDate'},
        {field:'EnDate',title:'EnDate'},
        {field:'NormalCost',title:'NormalCost'},
        {field:'RollbackCost',title:'RollbackCost'},
        {field:'NormalRetail',title:'NormalRetail'},
        {field:'RollbackRetail',title:'RollbackRetail'},
        {field:'FundingperUnit',title:'FundingperUnit'}
      ]
})

$TableRePublish.bootstrapTable('destroy').bootstrapTable({
  exportDataType: $(this).val(),
  exportTypes: ['json', 'xml', 'csv', 'txt', 'sql', 'excel', 'pdf'],
  data:dsUnpublishs,
  exportOptions: {
        fileName: 'RePublish',
        preventInjection: false
      },
      columns: [
        {field:'sku',title:'sku'},
        {field:'wm_no',title:'wm_no'},
        {field:'item_id',title:'item_id'},
        {field:'product_name',title:'product_name',formatter:linkName},
        {field:'gtin',title:'gtin'},
        {field:'cogs',title:'cogs'},
        {field:'CurrentCost',title:'CurrentCost'},
        {field:'new_cost',title:'New Cost'}
      ]
})

$TableReadReview.bootstrapTable('destroy').bootstrapTable({
  exportDataType: $(this).val(),
  exportTypes: ['json', 'xml', 'csv', 'txt', 'sql', 'excel', 'pdf'],
  data:dsReadReviews ,
  exportOptions: {
        fileName: 'RePublish',
        preventInjection: false
      },
      columns: [
        {field:'sku',title:'sku'},
        {field:'wm_no',title:'wm_no'},
        {field:'item_id',title:'item_id'},
        {field:'avg_rating',title:'avg_rating'},
        {field:'product_name',title:'product_name',formatter:linkName}
      ]
})

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



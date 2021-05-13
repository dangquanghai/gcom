@extends('layouts.admin')
@section('content')
  <div class="container-fluid">
    <!-- Control the column width, and how they should appear on different devices -->
      <div class="card-body">
        <div id="div1" class="row">
            <div class="col-lg-12" style="background-color:white;">
                <table id ="table"
                    data-show-export ="true"
                    data-pagination = "true"
                    data-side-pagination ="server"
                    data-click-to-select ="true"
                    data-toolbar ="#toolbar"
                    data-show-toggle ="true"
                    data-show-columns ="true">
                </table>

                <div id="toolbar" class ="select">
                    <select class ="form-control">
                      <option value ="">Export Basic</option>
                      <option value ="all">Export All</option>
                      <option value ="selected">Export Selected</option>
                    </select>
                </div>
            </div>
        </div>
      </div>
  </div>
@endsection
@section('styles')
<style>
</style>
@endsection
@section('scripts')
<script>

//======================================================
 var ds = {!! json_encode($dsPromotionAndSem)!!};
 var ColWitdh = 85;
 var $table = $('#table');

$(function() {
  $('#toolbar').find('select').change(function () {
    $table.bootstrapTable('destroy').bootstrapTable({
      exportDataType: $(this).val(),
      exportTypes: ['json', 'xml', 'csv', 'txt', 'sql', 'excel', 'pdf'],
      data:ds,
      columns:[
        {
          field: 'state',
          checkbox: true,
          visible: $(this).val() === 'selected'
        },
        { field: 'sku', title: 'SKU' },
        { field: 'promotion',title: 'promotion'},
        { field: 'sem',title: 'sem'}
      ]
    })
  }).trigger('change')
})
</script>
@endsection

@extends('layouts.admin')
@section('content')
<form action="" >
@csrf
<div class ="row">
  
  <div class="col-md-10" style="padding-left:0px">
      <div class="card card-primary">
              <div class="card-header">
              <div class="card-tools">
                  <a href="{{route('Promotion.create')}}"><i class="fa fa-plus-square" id="btnCreate"></i></a>
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
var ds = {!! json_encode($data) !!};


$(function() {
  $('#toolbar').find('select').change(function () {
    $table.bootstrapTable('destroy').bootstrapTable({
      height: 850,
      exportDataType: $(this).val(),
      exportTypes: ['json', 'xml', 'csv', 'txt', 'sql', 'excel', 'pdf'],
      data:ds,
      exportOptions: {
            fileName: 'Zalo Friends',
            preventInjection: false
          },
      columns: 
      [
        {field:'name',title:'name'},
        {field:'id',title:'Edit'}
      ]
    })
  }).trigger('change')
})
</script>
@endsection
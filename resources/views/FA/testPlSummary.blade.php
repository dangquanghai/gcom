@extends('layouts.admin')
@section('content')

<div class="container-fluid">
    <!-- Control the column width, and how they should appear on different devices -->
<div class="card-body">
    <form action="{{route('fa.PlReport.Summary')}}" method="POST" >
        @csrf
        <div id="div1" class="row">
            <div class="col-lg-1" style="background-color:whitesmoke;">
                <h5>Profit Report</h5>
                <div class="form-group">
                           Year:
                            <input type="text" class="form-control"  width= "150px"   name="year"   min="2020" max="2050"   value="{{old('year')}}" required>
                           Month:
                            <input type="number" class="form-control"  name="month" min="1" max="12" value="{{old('month')}}" required>
                        <button type="submit" class="btn btn-success">View</button>
                </div>
            </div>
            <div class="col-lg-11"  style ="background-color:whitesmoke;">

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

//-------------------------
$(function() {
  $('#toolbar').find('select').change(function () {
    $table.bootstrapTable('destroy').bootstrapTable({
      exportDataType: $(this).val(),
      exportTypes: ['json', 'xml', 'csv', 'txt', 'sql', 'excel', 'pdf'],
      data:ds,
      columns: [
        {
          field: 'state',
          checkbox: true,
          visible: $(this).val() === 'selected'
        },
        {field:'name',title:'Channel Name'},
        {title: "line_name", field: "line_name" },
        {title: "line_des", field: "line_des",footerFormatter: totalTextFormatter},
        {title: "des", field: "des"},
        {title: "amount", field: "amount", footerFormatter: totalAmountFormatter},
        {title: "balance", field: "balance" },
        {title: "ratio", field: "ratio"}
      ]
    })
  }).trigger('change')
})
function totalTextFormatter(data) {
     return 'Total'
  }

function totalAmountFormatter(data) {
    var field = this.field
    return  data.map(function (row) {
      return +row[field].substring(1)
    }).reduce(function (sum, i) {
      return sum + i
    }, 0)
  }

</script>
@endsection


@extends('layouts.admin')
@section('content')

<div class="container-fluid">
    <!-- Control the column width, and how they should appear on different devices -->
<div class="card-body">
    <form action="{{route('fa.summary')}}" method="POST" >
        @csrf
        <div id="div1" class="row">
            <div class="col-lg-1" style="background-color:whitesmoke;">
                <h5>Profit Report</h5>
                <div class="form-group">
                           Year:
                            <input type="text" class="form-control"  width= "150px"   name="year"   min="2020" max="2050"   value="{{old('year')}}" required>
                           Month:
                            <input type="number" class="form-control"  name="month" min="1" max="12" value="{{old('month')}}" required>
                           Channel:
                            <select id="channel" name="channel" class="form-control"   value="{{old('channel')}}" >
                                <option value="0">All</option>
                                <option value="2">AVC DS</option>
                                <option value="4">WM DSV</option>
                                <option value="5">WM MKP</option>
                                <option value="6">EBAY</option>
                                <option value="7">Craiglist</option>
                                <option value="8">Website</option>
                                <option value="7">Craiglist</option>
                                <option value="9">FBA</option>
                                <option value="10">FBM</option>
                                <option value="12">Wayfair</option>
                            </select>
                            Store:
                            <select id="store"   name ="store"  class = "form-control" value="{{old('store')}}">
                                <option value="0">All</option>
                                <option value="1">Ebay Fitness</option>
                                <option value="2">Ebay Inc</option>
                                <option value="3">Ebay Infideals</option>
                                <option value="4">Ebay Infideals</option>
                            </select>

                            sku:
                            <input id="sku" name = "sku" class="form-control" value="{{old('sku')}}">
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
                    data-show-columns="true">
                    <thead>
                      <tr>
                        <th data-field="sku" data-width="300">SKU</th>
                        <th data-field="title" data-width="900">title</th>
                        <th data-field="channel">Channel</th>

                        <th data-field="sell_quantity" data-width="300">sell_quantity</th>
                        <th data-field="return_quantity" data-width="900">return_quantity</th>
                        <th data-field="revenue">revenue</th>
                        <th data-field="refund" data-width="300">refund</th>
                        <th data-field="nest_sales" data-width="900">nest_sales</th>
                        <th data-field="cogs">cogs</th>
                        <th data-field="promotion" data-width="300">promotion</th>
                        <th data-field="seo_sem" data-width="900">seo_sem</th>
                        <th data-field="shiping_fee">shiping_fee</th>
                        <th data-field="OtherExpensive" data-width="300">OtherExpensive</th>
                        <th data-field="dip" data-width="900">dip</th>
                        <th data-field="msf">msf</th>
                        <th data-field="selling_fees" data-width="300">selling_fees</th>
                        <th data-field="fullfillment" data-width="900">fullfillment</th>
                        <th data-field="chargeback">chargeback</th>
                        <th data-field="coop" data-width="300">coop</th>
                        <th data-field="freight_handling_return_cost" data-width="900">freight_handling_return_cost</th>
                        <th data-field="ebay_final_fee">ebay_final_fee</th>
                        <th data-field="paypal_fee" data-width="900">paypal_fee</th>

                        <th data-field="discount" data-width="900">discount</th>
                        <th data-field="clip_fee" data-width="900">clip_fee</th>
                        <th data-field="liability_insurance" data-width="900">liability_insurance</th>
                        <th data-field="commission" data-width="900">commission</th>
                        <th data-field="vine" data-width="900">vine</th>
                        <th data-field="other_fee" data-width="900">other_fee</th>
                        <th data-field="profit" data-width="900">profit</th>

                      </tr>
                    </thead>
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
var ds = {!! json_encode($dsSummary) !!};

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
        { field:'sku', title: 'SKU' },
        { field:'title',title:'Product Name', width: "250px"},
        { field:'channel',title: 'Channel'},
        { field:'sell_quantity',title: 'Quantity'},
        {title: "return", field: "return_quantity" },
        {title: "revenue", field: "revenue"},
        {title: "refund", field: "refund"},
        {title: "net", field: "nest_sales" },
        {title: "cogs", field: "cogs"},
        {title: "promotion", field: "promotion" },
        {title: "seo", field: "seo_sem" },
        {title: "shiping_fee", field: "shiping_fee"},
        {title: "OtherExpensive", field: "other_selling_expensives" },
        {title: "dip", field: "dip" },
        {title: "msf", field: "msf" },
        {title: "s.fees/Referal", field: "selling_fees" },
        {title: "fullfillment", field: "fullfillment"  },
        {title: "Chargeback", field: "chargeback"  },
        {title: "coop", field: "coop"   },
        {title: "freight cost", field: "freight_cost"  },
        {title: "FHReturnCost", field: "freight_handling_return_cost"   },
        {title: "ebay final fee", field: "ebay_final_fee" },
        {title: "paypal Fee", field: "paypal_fee"  },
        {title: "discount", field: "discount" },
        {title: "clip", field: "clip_fee" },
        {title: "Insurance", field: "liability_insurance" },
        {title: "commission", field: "commission" },
        {title: "vine", field: "vine" },
        {title: "Other Fee", field: "other_fee" },
        {title: "profit", field: "profit" }
      ]
    })
  }).trigger('change')

})
</script>
@endsection


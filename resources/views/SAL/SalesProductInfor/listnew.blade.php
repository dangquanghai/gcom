@extends('layouts.admin')
@section('content')
<form role="form">
<div class ="row">
  <div class="col-md-1" style="padding-right:0px">
    <!-- general form elements -->
    <div class="card card-primary">
      <div class="card-header">
        <h3 class="card-title">Search</h3>
      </div>
      <!-- /.card-header -->
      <!-- form start -->
      <form role="form">
        <div class="card-body">
          <div class="form-group">
            <label for="sku">SKU</label>
            <input type="text" class="form-control" id="sku" name="sku" value="{{$Sku}}" >
          </div>
          <div class="form-group">
            <label>Title</label>
            <input type="text" id ="title" name ="title" class="form-control" value="{{$Title}}">
          </div>
          <div class="form-group">
            <label for="product_group">Brand</label>
            <select class="form-control" name="brand" id="brand" >
              {!! getList($Brands,$Brand) !!}
          </select>
          </div>
        </div>
        <!-- /.card-body -->
        <div class="card-footer">
          <button type="submit" class="btn btn-primary">
            <i class="fa fa-search"></i>
          </button>
        </div>
      </form>
    </div>
    <!-- /.card -->
    <!-- /.card-body -->
  </div>
    <!-- /.card -->
    <div class="col-md-11" style="padding-left:0px">
    <div class="card card-primary card-tabs">

<div class="card-header p-0 pt-1">
  <ul class="nav nav-tabs" id="custom-tabs-one-tab" role="tablist">
    <li class="nav-item">
      <a class="nav-link active" id="custom-tabs-one-salesproductinfor-tab" data-toggle="pill" href="#custom-tabs-one-salesproductinfor" role="tab" aria-controls="custom-tabs-one-salesproductinfor" aria-selected="false">sales product informations</a>
    </li>
    <li class="nav-item">
      <a class="nav-link" id="custom-tabs-one-asin-tab" data-toggle="pill" href="#custom-tabs-one-asin" role="tab" aria-controls="custom-tabs-one-asin" aria-selected="false">Asin Management</a>
    </li>
    <li class="nav-item">
      <a class="nav-link" id="custom-tabs-one-promotiontracking-tab" data-toggle="pill" href="#custom-tabs-one-mpromotiontracking-" role="tab" aria-controls="custom-tabs-one-makepromotion" aria-selected="false">Promotion tracking</a>
    </li>
  </ul>
</div>   <!-- /.card-header -->

<div class="card-body">
      <div class="tab-content" id="custom-tabs-one-tabContent">
      
        <div class="tab-pane fade active show" id="custom-tabs-one-salesproductinfor" role="tab" aria-controls="custom-tabs-one-salesproductinfor" role="tabpanel" aria-labelledby="custom-tabs-one-home-tab">
              <div id="MyToolbar"> </div>  
              <table id ="table" class="table table-head-hover "
                data-show-export="true"
                data-toolbar="#MyToolbar">
              </table>
        </div>

        <div class="tab-pane fade " id="custom-tabs-one-asin" role="tab" aria-controls="custom-tabs-one-asin" role="tabpanel" aria-labelledby="custom-tabs-one-home-tab">
              <div id="MyToolbarAsin"> </div>  
              <table id ="tableAsin" class="table table-head-hover "
                data-show-export="true"
                data-toolbar="#MyToolbarAsin">
              </table>
        </div>
      
    </div>

</div>

<div class="card-footer">
      <button type="submit" class="btn btn-primary">
      <i class="fa fa-magic" ></i>
    </button>
  </div>

</div>
    </div>
  </div>
</form>
@endsection
@section('scripts')
<script>
var $table = $('#table')
var $tableAsin = $('#tableAsin')
var ds = {!! json_encode($SalesProductInfors) !!}
var dsAsin = {!! json_encode($Asins) !!}

var sAsinColums =[
  {field:'id',title:'id',visible:false},
  {field:'title',title:'Product Name ---------'},
  {field:'sku',title:'sku'},
  {field:'amz_asin',title:'amz_asin',formatter:LinkToAmazonListing},

  {field:'ebay_infidealz',title:'ebay_infidealz',formatter:LinkToEbayInfListing},
  {field:'ebay_inc',title:'ebay_inc',formatter:LinkToEbayIncListing},
  {field:'ebay_fitness',title:'ebay_fitness',formatter:LinkToEbayFitnessListing},
  {field:'wm_item_id',title:'wm_item_id',formatter:LinkToWMListing},

  {field:'wayfair_asin',title:'wayfair_asin'},
  {field:'local',title:'local'},
  {field:'di',title:'DI ---- '}
];

var sColums =
        [
         [
          {title:'Product',colspan:4},
          {title:'Packaging Dimension',colspan:5,align: 'center'},
          {title:'Terms of Payment',colspan:3,align: 'center'},
          {title:'Logistic',colspan:5,align: 'center'},
          {title:'COGS',align:'center',valign: 'middle'},

          {title:'',colspan:6,align: 'center'},

          {title:'Operation Fees',colspan:2,align: 'center'},
          {title:'Total Cost',align: 'center',valign: 'middle'},

          {title:'GP (Wholesales)',colspan:2,align: 'center'},

          {title:'Retail Shipping',align: 'center',valign: 'middle'},
          {title:'%GP(Retail)',align: 'center',valign: 'middle'},

          {title:'Price',colspan:6,align: 'center'},
          {title:'Channel Fee(%)',colspan:9,align: 'center'},
          {title:'Channel Fee ($)',colspan:9,align: 'center'},
          {title:'Retail Price',colspan:9,align: 'center',visible:false},
          {title:'Cost Price ($)',colspan:9,align: 'center',visible:false},
          {title:'Unit Profit',colspan:10,align: 'center',visible:false}
        ],
        [
          {field:'id',title:'id',visible:false},
          {field:'title',title:'Product Name ------------------------------------',formatter:OpenSalesProductDetail},
          {field:'sku',title:'sku'},
          {field:'brand_name',title:'Brand Name'},
          // paking
          {field: "length", title: "Length"},
          {field: "width", title: "Width"},
          {field: "height", title: "Height"},
          {field: "weight", title: "Weight"},
          {field: "cubic", title: "Cubic"},

          // Tẻm payment
          {field: "per_deposit", title: "%deposit"},
          {field: "per_full_payment", title: "% full payment"},
          {field: "partner_split", title: "% partner_split"},

          // Logistic
          {field: "con20_capacity", title: "con20_capacity"},
          {field: "exw_vn", title: "exw_vn"},
          {field: "fob_vn", title: "fob_vn"},
          {field: "fob_cn", title: "fob_cn"},
          {field: "fob_us", title: "fob_us"},

          {field: "cosg_est", title: "COSG"},

          {field: "per_mkt", title: "% MKT",visible:false},
          {field: "per_promotion", title: "% Promotion",visible:false},
          {field: "per_return", title: "% Return",visible:false},
          {field: "selling_invoice", title: "% Selling Invoice",visible:false},
          {field: "per_duty", title: "% Duty",visible:false},
          {field: "sales_commision", title: "% Sales Commision",visible:false},

          {field: "per_wh_fee", title: "% wh_fee",visible:false},
          {field: "per_handing_fee", title: "%Handing_fee",visible:false},

          {field: "per_total_cost", title: "%Total Cost"},

          {field: "per_wholesales_gp_min", title: "% wholesales_gp_min"},
          {field: "per_wholesales_gp_max", title: "% wholesales_gp_max"},

          {field: "shiping_fee_est", title: "shiping_fee_est"},

          {field: "per_retail_profit", title: "% Retail_profit"},

          // price
          {field: "per_wholesales_price_min", title: "% per_wholesales_price_min"},
          {field: "per_wholesales_price_max", title: "% per_wholesales_price_max"},
          {field: "dsv_shipping_fee", title: "dsv_shipping_fee"},
          {field: "price_profit_min", title: "price_profit_min"},
          {field: "price_profit_max", title: "price_profit_max"},
          {field: "retail_price", title: "retail_price"},

          {field: "per_fbm_fee", title: "% per_fbm_fee",visible:false},
          {field: "per_avcds_fee", title: "% per_avcds_fee",visible:false},
          {field: "per_avcwh_fee", title: "% avcwh_fee",visible:false},
          {field: "per_wmdsv_fee", title: "% wmdsv_fee",visible:false},
          {field: "per_wmmkp_fee", title: "%wmmkp_fee",visible:false},
          {field: "per_ebay_fee", title: "%ebay_fee",visible:false},
          {field: "per_local_fee", title: "%local_fee",visible:false},
          {field: "per_website_fee", title: "%website_fee",visible:false},
          {field: "per_way_fee", title: "%wayfair_fee",visible:false},

          {field: "fbm_fee", title: "fbm_fee"},
          {field: "avcds_fee", title: "avcds_fee"},
          {field: "avcwh_fee", title: "avcwh_fee"},
          {field: "wmdsv_fee", title: "wmdsv_fee"},
          {field: "wmmkp_fee", title: "wmmkp_fee"},
          {field: "ebay_fee", title: "ebay_fee"},
          {field: "local_fee", title: "local_fee"},
          {field: "website_fee", title: "website_fee"},
          {field: "way_fee", title: "way_fee"},

          {field: "fbm_retail_price", title: "fbm_retail_price"},
          {field: "avcds_retail_price", title: "avcds_retail_price"},
          {field: "avcwh_retail_price", title: "avcwh_retail_price_fee",visible:false},
          {field: "wmdsv_retail_price", title: "wmdsv_retail_price"},
          {field: "wmmkp_retail_price", title: "wmmkp_retail_price"},
          {field: "ebay_retail_price", title: "ebay_retail_price"},
          {field: "local_retail_price", title: "local_retail_price"},
          {field: "website_retail_price", title: "website_retail_price"},
          {field: "wayfair_retail_price", title: "wayfair_retail_price",visible:false},

          {field: "fbm_cost", title: "fbm_cost",visible:false},
          {field: "avcds_cost", title: "avcds_cost"},
          {field: "avcwh_cost", title: "avcwh_cost"},
          {field: "wmdsv_cost", title: "wmdsv_cost"},
          {field: "wmmkp_cost", title: "wmmkp_cost",visible:false},
          {field: "ebay_cost", title: "ebay_cost",visible:false},
          {field: "local_cost", title: "local_cost",visible:false},
          {field: "website_cost", title: "website_cost",visible:false},
          {field: "wayfair_cost", title: "wayfair_cost",visible:false},

          {field: "fbm_profit", title: "fbm_profit"},
          {field: "avcds_profit", title: "avcds_profit"},
          {field: "avcwh_profit", title: "avcwh_profit"},
          {field: "wmdsv_profit", title: "wmdsv_profit"},
          {field: "wmmkp_profit", title: "wmmkp_profit"},
          {field: "ebay_profit", title: "ebay_profit"},
          {field: "local_profit", title: "local_profit"},
          {field: "website_profit", title: "website_profit"},
          {field: "wayfair_profit", title: "wayfair_profit"},
          {field: "fba_profit", title: "fba_profit"},
          
        ]
      ] ;

  $table.bootstrapTable('destroy').bootstrapTable
    ({
      height: 900,
      columns: sColums,
      data: ds,
      toolbar:'.toolbar',
      exportOptions: {
            fileName: 'Sales Product Information',
            preventInjection: false
          },
      showColumns: true,
      showToggle: true,
      clickToSelect: true,
      fixedColumns: true,
      fixedNumber: 2
    })

    $tableAsin.bootstrapTable('destroy').bootstrapTable
    ({
      height: 900,
      columns: sAsinColums,
      data: dsAsin,
      toolbar:'.MyToolbarAsin',
      exportOptions: {
            fileName: 'Sales Product Information',
            preventInjection: false
          },
      showColumns: true,
      showToggle: true
    })

function OpenSalesProductDetail(value,row,index)
{
  let url = "{{route('SalesProductInforController.edit', ':id') }}";
  url = url.replace(':id', row.id);
  url = '<a href="' + url + '">' + value +  '</a>';
  
  return[
    url
  ].join('')
}

function LinkToAmazonListing(value,row,index)
{
  return[
    '<a target="_blank" href="https://www.amazon.com/dp/'+row.amz_asin+'">',
    value,
    '</a>'
  ].join('')
}

function LinkToEbayInfListing(value,row,index)
{
  return[
    '<a target="_blank" href="https://www.ebay.com/itm/'+row.ebay_infidealz+'">',
    value,
    '</a>'
  ].join('')
}

function LinkToEbayIncListing(value,row,index)
{
  return[
    '<a target="_blank" href="https://www.ebay.com/itm/'+row.ebay_inc+'">',
    value,
    '</a>'
  ].join('')
}

function LinkToEbayFitnessListing(value,row,index)
{
  return[
    '<a target="_blank" href="https://www.ebay.com/itm/'+row.ebay_fitness+'">',
    value,
    '</a>'
  ].join('')
}



function LinkToWMListing(value,row,index)
{
  return[
    '<a target="_blank" href="https://www.walmart.com/ip/'+row.wm_item_id+'">',
    value,
    '</a>'
  ].join('')
}

function SetColorForFBMProfit(value,row,index)
{
    return {
        classes: value < 0 ? 'negative_return' : 'ok_return'
    }
}

</script>
@endsection
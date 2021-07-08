@extends('layouts.admin')
@section('content')
<form role="form">
<div class ="row">
  <div class="col-md-2" style="padding-right:0px">
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
    <div class="col-md-10" style="padding-left:0px">
    <div class="card card-primary card-tabs">
    

<div class="card-header p-0 pt-1">
  <ul class="nav nav-tabs" id="custom-tabs-one-tab" role="tablist">
    <li class="nav-item">
      <a class="nav-link active" id="custom-tabs-one-salesproductinfor-tab" data-toggle="pill" href="#custom-tabs-one-salesproductinfor" role="tab" aria-controls="custom-tabs-one-salesproductinfor" aria-selected="false">sales product informations</a>
    </li>
    <li class="nav-item">
      <a class="nav-link" id="custom-tabs-one-asin-tab" data-toggle="pill" href="#custom-tabs-one-asin" role="tab" aria-controls="custom-tabs-one-asin" aria-selected="false">Asin Management </a>
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
    <div class="card-tools">
        <a href="{{route('SalesProductInforController.create')}}"id="btnCreate"> <i class="fa fa-plus-square"></i></a>
    </div>
</div>

</div>
    </div>
  </div>
</form>
@endsection

@section('style')
<style>
  div.card-tools {
    float: right;
  }
  </style>
@endsection


@section('scripts')
<script>


var ThePermission = {!! json_encode($sPermission) !!};
var IsAdmin = {!! json_encode($IsAdmin) !!};
var CanViewDetail = 1;
var CanEdit=1;
// ---------------------------------------------------------------------------------------
function SetPermission(Permission)
{
  if(IsAdmin !=1)
  {
    let myElement = document.querySelector("#btnCreate");
    var sPermission ='';

    Permission.forEach(item=>
    {
        // res.push(item.action_no);
      if(sPermission=='')
        sPermission = item.action_no;
      else
        sPermission =  sPermission + ','+ item.action_no;   
    });
        
    if(sPermission.indexOf("2")== -1)// Nếu không tìm thấy action số 2 thì không cho xem chi tiết
      CanViewDetail = 0;
    if(sPermission.indexOf("3")== -1)// Nếu không tìm thấy action số 3 thì ẩn nút thêm mới
      myElement.style="display:none;";
    if(sPermission.indexOf("4")== -1)// Nếu không tìm thấy action số 4 thì không cho edit
      CanEdit = 0; 
  }
}
// ---------------------------------------------------------------------------------------
SetPermission(ThePermission);

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
  {field:'wayfair_asin',title:'wayfair_asin'}
];

var sColums =
        [
         [
          {title:'Product',colspan:4},
          {title:'Packaging Dimension',colspan:5,align: 'center'},
          {title:'Terms of Payment',colspan:3,align: 'center'},
          {title:'Logistic',colspan:5,align: 'center'},
          {title:'COGS',align:'center',valign: 'middle'},

          {title:'Sales Fees',colspan:6,align: 'center'},

          {title:'Operation Fees',colspan:2,align: 'center'},
          {title:'Total Cost',align: 'center',valign: 'middle'},

          {title:'GP (Wholesales)',colspan:2,align: 'center'},

          {title:'Shipping Fee',colspan:2, align: 'center',valign: 'middle'},
          {title:'%GP(Retail)',align: 'center',valign: 'middle'},

          {title:'Price',colspan:6,align: 'center'},
          {title:'Channel Fee(%)',colspan:11,align: 'center'},
          {title:'Channel Fee ($)',colspan:11,align: 'center'},
          {title:'Retail Price',colspan:11,align: 'center',visible:false},
          {title:'Cost Price ($)',colspan:11,align: 'center',visible:false},
          {title:'Unit Profit',colspan:11,align: 'center',visible:false}
        ],
        [
         
          {field:'id',title:'edit',formatter:EditSalesProductDetail},
          {field:'title',title:'Product Name ------------------------------------',formatter:ShowSalesProductDetail},
          {field:'sku',title:'sku'},
          {field:'brand_name',title:'Brand Name'},
          // paking
          {field: "the_length", title: "Length"},
          {field: "the_width", title: "Width"},
          {field: "the_height", title: "Height"},
          {field: "the_weight", title: "Weight"},
          {field: "cubic", title: "Cubic"},

          // Tẻm payment
          {field: "per_deposit", title: "%deposit"},
          {field: "per_full_payment", title: "% full payment"},
          {field: "per_rev_split_for_partner", title: "% partner_split"},

          // Logistic
          {field: "con20_capacity", title: "con20_capacity"},
          {field: "exw_vn", title: "exw_vn"},
          {field: "fob_vn", title: "fob_vn"},
          {field: "fob_cn", title: "fob_cn"},
          {field: "fob_us", title: "fob_us"},

          {field: "cosg_est", title: "COSG"},

          {field: "per_mkt", title: "% MKT"},
          {field: "per_promotion", title: "% Promotion"},
          {field: "per_return", title: "% Return"},
          {field: "selling_invoice", title: "% Selling Invoice"},
          {field: "per_duty", title: "% Duty"},
          {field: "sales_commision", title: "% Sales Commision"},

          {field: "per_wh_fee", title: "% wh_fee"},
          {field: "per_handing_fee", title: "%Handing_fee"},

          {field: "per_total_cost", title: "%Total Cost"},

          {field: "per_wholesales_gp_min", title: "% wholesales_gp_min"},
          {field: "per_wholesales_gp_max", title: "% wholesales_gp_max"},

          {field: "shiping_fee_est", title: "shiping_fee_est"},
          {field: "fba_shipping_est", title: "fba_shipping_est"},

          {field: "per_retail_profit", title: "% Retail_profit"},

          // price
          {field: "per_wholesales_price_min", title: "% per_wholesales_price_min"},
          {field: "per_wholesales_price_max", title: "% per_wholesales_price_max"},
          {field: "dsv_shipping_fee", title: "dsv_shipping_fee"},
          {field: "price_profit_min", title: "price_profit_min"},
          {field: "price_profit_max", title: "price_profit_max"},
          {field: "retail_price", title: "retail_price"},

          {field: "per_avcwh_fee", title: "% avcwh_fee"},
          {field: "per_avcds_fee", title: "% per_avcds_fee"},
          {field: "per_avcdi_fee", title: "% per_avcdi_fee"},
          {field: "per_wmdsv_fee", title: "% wmdsv_fee"},
          {field: "per_wmmkp_fee", title: "%wmmkp_fee"},
          {field: "per_ebay_fee", title: "%ebay_fee"},
          {field: "per_local_fee", title: "%local_fee"},
          {field: "per_website_fee", title: "%website_fee"},
          {field: "per_fba_fee", title: "% per_fba_fee"},
          {field: "per_fbm_fee", title: "% per_fbm_fee"},
          {field: "per_way_fee", title: "%wayfair_fee"},

          
          {field: "avcwh_fee", title: "avcwh_fee"},
          {field: "avcds_fee", title: "avcds_fee"},
          {field: "avcdi_fee", title: "avcdi_fee"},
          {field: "wmdsv_fee", title: "wmdsv_fee"},
          {field: "wmmkp_fee", title: "wmmkp_fee"},
          {field: "ebay_fee", title: "ebay_fee"},
          {field: "local_fee", title: "local_fee"},
          {field: "website_fee", title: "website_fee"},
          {field: "fba_fee", title: "fba_fee"},
          {field: "fbm_fee", title: "fbm_fee"},
          {field: "way_fee", title: "way_fee"},

          
          {field: "avcwh_retail_price", title: "avcwh_retail_price_fee"},
          {field: "avcds_retail_price", title: "avcds_retail_price"},
          {field: "avcdi_retail_price", title: "avcdi_retail_price_fee"},
          {field: "wmdsv_retail_price", title: "wmdsv_retail_price"},
          {field: "wmmkp_retail_price", title: "wmmkp_retail_price"},
          {field: "ebay_retail_price", title: "ebay_retail_price"},
          {field: "local_retail_price", title: "local_retail_price"},
          {field: "website_retail_price", title: "website_retail_price"},
          {field: "fba_retail_price", title: "fba_retail_price"},
          {field: "fbm_retail_price", title: "fbm_retail_price"},
          {field: "wayfair_retail_price", title: "wayfair_retail_price"},

          {field: "avcwh_cost", title: "avcwh_cost"},
          {field: "avcds_cost", title: "avcds_cost"},
          {field: "avcdi_cost", title: "avcdi_cost"},
          {field: "wmdsv_cost", title: "wmdsv_cost"},
          {field: "wmmkp_cost", title: "wmmkp_cost"},
          {field: "ebay_cost", title: "ebay_cost"},
          {field: "local_cost", title: "local_cost"},
          {field: "website_cost", title: "website_cost"},
          {field: "fba_cost", title: "fba_cost"},
          {field: "fbm_cost", title: "fbm_cost"},
          {field: "wayfair_cost", title: "wayfair_cost"},

          {field: "avcwh_profit", title: "avcwh_profit",formatter:colorFormatter},
          {field: "avcds_profit", title: "avcds_profit",formatter:colorFormatter},
          {field: "avcdi_profit", title: "avcdi_profit",formatter:colorFormatter},
          {field: "wmdsv_profit", title: "wmdsv_profit",formatter:colorFormatter},
          {field: "wmmkp_profit", title: "wmmkp_profit",formatter:colorFormatter},
          {field: "ebay_profit", title: "ebay_profit",formatter:colorFormatter},
          {field: "local_profit", title: "local_profit",formatter:colorFormatter},
          {field: "website_profit", title: "website_profit",formatter:colorFormatter},
          {field: "fba_profit", title: "fba_profit",formatter:colorFormatter},
          {field: "fbm_profit", title: "fbm_profit",formatter:colorFormatter},
          {field: "wayfair_profit", title: "wayfair_profit",formatter:colorFormatter}
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
// ------------------------------------------------    
function ShowSalesProductDetail(value,row,index)
{
 let url = "{{route('SalesProductInforController.show', ':id') }}";
 if(CanViewDetail ==1 )
 {
    url = url.replace(':id', row.id);
    url = '<a href="' + url + '">' + value +  '</a>';
 }else
 {
  url =value;
 } 
  return[
      url
    ].join('')
}
// ------------------------------------------------  
function EditSalesProductDetail(value,row,index)
{
 let url = "{{route('SalesProductInforController.edit', ':id') }}";
 if(CanEdit ==1 )
 {
    url = url.replace(':id', row.id);
    url = '<a href="' + url + '">' + value +  '</a>';
 }else
 {
  url =value;
 } 
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

function colorFormatter(value, row, index) {
    if (parseFloat(value) < 0 ) {
      return '<div style="background-color:red" >' + value + '</div>';            
    } else {
      return '<div style="background-color:while" >' + value + '</div>'; 
    }
  }

</script>
@endsection
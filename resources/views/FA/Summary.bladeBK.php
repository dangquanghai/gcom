@extends('inc.master')
@section('content')
<div class="container-fluid">
    <!-- Control the column width, and how they should appear on different devices -->
<div class="card-body">
    <form action="{{ route('LoadFASummary') }}" method="POST" >
        @csrf
        <div id="div1" class="row">
            <div class="col-lg-2" style="background-color:whitesmoke;">
                <h5>Profit Report</h5>
                <div class="form-group">
                 Year    <input type="text" class="form-control"  width= "150px"   name="year"   min="2020" max="2050"   value="{{old('year')}}" required>
                 Month   <input type="number" class="form-control"  name="month" min="1" max="12" value="{{old('month')}}" required>
                 Channel <input id="channel" name="channel" class="form-control" value="{{old('channel')}}"  >
                 Store   <input id="store" name = "store" class="form-control" value="{{old('store')}} "   >
                 sku     <input id="sku" name = "sku" class="form-control" value="{{old('sku')}}"   >
                </div>
                <button type="submit" class="btn btn-success">View</button>
            </div>
            <div class="col-lg-10" style="background-color:white;">
                <div id="example">
                    <div id="grid"></div>
                </div>
            </div>
        </div>
    </form>
</div>
</div>
@endsection
@section('styles')
<style>
input[type=number]
{
    width: 175px;
}
div.form-group{
    width: 176px;
}
</style>
@endsection

@section('scripts')
<script>
$(document).ready(function() {
    $("#menu").kendoMenu();

    var dsChannels = [
    { text: "None", value: "0" },
    { text: "AVC-WH", value: "1" },
    { text: "AVC-DS", value: "2" },
    { text: "AVC-DI", value: "3" },
    { text: "WM-DSV", value: "4" },
    { text: "WM-MKP", value: "5" },
    { text: "EBAY", value: "6" },
    { text: "Craig/Local", value: "7" },
    { text: "Website", value: "8" },
    { text: "FBA", value: "9" },
    { text: "FBM", value: "10" }
    ];

 var dsStores = [
    { text: "None", value: "0" },
    { text: "Ebay Fitness", value: "1" },
    { text: "Ebay Inc", value: "2" },
    { text: "Ebay Infideals", value: "3" },
    { text: "Ebay Idzo", value: "4" }
    ];


    $("#channel").kendoDropDownList({
    dataTextField: "text",
    dataValueField: "value",
    dataSource: dsChannels,
    index: 0,
    change: onChannelChange
});

var channel = $("#channel").data("kendoDropDownList");

function onChannelChange()
{
 var value = $("#channel").val();
};



$("#store").kendoDropDownList({
    dataTextField: "text",
    dataValueField: "value",
    dataSource: dsStores,
    index: 0,
    change: onStoreChange
});

var store = $("#store").data("kendoDropDownList");

function onStoreChange()
{
 var value = $("#store").val();
};

var ds = {!! json_encode($dsSummary) !!};
var ColWitdh = 85;
  $("#grid").kendoGrid
    ({
        toolbar: ["excel"],
            excel: {
                fileName: "Kendo UI Grid Export.xlsx",
                proxyURL: "https://demos.telerik.com/kendo-ui/service/export",
                filterable: true
            },
        columns:
            [
                {title: "sku", field: "sku", width:ColWitdh, locked: true},
                {title: "Quantity ", field: "sell_quantity", width:ColWitdh,format: "{0:n0}" , footerTemplate: " #= kendo.toString(sum, '0,00')#" },
                {title: "return", field: "return_quantity", width:ColWitdh,format: "{0:n0}" , footerTemplate: " #= kendo.toString(sum, '0,00')#" },
                {title: "revenue", field: "revenue", width:ColWitdh,format: "{0:n0}", footerTemplate: " #= kendo.toString(sum, '0,00')#" },
                {title: "refund", field: "refund", width:ColWitdh, format: "{0:n0}", footerTemplate: " #= kendo.toString(sum, '0,00')#" },

                {title: "net", field: "nest_sales", width:ColWitdh,format: "{0:n0}", footerTemplate: " #= kendo.toString(sum, '0,00')#" },

                {title: "cogs", field: "cogs", width:ColWitdh,format: "{0:n0}" , footerTemplate: " #= kendo.toString(sum, '0,00')#" },
                {title: "promotion", field: "promotion", width:ColWitdh,format: "{0:n0}" , footerTemplate: " #= kendo.toString(sum, '0,00')#"  },
                {title: "seo", field: "seo_sem", width:ColWitdh,format: "{0:n0}" , footerTemplate: " #= kendo.toString(sum, '0,00')#" },
                {title: "shiping_fee", field: "shiping_fee", width:ColWitdh ,format: "{0:n0}" , footerTemplate: " #= kendo.toString(sum, '0,00')#"  },
                {title: "OtherExpensive", field: "other_selling_expensives", width:ColWitdh,format: "{0:n0}" , footerTemplate: " #= kendo.toString(sum, '0,00')#" },

                {title: "dip", field: "dip", width:ColWitdh,format: "{0:n0}" , footerTemplate: " #= kendo.toString(sum, '0,00')#" },
                {title: "msf", field: "msf", width:ColWitdh,format: "{0:n0}", footerTemplate: " #= kendo.toString(sum, '0,00')#"  },

                {title: "s.fees/Referal", field: "selling_fees", width:ColWitdh+ 20,format: "{0:n0}", footerTemplate:" #= kendo.toString(sum, '0,00')#"  },
                {title: "fullfillment", field: "fullfillment", width:ColWitdh+10,format: "{0:n0}" , footerTemplate: " #= kendo.toString(sum, '0,00')#" },
                {title: "Chargeback", field: "chargeback", width:ColWitdh + 20,format: "{0:n0}" , footerTemplate: " #= kendo.toString(sum, '0,00')#" },

                {title: "coop", field: "coop", width:ColWitdh,format: "{0:n0}" , footerTemplate: " #= kendo.toString(sum, '0,00')#"   },
                {title: "freight cost", field: "freight_cost", width:ColWitdh+10,format: "{0:n0}" , footerTemplate: " #= kendo.toString(sum, '0,00')#"  },
                {title: "FHReturnCost", field: "freight_handling_return_cost", width:ColWitdh+10,format: "{0:n0}" , footerTemplate: " #= kendo.toString(sum, '0,00')#"  },
                {title: "ebay final fee", field: "ebay_final_fee", width:ColWitdh,format: "{0:n0}" , footerTemplate: " #= kendo.toString(sum, '0,00')#"  },
                {title: "paypal Fee", field: "paypal_fee", width:ColWitdh,format: "{0:n0}" , footerTemplate: " #= kendo.toString(sum, '0,00')#" },
                {title: "discount", field: "discount", width:ColWitdh,format: "{0:n0}" , footerTemplate:" #= kendo.toString(sum, '0,00')#" },
                {title: "clip", field: "clip_fee", width:ColWitdh,format: "{0:n0}" , footerTemplate: " #= kendo.toString(sum, '0,00')#"  },
                {title: "Other Fee", field: "other_fee", width:ColWitdh,format: "{0:n0}", footerTemplate: " #= kendo.toString(sum, '0,00')#"  },
                {title: "Insurance", field: "liability_insurance", width:ColWitdh,format: "{0:n0}" , footerTemplate: " #= kendo.toString(sum, '0,00')#"  }
                {title: "profit", field: "profit", width:ColWitdh,format: "{0:n0}" , footerTemplate: " #= kendo.toString(sum, '0,00')#"  }

            ],
dataSource: {
                data: ds,
                aggregate:
                [
                    { field: "sell_quantity", aggregate: "sum" },
                    { field: "return_quantity", aggregate: "sum" },
                    { field: "revenue", aggregate: "sum" },
                    { field: "refund", aggregate: "sum" },
                    { field: "nest_sales", aggregate: "sum" },

                    { field: "cogs", aggregate: "sum" }, // sai
                    { field: "promotion", aggregate: "sum" },
                    { field: "seo_sem", aggregate: "sum" },
                    { field: "shiping_fee", aggregate: "sum" },//sai
                    { field: "other_selling_expensives", aggregate: "sum" },

                    { field: "dip", aggregate: "sum" }, //sai
                    { field: "msf", aggregate: "sum" }, //sai

                    { field: "selling_fees", aggregate: "sum" },
                    { field: "fullfillment", aggregate: "sum" },
                    { field: "chargeback", aggregate: "sum" } ,


                    { field: "coop", aggregate: "sum" }, //sai
                    { field: "freight_cost", aggregate: "sum" }, //sai

                    { field: "freight_handling_return_cost", aggregate: "sum" },
                    { field: "ebay_final_fee", aggregate: "sum" },
                    { field: "paypal_fee", aggregate: "sum" } ,
                    { field: "discount", aggregate: "sum" }, //sai
                    { field: "clip_fee", aggregate: "sum" }, //sai

                    { field: "other_fee", aggregate: "sum" },
                    { field: "liability_insurance", aggregate: "sum" },
                    { field: "profit", aggregate: "sum" }
                ]
            },
            scrollable: true,
            width: 'auto',
            height: 900,
            pageable: true

    });

});
</script>
@endsection


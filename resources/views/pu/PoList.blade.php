@extends('inc.master')
@section('content')
<div class="container-fluid">
    <div class="col-lg-12" style="background-color:white;">
        <div id="gridPO"></div>
    </div>
    <a id="mylink"></a>
</div>
@endsection
@section('scripts')
<script>
$(document).ready(function() {
$("#menu").kendoMenu();

//var ds = {!! json_encode($dsPOs)!!};
//var dsDT = {!! json_encode($dsPoDetails)!!};

var dsPO = [
        {"id":16182,"order_year":2020,"order_week":23,"vendor_name":"Ada"},
        {"id":16183,"order_year":2020,"order_week":24,"vendor_name":"Ada"}
        ];

    var dsPODT = [
        {"id":16182,"sku":"AX59","title":"Universal Hammock red","quantity":463,"fob_price":25.29,"amount":11709},
        {"id":16182,"sku":"KGWK","title":"Universal Hammock Orange","quantity":30,"fob_price":28.5,"amount":855}
        ];

var ColWitdh = 85;
var element = $("#gridPO").kendoGrid
    ({
        toolbar: ["excel"],
            excel: {
                fileName: "Kendo UI Grid Export.xlsx",
                proxyURL: "https://demos.telerik.com/kendo-ui/service/export",
                filterable: true
            },
        columns:
            [
                {title: "ID", field: "id", width:ColWitdh},
                {title: "Order Year", field: "order_year", width:ColWitdh},
                {title: "Order Week ", field: "order_week", width:ColWitdh },
                {title: "Vendor Name", field: "vendor_name", width:ColWitdh }
                // {title: "Lead Time", field: "lead_time", width:ColWitdh},
                // {title: "Load Date", field: "expect_load_date", width:ColWitdh },
                // {title: "ETD", field: "expect_etd_date", width:ColWitdh},
                // {title: "ETA", field: "expect_eta_date", width:ColWitdh},
                // {title: "Start Selling", field: "start_selling_date", width:ColWitdh},
                // {title: "End Selling", field: "end_selling_date", width:ColWitdh}
            ],
dataSource: {
             data: dsPO,
            },
            scrollable: true,
            width: 'auto',
            height: 900,
            selectable: true,
            pageable: true,
            //change: onChange,
            detailInit: detailInit,

            //  dataBound: function () {
            //  this.expandRow(this.tbody.find("tr.k-master-row").first());
            //  },
    });
//you can expand it programatically using the expandRow like this

//  element.on('click','tr',function(){
//     $(element).data().kendoGrid.expandRow($(this));

//  });

function detailInit(e)
{
     $("<div/>").appendTo(e.detailCell).kendoGrid({

        dataSource:
        {
         data: dsPODT,
         serverPaging: true,
         serverSorting: true,
         serverFiltering: true,
         pageSize: 5,
         filter: { field: "id", operator: "eq", value: e.data.id }
        },
        scrollable: false,
        sortable: false,
        selectable: true,
        pageable: true,
        columns:
           [
            { field: "id", width: "50px" },
            { field: "sku", width: "50px" },
            { field: "title", title: "title", width: "250px" },
            //{ field: "sell_type", title: "sell type" , width: "70px" },
            { field: "quantity", title: "quantity", width: "70px" },
            { field: "fob_price", title: "fob price", width: "70px" },
            { field: "amount,", title: "amount", width: "80px" }
          ]
    }).data("kendoGrid");
  }
});

</script>
@endsection
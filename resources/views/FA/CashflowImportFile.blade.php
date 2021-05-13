@extends('layouts.admin')
@section('content')
<div class="container-fluid">
    <form action="{{route('fa.CashFlow.ImportFile')}}" method="POST" enctype="multipart/form-data">
        @csrf
        <div class="col-lg-2" style="background-color:whitesmoke;">
            <div class="form-group">
                Start  Year: <input type="number" name="start_year" id="start_year" placeholder="input year" min="2020" max="2050" value="{{ old('start_year') }}" class="form-control" required>
             </div>
            <div class="form-group">
                Start Week: <input type="number" name="start_week" id="start_week" placeholder="input Start week"  min="1" max="52" value="{{ old('start_week') }}" class="form-control" required>
            </div>
            <div class="form-group">
                Weeks: <input type="number" name="weeks" id="weeks" placeholder="input how many weeks"  min="1" max="100"  value="{{ old('weeks') }}" class="form-control" required>
             </div>
            <div class="form-group">
                <input type="file" name="file" id="file" class="form-control">
            </div>
            <div class="form-group" required>
            <br>
            <button class="btn btn-success">Import PU data</button>
            </div>
        </div>

        <div class="col-lg-10" style="background-color:whitesmoke;">
            <div id="example">
                <div id="grid"></div>
            </div>
            <a id="mylink"></a>
            <div id="window">
                <div id="gridPODetail"></div>
            </div>
        </div>
    </form>
</div>
@endsection
@section('scripts')
<script>
$(document).ready(function() {
$("#menu").kendoMenu();

var ds = {!! json_encode($dsPuPlan) !!};

var ColWitdh = 85;
var element = $("#grid").kendoGrid
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
                {title: "Vendor Name", field: "vendor_name", width:ColWitdh },
                {title: "Lead Time", field: "lead_time", width:ColWitdh},
                {title: "Load Date", field: "expect_load_date", width:ColWitdh },
                {title: "ETD", field: "expect_etd_date", width:ColWitdh},
                {title: "ETA", field: "expect_eta_date", width:ColWitdh},
                {title: "Start Selling", field: "start_selling_date", width:ColWitdh},
                {title: "End Selling", field: "end_selling_date", width:ColWitdh}

            ],
dataSource: {
             data: ds,
            },
            scrollable: true,
            width: 'auto',
            height: 900,
            selectable: "multiple cell",
            pageable: true,
            //change: onChange,
            //detailInit: detailInit,
    });
//you can expand it programatically using the expandRow like this
element.on('click','tr',function(){
   $(element).data().kendoGrid.expandRow($(this));
})
/*
function detailInit(e) {
 $("<div/>").appendTo(e.detailCell).kendoGrid({
    dataSource: {
        data: dsPoDetails,
        },
        serverPaging: true,
        serverSorting: true,
        serverFiltering: true,
        pageSize: 5,
        filter: { field: "po_estimate_id", operator: "eq", value: e.data.po_estimate_id }
    },
    scrollable: false,
    sortable: false,
    selectable: true,
    pageable: true,
    columns:
            [
                { field: "sku", width: "70px" },
                { field: "title", title: "title", width: "250px" },
                { field: "sell_type", title: "sell type" },
                { field: "quantity", title: "quantity,", width: "100px" },
                { field: "fob_price", title: "fob price,", width: "100px" },
                { field: "amount,", title: "amount,", width: "150px" },
            ]
}).data("kendoGrid");
}
*/
});

</script>
@endsection
@extends('inc.master')
@section('content')

<div class="container-fluid">
    <div class="col-lg-10" style="background-color:white;">
        <div id="grid"></div>
    </div>
</div>
@endsection
@section('scripts')
<script>
    $("#menu").kendoMenu();
     var ds = {!! json_encode($PODetails) !!};
     $("#grid").kendoGrid({
                        toolbar: ["excel"],
                            excel: {
                                fileName: "PO Detail Export.xlsx",
                                filterable: true
                            },
                        dataSource: {
                            data: ds,
                            schema: {
                                model: {
                                    fields: {
                                        sku: { type: "string" },
                                        title: { type: "string" },
                                        sell_type: { type: "string" },
                                        life_cycle: { type: "string" },
                                        balance: { type: "number" },
                                        balance_at_selling: { type: "number" },
                                        fob_price: { type: "number" },
                                        moq: { type: "number" },
                                        quantity: { type: "number" }
                                    }
                                }
                            },
                            pageSize: 1000
                        },
                        height: 800,
                        width:500,
                        scrollable: true,
                        sortable: true,
                       // filterable: true,
                        pageable: {
                            input: true,
                            numeric: false
                        },
                        columns: [
                            { field: "sku", title: "sku",  width: "35px" },
                            { field: "title", title: "title", width: "130px" },
                            { field: "sell_type", title: "sell type",  width: "40px" },
                            { field: "life_cycle", title: "life cycle",  width: "40px" },

                            { field: "balance", title: "balance", width: "50px" },
                            { field: "balance_at_selling", title: "At selling",  width: "40px" },
                            { field: "fob_price", title: "fob_price", width: "50px" },
                            { field: "moq", title: "moq",  width: "40px" },
                            { field: "quantity", width: "50px" }
                        ]
                    });


</script>
@endsection
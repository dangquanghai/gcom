@extends('inc.master')
@section('content')

    <a id="mylink"></a>
    <div class="col-sm-6" style="background-color:white;">
    <div id="grid"></div>
    <div id="wnd">
      <div id="wrapped-grid"></div>
    </div>
  </div>

@endsection
@section('scripts')
<script>
    $("#menu").kendoMenu();
     $(document).ready(function() {

      var ds = {!! json_encode($POs) !!};
          $("#grid").kendoGrid({
            dataSource: {
              data:ds
              },
              schema: {
                model: {
                  fields: {
                    id: { type: "number" },
                    title: { type: "string" },
                    the_week: { type: "number" },
                    the_year: { type: "number" },
                    order_date: { type: "date" },
                    expect_eta: { type: "date" },
                    end_selling_date: { type: "date" }
                  }
                }
              },

            height: 400,
            filterable: true,
            sortable: true,
            pageable: true,
            columns: [
                        { field: "id", title: "id", width: "20px" },
                        { field: "title", title: "vendor", width: "30px" },
                        { field: "the_week", width: "30px" },
                        { field: "the_year", width: "30px" },

                        { field: "order_date", width: "50px" },
                        { field: "expect_eta", width: "50px" },
                        { field: "end_selling_date", width: "60px" }
                    ]
          });
          // end grid master
          var wnd = $("#wnd").kendoWindow({
            height: 600,
            width: 920,
            visible: false
          }).data("kendoWindow");


          //apply the activate event, which is thrown only after the animation is played out
          wnd.one("activate", function() {
            wrappedGrid.resize();
          });


          $('table').on('click', function(e){
          	if(e.target.nodeName == 'TD'){
              $.ajaxSetup({
                  headers: {
                      'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                  }
                });

                $.ajax({
                    url:'/pu.ShowPODetail/'+ e.target.innerText,
                    type: 'POST',
                    dataType:'json',
                    success: function(data){
                      var products = data;
                      var wrappedGrid = $("#wrapped-grid").kendoGrid({
                            toolbar: ["excel"],
                                      excel: {
                                          fileName: "PO Detail Export.xlsx",
                                          filterable: true
                                      },
                             dataSource: {
                             data: products,
                             pageSize: 500
                             },
                            height: 650,
                            scrollable: true,
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
                              }).data("kendoGrid");
                        wnd.title(e.target.innerText);
                        wnd.open();

                      }
                });


            }

          })
        });
</script>
@endsection
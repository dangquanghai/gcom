@extends('inc.master')
@section('content')
<div class="container-fluid">
    <!-- Control the column width, and how they should appear on different devices -->
    <form action="{{ route('showPLReport') }}" method="POST" >
        @csrf
        <div id="div1" class="row">
            <div class="col-lg-1" style="background-color:whitesmoke;">
                <h5>Profit Report</h5>
                <div class="form-group">
                    <input type="number" class="form-control" id="year" style="width: 90%;" placeholder="input year" name="year" min="2020" max="2050" value="{{ old('year') }}" required>
                </div>
                <div class="form-group">
                    <input   class="form-control" id="ReportType" style="width: 90%;" name="ReportType"    >
                </div>
                <button type="submit" class="btn btn-success">View</button>

                <div class="form-group">
                    <div id="loadingProgressBar"></div>
                </div>


            </div>
            <div class="col-lg-11" style="background-color:white;">
                <div id="example">
                    <div id="grid"></div>
                </div>
            </div>
        </div>
    </form>
</div>
@endsection

@section('styles')
    <style>
    .color01 {
        background-color: #70ad47;
        color: white;
    }
    .color02 {
        background-color:#9ad46a;
    }
    .color03{
    background-color:#c6e7ab;
    }
    .bosungvung1{
    background-color:#e6f0de;
    }
    .color04 {
    background-color:#f56e14;
    color:white;
    }
    .color05 {
        background-color:#f3914f;
        color:white;

    }
    .color06 {
    background-color: #f5ede9;
    }

    .color07 {
    background-color:#1287ec;
    color:white;
    }
    .color08 {
    background-color: #79b3ec;
    }
    .color09 {
     background-color: #c3d8ec;
    }

    .k-progressbar
    {
        width: 110px;
        height: 8px;
    }

    #loadingProgressBar
    {
        margin-left: 0px;
    }

</style>
@endsection

@section('scripts')
<script>
$(document).ready(function() {
$("#menu").kendoMenu();

var dsReportType = [
    { text: "For FA", value: "0" },
    { text: "For AC", value: "1" }
    ];

$("#ReportType").kendoDropDownList({
    dataTextField: "text",
    dataValueField: "value",
    dataSource: dsReportType,
    index: 0,
    change: onReportTypeChange
});

function onReportTypeChange()
{
 var value = $("#ReportType").val();
};


$("#loadingProgressBar").kendoProgressBar({
    showStatus: false,
    animation: false,
    change: onChange,
    complete: onComplete
});

//load();

function onChange(e) {
$(".loadingStatus").text(e.value + "%");
}

function onComplete(e) {
    //var total = $("#totalProgressBar").data("kendoProgressBar");
    var total = $("#loadingProgressBar");

    total.value(total.value() + 1);

    if (total.value() < total.options.max) {
        //$(".chunkStatus").text(total.value() + 1);
        //$(".loadingInfo h4").text("Loading " + itemsToLoad[total.value()]);
        load();
    }
}

function load() {
    var pb = $("#loadingProgressBar").data("kendoProgressBar");
    pb.value(0);

    var interval = setInterval(function () {
        if (pb.value() < 100) {
            pb.value(pb.value() + 1);
        } else {
            clearInterval(interval);
        }
    }, 30);
}

var ds = {!! json_encode($plReport) !!};
var ColWitdh = 90;
  $("#grid").kendoGrid
    ({
        toolbar: ["excel"],
            excel: {
                fileName: "Kendo UI Grid Export.xlsx",
                proxyURL: "https://demos.telerik.com/kendo-ui/service/export",
                filterable: true
            },

        dataSource: {
                            data: ds,
                            schema: {
                                model: {
                                    fields: {
                                        article: { type: "number" },
                                        des: { type: "string" },
                                        account : { type: "string" },
                                        grant_total_total: { type: "number" },
                                        grant_total_xl: { type: "number" },
                                        grant_total_tc: { type: "number" },
                                        grant_total_wel: { type: "number" },

                                        mot_total: { type: "number" },
                                        mot_xl: { type: "number" },
                                        mot_tc: { type: "number" },
                                        mot_wel: { type: "number" },

                                        hai_total: { type: "number" },
                                        hai_xl: { type: "number" },
                                        hai_tc: { type: "number" },
                                        hai_wel: { type: "number" },

                                        ba_total: { type: "number" },
                                        ba_xl: { type: "number" },
                                        ba_tc: { type: "number" },
                                        ba_wel: { type: "number" },

                                        q1_total: { type: "number" },
                                        q1_xl: { type: "number" },
                                        q1_tc: { type: "number" },
                                        q1_wel: { type: "number" },

                                        bon_total: { type: "number" },
                                        bon_xl: { type: "number" },
                                        bon_tc: { type: "number" },
                                        bon_wel: { type: "number" },

                                        nam_total: { type: "number" },
                                        nam_xl: { type: "number" },
                                        nam_tc: { type: "number" },
                                        nam_wel: { type: "number" },

                                        sau_total: { type: "number" },
                                        sau_xl: { type: "number" },
                                        sau_tc: { type: "number" },
                                        sau_wel: { type: "number" },

                                        q2_total: { type: "number" },
                                        q2_xl: { type: "number" },
                                        q2_tc: { type: "number" },
                                        q2_wel: { type: "number" },

                                        bay_total: { type: "number" },
                                        bay_xl: { type: "number" },
                                        bay_tc: { type: "number" },
                                        bay_wel: { type: "number" },

                                        tam_total: { type: "number" },
                                        tam_xl: { type: "number" },
                                        tam_tc: { type: "number" },
                                        tam_wel: { type: "number" },

                                        chin_total: { type: "number" },
                                        chin_xl: { type: "number" },
                                        chin_tc: { type: "number" },
                                        chin_wel: { type: "number" },

                                        q3_total: { type: "number" },
                                        q3_xl: { type: "number" },
                                        q3_tc: { type: "number" },
                                        q3_wel: { type: "number" },

                                        muoi_total: { type: "number" },
                                        muoi_xl: { type: "number" },
                                        muoi_tc: { type: "number" },
                                        muoi_wel: { type: "number" },

                                        mmot_total: { type: "number" },
                                        mmot_xl: { type: "number" },
                                        mmot_tc: { type: "number" },
                                        mmot_wel: { type: "number" },

                                        mhai_total: { type: "number" },
                                        mhai_xl: { type: "number" },
                                        mhai_tc: { type: "number" },
                                        mhai_wel: { type: "number" },

                                        q4_total: { type: "number" },
                                        q4_xl: { type: "number" },
                                        q4_tc: { type: "number" },
                                        q4_wel: { type: "number" }

                                    }
                                }
                            },
                            pageSize: 350
                        },

                        columns:
                        [
                            {
                                title: "article",
                                field: "article",
                                width:0
                            },
                            {
                                title: "Description",
                                field: "des",
                                width:250,
                                locked: true
                            },

                            {
                                title: "Account",
                                field: "account",
                                width:100
                            },
                            {
                            title: "Grant Total",
                                    columns:
                                    [
                                        { field: "grant_total_total", title: "Total",format: "{0:n0}",width:ColWitdh},
                                        { field: "grant_total_xl", title: "XL",format: "{0:n0}",width:ColWitdh},
                                        { field: "grant_total_tc", title: "T.Châu",format: "{0:n0}",width:ColWitdh},
                                        { field: "grant_total_wel", title: "WEL",format: "{0:n0}",width:ColWitdh}
                                    ]
                            },
                            {   //------------------ Q1 ---------------
                            title: "01",
                                    columns:
                                    [
                                        { field: "mot_total", title: "Total",format: "{0:n0}",width:ColWitdh},
                                        { field: "mot_xl", title: "XL",format: "{0:n0}",width:ColWitdh},
                                        { field: "mot_tc", title: "T.Châu",format: "{0:n0}",width:ColWitdh},
                                        { field: "mot_wel", title: "WEL",format: "{0:n0}",width:ColWitdh}
                                    ]
                            },
                            {
                            title: "02",
                                    columns:
                                    [
                                        { field: "hai_total", title: "Total",format: "{0:n0}",width:ColWitdh},
                                        { field: "hai_xl", title: "XL",format: "{0:n0}",width:ColWitdh},
                                        { field: "hai_tc", title: "T.Châu",format: "{0:n0}",width:ColWitdh},
                                        { field: "hai_wel", title: "WEL",format: "{0:n0}",width:ColWitdh}
                                    ]
                            },
                            {
                            title: "03",
                                    columns:
                                    [
                                        { field: "ba_total", title: "Total",format: "{0:n0}",width:ColWitdh},
                                        { field: "ba_xl", title: "XL",format: "{0:n0}",width:ColWitdh},
                                        { field: "ba_tc", title: "T.Châu",format: "{0:n0}",width:ColWitdh},
                                        { field: "ba_wel", title: "WEL",format: "{0:n0}",width:ColWitdh}
                                    ]
                            },
                            {
                            title: "Total Q1",
                                    columns:
                                    [
                                        { field: "q1_total", title: "Total",format: "{0:n0}",width:ColWitdh},
                                        { field: "q1_xl", title: "XL",format: "{0:n0}",width:ColWitdh},
                                        { field: "q1_tc", title: "T.Châu",format: "{0:n0}",width:ColWitdh},
                                        { field: "q1_wel", title: "WEL",format: "{0:n0}",width:ColWitdh}
                                    ]
                            },

                        { //----------------------------Q2 -----------------
                            title: "04",
                                    columns:
                                    [
                                        { field: "bon_total", title: "Total",format: "{0:n0}",width:ColWitdh},
                                        { field: "bon_xl", title: "XL",format: "{0:n0}",width:ColWitdh},
                                        { field: "bon_tc", title: "T.Châu",format: "{0:n0}",width:ColWitdh},
                                        { field: "bon_wel", title: "WEL",format: "{0:n0}",width:ColWitdh}
                                    ]
                            },
                            {
                            title: "05",
                                    columns:
                                    [
                                        { field: "nam_total", title: "Total",format: "{0:n0}",width:ColWitdh},
                                        { field: "nam_xl", title: "XL",format: "{0:n0}",width:ColWitdh},
                                        { field: "nam_tc", title: "T.Châu",format: "{0:n0}",width:ColWitdh},
                                        { field: "nam_wel", title: "WEL",format: "{0:n0}",width:ColWitdh}
                                    ]
                            },
                            {
                            title: "06",
                                    columns:
                                    [
                                        { field: "sau_total", title: "Total",format: "{0:n0}",width:ColWitdh},
                                        { field: "sau_xl", title: "XL",format: "{0:n0}",width:ColWitdh},
                                        { field: "sau_tc", title: "T.Châu",format: "{0:n0}",width:ColWitdh},
                                        { field: "sau_wel", title: "WEL",format: "{0:n0}",width:ColWitdh}
                                    ]
                            },
                            {
                            title: "Total Q2",
                                    columns:
                                    [
                                        { field: "q2_total", title: "Total",format: "{0:n0}",width:ColWitdh},
                                        { field: "q2_xl", title: "XL",format: "{0:n0}",width:ColWitdh},
                                        { field: "q2_tc", title: "T.Châu",format: "{0:n0}",width:ColWitdh},
                                        { field: "q2_wel", title: "WEL",format: "{0:n0}",width:ColWitdh}
                                    ]
                            },
                            { //----------------------------Q3 -----------------
                            title: "07",
                                    columns:
                                    [
                                        { field: "bay_total", title: "Total",format: "{0:n0}",width:ColWitdh},
                                        { field: "bay_xl", title: "XL",format: "{0:n0}",width:ColWitdh},
                                        { field: "bay_tc", title: "T.Châu",format: "{0:n0}",width:ColWitdh},
                                        { field: "bay_wel", title: "WEL",format: "{0:n0}",width:ColWitdh}
                                    ]
                            },
                            {
                            title: "08",
                                    columns:
                                    [
                                        { field: "tam_total", title: "Total",format: "{0:n0}",width:ColWitdh},
                                        { field: "tam_xl", title: "XL",format: "{0:n0}",width:ColWitdh},
                                        { field: "tam_tc", title: "T.Châu",format: "{0:n0}",width:ColWitdh},
                                        { field: "tam_wel", title: "WEL",format: "{0:n0}",width:ColWitdh}
                                    ]
                            },
                            {
                            title: "09",
                                    columns:
                                    [
                                        { field: "chin_total", title: "Total",format: "{0:n0}",width:ColWitdh},
                                        { field: "chin_xl", title: "XL",format: "{0:n0}",width:ColWitdh},
                                        { field: "chin_tc", title: "T.Châu",format: "{0:n0}",width:ColWitdh},
                                        { field: "chin_wel", title: "WEL",format: "{0:n0}",width:ColWitdh}
                                    ]
                            },
                            {
                            title: "Total Q3",
                                    columns:
                                    [
                                        { field: "q3_total", title: "Total",format: "{0:n0}",width:ColWitdh},
                                        { field: "q3_xl", title: "XL",format: "{0:n0}",width:ColWitdh},
                                        { field: "q3_tc", title: "T.Châu",format: "{0:n0}",width:ColWitdh},
                                        { field: "q3_wel", title: "WEL",format: "{0:n0}",width:ColWitdh}
                                    ]
                            },
                            { //----------------------------Q4 -----------------
                            title: "10",
                                    columns:
                                    [
                                        { field: "muoi_total", title: "Total",format: "{0:n0}",width:ColWitdh},
                                        { field: "muoi_xl", title: "XL",format: "{0:n0}",width:ColWitdh},
                                        { field: "muoi_tc", title: "T.Châu",format: "{0:n0}",width:ColWitdh},
                                        { field: "muoi_wel", title: "WEL",format: "{0:n0}",width:ColWitdh}
                                    ]
                            },
                            {
                            title: "11",
                                    columns:
                                    [
                                        { field: "mmot_total", title: "Total",format: "{0:n0}",width:ColWitdh},
                                        { field: "mmot_xl", title: "XL",format: "{0:n0}",width:ColWitdh},
                                        { field: "mmot_tc", title: "T.Châu",format: "{0:n0}",width:ColWitdh},
                                        { field: "mmot_wel", title: "WEL",format: "{0:n0}",width:ColWitdh}
                                    ]
                            },
                            {
                            title: "12",
                                    columns:
                                    [
                                        { field: "mhai_total", title: "Total",format: "{0:n0}",width:ColWitdh},
                                        { field: "mhai_xl", title: "XL",format: "{0:n0}",width:ColWitdh},
                                        { field: "mhai_tc", title: "T.Châu",format: "{0:n0}",width:ColWitdh},
                                        { field: "mhai_wel", title: "WEL",format: "{0:n0}",width:ColWitdh}
                                    ]
                            },
                            {
                            title: "Total Q4",
                                    columns:
                                    [
                                        { field: "q4_total", title: "Total",format: "{0:n0}",width:ColWitdh},
                                        { field: "q4_xl", title: "XL",format: "{0:n0}",width:ColWitdh},
                                        { field: "q4_tc", title: "T.Châu",format: "{0:n0}",width:ColWitdh},
                                        { field: "q4_wel", title: "WEL",format: "{0:n0}",width:ColWitdh}
                                    ]
                            },

                        ],
                        scrollable: true,
                        width: 'auto',
                        height: 900,
                        pageable: true,
                        dataBound: OndataBound
    });

    function OndataBound(e) {
        var grid = $('#grid').data('kendoGrid');

        grid.tbody.find('>tr').each(function () {
            var dataItem = grid.dataItem(this);
            if (dataItem.article == 7 ||dataItem.article == 8){
               $(this).addClass('color01');
            }
            else if(dataItem.article == 9 ||dataItem.article == 10 || dataItem.article == 61 ||dataItem.article == 62 ||dataItem.article == 103
            ||dataItem.article == 104 || dataItem.article == 125 ||dataItem.article == 126  ||dataItem.article == 136 ||dataItem.article == 137)
               $(this).addClass('color02');
            else if(dataItem.article == 11 ||dataItem.article == 21 ||dataItem.article == 31 || dataItem.article == 41  || dataItem.article == 51
            || dataItem.article == 63  || dataItem.article == 73 || dataItem.article == 83 || dataItem.article == 93 || dataItem.article == 105
            || dataItem.article == 115)
               $(this).addClass('color03');
            else if(
              (dataItem.article >=12 && dataItem.article <= 20) ||(dataItem.article >=22 && dataItem.article <= 30)
            ||(dataItem.article >=32 && dataItem.article <= 40) ||(dataItem.article >=42 && dataItem.article <= 50)
            ||(dataItem.article >=52 && dataItem.article <= 60) ||(dataItem.article >=64 && dataItem.article <= 72)
            ||(dataItem.article >=74 && dataItem.article <= 82) ||(dataItem.article >=84 && dataItem.article <= 92)
            ||(dataItem.article >=94 && dataItem.article <= 102) ||(dataItem.article >=106 && dataItem.article <= 114)
            ||(dataItem.article >=116 && dataItem.article <= 124) ||(dataItem.article >=127 && dataItem.article <= 135)
            ||(dataItem.article >=138 && dataItem.article <= 146)
            )
               $(this).addClass('bosungvung1');
            else if(dataItem.article == 147)
               $(this).addClass('color04');
            else if(dataItem.article == 148 || dataItem.article == 174 || dataItem.article == 195 || dataItem.article == 206 || dataItem.article == 211 )
               $(this).addClass('color05');
            else if(dataItem.article == 149 || dataItem.article == 154 || dataItem.article == 159 || dataItem.article == 164
            || dataItem.article == 169   || dataItem.article == 175  || dataItem.article == 180  || dataItem.article == 185  || dataItem.article == 190
            || dataItem.article == 196  || dataItem.article == 201    )
               $(this).addClass('color06');

            else if(dataItem.article == 216 )
               $(this).addClass('color07');

            else if(dataItem.article == 217  ||dataItem.article == 223 || dataItem.article == 224
             || dataItem.article == 228 || dataItem.article == 231 || dataItem.article == 232)
               $(this).addClass('color08');

            else if((dataItem.article >= 218  && dataItem.article <= 222 )|| (dataItem.article >= 224 && dataItem.article <= 227)
            || ( dataItem.article >= 229 && dataItem.article <= 230))
            $(this).addClass('color09');


        })
    }
});
</script>
@endsection



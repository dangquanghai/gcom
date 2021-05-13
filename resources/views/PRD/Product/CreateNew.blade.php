@extends('layouts.admin')
@section('content')
<div class="col-md-12" style="padding:0px">
        <div class="row">
            <div class="col-md-6" style="padding:0px">
                <div class="card card-primary">
                        <div class="card-header">
                            <h5>Product main information</h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <label for="inputEmail3" class="col-md-2">Product Name</label>
                                <div class="col-md-10">
                                        <input type="text" class="form-control" id="inputEmail3" >
                                </div>
                                <label for="sku" class="col-md-2">SKU:</label>
                                <div class="col-md-4">
                                    <input type="text" class="form-control" id="sku">
                                </div>
                                <label for="categoty" class="col-md-2">Categoty:</label>
                                <div class="col-md-4">
                                    <input type="text" class="form-control" id="categoty" >
                                </div>
                                <label for="group" class="col-md-2">Group:</label>
                                <div class="col-md-4">
                                    <input type="text" class="form-control" id="group" >
                                </div>
                                <label for="life_circle" class="col-md-2">Life Circle:</label>
                                <div class="col-md-4">
                                    <input type="text" class="form-control" id="life_circle" >
                                </div>

                                <label for="length" class="col-md-2">length:</label>
                                <div class="col-md-4">
                                    <input type="number" class="form-control" id="length" >
                                </div>

                                <label for="weight" class="col-md-2">weight:</label>
                                <div class="col-md-4">
                                    <input type="number" class="form-control" id="weight" >
                                </div>

                                <label for="width" class="col-md-2">width:</label>
                                <div class="col-md-4">
                                    <input type="number" class="form-control" id="width" >
                                </div>

                                <label for="cubic" class="col-md-2">Cubic:</label>
                                <div class="col-md-4">
                                    <input type="number" class="form-control" id="cubic" >
                                </div>

                                <label for="height" class="col-md-2">Height:</label>
                                <div class="col-md-4">
                                    <input type="number" class="form-control" id="height" >
                                </div>

                                <label for="volume" class="col-md-2">Volume:</label>
                                <div class="col-md-4">
                                    <input type="number" class="form-control" id="volume" >
                                </div>
                            </div>
                        </div>
                        <!-- /.card-body -->
                </div>
                <!-- /.card-footer -->
            </div>
            <div class="col-md-6" style="padding:0px">
                <div class="card card-primary">
                        <div class="card-header">
                            <h5>Product main information</h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <label for="inputEmail3" class="col-md-2">Product Name</label>
                                <div class="col-md-10">
                                        <input type="text" class="form-control" id="inputEmail3" >
                                </div>
                                <label for="sku" class="col-md-2">SKU:</label>
                                <div class="col-md-4">
                                    <input type="text" class="form-control" id="sku">
                                </div>
                                <label for="categoty" class="col-md-2">Categoty:</label>
                                <div class="col-md-4">
                                    <input type="text" class="form-control" id="categoty" >
                                </div>
                                <label for="group" class="col-md-2">Group:</label>
                                <div class="col-md-4">
                                    <input type="text" class="form-control" id="group" >
                                </div>
                                <label for="life_circle" class="col-md-2">Life Circle:</label>
                                <div class="col-md-4">
                                    <input type="text" class="form-control" id="life_circle" >
                                </div>

                                <label for="length" class="col-md-2">length:</label>
                                <div class="col-md-4">
                                    <input type="number" class="form-control" id="length" >
                                </div>

                                <label for="weight" class="col-md-2">weight:</label>
                                <div class="col-md-4">
                                    <input type="number" class="form-control" id="weight" >
                                </div>

                                <label for="width" class="col-md-2">width:</label>
                                <div class="col-md-4">
                                    <input type="number" class="form-control" id="width" >
                                </div>

                                <label for="cubic" class="col-md-2">Cubic:</label>
                                <div class="col-md-4">
                                    <input type="number" class="form-control" id="cubic" >
                                </div>

                                <label for="height" class="col-md-2">Height:</label>
                                <div class="col-md-4">
                                    <input type="number" class="form-control" id="height" >
                                </div>

                                <label for="volume" class="col-md-2">Volume:</label>
                                <div class="col-md-4">
                                    <input type="number" class="form-control" id="volume" >
                                </div>
                            </div>
                        </div>
                        <!-- /.card-body -->
                </div>
                <!-- /.card-footer -->
            </div>
        </div>
        <div class="row">
            <div class="col-md-6" style="padding:0px">
                <div class="card card-primary">
                    <div class="card-header">
                        <h5>Product Purchasing Information</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <label for="fob" class="col-md-2">FOB</label>
                            <div class="col-md-4">
                                    <input type="number" class="form-control" id="fob" >
                            </div>
                            <label for="cosg" class="col-md-2">COSG:</label>
                            <div class="col-md-4">
                                <input type="text" class="form-control" id="cosg">
                            </div>
                            <label for="moq" class="col-md-2">MOQ:</label>
                            <div class="col-md-4">
                                <input type="number" class="form-control" id="moq" >
                            </div>
                            <label for="tax_rate" class="col-md-2">Tax rate:</label>
                            <div class="col-md-4">
                                <input type="text" class="form-control" id="tax_rate" >
                            </div>
                            <label for="start_selling" class="col-md-2">Start Selling:</label>
                            <div class="col-md-4">
                                <input type="date" class="form-control" id="start_selling" >
                            </div>

                            <label for="size_tier" class="col-md-2">Size Tier:</label>
                            <div class="col-md-4">
                                <input type="number" class="form-control" id="size_tier" >
                            </div>
                            <label for="retire_selling" class="col-md-2">Retire Selling:</label>
                            <div class="col-md-4">
                                <input type="date" class="form-control" id="retire_selling" >
                            </div>

                            <label for="box_count" class="col-md-2">Box Count:</label>
                            <div class="col-md-4">
                                <input type="number" class="form-control" id="box_count" >
                            </div>
                        </div>
                    </div>
                    <!-- /.card-body -->
                    <div class="card-footer">
                        <button type="submit" class="btn btn-primary float-right">
                            <i class="fa fa-save "></i>
                        </button>
                    </div>
                </div>
            </div>
            <div class="col-md-6" style="padding:0px">
                <div class="card card-primary">
                    <div class="card-header">
                        <h5>Product Purchasing Information</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <label for="fob" class="col-md-2">FOB</label>
                            <div class="col-md-4">
                                    <input type="number" class="form-control" id="fob" >
                            </div>
                            <label for="cosg" class="col-md-2">COSG:</label>
                            <div class="col-md-4">
                                <input type="text" class="form-control" id="cosg">
                            </div>
                            <label for="moq" class="col-md-2">MOQ:</label>
                            <div class="col-md-4">
                                <input type="number" class="form-control" id="moq" >
                            </div>
                            <label for="tax_rate" class="col-md-2">Tax rate:</label>
                            <div class="col-md-4">
                                <input type="text" class="form-control" id="tax_rate" >
                            </div>
                            <label for="start_selling" class="col-md-2">Start Selling:</label>
                            <div class="col-md-4">
                                <input type="date" class="form-control" id="start_selling" >
                            </div>

                            <label for="size_tier" class="col-md-2">Size Tier:</label>
                            <div class="col-md-4">
                                <input type="number" class="form-control" id="size_tier" >
                            </div>
                            <label for="retire_selling" class="col-md-2">Retire Selling:</label>
                            <div class="col-md-4">
                                <input type="date" class="form-control" id="retire_selling" >
                            </div>

                            <label for="box_count" class="col-md-2">Box Count:</label>
                            <div class="col-md-4">
                                <input type="number" class="form-control" id="box_count" >
                            </div>
                        </div>
                    </div>
                    <!-- /.card-body -->
                    <div class="card-footer">
                        <button type="submit" class="btn btn-primary float-right">
                            <i class="fa fa-save "></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>
</div>
@endsection
@section('scripts')
<script src="/vendor/laravel-filemanager/js/lfm.js"></script>
<script src="//cdn.ckeditor.com/4.6.2/standard/ckeditor.js"></script>
<script>
function OpenServerBrowser(url, width, height) {
    let iLeft = (screen.width - width) / 2;
    let iTop = (screen.height - height) / 2;
    let sOptions = "toolbar=no,status=no,resizable=yes,dependent=yes";
    sOptions += ",width=" + width;
    sOptions += ",height=" + height;
    sOptions += ",left=" + iLeft;
    sOptions += ",top=" + iTop;
    window.open(url, "BrowseWindow", sOptions);
}

function SetUrl(url, width, height, alt) {
    let src_url_img = '/files/' + url.split(/\/files\//)[1];
    $('input[name=url_img]').val(src_url_img);
    $('img.image-review').attr('src', src_url_img);
}

$(document).ready(function() {
$('.lifce_circle').select2({
    theme: 'bootstrap4'
    })
});

var options = {
filebrowserImageBrowseUrl: '{{ route('unisharp.lfm.show') }}?view=Images',
filebrowserImageUploadUrl: '{{ route('unisharp.lfm.upload') }}?view=Images&_token=',
filebrowserBrowseUrl: '{{ route('unisharp.lfm.show') }}?type=Files',
filebrowserUploadUrl: '{{ route('unisharp.lfm.upload') }}?type=Files&_token='
};
CKEDITOR.replace('description', options);
</script>
@endsection

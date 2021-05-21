@extends('layouts.admin')
@section('content')
@if($Product)
<form role="form" method="post" action="{{route('Product.update',$Product->id) }}">
    {{ csrf_field() }}
    <input type="hidden" name ="_method" value ="PUT">
        <!-- left column -->
        <div class="col-md-9">
           @if($Product)
            <!-- general form elements -->
            <div class="card card-primary">
                <div class="card-header">
                    <h3 class="card-title">{{trans('product.info')}}</h3>
                </div>
                <!-- /.card-header -->
                <!-- form start -->
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="sku">SKU</label>
                                <input type="text" class="form-control" name="sku" id="sku" value="{{$Product->sku}}">
                            </div>
                        </div>
                        <div class="col-md-9">
                            <div class="form-group">
                                <label for="name">{{trans('product.name')}}</label>
                                <input type="text" name="name" id="name"name="name" class="form-control" value ="{{$Product->name}}" >
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-sm-3">
                            <div class="form-group">
                                <label for="height">Height</label>
                                <input type="text" class="form-control" name="height" id="height" value ="{{$Product->height}}">
                            </div>
                        </div>
                        <div class="col-sm-3">
                            <div class="form-group">
                                <label for="width">Width</label>
                                <input type="text" class="form-control" name="width" id="width" value ="{{$Product->width}}" >
                            </div>
                        </div>
                        <div class="col-sm-3">
                            <div class="form-group">
                                <label for="length">Length</label>
                                <input type="text" class="form-control" name="length" id="length" value ="{{$Product->length}}">
                            </div>
                        </div>
                        <div class="col-sm-3">
                            <div class="form-group">
                                <label for="life_circle">Life Circle</label>
                                <select class="form-control" name ="life_circle" id="life_circle">
                                    {!! getList($ProductLifeCircles,$Product->life_circle) !!}
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class ="row">
                        <div class="col-md-9">
                            <div class="form-group">
                                <label for="description">Description</label>
                                <textarea name="description" id="description" class="form-control">
                                     {{$Product->description}}
                                </textarea>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="url_img">{{trans('admin.image') }}</label> <br/>
                                <img width="auto" height="auto" class="image-review"
                                    alt="url_img"
                                    src="{{!empty($Product->url_img) ? $Product->url_img : 'http://placehold.it/270x200' }}"
                                    onclick="OpenServerBrowser('{!! route('unisharp.lfm.show') !!}?view=images', screen.width * 1, screen.height * 1);">
                                <input type="hidden" class="form-control" name="url_img" id="url_img" value ="{{$Product->url_img}}">
                            </div>
                        </div>
                    </div>
                </div>
                <!-- /.card-body -->
                <div class="card-footer">
                    <button type="submit" class="btn btn-primary">
                        <i class="fa fa-save"></i>
                    </button>
                </div>
            </div>
            <!-- /.card -->
            @endif
        </div>
        <!--/.col (left) -->
        <!-- right column -->
</form>
@endif
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

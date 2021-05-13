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
            <input type="text" class="form-control" id="sku" name="sku"  value="{{$Sku}}">
          </div>

          <div class="form-group">
            <label>Product Name</label>
            <input type="text" id ="product_name" name ="product_name" class="form-control"
            value="{{$ProductName}}">
          </div>
          <div class="form-group">
            <label for="product_group">Product Group</label>
            <select class="form-control" name="product_group" id="product_group" >
              {!! getList($ProductGroups,$ProductGroup) !!}
            </select>
          </div>
          <div class="form-group">
            <label for="life_circle">Life Circle</label>
            <select class="form-control" name="life_circle" id="life_circle" >
              {!! getList($ProductLifeCircles,$LifeCircle) !!}
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
      <div class="card card-primary" >
        <div class="card-header">
          <h3 class="card-title">Produtc List</h3>
          <div class="card-tools">
           <a href="{{route('Product.create')}}"><i class="fa fa-plus-square"></i></a>
          </div>
        </div>
        <!-- /.card-header -->
        <div class="card-body table-responsive p-0" style="height: 800px;">
          <table class="table table-head-fixed text-nowrap">
            <thead>
              <tr>
                <th>ID</th>
                <th>SKU</th>
                <th>Product Name</th>
                <th>Life Circle</th>
                <th>Width</th>
                <th>Height</th>
                <th>Action</th>
              </tr>
            </thead>
            <tbody>
              @if($Products)
                @foreach ( $Products as $p )
                  <tr>
                    <td>{{$p->id}}</td>
                    <td>{{$p->sku}}</td>
                    <td><img src={{asset($p->url_img)}} style class="img-in-row" > <a href="{{route('Product.show',$p->id)}}"> {{$p->ProductName}} </a></td>
                    <td>{{$p->LifeCircle}}</td>
                    <td>{{$p->width}}</td>
                    <td>{{$p->height}}</td>
                    <td>
                      <a href="{{route('Product.edit',$p->id)}}"> <i class="fa fa-edit"></i></a>
                      &nbsp
                      <a href="{{route('Product.del',$p->id)}}"> <i class="fa fa-trash" style="color:red;"></i></a>
                    </td>
                  </tr>
                @endforeach
              @endif
              </tbody>
          </table>
        </div>
        <!-- /.card-body -->
      </div>
      <!-- /.card -->
    </div>
  </div>
</form>
@endsection
@section('scripts')
@if(Session::has('success'))
<script>
    showMessage('success', '{{ Session::get('success') }}')
</script>
@endif
@endsection
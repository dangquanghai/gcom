@extends('layouts.admin')
@section('content')
<form role="form">
<div class ="row">
  <div class="col-md-2" style="padding-right:0px">
    <!-- general form elements -->
        <!-- general form elements tao lao thjoi-->
    <div class="card card-primary">
      <div class="card-header">
        <h3 class="card-title">Search</h3>
      </div>
      <!-- /.card-header -->
      <!-- form start -->
      <form role="form">
        <div class="card-body">
          <div class="form-group">
            <label for="no">No</label>
            <input type="text" class="form-control" id="no" name="no"  value="{{ $No }}">
          </div>
          <div class="form-group">
            <label for="vendor_id">Vendor</label>
            <select class="form-control" name="vendor_id" id="vendor_id" >
              {!! getList($Vendors,$VendorID)!!}
            </select>
          </div>

          <div class="form-group">
            <label for="from_date"> From Date</label>
            <input type="date" class="form-control"  width= "150px" id="from_date"  name="from_date"
            value="{{$FromDate}}" required>
          </div>
          <div class="form-group">
            <label for="to_date">ToDate</label>
            <input type="date" class="form-control"  width= "150px"  id ="to_date" name="to_date"
            value="{{$ToDate}}" required>
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
          <h3 class="card-title">Import List</h3>
          <div class="card-tools">
           <a href="{{route('Transaction.create')}}"><i class="fa fa-plus-square"></i></a>
          </div>
        </div>
        <!-- /.card-header -->
        <div class="card-body table-responsive p-0" style="height: 800px;">
          <table class="table table-head-fixed text-nowrap">
            <thead>
              <tr>
                <th>ID</th>
                <th>NO</th>
                <th>The Date</th>
                <th>Vendor</th>
                <th>Description</th>
                <th>Amount</th>
              </tr>
            </thead>
            <tbody>
              @if($Transactions)
                @foreach ( $Transactions as $t )
                  <tr>
                    <td>{{$t->id}} </td>
                    <td>
                       <a href="{{route('Transaction.show',$t->id)}}"> {{$t->no}} </a>
                    </td>
                    <td>{{$t->the_date}}</td>
                    <td>{{$t->vendor_name}}</td>
                    <td>{{$t->description}}</td>
                    <td>{{$t->amount}}</td>
                    <td>
                      <a href="{{route('Transaction.edit',$t->id)}}"> <i class="fa fa-edit"></i></a>
                      &nbsp
                      <a href="{{route('Transaction.destroy',$t->id)}}"> <i class="fa fa-trash" style="color:red;"></i></a>
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
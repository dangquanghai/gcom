@extends('layouts.admin')
@section('content')
 <!-- Content Header (Page header) -->
 <div class="content-header">
    <div class="container-fluid">
      <div class="row mb-2">
        <div class="col-sm-6">
          <h1 class="m-0 text-dark">Categories</h1>
        </div><!-- /.col -->
        <div class="col-sm-6">
          <ol class="breadcrumb float-sm-right">
            <li class="breadcrumb-item"><a href="{{route('home')}}">Dashboard</a></li>
            <li class="breadcrumb-item active">Categories</li>
          </ol>
        </div><!-- /.col -->
      </div><!-- /.row -->
    </div><!-- /.container-fluid -->
  </div>
  <!-- /.content-header -->
<div class = "container-fluid">
  <p>
    <a href="#" class ="btn btn-primary">Add new </a>
  </p>
  <table class= "table table-bordered table-striped ">
    <tr>
      <th>ID</th>
      <th>Name</th>
      <th>Action</th>
    </tr>
    @foreach ($categories as $c)
    <tr>
     <td>{{$c->id}}</td>
     <td>{{$c->name}}</td>
     <td><a href="#" class ="btn btn-info">Edit </a> <a href="#" class="btn btn-danger">delete </a> </td>
    </tr>
    @endforeach
  </table>
</div>
@endsection



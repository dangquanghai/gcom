@extends('layouts.admin')
@section('content')
<form role="form" method="post" action="">
    {{ csrf_field() }}
    <input type="hidden" name ="_method" value ="PUT">
        <!-- left column -->
    <div class="row">
        <div class="col-md-6" style="padding-right:0px">
        <!-- @if($dsProduct) -->
            <!-- general form elements -->
            <div class="card card-primary">
                <div class="card-header">
                    <h3 class="card-title"> Sales Product Informations </h3>
                </div>
                <!-- /.card-header -->
                <!-- form start -->
                
                <div class="card-footer">
                   
                </div>
            </div>
            <!-- /.card -->
           <!--  @endif  -->
        </div> <!-- end card bên trái  -->      
</form>
@endsection
@section('scripts')
<script type= "text/javascript">
</script>
@endsection

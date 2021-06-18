@extends('layouts.admin')
@section('content')
<form action="{{route('vendor.index')}}" method="POST" >
@csrf
<div class ="row">
  <div class="col-md-2" style="padding-right:0px">
    <!-- general form elements -->
    <div class="card card-primary">
      <div class="card-header">
        <h3 class="card-title">Search</h3>
      </div> <!-- /.card-header -->
      <!-- form start -->
      <form role="form">
        <div class="card-body">
          <div class="form-group">
            <label for="sku">SKU</label>
            <input type="text" class="form-control" id="sku" name="sku"  >
          </div>
          <div class="form-group">
            <label for="asin">ASIN</label>
            <input type="text" class="form-control" id="asin" name="asin" >
          </div>
          <div class="form-group">
            <label>Title</label>
            <input type="text" id ="title" name ="title" class="form-control" >
          </div>
          
          <div class="form-group">
            <label for="promotion_no">Promo ID</label>
            <input type="text" class="form-control" id="promotion_no" name="promotion_no" >
          </div>
        
          <div class="form-group">
            <label for="promotion_type">Promotion Type</label>
            <select class="form-control" name="promotion_type" id="promotion_type" >
             
            </select>
          </div>
          <div class="form-group">
            <label for="promotion_status">Promotion Status</label>
            <select class="form-control" name="promotion_status" id="promotion_status" >
             
            </select>
          </div>

          <div class="form-group">
            <label for="from_date">From Date</label>
            <input type="date" class="form-control" id="from_date" name="from_date"  required>
          </div>

          <div class="form-group">
            <label for="to_date">To Date</label>
            <input type="date" class="form-control" id="to_date" name="to_date"   required >
          </div>

          <div class="form-group">
            <label for="channel">Channel</label>
            <select  id="channel_id" > </select>
          </div>

          

          <div class="form-group">
            <label for="product_group">Brand</label>
            <select class="form-control" name="brand" id="brand" >
           
          </select>
          </div>
        </div> <!-- /.card-body -->
        <div class="card-footer">
          <button type="submit" class="btn btn-primary">
            <i class="fa fa-search"></i>
          </button>
        </div><!-- /.card-footer -->
      </form>
    </div><!-- card card-primary -->
  </div>  <!-- /.col-md-2 -->
  
  <div class="col-md-10" style="padding-left:0px">
      <div class="card card-primary">
              <div class="card-header">
              <div class="card-tools">
                  <a href="{{route('Promotion.create')}}"><i class="fa fa-plus-square" id="btnCreate"></i></a>
              </div>
                <h3 class="card-title">Data</h3>
              </div>
          <div class="card-body">

          <div id="wrapper">
			      <h1>Selectize.js</h1>
			      <div class="demo">
				      <h2>API</h2>
				      <p>Examples of how to interact with the control programmatically.</p>
              <div class="control-group">
                <label for="select-tools">Tools:</label>
                <select id="select-tools"  multiple placeholder="Pick an item..."></select>
              </div>
              <div class="buttons">
                <input type="button" value="clear()" id="button-clear">
                <input type="button" value="clearOptions()" id="button-clearoptions">
                <input type="button" value="addOption()" id="button-addoption">
                <input type="button" value="addItem()" id="button-additem">
                <input type="button" value="setValue()" id="button-setvalue">
                <input type="button" value="maxItems(2)" id="button-maxitems2">
                <input type="button" value="maxItems(100)" id="button-maxitems100">
              </div>
            </div>
          </div>


          </div> <!-- /.card-body -->
          <div class="card-footer">
          </div>  <!-- /.card-footer -->
      </div><!-- /.card-primary -->
  </div><!-- /.col-md-10 -->
</div><!-- /.row -->
</form>
@endsection
@section('scripts')
<script>
       var sChannel='';
       var ds = {!! json_encode($ds) !!};

        var Element = document.getElementById("channel_id");
        LoadList(Element,ds) ;

       var Element1 = document.getElementById("select-tools");
       LoadList(Element1,ds) ;
        
        
	    // - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
     

			
      
</script>
@endsection
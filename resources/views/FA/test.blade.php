@extends('inc.master')
@section('content')
<h1>Add New Product</h1>
<hr>
<form action="/test" method="post">
{{ csrf_field() }}
<div class="form-group">
<label for="title">Product Name</label>
<input type="text" class="form-control" id="productName" name="name" value="{{old('name')}}">

</div>
<div class="form-group">
<label for="description">Product Company</label>
<select class="form-control" name="company">
<option value="Apple" {{ old('company') == "Apple" ? 'selected' : '' }}>Apple</option>
<option value="Google" {{ old('company') =="Google" ? 'selected' : '' }}>Google</option>
<option value="Mi" {{ old('company') == "Mi"? 'selected' : '' }}>Mi</option>
<option value="Samsung" {{ old('company') == "Samsung" ? 'selected' : '' }}>Samsung</option>
</select>
</div>
<div class="form-group">
<label for="description">Product Amount</label>
<input type="text" class="form-control" id="productAmount" name="amount" value="{{old('amount')}}"/>
</div>
<div class="form-group">
<label for="description">Product Available</label><br/>
<label class="radio-inline"><input type="radio" name="available" id="available" value="1" {{ (old('available') == '1') ? 'checked' : ''}}>Yes</label>
<label class="radio-inline"><input type="radio" name="available" id="available" value="0" {{ (old('available') == '0') ? 'checked' : ''}}> No</label>
</div>
<div class="form-group">
<label for="description">Product Description</label>
<textarea type="text" class="form-control" id="productDescription" name="description" />{{old('description')}}</textarea>
</div>
<div>
<label for="features">Product Features</label><br/>
<label class="checkbox-inline"><input type="checkbox" name="features[]" value="Camera" {{ (is_array(old('features')) and in_array('Camera', old('features'))) ? ' checked' : '' }}/>Camera</label>
<label class="checkbox-inline"><input type="checkbox" name="features[]" value="FrontCamera" {{ (is_array(old('features')) and in_array("FrontCamera", old('features'))) ? ' checked' : '' }}/>Front Camera</label>
<label class="checkbox-inline"><input type="checkbox" name="features[]" value="FingerPrint" {{ (is_array(old('features')) and in_array('FingerPrint', old('features'))) ? ' checked' : '' }}/>Finger print sensor</label>
<label class="checkbox-inline"><input type="checkbox" name="features[]" value="DualSim" {{ (is_array(old('features')) and in_array('DualSim', old('features'))) ? ' checked' : '' }}/>Dual sim</label>
</div>
@if ($errors->any())
<div class="alert alert-danger">
<ul>
@foreach ($errors->all() as $error)
<li>{{ $error }}</li>
@endforeach

</ul>
</div>
@endif
<button type="submit" class="btn btn-primary">Submit</button>
</form>
@endsection
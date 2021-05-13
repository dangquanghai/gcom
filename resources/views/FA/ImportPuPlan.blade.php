@extends('inc.master')
@section('content')
<div class="container-fluid">
    <form action="{{route('fa.ImportPuPlan')}}" method="POST" enctype="multipart/form-data">
        @csrf
        <div class="col-lg-2" style="background-color:whitesmoke;">
            <div class="form-group">
                <input type="file" name="file" id="file" class="form-control">
            </div>
            <div class="form-group" required>
            <br>
            <button class="btn btn-success">Import PU Plan</button>
            </div>
        </div>

        <div class="col-lg-10" style="background-color:whitesmoke;">
            <div id="example">
                <div id="grid"></div>
            </div>
        </div>
    </form>
</div>
@endsection
@section('scripts')
<script>
$(document).ready(function() {
$("#menu").kendoMenu();
 var ds = {!! json_encode($dsPuPlan) !!};
});
</script>
@endsection
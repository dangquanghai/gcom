@extends('inc.master')
@section('content')
<div class="container-fluid">
    <form action="{{ route('pu.puImport') }}" method="POST" enctype="multipart/form-data">
        @csrf
        <div class="col-lg-3" style="background-color:whitesmoke;">
            <div class="form-group">
                Start Week: <input type="number" name="start_week" id="start_week" placeholder="input Start week"  min="1" max="52" value="{{ old('start_week') }}" class="form-control" required>
            </div>
            <div class="form-group">
               The Year: <input type="number" name="the_year" id="the_year" placeholder="input year" min="2020" max="2050" value="{{ old('the_year') }}" class="form-control" required>
            </div>
            <div class="form-group">
               Weeks: <input type="number" name="weeks" id="weeks" placeholder="input how many weeks"  min="1" max="52"  value="{{ old('weeks') }}" class="form-control" required>
            </div>
            <div class="form-group">
                <input type="file" name="file" id="file" class="form-control">  <div class="form-group" required>
            <br>
            <button class="btn btn-success">Import PU data</button>
        </div>
    </form>
</div>
@endsection
@section('scripts')
<script>
    $("#menu").kendoMenu();
</script>
@endsection
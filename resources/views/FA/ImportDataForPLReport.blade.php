@extends('layouts.admin')
@section('content')
<div class="container-fluid">
    <div class="card bg-light mt-3">
        <div class="card-body">
            <form action="{{ route('importData') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="col-lg-3" style="background-color:whitesmoke;">
                    <div class="form-group">
                        <input type="number" class="form-control" id="year" placeholder="input year" name="year" min="2020" max="2050">
                    </div>
                    <div class="form-group">
                    <input type="number" class="form-control" id="month" placeholder="month" name="month" min="1" max="12" value="{{ old('month') }}" >
                    </div>
                    <input type="file" name="file" id="file" class="form-control">
                    <br>
                    <button class="btn btn-success">Import Data</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
@section('scripts')
<script>

</script>
@endsection
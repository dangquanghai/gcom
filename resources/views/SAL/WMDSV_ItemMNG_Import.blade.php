@extends('layouts.admin')
@section('content')
<div class="container-fluid">
    <div class="card bg-light mt-3">
        <div class="card-body">
            <form action="{{ route('sal.wm.item_mng.import') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="col-lg-3" style="background-color:whitesmoke;">
                    <input type="file" name="file" id="file" class="form-control">
                    <button class="btn btn-success">Import WM DSV Item MNG</button>
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
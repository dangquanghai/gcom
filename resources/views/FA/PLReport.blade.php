@extends('layouts.admin')
@section('content')
<div class="container-fluid">
    <!-- Control the column width, and how they should appear on different devices -->
    <form action="{{route('fa.PLReport') }}" method="POST" >
        @csrf
        <div id="div1" class="row">
            <div class="col-lg-1" style="background-color:whitesmoke;">
                <h5>Profit Report</h5>
                <div class="form-group">
                    <input type="number" class="form-control" id="year" style="width: 90%;" placeholder="input year" name="year" min="2020" max="2050" value="{{old('year') }}" required>
                </div>
                <button type="submit" class="btn btn-success">View</button>

            </div>
            <div class="col-lg-11" style="background-color:white;">
                <div id="example">
                    <div id="grid"></div>
                </div>
            </div>
        </div>
    </form>
</div>
@endsection

@section('styles')
<style>
</style>
@endsection
@section('scripts')
<script>
var $table = $('#table');
var ds = {!! json_encode($plReport) !!};
var ColWitdh = 90;
$(function() {
$table.bootstrapTable('destroy').bootstrapTable({
        data: ds,
        columns:
                    [
                        {
                            title: "article",
                            field: "article",
                            width:0
                        },
                        {
                            title: "Description",
                            field: "des",
                            width:250,
                            locked: true
                        },

                        {
                            title: "Account",
                            field: "account",
                            width:100
                        }
                   ]
      })
    })
</script>
@endsection



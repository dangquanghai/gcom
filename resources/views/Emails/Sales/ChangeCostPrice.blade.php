@extends('layouts.email')
@section('content')
<div class="col-md-06" style="padding-right:0px">
    <div class="card card-primary">
        <div class="card-header">
            <h3 class="card-title"> Notification => Change Price and/or cost </h3>

            <div class="alert alert-dange ajax-error" role="alert">
                <div class="ajax-error-ct"></div>
            </div>
            <div class="alert ajax-success" role="alert" style="width: 250px;background: rgba(92,130,79,0.9); display:none; color: #fff;">
                <div class="ajax-success-ct"></div>
            </div>
            
        </div>
        <div class="card-body">
            <div class="row">
            The message:
            {{ $ReceiveMessage}}
            </div>
            <div class="row">
            The link:
            {{$ReceiveTheLink}}
            </div>
        </div>
        <div class="card-footer">
        </div>
    </div>
</div> 
@endsection


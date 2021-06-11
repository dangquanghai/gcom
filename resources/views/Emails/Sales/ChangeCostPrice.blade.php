@extends('layouts.email')
@section('content')
<div class="col-md-12" style="padding-left:0px">
    <div class="card card-primary">
        <div class="card-header">
            <h3 class="card-title"> Notification => Change Cost and/or Price </h3>

            <div class="alert alert-dange ajax-error" role="alert">
                <div class="ajax-error-ct"></div>
            </div>
            <div class="alert ajax-success" role="alert" style="width: 250px;background: rgba(92,130,79,0.9); display:none; color: #fff;">
                <div class="ajax-success-ct"></div>
            </div>
            
        </div>
        <div class="card-body">
            <div class="row">
            <h3> Dear Sir/Madam,<br> <br> 
                At {{$EffectFrom }} Mr/Ms {{$UserName }} has update Cost/Price for the product <br>
                SKU : {{$Sku}}  , Product Name : {{$ProductName}} <br>
                Old Cost: {{$OldCost}} => New Cost : {{$NewCost}}<br>
                Old Price:{{$OldPrice}} => New Price : {{$NewPrice}}<br>
                on the Channel : {{$ChannelName}}<br><br>

                Thank for  being awared ,<br>
                {{ config('app.name') }}
            </h3>

            </div>
           
        </div>
        <div class="card-footer">
        </div>
    </div>
</div> 
@endsection
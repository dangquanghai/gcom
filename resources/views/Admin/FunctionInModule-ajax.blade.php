@if (isset($dsFnc))
    @foreach ($dsFnc as $Item)
     <option    value="{{$Item->id}}">{{$Item->name}} </option>
    @endforeach
@else
    <option> </option>;
@endif



<tr>
<td  id="id" style="display:none;"></td>
<td  id="channel_name" contenteditable > 
    <select class="form-control" name="channelx" id="channelx" >
        {!! getList($dsChannels,0)!!}
    </select>
</td>
<td  id="channel_id" contenteditable style="display:none;"> </td>

<td contenteditable id="retail_price"> </td>
<td contenteditable id="per_cost"> </td>
<td contenteditable id="cost"> </td>

<td contenteditable id="some_fee" style="display:none;"> </td>
<td contenteditable id="per_other_fee" style="display:none;"> </td>

<td id="est_profit"> </td>
<td> <button id="Addbtn" type= "button" class="btn btn-success btn-xs" id="add" disabled> Add </button> </td> 
</tr>

@foreach($data as $item)
<tr>
    <td class = "column_name"   data-column_name ="id"  id ="{{$item->id}}" style="display:none;"> {{$item->id}}</td>
    <td class = "column_name"   data-column_name ="channel_name"  id ="{{$item->id}}">
        <select class="form-control" disabled >
            {!! getList($dsChannels,$item->channel_id)!!}
        </select>   
    </td>
    <td class = "column_name"   data-column_name ="channel_id"  id ="{{$item->id}}" style="display:none;" >{{$item->channel_id}} </td>
    <td contenteditable class = "column_name" data-column_name ="retail_price"  id ="{{$item->id}}">{{$item->retail_price}} </td>
    <td contenteditable class = "column_name" data-column_name ="per_cost"  id ="{{$item->id}}">{{$item->per_cost}} </td>
    <td  class = "column_name" data-column_name ="cost"  id ="{{$item->id}}"> {{$item->cost}}</td>
    
    <td  class = "column_name" data-column_name ="some_fee"  id ="{{$item->id}}" style="display:none;"> {{$item->some_fee}}</td>
    <td  class = "column_name" data-column_name ="per_other_fee"  id ="{{$item->id}}" style="display:none;"> {{$item->per_other_fee}}</td>
    
    <td  class = "column_name" data-column_name ="est_profit"  id ="{{$item->id}}" disabled> {{$item->est_profit}}</td>
    <td> <button type= "button" class="btn btn-danger btn-xs" id="{{$item->id}}" disabled> Delete </button> 
    </td>
</tr>
@endforeach

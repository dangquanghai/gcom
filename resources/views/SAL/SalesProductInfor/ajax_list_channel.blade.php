
<tr>
<td  id="id" style="display:none;" > </td>
<td  id="channel_id" contenteditable > 
<select class="form-control" name="channelx" id="channelx" >
    {!! getList($dsChannels,0)!!}
    </select>
</td>

<td contenteditable id="retail_price"> </td>
<td contenteditable id="per_cost"> </td>
<td contenteditable id="cost"> </td>
<td> <button id="Addbtn" type= "button" class="btn btn-success btn-xs" id="add" disabled> Add </button> </td> 
</tr>

@foreach($data as $item)
<tr>
    <td class = "column_name"  style="display:none;" data-column_name ="id"  id ="{{$item->id}}"> {{$item->id}}</td>
    <td class = "column_name"   data-column_name ="channel_id"  id ="{{$item->id}}" > 
        <select class="form-control" disabled >
            {!! getList($dsChannels,$item->channel_id)!!}
        </select>   
    </td>
    <td contenteditable class = "column_name" data-column_name ="retail_price"  id ="{{$item->id}}">{{$item->retail_price}} </td>
    <td contenteditable class = "column_name" data-column_name ="per_cost"  id ="{{$item->id}}">{{$item->per_cost}} </td>
    <td  class = "column_name" data-column_name ="cost"  id ="{{$item->id}}"> {{$item->cost}}</td>
    <td> <button type= "button" class="btn btn-danger btn-xs" id="{{$item->id}}" disabled> Delete </button> 
    </td>
</tr>
@endforeach

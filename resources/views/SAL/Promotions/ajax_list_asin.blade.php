<tr>
<td  id="id_add" style="display:none;"></td>
<td  id="channel_name_add" contenteditable > 
    <select class="form-control" name="channel_name_select" id="channel_name_select" >
        {!! getList($dsChannels,0)!!}
    </select>
</td>
<td  id="channel_id_add" contenteditable style="display:none;" > </td>

<td  id="retail_price_add" disabled> </td>
<td  id="per_cost_add" disabled> </td>
<td  id="cost_add" disabled> </td>

<td contenteditable id="some_fee_add" style="display:none;"> </td>
<td contenteditable id="per_other_fee_add" style="display:none;"> </td>

<td id="est_profit_add" disable> </td>
<td> <button id="Addbtn" type= "button" class="btn btn-success btn-xs" id="add" > Add </button> </td> 
</tr>

@foreach($data as $item)
<tr>
    <td class = "column_name"   data-column_name ="id"  id ="{{$item->id}}" style="display:none;" >{{$item->id}}</td>
    <td class = "column_name"   data-column_name ="channel_name"  id ="{{$item->id}}">
        <select class="form-control" disabled >
            {!! getList($dsChannels,$item->channel_id)!!}
        </select>   
    </td>
    <td class = "column_name"   data-column_name ="channel_id"  id ="{{$item->id}}" style="display:none;">{{$item->channel_id}}</td>
    <td contenteditable class = "column_name" data-column_name ="retail_price"  id ="{{$item->id}}">{{$item->retail_price}}</td>
    <td contenteditable class = "column_name" data-column_name ="per_cost"  id ="{{$item->id}}">{{$item->per_cost}}</td>
    <td  class = "column_name" data-column_name ="cost"  id ="{{$item->id}}" disabled> {{$item->cost}}</td>
    
    <td  class = "column_name" data-column_name ="some_fee"  id ="{{$item->id}}" style="display:none;"> {{$item->some_fee}}</td>
    <td  class = "column_name" data-column_name ="per_other_fee"  id ="{{$item->id}}" style="display:none;"> {{$item->per_other_fee}}</td>
    
    <td  class = "column_name" data-column_name ="est_profit"  id ="{{$item->id}}" disabled>{{$item->est_profit}}</td>
    <td> <button type= "button" class="btn btn-danger btn-xs" id="{{$item->id}}" disabled> Delete </button> 
    </td>
</tr>
@endforeach

@if (isset($dsRolePermissions))
    @foreach ($dsRolePermissions as $dt)
        <tr role="row" data-id="{{$dt->id}}">
            <td class="column_name" data-column_name ="id" id= "{{$dt->id}}" >{{$dt->id}}</td>
            <td class="column_name" data-column_name ="role_id" id= "{{$dt->id}}" >{{$dt->role_id}}</td>
            <td class="column_name" data-column_name ="action_id" id= "{{$dt->id}}">{{$dt->action_id}}</td>
            <td class="column_name" data-column_name ="name"  id= "{{$dt->id}}">{{$dt->name}} </td>
            <td contenteditable class = "column_name" data-column_name ="is_active"  id ="{{$dt->id}}"> 
                <input class="form-control" type="checkbox" id= "{{$dt->id}}"  name="is_active"  value="{{$dt->is_active}}" {{$dt->is_active?'checked':' '}}>
            </td>
        </tr>
    @endforeach
@endif
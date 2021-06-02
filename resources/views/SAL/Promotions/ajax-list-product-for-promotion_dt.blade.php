@if (isset($product))
    <tr role="row" data-id="{{$product->id}}">
        <td class="text-center seq">{{ $seq }}</td>
       
        <td class="text-center" style="display:none;">{{$product->id}}</td>
        <td class="serial">{{$product->asin}}</td>
        <td class="product_name">{{$product->title}}  </td> 
        <td>    
         <input  class="form-control" type="number"  id="per_funding" name="per_funding"  >
        </td>
        <td>    
         <input class="form-control"  type="number"  id="funding" name="funding">
        </td>
        <td>    
         <input class="form-control"  type="number"  id="unit_sold"  name="unit_sold" >
        </td>
        <td>    
         <input class="form-control"  type="number"  id="amount_spent" name ="amount_spent" >
        </td>
        <td>    
         <input class="form-control revenue"  type="number"  id="revenue"  name ="revenue">
        </td>
        <td class="text-center"><i class="fa fa-trash del-pro-order" ></i></td>
    </tr>
@endif


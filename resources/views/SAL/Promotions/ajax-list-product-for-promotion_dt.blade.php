@if (isset($product))
    <tr role="row" data-id="{{$product->id}}">
        <td class="text-center">{{$product->id}}</td>
        <td class="serial">{{$product->sku}}</td>
        <td class="product_name">{{$product->name}}  </td> 
        <td>    
         <input type="number"  id="per_funding"   >
        </td>
        <td>    
         <input type="number"  id="funding"  >
        </td>
        <td>    
         <input type="number"  id="UnitSold"  >
        </td>
        <td>    
         <input type="number"  id="AmoutnSpend"  >
        </td>
        <td>    
         <input type="number"  id="Revenue" >
        </td>
        <td class="text-center"><i class="fa fa-trash del-pro-order" ></i></td>
    </tr>
@endif
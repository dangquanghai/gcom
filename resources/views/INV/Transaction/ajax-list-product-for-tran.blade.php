@if (isset($product))
    <tr role="row" data-id="{{$product->id}}">
        <td class="text-center seq">{{ $seq }}</td>
        <td class="text-center">{{$product->id}}</td>
        <td class="serial">{{$product->sku}}</td>
        <td class="product_name">{{$product->name}}
            <div class="note_toggle">*Note</div>
            <input type="text" class="form-control note_product" placeholder="Note" value="" style="display:none;">
        </td>
        <td class="text-center qty">
            <input type="number" min=1 id="quantity_product"  class="txtNumber form-control quantity_product text-center" value="1"></td>
        <td class="text-center output price">
            <input type="text"  class="form-control txtMoney number_controll text-center price-order price-{{$product->id}}" data-origin="" value=""></td>
        <td data-origin="0" class="total-money number_controll total-money-{{$product->id}}">0</td>
        <td class="text-center"><i class="fa fa-trash del-pro-order" ></i></td>
    </tr>
@endif
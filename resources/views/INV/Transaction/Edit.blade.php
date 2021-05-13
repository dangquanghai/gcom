@extends('layouts.admin')
@section('content')
    <div class="col-md-12">
        <div class="alert alert-dange ajax-error" role="alert">
            <span style="font-weight: bold; font-size: 18px;">Thông báo!</span><br>
            <div class="ajax-error-ct"></div>
        </div>
        <div class="alert ajax-success" role="alert" style="width: 350px;background: rgba(92,130,79,0.9); display:none; color: #fff;"><span
                style="font-weight: bold; font-size: 18px;">Thông báo!</span>
            <br>
            <div class="ajax-success-ct"></div>
        </div>
        <!-- general form elements -->
        <div class="card card-primary">
            <div class="card-header">
                <h3 class="card-title">Import master </h3>
            </div>
            <!-- /.card-header -->
            <div class="card-body">
                <div class="row">
                    <div class="col-md-2">
                        <div class="form-group">
                            <label for="no">No</label>
                            <input type="text" class="form-control" name="no" id="no" value="{{ $tran->no }}">
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="form-group">
                            <label for="the_date">The Date</label>
                            <input type="date" class="form-control" name="the_date" id="the_date" value="{{ $tran->the_date }}">
                        </div>
                    </div>
                    <div class="col-sm-2">
                        <div class="form-group">
                            <label for="vendor_id">Vendor</label>
                            <select class="form-control" name="vendor_id" id="vendor_id" >
                                {!! getList($Vendors,$tran->vendor_id)!!}
                            </select>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-sm-12">
                        <div class="form-group">
                            <label for="description">Description</label>
                            <input type="text" class="form-control" name="description" id="description" value="{{ $tran->description }}">
                        </div>
                    </div>
                </div>
        </div>
        <div class="card-footer">
        </div>
        <div class="card card-primary">
            <div class="card-header">
                <h3 class="card-title">Import Detail </h3>
                <div class="card-tools">
                    <div class="input-group" style="height: 35px;width:550px">
                        <input type="text" id="search-pro-box" name="search"
                            placeholder="Input SKU or Product name"
                            class="form-control"
                            style="height: 35px;border-radius: 15px 0 0 15px;border-right: 0;background-color: #f2f4f6;"
                        >
                        <div class="input-group-append">
                            <span class="input-group-text"
                                style="background-color: #f2f4f6;border-left: 0px;border-radius: 0px 15px 15px 0px;">
                                <i class="fas fa-search"></i>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="card-body">
                    <table
                        class="table table-head-fixed table-hover table-bordered table-striped text-nowrap"
                        id="table_item_trans">
                        <thead>
                            <tr>
                                <th style="width:10px;text-align:center;">#</th>
                                <th style="width:20px;">Product ID</th>
                                <th style="width:150px;">SKU</th>
                                <th>Product Name</th>
                                <th style="width:150px;">Quantity</th>
                                <th style="width:150px;">Price</th>
                                <th style="width:150px;">Amount</th>
                                <th style="width:50px;">#</th>
                            </tr>
                        </thead>
                        <tbody class="list_products" id="list_products">
                            @php
                            $total=0;
                            $seq=1;
                        @endphp
                        @foreach ($tran->TransactionDetails as $product)
                            <tr role="row" data-id="{{$product->product_id}}">
                                <td class="text-center seq">{{ $seq }}</td>
                                <td class="text-center">{{$product->product_id}}</td>
                                <td class="serial">{{$product->products->sku}}</td>
                                <td class="product_name">{{$product->products->name}}
                                    <div class="note_toggle">*Note</div>
                                    <input type="text" class="form-control note_product" placeholder="Note" value="{{ $product->note }}" style="display:none;">
                                </td>
                                <td class="text-center qty">
                                    <input type="number" min= 1 id="quantity_product"  class="txtNumber form-control quantity_product text-center" value="{{ $product->quantity }}"></td>
                                <td class="text-center output price">
                                    <input type="text"  class="form-control txtMoney number_controll text-center price-order price-{{$product->product_id}}" data-origin="" value="{{ $product->price }}"></td>
                                <td data-origin="0" class="total-money number_controll total-money-{{$product->product_id}}">{{ $product->amount }}</td>

                                <td class="text-center"><i title="Xóa dòng này" class="fa fa-trash del-pro-order"></i></td>
                            </tr>
                            @php
                                $total +=($product->quantity)*($product->price);
                                $seq +=1;
                            @endphp
                        @endforeach
                        </tbody>
                        <tfoot>
                            <tr>
                                <td colspan="6" style="text-align: right;font-weight: bold;">Total:</td>
                                <td colspan="2"><span id="total" class="total">{{ $total }}</span></td>
                            </tr>
                        </tfoot>
                    </table>
            </div>
            <!-- /.card-body -->
            <div class="card-footer">
                <a title="Quay ra" href="{{ route('Transaction.index') }}" class="btn btn-primary">
                    <i class="fas fa-arrow-left"></i></i> Back
                </a>
            <button title="Lưu lại thay đổi" class="btn btn-primary btn-save" onclick="update_import({{ $tran->id }})">
                    <i class=" fa fa-save"></i>
            </button>
            </div>
        </div>
        <!-- /.card -->
    </div>
@endsection
@section('scripts')
<script src="{{asset('plugins/jquery-ui/jquery-ui.min.js')}}"></script>
<script src="{{asset('js/transaction.js') }}"></script>
<script>
    $(document).ready(function() {
    $('.vendor_id').select2({
        theme: 'bootstrap4'
        })
    });
</script>
@endsection

var _url = $('base').attr('href');
var CSRF_TOKEN = $("meta[name='csrf-token']").attr("content");

function hotkey(e) {
    var keycode = (e.keyCode ?e.keyCode :e.which);
    //F2
    if (keycode == '113') {
        $('#search-pro-box').focus();
        console.log('click F2');
    }
    //F4
    if (keycode == '115') {
        console.log('click F4');
    }
    //F7
    if (keycode == '118') {
        console.log('click F7');
    }
    //F8
    if (keycode == '119') {
        console.log('click F8');
    }
    //F9
    if (keycode == '120') {
        console.log('click F9');
    }
    //F10
    if (keycode == '121') {
        console.log('click F10');
    }
}

function _ajax($param) {
    $.ajax({
        url: $param.url,
        type: $param.type,
        data: $param.data,
        async: true,
        success: $param.callback
    });
}
/**
 * autocomplete theo jquery-ui
*/
function search_pro_autocomplete()
{
    $(document).ready(function () {
        $("#search-pro-box").autocomplete({
            minLength: 1,
            source: '/ajax_pro/search',// trả về danh sách product theo điều kiện đưa vào tìm
            focus: function (event, ui){
                $("#search-pro-box").val(ui.item.sku + " - " + ui.item.url_img + ui.item.name);
                return false;
            },
            select: function (event, ui) {
                select_product(ui.item.id);
                $("#search-pro-box").val('');
                return false;
            }
        }).keyup(function (e) {
            if (e.which === 13) //trường hợp nhập đúng số SKU và enter hoặc dùng máy đọc mã vạch với đk mã vạch là số SKU sp và máy đọc mã vạch có sự kiện enter
            {
                enter_event_search_pro_box();
                $("#search-pro-box").val('');
                $(".ui-menu-item").hide();// ẩn danh sách chứa các product
            }
        })
            $("#search-pro-box").autocomplete().data("uiAutocomplete")._renderItem= function (ul, item) {
            return $("<li>")
            .append("<div>" + item.sku + "-" + item.name + "</div>")
            .appendTo(ul);
        };
    });
}
/**
 * su kien enter trong textbox search thì sẽ gọi hàm này
 */
function enter_event_search_pro_box()
{
    $sku = $("#search-pro-box").val();//sku
    if($sku !='')
    {
        var $param = {
            'type': 'POST',
            'url': '/ajax_pro/check_sku/' + $sku,
            'data': {
                '_token': CSRF_TOKEN
            },
            'callback': function (data) {
                if (data > 0) {
                    select_product(data);
                    $(this).val('');
                }
            }
        };
        _ajax($param);
    }
}
/**
 * chọn sản phẩm show lên table sản phẩm
 * @param id product
 */
function select_product(id) {
  
    if ($('tbody#list_products tr').length != 0) {//trường hợp đã chọn ít nhất 1 sp vào danh sách rồi
        var flag = 0;//muốn tách dòng không
        // truong hop ko tach dong khi chon cung sp
        $('tbody#list_products tr').each(function ()
        {
            var id_temp = $(this).attr('data-id');
            if (id == id_temp)
            {
                var value_input = $(this).find('input.quantity_product');
                value_input.val(parseInt(value_input.val()) + 1);
                flag = 1;
                //cms_load_infor_order();
                return false;
            }
        });
        if (flag == 0) {
            var seq = parseInt($('td.seq').last().text()) + 1;
            var param = {
                'type': 'POST',
                'url': '/ajax_pro/select',
                'data': {
                    'id': id,
                    'seq': seq,
                    '_token': CSRF_TOKEN
                    },
                'callback': function (data) {
                   $('#list_products').append(data);
                }
            };
            _ajax(param);
        }
    }
    else {
        var param = {
            'type': 'POST',
            'url': '/ajax_pro/select',
            'data': {
                'id': id,
                'seq': 1,
                '_token': CSRF_TOKEN
            },
            'callback': function (data) {
                $('#list_products').append(data);
            }
        };
        _ajax(param);
    }
    $('#search-pro-box').val('');
    $('#search-pro-result').hide();
}
/**
 * thông tin đơn hàng
 */
$(document).on('ready ajaxComplete', function () {
    calc_infor_import();
});
function calc_infor_import() {
    $total_money = 0;
    $('tbody#list_products tr').each(function () {
        $quantity_product = parseInt($(this).find('input.quantity_product').val());
        $price = decode_currency_format($(this).find('input.price-order').val());
        $total = $price * $quantity_product;
        $total_money += $total;
        $(this).find('td.total-money').text(encode_currency_format($total));
    });

    $('#total').text(encode_currency_format($total_money));
}
//khi thay doi số luong thi tinh lai tien
$(document).on('change', '.quantity_product', function() {
    calc_infor_import();
});
//khi thay doi gia thi tinh lại tien
$(document).on('change', '.price-order', function() {
    $(this).val(decode_currency_format($(this).val()));
    $(this).val(encode_currency_format($(this).val()));
    calc_infor_import();
});
//khi nhap xong gia thi format lai kiểu có dấu , hàng nghìn
$(document).on('keyup', '.price-order', function(e) {
    if(e.keyCode == 13)
    {
        $(this).val(decode_currency_format($(this).val()));
        $(this).val(encode_currency_format($(this).val()));
        calc_infor_import();
    }
});
function encode_currency_format(obs) {
    return obs.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
}

function decode_currency_format(obs) {
    if (obs == '')
        return 0;
    else
        return parseInt(obs.replace(",", ''));
}
$(document).on('click','.note_toggle', function () {
    if ($(this).parent().find('.note_product').is(':visible'))
        $(this).parent().find('.note_product').fadeIn().delay(200).fadeOut('slow');
    else
        $(this).parent().find('.note_product').fadeOut().delay(200).fadeIn('slow');
});
/**
 * Xóa 1 dòng sản phẩm khỏi List
 */
function del_pro_tran() {
    $(document).on('click', '.del-pro-order', function () {
        var conf = confirm('Are you sure?');
        if(conf)
        {
            $(this).parents('tr').remove();
            calc_infor_import();
            var seq = 0;
            $('tbody#list_products tr').each(function () {
                seq += 1;
                value_input = $(this).find('td.seq').text(seq);
            });
        }
    });

}
/**
 * Lưu transaction import
 * @param
 */
function save_import(type) {
    if ($('tbody#list_products tr').length == 0) {
        $('.ajax-error-ct').html('Xin vui lòng chọn ít nhất 1 sản phẩm. Xin cảm ơn!').parent().fadeIn().delay(2000).fadeOut('slow');
    } else {
        $('.btn-save').attr("disabled","disabled");
        $no = $('#no').val();
        $vendor_id = $('#vendor_id').val();
        $date = $('#the_date').val();
        $description = $('#description').val();
        $detail = [];
        $('tbody#list_products tr').each(function () {
            $price = decode_currency_format($(this).find('input.price-order').val());
            $product_id = $(this).attr('data-id');
            $quantity = $(this).find('input.quantity_product').val();
            $note_item = $(this).find('input.note_product').val();
            $detail.push(
                {product_id: $product_id, quantity: $quantity, price: $price,note:$note_item}
            );
        });
        $data = {
            'data': {
                'no':$no,
                'vendor_id': $vendor_id,
                'the_date': $date,
                'description': $description,
                'detail_input': $detail,
                'type': type
            },
            '_token': CSRF_TOKEN
        };
        var $param = {
            'type': 'POST',
            'url': '/Transaction',
            'data': $data,
            'callback': function (data) {
                if (data == '0') {
                    $('.ajax-error-ct').html('Oops! This system is errors! please try again.').parent().fadeIn().delay(1000).fadeOut('slow');
                } else {
                        $('.ajax-success-ct').html('Đã lưu thành công phiếu nhập.').parent().fadeIn().delay(1000).fadeOut('slow');
                    }
                    $('.btn-save').removeAttr("disabled");
                }
        };
        _ajax($param);
    }
}
/**
 * update transaction import
 * @param
 */
function update_import($transaction_id = "") {
    if ($('tbody#list_products tr').length == 0) {
        $('.ajax-error-ct').html('Xin vui lòng chọn ít nhất 1 sản phẩm. Xin cảm ơn!').parent().fadeIn().delay(2000).fadeOut('slow');
    } else {
        $('.btn-save').attr("disabled","disabled");
        $no = $('#no').val();
        $vendor_id = $('#vendor_id').val();
        $date = $('#the_date').val();
        $description = $('#description').val();
        $detail = [];
        $('tbody#list_products tr').each(function () {
            $price = decode_currency_format($(this).find('input.price-order').val());
            $product_id = $(this).attr('data-id');
            $quantity = $(this).find('input.quantity_product').val();
            $note_item = $(this).find('input.note_product').val();
            $detail.push(
                {product_id: $product_id, quantity: $quantity, price: $price,note:$note_item}
            );
        });
        $data = {
            'data': {
                'no':$no,
                'vendor_id': $vendor_id,
                'the_date': $date,
                'description': $description,
                'detail_input': $detail,
                'type': ''
            },
            '_token': CSRF_TOKEN
        };
        var $param = {
            'type': 'PUT',
            'url': '/Transaction/'+$transaction_id,
            'data': $data,
            'callback': function (data) {
                if (data == '0') {
                    $('.ajax-error-ct').html('Oops! This system is errors! please try again.').parent().fadeIn().delay(1000).fadeOut('slow');
                } else {
                        $('.ajax-success-ct').html('Đã cập nhật thành công phiếu nhập.').parent().fadeIn().delay(1000).fadeOut('slow');
                    }
                    $('.btn-save').removeAttr("disabled");
                }
        };
        _ajax($param);
    }
}

$(document).ready(function () {
    "use strict";
    search_pro_autocomplete();
    del_pro_tran();
    // _search_product_box_editValuChange();
    //huy bo lenh khi nha enter trong control thì submit form
    $(window).keydown(function(event){
        if(event.keyCode == 13) {
          event.preventDefault();
          return false;
        }
      });
});
document.addEventListener('keyup', hotkey, false);
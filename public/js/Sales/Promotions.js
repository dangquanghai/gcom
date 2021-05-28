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
    $("#search-pro-box").autocomplete({
    minLength: 1,
    source: '/ProductNew/search',// trả về danh sách product theo điều kiện đưa vào tìm
    focus: function (event, ui){
        $("#search-pro-box").val(ui.item.asin + " - " + ui.item.title);
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
    .append("<div>" + item.asin + "-" + item.title + "</div>")
    .appendTo(ul);
    };

   // console.log(gChannelID);
    
}
/**
 * su kien enter trong textbox search thì sẽ gọi hàm này
 */
function enter_event_search_pro_box()
{
    $asin = $("#search-pro-box").val();//sku
    if($asin !='')
    {
        var $param = {
            'type': 'POST',
            'url': '/ProductNew/check_asin/' + $asin,
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
  
    if ($('tbody#list_promotion_dt tr').length != 0) {//trường hợp đã chọn ít nhất 1 sp vào danh sách rồi
        var flag = 0;//muốn tách dòng không
        // truong hop ko tach dong khi chon cung sp
        $('tbody#list_promotion_dt tr').each(function ()
        {
            var id_temp = $(this).attr('data-id');
            if (id == id_temp)
            {
                var value_input = $(this).find('input.funding');
                value_input.val(parseInt(value_input.val()) + 1);
                flag = 1;
                return false;
            }
        });
        if (flag == 0) {
            var seq = parseInt($('td.seq').last().text()) + 1;
            var param = {
                'type': 'POST',
                'url': '/ProductNew/select',
                'data': {
                    'id': id,
                    'seq': seq,
                    '_token': CSRF_TOKEN
                    },
                'callback': function (data) {
                   $('#list_promotion_dt').append(data);
                }
            };
            _ajax(param);
        }
    }
    else {
        var param = {
            'type': 'POST',
            'url': '/ProductNew/select',
            'data': {
                'id': id,
                'seq': 1,
                '_token': CSRF_TOKEN
            },
            'callback': function (data) {
                $('#list_promotion_dt').append(data);
            }
        };
        _ajax(param);
    }
    $('#search-pro-box').val('');
    $('#search-pro-result').hide();
}

/**
 * Xóa 1 dòng sản phẩm khỏi List
 */
 function del_pro_tran() {
    $(document).on('click', '.del-pro-order', function () {
        var conf = confirm('Are you sure?');
        if(conf)
        {
            $(this).parents('tr').remove();
            //calc_infor_import();
            var seq = 0;
            $('tbody#list_products tr').each(function () {
                seq += 1;
                value_input = $(this).find('td.seq').text(seq);
            });
        }
    });
}

function save_promotion() {
    var fd = new Date();
    var td = new Date();
    
    if ($('tbody#list_promotion_dt tr').length == 0) {
        $('.ajax-error-ct').html('Xin vui lòng chọn ít nhất 1 sản phẩm. Xin cảm ơn!').parent().fadeIn().delay(2000).fadeOut('slow');
    }else if( $('#promotion_no').val() =='' ){
        $('.ajax-error-ct').html('Xin vui lòng điền Promotion id').parent().fadeIn().delay(2000).fadeOut('slow');
    }else if( $('#from_date').val()==''){
            $('.ajax-error-ct').html('Xin vui lòng điền from date').parent().fadeIn().delay(2000).fadeOut('slow');
    }else if( $('#to_date').val()==''){
        $('.ajax-error-ct').html('Xin vui lòng điền to date').parent().fadeIn().delay(2000).fadeOut('slow');
    }/*
    else if( $('#from_date').val() != '' && $('#to_date').val() != '' ){
      
        fd = $('#from_date').val();
        td = $('#to_date').val();
        if( fd > td) {  $('.ajax-error-ct').html('From Date phải <= To date').parent().fadeIn().delay(2000).fadeOut('slow');  }
    }*/
    else {
        $('.btn-save').attr("disabled","disabled");
        $promotion_no = $('#promotion_no').val();
        $promotion_type = $('#promotion_type').val();
        $promotion_status = $('#promotion_status').val();
        $channel_id = $('#channel_id').val();
        $from_date = $('#from_date').val();
        $to_date = $('#to_date').val();
        $detail = [];
        $('tbody#list_promotion_dt tr').each(function () {
            $product_id = $(this).attr('data-id');
            $per_funding = $(this).find("[name ='per_funding']").val();
            if($per_funding==''){$per_funding=0;}
            $funding =  $(this).find("[name ='funding']").val();
            if($funding==''){$funding=0;}
            $unit_sold =  $(this).find("[name ='unit_sold']").val();
            if($unit_sold==''){$unit_sold=0;}
            $amount_spent =  $(this).find("[name='amount_spent']").val();
            if($amount_spent==''){$amount_spent=0;}
            $revenue =$(this).find("[name='revenue']").val();
            if($revenue==''){$revenue=0;}

            $detail.push(
                {product_id: $product_id, per_funding: $per_funding, funding: $funding,unit_sold:$unit_sold,amount_spent:$amount_spent,revenue:$revenue}
            );
        });
        $data = {
            'data': {
                'promotion_no':$promotion_no,
                'promotion_type':$promotion_type,
                'promotion_status':$promotion_status,
                'channel_id': $channel_id,
                'from_date':$from_date,
                'to_date':$to_date,
                'detail_input': $detail
            },
            '_token': CSRF_TOKEN
        };
        var $param = {
            'type': 'POST',
            'url': '/Promotion',
            'data': $data,
            'callback': function (data) {
                if (data == '0') {
                    $('.ajax-error-ct').html('KHông thể lưu').parent().fadeIn().delay(1000).fadeOut('slow');
                } else {
                        $('.ajax-success-ct').html('Đã lưu thành công .').parent().fadeIn().delay(1000).fadeOut('slow');
                    }
                   // $('.btn-save').removeAttr("disabled");
                 
                }

        };
        _ajax($param);
    }
}


// Update
function update_promotion($id = "") {
    if ($('tbody#list_promotion_dt tr').length == 0) {
        $('.ajax-error-ct').html('Xin vui lòng chọn ít nhất 1 sản phẩm. Xin cảm ơn!').parent().fadeIn().delay(2000).fadeOut('slow');
    } else {
        $('.btn-save').attr("disabled","disabled");
        $promotion_no = $('#promotion_no').val();
        $promotion_type = $('#promotion_type').val();
        $promotion_status = $('#promotion_status').val();
        $channel_id = $('#channel_id').val();
        $from_date = $('#from_date').val();
        $to_date = $('#to_date').val();
        $detail = [];
        $('tbody#list_promotion_dt tr').each(function () {
            $product_id = $(this).attr('data-id');
            $per_funding = $(this).find("[name ='per_funding']").val();
            $funding =  $(this).find("[name ='funding']").val();
            $unit_sold =  $(this).find("[name ='unit_sold']").val();
            $amount_spent =  $(this).find("[name='amount_spent']").val();
            $revenue =$(this).find("[name='revenue']").val();
            $detail.push(
                {product_id: $product_id, per_funding: $per_funding, funding: $funding,unit_sold:$unit_sold,amount_spent:$amount_spent,revenue:$revenue}
            );
        });
        $data = {
            'data': {
                'promotion_no':$promotion_no,
                'promotion_type':$promotion_type,
                'promotion_status':$promotion_status,
                'channel_id': $channel_id,
                'from_date':$from_date,
                'to_date':$to_date,
                'detail_input': $detail
            },
            '_token': CSRF_TOKEN
        };
        var $param = {
            'type': 'PUT',
            'url': '/Promotion/'+ $id,
            'data': $data,
            'callback': function (data) {
                if (data == '0') {
                    $('.ajax-error-ct').html('Oops! This system is errors! please try again.').parent().fadeIn().delay(1000).fadeOut('slow');
                } else {
                        $('.ajax-success-ct').html('Đã cập nhật thành công phiếu nhập.').parent().fadeIn().delay(1000).fadeOut('slow');
                    }
                    //$('.btn-save').removeAttr("disabled");
                }
        };
        _ajax($param);
    }
}


$(document).ready(function () {
    "use strict";// Thiết lập chế độ dòng lệnh nghiêm ngặt
    var gChannelID = 2;
    search_pro_autocomplete();
    del_pro_tran();
    $(document).on('change','#channel_id',function(){
        gChannelID =   $(this).val();
        //console.log(gChannelID);
    });
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
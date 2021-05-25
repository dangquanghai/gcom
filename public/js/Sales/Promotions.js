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
            source: '/ProductNew/select',// trả về danh sách product theo điều kiện đưa vào tìm
            focus: function (event, ui){
                $("#search-pro-box").val(ui.item.sku + " - " + ui.item.title);
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
            .append("<div>" + item.sku + "-" + item.title + "</div>")
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
            'url': '/ProductNew/check_sku/' + $sku,
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
                //var value_input = $(this).find('input.funding');
                //value_input.val(parseInt(value_input.val()) + 1);
                flag = 1;
                return false;
            }
        });
        if (flag == 0) {
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

$(document).ready(function () {
    "use strict";// Thiết lập chế độ dòng lệnh nghiêm ngặt
    search_pro_autocomplete();
    // del_pro_tran();
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
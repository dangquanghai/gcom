$('.btnAddCondition').click(function(){
    $column = $('#column option:selected').text();
    $operator = $('#operator option:selected').text();
    $value = $('#value').val();
    if($operator == 'like' || $operator == 'not like')
    {
      $value ='%'+ $value + '%';
    }
    else if(!isNumeric($value))
    {
      $value = "'"+$value+"'";
     // alert( $value);
    }
    //$strReturn = $(this).text()+"("+$column+" "+ $operator + " " +(!isNumeric($value)?"'"+$value+"'":$value)+")";
     $strReturn = $(this).text()+"("+$column+" "+ $operator + " " + $value +")";

    $strCondition = $('#conditions').val();
    if($strCondition.trim()!='')
       //$('#conditions').html($strCondition +'\n'+$strReturn);
       $('#conditions').val($strCondition + $strReturn);
    else
      $('#conditions').val($strReturn);

    return false;// loại bỏ hành đông default tiếp theo
})
function isNumeric(str) {
if (typeof str != "string") return false
return !isNaN(str) && !isNaN(parseFloat(str))
}

var li = $('.nav-sidebar').find('li a');
$.each(li,function(i,val){
    if($(val).attr('href') == window.location.href)
    {
        $(val).addClass('active');
        $(val).parent().parent().css({display:'block'});
        if($(val).parent().parents('li').length)// len >0
        {
           $($(val).parent().parents('li')[0]).addClass('menu-open');
        }
    }
})
function showMessage(messageType, message) {
    toastr.clear();
    toastr.options = {
        closeButton: true,
        positionClass: 'toast-bottom-right',
        progressBar: true,
        onclick: null,
        showDuration: 1000,
        hideDuration: 5000,
        timeOut: 5000,
        extendedTimeOut: 1000,
        showEasing: 'swing',
        hideEasing: 'linear',
        showMethod: 'fadeIn',
        hideMethod: 'fadeOut',
        closeHtml: '<button><i class="icon-off"></i></button>',
    };
    let messageHeader = '';

    switch (messageType) {
        case 'error':
            messageHeader = "Lỗi";
            break;
        case 'success':
            messageHeader = "Thành Công";
            break;
    }
    toastr[messageType](message, messageHeader);
}

var li = $('.nav-sidebar').find('li a');
$.each(li,function(i,val){
    if($(val).attr('href')==window.location.href)
    {
        $(val).addClass('active');
        $(val).parent().parent().css({display:'block'});
        if($(val).parent().parents('li').length)
        {
            $($(val).parent().parents('li')[0]).addClass('menu-open');
        }
    }
})
function showMessage(messageType, message) {
    toastr.clear();

    toastr.options = {
        closeButton: true,
        positionClass: 'toast-bottom-right',
        progressBar: true,
        onclick: null,
        showDuration: 1000,
        hideDuration: 5000,
        timeOut: 5000,
        extendedTimeOut: 1000,
        showEasing: 'swing',
        hideEasing: 'linear',
        showMethod: 'fadeIn',
        hideMethod: 'fadeOut',
        closeHtml: '<button><i class="icon-off"></i></button>',
    };

    let messageHeader = '';

    switch (messageType) {
        case 'error':
            messageHeader = "Lỗi";
            break;
        case 'success':
            messageHeader = "Thành Công";
            break;
    }
    toastr[messageType](message, messageHeader);
}
//chỉ cho nhập số
$(document).on('keydown', '.number_controll', function (e) {
    // Allow: backspace, delete, tab, escape, enter and .
    if ($.inArray(e.keyCode, [46,8,9,27,13,110,190,188]) !== -1 ||
        // Allow: Ctrl+A, Command+A
        (e.keyCode == 65 && (e.ctrlKey === true || e.metaKey === true)) ||
        // Allow: home, end, left, right, down, up
        (e.keyCode >= 35 && e.keyCode <= 40)) {
        // let it happen, don't do anything
        return;
    }
    // Ensure that it is a number and stop the keypress
    if ((e.shiftKey || (e.keyCode < 48 || e.keyCode > 57)) && (e.keyCode < 96 || e.keyCode > 105)) {
        e.preventDefault();
    }
});
function EchoMsg(msg,auto,local,time=1000){
    $.alert({
        title: '温馨提示',
        content: msg,
        buttons: {
            okay: {
                text: '确认',
                btnClass: 'btn-blue'
            }
        }
    });
    if(auto){
        if(local){
            setTimeout("window.location.href='"+local+"'", time);
        }else{
            setTimeout("location.reload();", time);
        }
    }
}

function clear_cache(){
    $.post('clear_cache', {
    }, function (data, textStatus, xhr) {
        if (data.code == 1) {
            EchoMsg(data.msg, 1);
        } else {
            EchoMsg('error：' + data.msg, 1);
        }
    })
    .fail(function () {
        EchoMsg('请求错误，请稍候重试');
    })
    .always(function () {
        
    })
}

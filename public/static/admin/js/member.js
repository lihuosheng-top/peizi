/**
 * lihuosheng
 * @param id
 * @param status
 */

function doWithdraw(id, status){
    if(id <= 0){
        alert("id错误");
    }
    $.post("./do_withdraw", {id : id, status:status}, function(data){
        console.log(data);
        if(data.code == '0'){
            alert("操作成功");
            // location.reload();
        }else{
            alert("失败");
            alert(data.msg);
        }
    }, 'json');
}


function doDate(id,status){
    if( id <= 0){
        alert("id错误");
        return;
    }
    if(!window.confirm("确定审核通过？")){
        return;
    }
    $.post( "./bank_app", {id : id,status:status}, function(data){
        if(data.code == '0'){
            alert("审核成功");
            location.reload();
        }else{
            alert("审核失败");
            alert(data.msg);
        }
    }, 'json' );
}


function doAlipay(id,status){
    if( id <= 0){
        alert("id错误");
        return;
    }
    if(!window.confirm("确定审核通过？")){
        return;
    }
    $.post( "./alipay_examine_action", {id : id,status:status}, function(data){
        if(data.code == '0'){
            alert("审核成功");
            location.reload();
        }else{
            alert("审核失败");
            alert(data.msg);
        }
    }, 'json' );
}

function doWechat(id,status){
    if( id <= 0){
        alert("id错误");
        return;
    }
    if(!window.confirm("确定审核通过？")){
        return;
    }
    $.post( "./weichat_examine_action", {id : id,status:status}, function(data){
        if(data.code == '0'){
            alert("审核成功");
            location.reload();
        }else{
            alert("审核失败");
            alert(data.msg);
        }
    }, 'json' );
}


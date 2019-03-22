/**
 * lihuosheng
 * @param orderId
 */
function doLiquidation(orderId){
    if( orderId <= 0){
        return;
    }
    if(!window.confirm("确定平仓吗？")){
        return;
    }
    $.ajax({
        url: './liquidation',
        type: 'POST',
        dataType: 'JSON',
        async: false,
        data: {
            orderId : orderId
        },
        success: function(data){
            console.log(data);
            if(data.status ==1){
                alert(data.info);
                location.reload();
            }
            if(data.code ==0){
                alert(data.msg);
            }
        },
        error: function(data){
            alert('失败');
            // console.log(data);

        }
    })

    // $.post( "./liquidation", {orderId : orderId}, function(data){
    //     // console.log(data);
    //     if(data.data.code == '0'){
    //         alert("已平仓");
    //         location.reload();
    //     }else{
    //         alert('失败');
    //         alert(data.msg);
    //     }
    // }, 'json' );
}







$(function(){
    $("td").each(function(i, o){
        if(isFloat($(o).html())){
            var f = parseFloat( $(o).html() );
            $(o).html(f.toFixed(2));
        }
    });
});

function isFloat(c)
{
    if(!isNaN(c) && c.indexOf('.') > 0){
        return true;
    }

    return false;
}
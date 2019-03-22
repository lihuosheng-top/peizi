/**
 * Created by wo on 2017/7/26.
 */

function setStatus(id, th){
    $.post("./setStatus", {id:id}, function(data){
        if(data.code == '0'){
            $("tr>td:first-child").each(function(i, o){
                if($(o).html().trim() == id){
                    $(o).parent().remove();
                }
            });
        }
    }, 'json');
}

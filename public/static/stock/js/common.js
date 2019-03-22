// util js file include validate , time format etc...
var Util = {
    Validate : {
        phone : function (num) {
            var reg = /^(13|14|15|17|18)[0-9]{9}$/;
            return reg.test(parseInt(num));
        },
        email : function(email){
            var reg = /^[\w-\.]+@([\w-]+\.)+[a-z]{2,3}/g;
            return reg.test(parseInt(email));
        }
    }
};


$(function(){
    //回退按钮
    $(".v-header").on("click",".v-header-left",function (e) {
        if(e.target.tagName == "DIV") history.back();

    })


})
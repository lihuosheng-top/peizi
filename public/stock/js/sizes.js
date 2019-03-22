/**
 * Created by Administrator on 2016/8/21.
 */
(function(){
    var iWidth = document.documentElement.clientWidth;
    if(iWidth>640){
        document.getElementsByTagName('html')[0].style.fontSize=15+"px";
        document.getElementsByTagName('html')[0].style.width=720+"px";
        document.getElementsByTagName('html')[0].style.margin="0 auto";
    }else{
        document.getElementsByTagName('html')[0].style.fontSize = parseInt(iWidth / 25)>16?16+'px':parseInt(iWidth / 25)+'px';
    }
})();
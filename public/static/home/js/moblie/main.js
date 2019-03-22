/// <reference path="jquery-1.11.2.min.js" />
$(function () {

    //$("body").append('<a href="https://cs.ecqun.com/mobile/rand?id=4272575&scheme=0" target="_blank" ><img id="ec_cs_invitebtn" src="//www.staticec.com/cs/images/mobilecode_cn.png" /></a>');

    // $("body").append(' <script type="text/javascript"> var EC_CS_ID = 4272575; var EC_CS_SCHEME = 0; (function () { var ecscript = document.createElement("script"); ecscript.src = (document.location.protocol == "https:") ? "https://www.staticec.com/cs/track/mvisitor.js" : "http://www.staticec.com/cs/track/mvisitor.js"; var h = document.getElementsByTagName("head")[0]; h.appendChild(ecscript);  })();</script>');

    $("body").append('<div class="m-foot"><div class="nav-foot clearfix"><div class="nav-foot"><ul class="clearfix"><li id="default"><a href="../index.php" ><i class="ico ico-stock"></i> <span>首页</span></a></li><li id="Li1"><a href="capital.php?type=day"><i class="ico ico-deal"></i> <span>我要配资</span></a></li><li id="stock_pool"><a href="trading.php"><i class="ico ico-statics"></i> <span>我要交易</span></a></li><li id="user"><a href="../user_center.php"><i class="ico ico-account"></i> <span>账户中心</span></a></li></ul></div></div></div>');

    //菜单栏和是否登录
//  islogin();

    var winHight = $(window).height();
    $(".ng-nav-open").click(function () {
        $(this).addClass("hide");
        $(".ng-nav-close").removeClass("hide");
        $(".ng-nav-con").removeClass("hide").css({"height": winHight - 44}).show();
        $("body").css({"overflow": "hidden", "height": winHight});
    });
    $(".ng-nav-close").click(function () {
        $(this).addClass("hide");
        $(".ng-nav-open").removeClass("hide");
        $(".ng-nav-con").addClass("hide");
        $("body").removeAttr("style");
    });
    $(".font-dorange").click(function () {
        if ($(this).hasClass("icon-xuanze")) {
            $(this).removeClass("icon-xuanze").addClass("icon-yixuanze");
        }
        else {
            $(this).removeClass("icon-yixuanze").addClass("icon-xuanze");
        }
    });

    $(".img_index").click(function () {

        location.href = 'index.php'
    });

    //var _code = getParam('code');

    //if (_code != null || _code != "") {
    //    $.get("/tools/user_ajax.ashx", { action: "GetUser", code: _code }, function (data) {

    //    });
    //}

});

function Waiting() {
    this._waiting_div = document.createElement('div');//创建div
    this._waiting_div.className = 'postionfix w100p h100 left0 top0 fade-in_3 waiting';
    this._icon = document.createElement("img");//等待图标 this._icon = document.createElement("i");//等待图标
    //this._icon.className = 'mui-icon mui-icon-spinner-cycle mui-spin';
    this._icon.className = 'img-loading';
    this._waiting_div.appendChild(this._icon);
    this.flag = false;
}

Waiting.prototype.show = function () {
    document.body.appendChild(this._waiting_div);
    this.flag = true;
    // console.log('append')
};
Waiting.prototype.close = function () {
    document.body.removeChild(this._waiting_div);
    // console.log('remove');
    this.flag = false;
};

var waiting = new Waiting();

/*
window.alert = function(name){
    var iframe = document.createElement("IFRAME");
    iframe.style.display="none";
    iframe.setAttribute("src", 'data:text/plain');
    document.documentElement.appendChild(iframe);
    window.frames[0].window.alert(name);
    iframe.parentNode.removeChild(iframe);
};*/
function showStockLimitModal(id) {

    var winHight = $(window).height();

    $("#" + id).removeClass("hide");
    $("body").css({"overflow": "hidden", "height": winHight});
}

function closeStockLimitModal(id) {
    $("#" + id).addClass("hide");
    $("body").removeAttr("style");
}

function islogin() {

    $.get("/tools/user_ajax.ashx", {action: "islogin"}, function (data) {
        var _obj = eval('(' + data + ')');

        var _str = "";

        _str += '<header>';
        _str += '<a href="javascript:window.history.back();" id="back" style="background:url(/resources/images/back.png) center no-repeat; background-size:80%; height:100%; float:left; width:20px; display:block; margin-left:12px;" alt="返回"/></a>';
        _str += '<img src="/resources/images/35x35logo.png" class="img_index" alt="盈股在线在线配资首页" onclick=\'location.href="/index.html"\'></a>';
        // _str += ' <img class="ng-nav-open" src="/resources/images/nav_open.png" alt="盈股在线在线配资菜单" >';
        // _str += ' <img class="ng-nav-close hide" src="/resources/images/nav_close.png" alt="盈股在线在线配资
        //  _str += ' <a class="home" href="/index.html" >首页</a>';
        _str += ' </header>';

        // <!-- menu -->
        _str += ' <aside  class="ng-nav-con animated hide">';

        if (_obj.status == "y") {
            //<!-- 登录 -->
            _str += ' <div class="user-info ">';

            if (_obj.avator == "") {
                // <!-- 未设置头像 -->
                _str += ' <p>';
                _str += '<a href="/personcenter/personcenterIndex.html"><img src="/resources/images/defaultUserPhoto.png " alt="用户头像"></a>';
                _str += ' </p>';
            } else {
                var img = decodeURIComponent(_obj.avator);

                _str += ' <p>';
                _str += '<a href="/personcenter/personcenterIndex.html"><img src="' + img + '" alt="用户头像"></a>';
                _str += ' </p>';

            }
            _str += '</div>';

            //<!-- nav -->
            _str += ' <nav class="nav-wrap wrap">';
            _str += '<p>';
            _str += '<span style="display: inline-block;width: 35%;" onclick=\'location.href="/personcenter/personcenterIndex.html"\'><i class="niu-icon icon-yonghu"></i>个人中心</span>';
            _str += '<span class="clearfix" style="display: inline-block;width: 55%;float: right;">';
            _str += '<a title="盈股在线在线配资在线充值" href="/personcenter/drawing.html">提现</a>';
            _str += '<a title="盈股在线在线配资在线充值" href="/personcenter/pay.html">充值</a>';
            _str += '</span>';
            _str += '</p>';
            _str += '<a href="/trade/dayTrade.html"><p class="placeholder-height"><i class="niu-icon icon-chaopan"></i>我要配资</p></a>';
            _str += '<a href="/trade/freeTrade.html"><p><i class="niu-icon icon-jingji"></i>免息体验</p></a>';

            _str += '<a href="/suggest/suggest.html"><p class="placeholder-height"><i class="niu-icon icon-kefu"></i>联系客服</p></a>';
            _str += '<a href="https://cs.ecqun.com/mobile/rand?id=4272575&scheme=0" target="_blank"><p class=""><i class="niu-icon icon-kefu"></i>在线客服</p></a>';
            _str += '</nav>';

        } else {

            // <!-- 未登录 -->
            _str += '<div class="ng-scope ">';
            _str += ' <a id="JS-register" href="/register.html">注册</a>';
            _str += ' <a id="JS-login" href="/login/login.html">登录</a>';
            _str += ' </div>';

            //<!-- nav -->
            _str += ' <nav class="nav-wrap wrap">';
            _str += '<p>';
            _str += '<span style="display: inline-block;width: 35%;" onclick=\'location.href="/login/login.html"\'><i class="niu-icon icon-yonghu"></i>个人中心</span>';
            _str += '<span class="clearfix" style="display: inline-block;width: 55%;float: right;">';
            _str += '<a title="盈股在线在线配资在线充值" href="/login/login.html">提现</a>';
            _str += '<a title="盈股在线在线配资在线充值"  href="/login/login.html">充值</a>';
            _str += '</span>';
            _str += '</p>';
            _str += '<a href="/trade/dayTrade.html"><p class="placeholder-height"><i class="niu-icon icon-chaopan"></i>我要配资</p></a>';
            _str += '<a href="/trade/freeTrade.html"><p><i class="niu-icon icon-jingji"></i>免息体验</p></a>';

            _str += '<a href="/suggest/suggest.html"><p class="placeholder-height"><i class="niu-icon icon-kefu"></i>联系客服</p></a>';
            _str += '<a href="https://cs.ecqun.com/mobile/rand?id=4272575&scheme=0" target="_blank"><p class=""><i class="niu-icon icon-kefu"></i>在线客服</p></a>';

            _str += '</nav>';


            //if (location.href.substring(location.href.lastIndexOf('/')) != "/index.html" && location.href.substring(location.href.lastIndexOf('/')) != "/login.html" && location.href.substring(location.href.lastIndexOf('/')) != "/register.html" && location.href.substring(location.href.lastIndexOf('/')) != "/wxregister.html") {
            //    location.href = "/login/login.html";
            //}

        }

        _str += '</aside>';


        $(".global-nav").html(_str);
        flag = 1;
        var winHight = $(window).height();
        $(".ng-nav-open").click(function () {
            $(this).addClass("hide");
            $(".ng-nav-close").removeClass("hide");
            $(".ng-nav-con").removeClass("hide").css({"height": winHight - 44}).show();
            $("body").css({"overflow": "hidden", "height": winHight});
        });
        $(".ng-nav-close").click(function () {
            $(this).addClass("hide");
            $(".ng-nav-open").removeClass("hide");
            $(".ng-nav-con").addClass("hide");
            $("body").removeAttr("style");
        });
    });
}

//======================提示框================

function showMessage(str) {

    $('body').append("<div  class='ng-isolate-scope error-wrap' style=''><span ng-show='!error.convertHtml' class='error-msg ng-binding' ng-bind='error.msg'>" + str + "</span></div>")
    setTimeout(function () {
        $(".error-wrap").remove();

    }, 3000);
}

var getParam = function (name) {
    var search = document.location.search;
    var pattern = new RegExp("[?&]" + name + "\=([^&]+)", "g");
    var matcher = pattern.exec(search);
    var items = null;
    if (null != matcher) {
        try {
            items = decodeURIComponent(decodeURIComponent(matcher[1]));
        } catch (e) {
            try {
                items = decodeURIComponent(matcher[1]);
            } catch (e) {
                items = matcher[1];
            }
        }
    }
    return items;
};









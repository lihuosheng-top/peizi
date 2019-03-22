//=======================买入
function buy() {
    var stock_code = $("#stock_code").html(), name = $("#stock_name").html(), decl_price = $("#decl_price").html(), decl_num = $("#decl_num").html();

    var data = {};

    data.stock_code = stock_code;
    data.name = name;
    data.decl_price = decl_price;
    data.decl_num = decl_num;
    if (stock_code.length != 6) {
        showMessage("请先输入正确的股票代码！");
        return false;
    }

    layer.load("买入处理中，请稍后...", 2);
    $.ajax({
        url: "/tools/user_trans_ajax.ashx?act=buy&follow_id=" + $("#follow_id").val() + "&t" + new Date(),
        dataType: "text",
        type: "post",
        timeout: 6000,
        data: data,
        success: function (data) {
            if (data == "" || data == null) {
                window.location.href = "/login.html";
                return false;
            }
            var obj = eval('(' + data + ')');
            if (obj.status == "y") {

                showMessage(obj.info);

                location.reload();
                return false;
            }
            else {
                showMessage(obj.info);
                return false;
            }
        },
        error: function (XMLHttpRequest, textStatus, errorThrown) {
            showMessage("状态：" + textStatus + "；出错提示：" + errorThrown);
        }
    });
}
//=======================卖出
function sale() {

    var _stock_id = $("#_stock_id").val();
    var stock_code = $("#stock_code").html(), name = $("#stock_name").html(), decl_price = $("#decl_price").html(), decl_num = $("#decl_num").html();
    if (stock_code.length != 6) {
        showMessage("请先输入该账户持有的股票代码！", 8);
        return false;
    }

    var data = {};
    data.stock_code = stock_code;
    data.name = name;
    data.decl_price = decl_price;
    data.decl_num = decl_num;

    layer.load("卖出处理中，请稍后...", 2);
    $.ajax({
        url: "/tools/user_trans_ajax.ashx?act=sale&follow_id=" + $("#follow_id").val() + "&_stock_id=" + _stock_id + "&t" + new Date(),
        dataType: "text",
        type: "post",
        timeout: 6000,
        async: false,
        cache: false,
        data: data,
        success: function (data) {
            if (data == "" || data == null) {
                window.location.href = "/login.html";
                return false;
            }
            var obj = eval('(' + data + ')');
            if (obj.status == "y") {

                showMessage(obj.info);

                location.reload();
                return false;
            }
            else {
                showMessage(obj.info);
                return false;
            }
        }
    });

}
//=======================更改价格
function uptPrice() {
    var maxprice = parseFloat($("#decl_price").attr("maxprice")), minprice = parseFloat($("#decl_price").attr("minprice")),
        price = parseFloat($("#decl_price").val()), money_now = parseFloat($("#money_balance").val()),
        quantity = 0, q_100 = 0;
    if (price > maxprice) {
        price = maxprice;
    }
    if (price < minprice) {
        price = minprice;
    }
    if (isNaN(price)) {
        price = 0;
    }
    $("#decl_price").val(Number(price).toFixed(2));
    $.get("/tools/stock_ajax.ashx", { act: "max_num", follow_id: $("#follow_id").val(), now: price, t: new Date() }, function (data) {
        quantity = Number(data).toFixed(0);
        var q = quantity % 100, q_100 = quantity - q;//可买数量是否是100的倍数，不是则去最接近的
        $("#can_quantity").val(q_100);
    });
}
//=======================更改价格
function s_uptPrice() {
    var maxprice = parseFloat($("#decl_price").attr("maxprice")), minprice = parseFloat($("#decl_price").attr("minprice")),
        price = parseFloat($("#decl_price").val());
    if (price > maxprice) {
        price = maxprice;
    }
    if (price < minprice) {
        price = minprice;
    }
    if (isNaN(price)) {
        price = 0;
    }
    $("#decl_price").val(price);
}
//=======================卖出验证数量
function check_num_sale() {
    var num = parseFloat($("#decl_num").val()), quantity = parseFloat($("#can_quantity").html());
    if (!isNaN(num)) {
        if (num < 1) {
            layer.alert("卖出数量不能小于0股！", 8);
            return;
        }
        if (num > quantity) {
            layer.alert("数量不能大于可卖数量！", 8);
            return;
        }
    }
}
//=======================买入验证数量
function check_num() {
    var num = parseFloat($("#decl_num").val()), quantity = parseFloat($("#can_quantity").html());
    if (!isNaN(num)) {
        if (num > 0 && num % 100 != 0 && num) {
            layer.alert("数量只能是100的倍数！", 8);
            return;
        }
        if (num > quantity) {
            layer.alert("数量不能大于可买数量！", 8);
            return;
        }
    }
}
//=======================买入百分比快捷键
function percentnum(_this, percent) {

    var quantity = parseFloat($(".can_quantity").html()), now_q = 0;
    if ($(".can_quantity").html() == "0" || $(".can_quantity").html() == undefined || $(".can_quantity").html() == 0) {
        return;
    }

    $("i").each(function () {
        if ($(this).hasClass("icon-yixuanze")) {

            $(this).removeClass("icon-yixuanze");
            $(this).addClass("icon-xuanze");
        }

    });
    $(_this).addClass("icon-yixuanze");
    $(_this).removeClass("icon-xuanze");
    switch (percent) {
        case 1:
            now_q = quantity;
            break;
        case 2:
            now_q = quantity / 2;
            break;
        case 3:
            now_q = quantity / 3;
            break;
        case 4:
            now_q = quantity / 4;
            break;
        case 5:
            now_q = quantity / 5;
            break;
    }
    if (now_q < 100) {
        layer.alert("数量只能是100的倍数！", 8);
        return;
    }
    var q = now_q % 100, c_q = now_q - q;//可买数量是否是100的倍数，不是则去最接近的
    $("#inputOnfocus-buy").val(Number(c_q).toFixed(0));
}
//=======================卖出百分比快捷键
function percentnum_sale(_this, percent) {
    var quantity = parseFloat($("#can_quantity_sale").html()), now_q = 0;

    if ($("#can_quantity_sale").html() == "0" || $("#can_quantity_sale").html() == undefined || $("#can_quantity_sale").html() == 0) {
        return;
    }

    $("i").each(function () {
        if ($(this).hasClass("icon-yixuanze")) {

            $(this).removeClass("icon-yixuanze");
            $(this).addClass("icon-xuanze");
        }

    });
    $(_this).addClass("icon-yixuanze");
    $(_this).removeClass("icon-xuanze");
    switch (percent) {
        case 1:
            now_q = quantity;
            break;
        case 2:
            now_q = quantity / 2;
            break;
        case 3:
            now_q = quantity / 3;
            break;
        case 4:
            now_q = quantity / 4;
            break;
        case 5:
            now_q = quantity / 5;
            break;
    }
    if (now_q < 100) {
        layer.alert("数量只能是100的倍数！", 8);
        return;
    }
    var q = now_q % 100, c_q = now_q - q;//可买数量是否是100的倍数，不是则去最接近的

    $("#inputOnfocus-sell").val(Number(c_q).toFixed(0))
    // $("#deal_num_sale").val(Number(c_q).toFixed(0));
}
//=======================买入价格选择
var choosePriceBuy = function (obj) {
    var price = Number($(obj).html()).toFixed(2);
    $("#decl_price").val(price);
    $.get("/tools/stock_ajax.ashx", { act: "max_num", follow_id: $("#follow_id").val(), now: price, t: new Date() }, function (data) {
        quantity = Number(data).toFixed(0);
        var q = quantity % 100, q_100 = quantity - q;//可买数量是否是100的倍数，不是则去最接近的
        $("#can_quantity").val(q_100);
    });
}
//=======================卖出价格选择
var choosePriceSale = function (obj) {
    var price = Number($(obj).html()).toFixed(2);
    $("#decl_price_sale").val(price);
}
//=======================清除代码
function clearSearch() {

    $("#stock_code").html("--");//股票代码
    $("#stock_name").val("--");//股票名字
    $("#decl_price").val("0");//当前价格
    $("#decl_num").val("0");//数量
    $("#decl_price").attr("maxprice", "0");//今日最高价
    $("#decl_price").attr("minprice", "0");//今日最低价
    $(".sale5_p").html("0.00");//“卖五”报价
    $(".sale5_g").html("--");//“卖五”申请4695股，即X手；
    $(".sale4_p").html("0.00");
    $(".sale4_g").html("--");
    $(".sale3_p").html("0.00");
    $(".sale3_g").html("--");
    $(".sale2_p").html("0.00");
    $(".sale2_g").html("--");
    $(".sale1_p").html("0.00");
    $(".sale1_g").html("--");
    $(".buy1_p").html("0.00");//“买五”报价
    $(".buy1_g").html("--");//“买五”申请4695股，即X手；
    $(".buy2_p").html("0.00");
    $(".buy2_g").html("--");
    $(".buy3_p").html("0.00");
    $(".buy3_g").html("--");
    $(".buy4_p").html("0.00");
    $(".buy4_g").html("--");
    $(".buy5_p").html("0.00");
    $(".buy5_g").html("--");
    $(".y_close").html("0.00");//昨收
    $(".t_open").html("0.00");//今开
    $(".t_max").html("0.00");//今日最高价
    $(".t_min").html("0.00");//今日最低价
    $("#price_now").val("0.00");//当前价
    $(".upstop").html("0.00");//涨停
    $(".downstop").html("0.00");//跌停
    $("#can_quantity").val("0");
    $("#decl_num").val("0");

    $("#salsstockCode").val("");
    $("#buystockCode").val("");
    $("#codeName").html("");
    $("#salscodename").html("");
    $(".decl_price").val("");

    for (var i = 1; i < 6; i++) {


        if (!$(".buy" + i + "_p").hasClass("font-red")) {
            $(".buy" + i + "_p").addClass("font-red");
            $(".buy" + i + "_p").removeClass("font-green");
        }
        if (!$(".sale" + i + "_p").hasClass("font-red")) {
            $(".sale" + i + "_p").addClass("font-red");
            $(".sale" + i + "_p").removeClass("font-green");
        }

    }



}
//=======================自动提示，点击搜索
function keys(kw) {
    search(kw);
}
////=======================搜索
function keypress() {
    $('#stock_code').bind('keypress', function (event) {
        if (event.keyCode == "13") {
            var stock_code = $("#stock_code").html();
            if (stock_code.length == 6) {

                search(stock_code);
                $("#keyup_d").hide();
            }

        }
    });
}

////=======================搜索
function buycodekeypress() {

    var stock_code = $("#buystockCode").val();


    if (stock_code.length == 6) {

        $("#codeName").html("");

        search(stock_code);

    }

}

function salekeypress() {

    var stock_code = $("#salsstockCode").val();
    var _stock_id = $("#_stock_id").val();
    $("#stock_code").html(stock_code)
    if (stock_code.length == 6) {
        $("#salscodename").html("");
        salsearch(stock_code, _stock_id);

    }

}
//=======================信息提示
var openTips = function (typeid) {
    switch (typeid) {
        case 1:
            var money_able = $(".money_able").html(), money_yue = $("#money_yue").val(), money_lock = $(".money_lock").html();
            layer.alert("可用资金(" + money_able + ") = 余额(" + money_yue + ") - 冻结资金(" + money_lock + ")", 4);
            break;
        case 2:
            var money_lock = $(".money_lock").html(), money_cash_lock = $("#money_cash_lock").val(),
                money_today_yq = $("#money_today_yq").val(), money_stock_yk = $("#money_stock_yk").val();
            layer.alert("冻结资金(" + money_lock + ") ＝ 已提现未付金额(" + money_cash_lock + ") + 当日延期总费用金额(" + money_today_yq + ") + 持仓亏损到本金金额(" + money_stock_yk + ")", 4);
            break;
        default:
            break;
    }
}
//=======================买入查询
var search = function (stock_code) {
    $("#stock_code").html(stock_code);
    $.ajax({
        url: "/tools/stock_ajax.ashx?act=my_api&follow_id=" + $("#follow_id").val() + "&code=" + stock_code + "&t=" + new Date(),
        dataType: "text",
        type: "GET",
        timeout: 6000,
        success: function (data) {
            var api = data.split(',');
            if (api.length > 30) {

                var yprice = api[2], money_now = $("#money_balance").val(), decl_price = Number(api[3]).toFixed(2),
                    quantity = parseInt(api[34]), q_100 = 0, forbid = parseInt(api[33]), forbidprice = parseFloat("<%=config.forbidprice%>");//昨日收盘价,账户当前总额,当前价格,是否禁止买入状态及价格
                salquantity = parseInt(api[35]);

                if (quantity > 0) {
                    var q = quantity % 100, q_100 = quantity - q;//可买数量是否是100的倍数，不是则去最接近的

                }
                $(".stock_name_head").html(api[0] + "    " + stock_code);

                //$("*[name='stock_code']").val(stock_code);//股票代码
                //$("*[name='stock_code_sale']").val(stock_code);//股票代码

                $("#codeName").html(api[0]);//股票名字
                $("#stock_code").html(stock_code);
                $("#stock_name").html(api[0]);

                $("#salscodename").html(api[0]);

                //$(".y_close").html(Number(api[2]).toFixed(2));//昨收
                //$(".t_open").html(Number(api[1]).toFixed(2));//今开
                //$(".t_max").html(Number(api[4]).toFixed(2));//今日最高价
                //$(".t_min").html(Number(api[5]).toFixed(2));//今日最低价

                $(".upstop").html(Number(yprice * 1.1).toFixed(2));//涨停
                $(".downstop").html(Number(yprice * 0.9).toFixed(2));//跌停

                if (parseFloat(decl_price) == 0) {
                    $(".decl_price").val(Number(api[2]).toFixed(2));//当前价

                } else {
                    $(".decl_price").val(decl_price);//当前价
                }
                $(".sale5_p").html(Number(api[29]).toFixed(2));//“卖五”报价
                $(".sale5_g").html(parseInt(parseFloat(api[28]) / 100));//“卖五”申请4695股，即X手；
                $(".sale4_p").html(Number(api[27]).toFixed(2));
                $(".sale4_g").html(parseInt(parseFloat(api[26]) / 100));
                $(".sale3_p").html(Number(api[25]).toFixed(2));
                $(".sale3_g").html(parseInt(parseFloat(api[24]) / 100));
                $(".sale2_p").html(Number(api[23]).toFixed(2));
                $(".sale2_g").html(parseInt(parseFloat(api[22]) / 100));
                $(".sale1_p").html(Number(api[21]).toFixed(2));
                $(".sale1_g").html(parseInt(parseFloat(api[20]) / 100));
                $(".buy1_p").html(Number(api[11]).toFixed(2));//“买五”报价
                $(".buy1_g").html(parseInt(parseFloat(api[10]) / 100));//“买五”申请4695股，即X手；
                $(".buy2_p").html(Number(api[13]).toFixed(2));
                $(".buy2_g").html(parseInt(parseFloat(api[12]) / 100));
                $(".buy3_p").html(Number(api[15]).toFixed(2));
                $(".buy3_g").html(parseInt(parseFloat(api[14]) / 100));
                $(".buy4_p").html(Number(api[17]).toFixed(2));
                $(".buy4_g").html(parseInt(parseFloat(api[16]) / 100));
                $(".buy5_p").html(Number(api[19]).toFixed(2));
                $(".buy5_g").html(parseInt(parseFloat(api[18]) / 100));


                $(".can_quantity").html(q_100);
                // $(".can_salquantity").html(salquantity);

                $("#decl_num").val("100");
                $("#can_quantity_sale").html(api[35]);
                $("#deal_num_sale").val(api[35]);
                $("#stock_buy").css("background", "#ff5a55");
                if (forbid == 1) {
                    $("#stock_buy").css("background", "#ddd");
                }
                if (decl_price > forbidprice) {
                    $("#stock_buy").css("background", "#ddd");
                }
                chart_k(api[0], stock_code);//分时线ID

                for (var i = 1; i < 6; i++) {

                    if (parseFloat($(".buy" + i + "_p").text()) > decl_price) {
                        if (!$(".buy" + i + "_p").hasClass("font-red")) {
                            $(".buy" + i + "_p").addClass("font-red");
                            $(".buy" + i + "_p").removeClass("font-green");
                        }

                    } else {

                        if (!$(".buy" + i + "_p").hasClass("font-green")) {
                            $(".buy" + i + "_p").addClass("font-green");
                            $(".buy" + i + "_p").removeClass("font-red");
                        }

                    }

                    if (parseFloat($(".sale" + i + "_p").text()) > decl_price) {
                        if (!$(".sale" + i + "_p").hasClass("font-red")) {
                            $(".sale" + i + "_p").addClass("font-red");
                            $(".sale" + i + "_p").removeClass("font-green");
                        }

                    } else {

                        if (!$(".sale" + i + "_p").hasClass("font-green")) {
                            $(".sale" + i + "_p").addClass("font-green");
                            $(".sale" + i + "_p").removeClass("font-red");
                        }

                    }
                }

                //font-green font-red


            }
            else {
                showMessage("没有查询到相关数据！");
            }
        }
    });
}


//=======================卖出查询
var salsearch = function (stock_code, id) {
    $("#stock_code").val(stock_code);

    $("#stock_code_id").val(id);
    $.ajax({
        url: "/tools/stock_ajax.ashx?act=my_api&follow_id=" + $("#follow_id").val() + "&code=" + stock_code + "&stockid=" + id + "&t=" + new Date(),
        dataType: "text",
        type: "GET",
        timeout: 6000,
        success: function (data) {
            var api = data.split(',');
            if (api.length > 30) {

                var yprice = api[2], money_now = $("#money_balance").val(), decl_price = Number(api[3]).toFixed(2),
                    quantity = parseInt(api[34]), q_100 = 0, forbid = parseInt(api[33]), forbidprice = parseFloat("<%=config.forbidprice%>");//昨日收盘价,账户当前总额,当前价格,是否禁止买入状态及价格
                if (quantity > 0) {
                    var q = quantity % 100, q_100 = quantity - q;//可买数量是否是100的倍数，不是则去最接近的
                }
                $(".stock_name_head").html(api[0] + "    " + stock_code);
                $("*[name='stock_code']").val(stock_code);//股票代码
                $("*[name='stock_code_sale']").val(stock_code);//股票代码
                $("*[name='stock_name']").val(api[0]);//股票名字

                $("#stock_name").html(api[0]);

                $(".y_close").html(Number(api[2]).toFixed(2));//昨收
                $(".t_open").html(Number(api[1]).toFixed(2));//今开
                $(".t_max").html(Number(api[4]).toFixed(2));//今日最高价
                $(".t_min").html(Number(api[5]).toFixed(2));//今日最低价
                $(".upstop").html(Number(yprice * 1.1).toFixed(2));//涨停
                $(".downstop").html(Number(yprice * 0.9).toFixed(2));//跌停
                $("*[name='price_now']").val(decl_price);//当前价
                $("*[name='decl_price']").val(decl_price);
                $("*[name='decl_price']").attr("maxprice", Number(yprice * 1.1).toFixed(2));//涨停
                $("*[name='decl_price']").attr("minprice", Number(yprice * 0.9).toFixed(2));//跌停
                $(".sale5_p").html(Number(api[29]).toFixed(2));//“卖五”报价
                $(".sale5_g").html(parseInt(parseFloat(api[28]) / 100));//“卖五”申请4695股，即X手；
                $(".sale4_p").html(Number(api[27]).toFixed(2));
                $(".sale4_g").html(parseInt(parseFloat(api[26]) / 100));
                $(".sale3_p").html(Number(api[25]).toFixed(2));
                $(".sale3_g").html(parseInt(parseFloat(api[24]) / 100));
                $(".sale2_p").html(Number(api[23]).toFixed(2));
                $(".sale2_g").html(parseInt(parseFloat(api[22]) / 100));
                $(".sale1_p").html(Number(api[21]).toFixed(2));
                $(".sale1_g").html(parseInt(parseFloat(api[20]) / 100));
                $(".buy1_p").html(Number(api[11]).toFixed(2));//“买五”报价
                $(".buy1_g").html(parseInt(parseFloat(api[10]) / 100));//“买五”申请4695股，即X手；
                $(".buy2_p").html(Number(api[13]).toFixed(2));
                $(".buy2_g").html(parseInt(parseFloat(api[12]) / 100));
                $(".buy3_p").html(Number(api[15]).toFixed(2));
                $(".buy3_g").html(parseInt(parseFloat(api[14]) / 100));
                $(".buy4_p").html(Number(api[17]).toFixed(2));
                $(".buy4_g").html(parseInt(parseFloat(api[16]) / 100));
                $(".buy5_p").html(Number(api[19]).toFixed(2));
                $(".buy5_g").html(parseInt(parseFloat(api[18]) / 100));
                $("*[name='can_quantity']").val(q_100);
                $("#decl_num").val("100");
                $("#can_quantity_sale").val(api[35]);
                $("#deal_num_sale").val(api[35]);
                $("#can_quantity_sale").html(api[35]);


                $("#salscodename").html(api[0]);
                $(".decl_price").val(decl_price);
                $("#stock_buy").css("background", "#ff5a55");
                if (forbid == 1) {
                    $("#stock_buy").css("background", "#ddd");
                }
                if (decl_price > forbidprice) {
                    $("#stock_buy").css("background", "#ddd");
                }
                chart_k(api[0], stock_code);//分时线ID

            }
            else {
                layer.alert("没有查询到相关数据！", 8);
            }
        }
    });
}

//=======================分时图、K线图
var chart_k = function (stock_name, stock_code) {
    $("#stock_chart_k").html("<div class='imitate-mid-swf-tit stock_name_head'>" + stock_name + " " + stock_code + "</div><iframe id='chart_k' name='mainframe' frameborder='0' src='/tools/k_t_line.aspx?code=" + stock_code + "&h=560' width='930' height='600'></iframe>");
}
//=======================资金查询
var money_all = function () {
    $.get("/tools/user_trans_ajax.ashx", { act: "money_all", follow_id: $("#follow_id").val(), t: new Date() }, function (data) {

        if (data == "" || data == null) {

            return;
        }
        var api = data.split(',');
        if (api.length > 0) {
            //var money_profit = parseFloat(api[5]).toFixed(2);
            //$(".money_begin").html(Number(api[0]).toFixed(2));
            //$(".money_balance").html(Number(api[1]).toFixed(2));
            $(".money_balance_able").html(Number(api[2]).toFixed(2));
            //$(".money_cap").html(Number(api[3]).toFixed(2));
            //$(".money_all").html(Number(api[4]).toFixed(2));
            //$(".money_profit").html(money_profit);

            //$("#money_yue").val(Number(api[1]).toFixed(2));
            //$("#money_stock_yk").val(money_profit);

            //if (money_profit >= 0) {
            //    $(".money_profit").css("color", "red");
            //} else {
            //    $(".money_profit").css("color", "green");
            //}

            money_flag = 1;

        }
    });
}
//=======================持仓
var stock_list = function () {
    $.get("/tools/user_trans_ajax.ashx", { act: "stock_list", follow_id: $("#follow_id").val(), t: new Date() }, function (data) {
        flag = 1;

        if (data.length < 16) {
            $(".stock_list").html("<tr class='red'><td colspan='4' style='text-align: center;'>暂无记录</td></tr>");
        } else {
            $(".stock_list").html(data);
        }
    });
}
//=======================持仓
var stock_list_big = function () {
    $.get("/tools/user_trans_ajax.ashx", { act: "stock_list_big", follow_id: $("#follow_id").val(), t: new Date() }, function (data) {
        if (data.length < 16) {
            $("#stock_list_big").html("<tr class='red'><td colspan='10'>暂无记录</td></tr>");
        } else {
            $("#stock_list_big").html(data);
        }
    });
}
//=======================当日成交记录
function deal_today() {
    $.get("/tools/user_trans_ajax.ashx", { act: "deal_today", follow_id: $("#follow_id").val(), t: new Date() }, function (data) {
        if (data.length < 16) {
            $("#deal_today").html("<tr class='red'><td colspan='4' style='text-align: center;'>暂无记录</td></tr>");
        } else {
            $("#deal_today").html(data);
        }
    });
}
//=======================当日用户成交记录
function deal_today_user() {
    $.get("/tools/user_trans_ajax.ashx", { act: "deal_today_user", userid: $("#userid").val(), t: new Date() }, function (data) {
        if (data.length < 16) {
            $("#deal_today").html("<tr class='red'><td colspan='4' style='text-align: center;'>暂无记录</td></tr>");
        } else {
            $("#deal_today").html(data);
        }
    });
}


function sale_click(code) {
    setTab('imitate', 2, 9);
    var _stock_id = $("#_stock_id").val();
    salsearch(code, _stock_id);
}

//=======================委托记录(撤单)
var apply_list = function () {
    $.get("/tools/user_trans_ajax.ashx", { act: "apply_list_cd", follow_id: $("#follow_id").val(), type: 0, t: new Date() }, function (data) {
        if (data.length < 16) {
            $("#apply_list").html("<tr class='red'><td colspan='5' style='text-align: center;'>暂无记录</td></tr>");
        } else {
            $("#apply_list").html(data);


            $("#apply_list tr").each(function () {

                $this = $(this);
                $(this).bind("click", function () {
                    if (!$(this).find("i").hasClass("icon-yixuanze")) {

                        $(this).find("i").addClass("icon-yixuanze");

                        $(this).siblings("").find("i").removeClass("icon-yixuanze");
                        $(this).siblings("").find("i").addClass("icon-xuanze");

                        $(this).find("i").removeClass("icon-xuanze");
                    } else {

                        $(this).find("i").removeClass("icon-yixuanze");
                        $(this).find("i").addClass("icon-xuanze");
                    }

                });

            });
        }
    });
}
//=======================当日委托记录
var apply_list_today = function () {
    $.get("/tools/user_trans_ajax.ashx", { act: "apply_list", follow_id: $("#follow_id").val(), type: 1, t: new Date() }, function (data) {
        if (data.length < 16) {
            $("#apply_list_today").html("<tr class='red'><td colspan='4' style='text-align: center;'>暂无记录</td></tr>");
        } else {
            $("#apply_list_today").html(data);
        }
    });
}

//=======================所有 当日委托记录
var apply_list_today_user = function () {
    $.get("/tools/user_trans_ajax.ashx", { act: "apply_list_user", userid: $("#userid").val(), type: 1, t: new Date() }, function (data) {
        if (data.length < 16) {
            $("#apply_list_today").html("<tr class='red'><td colspan='4' style='text-align: center;'>暂无记录</td></tr>");
        } else {
            $("#apply_list_today").html(data);
        }
    });
}

//=======================撤销委托记录
var apply_back = function () {

    var id = $("#apply_list tr").find(".icon-yixuanze").attr("data_id");
    if (id == "" || id == null) {

        showMessage("请选择撤单项");
        return;
    }

    layer.load("撤单处理中，请稍后...", 2);
    $.ajax({
        url: "/tools/user_trans_ajax.ashx?act=apply_back&id=" + id + "&follow_id=" + $("#follow_id").val() + "&t" + new Date(),
        dataType: "text",
        cache: false,
        async: false,
        type: "GET",
        timeout: 6000,
        success: function (data) {

            if (data == "") {
                layer.alert("撤单失败，请刷新重试", 8);
                return false;
            }
            var _obj = eval('(' + data + ')');

            if (_obj.status == "y") {
                closeStockLimitModal('cd_model');
                apply_list();
                apply_list_today();
                money_all();
            } else {
                layer.alert(_obj.info, 8);
            }

        }
    });


}


/*======================资金流水==============================*/
function MoneyCallback(page_id) {
    MoneyData(page_id);
}
function MoneyData(pageindx) {
    var listcount = $("#money_count").val();
    $.ajax({
        type: "GET",
        url: "/tools/user_trans_ajax.ashx",
        cache: false,
        data: { act: 'money_list', follow_id: $("#follow_id").val(), t: new Date() },
        success: function (data) {


            if (data.length < 16) {

                $("#moneylist").html("<tr class='red'><td colspan='4' style='text-align: center;'>暂无记录</td></tr>");
            } else {
                $("#moneylist").html(data);
            }
        }
    });
    $("#pager_money").pagination(listcount, {
        callback: MoneyCallback,
        prev_text: '上一页',
        next_text: '下一页',
        items_per_page: 10,
        num_display_entries: 2,
        current_page: pageindx,
        num_edge_entries: 1
    });
}

/*======================历史委托==============================*/
var ApplyHistoryCallback = function () {
    $.get("/tools/user_trans_ajax.ashx", { act: "apply_list_history", follow_id: $("#follow_id").val(), type: 1, t: new Date() }, function (data) {
        if (data.length < 16) {
            $("#apply_list_history").html("<tr class='red'><td colspan='4' style='text-align: center;'>暂无记录</td></tr>");
        } else {
            $("#apply_list_history").html(data);
        }
    });
}
/*======================用户所有历史委托==============================*/
var ApplyHistoryCallbackUser = function () {
    $.get("/tools/user_trans_ajax.ashx", { act: "apply_list_history_user", userid: $("#userid").val(), type: 1, t: new Date() }, function (data) {
        if (data.length < 16) {
            $("#apply_list_history").html("<tr class='red'><td colspan='4' style='text-align: center;'>暂无记录</td></tr>");
        } else {
            $("#apply_list_history").html(data);
        }
    });
}


/*======================历史交易==============================*/

var DealHistoryCallback = function () {
    $.get("/tools/user_trans_ajax.ashx", { act: "deal_history", follow_id: $("#follow_id").val(), type: 1, t: new Date() }, function (data) {
        if (data.length < 16) {
            $("#deal_history").html("<tr class='red'><td colspan='4' style='text-align: center;'>暂无记录</td></tr>");
        } else {
            $("#deal_history").html(data);
        }
    });
}
/*======================所有历史交易==============================*/

var DealHistoryCallback_user = function () {
    $.get("/tools/user_trans_ajax.ashx", { act: "deal_history_user", userid: $("#userid").val(), type: 1, t: new Date() }, function (data) {
        if (data.length < 16) {
            $("#deal_history").html("<tr class='red'><td colspan='4' style='text-align: center;'>暂无记录</td></tr>");
        } else {
            $("#deal_history").html(data);
        }
    });
}



//=======================添加自选股
function AddMySelfStock(obj) {
    var code = $("#txtMySelfCode").val(), stock_pool_count = $("#stock_pool_count").val();
    if (code.length != 6) {
        layer.alert("请输入正确的证券代码！", 8);
        return;
    }
    $("#btnSaveSelfStock").hide();
    $("#btnSaveSelfStock").after("<span class='red' id='selfAddLoading'>添加处理中...</span>");
    $.ajax({
        type: "post",
        url: "/tools/user_trans_ajax.ashx?act=add_myself_stock&code=" + code + "&follow_id=" + $("#follow_id").val() + "&t=" + new Date(),
        dataType: 'text',
        cache: false,
        timeout: 6000,
        success: function (data) {
            var obj = eval('(' + data + ')');
            if (obj.status == "y") {
                $("#txtMySelfCode").val("");
                $("#stock_pool_count").val(parseInt(stock_pool_count) + 1);
                StockPoolData(0);
            }
            else {
                layer.alert(obj.info, 8);
            }
            $("#btnSaveSelfStock").show();
            $("#selfAddLoading").remove();
            return false;
        }
    });
}
//=======================删除自选股
function DelMySelfStock(sid, obj) {
    var code = $("#selfcode" + sid).text(), name = $("#selfname" + sid).text(), stock_pool_count = $("#stock_pool_count").val();
    $.layer({
        shade: [0],
        area: ['auto', 'auto'],
        dialog: {
            msg: "确定要删除此自选股吗?<br/>证券代码：" + code + "<br/>证券名称：" + name,
            btns: 2,
            type: 4,
            btn: ['确定', '取消'],
            yes: function () {
                layer.load("删除处理中，请稍后...", 2);

                $.ajax({
                    url: "/tools/user_trans_ajax.ashx?act=del_myself_stock&id=" + sid + "&follow_id=" + $("#follow_id").val() + "&t" + new Date(),
                    dataType: "text",
                    type: "get",
                    timeout: 6000,
                    async: false,
                    cache: false,
                    success: function (data) {
                        if (data == "" || data == null) {
                            window.location.href = "/login.html";
                            return false;
                        }
                        var obj = eval('(' + data + ')');
                        if (obj.status == "y") {
                            $("#stock_pool_count").val(parseInt(stock_pool_count) - 1);
                            StockPoolData(0);
                            return false;
                        }
                        else {
                            layer.alert(obj.info, 8);
                            return false;
                        }
                    }
                });
            }
        }
    });
}
//=======================自选股单只查询
function GetMySelfStockinfo(code) {
    if (code.length != 6) {
        return;
    }
    $("#txtMySelfName").html("加载中...");
    $.ajax({
        url: "/tools/stock_ajax.ashx?act=stock_api&code=" + code + "&follow_id=" + $("#follow_id").val() + "&t=" + new Date(),
        dataType: "text",
        type: "GET",
        timeout: 6000,
        success: function (data) {
            var api = data.split(',');
            if (api.length > 30) {
                $("#txtMySelfName").html(api[0]);
            }
            else {
                $("#txtMySelfName").html("未找到");
            }
        }
    });
}
/*======================自选池==============================*/
function StockPoolCallback(page_id) {
    StockPoolData(page_id);
}
function StockPoolData(pageindx) {
    var listcount = $("#stock_pool_count").val();
    $.get("/tools/user_trans_ajax.ashx", { act: "stock_pool_list", follow_id: $("#follow_id").val(), p: (pageindx + 1), t: new Date() }, function (data) {
        if (listcount <= 10) {
            $("#pager_stock_pool").css("display", "none");
        } else {
            $("#pager_stock_pool").css("display", "block");
        }
        $("#stock_pool_list").html(data);
    });
    $("#pager_stock_pool").pagination(listcount, {
        callback: StockPoolCallback,
        prev_text: '上一页',
        next_text: '下一页',
        items_per_page: 10,
        num_display_entries: 2,
        current_page: pageindx,
        num_edge_entries: 1
    });
}
//=======================刷新5档行情
function GetMySelfStockinfo(code) {


    if (code.length != 6) {
        return;
    }

    $.ajax({
        url: "/tools/stock_ajax.ashx?act=stock_api&code=" + code + "&follow_id=" + $("#follow_id").val() + "&t=" + new Date(),
        dataType: "text",
        type: "GET",
        timeout: 6000,
        success: function (data) {
            var api = data.split(',');
            if (api.length > 30) {
                refreshStock5(data);
            }
            else {
                // $("#txtMySelfName").html("未找到");
            }
        }
    });
}


var refreshStock5 = function (stock_info) {
    var api = stock_info.split(",");
    var yprice = Number(api[2]).toFixed(2), decl_price = Number(api[3]).toFixed(2); //昨日收盘价,账户当前总额,当前价格
    $("*[name='price_now']").val(decl_price);//当前价
    $("#price_now").val(decl_price);

    $(".sale5_p").html(Number(api[29]).toFixed(2)); //“卖五”报价
    $(".sale5_g").html(parseInt(parseFloat(api[28]) / 100)); //“卖五”申请4695股，即X手；
    $(".sale4_p").html(Number(api[27]).toFixed(2));
    $(".sale4_g").html(parseInt(parseFloat(api[26]) / 100));
    $(".sale3_p").html(Number(api[25]).toFixed(2));
    $(".sale3_g").html(parseInt(parseFloat(api[24]) / 100));
    $(".sale2_p").html(Number(api[23]).toFixed(2));
    $(".sale2_g").html(parseInt(parseFloat(api[22]) / 100));
    $(".sale1_p").html(Number(api[21]).toFixed(2));
    $(".sale1_g").html(parseInt(parseFloat(api[20]) / 100));
    $(".buy1_p").html(Number(api[11]).toFixed(2)); //“买五”报价
    $(".buy1_g").html(parseInt(parseFloat(api[10]) / 100)); //“买五”申请4695股，即X手；
    $(".buy2_p").html(Number(api[13]).toFixed(2));
    $(".buy2_g").html(parseInt(parseFloat(api[12]) / 100));
    $(".buy3_p").html(Number(api[15]).toFixed(2));
    $(".buy3_g").html(parseInt(parseFloat(api[14]) / 100));
    $(".buy4_p").html(Number(api[17]).toFixed(2));
    $(".buy4_g").html(parseInt(parseFloat(api[16]) / 100));
    $(".buy5_p").html(Number(api[19]).toFixed(2));
    $(".buy5_g").html(parseInt(parseFloat(api[18]) / 100));

    $(".y_close").html(Number(api[2]).toFixed(2));//昨收
    $(".t_open").html(Number(api[1]).toFixed(2));//今开
    $(".t_max").html(Number(api[4]).toFixed(2));//今日最高价
    $(".t_min").html(Number(api[5]).toFixed(2));//今日最低价
    $(".upstop").html(Number(yprice * 1.1).toFixed(2));//涨停
    $(".downstop").html(Number(yprice * 0.9).toFixed(2));//跌停


}
/*======================交易&刷新==============================*/
var refresh_10 = function () {
    if ($("#imitate1").hasClass("hover")) {
        StockPoolData();
    }
    if ($("#imitate3").hasClass("hover")) {
        apply_list();
    }
}
var refresh_3 = function () {
    if ($("#imitate1").hasClass("hover") || $("#imitate2").hasClass("hover")) {
        refresh();
    }
    if ($("#imitate2").hasClass("hover")) {
        stock_list();
    }
    if ($("#imitate4").hasClass("hover")) {
        stock_list_big();
    }
}
function money_list() {
    $.ajax({
        type: "GET",
        url: "/tools/user_trans_ajax.ashx",
        cache: false,
        data: { act: 'money_list', follow_id: $("#follow_id").val(), t: new Date() },
        success: function (data) {

            if (data.length < 16) {
                $("#moneylist").html("<tr class='red'><td colspan='10'>暂无记录</td></tr>");
            } else {
                $("#moneylist").html(data);
            }
        }
    });

}

function setTab(name, cursel, n) {

    switch (cursel) {
        case 1:
            stock_list();//股票持仓
            break;
        case 2:
            //买入
            break;
        case 3:
            //卖出
            break;
        case 4:
            apply_list();//委托记录(撤单)
            break;
        case 5:
            //
            break;
        case 6:
            apply_list_today();//委托记录(今日)
            break;
        case 7:
            deal_today();//成交记录(今日)
            break;
        case 8:
            ApplyHistoryCallback();//历史委托
            break;
        case 9:
            DealHistoryCallback();//历史成交
            break;

        case 10:
            apply_list_today_user();//所有委托记录(今日) 根据userid
            break;
        case 11:
            deal_today_user();//所有成交记录(今日)
            break;
        case 12:
            ApplyHistoryCallbackUser();//所有历史委托
            break;
        case 13:
            DealHistoryCallback_user();//所有历史成交
            break;
        case 14:
            money_list();//资金流水
            break;
    }
    for (i = 1; i <= n; i++) {
        var menu = $("#" + name + i);
        var con = $("#con_" + name + "_" + i);
        i == cursel ? menu.addClass("selected") : menu.removeClass("selected");
        i == cursel ? con.addClass("block").removeClass("hide") : con.addClass("hide").removeClass("block");

        clearSearch();
    }
}
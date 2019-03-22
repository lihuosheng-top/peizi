var stockChange = $(".play-area").find(".change-stock");
var historyBox=$(".play-area").find('.search_history');
var cue=$('#search_cue');
var html_default='<tr class="active"><td>万科A</td><td>000002</td><td>wka</td></tr>';
var stopShare=true;

//选中的股票代码
var selectedCode = "000001";

//点击标题显示输入框
////$(".stock-name").on("click", function (e) {
//	e.stopPropagation();
//  stockChange.show();

//  输入框键盘按下事件

$('#searchTxt1').focus().off('keyup').on('keyup',function(e){
    e.stopPropagation();
    var tbody = $("#search_history, #search_cue").find("tbody:visible");
    var keywords=$(this).val().toLowerCase();//关键词
    var keycode=e.keyCode;//键盘码
    //默认
    if(keywords==""){
        tbody.html(html_default);
    }

    //上选
    if(keycode=='38'){
        goUp(tbody);
        return;
    }
    //下选
    if(keycode=='40'){
        goDown(tbody);
        return;
    }
    //enter
    if(keycode=='13'){
        tbody.find("tr.active").trigger("click")
        return;
    }
    //如果不是上面几个，即表示查询
    if(keycode!='38'&&keycode!='40'&&keycode!='13'){
        if(keywords.length){//如果值不为空
            searchCue(keywords)//非历史(cue)框的发送给后台查询
        }
    }
    //选中股票，改变标题，隐藏搜索框
    selectAShare($('#search_cue tr'));

});

//});


//取消
$('#searchCancel').click(function(){
    $('#searchTxt1').val("");
    cue.hide();
});

$('#searchTxt1').click(function(e){
    e.stopPropagation();
});

$('body').click(function(e){
    //  输入框消失事件
    cue.hide();
    initInput();
})


//---封装的函数
//第一个亮
function selectInit(tbody) {
    var tbody = $("#search_history, #search_cue").find("tbody:visible");
    var tr = tbody.find("tr");
    if (!tr.eq(0).hasClass("empty") || !tr.hasClass("active")) {
        tr.eq(0).addClass("active");
    }

    tbody.mouseenter(function () {
        tr.eq(0).removeClass("active");
    });
}

/**
 * 非历史(cue)框的发送给后台查询函数
 */
function searchCue(keywords){
    var tbody = cue.find("tbody");
//		console.log(tbody+'00')
    $("#search_history").hide();//历史框隐藏掉
    //发送请求
    $.ajax({
        type:"get",
        url:"./index/Alistock/getSharesByKeywords",
        data:{keywords:keywords},
        dataType:'json',
        success:function(data){
            //1.清空tbody
            cue.find("tbody").html("");
            //2.渲染(if如果没数据，else如果有数据)
            var html='';
            if(data.data==null||data.data==''){
                html='<tr class="empty"><td colspan="3">暂无数据</td></tr>';
            }else{
                for(var i=0;i<data.data.length&&i<10;i++){
                    html+='<tr><td>'+data.data[i].name+'</td><td>'+data.data[i].code+'</td><td>'+data.data[i].pinyin+'</td></tr>';
                }
            }
            tbody.html(html);
            //3.cue框显示
            cue.show();
            //4.亮第一个
            selectInit();
            //5.选中股票，改变标题，隐藏搜索框
            selectAShare($('#search_cue tr'));
            //6.发送给后台查询股票行情
        }
    });
}

/**
 * 选中股票，改变标题，隐藏搜索框
 */
function selectAShare(dom){
    dom.on('click',function(e){
        var ashareName=$(this).find('td').eq(0).html();
        var ashareCode=$(this).find('td').eq(1).html();
        $('.stock-name').find('span').html(ashareName+'('+ashareCode+')');
        /**
         * TODO:股票赋值开始
         */
        $.ajax({
            url:"./index/Index/buy",
            type:"post",
            data:{code:ashareCode},
            dataType:'json',
            success:function(data){
                if(data.status == 1){

                    // console.log(data.info);
                    var info_3 = data.info.info_arr[3];    //当前价格
                    var info_31 = data.info.info_arr[31];  //涨跌
                    var info_32 = data.info.info_arr[32];  //涨跌%
                    //卖5到卖1(先上面一排再下面一排数据)
                    var info_27 = data.info.info_arr[27];
                    var info_25 = data.info.info_arr[25];
                    var info_23 = data.info.info_arr[23];
                    var info_21 = data.info.info_arr[21];
                    var info_19 = data.info.info_arr[19];
                    var info_28 = data.info.info_arr[28];
                    var info_26 = data.info.info_arr[26];
                    var info_24 = data.info.info_arr[24];
                    var info_22 = data.info.info_arr[22];
                    var info_20 = data.info.info_arr[20];
                    //买1到买5(先上面一排再下面一排数据)
                    var info_9 = data.info.info_arr[9];
                    var info_11 = data.info.info_arr[11];
                    var info_13 = data.info.info_arr[13];
                    var info_15 = data.info.info_arr[15];
                    var info_17 = data.info.info_arr[17];
                    var info_10 = data.info.info_arr[10];
                    var info_12 = data.info.info_arr[12];
                    var info_14 = data.info.info_arr[14];
                    var info_16 = data.info.info_arr[16];
                    var info_18 = data.info.info_arr[18];
                    //股票信息部分（今开->振幅—>最高.....）
                    var info_5 = data.info.info_arr[5];
                    var info_43 = data.info.info_arr[43];
                    var info_41 = data.info.info_arr[41];
                    var info_42 = data.info.info_arr[42];
                    var info_47 = data.info.info_arr[47];
                    var info_48 = data.info.info_arr[48];
                    var info_36 = data.info.info_arr[36];
                    var info_37 = data.info.info_arr[37];



                    var time_img =data.info.time_img;       //分时K线图
                    var day_img =data.info.day_img;         //分日K线图

                    $(".J_price").html(info_3);            //当前价格
                    $(".J_num_1").html(info_31);           //涨跌
                    $(".J_num_2").html(info_32);           //涨跌%
                    $(".time_img").attr("src",time_img);   //时K线
                    $(".day_img").attr("src",day_img);     //日K线
                    //卖5到卖1(上面一排)
                    $(".info_27").html(info_27);
                    $(".info_25").html(info_25);
                    $(".info_23").html(info_23);
                    $(".info_21").html(info_21);
                    $(".info_19").html(info_19);
                    //卖5到卖1(下面一排)
                    $(".info_28").html(info_28);
                    $(".info_26").html(info_26);
                    $(".info_24").html(info_24);
                    $(".info_22").html(info_22);
                    $(".info_20").html(info_20);
                    //买1到买5(上面一排数据)
                    $(".info_9").html(info_9);
                    $(".info_11").html(info_11);
                    $(".info_13").html(info_13);
                    $(".info_15").html(info_15);
                    $(".info_17").html(info_17);
                    //买1到买5(下面一排数据)
                    $(".info_10").html(info_10);
                    $(".info_12").html(info_12);
                    $(".info_14").html(info_14);
                    $(".info_16").html(info_16);
                    $(".info_18").html(info_18);
                    //股票信息部分（今开->振幅—>最高.....）
                    $(".info_5").html(info_5); //今开
                    $(".info_43").html(info_43);//振幅
                    $(".info_41").html(info_41);//最高
                    $(".info_42").html(info_42);//最低
                    $(".info_47").html(info_47);//涨跌价
                    $(".info_48").html(info_48);//跌停价
                    $(".info_36").html(info_36);//成交量（手）
                    $(".info_37").html(info_37);//成交额（万）

                }
            },
            error:function (data) {
                console.log("错误");
            }
        });


        /**
         * TODO:股票赋值结束
         */

        //隐藏搜索框，值清空
        cue.hide();
        $("#searchTxt1").val("");

        selectedCode = ashareCode;

        //更新股票实时数据和分时数据
        stockInit();

        //更新股票K线图
        initKChart();
    });
}

/**
 * 向下
 */
function goDown(tbody) {
    var index = tbody.find("tr.active").index();
    if (index < tbody.find("tr").length - 1) {
        tbody.find("tr").removeClass("active").eq(index + 1).addClass("active");
    }
}

/**
 * 向上
 */
function goUp(tbody) {
    var index = tbody.find("tr.active").index();
    if (index > 0) {
        tbody.find("tr").removeClass("active").eq(index - 1).addClass("active");
    }
}


/**
 * 将输入框以及下方弹框恢复默认属性
 */
function initInput(){
    $('#searchTxt1').val("");
    cue.find('tbody').html(html_default);
}

//echarts
$('#chartContro').on('click',function(){
    $(this).siblings().removeClass('active');
    $(this).addClass('active');
    $('#chart').show();
    $('#chartK').hide();

})
$('#chartKContro').on('click',function(){
    $(this).siblings().removeClass('active');
    $(this).addClass('active');
    $('#chartK').show();
    $('#chart').hide();

})

//初始化页面 获取股票实时数据、分时数据、K线图
function stockInit(){
    //获取股票实时数据
    getStockInfo();

    //获取分时数据
    getTimeLine();
}


//获取分时数据
function getTimeLine(){
    $.post("./index/Alistock/getTimeLine", {code:selectedCode}, function(data){

        if(data.showapi_res_code != '0' || data.showapi_res_body.ret_code != '0'){
            return;
        }
        var arr = data.showapi_res_body.dataList[0].minuteList;
        //二维数组里面有平均价，现在价格，时间（分钟），成交量等信息
        var chartVal = [];
        //遍历
        $.each(arr, function () {
            var inVal = [];
            var price=this.nowPrice;
            //avg_price:6.456,price:6.45,time:"09:30",totalAmount:7046840.3080199985,totalVolume:1094569.7899999998,volume:32666.5
            if (price >= 0) {
                //现在日期2017-07-18
                var dVal = new Date().format("yyyy-MM-dd");// dVal = "2017-07-18"
                //分割现在日期字符串
                var s1arr1 = dVal.split("-");//s1arr1 = ["2017", "07", "18"]
                var s1arr2=this.time.substring(0,2)+':'+this.time.substring(2,4);
                s1arr2 = s1arr2.split(":");//s1arr2 = ["09", "36"]
                if (s1arr2.length == 2) {
                    s1arr2.push("00");//s1arr2 = ["09", "36", "00"]
                }
                //获取毫秒数
                //rVal = 1500341760000
                var rVal = new Date(s1arr1[0], s1arr1[1] - 1, s1arr1[2], s1arr2[0], s1arr2[1], s1arr2[2]).getTime();
                //毫秒加进数组
                inVal.push(rVal);
                //价格加进数组
                inVal.push(price);
                //数组加进chartVal数组
                chartVal.push(inVal);
                //现在有180多个价格>0的二维数组，数组【0】是毫秒数，【1】是价格
            }
            //console.log(inVal);
            //console.log(chartVal);
        });
        var yestclose = data.showapi_res_body.dataList[0].yestclose;
        //yestclose = "25.11"，昨天收盘价

        //dataList是几百个二维数组，name是时间，value是数组，包括【0】时间和【1】价格
        //绘制分时图
        var dataList = { "records": chartVal, "y_close": yestclose };
        initTimeLine.chartLine.init('#chart',dataList );

        //loading
        $('.loading').hide();

    }, 'json');
}

//获取股票实时数据
function getStockInfo(){
    $('#btn_buy').html('点买').attr('disabled',false).css({'background':'#E11923'})
    $.post("./index/Alistock/getStockInfo", {code:selectedCode}, function(data){
        if(data.code != '0'){
            return;
        }
        var map = data.data;
        $("#stockName").html(map.info_arr[1] + "(" + selectedCode + ")");
        //渲染页面价格
        var nowPrices=(map.info_arr[3]-0).toFixed(2);
        var nowPrice = parseFloat($("#nowPrice").html());
        if(nowPrice <= 0){
            nowPrice = nowPrices;
        }
        if(nowPrice >nowPrices){//比较最新价与原来的价格
            $(".stock-detail .up-arrow-box").hide();
            $(".stock-detail .down-arrow-box").css("display", "inline-block");
        }else if(nowPrice < nowPrices){
            $(".stock-detail .up-arrow-box").css("display", "inline-block");
            $(".stock-detail .down-arrow-box").hide();
        }

        // 如果不在交易时间，判断当前价格和昨日收盘价格(TODO：时间设置)
        if(!isTradingTime() ){
        //     // if(nowPrice < map.closePrice){
        //     //     $(".stock-detail .up-arrow-box").hide();
        //     //     $(".stock-detail .down-arrow-box").css("display", "inline-block");
        //     // }else{
        //     //     $(".stock-detail .up-arrow-box").css("display", "inline-block");
        //     //     $(".stock-detail .down-arrow-box").hide();
        //     // }
            $('#btn_buy').attr('disabled',true).css({'background':'#767679'}).html('点买时间9:30-11:30, 13:00-14:58');
        }

        // if(nowPrice < map.closePrice){
        //     $("#nowPrice").removeClass('red').removeClass('green').addClass("green");
        // }else if(nowPrice > map.closePrice){
        //     $("#nowPrice").removeClass('red').removeClass('green').addClass("red");
        // }
        //渲染页面价格
        var nowPrice_s=(map.info_arr[3]-0).toFixed(2);
        $("#nowPrice").html(nowPrice_s);
        //渲染涨跌
        //改变价格颜色
        if(map.info_arr[31] > 0){
            $(".color").removeClass('red').removeClass('green').addClass("red");
        }else if(map.info_arr[31] < 0){
            $(".color").removeClass('red').removeClass('green').addClass("green");
        }
        $("#num1").html(map.info_arr[31]);
        $("#num2").html(map.info_arr[32] + "%");

        //卖⑤...卖①...买①...买⑤
        // var bs = ["sell5_m", "sell5_n", "sell4_m", "sell4_n", "sell3_m", "sell3_n", "sell2_m", "sell2_n", "sell1_m", "sell1_n",
        // 	"buy1_m", "buy1_n", "buy2_m", "buy2_n", "buy3_m", "buy3_n", "buy4_m", "buy4_n", "buy5_m", "buy5_n"];
        // $("#stock-price li > b, .stock-price li > i").each(function(i, o){
        //    var t = map[bs[i]];
        //    if(i % 2 == 1){
        //        t = parseInt(map[bs[i]] / 100);
        //    }else{
        //        t = Number(t).toFixed(2);
        //    }
        // 	$(o).html(t);
        // });

        var time_img =data.day_url_info;       //分时K线图
        var day_img =data.time_url_info;         //分日K线图
        //卖5到卖1(先上面一排再下面一排数据)
        var info_27 = map.info_arr[27];
        var info_25 = map.info_arr[25];
        var info_23 = map.info_arr[23];
        var info_21 = map.info_arr[21];
        var info_19 = map.info_arr[19];
        var info_28 = map.info_arr[28];
        var info_26 = map.info_arr[26];
        var info_24 = map.info_arr[24];
        var info_22 = map.info_arr[22];
        var info_20 = map.info_arr[20];
        //买1到买5(先上面一排再下面一排数据)
        var info_9 = map.info_arr[9];
        var info_11 = map.info_arr[11];
        var info_13 = map.info_arr[13];
        var info_15 = map.info_arr[15];
        var info_17 = map.info_arr[17];
        var info_10 = map.info_arr[10];
        var info_12 = map.info_arr[12];
        var info_14 = map.info_arr[14];
        var info_16 = map.info_arr[16];
        var info_18 = map.info_arr[18];

        $(".time_img").attr("src",time_img);   //时K线
        $(".day_img").attr("src",day_img);     //日K线
        //卖5到卖1(上面一排)
        $(".info_27").html(info_27);
        $(".info_25").html(info_25);
        $(".info_23").html(info_23);
        $(".info_21").html(info_21);
        $(".info_19").html(info_19);
        //卖5到卖1(下面一排)
        $(".info_28").html(info_28);
        $(".info_26").html(info_26);
        $(".info_24").html(info_24);
        $(".info_22").html(info_22);
        $(".info_20").html(info_20);
        //买1到买5(上面一排数据)
        $(".info_9").html(info_9);
        $(".info_11").html(info_11);
        $(".info_13").html(info_13);
        $(".info_15").html(info_15);
        $(".info_17").html(info_17);
        //买1到买5(下面一排数据)
        $(".info_10").html(info_10);
        $(".info_12").html(info_12);
        $(".info_14").html(info_14);
        $(".info_16").html(info_16);
        $(".info_18").html(info_18);




        //今开 最高 ...... 成交额
        // bs = ['openPrice', 'swing', 'todayMax', 'todayMin', 'highLimit', 'downLimit', 'tradeNum', 'tradeAmount' ];
        // $("#stock-info li > span.r ").each(function(i, o){
        //     if(bs[i] == 'swing'){
        //         $(o).html(map[bs[i]] + "%");
        //     }else if(bs[i] == 'tradeNum'){
        //         $(o).html(map[bs[i]] / 100 + "手");
        //     }else if(bs[i] == 'tradeAmount'){
        //         $(o).html(map[bs[i]] / 10000 + "万");
        //     }else{
        //         $(o).html(Number(map[bs[i]]).toFixed(2));
        //     }
        // });

        //股票信息部分（今开->振幅—>最高.....）
        var info_5 = map.info_arr[5];
        var info_43 = map.info_arr[43];
        var info_41 = map.info_arr[41];
        var info_42 = map.info_arr[42];
        var info_47 = map.info_arr[47];
        var info_48 = map.info_arr[48];
        var info_36 = map.info_arr[36];
        var info_37 = map.info_arr[37];

        //股票信息部分（今开->振幅—>最高.....）
        $(".info_5").html(info_5); //今开
        $(".info_43").html(info_43);//振幅
        $(".info_41").html(info_41);//最高
        $(".info_42").html(info_42);//最低
        $(".info_47").html(info_47);//涨跌价
        $(".info_48").html(info_48);//跌停价
        $(".info_36").html(info_36);//成交量（手）
        $(".info_37").html(info_37);//成交额（万）



        updateStockNumber();


        /*停牌判断*/
        if(Number(map.info_arr[5]).toFixed(2)=='0'){
            $('#btn_buy').html(map.info_arr[5]).attr('tapEvent',false).css({'background':'#767679'});
            $('#btn_buy').attr('tapEvent',false).css({'background':'#767679'}).html('此股票不能购买！');
            updateMoneyRate();
            return;
        }
        // console.log(map.info_arr[48]);
        //不得购买首日上市新股（或复牌首日股票）等当日不设涨跌停板限制的股票；低价股不能买
        if(Number(map.info_arr[48]).toFixed(2)==null){
            $('#btn_buy').html(info_arr[48]).attr('tapEvent',false).css({'background':'#767679'});
            $('#btn_buy').attr('tapEvent',false).css({'background':'#767679'}).html('此股票没有设涨跌限制，不能购买！');
            updateMoneyRate();
            return;
        }
        if(Number(map.info_arr[47]).toFixed(2)==null){
            $('#btn_buy').html(map.info_arr[47]).attr('tapEvent',false).css({'background':'#767679'});
            $('#btn_buy').attr('tapEvent',false).css({'background':'#767679'}).html('此股票没有设涨跌限制，不能购买！');
            updateMoneyRate();
            return;
        }



        //停牌判断
        //   stopShare=true;
        //    if(Number(map.openPrice).toFixed(2)=='0'||Number(map.openPrice).toFixed(2)=='0.00'){
        //    	$('#btn_buy').html(map.remark).attr('disabled',true).css({'background':'#767679'});
        //    	stopShare=false;
        //    	updateMoneyRate();
        // 	return;
        // }

        //更新资金利用率数据
        updateMoneyRate();


    }, 'json')
}


$(function(){
    //loading
    $('.loading').show();

    //获取股票实时数据和分时数据。每几秒钟就刷新一次
    stockInit();

    //获取股票K线图。只在页面打开时加载一次，不需要再刷新
    // initKChart();


});


var freshTimeInterval = 10 * 1000;
var freshTimes = 0;
//定时器 获取实时数据（最新数据）, 每几秒钟就刷新一次
setInterval(function(){
    if(!isTradingTime()||!stopShare){ // 如果不在交易时间就不必刷新数据
//  	$('#btn_buy').attr('disabled',true).css({'background':'#eee'}).html('点买时间9:30-11:30, 13:00-14:58');
        return ;
    }
    freshTimes ++ ;
    if(freshTimes > 10){
        freshTimeInterval = 60 * 1000;
    }
    //获取股票实时数据
    getStockInfo();

}, 2000);


//定时器 获取分时数据
setInterval(function(){return;
    if(!isTradingTime()){ // 如果不在交易时间就不必刷新数据
        return ;
    }
    //获取分时数据
    getTimeLine();
}, 60 * 1000);



function isTradingTime(){
    var date = new Date();
    //判断是不是周末
    var dt=date.getDay();
    if(dt=='6'||dt=='7'){
        return false;
    }
    //判断当前时间是否在9:30-11:30, 13:00-15:00
    var h = date.getHours();
    var mi = date.getMinutes();
    var s = date.getSeconds();
    if(h < 10){
        h = "0" + h;
    }
    if(mi < 10){
        mi = "0"+ mi;
    }
    if(s < 10){
        s = "0" + s;
    }
    var curTime = h + ":" + mi + ":" + s;
    if( curTime >= "09:30:00" && curTime <= "11:30:00" || curTime >= "13:00:00" && curTime <= "15:00:00" ){
        return true;
    }
    return false;
}






function splitData(rawData) {
    var categoryData = [];
    var values = []
    for (var i = 0; i < rawData.length; i++) {
        categoryData.push(rawData[i].splice(0, 1)[0]);
        values.push(rawData[i])
    }
    return {
        categoryData: categoryData.reverse(),
        values: values.reverse()
    };
}

function calculateMA(dayCount) {
    var result = [];
    for (var i = 0, len = data0.values.length; i < len; i++) {
        if (i < dayCount) {
            result.push('-');
            continue;
        }
        var sum = 0;
        for (var j = 0; j < dayCount; j++) {
            sum += data0.values[i - j][1];
        }
        result.push(sum / dayCount);
    }
    return result;
}



function initKChart(){
    $.post("./index/Alistock/getKLine", {code:selectedCode}, function(data){
        if(data.showapi_res_code != '0' || data.showapi_res_body.ret_code != '0'){
            return;
        }
        var dataList = data.showapi_res_body.dataList;
        var data0 = [];
        for(var i = 0; i < dataList.length; i++){
            var day = dataList[i]['time'];
            day = day.substring(0,4) + "/" + day.substring(4,6) + "/" + day.substring(6,8);
            data0[i] = [day, dataList[i]['open'], dataList[i]['close'], dataList[i]['min'], dataList[i]['max']];

        }
        setKChartData(data0);
    }, 'json');
}

function setKChartData(dataArray) {

//---K线图
    //初始化K线图
    var myChartK = echarts.init(document.getElementById('chartK'));
    // 数据意义：开盘(open)，收盘(close)，最低(lowest)，最高(highest)
    var data0 = splitData(dataArray);

    option = {
        title: {
            text: '股市K线图',
            left: 0
        },
        tooltip: {
            trigger: 'axis',
            axisPointer: {
                type: 'cross'
            },
            formatter:function(data){
                var dom='开盘价'+data[0].value[1]+'</br>';
                dom+='收盘价'+data[0].value[2]+'</br>';
                dom+='最低价'+data[0].value[3]+'</br>';
                dom+='最高价'+data[0].value[4];
                return dom
            }
        },
        legend: {
            data: ['日K', 'MA5', 'MA10', 'MA20', 'MA30']
        },
        grid: {
            left: '10%',
            right: '10%',
            bottom: '15%'
        },
        xAxis: {
            type: 'category',
            data: data0.categoryData,
            scale: true,
            boundaryGap: false,
            axisLine: {onZero: false},
            splitLine: {show: false},
            splitNumber: 20,
            min: 'dataMin',
            max: 'dataMax'
        },
        yAxis: {
            scale: true,
            splitArea: {
                show: true
            }
        },
        dataZoom: [
            {
                type: 'inside',
                start: 0,
                end: 100
            },
            {
                show: true,
                type: 'slider',
                y: '90%',
                start: 0,
                end: 100
            }
        ],
        series: [
            {
                name: '日K',
                type: 'candlestick',
                data: data0.values,
                markPoint: {
                    label: {
                        normal: {
                            formatter: function (param) {
                                return param != null ? Math.round(param.value) : '';
                            }
                        }
                    },
                    data: [
                        {
                            name: 'XX标点',
                            coord: ['2013/5/31', 2300],
                            value: 2300,
                            itemStyle: {
                                normal: {color: 'rgb(41,60,85)'}
                            }
                        },
                        {
                            name: 'highest value',
                            type: 'max',
                            valueDim: 'highest'
                        },
                        {
                            name: 'lowest value',
                            type: 'min',
                            valueDim: 'lowest'
                        },
                        {
                            name: 'average value on close',
                            type: 'average',
                            valueDim: 'close'
                        }
                    ],
                    tooltip: {
                        formatter: function (param) {
                            return 'param.name' + '<br>' + (param.data.coord || '');
                        }
                    }
                },
                markLine: {
                    symbol: ['none', 'none'],
                    data: [
                        [
                            {
                                name: 'from lowest to highest',
                                type: 'min',
                                valueDim: 'lowest',
                                symbol: 'circle',
                                symbolSize: 10,
                                label: {
                                    normal: {show: false},
                                    emphasis: {show: false}
                                }
                            },
                            {
                                type: 'max',
                                valueDim: 'highest',
                                symbol: 'circle',
                                symbolSize: 10,
                                label: {
                                    normal: {show: false},
                                    emphasis: {show: false}
                                }
                            }
                        ],
                        {
                            name: 'min line on close',
                            type: 'min',
                            valueDim: 'close'
                        },
                        {
                            name: 'max line on close',
                            type: 'max',
                            valueDim: 'close'
                        }
                    ]
                }
            },

        ]
    };

    // 使用刚指定的配置项和数据显示图表。
    myChartK.setOption(option);

}

//点击刷新按钮
var refreshBtnDisabled = false;
$("#refreshBtn").off().click(function(e){
    if(refreshBtnDisabled){
        return;
    }
    refreshBtnDisabled = true;
    stockInit();
    //最多2秒钟点击一次
    setTimeout(function(){
        refreshBtnDisabled = false;
    }, 5000);
});





//金额的点击事件
$("#buy_price_ul > li").click(function(e){
//  $(this).addClass("active").siblings("li").removeClass("active");
    var price = parseInt($(this).html()); //点击的金额1万到50万
//  $("#check-surplus_ul>li").html(price * 5000);
//  $("#stop-loss_ul>li:eq(0)").html(price * -1000);
//  $("#stop-loss_ul>li:eq(1)").html(price * -1333);
//  $("#stop-loss_ul>li:eq(2)").html(price * -1700);
//  $("#stop-loss_ul>li:eq(3)").html(price * -2000);
//  $("#stop-loss_ul>li:eq(4)").html(price * -2100);
//  $("#stop-loss_ul>li:eq(5)").html(price * -2200);
//  $("#publicFee").html(price * publicFee );
//  $("#delay_fee").html(price * delayFee);
//
//
//  $("#stop-loss_ul > li:eq(0)").click();

    //输入框
    $('#buy_number').val(price).trigger('keyup');

    //更新资金利用率数据
//  updateMoneyRate();


});
//小数点传值1
function updateMoneyRate(){
    var price = parseFloat($('#buy_number').val());
    if($('#buy_number').val()==''||$('#buy_number').val()=='0'){price=1}
    if(price>50){$('#buy_number').val('50');price=50;}
    //可买入-股，资金利用率-%
    var nowPrice = parseFloat( $("#nowPrice").html() ); //当前的股票价钱
    if(nowPrice=='0'){
        $("#gu").html('-');
        $("#lyl").html('-');
    }else{
        var gu=((price*10000/nowPrice)/100)*100;
        gu=gu.toFixed(2);
        var lyl=(nowPrice*gu/(price*10000)*100).toFixed(2) + "%";
        $("#gu").html(gu);
        $("#lyl").html(lyl);
    }
}



// $(function(){
//     $("#stop-loss_ul > li:eq(0)").click();
// });
$(function () {
    var month_loss = $("#yuebeishu >p").eq(0).find("i").html()-3 ;//按天
    // alert(month_loss);
    // $("#stop-loss_ul > li:eq(1)").click();
    var datas =$("#yuebeishu >p").eq(0).find("i").html();
    if(datas== ''){
        $("#stop-loss_ul > li:eq(0)").click();
    }else {
        $("#stop-loss_ul > li").eq(month_loss).click();
        if(month_loss != null && month_loss!=-3){
            // consol$("#stop-loss_ul > li").html();
            $("#stop-loss_ul > li").eq(month_loss).trigger('tap');
            $("#stop-loss_ul > li").eq(month_loss).addClass("active").siblings("li").removeClass("active");
        }
    }


});

//小数点传值
$("#stop-loss_ul > li").click(function(e){
    var index = $(this).index(); //下标
    console.log(index);

    var buy_price = parseFloat($('#buy_number').val()); //输入的价格
    if($('#buy_number').val()==''||$('#buy_number').val()=='0'){buy_price=1}
    if(buy_price>50){$('#buy_number').val('50');buy_price=50;}
    $(this).addClass("active").siblings("li").removeClass("active");
    var p = parseInt($(this).html());
    // var ly_arr=[8, 6, 5];
    var ly_arr=[3, 4, 5, 6, 7, 8, 9, 10];
    var ly = parseInt( buy_price * 10000 / ly_arr[index] );
    $("#guaranteeFee").html(ly);
    //触发止损
    $("#stop-loss_ul > li ").each(function(i, o){
        var stop_loss = parseInt( buy_price * 10000 / ly_arr[i] ) * stopLossRate + buy_price*10000;
        var index_num= i+3;
        stop_loss = parseInt(stop_loss);
        $(o).html(-stop_loss);
        $(o).attr('id','inter_'+levers_month_data[i]+'_'+index_num);
    });
    //获取天数的值
    var month_number=$(".select_month option:selected").val();
    var interest_id =$(this)[0].id.split('_')[1];
    var index_num= $(this)[0].id.split('_')[2];
    var input_price =$('#buy_number').val();

    //TODO:6
    //公式：倍数杠杆/100*配资得的金额*10000/倍数*天数
    if(interest_id !==null){
        //警戒线
        $("#check-surplus_ul>li").html(-(input_price/index_num *10000*lossLine+input_price*10000).toFixed(2));

        $("#publicFee").html((interest_id*input_price*month_number*100).toFixed(2) );
    }
    //总计
    var total= ly+parseFloat($("#publicFee").html()); //总金
    $('#total').html(total);

    // var delay_line = parseInt( $("#stop-loss_ul > li.active ").html() * delayLineRate );
    // $("#delay_line").html(Math.abs(delay_line));

});

//按天配资选择天数触发的事件，让管理费收取
$(".select_month").change(function () {
    var month_numbers = $(".select_month option:selected").val();
    //获取天数的值
    var interest_id =$('#stop-loss_ul .active')[0].id.split('_')[1]; //倍率
    var index_num= $('#stop-loss_ul .active')[0].id.split('_')[2]; //倍数
    var input_price =$('#buy_number').val(); //配资的金额（倍数*保证金需要/10000）
    //公式：倍率杠杆/100*配资得的金额*10000/倍数*天数
    if(interest_id !==null){
        $("#publicFee").html((interest_id*input_price*month_numbers*100).toFixed(2) );
        $("#check-surplus_ul>a").html(-(input_price/index_num *10000*lossLine+input_price*10000).toFixed(2)); //警戒线
    }
    //保证金
    var bond = parseInt(input_price*10000/index_num);
    //总计(保证金+管理费)
    var total= bond + parseFloat($("#publicFee").html()); //总金
    $('#total').html(total);
});



//"点买"按钮
$("#btn_buy").click(function(e){
    var agree_val=$('input[name="agree_pro"]:checked').val();
    if(!agree_val){
        alert("请阅读并签署谋略协议");
        // tool.popup_err_msg("请阅读并签署谋略协议");
        return;
    }
    $.post("./isLogin", {}, function(data){
        if(data != 1){
            alert("请先登录");
            window.location.href ='./login';
            tool.popup.showPopup($("#popup-user-login"));
        }else{
            $("#popup-buy").show();
            $("#popBg").show();
        }
    });

    updateStockNumber();


});
$("#popup-buy .js-close-popup").click(function(e){
    $("#popup-buy").hide();
    $("#popBg").hide();
});

//点买弹出层的确定按钮
$("#popup-confirm-btn").click(function(e){
    var params = {};
    var nowPrice =parseFloat($('.J_price').html());   //股票的成交价格
    var dealAmount=parseFloat($('#buy_number').val()); //交易总操盘
    var dealQuantity =parseInt($('#t_shou').html());//交易多少手(数量)
    var month_num =$('#buy_month_num').html();//配资的天数
    //递延条件？(保证金*后台递延的多少倍)
    var guaranteeFee =$("#guaranteeFee").html();
    var fee_condition = guaranteeFee * delayLineRate; //递延条件
    var Multiple  =Math.floor(dealAmount*10000/guaranteeFee); //配资的倍数
    var levers_multiples;
    /*倍数后台过来的尴尬倍率*/
    switch(Multiple)
    {
        case 3:
             levers_multiples = levers_month_3;
            break;
        case 4:
             levers_multiples = levers_month_4;
            break;
        case 5:
             levers_multiples = levers_month_5;
            break;
        case 6:
             levers_multiples = levers_month_6;
            break;
        case 7:
             levers_multiples = levers_month_7;
            break;
        case 8:
             levers_multiples = levers_month_8;
            break;
        case 9:
             levers_multiples = levers_month_9;
            break;
        case 10:
             levers_multiples = levers_month_10;
            break;
        default:
            levers_multiples = 0;
            break
    }

    //当过了递延条件之后自动每天的递延费(倍数对应的倍率*保证金*倍数*1天)
    var day_deferred =levers_multiples*guaranteeFee*Multiple;
    if($('#buy_number').val()==''||$('#buy_number').val()=='0'){dealAmount=1}
    params['stockCode'] = selectedCode; //股票的代码
    params['nowPrice'] = nowPrice;              // 股票的成交价格
    params['dealAmount'] = dealAmount;  //交易总操盘
    params['dealQuantity'] =dealQuantity;                                   //交易多少手(数量)
    params['surplus'] = parseFloat($("#check-surplus_ul > li.active").html()); //警戒线
    params['loss'] = parseFloat($("#stop-loss_ul > li.active").html());       //亏损线
    params['publicFee'] = parseFloat($("#publicFee").html());                   //  综合费
    params['guaranteeFee'] = parseFloat($("#guaranteeFee").html());              //履约保证金
    params['buy_month_num'] =month_num;//配资的天数
    params['delayLine'] =parseFloat(fee_condition).toFixed(2);//递延条件
    params['delayFee'] =parseFloat(day_deferred).toFixed(2);//递延费（元/天)

    params['all_price_sum'] =parseFloat($("#totals").html());//总计金额
    params['stock_by_pay_type'] =2;//判断买入的类型是按月还是按日（1为按天，2为按月）
    params['Multiple'] =Multiple;//买入时的倍数
    params['levers_multiples'] =levers_multiples;//买入时的倍数
    // params['delayLine'] = parseInt($("#delay_line").html());                    //
    // params['delayFee'] = parseInt($("#delay_fee").html());
    $.post("./ucenter/stockBuyByMonth", params, function(data){
        console.log(data);
        if(data.code =='-1'){
            alert(data.msg);
        }
        if(data.code == '0'){
            alert(data.msg);
            location.href = "./month_buy";
        }
        if(data.code == '1'){
            alert(data.msg);
            window.location.href ='./sell';
        }
        // else{
        //     tool.popup_err_msg(data.msg);
        //     $('#popBg').hide()
        // }
    }, 'json');
});

//根据股票实时价格 更新弹出层的交易数量
function updateStockNumber(){
    $("#t_stock_name").html($("#stockName").html());
    var t_principal=parseFloat($('#buy_number').val());
    if($('#buy_number').val()==''||$('#buy_number').val()=='0'){t_principal=1}
    $("#t_principal").html(t_principal + "万元");
    var nowPrice = parseFloat( $("#nowPrice").html() );
    var month_numbers = $(".select_month option:selected").val();
    $('#buy_month_num').html(month_numbers);
    var amount = t_principal;
    $("#t_shou").html(parseInt(amount * 10000 / nowPrice / 100));
    var totals = parseFloat($('#total').html());
    $('#totals').html(totals);
}


var initTimeLine={
    chartLine: {
        chart: undefined,
        init: function (selector, json, full) {
            if (!json || !json.records)
                return false;
            if (this.chart) {
                this.chart.clear();
                //释放非托管资源？
                this.chart.dispose();
                this.chart = undefined;
            }
            //找到html元素#chart
            var myChart = echarts.init($(selector).get(0));

            var xdata = [], ydata = [];
            var ymin, ymax;
            //遍历数据，json.records是毫秒数+价格

            $.each(json.records, function (i, n) {//第一次   i = 0, n = [1500341400000, 6.45]
                if (i == 0) {
                    ymin = ymax = n[1];  //ymin = ymax=6.45
                }
                xdata.push(n[0])//xdata=1500341400000，1500341400011
                //比较找出最小价格和最大价格
                ymin = ymin > n[1] ? n[1] : ymin;
                ymax = ymax < n[1] ? n[1] : ymax;
                ydata.push(n[1]);//ydata=6.45，6.46
            });
            //这时xdata是一个数组，里面是毫秒数；ydata是价格的数组
            var a = ymin, b = json.y_close, c = ymax;
            //a是最小价格，b是收盘价，c是最大价格
            var ab = Math.abs(b - a);//收盘价往上y最大绝对值
            var cb = Math.abs(c - b);//收盘价往下y最大绝对值

            var speed = ab > cb ? ab : cb;//离中心线最远的点
            //console.log(speed, b);
            var min = a > b ? (b > c ? c : b) : a;//找到a，b，c的最小值

            if (a >= b && c >= b)//？？
                min = min - speed;
            //ymin = 0;
            //ymax = speed * 2;
            ymin = json.y_close - speed * 1.2;
            //最低价=收盘价-远点*1.2
            ymax = (+json.y_close) + speed * 1.2;
            //最高价=收盘价+远点*1.2
//              for (var i = 0; i < ydata.length; i++) {
//                  //ydata[i] = ydata[i] - min
//              }
            full = full || 242;//？
            for (var i = 0; i < full - xdata.length; i++) {
                xdata.push("-");
                ydata.push("-");
            }
            //标记线，昨天的收盘价
            var markLineData = [
                [
                    { name: '', xAxis: 0, yAxis: json.y_close },
                    { name: '', xAxis: xdata.length - 1, yAxis: json.y_close }
                ]
            ];

            var b_date=new Date(xdata[0]);
            var b_y= b_date.getFullYear();//year
            var b_m= (b_date.getMonth() + 1)<10?'0'+(b_date.getMonth()+1):(b_date.getMonth()+1);//month
            var b_d= b_date.getDate()<10?'0'+b_date.getDate():b_date.getDate(); //day
            //ymax = ymax * 1.01;
            //ymin = ymin * 0.99;
            //var markLineData = [
            //     [
            //          { name: '昨日收盘', xAxis: 0, yAxis: json.y_close },
            //          { name: json.y_close, xAxis: xdata.length - 1, yAxis: json.y_close }
            //     ]
            //];
            var config = {
                animation: false,
                title: {
                    show: false
                },
                grid: {
                    x: 40,
                    x2: 45,
                    y: 5,
                    y2: 5,
                    borderColor: "#eee"
                },
                tooltip: {
                    trigger: 'axis',
                    borderColor: "#ccc",
                    showDelay: 10,
                    hideDelay: 10,
                    transitionDuration: 0.1,
                    borderWidth: 1,
                    backgroundColor: "#ffffff",
                    textStyle: { color: "#666", fontSize: 11, fontFamily: "微软雅黑" },
                    padding: 10,
                    formatter: function (data) {
                        //时间
                        var date = new Date(data[0].name-0);
                        var y= date.getFullYear();//year
                        var m= (date.getMonth() + 1)<10?'0'+(date.getMonth()+1):(date.getMonth()+1);//month
                        var d= date.getDate()<10?'0'+date.getDate():date.getDate(); //day
                        var h= date.getHours()<10?'0'+date.getHours():date.getHours(); //h
                        var mm= date.getMinutes()<10?'0'+date.getMinutes():date.getMinutes(); //m
                        var s= date.getSeconds()<10?'0'+date.getSeconds():date.getSeconds(); //s
                        var price = data[0].data;
                        if (price != "-") {
                            var dom = y+'年'+m+'月'+d+'日';
                            price = parseFloat(price);
                            var p = (price - json.y_close).toFixed(2);
                            var pr = (p / json.y_close * 100).toFixed(2) + "%";
                            dom += "<br/>时间：" +h+':'+mm+':'+s;
                            dom += "<br/>价格：<span style='color:" + (p > 0 ? "red" : "green") + ";'>" + price.toFixed(2) + "</span>";
                            dom += "<br/>涨跌：<span style='color:" + (p > 0 ? "red" : "green") + ";'>" + p + "</span>";
                            dom += "<br/>涨跌幅：<span style='color:" + (p > 0 ? "red" : "green") + ";'>" + pr + "</span>";
                        }
                        else {
                            var dom=b_y+'年'+b_m+'月'+b_d+'日';
                            dom += "<br/>时间：-";
                            dom += "<br/>价格：-";
                            dom += "<br/>涨跌：-";
                            dom += "<br/>涨跌幅：-";
                        }
                        return dom;
                    },
                    axisPointer: {
                        lineStyle: {
                            color: '#ccc',
                            width: 1,
                            type: 'solid'
                        }
                    }
                },
                legend: {
                    show: false,
                    data: ['-']
                },
                toolbox: {
                    show: false
                },
                calculable: false,
                xAxis: [
                    {
                        type: 'category',
                        boundaryGap: false,
                        data: xdata,
                        axisLine: { show: false },
                        axisTick: { show: false },
                        splitNumber: 0,
                        splitLine: {
                            show: false,
                            lineStyle: {
                                color: ['#ccc'],
                                width: 1,
                                type: 'dashed'
                            }
                        }
                    }
                ],
                yAxis: [
                    {
                        show: true,
                        type: 'value',
                        position: 'left',
                        min: ymin,
                        max: ymax,
                        boundaryGap: false,
                        splitNumber: 4,
                        axisLine: { show: false },
                        axisTick: { show: false },
                        axisLabel: {
                            formatter: function (data) {
                                return data.toFixed(2);
                            },
                            textStyle: {
                                color: function (data) {
                                    var d = 1 * (+data).toFixed(2);
                                    if (d > json.y_close) return '#dd2200';
                                    if (d < json.y_close) return '#33aa60';
                                    if (json.y_close == "--") return '#c8c8c8';
                                    return '#c8c8c8';
                                }
                            }
                        },
                        splitLine: {
                            show: true,
                            lineStyle: {
                                color: ['#ccc'],
                                width: 1,
                                type: 'dashed'
                            }
                        }
                    },
                    {
                        type: 'value',
                        position: 'right',
                        min: (ymin - json.y_close) / json.y_close * 100,
                        max: (ymax - json.y_close) / json.y_close * 100,
                        boundaryGap: false,
                        splitLine: { lineStyle: { color: '#f1f1f1', width: 1, type: 'solid' } },
                        axisLine: { lineStyle: { color: '#f1f1f1', width: 0, type: 'solid' } },
                        splitNumber: 4,
                        axisLabel: {
                            formatter: function (data) { return Math.abs(data).toFixed(2) + "%"; },
                            textStyle: {
                                color: function (data) {
                                    if (json.y_close == "--") return '#c8c8c8';
                                    var d = 1 * parseFloat(data).toFixed(3);
                                    if (d >= 0.001) return '#dd2200';
                                    if (d < 0) return '#33aa60';
                                    return '#c8c8c8';
                                }
                            }
                        }
                    }
                ],
                series: [
                    {
                        name: '-',
                        type: 'line',
                        itemStyle: {
                            normal: {
                                areaStyle: { type: 'default' },
                                color: "#d5e1f2",
                                borderColor: "#3b98d3",
                                lineStyle: { width: 1, color: ['#3b98d3'] },
                            }
                        },
                        data: ydata,
                        symbol: "circle",
                        symbolSize: 5,
                        markLine: {
                            symbol: "none",
                            clickable: false,
                            large: true,
                            itemStyle: {
                                normal: {
                                    lineStyle: {
                                        color: ['#F96900'],
                                        width:1,
                                        type: 'dashed'
                                    }
                                }
                            },
                            data: markLineData
                        }
                    }
                ]
            };
            myChart.setOption(config);
            $(selector).css("background", "none");
            this.chart = myChart;
            //console.log(this.chart);
        },
        push: function (json, full) {
            //console.log(this.chart);
            if (!this.chart) return;
            if (!json || !json.records)
                return false;
            var option = this.chart.getOption();
            var xdata = [], ydata = [];
            var ymin, ymax;
            $.each(json.records, function (i, n) {
                if (i == 0) {
                    ymin = ymax = n[1];
                }
                xdata.push(n[0])
                ymin = ymin > n[1] ? n[1] : ymin;
                ymax = ymax < n[1] ? n[1] : ymax;
                ydata.push(n[1]);
            });

            var a = ymin, b = json.y_close, c = ymax;
            var ab = Math.abs(b - a);
            var cb = Math.abs(c - b);

            var speed = ab > cb ? ab : cb;//中心线
            var min = a > b ? (b > c ? c : b) : a;

            if (a >= b && c >= b)
                min = min - speed;
            ymin = 0;
            ymax = speed * 2;

            for (var i = 0; i < ydata.length; i++) {
                ydata[i] = ydata[i] - min;
            }

            full = full || 270;
            for (var i = 0; i < full - xdata.length; i++) {
                xdata.push("-");
                ydata.push("-");
            }
            var markLineData = [
                [
                    { name: '', xAxis: 0, yAxis: speed },
                    { name: '', xAxis: xdata.length - 1, yAxis: speed }
                ]
            ];
            //ymax = ymax * 1.01;
            //ymin = ymin * 0.99;
            option.yAxis[0].min = ymin;
            option.yAxis[0].max = ymax;
            option.xAxis[0].data = xdata;
            option.series[0].data = ydata;
            option.series[0].markLine.data = markLineData;
            this.chart.setOption(option);
        }
    }

}

$('#buy_number').on('onkeyup',function(){

})
//小数点传值
//失去焦点时控制范围
$("#buy_number").blur(function(){
    var nub=$("#buy_number").val();
    if(nub==""){
        $("#buy_number").val("1");
    }
    // else if(nub<1){
    else if(nub<0.01){
        $("#buy_number").val("1");
    }else if(nub>50){
        $("#buy_number").val("50");

    }

});
//按键抬起时判断值来触发按钮
$("#buy_number").off('keyup').on('keyup',function(){
    var price = parseInt($('#buy_number').val()); //这是1到50万那里
    if($("#buy_number").val()==''||$('#buy_number').val()=='0'){price=1}
    if(price>50){$("#buy_number").val('50');price=50;}
    //金额高亮
    $.each($('#buy_price_ul > li'),function(i,e){
        if(parseInt($(e).html())==price){
            $(e).addClass("active").siblings("li").removeClass("active");
            return false;
        }
        $('#buy_price_ul > li').removeClass("active");
    });
    // $("#check-surplus_ul>li").html(price * 5000); //警戒线
    // $("#stop-loss_ul>li:eq(0)").html(price * -1000);
    // $("#stop-loss_ul>li:eq(1)").html(price * -1333);
    // $("#stop-loss_ul>li:eq(2)").html(price * -1700);
    // $("#publicFee").html(price * publicFee );
    // $("#delay_fee").html(price * delayFee);
    $("#check-surplus_ul>li").html(-(price/3 *10000*lossLine+price*10000).toFixed(2)); //警戒线


    //TODO:
    //获取天数的值
    //公式：倍数杠杆/100*配资得的金额*10000/倍数*天数
    var month_number=$(".select_day option:selected").val();
    $("#publicFee").html((price*month_number*levers_month_3*10000/100 ).toFixed(2) );

    //触发止损默认第一个高亮
    // $("#stop-loss_ul > li:eq(0)").trigger('tap');
    // $("#stop-loss_ul>li:eq(0)").html(price * -1000);
    // $("#stop-loss_ul>li:eq(1)").html(price * -1333);
    // $("#stop-loss_ul>li:eq(2)").html(price * -1700);
    // $("#publicFee").html(price * publicFee );
    // $("#delay_fee").html(price * delayFee);


    // $("#stop-loss_ul > li:eq(0)").click();
    var month_loss = $("#yuebeishu >p").eq(0).find("i").html()-3 ;//按天
    var datas =$("#yuebeishu >p").eq(0).find("i").html();
    if(datas== ''){
        $("#stop-loss_ul > li:eq(0)").click();
        $("#stop-loss_ul > li:eq(0)").trigger('tap');
    }else {
        $("#stop-loss_ul > li").eq(month_loss).click();
        if(month_loss != null && month_loss!=-3){
            // consol$("#stop-loss_ul > li").html();
            $("#stop-loss_ul > li").eq(month_loss).trigger('tap');
            $("#stop-loss_ul > li").eq(month_loss).addClass("active").siblings("li").removeClass("active");
        }
    }
    //更新资金利用率数据
    updateMoneyRate();

})

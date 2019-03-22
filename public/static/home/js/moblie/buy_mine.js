//选中的股票代码
 var selectedCode = "000001";
var stopShare=true;

function updateMoneyRate(){
    var price = $('#buy_number').val();
    if($('#buy_number').val()==''||$('#buy_number').val()=='0'){price=1}
    if(price>500){$('#buy_number').val('500');price=500}
    //可买入-股，资金利用率-%
    var nowPrice = parseFloat( $("#nowPrice").html() );
    if(nowPrice=='0'){
        $("#gu").html('-');
        $("#lyl").html('-');
    }else{
        var gu=Math.floor((price*10000/nowPrice)/100*100);
        var lyl=(nowPrice*gu/(price*10000)*100).toFixed(2) + "%";
        $("#gu").html(gu); //可买入多少股
        $("#lyl").html(lyl);//资金利用率
    }
}


var buy_moblie={

    /**
     * 初始化
     */
    init:function(){
        this.eventsBind();
    },
    /**
     * 事件
     */
    eventsBind:function(){
        var base=this;

        //date
        Date.prototype.format = function (format) {
            var o = {
                "M+": this.getMonth() + 1, //month
                "d+": this.getDate(), //day
                "h+": this.getHours(), //hour
                "m+": this.getMinutes(), //minute
                "s+": this.getSeconds(), //second
                "q+": Math.floor((this.getMonth() + 3) / 3), //quarter
                "S": this.getMilliseconds() //millisecond
            }

            if (/(y+)/.test(format)) {
                format = format.replace(RegExp.$1, (this.getFullYear() + "").substr(4 - RegExp.$1.length));
            }

            for (var k in o) {
                if (new RegExp("(" + k + ")").test(format)) {
                    format = format.replace(RegExp.$1, RegExp.$1.length == 1 ? o[k] : ("00" + o[k]).substr(("" + o[k]).length));
                }
            }
            return format;
        };

        //点击input focus
        $('#searchTxt1').off('focus').on('focus',function(e){
            //让下面的消失，让ul出来
            $('.search_ul').show();
            $('.share_title,.chart_box,.stock-price,#buy_step1').hide();
            $('#item2').hide();


                $(this).off('keyup').on('keyup',function(){//关键词
                    var keywords=$(this).val().toLowerCase(); //在input中输入时
                    // console.log(keywords);
                    // alert(keywords);
                base.searchCue(keywords);
            })

        }).on('blur',function(){
            $('.search_ul').hide();
            $('.search_ul').html('');
            $('.search_ul').empty();
            $('#item1>.share_title,.chart_box,.stock-price,#buy_step1').show()
        })

        //点击买入step1显示item2
        $('#buy_step1').on('tap',function(){
            if($(this).attr('tapEvent')=='true'){
                $('#item1 .share_title,#item1 .chart_box,#item1 .stock-price').hide();
                $('#item2,#item2 .share_title').show();
            }
        })

        //金额的点击事件
        $("#buy_price_ul > li").on('tap',function(e){
            //点击金额高亮
		    $(this).find('a').addClass("chose-active").end().siblings("li").find('a').removeClass("chose-active");

            var price = parseInt($(this).find('a').html());
            //输入框
            $('#buy_number').val(price).trigger('keyup');
        });

        //触发止损的点击事件
        $("#stop-loss_ul > a").on('tap',function(e){
            //当前点击高亮
            $(this).addClass("chose-active").siblings("a").removeClass("chose-active");

            var index = $(this).index();
            //找到之前高亮的金额
            var buy_price = $('#buy_number').val();
            if($('#buy_number').val()==''||$('#buy_number').val()=='0'){buy_price=1}
            if(buy_price>200){$('#buy_number').val('200');buy_price=200;}

            //高亮的触发止损价格
            var p = parseInt($(this).html());
            var ly_arr=[3, 4, 5, 6, 7, 8, 9, 10];
            var ly = parseInt( buy_price * 10000 / ly_arr[index] ); //保证金
            if(ly<200){
                $("#g_text").html("(您的配资金额太少，请在“我要配资金额”输入更大值)");
            }else {
                $("#g_text").html("");
            }
            //改变履约保证金价格
            $("#guaranteeFee").html(ly);

//			//递延条件
//		    var delay_line = ly - 600 * buy_price;
//		    $("#delay_line").html(-delay_line);

            //根据之前高亮的价格改变触发止损价格
            $("#stop-loss_ul > a").each(function(i, o){
                var stop_loss = parseInt( (buy_price * 10000 / ly_arr[i] ) * stopLossRate+buy_price*10000);
                var index_num= i+3;
                stop_loss = parseInt(stop_loss);
                $(o).html(-stop_loss);
                $(o).attr('id','inter_'+levers_data[i]+'_'+index_num);
            });

            //获取天数的值
            var day_number=$(".select_day option:selected").val();

            var interest_id =$(this)[0].id.split('_')[1];
            var index_num= $(this)[0].id.split('_')[2];
            var input_price =$('#buy_number').val();

            //TODO:6
            //公式：倍数杠杆/100*配资得的金额*10000/倍数*天数
            if(interest_id !==null){
                //警戒线
                $("#check-surplus_ul>a").html(-(input_price/index_num *10000*lossLine+input_price*10000).toFixed(2));
                $("#publicFee>span").html((interest_id*input_price*day_number*100).toFixed(2) );
            }
            //总计
            var total= ly+parseFloat($("#publicFee>span").html()); //总金
            $('#total').html(total);

        });


        //按天配资选择天数触发的事件，让管理费收取
        $(".select_day").change(function () {
            var day_numbers = $(".select_day option:selected").val();
            //获取天数的值
            var interest_id =$('#stop-loss_ul .chose-active')[0].id.split('_')[1]; //倍率
            var index_num= $('#stop-loss_ul .chose-active')[0].id.split('_')[2]; //倍数
            var input_price =$('#buy_number').val(); //配资的金额（倍数*保证金需要/10000）
            //公式：倍率杠杆/100*配资得的金额*10000/倍数*天数
            if(interest_id !==null){
                $("#publicFee>span").html((interest_id*input_price*day_numbers*100).toFixed(2) );
                $("#check-surplus_ul>a").html(-(input_price/index_num *10000*lossLine+input_price*10000).toFixed(2)); //警戒线
            }
            //保证金
            var bond = parseInt(input_price*10000/index_num);
            //总计(保证金+管理费)
            var total= bond + parseFloat($("#publicFee>span").html()); //总金
            $('#total').html(total);
        });

        $(function(){
            //获取股票实时数据和分时数据。每几秒钟就刷新一次
            // base.stockInit();

            //获取股票K线图。只在页面打开时加载一次，不需要再刷新
            // base.initKChart();

            // //TODO:页面加载时金额默认点击第一个止损a
            //     var abc = $("#buy >p").eq(0).find("i").html()-1 ;
            //
            //
            // // $("#stop-loss_ul > a").eq(abc).addClass("chose-active").siblings("a").removeClass("chose-active");
            // if(abc != null){
            //     $("#stop-loss_ul > a").html(abc);
            //     $("#stop-loss_ul > a").eq(abc).trigger('tap');
            //     $("#stop-loss_ul > a").eq(abc).addClass("chose-active").siblings("a").removeClass("chose-active");
            // }

            //TODO:页面加载时金额默认点击第一个止损a
            var day_loss = $("#buy >p").eq(0).find("i").html()-1 ;//按天
            var month_loss = $("#yuebeishu >p").eq(0).find("i").html()-1 ;//按月
            // $("#stop-loss_ul > a").eq(abc).addClass("chose-active").siblings("a").removeClass("chose-active");
            if(day_loss != null && day_loss!=-1){
                $("#stop-loss_ul > a").html(day_loss);
                $("#stop-loss_ul > a").eq(day_loss).trigger('tap');
                $("#stop-loss_ul > a").eq(day_loss).addClass("chose-active").siblings("a").removeClass("chose-active");
            }
            if(month_loss != null && month_loss!=-1){
                $("#stop-loss_ul > a").html(month_loss);
                $("#stop-loss_ul > a").eq(month_loss).trigger('tap');
                $("#stop-loss_ul > a").eq(month_loss).addClass("chose-active").siblings("a").removeClass("chose-active");
            }
            if(day_loss ==-1 && month_loss==-1){
                $("#stop-loss_ul > a").html(month_loss);
                $("#stop-loss_ul > a").eq(month_loss).trigger('tap');
                $("#stop-loss_ul > a").eq(month_loss).addClass("chose-active").siblings("a").removeClass("chose-active");
            }

        });

        var freshTimeInterval = 10 * 1000;
        var freshTimes = 0;

        //定时器 获取实时数据（最新数据）, 每几秒钟就刷新一次
        setInterval(function(){
            if(!buy_moblie.isTradingTime()||!stopShare){ // 如果不在交易时间就不必刷新数据
                return ;
            }
            freshTimes ++ ;
            if(freshTimes > 10){
                freshTimeInterval = 60 * 1000;
            }
            //获取股票实时数据
            //  buy_moblie.getStockInfo();
            // buy_moblie.searchCue();

        }, 1000 );

        //定时器 获取分时数据
        setInterval(function(){
            if(!buy_moblie.isTradingTime()){ // 如果不在交易时间就不必刷新数据
                return ;
            }

            //获取分时数据
            // buy_moblie.getTimeLine();
            // buy_moblie.selectAShare();
        }, 60 * 1000);


        //"点买"step2按钮
        $("#buy_step2").on('tap',function(e){
            $("#buy_number").trigger('blur');
            var agree_val=$('input[name="agree_pro"]:checked').val();
            if(!agree_val){
                mui.alert("请阅读并签署谋略协议");
                return;
            }
            //5秒后才可以点击
            $(this).attr('disabled',true);
            //判断有没有登录
            $.post("./isLogin", {}, function(data){
                $("#buy_step2").attr("disabled", false);
                if(data != 1){
                    //alert("请先登录")
                    mui.alert('请先登录');
                    location.href='./login.html'
                }else{
                    // buy_moblie.updateStockNumber(2);
                }
            });

            buy_moblie.updateStockNumber();

        });


    },
    /**
     * 向后台发送查询数据,并渲染列表
     */
    searchCue:function(keywords){
        //发送请求
        $.ajax({
            type:"get",
            url:"./index/Alistock/getSharesByKeyWords",
            data:{keywords:keywords},
            dataType:'json',
//			beforeSend:function(){
//	           $("#vvv").append('<img src="../../images/loading.gif"  />');
//	        },
            success:function(data){
                //1.清空ul
                $('.search_ul').html("");
                //2.渲染(if如果没数据，else如果有数据)
                var html='';
                if(data.data==null||data.data==''){
                    html='<li class="search_li" style="text-align: center;color: #ccc;">暂无数据</li>';
                }else{
                    for(var i=0;i<data.data.length&&i<10;i++){
                        html+='<li class="search_li"><span class="result1">'+data.data[i].code+'</span><span class="result2">'+data.data[i].name+'</span>';
                    }
                }
                $('.search_ul').html(html);

                //3.选中股票，改变标题，隐藏搜索ul，发送给后台查询股票行情
                buy_moblie.selectAShare($('.search_ul li'));
            }
        });
    },



    /**
     * 选中股票，改变标题，隐藏搜索框
     */
    selectAShare:function (dom){

        dom.on('tap',function(e){
            $('#searchTxt1').trigger('blur');
            var ashareCode=$(this).find('span').eq(0).html();//股票编号
            var ashareName=$(this).find('span').eq(1).html();//股票名
            // alert(ashareCode);


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

                        }
                    },
                    error:function (data) {
                        console.log("错误");
                    }
            });

            //渲染到页面标题上
            $('.title_l').find('#stockName').html(ashareName); //股票名称
            $('.title_l').find('#stockNum').html(ashareCode);  //股票代号

            //搜索框的值清空
            $("#searchTxt1").val("");
            $('.search_ul').hide();

            selectedCode = ashareCode;

            //loading
            $('.loader').show();

        });

    },


    /**
     * 初始化页面 获取股票实时数据、分时数据、K线图
     */

    stockInit:function (){
        //获取股票实时数据
        buy_moblie.getStockInfo();

        //获取分时数据
        // buy_moblie.getTimeLine();

    },

    /**
     * 获取股票实时数据
     */




    getStockInfo:function (){

        $('#buy_step1').html('买入').attr('tapEvent',true).css({'background':'var(--color_red)'});

        $.post("./index/Alistock/getStockInfo", {code:selectedCode}, function(data){

            if(data.code != '0'){
                return;
            }
            var map = data.data;
            // console.log(map);
            $('.title_l').find('#stockName').html(map.info_arr[1]);//招商银行
            $('.title_l').find('#stockNum').html(selectedCode);
            //渲染页面价格
             var nowPrice=(map.info_arr[3]-0).toFixed(2);
            $("#nowPrice").html(nowPrice);
            $("#num1").html(map.info_arr[31]);
            $("#num2").html(map.info_arr[32] + "%");

            // 如果不在交易时间，不能点买
            if(!buy_moblie.isTradingTime() ){
                $('#buy_step1').attr('tapEvent',false).css({'background':'#767679'}).html('点买时间9:30-11:30, 13:00-14:58');
            }
            //改变价格颜色
            if(map.info_arr[31] > 0){
                $(".color").removeClass('color_red').removeClass('color_green').addClass("color_red");
            }else if(map.info_arr[31] < 0){
                $(".color").removeClass('color_red').removeClass('color_green').addClass("color_green");
            }
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

            /*停牌判断*/
            // console.log(map.info_arr[5]);
            if(Number(map.info_arr[5]).toFixed(2)=='0'){
                $('#buy_step1').html(map.info_arr[5]).attr('tapEvent',false).css({'background':'#767679'});
                $('#buy_step1').attr('tapEvent',false).css({'background':'#767679'}).html('此股票不能购买！');
                updateMoneyRate();
                return;
            }
                // console.log(map.info_arr[48]);
            //不得购买首日上市新股（或复牌首日股票）等当日不设涨跌停板限制的股票；低价股不能买
            if(Number(map.info_arr[48]).toFixed(2)==null ){
                $('#buy_step1').html(map.info_arr[48]).attr('tapEvent',false).css({'background':'#767679'});
                $('#buy_step1').attr('tapEvent',false).css({'background':'#767679'}).html('此股票没有设涨跌限制，不能购买！');
                updateMoneyRate();
                return;
            }
            if(Number(map.info_arr[47]).toFixed(2)==null){
                $('#buy_step1').html(map.info_arr[47]).attr('tapEvent',false).css({'background':'#767679'});
                $('#buy_step1').attr('tapEvent',false).css({'background':'#767679'}).html('此股票没有设涨跌限制，不能购买！');
                updateMoneyRate();
                return;
            }
//
            //更新资金利用率数据
            updateMoneyRate();

        }, 'json')
    },


    /**
     * 判断当前时间是否在9:30-11:30, 13:00-15:00（交易时间）
     */
    isTradingTime:function (){
        var date = new Date();
        // alert(date);
        //判断是不是周末
        var dt=date.getDay();
        // console.log(dt);
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
        if( (curTime >= "09:30:00" && curTime <= "11:30:00") || (curTime >= "13:00:00" && curTime <= "15:00:00" )){
            return true;
        }
        return false;
    }


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
                        smooth: false,
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
                        symbolSize: 1,
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
//              console.log(speed, b);
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

};


//失去焦点时控制范围

$("#buy_number").blur(function(){
    var nub=$("#buy_number").val();
});

//按键抬起时判断值来触发按钮
$("#buy_number").off('keyup').on('keyup',function(){
    if($('#buy_number').val()>=0.06){
        var price = $('#buy_number').val();
    }

    var ajax_html = $(".ajax_html").val();
    if($("#buy_number").val()==''||$('#buy_number').val()=='0'){price=1}
    if(price>200){$("#buy_number").val('200');price=200;}

    //金额高亮
    $.each($("#buy_price_ul > li"),function(i,e){

        if(parseInt($(e).find('a').html())==price){
            $(e).find('a').addClass("chose-active").end().siblings("li").find('a').removeClass("chose-active");
            return false;
        }
        $('#buy_price_ul > li a').removeClass("chose-active");
    });

    //警戒线
    $("#check-surplus_ul>a").html(-(price/3 *10000*lossLine+price*10000).toFixed(2));

    //TODO:
    //获取天数的值
    //公式：倍数杠杆/100*配资得的金额*10000/倍数*天数
    var day_number=$(".select_day option:selected").val();
    $("#publicFee>span").html((price*day_number*levers_3*10000/100 ).toFixed(2) );

    // $("#publicFee>span").html((price * delayFee  *levers_3*price*10000/100 ).toFixed(2) );

    //递延费
    //$("#delay_fee").html(price * delayFee);

    //触发止损默认第一个高亮
    $("#stop-loss_ul > a:eq(0)").trigger('tap');

    //更新资金利用率数据
    updateMoneyRate();

});




//
// $(function () {
//     if($("#buy_number").val()!=""){
//         var price1 = $('#buy_number').val();
//         $("#check-surplus_ul>a").html(price1 * 10000 *lossLine);
//         var nowPrice1 = parseFloat( $("#nowPrice").html() );
//         var gu1=Math.floor((price1*10000/nowPrice1)/100*100);
//         var lyl1=(nowPrice1*gu1/(price1*10000)*100).toFixed(2) + "%";
//         $("#gu").html(gu1); //可买入多少股
//         $("#lyl").html(lyl1);//资金利用率
//         //TODO:页面加载时金额默认点击第一个止损a
//         var day_loss = $("#buy >p").eq(0).find("i").html()-1 ;//按天
//         var month_loss = $("#yuebeishu >p").eq(0).find("i").html()-1 ;//按月
//
//         // $("#stop-loss_ul > a").eq(abc).addClass("chose-active").siblings("a").removeClass("chose-active");
//         if(day_loss != null && day_loss!=-1){
//             // $("#publicFee>span").html((price1 * 18).toFixed(2) ); //按天
//             //TODO:2
//              $("#publicFee>span").html((price1 * delayFee).toFixed(2) ); //按天
//
//         }
//         if(month_loss != null && month_loss!=-1){
//          //TODO:3
//              $("#publicFee>span").html((price1 * delayFee * 30 *0.7 ).toFixed(2) ); //按天
//         }
//         if(month_loss == -1 && day_loss == -1){
//             //TODO:4
//             $("#publicFee>span").html((price1 *delayFee  ).toFixed(2) ); //按天
//         }
//
//     }
// });





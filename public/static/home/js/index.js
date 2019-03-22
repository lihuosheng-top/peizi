//index_login
var index_login={
/**
 * 初始化
 */
	init:function(){
		this.eventsBind();
	},
/**
 * 事件绑定
 */
	eventsBind:function(){
		var base=this;
		//点击登录按钮
		$('.btn_login').on('click',function(){
			base.indexLogin()
		});
		//回车键
		$('.login_main').on('keyup',function(e){
			if(e.keyCode=='13'){
				base.indexLogin()
			}
		});
		
		//判断有无数据
        if($('#asset-details-tbl thead>tr').length==1){
        	$('section.moneydetails').append('<div class="data-empty"><div class="data-empty">暂无数据</div></div>')
        }
		
		//切换资金
		$("#money-flow > li > a").click(function () {
                $(this).addClass("active").parent().siblings().find("a").removeClass("active");
        });
        
        //切换时间
        $("#search-times").change(function () {
                var times = $(this).val();
        });
        
        
           
        
	},
/**
 * 首页登录
 */
	indexLogin:function(){
		var base = this;
		$('#err1').hide();
		$('#err2').hide();
		if($('#username').val().length==0){
			$('#err1').show();
			return;
		}
		
		if($('#password').val().length==0){
			$('#err2').show();
			return;
		}
		$.ajax({
			url:'./doLogin',
			data:{
				nick_name: $("#username").val(),
	        	login_pwd: $("#password").val()
			},
			type:'post',
			dataType:'json',
			success:function(data){
				console.log(data);
				if(data==null){return};
				if (data.code != '0') {
                    // tool.popup_err_msg(data.msg);
					alert(data.msg);
					window.location.href ='./index';
                    return;
                }else {
                    // tool.popup_err_msg("登录成功");
                    alert(data.msg);
                    // window.location.href = "./index";
                }
			}
		})
	},


}
//初始化
$(function(){index_login.init();})


function hq_code(s) {
    var zqcode = "sh000001";
    if (s == '0') $("#a0001_img").attr('src', 'http://image.sinajs.cn/newchart/min/n/' + zqcode + '.gif');
    if (s == '1') $("#a0001_img").attr('src', 'http://image.sinajs.cn/newchart/daily/n/' + zqcode + '.gif');
    if (s == '2') $("#a0001_img").attr('src', 'http://image.sinajs.cn/newchart/weekly/n/' + zqcode + '.gif');
    if (s == '3') $("#a0001_img").attr('src', 'http://image.sinajs.cn/newchart/monthly/n/' + zqcode + '.gif');
}

function hq_code1(s) {
    var zqcode = "sz399001";
    if (s == '0') $("#s0001_img").attr('src', 'http://image.sinajs.cn/newchart/min/n/' + zqcode + '.gif');
    if (s == '1') $("#s0001_img").attr('src', 'http://image.sinajs.cn/newchart/daily/n/' + zqcode + '.gif');
    if (s == '2') $("#s0001_img").attr('src', 'http://image.sinajs.cn/newchart/weekly/n/' + zqcode + '.gif');
    if (s == '3') $("#s0001_img").attr('src', 'http://image.sinajs.cn/newchart/monthly/n/' + zqcode + '.gif');
}
$("#a0001_bnt a").click(function() {
    hq_code($(this).attr('sv'));
    $("#a0001_bnt a").removeClass('cur');
    $(this).addClass('cur');
})
$("#s0001_bnt a").click(function() {
    hq_code1($(this).attr('sv'));
    $("#s0001_bnt a").removeClass('cur');
    $(this).addClass('cur');
})

<?php

namespace app\index\controller;

use app\cms\admin\Page;
use think\Model;
use think\Db;
use app\index\controller\Alistock;
use think\Request;
use app\common\controller\Common;
use app\index\controller\Ucenter;
use think\Session;
use think\Config;
use think\cache;
use think\captcha\Captcha;

class Index extends Home
{
    /**
     **************李火生*******************
     * @param Request $request
     * @return \think\response\View
     * 手机端首页
     **************************************
     */
    public function index(Request $request)
    {

        $sql = "select a.stockCode, c.`name` as stockName,  a.createTime, b.username,b.mobile
			from xh_stock_order as a, xh_member as b, xh_shares as c
			 WHERE isFreetrial=0 and a.memberId = b.id and a.stockCode=c.`code`
			order by a.id desc limit 7 ";
        $buyList = Db::query($sql);
        foreach ($buyList as $k => $v) {
            $tmStr = "一年以前";
            $tm = time() - strtotime($v['createTime']);//秒数
            if ($tm < 60) {
                $tmStr = "{$tm}秒前";
            } else if ($tm < 3600) {
                $tmStr = ((int)($tm / 60)) . "分钟前";
            } else if ($tm < 3600 * 24) {
                $tmStr = ((int)($tm / 3600)) . "小时前";
            } else {
                $tmStr = ((int)($tm / (3600 * 24))) . "天前";
            }
            $buyList[$k]['time'] = $tmStr;
            //用户名加**
            $mobile = $v['mobile'];
            $mobile = substr($mobile, 0, 3) . "****" . substr($mobile, strlen($mobile) - 4, 4);
            $buyList[$k]['mobile'] = $mobile;
        }
        $member = $_SESSION['member'];
        $id = $member['id'];
        $_SESSION['member_id'] = $id;
        if (!empty($id)) {
            //累计
            $count = Db::table("xh_stock_order")->where("isFreetrial=0")->count();
            //累计盈利
//            $earnSum = Db::table("xh_stock_order")->where("isFreetrial=0 and status=2 and sellPrice - dealPrice > 0")
//                ->sum("(sellPrice - dealPrice) * dealQuantity * 100");
            //总盈亏
//                 $earnSum =$_SESSION['profitSum'];
//               $earnSum = number_format($earnSum, 2, '.', '');
            //证券市值（所有已买履约保证金总和）
            $guaranteeFee = Db::name('stock_order')->field("sum(guaranteeFee)")->where('memberId', $id)->where('status', 1)->where('isFreetrial',0)->select();
            $guaranteeFee = $guaranteeFee[0]['sum(guaranteeFee)'];
            $guaranteeFee = number_format($guaranteeFee, 2, '.', '');
            /*******动态资产，可用余额，冻结资金***********************/
            //TODO：持仓盈利(开始)
            $this->real_buy(0);
            //TODO：持仓盈利(结束)
            /*******实盘可买，证卷市值，持仓盈亏***********************/
            //TODO:实盘可买(开始)
            $real_disk = Db::name('stock_order')->field("sum(dealAmount)")->where('memberId',$id)->where('status',1)->where('isFreetrial',0)->select();
            $_SESSION['real_disk'] =$real_disk;
            if(!empty($real_disk)){
                $real_disk =$real_disk[0]['sum(dealAmount)'];
                $real_disk = $real_disk *10000;
                $real_disk = number_format($real_disk,2,'.','');
                $profitSu =$_SESSION['profiSu']; //持仓盈利
                $real_disk_sum =number_format($real_disk + $profitSu,2,'.',''); //实盘可买
            }

            //TODO:实盘可买(结束)
            //TODO:余额
            $balance = Db::table("xh_member")->field('usableSum')->where('id', $id)->find();
           /*证卷市值(以第一个市值为主，没有买的话就为0)*/
            $market_value_code = Db::table("xh_stock_order")->field('stockCode')->where('memberId', $id)->where('status', 1)->select();
            //可用资金
            $peizi_price = $_SESSION["ajax_html"];
            if (!empty($peizi_price)) {
                $peizi = $peizi_price;
            }
            if (empty($peizi_price)) {
                $peizi = 0;
            }
            $this->assign('peizi', $peizi);
            $ablePrice = (float)$balance['usableSum'] + (float)$guaranteeFee; //可用金额
            if (!trim($market_value_code)) {
                $market_value_co = $market_value_code[0]['stockCode'];
                $res = $this->getMarketValueBycode($market_value_co);
                $this->assign('res', $res);
            }
            if (trim($market_value_code)) {
                $res = $this->getMarketValueBycode(600036);
                $this->assign('res', $res);
            }

        }
        /*分配数据*/
        if (is_mobile_request()) {
            //获取 上证指数 深证成指 创业板指
            $res_str = (new Alistock())->stockIndex();
            $res = json_decode($res_str);
            if ($res->showapi_res_code == '0' && $res->showapi_res_body->ret_code == '0') {
                $indexList = $res->showapi_res_body->indexList;
                $this->assign("dp", $indexList);
            }
            $this->assign('balance', $balance);//动态金额 =余额
            $this->assign('ablePrice', $ablePrice);  //可用资金 =动态自己 - A
            //冻结资金 =已付款的且为买入股票所触发（履约保证金总和）
            $this->assign('real_disk_sum',$real_disk_sum); //实盘可买 =配资资金+持仓盈亏
            $this->assign('guaranteeFee', $guaranteeFee); //证券市值（所购买股票的所有履约金之和）
//            $this->assign('earnSum', $earnSum);//总盈亏
            $this->assign("buyList", $buyList);
            return view('index/mobile/index');
        }else{
            $this->zixun();
            $this->emergency_notices();
            $this->emergency();
            return view('index');
        }


    }



    /**
     **************李火生*******************
     * @param $isFreetrial
     * 手机首页持仓总盈亏和实盘可买
     **************************************
     */
    public function real_buy($isFreetrial)
    {
        $member = $_SESSION['member'];
        $memberId = (int)$member['id'];//强制类型转换
        $all_code = Db::table("xh_stock_order")->field('stockCode')->where('memberId', $memberId)->where('status', 1)->where('isFreetrial', $isFreetrial)->order("createTime desc")->select();
        foreach ($all_code as $k => $v) {
            $code = $v['stockCode'];
            $re[] = (new Common())->getMarketValueBycode($code);
        }
        $lis = Db::field('xh_stock_order.*, xh_shares.name as stockName, xh_shares.market')->table('xh_stock_order, xh_shares')
            ->where(" xh_stock_order.stockCode = xh_shares.`code` and xh_stock_order.memberId=$memberId and isFreetrial=$isFreetrial and status = 1")
            ->order("createTime desc")->select();
        $profitSu = 0;
        $list3 = array();
        foreach ($lis as $i => $v) {
            $nowPric = (float)$re[$i]['info_arr'][3];//获取当钱的价格
            $dealPric = (float)$v['dealPrice'];
            $rate = ($nowPric - $dealPric) / $dealPric;//现在的价钱减去处理的价钱除处理价钱输出12.115
            $rate = round($rate, 4); //盈利或亏损的比率（四舍五进,保留四位小数）
            $profitAmoun = ($nowPric - $dealPric) * ((int)$v['dealQuantity']) * 100; //收益额（数量乘一百）//输出4346
            $profitAmoun = round($profitAmoun, 2);
            $profitSu += $profitAmoun; //收益金额累加
            $delayDays = (new Ucenter())->getDaysCount($v['createTime']) - 2;//递延天数
            if ($delayDays < 0) {
                $delayDays = 0;
            }
            $list3[$i]['nowPrice'] = $nowPric;
            $list3[$i]['rate'] = $rate;
            $list3[$i]['profitAmoun'] = $profitAmoun;
//            $a = $list3[$i]['profitAmoun'];
            $list3[$i]['delayDays'] = $delayDays;
        }
        $profitSu = number_format($profitSu, 2, '.', '');
        $_SESSION['profiSu'] =$profitSu;
        $this->assign("profitSum", $profitSu);
    }

    /**
     **************李火生*******************
     * 深证指数接口数据获取(399001)
     **************************************
     */
    public function  stock_exponential_sz(Request $request){
        if($request->isPost()){
            $url = 'http://cq.ssajax.cn/interact/getTradedata.ashx?pic=qlpic_399001_2_1';
            $apistr = file_get_contents($url);
            $str = iconv("gb2312", "utf-8", $apistr);
            $long_str = strlen($str)-1;
            $information = substr($str, 31,$long_str);//截取后的数据，字符串
            $information = substr($information, 0,strlen($information)-1);//截取后的数据，字符串
            return $this->ajax_success("返回数据",$information);
        }
    }

    /**
     **************李火生*******************
     * 上证指数接口的数据获取
     **************************************
     */
    public function  stock_exponential_sh(Request $request){
        if($request->isPost()){
            $url = 'http://cq.ssajax.cn/interact/getTradedata.ashx?pic=qlpic_000001_1_1';
            $apistr = file_get_contents($url);
            $str = iconv("gb2312", "utf-8", $apistr);
            $long_str = strlen($str)-1;
            $information = substr($str, 31,$long_str);//截取后的数据，字符串
            $information = substr($information, 0,strlen($information)-1);//截取后的数据，字符串
            return $this->ajax_success("返回数据",$information);
        }
    }

    /**
     * @param Request $request
     * lihuosheng
     * TODO:pC端上证指数接口
     */
    public function stock_information_index_sh(Request $request){
        if($request->isPost()){
            $code_info = input('code'); //由于是int类型，需要转化为string类型
            $code_info = (string)$code_info;
            $cod = strval($code_info);
            $res = $this->getMarketValueBycode_stock($cod);
            $info_arr = $res['info_arr']; //指数信息
            $info_all=[
                'info_arr'=>$info_arr,
                'time_url_info'=>$res['time_url_info'],//时分线
                'day_url_info'=>$res['day_url_info'],//日K
                'week_url_info'=>$res['week_url_info'],//周k
                'month_url_info'=>$res['month_url_info'],//月k
                'stock_num_info_arr'=>$res['stock_num_info_arr'],//指数信息
            ];
            return ajax_success('上证指数返回成功',$info_all);
        }
    }

    /**
     * @param Request $request
     * lihuosheng
     * TODO:PC端深证指数接口
     */
    public function stock_information_index_sz(Request $request){
        if($request->isPost()){
            $code_info = input('code'); //由于是int类型，需要转化为string类型
            $code_info = (string)$code_info;
            $cod = strval($code_info);
            $res = $this->getMarketValueBycode_stock($cod);
            $info_arr = $res['info_arr']; //指数信息
            $info_all=[
                'info_arr'=>$info_arr,
                'time_url_info'=>$res['time_url_info'],//时分线
                'day_url_info'=>$res['day_url_info'],//日K
                'week_url_info'=>$res['week_url_info'],//周k
                'month_url_info'=>$res['month_url_info'],//月k
                'stock_num_info_arr'=>$res['stock_num_info_arr'],//指数信息
            ];
            return ajax_success('深证指数返回成功',$info_all);
        }
    }



    /**
     **************李火生*******************
     * @param Request $request
     * @return \think\response\View
     * 按日A股点买
     **************************************
     */
    public function buy(Request $request)
    {
        if (!$_SESSION['member']) {
            redirect('./login');
        }
        if ($request->param()) {
            $member = $_SESSION['member'];
            if (isset($member)) {
                $curDate = date("Y-m-d");
                $count = Db::table("xh_stock_order")->where("memberId={$member['id']} and isFreetrial=0 and left(createTime,10)='$curDate'")->count();
                $this->assign('left', 10 - (int)$count);
            }
            //TODO:腾讯接口数据
            //string(484) "大秦铁路~601006~8.17~8.22~8.25~158767~67621~91142~8.17~470~8.16~560~8.15~63~8.14~84~8.13~39~8.18~712~8.19~1036~8.20~705~8.21~802~8.22~978~11:29:53/8.17/213/S/174031/3753|11:29:50/8.18/151/B/123368/3751|11:29:44/8.18/76/B/62168/3748|11:29:41/8.18/520/B/425360/3747|11:29:35/8.17/1/S/817/3744|11:29:32/8.17/235/S/192080/3743~20180706113451~-0.05~-0.61~8.32~8.08~8.18/157428/128963037~158767~13006~0.11~8.50~~8.32~8.08~2.92~1214.62~1214.62~1.17~9.04~7.40~0.88~-3017~8.19~7.41~9.10"; "
            //http://qt.gtimg.cn/q=sz000858
            $code_info = input('code'); //由于是int类型，需要转化为string类型
            $code_info = (string)$code_info;
            $cod = strval($code_info);
            $res = $this->getMarketValueBycode($cod);
            $info_arr = $res['info_arr'];
            $all_url_info = $res['time_url_info'];
            $day_url_info = $res['day_url_info'];
            //分配数据
            $this->assign('code', $cod);
            $this->assign('info_arr', $info_arr);
            $this->assign('time_img', $all_url_info);
            $this->assign('day_img', $day_url_info);
            //TODO:腾讯接口数据
            $this->assign('dealPoundage', getSysParamsByKey("dealPoundage")); //交易手续费(买入股票时，每万元收9元)
            $this->assign('delayFee', getSysParamsByKey("delayFee")); //递延费，默认18元每天
            $this->assign('dealFee', getSysParamsByKey("dealFee")); //第一天的交易费
            $this->assign('lossLine', getSysParamsByKey("lossLine")); //亏损警戒线
            $this->assign('delayLineRate', getSysParamsByKey("delayLineRate")); //递延条件是保证金的0.75倍
            $this->assign('stopLossRate', getSysParamsByKey("stopLossRate")); //触发止损是保证金的0.8倍（当亏损额大于触发止损时，马上强制平仓）
            /**
             * 按天杠杆倍数利息
             */
            $this->assign('levers_1',getStockInterestLeverByLevers("1"));
            $this->assign('levers_2',getStockInterestLeverByLevers("2"));
            $this->assign('levers_3',getStockInterestLeverByLevers("3"));
            $this->assign('levers_4',getStockInterestLeverByLevers("4"));
            $this->assign('levers_5',getStockInterestLeverByLevers("5"));
            $this->assign('levers_6',getStockInterestLeverByLevers("6"));
            $this->assign('levers_7',getStockInterestLeverByLevers("7"));
            $this->assign('levers_8',getStockInterestLeverByLevers("8"));
            $this->assign('levers_9',getStockInterestLeverByLevers("9"));
            $this->assign('levers_10',getStockInterestLeverByLevers("10"));
            /**
             * 按月杠杆倍数利息
             */
            $this->assign('levers_month_1',getStockInterestLeverMonthByLevers("1"));
            $this->assign('levers_month_2',getStockInterestLeverMonthByLevers("2"));
            $this->assign('levers_month_3',getStockInterestLeverMonthByLevers("3"));
            $this->assign('levers_month_4',getStockInterestLeverMonthByLevers("4"));
            $this->assign('levers_month_5',getStockInterestLeverMonthByLevers("5"));
            $this->assign('levers_month_6',getStockInterestLeverMonthByLevers("6"));
            $this->assign('levers_month_7',getStockInterestLeverMonthByLevers("7"));
            $this->assign('levers_month_8',getStockInterestLeverMonthByLevers("8"));
            $this->assign('levers_month_9',getStockInterestLeverMonthByLevers("9"));
            $this->assign('levers_month_10',getStockInterestLeverMonthByLevers("10"));

        }
        if (!empty($_POST['code'])) {
            $this->ajax_success(
                array(
                    // "code"=>$code,
                    "info_arr" => $info_arr,
                    "time_img" => $all_url_info,
                    "day_img" => $day_url_info
                )
            );
        }
        //$this->buy_ajax(0);
        //默认一开始界面是这样显示
        $cod = '000001';
        $res = (new Common())->getMarketValueBycode($cod);
        $info_arr = $res['info_arr'];
        $all_url_info = $res['time_url_info'];
        $day_url_info = $res['day_url_info'];

        $this->assign('code', $cod);
        $this->assign('info_arr', $info_arr);
        $this->assign('time_img', $all_url_info);
        $this->assign('day_img', $day_url_info);
        if (is_mobile_request()) {
            return view('index/mobile/buy');
        }else{
            return view('buy');
        }
    }


    /**
     **************李火生*******************
     * @param Request $request
     * @return \think\response\View
     * 按月点买
     **************************************
     */
    public function  month_buy(Request $request){
        if (!$_SESSION['member']) {
            redirect('./login');
        }
        if ($request->param()) {
            $member = $_SESSION['member'];
            if (isset($member)) {
                $curDate = date("Y-m-d");
                $count = Db::table("xh_stock_order")->where("memberId={$member['id']} and isFreetrial=0 and left(createTime,10)='$curDate'")->count();
                $this->assign('left', 10 - (int)$count);
            }
            //TODO:腾讯接口数据
            //string(484) "大秦铁路~601006~8.17~8.22~8.25~158767~67621~91142~8.17~470~8.16~560~8.15~63~8.14~84~8.13~39~8.18~712~8.19~1036~8.20~705~8.21~802~8.22~978~11:29:53/8.17/213/S/174031/3753|11:29:50/8.18/151/B/123368/3751|11:29:44/8.18/76/B/62168/3748|11:29:41/8.18/520/B/425360/3747|11:29:35/8.17/1/S/817/3744|11:29:32/8.17/235/S/192080/3743~20180706113451~-0.05~-0.61~8.32~8.08~8.18/157428/128963037~158767~13006~0.11~8.50~~8.32~8.08~2.92~1214.62~1214.62~1.17~9.04~7.40~0.88~-3017~8.19~7.41~9.10"; "
            //http://qt.gtimg.cn/q=sz000858
            $code_info = input('code'); //由于是int类型，需要转化为string类型
            $code_info = (string)$code_info;
            $cod = strval($code_info);
            $res = $this->getMarketValueBycode($cod);
            $info_arr = $res['info_arr'];
            $all_url_info = $res['time_url_info'];
            $day_url_info = $res['day_url_info'];
            //分配数据
            $this->assign('code', $cod);
            $this->assign('info_arr', $info_arr);
            $this->assign('time_img', $all_url_info);
            $this->assign('day_img', $day_url_info);
            //TODO:腾讯接口数据
            $this->assign('dealPoundage', getSysParamsByKey("dealPoundage")); //交易手续费(买入股票时，每万元收9元)
            $this->assign('delayFee', getSysParamsByKey("delayFee")); //递延费，默认18元每天
            $this->assign('dealFee', getSysParamsByKey("dealFee")); //第一天的交易费
            $this->assign('lossLine', getSysParamsByKey("lossLine")); //亏损警戒线
            $this->assign('delayLineRate', getSysParamsByKey("delayLineRate")); //递延条件是保证金的0.75倍
            $this->assign('stopLossRate', getSysParamsByKey("stopLossRate")); //触发止损是保证金的0.8倍（当亏损额大于触发止损时，马上强制平仓）
            /**
             * 按天杠杆倍数利息
             */
            $this->assign('levers_1',getStockInterestLeverByLevers("1"));
            $this->assign('levers_2',getStockInterestLeverByLevers("2"));
            $this->assign('levers_3',getStockInterestLeverByLevers("3"));
            $this->assign('levers_4',getStockInterestLeverByLevers("4"));
            $this->assign('levers_5',getStockInterestLeverByLevers("5"));
            $this->assign('levers_6',getStockInterestLeverByLevers("6"));
            $this->assign('levers_7',getStockInterestLeverByLevers("7"));
            $this->assign('levers_8',getStockInterestLeverByLevers("8"));
            $this->assign('levers_9',getStockInterestLeverByLevers("9"));
            $this->assign('levers_10',getStockInterestLeverByLevers("10"));
            /**
             * 按月杠杆倍数利息
             */
            $this->assign('levers_month_1',getStockInterestLeverMonthByLevers("1"));
            $this->assign('levers_month_2',getStockInterestLeverMonthByLevers("2"));
            $this->assign('levers_month_3',getStockInterestLeverMonthByLevers("3"));
            $this->assign('levers_month_4',getStockInterestLeverMonthByLevers("4"));
            $this->assign('levers_month_5',getStockInterestLeverMonthByLevers("5"));
            $this->assign('levers_month_6',getStockInterestLeverMonthByLevers("6"));
            $this->assign('levers_month_7',getStockInterestLeverMonthByLevers("7"));
            $this->assign('levers_month_8',getStockInterestLeverMonthByLevers("8"));
            $this->assign('levers_month_9',getStockInterestLeverMonthByLevers("9"));
            $this->assign('levers_month_10',getStockInterestLeverMonthByLevers("10"));

        }
        if (!empty($_POST['code'])) {
            $this->ajax_success(
                array(
                    // "code"=>$code,
                    "info_arr" => $info_arr,
                    "time_img" => $all_url_info,
                    "day_img" => $day_url_info
                )
            );
        }
        //默认一开始界面是这样显示
        $cod = '000001';
        $res = (new Common())->getMarketValueBycode($cod);
        $info_arr = $res['info_arr'];
        $all_url_info = $res['time_url_info'];
        $day_url_info = $res['day_url_info'];
        $this->assign('code', $cod);
        $this->assign('info_arr', $info_arr);
        $this->assign('time_img', $all_url_info);
        $this->assign('day_img', $day_url_info);

        if (is_mobile_request()) {
            return view('index/mobile/month_buy');
        }else{
            return view('month_buy');
        }

    }




    /**
     **************李火生*******************
     * @param $isFreetrial 0为A股买入，1为免费体验
     * ajax买入a股
     **************************************
     */
//    public function buy_ajax($isFreetrial)
//    {
//        if (!empty($_POST['nowPrice'])) {
//            $member = $_SESSION['member'];
//            $memberId = $member['id'];
//            $data = [
//                'memberId' => $memberId,
//                'stockCode' => $_POST['stockCode'],
//                'dealPrice' => $_POST['nowPrice'],
//                'dealAmount' => $_POST['dealAmount'],
//                'dealQuantity' => $_POST['dealQuantity'],
//                'surplus' => $_POST['surplus'],
//                'loss' => $_POST['loss'],
//                'publicFee' => $_POST['publicFee'],
//                'guaranteeFee' => $_POST['guaranteeFee'],
//                'delayLine' => $_POST['delayLine'],
//                'delayFee' => $_POST['delayFee'],
//                'createTime' => $_POST['createTime'],
//                'status' => $_POST['status'],
//                'isFreetrial' => $isFreetrial
//            ];
//
//            Db::name('stock_order')->data($data)->insert();
//            $this->ajax_success("发送成功", array("member" => $member, "memberId" => $memberId, "data" => $data));
//        }
//    }




    //买入委托
    public function buy_entrust()
    {
        return view('index/mobile/buy_entrust');
    }

    //模拟

    /**
     **************李火生*******************
     * @param Request $request
     * @param $code
     * @return \think\response\View
     * 免费体验
     **************************************
     */
    public function freetrial(Request $request)
    {
        if ($request->param()) {
            $member = $_SESSION['member'];
            if (isset($member)) {
                $curDate = date("Y-m-d");
                $count = Db::table("xh_stock_order")->where("memberId={$member['id']} and isFreetrial=1 and left(createTime,10)='$curDate'")->count();
                $this->assign('left', 10 - (int)$count);
            }
            //TODO:腾讯接口数据
            //string(484) "大秦铁路~601006~8.17~8.22~8.25~158767~67621~91142~8.17~470~8.16~560~8.15~63~8.14~84~8.13~39~8.18~712~8.19~1036~8.20~705~8.21~802~8.22~978~11:29:53/8.17/213/S/174031/3753|11:29:50/8.18/151/B/123368/3751|11:29:44/8.18/76/B/62168/3748|11:29:41/8.18/520/B/425360/3747|11:29:35/8.17/1/S/817/3744|11:29:32/8.17/235/S/192080/3743~20180706113451~-0.05~-0.61~8.32~8.08~8.18/157428/128963037~158767~13006~0.11~8.50~~8.32~8.08~2.92~1214.62~1214.62~1.17~9.04~7.40~0.88~-3017~8.19~7.41~9.10"; "
            //http://qt.gtimg.cn/q=sz000858
            $code_info = input('code'); //由于是int类型，需要转化为string类型
            $code_info = (string)$code_info;
            $cod = strval($code_info);
            $res = $this->getMarketValueBycode($cod);
            $info_arr = $res['info_arr'];
            $all_url_info = $res['time_url_info'];
            $day_url_info = $res['day_url_info'];
            //分配数据
            $this->assign('code', $cod);
            $this->assign('info_arr', $info_arr);
            $this->assign('time_img', $all_url_info);
            $this->assign('day_img', $day_url_info);

            $this->assign('dealPoundage', getSysParamsByKey("dealPoundage")); //交易手续费(买入股票时，每万元收9元)
            $this->assign('delayFee', getSysParamsByKey("delayFee")); //递延费，默认18元每天
            $this->assign('dealFee', getSysParamsByKey("dealFee")); //第一天的交易费
            $this->assign('lossLine', getSysParamsByKey("lossLine")); //亏损警戒线
            $this->assign('delayLineRate', getSysParamsByKey("delayLineRate")); //递延条件是保证金的0.75倍
            $this->assign('stopLossRate', getSysParamsByKey("stopLossRate")); //触发止损是保证金的0.8倍（当亏损额大于触发止损时，马上强制平仓）
            /**
             * 按天杠杆倍数利息
             */
            $this->assign('levers_1',getStockInterestLeverByLevers("1"));
            $this->assign('levers_2',getStockInterestLeverByLevers("2"));
            $this->assign('levers_3',getStockInterestLeverByLevers("3"));
            $this->assign('levers_4',getStockInterestLeverByLevers("4"));
            $this->assign('levers_5',getStockInterestLeverByLevers("5"));
            $this->assign('levers_6',getStockInterestLeverByLevers("6"));
            $this->assign('levers_7',getStockInterestLeverByLevers("7"));
            $this->assign('levers_8',getStockInterestLeverByLevers("8"));
            $this->assign('levers_9',getStockInterestLeverByLevers("9"));
            $this->assign('levers_10',getStockInterestLeverByLevers("10"));
            /**
             * 按月杠杆倍数利息
             */
            $this->assign('levers_month_1',getStockInterestLeverMonthByLevers("1"));
            $this->assign('levers_month_2',getStockInterestLeverMonthByLevers("2"));
            $this->assign('levers_month_3',getStockInterestLeverMonthByLevers("3"));
            $this->assign('levers_month_4',getStockInterestLeverMonthByLevers("4"));
            $this->assign('levers_month_5',getStockInterestLeverMonthByLevers("5"));
            $this->assign('levers_month_6',getStockInterestLeverMonthByLevers("6"));
            $this->assign('levers_month_7',getStockInterestLeverMonthByLevers("7"));
            $this->assign('levers_month_8',getStockInterestLeverMonthByLevers("8"));
            $this->assign('levers_month_9',getStockInterestLeverMonthByLevers("9"));
            $this->assign('levers_month_10',getStockInterestLeverMonthByLevers("10"));

        }
        if (!empty($_POST['code'])) {
            $this->ajax_success(
                array(
                    // "code"=>$code,
                    "info_arr" => $info_arr,
                    "time_img" => $all_url_info,
                    "day_img" => $day_url_info
                )
            );
        }
        //默认一开始界面是这样显示
        $freebleSum = Db::name("member")->field('freebleSum')->where('id',$member['id'])->find();
        $freebleSum =$freebleSum['freebleSum'];
        $cod = '000001';
        $res = (new Common())->getMarketValueBycode($cod);
        $info_arr = $res['info_arr'];
        $all_url_info = $res['time_url_info'];
        $day_url_info = $res['day_url_info'];
        $this->assign('code', $cod);
        $this->assign('info_arr', $info_arr);
        $this->assign('time_img', $all_url_info);
        $this->assign('day_img', $day_url_info);
        $this->assign('freebleSum',$freebleSum);
        if(is_mobile_request()){
            return view('index/mobile/freetrial');
        }else{
            return view('freetrial');
        }

    }

    //协议-1
    public function protocol_1()
    {
        return view('protocol_1');
    }

    //协议2
    public function protocol_2()
    {
        return view('protocol_2');
    }

    //协议-3
    public function protocol_3()
    {
        return view('protocol_3');
    }


    public function freetrial1()
    {
        return view('freetrial1');
    }

    //手机版
    public function safeensure()
    {
        return view('safeensure');
    }

    //帮助中心-常见问题
    public function help()
    {
        return view('help');
    }

    //帮助中心-新手教学
    public function guild()
    {
        if (is_mobile_request()) {
            return view('index/mobile/guild_moile');
        }
        return view('guild');
    }

    //感恩回馈
    public function gift()
    {
        return view('index/mobile/gift');
    }

    //下载
    public function download()
    {
        return view('index/mobile/download');
    }

    /**
     **************李火生*******************
     * @param $uid
     * @return \think\response\View
     * pc注册和手机注册
     **************************************
     */
    public function reg(Request $request,$uid)
    {
        if (is_mobile_request()) {
            $uid =Request::instance()->param('uid');
            $this->assign('uid',$uid);
            return view('index/mobile/reg');
        }
        return view('reg');
    }

    public function  reg_agreement(){
       return view('index/mobile/reg_agreement');
    }


    /**
     **************李火生*******************
     * @return \think\response\View
     * 邀请码
     **************************************
     */
    public function invite(){
        $member_data = $_SESSION['member'];
        //先判断是否登录，未登录则进不了邀请码页面
        if(empty($member_data)){
            return view('index/mobile/login');
        }
        $domain_name = 'http://sm00009.sm00009.com';//域名
//        $domain_name = 'localhost/feichangcelue';//域名
        $project_name = ''; //项目名字
        $member_id = $member_data['id'];   //所登录的id
        $reg = 'reg';  //注册地址
		$share_url = $domain_name."/".$reg."/".$member_id;
		/*二维码*/
		$share_code ='http://b.bshare.cn/barCode?site=weixin&url='.$share_url;
        /*推广详情*/
        /*推广人数，注册人数*/
        $selete_invite_num =Db::table('xh_member')->where('inviterId',$member_id)->count();
        if(!empty($selete_invite_num)){
            /*统计*/
            $data_statistics =[
                'selete_invite_num'=>$selete_invite_num,
                'make_money'=>$selete_invite_num *10,
            ];
        }
        $select_all_data =Db::table('xh_member')->field('username,mobile,createTime')->where('inviterId',$member_id)->select();
        $this->assign('data_statistics',$data_statistics);
        $this->assign('select_all_data',$select_all_data);
        $this->assign('share_code',$share_code);
        $this->assign('share_url',$share_url);
        return view('index/mobile/invite');
    }
	
	/*
	qq添加
	*/
	 public function qq_add(){
        return view('index/mobile/qqsay');
    }

    //登录
    public function login(Request $request)
    {
        if (is_mobile_request()) {
            return view('index/mobile/login');
        }
        return view('login');
    }

    /**
     **************李火生*******************
     * logout把重定向改为success方法跳转
     **************************************
     */
    public function logout()
    {
        unset($_SESSION['member']);
        $this->success("退出成功", url("/"));
    }

    public function doLogin()
    {
        $nick_name = trim($_POST['nick_name']);
        $login_pwd = trim($_POST['login_pwd']);
        /*if(!isset($nick_name) || $nick_name == '' || !isset($login_pwd) || $login_pwd == ''){
            error("参数填写不正确");
        }*/
        $login_pwd = md5($login_pwd);
        $member = Db::table("xh_member")->where("username='$nick_name' or mobile = '$nick_name'")->find();
        if (!$member || $member['password'] != $login_pwd) {
            $this->error("用户名或密码不正确", url("index/index/login"));
        }
        if ( $member['statuss'] != 1) {
            $this->error("用户名已被锁定", url("index/index/login"));
        }
        $_SESSION['member'] = $member;
        $redirect_url = $_SESSION['redirect_url'];
        if (!isset($redirect_url) || $redirect_url == '') {
            $redirect_url = "/";
        }
        $data = array();
        $data['redirect_url'] = $redirect_url;
        $data['usableSum'] = $member['usableSum'];
        $data['freebleSum'] =$member['freebleSum'];
        $data['username'] = $member['username'];

        unset($member['password']);
        unset($member['usableSum']);
        unset($member['freebleSum']);
//        $this->success("登录成功", url("/"));
        $this->success("登录成功", url("/"));


    }

    /**
     **************李火生*******************
     * 注册操作
     **************************************
     */
    public function doReg()
    {
        $nick_name = trim($_POST['nick_name']);
        $login_pwd = trim($_POST['login_pwd']);
        $mobile = trim($_POST['mobile']);
        $code = trim($_POST['code']);
        $inviterId =trim($_POST['inviterId']);
        //$recommendCode = trim($_POST['recommendCode']);
        /*if( !isset($nick_name) || !isset($login_pwd) || !isset($mobile) || !isset($code) || !isset($recommendCode) ) {
            error("参数填写不正确");
        }*/

        $freebleSun =getSysParamsByKey("freebleSun"); //免费体验的金额
        $usableSum =getSysParamsByKey("usableSum"); //注册成功奖励的金钱
        $invitingAwards =getSysParamsByKey("invitingAwards"); //注册成功奖励的金钱

        if (strlen($nick_name) < 6) {
            $this->error("用户名应不少于6个字符");
        }
        if ($mobile != $_SESSION['mobile'] || $code != $_SESSION['mobileCode']) {
            $this->error("验证码不正确");
        }
        if (Db::table("xh_member")->where("username", $nick_name)->find()) {
            $this->error("用户名已存在");
        }
        if (Db::table("xh_member")->where("mobile", $mobile)->find()) {
            $this->error("手机号已存在");
        }
        $data = array();
        $data['username'] = $nick_name;
        $data['mobile'] = $mobile;
        $data['password'] = md5($login_pwd);
        $data['createTime'] = date("Y-m-d H:i:s");
        $data['freebleSum'] = $freebleSun;  //体验金（不能提现）
        $data['usableSum']  = $usableSum ; //注册成功奖励的

        if(!empty($inviterId)){
            $data['inviterId'] =$inviterId;
        }
        if(empty($inviterId)){
            $data['inviterId'] =null;
        }

        $id = Db::table("xh_member")->insertGetId($data);

        if ($id > 0) {
            /*要求成功之后得对应的奖励10元进入账户（注册成功,被邀请人和邀请人都奖励10元）*/
            if(!empty($inviterId)){
                $create_time = date("Y-m-d H:i:s");
                $create_content ="成功邀请一人注册账户奖励".$invitingAwards."元";
                /*邀请人*/
                $active_inviter_data =[
                    'memberId'=>$inviterId,
                    'flow'=>1, //收入
                    'amount'=>$invitingAwards, //10元钱
                    'remarks'=>$create_content,
                    'createTime'=>$create_time
                ];
                $reward_one =Db::table('xh_member_fundrecord')->insertGetId($active_inviter_data);
                $active_inviter_data_usableSum =Db::table('xh_member')->field('usableSum')->where('id',$inviterId)->find();
               if(!empty($active_inviter_data_usableSum)){
                   Db::table('xh_member')->where('id',$inviterId)->update(['usableSum'=>$active_inviter_data_usableSum['usableSum']+$invitingAwards]);
                   Db::table('xh_member_fundrecord')->where('id',$reward_one)->update(['usableSum'=>$active_inviter_data_usableSum['usableSum']+$invitingAwards]);
               }
               $inv_content ="成功被邀请加入获得奖励".$invitingAwards."元,注册成功奖励".$usableSum."元";
               $all_invitingAwards =$invitingAwards+$usableSum;
                /*被邀请人（新注册）*/
                $invited_data =[
                    'memberId'=>$id,
                    'flow'=>1, //收入
                    'amount'=>$all_invitingAwards,
                    'remarks'=>$inv_content,
                    'createTime'=>$create_time
                ];
                $reward_tow =Db::table('xh_member_fundrecord')->insertGetId($invited_data);
               //对余额进行修改(先查在改)
                $invite_data_usableSum =Db::table('xh_member')->field('usableSum')->where('id',$id)->find();
                if(!empty($invite_data_usableSum)){
                    Db::table('xh_member')->where('id',$id)->update(['usableSum'=>$invite_data_usableSum['usableSum']+$invitingAwards]);
                    Db::table('xh_member_fundrecord')->where('id',$reward_tow)->update(['usableSum'=>$invite_data_usableSum['usableSum']+$invitingAwards]);
                    $this->sendMobileToInformation($mobile,$inv_content);
                }
                $this->success("注册成功", url("/"));
            }else{
                $no_content ="注册成功奖励".$usableSum."元，请留意账户";
                $this->sendMobileToInformation($mobile,$no_content);
                $this->success("注册成功", url("/"));
            }

        } else {
            $this->error("注册失败", url("/"));
        }


    }

    //判断是否已登录
    public function isLogin()
    {
        $ret = 0;
        if (isset($_SESSION['member'])) {
            $ret = 1;
        }
        echo $ret;
    }


    //服务协议
    public function reg_agree()
    {
        return view('reg_agree');
    }

    //关于我们
    public function company()
    {
        return view('company');
    }

    //联系我们
    public function contact()
    {
        return view('contact');
    }

    //忘记密码-01账户名
    public function forgot_pass()
    {
        if (is_mobile_request()) {
            return view('index/mobile/forgot_pass');
        }
        return view('forgot_pass');
    }

    //忘记密码-02密码重置
    public function mobile_val()
    {
        return view('mobile_val');
    }

    //忘记密码-03密码找回
    public function pass_reset()
    {
        if ($_SESSION['verifyForgotPass'] != 1) {
            die("请先验证手机号");
        }
        return view('pass_reset');
    }

    //忘记密码- 更新密码
    public function updateNewPwd(Request $request)
    {
        if ($request->isPost()) {
            /*$data = input('post.');
            var_dump($data);
            exit();*/
            $mobile = $_POST['mobile'];
            $login_newPwd = input('login_pwd');
            $yzm = $request->input('yzm');
            if ($login_newPwd == '') {
                $this->error("密码不能为空");
            }
            if ($yzm == '') {
                $this->error("验证码不能为空");
            }
            if ($yzm == $_SESSION['mobileCode']) {
                $this->error('验证码不正确');
            }
            if (strlen($mobile) != 11) {
                $this->error("手机号码不正确，请重新验证");
            }

            $login_newPwd = md5($login_newPwd);
            Db::table("xh_member")->where("mobile='{$mobile}'")->update(array('password' => $login_newPwd));

            unset($_SESSION['mobileForForgot']);
            unset($_SESSION['verifyForgotPass']);

            $this->success("更新成功");
        }
    }

    //忘记密码-04完成
    public function reset_result()
    {
        return view('reset_result');
    }

    /**
     **************李火生*******************
     * @param $code
     * @return array|string|true
     * TODO:验证码验证
     **************************************
     */
    //检查图片验证码是否正确
    	public function checkImageCode($code){
            $data = array('captcha' => $code); new \think\captcha\Captcha();
            $res = $this->validate($data,[
                'captcha|验证码'=>'require|captcha'
            ]);
            //返回true，或者错误信息

            return $res;
        }

    /**
     **************李火生*******************
     * @param Request $request
     * 接收注册时的验证码
     **************************************
     */
    public function sendMobileCode(Request $request)
    {
        //接受验证码的手机号码
        if ($request->isPost()) {
            $mobile = $request->param("mobile");
            if(empty($mobile)){
                $this->error('手机号码不能为空！');
            }
            $db_phone =Db::name('member')->where('mobile',$mobile)->find();

            if(!empty($db_phone)){
                $this->error('此手机号已注册，请前往登录！');
            }

            $mobileCode = rand(100000, 999999);
            $arr = json_decode($mobile, true);
            /*var_dump($arr);
            exit();*/
            $mobiles = strlen($arr);
            if (isset($mobiles) != 11) {
                $this->error("手机号码不正确");
            }

            //存入session中
            if (strlen($mobileCode) > 0) {
                $_SESSION['mobileCode'] = $mobileCode;
                $_SESSION['mobile'] = $mobile;
            }
            $content = "尊敬的用户，您本次验证码为{$mobileCode}，十分钟内有效";
            //$content = urlencode($content);

            $url = "http://120.26.38.54:8000/interface/smssend.aspx";
            $post_data = array("account" => "peizi", "password" => "123qwe", "mobile" => "$mobile", "content" => $content);
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
            $output = curl_exec($ch);
            curl_close($ch);
            if ($output) {
                $this->ajax_success("发送成功", $output);
            } else {
                $this->ajax_success("发送失败");
            }
            //json格式转换
        }
    }

    /**
     **************李火生*******************
     * @param Request $request
     * 接收找回密码的验证码
     **************************************
     */
    public function sendMobileCodeByPassWord(Request $request)
    {
        //接受验证码的手机号码
        if ($request->isPost()) {
            $mobile = $request->param("mobile");
            if(empty($mobile)){
                $this->error('手机号码不能为空！');
            }
            $db_phone =Db::name('member')->where('mobile',$mobile)->find();

            if(empty($db_phone)){
                $this->error('此手机号未注册，请前往注册！');
            }

            $mobileCode = rand(100000, 999999);
            $arr = json_decode($mobile, true);
            /*var_dump($arr);
            exit();*/
            $mobiles = strlen($arr);
            if (isset($mobiles) != 11) {
                $this->error("手机号码不正确");
            }

            //存入session中
            if (strlen($mobileCode) > 0) {
                $_SESSION['mobileCode'] = $mobileCode;
                $_SESSION['mobile'] = $mobile;
            }
            $content = "尊敬的用户，您本次找回密码验证码为{$mobileCode}，十分钟内有效";
            //$content = urlencode($content);

            $url = "http://120.26.38.54:8000/interface/smssend.aspx";
            $post_data = array("account" => "peizi", "password" => "123qwe", "mobile" => "$mobile", "content" => $content);
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
            $output = curl_exec($ch);
            curl_close($ch);
            if ($output) {
                $this->ajax_success("发送成功", $output);
            } else {
                $this->ajax_success("发送失败");
            }
            //json格式转换
        }
    }

    //忘记密码 输入手机号和验证码之后  验证
    //更改手机号码 验证手机验证码是否正确
    public function checkForgotMobileCode()
    {
        $mobile = trim(input('mobile'));
        $code = trim(input('code'));

        if (strlen($mobile) != 11 || substr($mobile, 0, 1) != '1' || $code == '') {
            error("参数不正确");
        }

        if ($_SESSION['mobileCode'] != $code || $_SESSION['mobile'] != $mobile) {
            error("验证码不正确");
        }

        if (!Db::table("xh_member")->where("mobile='{$mobile}'")->find()) {
            error("手机号码不存在");
        }

        unset($_SESSION['mobile']);
        unset($_SESSION['mobileCode']);

        //标记原手机号验证成功
        $_SESSION['verifyForgotPass'] = 1;
        $_SESSION['mobileForForgot'] = $mobile;

        success("验证成功");

    }

    //上传图片
    public function doImgUpload()
    {
        $serverPath = "/public/uploads/" . date("Y/m/d") . "/";
        halt($serverPath);
        $filepath = dirname(__FILE__) . '/../../..' . $serverPath;
        if (!is_dir($filepath)) {
            if (!mkdir($filepath, 0777, true)) {
                error("目录创建失败");
            }
        }
        foreach ($_FILES as $key => $val) {
            $imgname = $val['name'];
            $imgname = preg_replace('/([\x80-\xff]*)/i', '', $imgname); //去掉中文
            $tmp = $val['tmp_name'];
            $filename = get_total_millisecond() . rand(1000, 9999) . $imgname;
            if (move_uploaded_file($tmp, $filepath . $filename)) {
                $serverImgPath = $serverPath . $filename;
                success($serverImgPath);
            } else {
                error("上传失败");
            }
        }
        error("未选择要上传的图片");
    }

    //我要配资
    public function stock()
    {
        $this->assign('dealPoundage', getSysParamsByKey("dealPoundage")); //交易手续费(买入股票时，每万元收9元)
        $this->assign('delayFee', getSysParamsByKey("delayFee")); //递延费，默认18元每天
        $this->assign('dealFee', getSysParamsByKey("dealFee")); //第一天的交易费
        $this->assign('lossLine', getSysParamsByKey("lossLine")); //亏损警戒线
        $this->assign('delayLineRate', getSysParamsByKey("delayLineRate")); //递延条件是保证金的0.75倍
        $this->assign('stopLossRate', getSysParamsByKey("stopLossRate")); //触发止损是保证金的0.8倍（当亏损额大于触发止损时，马上强制平仓）
        /**
         * 按天杠杆倍数利息
         */
        $this->assign('levers_1',getStockInterestLeverByLevers("1"));
        $this->assign('levers_2',getStockInterestLeverByLevers("2"));
        $this->assign('levers_3',getStockInterestLeverByLevers("3"));
        $this->assign('levers_4',getStockInterestLeverByLevers("4"));
        $this->assign('levers_5',getStockInterestLeverByLevers("5"));
        $this->assign('levers_6',getStockInterestLeverByLevers("6"));
        $this->assign('levers_7',getStockInterestLeverByLevers("7"));
        $this->assign('levers_8',getStockInterestLeverByLevers("8"));
        $this->assign('levers_9',getStockInterestLeverByLevers("9"));
        $this->assign('levers_10',getStockInterestLeverByLevers("10"));
        /**
         * 按月杠杆倍数利息
         */
        $this->assign('levers_month_1',getStockInterestLeverMonthByLevers("1"));
        $this->assign('levers_month_2',getStockInterestLeverMonthByLevers("2"));
        $this->assign('levers_month_3',getStockInterestLeverMonthByLevers("3"));
        $this->assign('levers_month_4',getStockInterestLeverMonthByLevers("4"));
        $this->assign('levers_month_5',getStockInterestLeverMonthByLevers("5"));
        $this->assign('levers_month_6',getStockInterestLeverMonthByLevers("6"));
        $this->assign('levers_month_7',getStockInterestLeverMonthByLevers("7"));
        $this->assign('levers_month_8',getStockInterestLeverMonthByLevers("8"));
        $this->assign('levers_month_9',getStockInterestLeverMonthByLevers("9"));
        $this->assign('levers_month_10',getStockInterestLeverMonthByLevers("10"));
        return view("index/mobile/pz");
    }


    /**
     **************李火生*******************
     * @param Request $request
     * 按天配资传值给点买
     **************************************
     */

    public function ajax_html(Request $request)
    {
        if ($request->isPost("post.")) {

            if($_SESSION['yuebeishu']){
                unset($_SESSION['yuebeishu']);
            }
            if($_SESSION['ajax_html']){
                unset($_SESSION['ajax_html']);
            }
            if($_SESSION['day_select']){
                unset($_SESSION['month_select']);
            }
            $Con_Lists = $request->param('Con_Lists');
            $beishu = $request->param('beishu');
            $day_select =$request->param('day_select');
            $DataCon = round(($Con_Lists / 10000), 2);
            if ($Con_Lists != 0) {
//                session(array('Con_Lists'=>$DataCon,'expire'=>600));
                $_SESSION["Con_Lists"] = $DataCon;
            }
            if ($beishu != 0) {
                $_SESSION["beishu"]  = $beishu;
            }
            if($day_select){
                $_SESSION['day_select'] =$day_select;
            }
            $this->ajax_success("获取成功", array("data" => $_SESSION["Con_Lists"], "DataCon" => $Con_Lists,"beishu"=>$beishu,"day_select"=>$day_select));
        }

    }

    /**
     **************李火生*******************
     * @param Request $request
     * 按月配资传值给点买
     **************************************
     */
    public function ajax_yue(Request $request)
    {

        if($_SESSION['beishu']){
            unset($_SESSION['beishu']);
        }
        if($_SESSION['Con_Lists']){
            unset($_SESSION['Con_Lists']);
        }
        if($_SESSION['day_select']){
            unset($_SESSION['day_select']);
        }
        if ($request->isPost("post.")) {
            $ajax_html = $request->param('ajax_html');
            $yuebeishu =$request->param('yuebeishu');
            $month_select =$request->param('month_select');
            $data = round(($ajax_html / 10000), 2);
            if ($ajax_html != 0) {
                $_SESSION["ajax_html"] = $data;
            }
            if($yuebeishu !=0)
            {
                $_SESSION["yuebeishu"] =$yuebeishu;
            }
            if($month_select){
                $_SESSION['month_select'] =$month_select;
            }
            $this->ajax_success("获取成功", array("data" => $_SESSION["ajax_html"],"yuebeishu"=>$yuebeishu,"month_select"=>$_SESSION['month_select']));
        }



    }

    public function ajax_free(Request $request)
    {
        if ($request->isPost("post.")) {

            if($_SESSION['yuebeishu']){
                unset($_SESSION['yuebeishu']);
            }
            if($_SESSION['ajax_html']){
                unset($_SESSION['ajax_html']);
            }
            if($_SESSION['day_select']){
                unset($_SESSION['month_select']);
            }
            $Con_Lists = $request->param('free_ajax_html');
            $beishu = $request->param('freebeishu');
            $day_select =$request->param('free_day_select');
            $DataCon = round(($Con_Lists / 10000), 2);
            if ($Con_Lists != 0) {
//                session(array('Con_Lists'=>$DataCon,'expire'=>600));
                $_SESSION["free_ajax_html"] = $DataCon;
            }
            if ($beishu != 0) {
                $_SESSION["freebeishu"]  = $beishu;
            }
            if($day_select){
                $_SESSION['free_day_select'] =$day_select;
            }
            $this->ajax_success("获取成功", array("data" => $_SESSION["free_ajax_html"], "DataCon" => $Con_Lists,"freebeishu"=>$beishu,"free_day_select"=>$day_select));
        }

    }



    /**
     **************李火生*******************
     * @return \think\response\View
     * 财经资讯
     **************************************
     */
    public function news(){
        $res =Db::name('article')->order('createTime','desc')->paginate(5,false,[
            'type'      =>'index',
            'var_page'  => 'page',
            'list_rows' => 3,
        ]);
        $page = $res->render();
       $pas = Config::get('list_rows');
        return view('index/mobile/news',['res'=>$res,'page'=>$page]);

    }



    /**
     **************李火生*******************
     * @param Request $request
     * 获取点击过来的article_id
     **************************************
     */
    public function new_id(Request $request){
        if($request->isPost()){
            $article_id =$_POST['article_id'];
            if(!empty($article_id)){
                session('article_id',$article_id);
                $this->ajax_success('获取成功',$article_id);
            }

        }
    }
    /**
     **************李火生*******************
     * @param Request $request
     * 财经资讯详情
     * @return \think\response\View
     **************************************
     */
    public function news_t(Request $request){
        if($request->isPost()){
            $article_id = Session::get('article_id');
            if(!empty($article_id)){
                $res = Db::name('article')->where('id',$article_id)->find();
                if($res){
//                    session('article_id',null);
                    $this->ajax_success('获取成功',$res);
                }
            }
        }
        if (is_mobile_request()) {
            return view('index/mobile/news_t');
        }else{
            return view('news_t');
        }
    }


    /**
     **************李火生*******************
     * @param Request $request
     * 获取点击过来的notice_id
     **************************************
     */
    public function notice_id(Request $request){
        if($request->isPost()){
            $article_id =$_POST['article_id'];
            if(!empty($article_id)){
                session('notice_id',$article_id);
                $this->ajax_success('获取成功',$article_id);
            }

        }
    }

    /**
     **************李火生*******************
     * @param Request $request
     * 网站公告详情
     * @return \think\response\View
     **************************************
     */
    public function notice_t(Request $request){
        if($request->isPost()){
            $notice_id = Session::get('notice_id');
            if(!empty($notice_id)){
                $res = Db::name('notice')->where('id',$notice_id)->find();
                if($res){
//                    session('notice_id',null);
                    $this->ajax_success('获取成功',$res);
                }
            }
        }
        if (is_mobile_request()) {
            return view('index/mobile/notice_t');
        }else{
            return view('notice_t');
        }
    }




    /**
     **************李火生*******************
     * @param Request $request
     * 手机端的轮播图
     **************************************
     */
    public function app_broadcast(Request $request){
        if($request->isPost()){
            $res =Db::name('images')->field('src,href')->order('id','desc')->where('type',2)->limit(0,3)->select();
            if(!empty($res)){
                $this->ajax_success('成功',$res);
            }
        }
    }
    /**
     **************李火生*******************
     * @param Request $request
     * pc端轮播图
     **************************************
     */
    public function pc_broadcast(Request $request){
        if($request->isPost()){
            $res = Db::name('images')->field('src,href')->order('id','desc')->where('type',1)->limit(0,3)->select();
            if(!empty($res)){
               $this->ajax_success('成功',$res);
            }
        }
    }

    /**
     **************李火生*******************
     * @return \think\response\View
     * Pc端配资1
     **************************************
     */
    public  function  index_information(){
        /* 一开始进入首页显示的数据5倍的数据*/
        $data_money =getStockInterestLeverByLevers("5");
        $res_money =100;
//        $res_bei =200*5*$data_money/100;
        $res_bei =0;  //免费体验利息为0
        /*日利息*/
        $this->assign('res_bei',$res_bei);
        /*分配到准备资金那里*/
        $this->assign('res_money',$res_money);
        /*亏损警戒线*/
        $con_line =getSysParamsByKey("lossLine");
        $cordon_line = $con_line*100+500;
        /*亏损平仓线*/
        $loss_line =  getSysParamsByKey("stopLossRate")*100+500;
        $this->assign('cordon_line',$cordon_line);
        $this->assign('loss_line',$loss_line);

        /**
         * 按天杠杆倍数利息
         */
        $this->assign('levers_1',getStockInterestLeverByLevers("1"));
        $this->assign('levers_2',getStockInterestLeverByLevers("2"));
        $this->assign('levers_3',getStockInterestLeverByLevers("3"));
        $this->assign('levers_4',getStockInterestLeverByLevers("4"));
        $this->assign('levers_5',getStockInterestLeverByLevers("5"));
        $this->assign('levers_6',getStockInterestLeverByLevers("6"));
        $this->assign('levers_7',getStockInterestLeverByLevers("7"));
        $this->assign('levers_8',getStockInterestLeverByLevers("8"));
        $this->assign('levers_9',getStockInterestLeverByLevers("9"));
        $this->assign('levers_10',getStockInterestLeverByLevers("10"));

        $this->assign('lossLine', getSysParamsByKey("lossLine")); //亏损警戒线
        $this->assign('stopLossRate', getSysParamsByKey("stopLossRate")); //触发止损是保证金的0.8倍（当亏损额大于触发止损时，马上强制平仓）

        return view('index_information');
    }
    /**
     **************李火生*******************
     * @return \think\response\View
     * Pc端配资2
     **************************************
     */
    public  function  index_information_first(){

        /* 一开始进入首页显示的数据3倍的数据*/
        $data_money =getStockInterestLeverMonthByLevers("3");
        $res_money =200+200*3*$data_money/100;
        $res_bei =200*3*$data_money/100;
        /*月利息*/
        $this->assign('res_bei',$res_bei);
        /*分配到准备资金那里*/
        $this->assign('res_money',$res_money);
        /*亏损警戒线*/
        $con_line =getSysParamsByKey("lossLine");
        $cordon_line = $con_line*200+600;
        /*亏损平仓线*/
        $loss_line =  getSysParamsByKey("stopLossRate")*200+600;
        $this->assign('cordon_line',$cordon_line);
        $this->assign('loss_line',$loss_line);


        /**
         * 按月杠杆倍数利息
         */
        $this->assign('levers_month_1',getStockInterestLeverMonthByLevers("1"));
        $this->assign('levers_month_2',getStockInterestLeverMonthByLevers("2"));
        $this->assign('levers_month_3',getStockInterestLeverMonthByLevers("3"));
        $this->assign('levers_month_4',getStockInterestLeverMonthByLevers("4"));
        $this->assign('levers_month_5',getStockInterestLeverMonthByLevers("5"));
        $this->assign('levers_month_6',getStockInterestLeverMonthByLevers("6"));
        $this->assign('levers_month_7',getStockInterestLeverMonthByLevers("7"));
        $this->assign('levers_month_8',getStockInterestLeverMonthByLevers("8"));
        $this->assign('levers_month_9',getStockInterestLeverMonthByLevers("9"));
        $this->assign('levers_month_10',getStockInterestLeverMonthByLevers("10"));

        $this->assign('lossLine', getSysParamsByKey("lossLine")); //亏损警戒线
        $this->assign('stopLossRate', getSysParamsByKey("stopLossRate")); //触发止损是保证金的0.8倍（当亏损额大于触发止损时，马上强制平仓）

        return view('index_information_first');
    }
    /**
     **************李火生*******************
     * @return \think\response\View
     * Pc端配资3
     **************************************
     */
    public  function  index_information_second(){

        /* 一开始进入首页显示的数据3倍的数据*/
        $data_money =getStockInterestLeverByLevers("3");
        $res_money =200+200*3*$data_money/100;
        $res_bei =200*3*$data_money/100;
        /*日利息*/
        $this->assign('res_bei',$res_bei);
        /*分配到准备资金那里*/
        $this->assign('res_money',$res_money);
        /*亏损警戒线*/
        $con_line =getSysParamsByKey("lossLine");
        $cordon_line = $con_line*200+600;
        /*亏损平仓线*/
        $loss_line =  getSysParamsByKey("stopLossRate")*200+600;
        $this->assign('cordon_line',$cordon_line);
        $this->assign('loss_line',$loss_line);


        /**
         * 按天杠杆倍数利息
         */
        $this->assign('levers_1',getStockInterestLeverByLevers("1"));
        $this->assign('levers_2',getStockInterestLeverByLevers("2"));
        $this->assign('levers_3',getStockInterestLeverByLevers("3"));
        $this->assign('levers_4',getStockInterestLeverByLevers("4"));
        $this->assign('levers_5',getStockInterestLeverByLevers("5"));
        $this->assign('levers_6',getStockInterestLeverByLevers("6"));
        $this->assign('levers_7',getStockInterestLeverByLevers("7"));
        $this->assign('levers_8',getStockInterestLeverByLevers("8"));
        $this->assign('levers_9',getStockInterestLeverByLevers("9"));
        $this->assign('levers_10',getStockInterestLeverByLevers("10"));




        $this->assign('lossLine', getSysParamsByKey("lossLine")); //亏损警戒线
        $this->assign('stopLossRate', getSysParamsByKey("stopLossRate")); //触发止损是保证金的0.8倍（当亏损额大于触发止损时，马上强制平仓）
        return view('index_information_second');
    }


    /**
     **************李火生*******************
     * @return \think\response\View
     * pc端配资委托协议
     **************************************
     */
    public function EntrustmentAgreement(){
        return view('entrustment_agreement');
    }

    /**
     **************李火生*******************
     * @return \think\response\View
     * pc端广告页面
     **************************************
     */
    public function  Advertisement(){

        if (is_mobile_request()) {
            return view('index/mobile/advertisement');
        }else{
            return view('advertisement');
        }

    }

    public function  Advertisement_2(){

        if (is_mobile_request()) {
            return view('index/mobile/advertisement_2');
        }else{
            return view('advertisement_2');
        }

    }

    /**
 **************李火生*******************
 * @return \think\response\View
 * pc端A股点买(按天)
 **************************************
 */
    public function PcBuy(){
        return view('pc_buy');
    }
    /**
     **************李火生*******************
     * @return \think\response\View
     * pc端A股点买（按月）
     **************************************
     */
    public function PcMonthBuy(){
        return view('pc_month_buy');
    }
    /**
     **************李火生*******************
     * @return \think\response\View
     * pc端A股（免费体验）点买
     **************************************
     */
    public function PcFreeBuy(){
        return view('pc_free_buy');
    }


    /**
     **************李火生*******************
     * @return \think\response\View
     * pc端A股点卖
     **************************************
     */
    public function PcSell(){
        return view('pc_sell');
    }

    /**
     **************李火生*******************
     * @return \think\response\View
     * pc端A股（免费体验）点卖
     **************************************
     */
    public function PcFreeSell(){
        return view('pc_free_sell');
    }

    /**
     **************李火生*******************
     * @return \think\response\View
     * pc端A股结算
     **************************************
     */
    public function PcHistory(){
        return view('pc_history');
    }

    /**
     **************李火生*******************
     * @return \think\response\View
     * pc端A股（免费体验）结算
     **************************************
     */
    public  function  PcFreeHistory(){
        return view('pc_free_history');
    }

    /**
     * pC端首页最底部连接开始
     */
//    public  function  PcPublicFoot(){
//        return view('PcPublicFoot');
//    }
    public  function  PcBuyDay(){
        return view('pc_buy_day');
    }
    public  function  PcBuyMonth(){
        return view('pc_buy_month');
    }
    public  function  PcBuyFree(){
        return view('pc_buy_free');
    }
    public  function  atlxjj(){
        return view('atlxjj');
    }
    public  function  aylxjj(){
        return view('aylxjj');
    }
    public  function  mxlxjj(){
        return view('mxlxjj');
    }
    public  function  rhzchy(){
        return view('rhzchy');
    }
    public  function  rhsmrz(){
        return view('rhsmrz');
    }
    public  function  rhjxcz(){
        return view('rhjxcz');
    }
    public  function  rhsqpz(){
        return view('rhsqpz');
    }

    /**
     * pC端首页最底部连接结束
     */

    public function  zixun(){
        $news_informations =Db::name('article')->order('createTime','desc')->limit(7)->select();
        $this->assign('news_informations',$news_informations);
        $news_informations_tow =Db::name('notice')->order('createTime','desc')->limit(7)->select();
        $this->assign('news_informations_tow',$news_informations_tow);
    }

    /**
     **************李火生*******************
     * PC端紧急通知返回的数据
     **************************************
     */
    public  function  emergency_notices(){
        $number =Db::name('notice')->count();
        $data =Db::name('notice')->order('createTime','desc')->limit($number,1)->find();
        $this->assign('notice_content',$data);

    }

    /**
     **************李火生*******************
     * 关闭紧急通知按钮
     **************************************
     */
    public function emergency(){
        $content =Db::name('emergency_notice')->limit(1,1)->find();
        $this->assign('btn_emergency',$content);
    }












}

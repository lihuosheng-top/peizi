<?php
namespace app\admin\controller;
//use app\common\controller\Common;
use think\Db;
use app\index\controller\Alistock;
use app\index\controller\Home;
use app\common\controller\Common;

set_time_limit(0);

class Jobs
{
    //批量获取在线股票的实时数据，存到数据库
    public function getStockDataToDb()
    {
        $t1 = time();
        $tm = time() - 10;
        $stockList = Db::table("xh_stock_visit_record")->field("distinct(code)")->where("createTimeStamp < $tm")->select();
        $stocks = "";
        foreach ($stockList as $k => $v) {
            if ($stocks != '') {
                $stocks .= ",";
            }
            $stocks .= $v['code'];
        }
        $stockMap = (new Common())->getMarketVlueByCodes($stocks);

        foreach ($stockMap as $k => $v) {
            foreach ($v as $keys => $values) {
                $data['detail'] = implode(',', $values);
                $data['createTime'] = date("Y-m-d H:i:s");
                $data['createTimeStamp'] = time();
                $data['code'] = $values[2];
                $ret = Db::table("xh_online_stock_detail")->insertGetId($data);
                echo($ret . "<br/>");
            }
        }
        $t2 = time();
        echo "t2-t1=" . ($t2 - $t1);
        //print_r($this->object2array($stockMap));
    }

    private function object2array(&$object)
    {
        if (is_object($object)) {
            $arr = (array)($object);
        } else {
            $arr = &$object;
        }
        if (is_array($arr)) {
            foreach ($arr as $varName => $varValue) {
                $arr[$varName] = $this->object2array_($varValue);
            }
        }
        return $arr;
    }

    function object2array_(&$object)
    {
        $object = json_decode(json_encode($object), true);
        return $object;
    }

    //获取股票实时价格，自动平仓 (每秒运行一次) 配资到期，自动平仓 （工作时间）
    public function pingCang()
    {
        $t1 = time();
        //触发止损所发出短信信息
        $loss_information = "尊敬的客户，你在本平台已达强制抹舱线，平台已自动抹舱《尚牛在线》";

        //平仓警戒线发的短信信息
        $surplus_information = "尊敬的客户，你在本平台所建舱位已达警戒线，敬请注意观看以免造成损失《尚牛在线》";
        $expire_information = "尊敬的客户，你在本平台所建苍已达最长时间，平台已帮您苹苍，敬请注意《尚牛在线》";
        $res = Db::table("xh_stock_order")
//            ->where("status=1 and isFreetrial=0")
            ->where("status=1")
            ->field("distinct(stockCode)")
            ->select();
        $stocks = ""; //上证指数 深证成指 创业板指
        foreach ($res as $k => $v) {
            if ($stocks != "") {
                $stocks .= ",";
            }
            $stocks .= $v['stockCode'];
        }
        $orderList = Db::table("xh_stock_order")
//            ->where("status=1 and isFreetrial=0")
            ->where("status=1")
            ->select();
        foreach ($orderList as $k => $v) {
            global $orderId, $liquidation;
            $send_phone_num = Db::table('xh_member')->where('id', $v['memberId'])->find();
            $orderId = $v['id'];
            $surplus = $v['surplus']; //警戒线
            $loss = $v['loss'];//止损线
            $buy_day_end_time =$v['buy_day_end_time']; //订单中的配资结束时间
            $time_day =date("Y-m-d H:i:s"); //当前时间
//            $stockDetail = $stockMap[$v['stockCode']];
            /*从实时数据过来*/
            $stockDetail = (new Common())->getMarketValueBycode_second($v['stockCode']);
            $diff_rate = $stockDetail['info_arr']['31'];//涨跌幅
            if ($diff_rate <= -9.95) {//股票跌停则不允许卖出
                echo $v['stockCode'] . "跌停<br/>";
                continue;
            }
            $nowPrice = $stockDetail['info_arr'][3];   //实时价格
            if ($nowPrice <= 0) {
                continue;
            }
            $profit = ((float)$nowPrice - (float)$v['dealPrice']) * $v['dealQuantity'] * 100; //交易盈亏
            $profit = round($profit, 2);
            //如果亏损小于止损线，则即时强制平仓
            $liquidation = -1; //0用户自己卖出; 1后台手动平仓；2超过止损线自动平仓；3超过警戒线线自动平仓，4超过配资期限自动平仓
            if ($profit < $surplus) {
                $liquidation = 3;
                //这部分是超过警戒线的值发送一个短信给用户
                (new  Common())->sendMobileToInformation($send_phone_num['mobile'], $surplus_information);
            } else if ($profit < $loss) {
                $liquidation = 2;
                //这部分是超过止损线的值发送的一个信息给用户
                (new  Common())->sendMobileToInformation($send_phone_num['mobile'], $loss_information);
            }

            if(strtotime($time_day)-strtotime($buy_day_end_time)>0){
                $liquidation = 5;
                (new  Common())->sendMobileToInformation($send_phone_num['mobile'], $expire_information);
            }
            echo "diff_rate=$diff_rate % , orderId = $orderId, profit = $profit, surplus = $surplus, loss=$loss <br/>";
            //访问后台函数的权限
//            define('UID', 1);
            session('user_auth.uid', 1);
            if ($liquidation == 2 || $liquidation == 3||$liquidation==5) {
                Db::transaction(function () {
                    global $orderId, $liquidation;
                    echo "orderId=$orderId, liquidation=$liquidation";
                    (new Order())->stock_sell_do($orderId, $liquidation);
                    Db::commit();
                });
            }
            $t2 = time();
            echo "t2-t1=" . ($t2 - $t1) . "<br/>";
        }

    }

    //获取股票实时价格，判断亏损额大于递延条件则不允许递延（每天结束交易时运行一次）
    public function noDelay()
    {
//        if(date("H:i:s") <= "15:00:00"){
//            die("交易时间不能调用此接口");
//        }
        $res = Db::table("xh_stock_order")
            ->where("status=1 and isFreetrial=0")
            ->field("distinct(stockCode)")
            ->select();
        $stocks = ""; //上证指数 深证成指 创业板指
        foreach ($res as $k => $v) {
            if ($stocks != "") {
                $stocks .= ",";
            }
            $stocks .= $v['stockCode'];
        }
//        $stockMap = (new Alistock())->batch_real_stockinfo_full($stocks);

        $orderList = Db::table("xh_stock_order")
            ->where("status=1 and isFreetrial=0")
            ->select();

        foreach ($orderList as $i => $v) {
            global $orderId;
            $orderId = $v['id'];
            $delayLine = $v['delayLine'];//止损线
            $stockDetail = (new Common())->getMarketValueBycode_second($v['stockCode']);
            $diff_rate = $stockDetail['info_arr']['31'];//涨跌幅
//            dump($diff_rate);
            //            $stockDetail = $stockMap[$v['stockCode']];
//            $diff_rate = $stockDetail->diff_rate ;//涨跌幅
            if ($diff_rate <= -9.95) {//股票跌停则不允许卖出
                echo $v['stockCode'] . "跌停<br/>";
                continue;
            }
//            $nowPrice = $stockDetail->nowPrice;
            $nowPrice = $stockDetail['info_arr'][3];   //实时价格
            if ($nowPrice <= 0) {
                continue;
            }
            //echo "diff_rate=$diff_rate, nowPrice=$nowPrice <br/>";
            $profit = ((float)$nowPrice - (float)$v['dealPrice']) * $v['dealQuantity'] * 100; //交易盈亏
            $profit = round($profit, 2);
            if ($profit < -abs($delayLine)) {
                echo "orderId=$orderId, profit=$profit, delayLine=$delayLine <br/>";
                Db::transaction(function () {
                    global $orderId;
                    (new Order())->stock_sell_do($orderId, 4); //4 收盘时亏损额大于递延条件而自动平仓
                    Db::commit();
                });
            }
        }
    }

    //计算递延天数和递延费 每天凌晨运行一次
    public function delayDays()
    {
        Db::transaction(function () {
            $t1 = time();
            require_once(dirname(__FILE__) . "/../../index/controller/Home.php");

            $delayFee = \app\index\controller\getSysParamsByKey("delayFee");
            if (!is_numeric($delayFee) || $delayFee <= 0) {
                die("递延费({$delayFee})不正确");
            }
            //获取交易列表
            $orderList = Db::table("xh_stock_order")
                ->where("status=1 and isFreetrial=0 and TO_DAYS(now()) - TO_DAYS(createTime)  > 0 ")
                ->select();
//          TODO：
            $hList = Db::table("xh_holiday")->field("day")->select();
            foreach ($orderList as $k => $v) {
                $orderId = $v['id'];
                $memberId = $v['memberId'];
                $dealAmount = $v['dealAmount'];
                $createTime = $v['createTime'];
                $workDays = $this->getWorkdaysCount($createTime, $orderId, $memberId, $delayFee * $dealAmount, $hList);
                $delayDays = $workDays;
                if ($delayDays < 0) {
                    $delayDays = 0;
                }
                if ($delayDays > 0) {
                    $delayFeeSum = 0;
                    if ($delayDays > 2) {
                        $delayFeeSum = ($delayDays - 2) * $delayFee * $dealAmount;
                    }
                    $sql = "update xh_stock_order set delayDays = $delayDays, delayFeeSum = $delayFeeSum where id=$orderId ";
                    Db::execute($sql);
                }
            }

            $t2 = time();
            echo "time=" . ($t2 - $t1);
        });
    }

    function getWorkdaysCount($start_date, $orderId, $memberId, $delayFee, $hList = null)
    {
        $t_start = strtotime($start_date);
        if (!$t_start) {
            die("时间格式不对");
        }

        //从数据库读取节假日列表
        if (!$hList || count($hList) <= 0) {
            $hList = Db::table("xh_holiday")->field("day")->select();
        }

        $days = 0;
        $i = 0;
        $nowTime = strtotime(date("Y-m-d ") . "23:59:59");
        while ($t_start + 3600 * 24 * $i < $nowTime) {
            $timer = $t_start + 3600 * 24 * $i;
            $num = date("N", $timer);
            if ($num >= 1 and $num <= 5) {
                $timerDate = date("Y-m-d", $timer);
                //判断是否是节假日
                $isHoliday = false;
                foreach ($hList as $k => $v) {
                    if ($timerDate == $v['day']) {
                        $isHoliday = true;
                        break;
                    }
                }
                if (!$isHoliday) { //非周末以及非节假日，则天数加1
                    $days++;
                    //如果days大于2，则添加递延费记录
                    if ($days > 2 && !Db::table("xh_day_delayfee")->where("day='$timerDate' and orderId=$orderId")->find()) {
                        $member = Db::table("xh_member")->field("username,mobile,usableSum")->where("id=$memberId")->find();
                        $usableSum = $member['usableSum'];
                        if ($usableSum >= $delayFee) {//如果可用余额大于即将扣除的递延费
                            $data = array();
                            $data['day'] = $timerDate;
                            $data['memberId'] = $memberId;
                            $data['orderId'] = $orderId;
                            $data['delayFee'] = $delayFee;
                            Db::table("xh_day_delayfee")->insertGetId($data);//增加递延记录

                            //扣除余额
                            Db::table("xh_member")->where("id=$memberId")->setInc("usableSum", -$delayFee);
                            //添加资金记录
                            $data = array();
                            $data['memberId'] = $memberId;
                            $data['flow'] = 2;
                            $data['amount'] = $delayFee;
                            $data['usableSum'] = $usableSum - $delayFee;
                            $data['remarks'] = "扣除{$timerDate}递延费{$delayFee}元(订单号:$orderId)";
                            $data['createTime'] = date("Y-m-d H:i:s");
                            Db::table("xh_member_fundrecord")->insertGetId($data);
                        } else {//如果余额不足  添加系统消息
                            $msg = "用户" . $member['username'] . "(手机号为" . $member['mobile'] . ")余额{$usableSum}元，扣除{$timerDate}的递延费{$delayFee}元失败(订单号:$orderId)";
                            Db::table("xh_note_msg")->insertGetId(array('message' => $msg, 'createTime' => date("Y-m-d H:i:s")));
                        }

                    }
                }

            }
            $i++;
        }

        return $days;
    }
    /*统计实际的配资天数即配资7天，则其中有三天是节假日，则实际就得添加10天的值才能计算到那一天（每天凌晨判断一次，如果是节假日自动加一）*/


}


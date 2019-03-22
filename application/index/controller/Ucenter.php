<?php

namespace app\index\controller;

use app\common\controller\Common;
use think\image\Exception;
use think\Model;

use think\Db;
use think\View;
use think\Request;


class Ucenter extends Home
{

    private $publicUrl = array('ff', 'stock_sell_do', '');

    public function ff()
    {
        echo "fff";
    }

    public function _initialize()
    {
        parent::_initialize();
        $request = \think\Request::instance();
        $action_name = $request->action();
        if (in_array($action_name, $this->publicUrl)) {
            return;
        }

        if (!isset($_SESSION['member'])) {
            $url = 'http://' . $_SERVER['HTTP_HOST'] . str_replace("index.php/", "", $_SERVER['PHP_SELF']);//.'?'.$_SERVER['QUERY_STRING'];
            if (isset($_SERVER['QUERY_STRING']) && $_SERVER['QUERY_STRING'] != '') {
                $url .= '?' . $_SERVER['QUERY_STRING'];
            }
            $_SESSION['redirect_url'] = $url;
            $this->redirect("index/index/login");
        }

        $memberId = $_SESSION['member']['id'];
        $member = Db::table("xh_member")->where("id=$memberId")->find();
        $this->assign("member", $member);

    }


    //个人中心-首页
    public function member()
    {
        $memberId = $_SESSION['member']['id'];

        $flow = trim(input("flow"));
        $recent = trim(input("recent"));
        $condition = " 1=1 ";

        if (isset($flow) && $flow != '') {
            $condition .= " and flow=$flow ";
        }
        if (isset($recent) && $recent != '' && $recent < 0) {
            $recent = -(int)$recent;
            $startDay = date("Y-m-d", time() - $recent * 24 * 3600);
            $startTime = $startDay . " 00:00:00";
            $condition .= " and createTime >= '$startTime' ";
        }

        //资金记录
        $fundrecord = Db::table("xh_member_fundrecord")
            ->where("memberId = $memberId and $condition ")
            ->order("id desc")
            ->paginate(10, false, ['query' => request()->param()]);
            $this->assign("fundrecord", $fundrecord);
        if (is_mobile_request()) {
            return view('ucenter/mobile/index');//手机的资金页面
        }
        return view('member');
    }

    //手机个人中心首页
    public function home()
    {
        return view('ucenter/mobile/home');
    }

//  //手机-资金明细
//  	
//  public function money_detail()
//  {
//		return view('ucenter/mobile/money_detail');
//  }


    //个人中心-充值
    public function payment()
    {
        if (is_mobile_request()) {
            return view('ucenter/mobile/payment');
        }
        return view('payment');
    }

    //手机-银行卡充值

    /**
     **************李火生*******************
     * @return \think\response\View
     * 银行卡申请
     **************************************
     */
    public function bank_transfer()
    {
        $memberId =$_SESSION['member']['id'];
        $name =Db::name('member')->field('username')->where('id',$memberId)->find();
        $name =$name['username'];
        $mobile =Db::name('member')->field('mobile')->where('id',$memberId)->find();
        $mobile =$mobile['mobile'];
        $create_time =date('Y-m-d H:i:s');
        $datas = input('post.');
        $pay_money =$datas['pay_money'];
        $beizhu  =$datas['beizhu'];
        $bank_num =$datas['bank_num'];
        $data =[
            'memberId'=>$memberId,
            'bankCard_num' =>$bank_num,
            'create_time' =>$create_time,
            'pay_money'=>$pay_money,
            'Remarks'=>$beizhu
        ];



        if(!empty($pay_money)){
            if($_POST){
                $data['email'] = '910705652@qq.com';
                $title ='尚牛在线银行卡充值';
//                $url =request()->domain().url('index/index/index', ['id'=> $memberId]);
                $content ='账号为:'.$memberId.'</br>手机号为:'.$mobile.'</br>用户名为:'.$name.'</br>通过银行卡号为：'.$bank_num.'</br>充值了：'.$pay_money.'</br>备注：'.$beizhu.'</br>申请时间:'.$create_time.'</br>'.'<span style="color: red">请管理员进行对账，查验是否充值进入账户(进行审核)</span>';
                \phpmailer\Email::send($data['email'],$title,$content);
               $res_data = Db::name('member_card_pay')->insertGetId($data);
               if($res_data>0){
                    //TODO:充值提示音
                   DB::name('music')->insert(['content'=>"银行卡充值申请"]);
                   $this->success('申请成功');
               }

            }
        }


//        if(!empty($pay_money)){
//            Db::name('member_card_pay')->data($data)->insert();
//            $this->success('申请成功');
//        }
        return view('ucenter/mobile/bank_transfer');
    }


    //手机-银联充值
    public function quick_pay()
    {
        $member = $this->getMember();
        $memberId = $member['id'];
        $this->assign("realName", $member['realName']);
        $this->assign("IDNumber", $member['IDNumber']);

        $bankcard = Db::table("xh_bankcard")->where("memberId=$memberId")->find();
        $this->assign("bankcard", $bankcard);

        if (is_mobile_request()) {
            return view('ucenter/mobile/quick_pay');
        }
        return view('quick_pay');
    }

    //手机-支付宝充值

    public function alipay()
    {
        //从数据库中找到这个用户
        $member = $this->getMember();
        $memberId = $member['id'];
        //拿到这个用户的相应数据
        $this->assign("realName", $member['realName']);
//      $this->assign("IDNumber", $member['IDNumber']);
        return view('ucenter/mobile/alipay');
    }

    //手机-支付宝充值-2
    public function re_tip()
    {
        return view('ucenter/mobile/re_tip');
    }

    //手机-微信

    public function wechatpay()
    {
        //从数据库中找到这个用户
        $member = $this->getMember();
        $memberId = $member['id'];
        //拿到这个用户的相应数据
        $this->assign("realName", $member['realName']);
        return view('ucenter/mobile/wechatpay');
    }

    //个人中心-提现
    public function withdraw()
    {
        $member = $this->getMember();
        $memberId = $member['id'];
        $this->assign("realName", $member['realName']);

        $bankcard = Db::table("xh_bankcard")->where("memberId=$memberId")->find();
        $this->assign("bankcard", $bankcard);

        $minWithdraw = getSysParamsByKey("minWithdraw");//最低提现金额
        $this->assign("minWithdraw", $minWithdraw);

        $_SESSION['needMobile'] = "no";
        $this->assign("needMobile", $_SESSION['needMobile']);
        $member = $this->getMember();
        $this->assign('mobile', $member['mobile']);

        if (is_mobile_request()) {
            return view('ucenter/mobile/withdraw');
        }
        return view('withdraw');
    }

    //提交操作 提现
    public function doWithdraw()
    {
        Db::transaction(function () {
            $bankId = trim(input("bankId"));
            $amount = trim(input("amount")); //提现金额

            if (!is_numeric($amount) || $amount <= 0) {
                error("提现金额不正确");
            }
            if (!is_numeric($bankId) || $bankId <= 0) {
                error("银行卡数据异常");
            }

            $minWithdraw = getSysParamsByKey("minWithdraw");//最低提现金额
            $member = $this->getMember();
            $memberId = $member['id'];
            $usableSum = $member['usableSum'];
            $realName = $member['realName'];
            /*先判断是否在平台上面购买过股票，是则可以进行提现，不是则提示未在尚牛平台上面配资不能进行提现*/
            $is_buy_stock =Db::table('xh_stock_order')->where('memberId',$memberId)->where('isFreetrial',0)->select();
            if(empty($is_buy_stock)){
                error("提现最低要求进行在尚牛平台上面进行配资一次");
            }

            if ($usableSum >= $minWithdraw && $amount < $minWithdraw) {
                error("最小提现金额为{$minWithdraw}元");
            }
            if ($usableSum < $minWithdraw && $amount != $usableSum) {
                error("余额小于{$minWithdraw}元必须全部提取");
            }

            if ($amount > $usableSum) {
                error("最大提现金额为{$usableSum }元");
            }

            $bank = Db::table("xh_bankcard")->where("memberId=$memberId and id=$bankId")->find();
            if (!$bank) {
                error("银行卡不存在");
            }
            if (!$realName || $realName == '') {
                error("您还未实名认证");
            }

            $data = array();
            $data['memberId'] = $memberId;
            $data['amount'] = $amount;
            $data['status'] = 0;
            $data['createTime'] = date("Y-m-d H:i:s");
            $data['bankName'] = $bank['bankName'] . " " . $bank['province'] . $bank['city'] . $bank['branch'];
            $data['cardNumber'] = $bank['cardNumber'];
            $data['realName'] = $realName;

            //增加提现申请的记录
            $ret = Db::table("xh_member_withdraw")->insertGetId($data);
            if ($ret <= 0) {
                error("添加数据失败.");
            }
            //记录新的数据提现申请（易于后台操作）//TODO:新添加（提现申请提示音）
            if($ret>0){
                Db::name('music')->insert(['content'=>'银行卡提现申请']);
            }
            //资金变动
            //余额减少
            $ret = Db::table("xh_member")->where("id = $memberId and usableSum >= $amount")->setInc('usableSum', -$amount);
            if ($ret <= 0) {
                error("余额不足");
            }

            //增加资金记录
            $data = array();
            $data['memberId'] = $memberId;
            $data['flow'] = 2;
            $data['amount'] = $amount;
            $data['usableSum'] = $usableSum - $amount;
            $data['remarks'] = "申请提现{$amount}元";
            $data['createTime'] = date("Y-m-d H:i:s");
            $ret = Db::table("xh_member_fundrecord")->insertGetId($data);
            if ($ret <= 0) {
                error("添加资金记录失败");
            }

            Db::commit();

            success("操作成功");

        });
    }


    private function getMember()
    {
        $memberId = $_SESSION['member']['id'];
        $member = Db::table("xh_member")->where("id = $memberId")->find();
        return $member;
    }


    //个人中心-银行卡管理
    public function bankcards()
    {
        $member = $this->getMember();
        $memberId = $member['id'];
        $this->assign("realName", $member['realName']);

        $bankcard = Db::table("xh_bankcard")->where("memberId=$memberId")->find();
        $this->assign("bankcard", $bankcard);
        $this->assign(


            "cardEndNO", substr($bankcard['cardNumber'], strlen($bankcard['cardNumber']) - 4, 4));

        if (is_mobile_request()) {
            return view('ucenter/mobile/bankcard');
        }
        return view('bankcards');
    }


    //保存银行卡信息
    public function saveBankCardsData()
    {
        $bankName = trim(input("bankName"));
        $province = trim(input("province"));
        $city = trim(input("city"));
        $branch_name = trim(input("branch_name"));
        $card_no = trim(input("card_no"));

        if ($bankName == '' || $province == '' || $city == '' || $branch_name == '' || $card_no == '') {
            error("信息填写不完整");
        }
        $member = $this->getMember();
        if (!$member['realName'] || trim($member['realName']) == '') {
            error("请先实名认证");
        }
        $memberId = $member['id'];

        $data['memberId'] = $memberId;
        $data['memberName'] = $member['realName'];
        $data['bankName'] = $bankName;
        $data['province'] = $province;
        $data['city'] = $city;
        $data['branch'] = $branch_name;
        $data['cardNumber'] = $card_no;
        $data['status'] = 1;
        $data['createTime'] = date("Y-m-d H:i:s");

        if (!Db::table("xh_bankcard")->where("memberId=$memberId")->find()) {
            Db::table("xh_bankcard")->insertGetId($data);
        } else {
            Db::table("xh_bankcard")->where("memberId=$memberId")->update($data);
        }

        success("保存成功");
    }

    //删除银行卡的操作
    public function deleteBankCard()
    {
        $memberId = $_SESSION['member']['id'];
        Db::table("xh_bankcard")->where("memberId=$memberId")->delete();
        success("删除成功");
    }

    //手机-添加/修改银行卡
    public function add_bankcard()
    {
        //找到用户
        $member = $this->getMember();
        $memberId = $member['id'];
        //拿到用户真实姓名
        $this->assign("realName", $member['realName']);
        $bankcard = Db::table("xh_bankcard")->where("memberId=$memberId")->find();
        $this->assign("bankcard", $bankcard);
        $this->assign("cardEndNO", substr($bankcard['cardNumber'], strlen($bankcard['cardNumber']) - 4, 4));

        return view('ucenter/mobile/add_bankcard');
    }


    //修改密码操作
    public function doUpdateNewPassword()
    {
        $login_oldPwd = trim(input('login_oldPwd'));
        $login_newPwd = trim(input('login_newPwd'));
        if (strlen($login_oldPwd) < 6 || strlen($login_newPwd) < 6) {
            error("请正确填写密码");
        }

        $member = $this->getMember();
        if (md5($login_oldPwd) != $member['password']) {
            error("原密码不正确");
        }
        $memberId = $member['id'];
        Db::table("xh_member")->where("id=$memberId")->update(array('password' => md5($login_newPwd)));
        success("更改密码成功");

    }

    //个人中心-账户安全
    public function security()
    {
        $member = $this->getMember();
        $IDNumber = $member['IDNumber'];
        if (strlen($IDNumber) > 8) {
            $len = strlen($IDNumber);
            $IDNumber = substr($IDNumber, 0, 4) . "**********" . substr($IDNumber, $len - 4, 4);
        }
        $mobile = $member['mobile'];
        if (strlen($mobile) == 11) {
            $mobile = substr($mobile, 0, 3) . "****" . substr($mobile, strlen($mobile) - 4, 4);
        }

        $this->assign("realName", $member['realName']);
        $this->assign("IDNumber", $IDNumber);
        $this->assign("mobile", $mobile);
        $this->assign("mobile1", $member['mobile']);

        return view('security');
    }

    //提交实名认证
    public function doReanNameAuth()
    {
        $memberId = $_SESSION['member']['id'];
        $realName = trim(input("realName"));
        $IDNumber = trim(input("IDNumber"));

        if (strlen($realName) <= 1) {
            $this->error("姓名不正确");
        }
        if (!$this->isIdcard($IDNumber)) {
            $this->error("身份证号码不正确");
        }
        $data = array();
        $data['realName'] = $realName;
        $data['IDNumber'] = $IDNumber;
       $bool = Db::table("xh_member")->where("id=$memberId")->update($data);
        if($bool){
            $this->success("ok", "index/ucenter/home");
        }

    }

    //手机-实名认证
    public function user_info()
    {
        //找到用户
        $member = $this->getMember();
        $IDNumber = $member['IDNumber'];
        //拿到用户身份证
        $this->assign("IDNumber", $IDNumber);

        if (is_mobile_request()) {
            return view('ucenter/mobile/user_info');
        }
    }

    //手机-实名认证2
    public function real_name()
    {
        return view('ucenter/mobile/real_name');
    }


    //个人中心-推广赚钱
    public function agent()
    {
        if (is_mobile_request()) {
            return view('ucenter/mobile/share');
        }
        return view('agent');
    }

    /**
     **************李火生*******************
     * @param $isFreetrial 0是A股 ，1是体验
     * 数据信息（点卖页面）
     **************************************
     */
    private function getSellData($isFreetrial)
    {
        $member = $_SESSION['member'];
        $memberId = (int)$member['id'];//强制类型转换
        $count = Db::table("xh_stock_order")->where("memberId=$memberId and isFreetrial=$isFreetrial and status = 1")->count();
        $this->assign('count', $count);// 分配数据统计出来这个count字数
        $sys_delayFee = getSysParamsByKey("delayFee");//获取系统表中delayFee的数据，数组形式18
        //即将收取的下个工作日的递延费
        $delayFeeSum = Db::table("xh_stock_order")
            ->where("memberId=$memberId and isFreetrial=$isFreetrial and status = 1 ")
            ->sum("$sys_delayFee * dealAmount");//18*成交金额
//            $delayFeeSum = 0;
        $delayFeeSum = round($delayFeeSum,2);
//        $delayFeeSum = number_format($price, 2, '.', '');
        $this->assign('delayFeeSum', $delayFeeSum);//18*成交金额
        //$sql = "select a.*, b.name as stockName, b.market from xh_stock_order as a, xh_shares as b where a.stockCode = b.`code`
        //  and a.memberId=$memberId and status = 1 order by a.id desc";
        //$list = Db::query($sql);
        $list = Db::field('xh_stock_order.*, xh_shares.name as stockName, xh_shares.market')->table('xh_stock_order, xh_shares')
            ->where(" xh_stock_order.stockCode = xh_shares.`code` and xh_stock_order.memberId=$memberId and isFreetrial=$isFreetrial and status = 1")
            ->order("createTime desc")->paginate(5);

        //获取列表信息，分五条数据显示，超过五条则分页显示
        //查询股票最新价
        $stocks = "";
        foreach ($list as $i => $v) {
            $s = $v['market'] . $v['stockCode'];
            //数到$s在第几个位置,输出8为这个数据,把拼接成sh300036
            if (strpos($stocks, $s) === false) {
                if ($stocks != "") {
                    $stocks .= ",";
                }
                $stocks .= $s;
            }
        }
        //调用腾讯接口数据
        $market_value_code = Db::table("xh_stock_order")->field('stockCode')->where('memberId', $memberId)->where('status', 1)->where('isFreetrial', $isFreetrial)->order("createTime desc")->paginate(5);
        foreach ($market_value_code as $k => $v) {
            $code = $v['stockCode'];
            $res[] = (new Common())->getMarketValueBycode($code);
        }
        if (!$res) {
            return view('sell');
        }
        $all_code = Db::table("xh_stock_order")->field('stockCode')->where('memberId', $memberId)->where('status', 1)->where('isFreetrial', $isFreetrial)->order("createTime desc")->select();
        foreach ($all_code as $k => $v) {
            $code = $v['stockCode'];
            $re[] = (new Common())->getMarketValueBycode($code);
            // $result =$res['info_arr'][3];
        }
        $lis = Db::field('xh_stock_order.*, xh_shares.name as stockName, xh_shares.market')->table('xh_stock_order, xh_shares')
            ->where(" xh_stock_order.stockCode = xh_shares.`code` and xh_stock_order.memberId=$memberId and isFreetrial=$isFreetrial and status = 1")
            ->order("createTime desc")->select();
        $profitSu = 0;
        $list3 = array();
        foreach ($lis as $i => $v) {
            $code = $v['stockCode'];//获取的是当时的值code代码
            $nowPric = (float)$re[$i]['info_arr'][3];//获取当钱的价格
            $dealPric = (float)$v['dealPrice'];
            $rate = ($nowPric - $dealPric) / $dealPric;//现在的价钱减去处理的价钱除处理价钱输出12.115
            $rate = round($rate, 4); //盈利或亏损的比率（四舍五进,保留四位小数）
            $profitAmoun = ($nowPric - $dealPric) * ((int)$v['dealQuantity']) * 100; //收益额（数量乘一百）//输出4346
            $profitAmoun = round($profitAmoun, 2);
            $profitSu += $profitAmoun; //收益金额累加
            $delayDays = $this->getDaysCount($v['createTime']) - 2;//递延天数
//            //TODO:递延天数
            if ($delayDays < 0) {
                $delayDays = 0;
            }
            //递延费 =递延天数*18
            $list3[$i]['nowPrice'] = $nowPric;
            $list3[$i]['rate'] = $rate;
            $list3[$i]['profitAmoun'] = $profitAmoun;
            $a = $list3[$i]['profitAmoun'];
            $list3[$i]['delayDays'] = $delayDays;  //递延天数
        }
        $_SESSION['profitSum'] = $profitSu;
        $this->assign("profitSum", $profitSu);
        $profitSum = 0;
        $list2 = array();
        foreach ($list as $i => $v) {

            $code = $v['stockCode'];//获取的是当时的值code代码
            $nowPrice = (float)$res[$i]['info_arr'][3];//获取当钱的价格
            $dealPrice = (float)$v['dealPrice'];
            $rate = ($nowPrice - $dealPrice) / $dealPrice;//现在的价钱减去处理的价钱除处理价钱输出12.115
            $rate = round($rate, 4); //盈利或亏损的比率（四舍五进,保留四位小数）
            $profitAmount = ($nowPrice - $dealPrice) * ((int)$v['dealQuantity']) * 100; //收益额（数量乘一百）//输出4346
            $profitAmount = round($profitAmount, 2);
            $delayDays = $this->getDaysCount($v['createTime']) - 2;//递延天数
            //入库
            if ($delayDays < 0) {
                $delayDays = 0;
            }
            $list2[$i]['nowPrice'] = $nowPrice;
            $list2[$i]['rate'] = $rate;
            $list2[$i]['profitAmount'] = $profitAmount;
            $list2[$i]['delayDays'] = $delayDays;
        }
        $this->assign("list", $list);
        $this->assign("listJson", json_encode($list));
        $this->assign("list2", $list2);
        $this->assign("listJson2", json_encode($list2));
    }

    //点卖区
    public function sell()
    {
        $this->getSellData(0);
        if (is_mobile_request()) {
            return view('ucenter/mobile/sell');
        }
        return view('sell');
    }

    //模拟点卖区
    public function freetrialSell()
    {
        $this->getSellData(1);
        if (is_mobile_request()) {
            return view('ucenter/mobile/freetrialSell');
        }
        return view('freetrialSell');
    }

    /**
     **************李火生*******************
     * @param $isFreetrial 1免费体验，0A股
     **************************************
     */
    private function getHistoryData($isFreetrial)
    {
        $member = $_SESSION['member'];
        $memberId = (int)$member['id'];
        $recent = input("recent");
        $condition = " 1=1 ";
        if (isset($recent)) {
            if ($recent == 7 || $recent == 30) {
                $recentDate = date("Y-m-d", time() - $recent * 24 * 3600);
                $recentDate .= " 00:00:00";
                $condition = " sellTime >= '$recentDate' ";
            } else if (strlen($recent) == 7) {
                $m1 = $recent . "-01 00:00:00";
                $m2 = $recent . "-31 23:59:59";
                //判断是否是合法的时间字符串
                if (date("Y-m-d H:i:s", strtotime($m1)) != $m1) {
                    die("时间格式不对");
                }
                $condition = " sellTime >= '$m1' and sellTime <= '$m2' ";
            }
        }

      $list = Db::field('xh_stock_order.*, xh_shares.name as stockName, xh_shares.market')->table('xh_stock_order, xh_shares')
            ->where(" xh_stock_order.stockCode = xh_shares.`code` and xh_stock_order.memberId=$memberId and isFreetrial=$isFreetrial and status = 2 and $condition ")
            ->order("sellTime desc")
            ->paginate(5, false, ['query' => request()->param()]);

        $this->assign("list", $list);
        $this->assign('profitFee', getSysParamsByKey("profitFee")); //平台对纯利润手续的手续费, 默认10%
    }

    //结算区
    public function history()
    {
        $this->getHistoryData(0);
        if (is_mobile_request()) {
            return view('ucenter/mobile/history');
        }
        return view('history');
    }

    //结算区 免费模拟
    public function freetrialHistory()
    {
        $this->getHistoryData(1);
        if (is_mobile_request()) {
            return view('ucenter/mobile/freetrialHistory');
        }
        return view('freetrialHistory');
    }


    //结算区-订单详情
    public function detail()
    {
        $id = (int)trim(input("id"));
        $d = Db::table("xh_stock_order")->where("id=$id")->find();
        $code = $d['stockCode'];
        $stock = Db::table("xh_shares")->field("name")->where("code='{$code}'")->find();
        $d['stockName'] = $stock['name'];
        $this->assign("d", $d);
        return view('detail');
    }

    //计算时间距离今天有多少天
    public function getDaysCount($dateStr)
    {
        $enddate = strtotime(date("Y-m-d H:i:s"));
        $startdate = strtotime(substr($dateStr, 0, 10));
        $days = round(($enddate - $startdate) / 3600 / 24);
        return $days;
    }

    /**
     **************李火生*******************
     * 买进股票的操作（按天买入）
     **************************************
     */
    public function stockBuy()
    {
            $this->isTradingTime();
            Db::transaction(function () {
                $member = $_SESSION['member'];
                $memberId = (int)$member['id'];
                $stockCode = trim($_POST['stockCode']);
                $all_price_sum = trim($_POST['all_price_sum']);//总计金额
                $dealAmount = (float)trim($_POST['dealAmount']);      //买入金额(万元)
                $surplus = (int)trim($_POST['surplus']);            //警戒线
                $loss = (int)trim($_POST['loss']);                  //触 发 止 损
//                $stockname =trim($_POST['name']);                    //股票的名字
                $publicFee = (int)trim($_POST['publicFee']);        //交易综合费

                $guaranteeFee = (int)trim($_POST['guaranteeFee']);  //履约保证金
                $delayLine = (int)trim($_POST['delayLine']);        //递延条件
                $delayFee = (int)trim($_POST['delayFee']);          //递延费（元/天)
                $stock_by_pay_type = (int)trim($_POST['stock_by_pay_type']);  //判断买入的类型是按月还是按日（1为按天，2为按月
                $Multiple =(int)trim($_POST['Multiple']); //买入的倍数
                $levers_multiples =(float)trim($_POST['levers_multiples']); //买入得到倍数对应的杠杆率
                $buy_day_num = trim($_POST['buy_day_num']); //配资的天数
                //读取系统设置的参数
                $sys_delayFee = (int)(getSysParamsByKey("delayFee"));
                $sys_dealFee = (int)(getSysParamsByKey("dealFee"));
                $delayLineRate = (float)getSysParamsByKey("delayLineRate"); //递延条件是保证金的0.75倍
                $stopLossRate = (float)getSysParamsByKey("stopLossRate"); //触发止损是保证金的0.8倍（当亏损额大于触发止损时，马上强制平仓）
                $maxDiffRate = (float)getSysParamsByKey("maxDiffRate"); //当某股票当天涨跌幅大于8%时不能购买
                $lossLine = (float)getSysParamsByKey("lossLine"); //亏损警戒线
                if($dealAmount>50){
                    error("买入金额超出范围，最高交易50万");
                }
                if($dealAmount<0.06){
                    error('买入的金额太低，最低交易600');
                }
//
                $create_date = date("Y-m-d H:i:s");
                $curDate = date("Y-m-d");
                $now_time =time();
                /**
                 * TODO:开始
                 */
                $buy_day_nums =$buy_day_num;
                $data_holidays =Db::table('xh_holiday')->field('day')->where('day','>',$curDate)->select();
                foreach($data_holidays as $k => $v)
                {
                    $data_holiday[] = $v['day'];
                }
                $i_day = 1;
                while($buy_day_nums)
                {
                    $temp = date("Y-m-d", strtotime('+'.$i_day.' days', $now_time));
                    $i_day++;
                    if (!in_array($temp, $data_holiday))
                        $buy_day_nums--;
                }

               $temp_str_time = strtotime("next day", strtotime($temp)) - 1;
                $temps =date("Y-m-d H:i:s",$temp_str_time);
                /**
                 * TODO:结束
                 */
                if(Db::table("xh_stock_order")->where("memberId=$memberId and left(createTime,10)=' $curDate'" )->count() >= 10){
                    error("您今天已购买了10次，不能再购买了。");
                }
                $res_str = $this->getMarketValueBycode($stockCode);
                $nowPrice = $res_str['info_arr'][3];
                $data = $res_str['info_arr'];
                /*股票名字先判断数据库中的是否名字已经存在，就是说遇到股票名字变换的时候，直接修改库中的内容*/
                $select_stock_name = Db::table('xh_shares')->field('name')->where('code', $stockCode)->find();
                if($data[1] !==$select_stock_name){
                    Db::table('xh_shares')->where('code',$stockCode)->update(['name'=>$data[1]]);
                }
                if(!empty(stristr($data[1],'S'))){
                    error('不能购买S、ST、*ST、S*ST、SST、以及被交易所特别处理的股票');
                }
                if ($data) {
                    $nowPrice = $data[3];
                    $diff_rate = $data[32];
                } else {
                    error("获取价格数据异常");
                }

//                $dealQuantity = (int)($dealAmount * 10000 / $nowPrice / 100); //买入多少手
                $dealQuantity = $dealAmount * 10000 / $nowPrice / 100; //买入多少手
                if($dealQuantity <1){
                    error("买入数量必须大于1手");
                }

                if(!$nowPrice || !is_numeric($nowPrice) || $nowPrice <= 0){
                    error("股票价格异常");
                }
                if($diff_rate >= $maxDiffRate || $diff_rate <= -$maxDiffRate ){
                    error("涨跌幅大于{$maxDiffRate}%的股票不能购买");
                }
                //in in_member_id int, in in_stockCode varchar(100), in in_dealPrice int , in in_dealAmount int , in in_dealQuantity int , in in_surplus int,
                // in in_loss int, in in_publicFee int, in in_guaranteeFee int, in in_delayLine int, in in_delayFee int
                //调用存储过程
//            $res = Db::query("call p_stock_buy( $memberId, '$stockCode', $nowPrice, $dealAmount, $dealQuantity, $surplus,
//                    $loss, $publicFee, $guaranteeFee, $delayLine, $delayFee, 0 )");
                //余额中扣钱
                $usableSum = Db::name('member')->field('usableSum')->where('id', $memberId)->find();
                $usableSum = $usableSum['usableSum']; //浮点数余额
                $new_usableSum = $usableSum - (float)$all_price_sum;
                if($new_usableSum > 0){
                    Db::name('member')->data('usableSum', $new_usableSum)->where('id', $memberId)->update();
                    $data = [
                        'memberId' => $memberId, //用户id
                        'stockCode' => $stockCode, //股票代号
                        'dealPrice' => $nowPrice,
                        'dealAmount' => $dealAmount,    //买入金额(万元)
                        'dealQuantity' => $dealQuantity,
                        'surplus' => $surplus,
                        'loss' => $loss,
                        'publicFee' => $publicFee,
                        'guaranteeFee' => $guaranteeFee,
                        'delayLine' => $delayLine,
                        'delayFee' => $sys_delayFee,
                        'createTime' => $create_date, //买入的时间
                        'status' => 1,
                        'isFreetrial'=>0, //a股购买
                        'stock_by_pay_type'=>$stock_by_pay_type, //判断买入的类型是按月还是按日（1为按天，2为按月）
                        'buy_day_num'=>$buy_day_num, //配资的天数
//                        'count_day_num'=>$count_day_num, //计算累加天数，方便计算配资天数到期的那一天
                        'buy_day_end_time'=>$temps, //按天配资所配资到的结束那天（中间已经除去非工作日）
                    ];
                }else{
              $this->error('余额不足，请前往充值','/home');
                }
                $res = Db::name('stock_order')->data($data)->insert();
                Db::commit();
                if ($res) {
                    $this->success("交易成功",'/sell');
                } else {
                    $this->error("交易失败");
                }

            });
        error("交易失败");
    }

    public function stockBuyByMonth(){
        $this->isTradingTime();
        Db::transaction(function () {
            $member = $_SESSION['member'];
            $memberId = (int)$member['id'];
            $stockCode = trim($_POST['stockCode']);
            $all_price_sum = trim($_POST['all_price_sum']);//总计金额
            $dealAmount = (float)trim($_POST['dealAmount']);      //买入金额(万元)
            $surplus = (int)trim($_POST['surplus']);            //警戒线
            $loss = (int)trim($_POST['loss']);                  //触 发 止 损
//            $stockname =trim($_POST['name']);                    //股票的名字
            $publicFee = (int)trim($_POST['publicFee']);        //交易综合费
            $guaranteeFee = (int)trim($_POST['guaranteeFee']);  //履约保证金
            $delayLine = (int)trim($_POST['delayLine']);        //递延条件
            $delayFee = (int)trim($_POST['delayFee']);          //递延费（元/天)
            $stock_by_pay_type = (int)trim($_POST['stock_by_pay_type']);  //判断买入的类型是按月还是按日（1为按天，2为按月)
            $Multiple =(int)trim($_POST['Multiple']); //买入的倍数
            $levers_multiples =(float)trim($_POST['levers_multiples']); //买入得到倍数对应的杠杆率
            $buy_month_num =(int)trim($_POST['buy_month_num']); //配资的月数
            //读取系统设置的参数
            $sys_delayFee = (int)(getSysParamsByKey("delayFee"));
            $sys_dealFee = (int)(getSysParamsByKey("dealFee"));
            $delayLineRate = (float)getSysParamsByKey("delayLineRate"); //递延条件是保证金的0.75倍
            $stopLossRate = (float)getSysParamsByKey("stopLossRate"); //触发止损是保证金的0.8倍（当亏损额大于触发止损时，马上强制平仓）
            $maxDiffRate = (float)getSysParamsByKey("maxDiffRate"); //当某股票当天涨跌幅大于8%时不能购买
            $lossLine = (float)getSysParamsByKey("lossLine"); //亏损警戒线
            if($dealAmount>50){
                error("买入金额超出范围，最高交易50万");
            }
            if($dealAmount<0.06){
                error('买入的金额太低，最低交易600');
            }
//                if($surplus != $dealAmount * 10000 *$lossLine/$Multiple){
//                    error("亏损警戒线数据错误");
//                }
//                //公式：倍数杠杆/100*保证金*天数*倍数
//                if($publicFee !=$levers_multiples*$guaranteeFee*$buy_day_num*$Multiple/100){
//                    error("交易综合费数据错误");
//                }
//                //当过了配资天数之后自动每天的递延费(倍数对应的倍率*保证金*倍数*1天)
//                if($delayFee !=$levers_multiples*$guaranteeFee*$Multiple){
//                    error("递延费数据错误");
//                }
//            if(abs($delayLine) != abs( (int)($guaranteeFee* $delayLineRate) ) ) {
//                error("递延条件数据错误");
//            }
//            if(abs($loss) != (int)($guaranteeFee * $stopLossRate)){
//                error("触发止损数据错误");
//            }
            $create_date = date("Y-m-d H:i:s");
            $curDate = date("Y-m-d");

            /**
             * 计算购买一个月之后的时间
             */
          $day_times =  date("Y-m-d H:i:s", strtotime("+$buy_month_num months", strtotime($curDate)));
            $temp_str_time = strtotime("next day", strtotime($day_times)) - 1;
            $buy_day_end_time =date("Y-m-d H:i:s",$temp_str_time);
            if(Db::table("xh_stock_order")->where("memberId=$memberId and left(createTime,10)=' $curDate'" )->count() >= 10){
                error("您今天已购买了10次，不能再购买了。");
            }
            $res_str = $this->getMarketValueBycode($stockCode);
            $nowPrice = $res_str['info_arr'][3];
            $data = $res_str['info_arr'];
            /*股票名字先判断数据库中的是否名字已经存在，就是说遇到股票名字变换的时候，直接修改库中的内容*/
            $select_stock_name = Db::table('xh_shares')->field('name')->where('code', $stockCode)->find();
            if($data[1] !==$select_stock_name){
                Db::table('xh_shares')->where('code',$stockCode)->update(['name'=>$data[1]]);
            }
            if(!empty(stristr($data[1],'S'))){
                error('不能购买S、ST、*ST、S*ST、SST、以及被交易所特别处理的股票');
            }
            if ($data) {
                $nowPrice = $data[3];
                $diff_rate = $data[32];
            } else {
                error("获取价格数据异常");
            }
            $dealQuantity = $dealAmount * 10000 / $nowPrice / 100; //买入多少手
            if($dealQuantity <1){
                error("买入数量必须大于1手(100股)");
            }

            if(!$nowPrice || !is_numeric($nowPrice) || $nowPrice <= 0){
                error("股票价格异常");
            }
            if($diff_rate >= $maxDiffRate || $diff_rate <= -$maxDiffRate ){
                error("涨跌幅大于{$maxDiffRate}%的股票不能购买");
            }
            //余额中扣钱
            $usableSum = Db::name('member')->field('usableSum')->where('id', $memberId)->find();
            $usableSum = $usableSum['usableSum']; //浮点数余额
            $new_usableSum = $usableSum - (float)$all_price_sum;
            if($new_usableSum > 0){
                Db::name('member')->data('usableSum', $new_usableSum)->where('id', $memberId)->update();
                $data = [
                    'memberId' => $memberId, //用户id
                    'stockCode' => $stockCode, //股票代号
                    'dealPrice' => $nowPrice,
                    'dealAmount' => $dealAmount,    //买入金额(万元)
                    'dealQuantity' => $dealQuantity,
                    'surplus' => $surplus,
                    'loss' => $loss,
                    'publicFee' => $publicFee,
                    'guaranteeFee' => $guaranteeFee,
                    'delayLine' => $delayLine,
                    'delayFee' => $sys_delayFee,
                    'createTime' => $create_date, //买入的时间
                    'status' => 1,     //状态值，1为买入
                    'isFreetrial'=>0, //a股购买（0为a股购买,1为免费体验）
                    'stock_by_pay_type'=>$stock_by_pay_type, //判断买入的类型是按月还是按日（1为按天，2为按月）
                    'buy_month_num'=>$buy_month_num, //配资的月数
                     'buy_day_end_time'=>$buy_day_end_time,                                   //配资到期时间
                ];
            }else{
                $this->error('余额不足，请前往充值','/home');
            }
            $res = Db::name('stock_order')->data($data)->insert();
            Db::commit();
            if ($res) {
                $this->success("交易成功",'/sell');
            } else {
                $this->error("交易失败");
            }

        });
        error("交易失败");
    }

    /**
     **************李火生*******************
     * 免费体验
     **************************************
     */
    public function freetrialBuy()
    {
        $this->isTradingTime();
        Db::transaction(function () {
            $member = $_SESSION['member'];
            $memberId = (int)$member['id'];
            $stockCode = trim($_POST['stockCode']);
            $all_price_sum = trim($_POST['all_price_sum']);//总计金额
            $dealAmount = (float)trim($_POST['dealAmount']);      //买入金额(万元)
            $surplus = (int)trim($_POST['surplus']);            //警戒线
            $loss = (int)trim($_POST['loss']);                  //触 发 止 损
//            $stockname =trim($_POST['name']);                    //股票的名字
            $publicFee = (int)trim($_POST['publicFee']);        //交易综合费
            $guaranteeFee = (int)trim($_POST['guaranteeFee']);  //履约保证金
            $delayLine = (int)trim($_POST['delayLine']);        //递延条件
            $delayFee = (int)trim($_POST['delayFee']);          //递延费（元/天)
            $stock_by_pay_type = (int)trim($_POST['stock_by_pay_type']);  //判断买入的类型是按月还是按日（1为按天，2为按月
            $Multiple =(int)trim($_POST['Multiple']); //买入的倍数
            $levers_multiples =(float)trim($_POST['levers_multiples']); //买入得到倍数对应的杠杆率
            $buy_day_num = trim($_POST['buy_day_num']); //配资的天数
            //读取系统设置的参数
            $sys_delayFee = (int)(getSysParamsByKey("delayFee"));
            $sys_dealFee = (int)(getSysParamsByKey("dealFee"));
            $delayLineRate = (float)getSysParamsByKey("delayLineRate"); //递延条件是保证金的0.75倍
            $stopLossRate = (float)getSysParamsByKey("stopLossRate"); //触发止损是保证金的0.8倍（当亏损额大于触发止损时，马上强制平仓）
            $maxDiffRate = (float)getSysParamsByKey("maxDiffRate"); //当某股票当天涨跌幅大于8%时不能购买
            $lossLine = (float)getSysParamsByKey("lossLine"); //亏损警戒线

            if($$dealAmount>50){
                error("买入金额超出范围，最高交易50万");
            }
            if($dealAmount<0.01){
                error('买入的金额太低，最低交易600');
            }
//            if($surplus != $dealAmount * 10000 *$lossLine/$Multiple+$dealAmount*10000){
//                error("亏损警戒线数据错误");
//            }
//
//            if(abs($loss) != (int)($guaranteeFee * $stopLossRate+$guaranteeFee*$Multiple)){
//                error("触发止损数据错误");
//            }
            $create_date = date("Y-m-d H:i:s");
            $curDate = date("Y-m-d");
            $now_time =time();
            /**
             * TODO:开始
             */
            $buy_day_nums =$buy_day_num;
            $data_holidays =Db::table('xh_holiday')->field('day')->where('day','>',$curDate)->select();
            foreach($data_holidays as $k => $v)
            {
                $data_holiday[] = $v['day'];
            }
            $i_day = 1;
            while($buy_day_nums)
            {
                $temp = date("Y-m-d", strtotime('+'.$i_day.' days', $now_time));
                $i_day++;
                if (!in_array($temp, $data_holiday))
                    $buy_day_nums--;
            }

            $temp_str_time = strtotime("next day", strtotime($temp)) - 1;
            $temps =date("Y-m-d H:i:s",$temp_str_time);
            /**
             * TODO:结束
             */
            $res_str = $this->getMarketValueBycode($stockCode);
            $nowPrice = $res_str['info_arr'][3];
            $data = $res_str['info_arr'];
            if(!empty(stristr($data[1],'S'))){
                error('不能购买S、ST、*ST、S*ST、SST、以及被交易所特别处理的股票');
            }
            if ($data) {
                $nowPrice = $data[3];
                $diff_rate = $data[32];
            } else {
                error("获取价格数据异常");
            }
            $dealQuantity = (int)($dealAmount * 10000 / $nowPrice / 100); //买入多少手
//            if($dealQuantity <1){
//                error("买入数量必须大于1手");
//            }
            if(!$nowPrice || !is_numeric($nowPrice) || $nowPrice <= 0){
                error("股票价格异常");
            }
            if($diff_rate >= $maxDiffRate || $diff_rate <= -$maxDiffRate ){
                $this->error("涨跌幅大于{$maxDiffRate}%的股票不能购买");
            }
            //余额中扣钱
            $freebleSum = Db::name('member')->field('freebleSum')->where('id', $memberId)->find();
            $freebleSum = $freebleSum['freebleSum']; //浮点数余额
            $new_freebleSum = $freebleSum - (float)$all_price_sum;

            if($new_freebleSum > 0){
                Db::name('member')->data('freebleSum', $new_freebleSum)->where('id', $memberId)->update();
//                $data = [
//                    'memberId' => $memberId,
//                    'stockCode' => $stockCode,
//                    'dealPrice' => $nowPrice,
//                    'dealAmount' => $dealAmount,
//                    'dealQuantity' => $dealQuantity,
//                    'surplus' => $surplus,
//                    'loss' => $loss,
//                    'publicFee' => $publicFee,
//                    'guaranteeFee' => $guaranteeFee,
//                    'delayLine' => $delayLine,
//                    'delayFee' => $sys_delayFee,
//                    'createTime' => $create_date,
//                    'status' => 1,
//                    'isFreetrial'=>1 //免费体验
//                ];
                $data = [
                    'memberId' => $memberId, //用户id
                    'stockCode' => $stockCode, //股票代号
                    'dealPrice' => $nowPrice,
                    'dealAmount' => $dealAmount,    //买入金额(万元)
                    'dealQuantity' => $dealQuantity,
                    'surplus' => $surplus,
                    'loss' => $loss,
                    'publicFee' => $publicFee,
                    'guaranteeFee' => $guaranteeFee,
                    'delayLine' => $delayLine,
                    'delayFee' => $sys_delayFee,
                    'createTime' => $create_date, //买入的时间
                    'status' => 1,
                    'isFreetrial'=>1 ,//免费体验
                    'stock_by_pay_type'=>$stock_by_pay_type, //判断买入的类型是按月还是按日（1为按天，2为按月）
                    'buy_day_num'=>$buy_day_num, //配资的天
                    'buy_day_end_time'=>$temps, //按天配资所配资到的结束那天（中间已经除去非工作日）
                ];
                }else{
                $this->error('体验金已用完，不能再购买');
            }
            $res = Db::name('stock_order')->data($data)->insert();
            Db::commit();
            if ($res) {
                $this->success("交易成功",'/sell');
            } else {
                $this->error("交易失败");
            }

        });
        error("交易失败");
    }

    /**
     **************李火生*******************
     * 节假日判断
     **************************************
     */
    public function isTradingTime()
    {
        //判断是否是节假日
        $isHoliday = false;
        $num = date("N", time());
        if ($num >= 1 and $num <= 5) {
            $timerDate = date("Y-m-d", time());
            $hList = Db::table("xh_holiday")->field("day")->select();
            foreach ($hList as $k => $v) {
                if ($timerDate == $v['day']) {
                    $isHoliday = true;
                    break;
                }
            }
        } else {
            $isHoliday = true;
        }
        if ($isHoliday) {
            error("节假日不能交易");
        }
        //TODO:限制购买时间
        $curTime = date("H:i:s");
        if (!($curTime >= '09:30:00' && $curTime <= '11:30:00' || $curTime >= '13:00:00' && $curTime <= '14:58:00')) {
            error("非交易时间,可交易时间（09:30:00-11:30:00）（13:00:00-14:58:00）");
        }

    }

    /**
     **************李火生*******************
     * 卖出股票
     **************************************
     */
    function stockSell()
    {
         $this->isTradingTime();
        Db::transaction(function () {
            $member = $_SESSION['member'];
            $memberId = (int)$member['id'];
            $orderId = (int)trim($_POST['orderId']);
            $ret = $this->stock_sell_do($orderId, $memberId);
            Db::commit();
            if ($ret > 0) {
                success("交易成功");
            }
        });
        error("交易失败");
    }

    /**
     **************李火生*******************
     * @param $orderId
     * @param null $memberId
     * @return int
     * 卖出股票操作
     **************************************
     */
    private function stock_sell_do($orderId, $memberId = null)
    {
        $this->isTradingTime();
        if (!$_SESSION['member'] && !session('user_auth')) {
            die("请先登录");
        }
        if (!$orderId || !is_numeric($orderId)) {
            die(false);
        }
        $con = " id=$orderId ";
        if ($memberId && is_numeric($memberId)) {
            $con .= " and memberId = $memberId";
        }
        $order = Db::table('xh_stock_order')->where($con)->find();
        if (!$order) {
            error("订单不存在");
        }
        $code = $order['stockCode'];
        $stock = Db::table("xh_shares")->where("code='$code'")->find();
        if (!$stock) {
            error("股票不存在");
        }
//                TODO:别忘记1
        //0到10个
         if(substr($order['createTime'], 0, 10) == date("Y-m-d")){
             error("当天点买的股票下个工作日才能卖出");
         }
        //判断是都在购买中遇到节假日或者周末进行跳过（只计算工作日）

//        $arr = (new Alistock())->batch_real_stockinfo($stock['market'].$stock['code']);
//        $nowPrice = $arr[$code];
        $arr = (new Common())->getMarketValueBycode($code);
        $nowPrice = $arr['info_arr'][3];
        //计算盈亏
        $profit = round(($nowPrice - $order['dealPrice']) * $order['dealQuantity'] * 100, 2);
        //计算盈利分配
        $profitSelf = $profit;
        $profitFee = (float)(getSysParamsByKey("profitFee"));
        if ($profit > 0) {
            $profitSelf = $profit * (1 - $profitFee);
        }
        //保证金
        $guaranteeFee = round($order['guaranteeFee'], 2);
        $amount = $guaranteeFee + $profitSelf;

        $sql = "update xh_stock_order set `status` = 2, sellPrice=$nowPrice, profit = $profit, profitSelf=$profitSelf, sellTime = now() where id = $orderId and `status` = 1";
        $ret = Db::execute($sql);
        if ($ret != 1) {
            error("请勿重复交易");
        }
        $sql = "update xh_member set usableSum = usableSum + $amount where id = $memberId;";
        $ret = Db::execute($sql);
        //查询余额
        $map = Db::table("xh_member")->field("usableSum")->where("id=$memberId")->find();
        $usableSum = $map['usableSum'];
        $sql = "insert into xh_member_fundrecord (memberId, flow, amount, usableSum, remarks, createTime)
            values ($memberId, '1', $amount, $usableSum , '卖出股票,退还保证金{$guaranteeFee}元，盈利分配{$profitSelf}元 ', now() );";
        $ret = Db::execute($sql);
        return $ret;
    }


    //卖出股票的操作
    function doFreetrialSell()
    {
        if (!$_SESSION['member'] && !session('user_auth')) {
            die("请先登录");
        }
        $this->isTradingTime();
            $member = $_SESSION['member'];
            $memberId = (int)$member['id'];
            $orderId = (int)trim($_POST['orderId']);

            $order = Db::table('xh_stock_order')->where("id=$orderId")->find();
            if (!$order) {
                error("订单不存在");
            }
            $code = $order['stockCode'];
            $stock = Db::table("xh_shares")->where("code='$code'")->find();
            if (!$stock) {
                error("股票不存在");
            }
            if (substr($order['createTime'], 0, 10) == date("Y-m-d")) {
                error("当天点买的股票下个工作日才能卖出");
            }
            $arr = (new Common())->getMarketValueBycode($code);
            $nowPrice = $arr['info_arr'][3];
            //计算盈亏
            $profit = round(($nowPrice - $order['dealPrice']) * $order['dealQuantity'] * 100, 2);
            //计算盈利分配
            $profitSelf = $profit;
//            $profitFee = (float)(getSysParamsByKey("profitFee"));
            $profitFee = (float)(getSysParamsByKey("FreeSellRate"));
            if ($profit > 0) {
//                $profitSelf = $profit * (1 - $profitFee);
                $profitSelf =$profit*$profitFee;
            }
            //保证金
            $guaranteeFee = round($order['guaranteeFee'], 2);
            $amount = $guaranteeFee + $profit;
//             $amount =$guaranteeFee;
            $sql = "update xh_stock_order set `status` = 2, sellPrice=$nowPrice, profit = $profit, profitSelf=$profitSelf, sellTime = now() where id = $orderId and `status` = 1";
            $ret = Db::execute($sql);
            if ($ret != 1) {
                error("请勿重复交易");
            }
            if( $profitSelf>=0){
                $sql = "update xh_member set freebleSum = freebleSum + $amount ,usableSum = usableSum + $profitSelf where id = $memberId;";
            }else{
                $sql = "update xh_member set freebleSum = freebleSum + $amount  where id = $memberId;";
            }
            $ret = Db::execute($sql);
            //查询余额
            $map = Db::table("xh_member")->field("usableSum,freebleSum")->where("id=$memberId")->find();
            $usableSum = $map['usableSum'];
            $freebleSum=$map['freebleSum'];
        if( $profitSelf>=0) {
            $sqls = "insert into xh_member_fundrecord (memberId, flow, amount, usableSum, remarks, createTime)
            values ($memberId, '1', $amount, $usableSum , '卖出股票,退还免息保证金{$guaranteeFee}元，盈利分配{$profitSelf}元，账户余额增加{$profitSelf}元 ，账户余额：{$usableSum}，免息体验金余额：{$freebleSum}', now() );";
        }else{
            $sqls = "insert into xh_member_fundrecord (memberId, flow, amount, usableSum, remarks, createTime)
            values ($memberId, '1', $amount, $usableSum , '卖出股票,退还免息保证金{$guaranteeFee}元，盈利分配{$profitSelf}元，账户余额增加0元，账户余额：{$usableSum}，免息体验金余额：{$freebleSum}', now() );";
        }

            $re = Db::execute($sqls);
            if($ret&&$re){
                $this->success('卖出成功',url('./freetrialHistory'));
                return $ret;
            }

    }

    /********************php验证身份证号码是否正确函数*********************/
    function isIdcard($id)
    {
        $id = strtoupper($id);
        $regx = "/(^\d{15}$)|(^\d{17}([0-9]|X)$)/";
        $arr_split = array();
        if (!preg_match($regx, $id)) {
            return FALSE;
        }
        if (15 == strlen($id)) //检查15位
        {
            $regx = "/^(\d{6})+(\d{2})+(\d{2})+(\d{2})+(\d{3})$/";

            @preg_match($regx, $id, $arr_split);
            //检查生日日期是否正确
            $dtm_birth = "19" . $arr_split[2] . '/' . $arr_split[3] . '/' . $arr_split[4];
            if (!strtotime($dtm_birth)) {
                return FALSE;
            } else {
                return TRUE;
            }
        } else      //检查18位
        {
            $regx = "/^(\d{6})+(\d{4})+(\d{2})+(\d{2})+(\d{3})([0-9]|X)$/";
            @preg_match($regx, $id, $arr_split);
            $dtm_birth = $arr_split[2] . '/' . $arr_split[3] . '/' . $arr_split[4];
            if (!strtotime($dtm_birth)) //检查生日日期是否正确
            {
                return FALSE;
            } else {
                //检验18位身份证的校验码是否正确。
                //校验位按照ISO 7064:1983.MOD 11-2的规定生成，X可以认为是数字10。
                $arr_int = array(7, 9, 10, 5, 8, 4, 2, 1, 6, 3, 7, 9, 10, 5, 8, 4, 2);
                $arr_ch = array('1', '0', 'X', '9', '8', '7', '6', '5', '4', '3', '2');
                $sign = 0;
                for ($i = 0; $i < 17; $i++) {
                    $b = (int)$id{$i};
                    $w = $arr_int[$i];
                    $sign += $b * $w;
                }
                $n = $sign % 11;
                $val_num = $arr_ch[$n];
                if ($val_num != substr($id, 17, 1)) {
                    return FALSE;
                } //phpfensi.com
                else {
                    return TRUE;
                }
            }
        }

    }

    //保存上传的图片
    public function savePeopleImg()
    {
        $memberId = $_SESSION['member']['id'];
        $headImg = trim(input("headImg"));
        Db::table("xh_member")->where("id=$memberId")->update(array('headImg' => $headImg));
        success("保存成功");
    }

    //更改手机号码 验证手机验证码是否正确
    public function checkMobileCode()
    {
        $mobile = trim(input('mobile'));
        $code = trim(input('code'));

        if (strlen($mobile) != 11 || substr($mobile, 0, 1) != '1' || $code == '') {
            error("参数不正确");
        }

        if ($_SESSION['mobileCode'] != $code || $_SESSION['mobile'] != $mobile) {
            error("验证码不正确");
        }

        unset($_SESSION['mobile']);
        unset($_SESSION['mobileCode']);

        //标记原手机号验证成功
        $_SESSION['verifyOldMobile'] = 1;

        success("验证成功");

    }

    //更新新手机号码
    public function updateNewMobile()
    {
        $mobile = trim(input('mobile'));
        $code = trim(input('code'));

        if (strlen($mobile) != 11 || substr($mobile, 0, 1) != '1' || $code == '') {
            error("参数不正确");
        }

        if ($_SESSION['mobileCode'] != $code || $_SESSION['mobile'] != $mobile) {
            error("验证码不正确");
        }

        if ($_SESSION['verifyOldMobile'] != 1) {
            error("您不该这样操作...");
        }

        $memberId = $_SESSION['member']['id'];
        if (Db::table("xh_member")->where("mobile='$mobile'")->find()) {
            error("手机号{$mobile}已存在");
        }
        Db::table("xh_member")->where("id=$memberId")->update(array("mobile" => $mobile));

        unset($_SESSION['verifyOldMobile']);

        success("修改成功");

    }


    /**
     **************李火生*******************
     * @param Request $request
     * 支付宝充值获取信息存库
     **************************************
     */
    public function  getInformationAlipay(Request $request){
        if($request->isPost()){
            $data['pay_money']=trim($_POST['amount']);
            $data['pay_explain']=trim($_POST['instructions']);
            $data['pay_number']=trim($_POST['alipay']);
            $data['user_id']=trim($_SESSION['member']['id']);
            $data['createTime']=date("Y-m-d H:i:s");
            $data['status']=0;
           if(!empty($data)){
               $res = Db::table('xh_alipay_examine')->insertGetId($data);
               if($res>0){
                    //TODO:充值提示音
                       Db::name('music')->insert(['content'=>"支付宝充值申请"]);
                   return $this->ajax_success('提交成功,请等候审核',$data);
               }
           }

        }
    }

    /**
     **************李火生*******************
     * @param Request $request
     * 微信充值获取信息进行存库操作
     **************************************
     */
    public function  getInformationWeChat(Request $request){
        if($request->isPost()){
            $data['pay_money']=trim($_POST['amount']);
            $data['pay_explain']=trim($_POST['instructions']);
            $data['pay_number']=trim($_POST['alipay']);
            $data['user_id']=trim($_SESSION['member']['id']);
            $data['createTime']=date("Y-m-d H:i:s");
            $data['status']=0;
            if(!empty($data)){
                $res = Db::table('xh_wechat_examine')->insertGetId($data);
                if($res){
                    //TODO:充值提示音
                    Db::name('music')->insert(['content'=>"微信充值申请"]);
                    return $this->ajax_success('提交成功,请等候审核',$data);
                }
            }

        }
    }


    /**
     **************李火生*******************
     * 补仓
     **************************************
     */
    public function  money_add(Request $request)
    {
        if ($request->isPost()) {
            $order_id = $_POST['order_id'];
            $money_update = $_POST['money_update'];
            if (empty($order_id)) {
                return ajax_success('订单号不存在', ['status' => 0]);
            }

            if (empty($money_update)) {
                return ajax_success('请输入补仓金额', ['status' => 0]);
            }
            $memberId = $_SESSION['member']['id'];
            $usableSum = Db::name('member')->where('id',$memberId)->find();
            $usableSum_change = $usableSum['usableSum'] - $money_update;
            if ($usableSum_change < 0) {
                return ajax_success('余额不足，请前往充值');
            }

            $data = Db::name('stock_order')->where('id', $order_id)->find();
            $order_new_money = $data['guaranteeFee'] + $money_update;//新保证金
            $order_dealAmount = $data['dealAmount'];//成交金额
            $stopLossRate = getSysParamsByKey("stopLossRate");//止损线
            $lossLine = getSysParamsByKey("lossLine");//警戒线

            $loss = $stopLossRate * $order_new_money + $order_dealAmount * 10000;
            $surplus = $lossLine * $order_new_money + $order_dealAmount * 10000;
            $data_update = [
                'loss' => $loss,
                'surplus' => $surplus,
                'guaranteeFee'=>$order_new_money
            ];
            $res = Db::name('stock_order')->where('id',$order_id)->update($data_update);
            if ($res) {
                $usableSum = Db::name('member')->where('id', $data['memberId'])->find();
                $usableSum_change = $usableSum['usableSum'] - $money_update;
                if ($usableSum_change < 0) {
                    $this->ajax_success('余额不足，请前往充值');
                }
                $del_money = Db::name('member')->where('id', $data['memberId'])->update(['usableSum' => $usableSum_change]);
                if ($del_money) {
                    return ajax_success('补仓成功', ['status' => 1]);
                } else {
                    return ajax_success('补仓失败', ['status' => 2]);
                }

            } else {
                return ajax_success('补仓失败', ['status' => 2]);
            }

        }
    }


}
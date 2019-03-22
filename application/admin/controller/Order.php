<?php
/**
 * Created by PhpStorm.
 * User: wo
 * Date: 2017/7/12
 * Time: 15:20
 */

namespace app\admin\controller;

use app\admin\controller\Admin;
use app\user\model\Role as RoleModel;
use app\common\builder\ZBuilder;
use app\admin\model\Module as ModuleModel;
use app\admin\model\Menu as MenuModel;
use app\admin\model\Access as AccessModel;
use app\user\model\User as UserModel;
use think\Cache;
use think\Db;
use app\user\validate\User;
use util\Tree;
use app\common\controller\common;
use app\index\controller\Alistock;
use app\index\controller\Home;
use think\Request;

class Order extends Admin
{
    //股票持仓列表（点买列表）
    public function buyList(){
		// 获取筛选
		$map = $this->getMap();
		
        $auth = session('user_auth');
        if($auth['role'] == 2){
            $uid = $auth['uid'];
            $condition = " and recommendCode='{$uid}'";
        }
        // 数据列表
        $data_list = Db::table(["xh_stock_order"=>'a', "xh_member"=>'b' ])
            ->field("a.*, b.username, b.mobile")
            ->where($map)
            ->where("a.memberId = b.id and a.isFreetrial = 0 and status = 1 $condition")
            ->order("a.id desc")->paginate(10);
        $list = array();
        //数据处理(递延天数减2)
        foreach($data_list as $i=>$v){
            $v['delayDays'] -= 2;
            if( $v['delayDays'] < 0){
                $v['delayDays'] = 0;
            }
            $list[$i] = $v;
        }
        // 分页数据
        $page = $data_list->render();

        $btn = ['title' => '平仓',
            'icon'  => 'fa fa-fw fa-key',
            'href'  => 'javascript:doLiquidation(__id__)'//url('index', ['uid' => '__id__',  ])
        ];

        // 使用ZBuilder快速创建数据表格   liquidation
        return ZBuilder::make('table')
            ->hideCheckbox()
            ->js('order')
            ->setPageTitle('交易管理') // 设置页面标题
            ->setTableName('xh_stock_order') // 设置数据表名
            ->addTimeFilter('a.createTime')//日期
            ->setSearch(['username', 'mobile','stockCode']) // 设置搜索参数
            ->addColumns([ // 批量添加列
                ['username', '用户名'],
                ['mobile', '手机号'],
                ['stockCode', '股票' ],
                ['dealPrice', '买入价(元)'],
                ['dealAmount', '买入金额(万)'],
                ['dealQuantity', '数量(手)'],
                ['surplus', '警戒线(元)'],
                ['loss', '平仓线(元)'],
                ['publicFee', '综合费(元)'],
                ['guaranteeFee', '保证金(元)'],
                ['delayLine', '递延线(元)'],
                ['buy_day_num','配资天数'],
                ['buy_month_num','配资月数(月)'],
                ['delayDays', '递延天数（天）'],
                ['delayFeeSum', '递延费(元)'],
                ['createTime', '买入时间' ],
                ['buy_day_end_time','配资到期时间'],
                ['right_button', '操作', 'btn']
            ])
            ->addRightButton('custom', $btn ) // 批量添加右侧按钮
            ->setRowList($list) // 设置表格数据
            ->setPages($page) // 设置分页数据
            ->fetch(); // 渲染页面
    }

    /**
     **************李火生*******************
     * @return mixed
     **************************************
     */
    public function finished(){
    	// 获取筛选
		$map = $this->getMap();
        $auth = session('user_auth');
        if($auth['role'] == 2){
            $uid = $auth['uid'];
            $condition = " and recommendCode='{$uid}'";
        }

        // 数据列表
        $data_list = Db::table(["xh_stock_order"=>'a', "xh_member"=>'b' ])
            ->field("a.*, b.username, b.mobile")
            ->where($map)
            ->where("a.memberId = b.id and a.isFreetrial = 0 and status = 2 $condition ")
            ->order("a.sellTime desc, a.id desc")->paginate(10);

        // 分页数据
        $page = $data_list->render();
        $list = array();
        foreach($data_list as $i=>$v){
            //0用户自己卖出; 1后台手动平仓；2超过止损线自动平仓；3超过止盈线自动平仓；4 收盘时亏损额大于递延条件而自动平仓
            $l_map = array('用户卖出', '后台手动平仓','超过止损线自动平仓', '超过止盈线自动平仓', '大于递延线自动平仓');
            $v['liquidation'] = $l_map[$v['liquidation']];
            $list[$i] = $v;
        }

        // 使用ZBuilder快速创建数据表格   liquidation
        return ZBuilder::make('table')
            ->hideCheckbox()
            ->js('order')
            ->setPageTitle('交易管理') // 设置页面标题
            ->addTimeFilter('a.createTime')//时间
            ->setTableName('xh_stock_order') // 设置数据表名
            ->setSearch(['username', 'mobile','stockCode']) // 设置搜索参数
            ->addColumns([ // 批量添加列
                ['username', '用户名'],
                ['mobile', '手机号'],
                ['stockCode', '股票' ],
                ['dealPrice', '买入价(元)'],
                ['sellPrice', '卖出价(元)'],
                ['dealAmount', '买入金额(万)'],
                ['dealQuantity', '数量(手)'],
                ['publicFee', '综合费(元)'],
                ['delayFee', '递延费(元)'],
                ['profit', '盈亏(元)'],
                ['profitSelf', '盈利分配(元)'],
                ['guaranteeFee', '保证金(元)'],
                ['delayDays', '递延天数（天）'],
                ['buy_day_num','配资天数'],
                ['buy_month_num','配资月数(月)'],
                ['createTime', '买入时间' ],
                ['buy_day_end_time','配资到期时间'],
                ['sellTime', '卖出时间' ],
                ['liquidation', '类型' ],
            ])
            ->setRowList($list) // 设置表格数据
            ->setPages($page) // 设置分页数据
            ->fetch(); // 渲染页面
    }

    /**
     **************李火生*******************
     * 手动平仓
     **************************************
     */
    public function liquidation(){
        global $orderId ;
        $orderId = trim(input("orderId"));
        if($orderId=='' || !is_numeric($orderId) || $orderId <= 0){
            error("订单号不对");
        }
        Db::transaction(function(){
            global $orderId ;
            $this->stock_sell_do($orderId, 1);
            Db::commit();
            ajax_success('操作成功');

        });
        error("操作失败");
    }

    //$liquidation: 0用户自己卖出; 1后台手动平仓；2超过止损线自动平仓；3超过警戒线; 4 收盘时亏损额大于递延条件而自动平仓
    public function stock_sell_do($orderId , $liquidation = 1){
        if(!$orderId || !is_numeric($orderId)){
            die(false);
        }
        $con = " id=$orderId ";
        $order = Db::table('xh_stock_order')->where( $con )->find();
        if(!$order ){
            error("订单不存在");
        }
        $memberId = $order['memberId'];
        $code = $order['stockCode'];
        $stock = Db::table("xh_shares")->where("code='$code'")->find();
        if(!$stock){
            error("股票不存在");
        }
        if(substr($order['createTime'], 0, 10) == date("Y-m-d")){
          $this->error("当天点买的股票下个工作日才能卖出");
        }
//        $arr = (new Alistock())->batch_real_stockinfo($stock['market'].$stock['code']);
        $arr =(new Common())->getMarketValueBycode($stock['code']);
        if(!$arr || !is_array($arr)){
            error("获取股票数据失败");
        }
        $nowPrice = $arr['info_arr'][3];
        if($nowPrice <= 0 || !is_numeric($nowPrice)){
            error("股票数据异常");
        }

        //计算盈亏
        $profit = round( ($nowPrice - $order['dealPrice']) * $order['dealQuantity'] * 100 ,2 );
        //计算盈利分配
        $profitSelf = $profit;
        //手续费
        $profitFee = (float)(\app\index\controller\Home::getSysParamsByKey("profitFee"));
        //减去分成之后实际得的钱
        if($profit > 0){
            $profitSelf = $profit * ( 1- $profitFee);
        }
        //保证金
        $guaranteeFee = round( $order['guaranteeFee'], 2 ) ;
        $amount = $guaranteeFee + $profitSelf;

        $sql = "update xh_stock_order set `status` = 2, sellPrice=$nowPrice, profit = $profit, profitSelf=$profitSelf, sellTime = now(), liquidation = $liquidation
            where id = $orderId and `status` = 1";
        //echo $sql;return;
        $ret = Db::execute($sql);
        if($ret != 1){
            error("请勿重复交易");
        }
        $sql = "update xh_member set usableSum = usableSum + $amount where id = $memberId;";
        $ret = Db::execute($sql);
        //查询余额
        $map = Db::table("xh_member")->field("usableSum")->where("id=$memberId")->find();
        $usableSum = $map['usableSum'];
        if(empty($usableSum)){
            $usableSum =0;
        }
        $remarks = "手动";
        if($liquidation == 2){
            $remarks = "亏损超过止损线自动";
        }else if($liquidation == 3){
            $remarks = "亏损超过警戒线自动";
        }else if($liquidation == 4){
            $remarks = "收盘时亏损额超过递延线自动";
        }else if($liquidation == 5){
            $remarks = "配资时间到期自动";
        }
        $remarks .= "平仓,退还保证金{$guaranteeFee}元,盈利分配{$profitSelf}元。";
        $creat_tiems =date("Y-m-d H:i:s");
        $sql = "insert into xh_member_fundrecord (memberId, flow, amount, usableSum, remarks, createTime)
            values ($memberId, '1', $amount, $usableSum , '{$remarks}', '{$creat_tiems}' );";
        $ret = Db::execute($sql);
        return $ret;
    }


}
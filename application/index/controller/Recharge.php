<?php
/**
 * Created by PhpStorm.
 * User: 李火生
 * Date: 2018/12/1
 * Time: 10:27
 */
namespace app\index\controller;

use think\Controller;

class Recharge extends  Controller{

    /**
     **************李火生*******************
     * @return \think\response\View
     * 充值首页
     **************************************
     */
    public function recharge_index(){
        return view('recharge_index');
    }

    /**
     **************李火生*******************
     * @return \think\response\View
     * 微信充值
     **************************************
     */
    public function recharge_wechat(){
        return view('recharge_wechat');
    }

    /**
     **************李火生*******************
     * @return \think\response\View
     * 支付宝充值
     **************************************
     */
    public function recharge_alipay(){
        return view('recharge_alipay');
    }

    /**
     **************李火生*******************
     * @return \think\response\View
     * 银行卡充值
     **************************************
     */
    public function recharge_bank(){
        return view('recharge_bank');
    }

}
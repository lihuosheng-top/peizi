<?php
/**
 * Created by PhpStorm.
 * User: 李火生
 * Date: 2018/9/18
 * Time: 10:17
 */

namespace app\index\controller;

use think\Controller;
use think\Db;
use think\Request;

class  Stock extends  Controller{

    /**
     **************李火生*******************
     * @return \think\response\View
     * 按天配资
     **************************************
     */
    public function  stock_day(){
      return  view('index/mobile/stock_day');
    }

    /**
     **************李火生*******************
     * 按天配资返回的利息数据(返回给前台信息使用)
     **************************************
     */
    public function stock_day_return(Request $request){
        if($request->isPost()){
            $res_data =Db::table('xh_stock_interest_day')->order('days','asc')->select();
            if(!empty($res_data)){
                return ajax_success('成功返回',$res_data);
            }else{
                return error('失败');
            }
        }
    }


    /**
     **************李火生*******************
     * @return \think\response\View
     * 按月配资
     **************************************
     */
    public function  stock_week(){
        return  view('index/mobile/stock_week');
    }


    /**
     **************李火生*******************
     * 按周配资返回的利息数据(返回给前台信息使用)
     **************************************
     */
    public function stock_week_return(Request $request){
        if($request->isPost()){
            $res_data =Db::table('xh_stock_interest_week')->order('weeks','asc')->select();
            if(!empty($res_data)){
                return ajax_success('成功返回',$res_data);
            }else{
                return error('失败');
            }
        }
    }

    /**
     **************李火生*******************
     * @return \think\response\View
     * 按周配资
     **************************************
     */
    public function  stock_month(){
        return  view('index/mobile/stock_month');
    }


    /**
     **************李火生*******************
     * 按周配资返回的利息数据(返回给前台信息使用)
     **************************************
     */
    public function stock_month_return(Request $request){
        if($request->isPost()){
            $res_data =Db::table('xh_stock_interest_month')->order('months','asc')->select();
            if(!empty($res_data)){
                return ajax_success('成功返回',$res_data);
            }else{
                return error('失败');
            }
        }
    }


    /**
     **************李火生*******************
     * @return \think\response\View
     * 免息配资
     **************************************
     */
    public function  stock_free(){
        return  view('index/mobile/stock_free');
    }



}
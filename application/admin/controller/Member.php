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
use think\session;


class Member extends Admin
{
    public function index(){
    	// 获取筛选
		$map = $this->getMap();

        $auth = session('user_auth');
        if($auth['role'] == 2){
            $uid = $auth['uid'];
            $condition = "recommendCode='{$uid}'";
        }

        // 数据列表
        $data_list = Db::table("xh_member")->where($map)
        ->where($condition)->where('statuss',1)->order("id desc")->paginate();

        // 分页数据
        $page = $data_list->render();
 

        // 按钮
        $btn_recharge = [
            'title' => '充值/扣费',
            'icon'  => 'fa fa-fw fa-key',
            'href'  => url('recharge', ['uid' => '__id__'])
        ];

        //查看资金按钮
        $btn_fundrecord = [
            'title' => '资金明细',
            'icon'  => 'fa fa-fw fa-rmb',
            'href'  => url('fundrecord', ['uid' => '__id__'])
        ];

        // 使用ZBuilder快速创建数据表格
        return ZBuilder::make('table')
            ->setPageTitle('用户列表') // 设置页面标题
            ->addTimeFilter('createTime')//时间
            ->setTableName('admin_user') // 设置数据表名
           	->setSearch([ 'username' => '用户名', 'mobile' => '手机','recommendCode'=>'机构推荐码']) // 设置搜索参数
            ->addColumns([ // 批量添加列
                ['username', '用户名'],
                ['mobile', '手机号'],
                ['usableSum', '可用余额(元)'],
                ['recommendCode', '机构推荐码'],
                ['createTime', '创建时间' ],
                ['realName', '真实姓名' ],
                ['IDNumber', '身份证号' ],
                ['right_button', '操作', 'btn']
            ])
            ->addRightButtons('edit') // 批量添加右侧按钮
            ->addRightButton('custom', $btn_recharge)
            ->addRightButton('custom', $btn_fundrecord)
            ->addRightButton('delete'  ,['href' => url('delete', ['id' => '__id__'])])
            ->setRowList($data_list) // 设置表格数据
            ->setPages($page) // 设置分页数据
            ->fetch(); // 渲染页面
    }


    /**
     **************李火生*******************
     * @param null $id
     * @return mixed|void
     * 编辑
     **************************************
     */
    public function edit($id = null)
    {
        if ($id === null) return $this->error('缺少参数');

        // 保存数据
        if ($this->request->isPost()) {
            $data = $this->request->post();

            if (Db::table("xh_member")->where("id=$id")->update($data)) {
                // 记录行为
                action_log('member_edit', 'admin_role', $id, UID, $data['name']);
                return $this->success('编辑成功', url('index'));
            } else {
                return $this->error('编辑失败');
            }
        }

        // 获取数据
        $info       = Db::table("xh_member")->where("id=$id")->find();

        $this->assign('info', $info);


        // 使用ZBuilder快速创建表单
        return ZBuilder::make('form')
            ->addFormItems([
                ['static', 'username', '用户名'],
                ['text', 'mobile', '手机号'],
                ['static', 'usableSum', '可用余额'],
                ['text', 'recommendCode', '机构推荐码'],
                ['static', 'createTime', '注册时间'],
                ['text', 'realName', '真实姓名'],
                ['text', 'IDNumber', '身份证号'],
            ])
            ->setFormData($info)
            ->fetch();
    }


    /**
     **************李火生*******************
     * @return mixed
     * 软删除
     **************************************
     */
    public function delete($id=null)
    {
        if($id <= 0){
            return $this->error("id不正确");
        }
            $data = ['statuss'=>0];
            if (Db::table("xh_member")->where("id=$id")->update($data)) {
                // 记录行为
                action_log('member_delete', 'admin_role', $id, UID, $data['name']);
                return $this->success('删除成功', url('index'));
            } else {
                return $this->error('删除失败');
            }


    }


    public function recharge()
    {
        // 保存数据
        if ($this->request->isPost()) {
            Db::transaction(function(){
                $uid = (int)trim(input("uid"));
                $amount = trim(input("amount"));
                if($uid <= 0){
                    return $this->error('参数错误');
                }
                if(!is_numeric($amount)){
                    return $this->error('金额格式不对');
                }

                if ( Db::table("xh_member")->where("id=$uid")->setInc("usableSum", $amount) ) {
                    $member =  Db::table("xh_member")->field("usableSum")->where("id=$uid")->find();
                    $usableSum = $member['usableSum'];
                    $flow = 1;
                    $remarks = "后台手动充值";
                    if($amount < 0){
                        $flow = 2;
                        $remarks = "后台手动扣费";
                        $amount = -$amount;
                    }

                    //增加资金记录
                    $data = array();
                    $data['memberId'] = $uid;
                    $data['flow'] = $flow;
                    $data['amount'] = $amount;
                    $data['usableSum'] = $usableSum;
                    $data['remarks'] = $remarks;
                    $data['createTime'] = date("Y-m-d H:i:s");
                    Db::table("xh_member_fundrecord")->insertGetId($data);

                    //如果是充值，则增加充值记录
                    if($amount > 0){
                        $data = array();
                        $data['amount'] = $amount;
                        $data['memberId'] = $uid;
                        $data['status'] = 3;
                        $data['no_order'] = "admin_{$uid}_".date("YmdHis").rand(10,99);
                        $data['createTime'] = date("Y-m-d H:i:s");
                        Db::table("xh_member_recharge")->insertGetId($data);
                    }

                    Db::commit();

                    // 记录行为
                    action_log('recharge', 'admin_member', $uid , UID);
                    return $this->success('新增成功', url('index'));
                } else {
                    return $this->error('新增失败');
                }
            });

        }

        // 使用ZBuilder快速创建表单
        return ZBuilder::make('form')
            ->setPageTitle('充值/扣费') // 设置页面标题
            ->addFormItems([ // 批量添加表单项
                ['text', 'amount', '金额', '必填，金额大于0为充值，金额小于0为扣费'],
            ])
            ->fetch();
    }

    //提现申请
    public function withdraw_apply(){
    	// 获取查询条件
        $map = $this->getMap();
        // 数据列表
        $data_list = Db::table(["xh_member_withdraw"=>'a', "xh_member"=>'b'])
            ->field("a.*,  b.username, b.mobile")
            ->where($map)
            ->where("a.memberId = b.id and a.status=0")
            ->order("id desc")
            ->paginate();
        // 分页数据
        $page = $data_list->render();
        $btn_yes = [
            'title' => '审核通过',
            'icon'  => 'fa fa-fw fa-check-square-o',
            'href'  => "javascript:doWithdraw(__id__, 1)"
        ];
        $btn_no = [
            'title' => '审核不通过',
            'icon'  => 'fa fa-fw fa-power-off',
            'href'  => "javascript:doWithdraw(__id__, 2)"
        ];
        // 使用ZBuilder快速创建数据表格
        return ZBuilder::make('table')
            ->hideCheckbox()
            ->js('member')
            ->setPageTitle('用户列表') // 设置页面标题
             ->addTimeFilter('a.createTime')//日期
            ->setTableName('admin_user') // 设置数据表名
            ->setSearch(['username','mobile']) // 设置搜索参数
            ->addColumns([ // 批量添加列
                ['username', '用户名'],
                ['mobile', '手机号'],
                ['amount', '提现金额'],
                ['bankName', '提现银行卡名称'],
                ['cardNumber', '提现银行卡号'],
                ['realName', '银行卡姓名' ],
                ['createTime', '创建时间' ],
                ['right_button', '操作', 'btn']
            ])
            ->addRightButton('custom', $btn_yes)
            ->addRightButton('custom', $btn_no)
            ->setRowList($data_list) // 设置表格数据
            ->setPages($page) // 设置分页数据
            ->fetch(); // 渲染页面
    }


    //提现记录
    public function withdraw_finished(){
    	// 获取查询条件
        $map = $this->getMap();
        // 数据列表
        $data_list = Db::table(["xh_member_withdraw"=>'a', "xh_member"=>'b'])
            ->field("a.*,  b.username, b.mobile")
            ->where($map)
            ->where("a.memberId = b.id and a.status>0")
            ->order("id desc")
            ->paginate();

        // 分页数据
        $page = $data_list->render();

        $list = array();
        foreach($data_list as $i=>$v){
            if($v['status'] == 1){
                $v['status'] = '通过';
            }else if($v['status'] == 2){
                $v['status'] = '未通过';
            }
            $list[$i] = $v;
        }

        // 使用ZBuilder快速创建数据表格
        return ZBuilder::make('table')
            ->hideCheckbox()
            ->js('member')
            ->setPageTitle('用户列表') // 设置页面标题
            ->setTableName('admin_user') // 设置数据表名
            ->setSearch(['username', 'mobile']) // 设置搜索参数
            ->addColumns([ // 批量添加列
                ['username', '用户名'],
                ['mobile', '手机号'],
                ['amount', '提现金额'],
                ['bankName', '提现银行卡名称'],
                ['cardNumber', '提现银行卡号'],
                ['realName', '银行卡姓名' ],
                ['createTime', '创建时间' ],
                ['status', '状态' ],
            ])
             ->addTimeFilter('a.createTime')//日期
            ->setRowList($list) // 设置表格数据
            ->setPages($page) // 设置分页数据
            ->fetch(); // 渲染页面
    }

    /**
     **************李火生*******************
     * 提现过程
     **************************************
     */
    public function do_withdraw(){
        $id = (int)trim(input("id"));
        if($id <= 0){
            error("数据错误");
        }
        $status = (int)trim(input("status"));//1 审核通过；2审核失败
        if($status != 1 && $status != 2){
            error("状态值不对");
        }

        $withdraw = Db::table("xh_member_withdraw")->where("id=$id")->find();
        if(!$withdraw){
            error("数据不存在");
        }
        $memberId = $withdraw['memberId'];
        $amount = $withdraw['amount'];
      $music =  Db::table("xh_member_withdraw")->where("id=$id")->update( array("status"=>$status) );
        if($music){
            $this->set_music_null();
        }
        if($status == 2){
            //余额\增加
            $ret = Db::table("xh_member")->where("id = $memberId ")->setInc('usableSum', $amount);
            $member = Db::table("xh_member")->field("usableSum")->where("id = $memberId")->find();

            //增加资金记录
            $data=array();
            $data['memberId'] = $memberId;
            $data['flow'] = 1;
            $data['amount'] = $amount;
            $data['usableSum'] = $member['usableSum'];
            $data['remarks'] = "提现申请审核未通过，退还{$amount}元";
            $data['createTime'] = date("Y-m-d H:i:s");
            $ret = Db::table("xh_member_fundrecord")->insertGetId($data);
        }else if($status == 1){//审核成功

        }

        /*
        $memberId = $withdraw['memberId'];
        $amount = $withdraw['amount'];
        $bankName = $withdraw['bankName'];
        $cardNumber = $withdraw['cardNumber'];
        $realName = $withdraw['realName'];

        //余额减少
        $ret = Db::table("xh_member")->where("id = $memberId and usableSum >= $amount")->setInc('usableSum', -$amount);
        if($ret <= 0) {
            error("余额不足");
        }
        $member = Db::table("xh_member")->field("usableSum")->where("id = $memberId")->find();
        $usableSum = $member['usableSum'];

        //增加资金记录
        $data=array();
        $data['memberId'] = $memberId;
        $data['flow'] = 2;
        $data['amount'] = $amount;
        $data['usableSum'] = $usableSum;
        $data['remarks'] = "申请提现{$amount}元";
        $data['createTime'] = date("Y-m-d H:i:s");
        $ret = Db::table("xh_member_fundrecord")->insertGetId($data);
        if($ret <= 0){
            error("添加资金记录失败");
        }
        */

        success("操作成功");

    }


    //用户资金明细
    public function fundrecord(){

        $uid = (int)trim(input("uid"));
        if(!empty($uid)){
            // 数据列表
            $map = $this->getMap();
            $data_list = Db::table(["xh_member_fundrecord"=>'a', "xh_member"=>'b'])
                ->field("a.*,  b.username, b.mobile")
                ->where("a.memberId = b.id and a.memberId=$uid ")
                ->where($map)
                ->order("id desc")
                ->paginate();

            // 分页数据
            $page = $data_list->render();
            // 使用ZBuilder快速创建数据表格
            return ZBuilder::make('table')
                ->hideCheckbox()
                ->js('pay_static')
                ->setPageTitle('用户列表') // 设置页面标题
                ->setTableName('admin_user') // 设置数据表名
                ->setSearch(['memberId' => 'ID', 'username' => '户名', 'mobile' => '手机号']) // 设置搜索参数// 设置搜索参数
                ->addColumns([ // 批量添加列
                    ['memberId','用户ID'],
                    ['username','用户名'],
                    ['mobile', '手机号'],
                    ['flow', '资金流向'],
                    ['amount', '操作金额'],
                    ['usableSum', '余额' ],
                    ['createTime', '时间' ],
                    ['remarks', '说明' ]
                ])
                ->setRowList($data_list) // 设置表格数据
                ->setPages($page) // 设置分页数据
                ->fetch(); // 渲染页面
        }
       if(empty($uid)){
                $map = $this->getMap();
                // 数据列表
                $data_list = Db::table(["xh_member_fundrecord"=>'a', "xh_member"=>'b'])
                    ->field("a.*,  b.username, b.mobile")
                    ->where("a.memberId = b.id")
                    ->where($map)
                    ->order("id desc")
                    ->paginate();
                // 分页数据
                $page = $data_list->render();
                // 使用ZBuilder快速创建数据表格
                return ZBuilder::make('table')
                    ->hideCheckbox()
                    ->js('pay_static')
                    ->setPageTitle('用户列表') // 设置页面标题
                    ->setTableName('admin_user') // 设置数据表名
                    ->setSearch(['memberId' => 'ID', 'username' => '户名', 'mobile' => '手机号']) // 设置搜索参数
                    ->addColumns([ // 批量添加列
                        ['memberId','用户ID'],
                        ['username', '用户名'],
                        ['mobile', '手机号'],
                        ['flow', '资金流向'],
                        ['amount', '操作金额'],
                        ['usableSum', '余额' ],
                        ['createTime', '时间' ],
                        ['remarks', '说明' ]
                    ])
                    ->setRowList($data_list) // 设置表格数据
                    ->setPages($page) // 设置分页数据
                    ->fetch(); // 渲染页面
        }

    }





    /**
     **************李火生*******************
     *审核信息显示
     **************************************
     */
    public  function bank_apppay(){


        $data_list = Db::table(["xh_member_card_pay"=>'a', "xh_member"=>'b'])
            ->field("a.*,  b.username, b.mobile")
            ->where("a.memberId = b.id")
            ->order("id desc")
            ->paginate();

        // 分页数据
        $page = $data_list->render();
        $list = array();
        foreach($data_list as $i=>$v){
            if($v['status'] == 0){
                $v['status'] = '待审核';
            }
            if($v['status'] == 1){
                $v['status'] = '通过';
            }
            if($v['status'] == 2){
                $v['status'] = '未通过';
            }
            $list[$i] = $v;
        }
        // 使用ZBuilder快速创建数据表格
        return ZBuilder::make('table')
            ->hideCheckbox()
            ->js('member')
            ->setPageTitle('充值状态') // 设置页面标题
            ->setTableName('member_card_pay') // 设置数据表名
            ->setSearch(['memberId' => '用户ID', 'username' => '用户名', 'mobile' => '手机号']) // 设置搜索参数
            ->addColumns([ // 批量添加列
                ['id', '订单号'],
                ['username', '用户名'],
                ['memberId', '用户Id'],
                ['mobile', '用户手机号'],
                ['pay_money', '操作金额'],
                ['bankCard_num', '充值的银行卡号' ],
                ['create_time', '时间' ],
                ['Remarks', '备注' ],
                ['status','状态'],
            ])
            ->setRowList($list) // 设置表格数据
            ->setPages($page) // 设置分页数据
            ->fetch(); // 渲染页面
    }

    /**
     **************李火生*******************
     * 审核操作
     **************************************
     */
    public function bank_app()
    {
        $id = (int)trim(input("id"));
        if($id <= 0){
            error("数据错误");
        }
        $status = (int)trim(input("status"));//1 审核通过；2审核失败，0待审核
        if($status != 1 && $status != 2&&$status !=0){
            error("状态值不对");
        }

        $withdraw = Db::table("xh_member_card_pay")->where("id=$id")->find();
        if(!$withdraw){
            error("数据不存在");
        }

      $music=  Db::table("xh_member_card_pay")->where("id=$id")->update(array("status"=>$status));
       if($music){
           $this->set_music_null();
       }
        if($status == 1){
           //审核不通过
           success('审核通过');

        }else if($status == 1){
            //审核通过
            error('失败');
        }
        success("操作成功");
    }


    /**
     **************李火生*******************
     * 用户银行卡充值操作（审核）
     **************************************
     */
    public function recharge_operation(){
    // 获取查询条件
    $map = $this->getMap();
    // 数据列表
    $data_list = Db::table(["xh_member_card_pay"=>'a', "xh_member"=>'b'])
        ->field("a.*,  b.username, b.mobile")
        ->where($map)
        ->where("a.memberId = b.id and a.status=0")
        ->order("id desc")
        ->paginate();
    // 分页数据
    $page = $data_list->render();
    $btn_yes = [
        'title' => '审核通过',
        'icon'  => 'fa fa-fw fa-check-square-o',
        'href'  => "javascript:doDate(__id__,1)"
    ];
    $btn_no = [
        'title' => '审核不通过',
        'icon'  => 'fa fa-fw fa-power-off',
        'href'  => "javascript:doDate(__id__,2)"
    ];
    // 使用ZBuilder快速创建数据表格
    return ZBuilder::make('table')
        ->hideCheckbox()
        ->js('member')
        ->setPageTitle('充值状态') // 设置页面标题
        ->setTableName('member_card_pay') // 设置数据表名
        ->setSearch(['username','mobile']) // 设置搜索参数
        ->addColumns([ // 批量添加列

            ['id', '订单号'],
            ['username', '用户名'],
            ['memberId', '用户Id'],
            ['mobile', '用户手机号'],
            ['pay_money', '操作金额'],
            ['bankCard_num', '充值的银行卡号' ],
            ['create_time', '申请时间' ],
            ['Remarks', '备注' ],
            ['right_button', '操作', 'btn']
        ])
        ->addRightButton('custom', $btn_yes)
        ->addRightButton('custom', $btn_no)
        ->setRowList($data_list) // 设置表格数据
        ->setPages($page) // 设置分页数据
        ->fetch(); // 渲染页面
}






    /**
     **************李火生*******************
     * 支付宝支付的信息
     **************************************
     */
    public function  alipay_examine(){

        $data_list = Db::table(["xh_alipay_examine"=>'a', "xh_member"=>'b'])
            ->field("a.*,  b.username, b.mobile")
            ->where("a.user_id = b.id")
            ->order("id desc")
            ->paginate();

        // 分页数据
        $page = $data_list->render();
        $list = array();
        foreach($data_list as $i=>$v){
            if($v['status'] == 0){
                $v['status'] = '待审核';
            }
            if($v['status'] == 1){
                $v['status'] = '通过';
            }
            if($v['status'] == 2){
                $v['status'] = '未通过';
            }
            $list[$i] = $v;
        }
        // 使用ZBuilder快速创建数据表格
        return ZBuilder::make('table')
            ->hideCheckbox()
            ->js('member')
            ->setPageTitle('充值状态') // 设置页面标题
            ->setTableName('alipay_examine') // 设置数据表名
            ->setSearch(['memberId' => '用户ID', 'username' => '用户名', 'mobile' => '手机号']) // 设置搜索参数
            ->addColumns([ // 批量添加列
                ['id', '订单号'],
                ['username', '用户名'],
                ['memberId', '用户Id'],
                ['mobile', '用户手机号'],
                ['pay_money', '操作金额'],
                ['pay_number', '充值的支付宝账号' ],
                ['createTime', '时间' ],
                ['pay_explain', '备注' ],
                ['status','状态'],
            ])
            ->setRowList($list) // 设置表格数据
            ->setPages($page) // 设置分页数据
            ->fetch(); // 渲染页面
    }

    /**
     **************李火生*******************
     * 支付宝的审核操作（0未审核，1审核通过，2审核不通过）
     **************************************
     */
    public function alipay_examine_action()
    {
        $id = (int)trim(input("id"));
        if ($id <= 0) {
            error("数据错误");
        }
        $status = (int)trim(input("status"));//1 审核通过；2审核失败，0待审核
        if ($status != 1 && $status != 2 && $status != 0) {
            error("状态值不对");
        }

        $withdraw = Db::table("xh_alipay_examine")->where("id=$id")->find();
        if (!$withdraw) {
            error("数据不存在");
        }

      $music =  Db::table("xh_alipay_examine")->where("id=$id")->update(array("status" => $status));
        if($music){
            $this->set_music_null();
        }
        if ($status == 1) {
            //审核不通过
            success('审核通过');

        } else if ($status == 1) {
            //审核通过
            error('失败');
        }
        success("操作成功");

    }

    /**
     **************李火生*******************
     * 支付宝的待审核
     **************************************
     */
    public function  alipay_examine_unaudited(){
        // 获取查询条件
        $map = $this->getMap();
        // 数据列表
        $data_list = Db::table(["xh_alipay_examine"=>'a', "xh_member"=>'b'])
            ->field("a.*,  b.username, b.mobile")
            ->where($map)
            ->where("a.user_id = b.id and a.status=0")
            ->order("id desc")
            ->paginate();
        // 分页数据
        $page = $data_list->render();
        $btn_yes = [
            'title' => '审核通过',
            'icon'  => 'fa fa-fw fa-check-square-o',
            'href'  => "javascript:doAlipay(__id__,1)"
        ];
        $btn_no = [
            'title' => '审核不通过',
            'icon'  => 'fa fa-fw fa-power-off',
            'href'  => "javascript:doAlipay(__id__,2)"
        ];
        // 使用ZBuilder快速创建数据表格
        return ZBuilder::make('table')
            ->hideCheckbox()
            ->js('member')
            ->setPageTitle('充值状态') // 设置页面标题
            ->setTableName('alipay_examine') // 设置数据表名
            ->setSearch(['username','mobile']) // 设置搜索参数
            ->addColumns([ // 批量添加列

                ['id', '订单号'],
                ['username', '用户名'],
                ['memberId', '用户Id'],
                ['mobile', '用户手机号'],
                ['pay_money', '操作金额'],
                ['pay_number', '充值的支付宝账号' ],
                ['createTime', '时间' ],
                ['pay_explain', '备注' ],
                ['right_button', '操作', 'btn']
            ])
            ->addRightButton('custom', $btn_yes)
            ->addRightButton('custom', $btn_no)
            ->setRowList($data_list) // 设置表格数据
            ->setPages($page) // 设置分页数据
            ->fetch(); // 渲染页面
    }






    /**
     **************李火生*******************
     * 微信支付的信息
     **************************************
     */
    public function  weichat_examine(){
        $data_list = Db::table(["xh_wechat_examine"=>'a', "xh_member"=>'b'])
            ->field("a.*,  b.username, b.mobile")
            ->where("a.user_id = b.id")
            ->order("id desc")
            ->paginate();

        // 分页数据
        $page = $data_list->render();
        $list = array();
        foreach($data_list as $i=>$v){
            if($v['status'] == 0){
                $v['status'] = '待审核';
            }
            if($v['status'] == 1){
                $v['status'] = '通过';
            }
            if($v['status'] == 2){
                $v['status'] = '未通过';
            }
            $list[$i] = $v;
        }
        // 使用ZBuilder快速创建数据表格
        return ZBuilder::make('table')
            ->hideCheckbox()
            ->js('member')
            ->setPageTitle('充值状态') // 设置页面标题
            ->setTableName('wechat_examine') // 设置数据表名
            ->setSearch(['memberId' => '用户ID', 'username' => '用户名', 'mobile' => '手机号']) // 设置搜索参数
            ->addColumns([ // 批量添加列
                ['id', '订单号'],
                ['username', '用户名'],
                ['memberId', '用户Id'],
                ['mobile', '用户手机号'],
                ['pay_money', '操作金额'],
                ['pay_number', '充值的支付宝账号' ],
                ['createTime', '时间' ],
                ['pay_explain', '备注' ],
                ['status','状态'],
            ])
            ->setRowList($list) // 设置表格数据
            ->setPages($page) // 设置分页数据
            ->fetch(); // 渲染页面
    }

    /**
     **************李火生*******************
     * 微信支付的审核操作（0未审核，1审核通过，2审核不通过）
     **************************************
     */
    public function weichat_examine_action(){
        $id = (int)trim(input("id"));
        if ($id <= 0) {
            error("数据错误");
        }
        $status = (int)trim(input("status"));//1 审核通过；2审核失败，0待审核
        if ($status != 1 && $status != 2 && $status != 0) {
            error("状态值不对");
        }

        $withdraw = Db::table("xh_wechat_examine")->where("id=$id")->find();
        if (!$withdraw) {
            error("数据不存在");
        }

       $music = Db::table("xh_wechat_examine")->where("id=$id")->update(array("status" => $status));
        if($music){
            $this->set_music_null();
        }

        if ($status == 1) {
            //审核不通过
            success('审核通过');

        } else if ($status == 1) {
            //审核通过
            error('失败');
        }
        success("操作成功");
    }

    /**
     **************李火生*******************
     * 微信的未审核
     **************************************
     */
    public function  weichat_examine_unaudited(){
        // 获取查询条件
        $map = $this->getMap();
        // 数据列表
        $data_list = Db::table(["xh_wechat_examine"=>'a', "xh_member"=>'b'])
            ->field("a.*,  b.username, b.mobile")
            ->where($map)
            ->where("a.user_id = b.id and a.status=0")
            ->order("id desc")
            ->paginate();
        // 分页数据
        $page = $data_list->render();
        $btn_yes = [
            'title' => '审核通过',
            'icon'  => 'fa fa-fw fa-check-square-o',
            'href'  => "javascript:doWechat(__id__,1)"
        ];
        $btn_no = [
            'title' => '审核不通过',
            'icon'  => 'fa fa-fw fa-power-off',
            'href'  => "javascript:doWechat(__id__,2)"
        ];
        // 使用ZBuilder快速创建数据表格
        return ZBuilder::make('table')
            ->hideCheckbox()
            ->js('member')
            ->setPageTitle('充值状态') // 设置页面标题
            ->setTableName('wechat_examine') // 设置数据表名
            ->setSearch(['username','mobile']) // 设置搜索参数
            ->addColumns([ // 批量添加列

                ['id', '订单号'],
                ['username', '用户名'],
                ['memberId', '用户Id'],
                ['mobile', '用户手机号'],
                ['pay_money', '操作金额'],
                ['pay_number', '充值的支付宝账号' ],
                ['createTime', '时间' ],
                ['pay_explain', '备注' ],
                ['right_button', '操作', 'btn']
            ])
            ->addRightButton('custom', $btn_yes)
            ->addRightButton('custom', $btn_no)
            ->setRowList($data_list) // 设置表格数据
            ->setPages($page) // 设置分页数据
            ->fetch(); // 渲染页面
    }


    /**
     **************李火生*******************
     * @return mixed
     * 删除列表的用户列表
     **************************************
     */
    public function del_index(){
        // 获取筛选
        $map = $this->getMap();

        $auth = session('user_auth');
        if($auth['role'] == 2){
            $uid = $auth['uid'];
            $condition = "recommendCode='{$uid}'";
        }

        // 数据列表
        $data_list = Db::table("xh_member")->where($map)
            ->where($condition)->where('statuss',0)->order("id desc")->paginate();

        // 分页数据
        $page = $data_list->render();

        // 使用ZBuilder快速创建数据表格
        return ZBuilder::make('table')
            ->setPageTitle('用户列表') // 设置页面标题
            ->addTimeFilter('createTime')//时间
            ->setTableName('admin_user') // 设置数据表名
            ->setSearch([ 'username' => '用户名', 'mobile' => '手机','recommendCode'=>'机构推荐码']) // 设置搜索参数
            ->addColumns([ // 批量添加列
                ['username', '用户名'],
                ['mobile', '手机号'],
                ['usableSum', '可用余额(元)'],
                ['recommendCode', '机构推荐码'],
                ['createTime', '创建时间' ],
                ['realName', '真实姓名' ],
                ['IDNumber', '身份证号' ],
                ['right_button', '操作', 'btn']
            ])
            ->addRightButton('edit'  ,['href' => url('edits', ['id' => '__id__'])])
            ->setRowList($data_list) // 设置表格数据
            ->setPages($page) // 设置分页数据
            ->fetch(); // 渲染页面
    }


    /**
     **************李火生*******************
     * @param null $id
     * 用户恢复软删除操作，回顾登录功能
     **************************************
     */
    public function edits($id=null)
    {
        if($id <= 0){
            return $this->error("id不正确");
        }
        $data = ['statuss'=>1];
        if (Db::table("xh_member")->where("id=$id")->update($data)) {
            // 记录行为
            action_log('member_delete', 'admin_role', $id, UID, $data['name']);
            return $this->success('恢复成功', url('index'));
        } else {
            return $this->error('恢复失败');
        }


    }

    /**
     **************李火生*******************
     * 手动清除音乐
     **************************************
     */
    public function set_music_null(){
        $music_counts =Db::name('music')->count();
        if($music_counts>0){
            $music_datas =Db::name('music')->select();
            foreach ($music_datas as $key=>$val){
                $music_ids[] =$val['id'];
            }
            $music_id = $music_ids[$music_counts-1];
            Db::name('music')->where('id',$music_id)->delete();
        }
    }






}
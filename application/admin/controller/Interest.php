<?php
/**
 * Created by PhpStorm.
 * User: 李火生
 * Date: 2018/9/17
 * Time: 10:17
 */
namespace  app\admin\controller;
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
use think\Request;
use util\Tree;

class Interest extends  Admin{
    public function  index(){

    }

    /**
     **************李火生*******************
     * @return \think\response\View
     * 按日配资利息管理
     **************************************
     */
    public function  day(){
            // 数据列表(按天配资)
            $data_list = Db::table("xh_stock_interest_day")->where('belong',0)->order('days','asc')->paginate();
            // 分页数据
            $page = $data_list->render();
            // 使用ZBuilder快速创建数据表格
            return ZBuilder::make('table')
                ->hideCheckbox()
                ->setPageTitle('按天配资利息管理列表') // 设置页面标题
                ->addColumns([ // 批量添加列
                    ['id', 'ID'],
                    ['interest', '利息率（%）'],
                    ['days', '配资周期（天）'],
                    ['createTime','时间']
                ])
                ->addColumn('belong', '配资类型', 'status', '', [0=>'按天', 1=>'按周',2=>'按月']) //type为数据库字段名, status为列的属性
                ->addColumns([
                    ['right_button', '操作', 'btn']
                ])
                ->addTopButton('add', ['href' => url('edits' )]) // 批量添加顶部按钮
                ->addRightButton('edit', ['href' => url('edits', ['id' => '__id__'])]) // 批量添加右侧按钮
                ->addRightButton('delete', ['href' => url('day_delete', ['id' => '__id__'])])
                ->setRowList($data_list) // 设置表格数据
                ->setPages($page) // 设置分页数据
                ->fetch(); // 渲染页面
    }

    /**
     **************李火生*******************
     * @return mixed
     * 按周配资管理
     **************************************
     */
    public function week(){
        // 数据列表(按周配资)
        $data_list = Db::table("xh_stock_interest_week")->where('belong',1)->order('weeks','asc')->paginate();
        // 分页数据
        $page = $data_list->render();
        // 使用ZBuilder快速创建数据表格
        return ZBuilder::make('table')
            ->hideCheckbox()
            ->setPageTitle('按周配资利息管理列表') // 设置页面标题
            ->addColumns([ // 批量添加列
                ['id', 'ID'],
                ['interest', '利息率（%）'],
                ['weeks', '配资周期（周）'],
                ['createTime','时间']
            ])
            ->addColumn('belong', '配资类型', 'status', '', [0=>'按天', 1=>'按周',2=>'按月']) //type为数据库字段名, status为列的属性
            ->addColumns([
                ['right_button', '操作', 'btn']
            ])
            ->addTopButton('add', ['href' => url('week_edit' )]) // 批量添加顶部按钮
            ->addRightButton('edit', ['href' => url('week_edit', ['id' => '__id__'])]) // 批量添加右侧按钮
            ->addRightButton('delete', ['href' => url('week_delete', ['id' => '__id__'])])
            ->setRowList($data_list) // 设置表格数据
            ->setPages($page) // 设置分页数据
            ->fetch(); // 渲染页面
    }


    /**
     **************李火生*******************
     * @return \think\response\View
     * 按月利息配资管理
     **************************************
     */
    public function  month(){
        // 数据列表(按月配资)
        $data_list = Db::table("xh_stock_interest_month")->where('belong',2)->order('months','asc')->paginate();
        // 分页数据
        $page = $data_list->render();
        // 使用ZBuilder快速创建数据表格
        return ZBuilder::make('table')
            ->hideCheckbox()
            ->setPageTitle('按月配资利息管理列表') // 设置页面标题
            ->addColumns([ // 批量添加列
                ['id', 'ID'],
                ['interest', '利息率（%）'],
                ['months', '配资周期（月）'],
                ['createTime','时间']
            ])
            ->addColumn('belong', '配资类型', 'status', '', [0=>'按天', 1=>'按周',2=>'按月']) //type为数据库字段名, status为列的属性
            ->addColumns([
                ['right_button', '操作', 'btn']
            ])
            ->addTopButton('add', ['href' => url('month_edit' )]) // 批量添加顶部按钮
            ->addRightButton('edit', ['href' => url('month_edit', ['id' => '__id__'])]) // 批量添加右侧按钮
            ->addRightButton('delete', ['href' => url('month_delete', ['id' => '__id__'])])
            ->setRowList($data_list) // 设置表格数据
            ->setPages($page) // 设置分页数据
            ->fetch(); // 渲染页面
    }


    /**
     **************李火生*******************
     * @return \think\response\View
     * 按天杠杆利息配资管理
     **************************************
     */
    public function  lever(){
        // 数据列表(杠杆倍率)
        $data_list = Db::table("xh_stock_interest_lever")->order('levers','asc')->paginate();
        // 分页数据
        $page = $data_list->render();
        // 使用ZBuilder快速创建数据表格
        return ZBuilder::make('table')
            ->hideCheckbox()
            ->setPageTitle('按天杠杆利息管理列表') // 设置页面标题
            ->addColumns([ // 批量添加列
                ['id', 'ID'],
                ['interest', '利息率（%）'],
                ['levers', '杠杆率（倍）'],
                ['createTime','时间']
            ])
            ->addColumns([
                ['right_button', '操作', 'btn']
            ])
            ->addTopButton('add', ['href' => url('lever_edit' )]) // 批量添加顶部按钮
            ->addRightButton('edit', ['href' => url('lever_edit', ['id' => '__id__'])]) // 批量添加右侧按钮
            ->addRightButton('delete', ['href' => url('lever_delete', ['id' => '__id__'])])
            ->setRowList($data_list) // 设置表格数据
            ->setPages($page) // 设置分页数据
            ->fetch(); // 渲染页面
    }

    /**
     **************李火生*******************
     * @return mixed
     * 按月杠杆利息管理
     **************************************
     */
    public function  lever_month(){
        // 数据列表(杠杆倍率)
        $data_list = Db::table("xh_stock_interest_lever_month")->order('levers','asc')->paginate();
        // 分页数据
        $page = $data_list->render();
        // 使用ZBuilder快速创建数据表格
        return ZBuilder::make('table')
            ->hideCheckbox()
            ->setPageTitle('按月杠杆利息管理列表') // 设置页面标题
            ->addColumns([ // 批量添加列
                ['id', 'ID'],
                ['interest', '利息率（%）'],
                ['levers', '杠杆率（倍）'],
                ['createTime','时间']
            ])
            ->addColumns([
                ['right_button', '操作', 'btn']
            ])
            ->addTopButton('add', ['href' => url('lever_month_edit' )]) // 批量添加顶部按钮
            ->addRightButton('edit', ['href' => url('lever_month_edit', ['id' => '__id__'])]) // 批量添加右侧按钮
            ->addRightButton('delete', ['href' => url('lever_month_delete', ['id' => '__id__'])])
            ->setRowList($data_list) // 设置表格数据
            ->setPages($page) // 设置分页数据
            ->fetch(); // 渲染页面
    }






    /**
     **************李火生*******************
     * @return mixed
     * 按天利息编辑
     **************************************
     */
    public function  edits($id = null){
        // 保存数据
        if ($this->request->isPost()) {
            $data = $this->request->post();
            $data['createTime'] = date("Y-m-d H:i:s");
            if($id > 0){
                $ret = Db::table("xh_stock_interest_day")->where("id=$id")->update($data);
            }else{
                $ret = Db::table("xh_stock_interest_day")->insertGetId($data);
            }
            if ($ret > 0) {
                // 记录行为
                action_log('edits', 'admin_role', $id, UID, $data['name']);
                return $this->success('编辑成功', url('day'));
            } else {
                return $this->error('编辑失败');
            }
        }
        // 获取数据
        if( $id > 0){
            $info= Db::table("xh_stock_interest_day")->where("id=$id")->find();
            $this->assign('info', $info);
        }

        // 使用ZBuilder快速创建表单
        $TypeArray =  array('0'=>'按天配资');
        $TypeDay =array(
            '1'=>'1天',
            '2'=>'2天',
            '3'=>'3天',
            '4'=>'4天',
            '5'=>'5天',
            '6'=>'6天',
            '7'=>'7天',
            '8'=>'8天',
            '9'=>'9天',
            '10'=>'10天',
            '11'=>'11天',
            '12'=>'12天',
            '13'=>'13天',
            '14'=>'14天',
            '15'=>'15天',
            '16'=>'16天',
            '17'=>'17天',
            '18'=>'18天',
            '19'=>'19天',
            '20'=>'20天',
            '21'=>'21天',
            '22'=>'22天',
            '23'=>'23天',
            '24'=>'24天',
            '25'=>'25天',
            '26'=>'26天',
            '27'=>'27天',
            '28'=>'28天',
            '29'=>'29天',
            '30'=>'30天',
            '31'=>'31天',
        );
        /*天数*/
        return ZBuilder::make('form')
            ->addSelect('belong', '选择类型', '请选择类型',$TypeArray)
            ->addSelect('days', '选择天数', '请选择类型',$TypeDay)
            ->addFormItems([
                ['text', 'interest', '利息（单位%）'],
            ])
            ->setFormData($info)
            ->fetch();
    }

    /**
     **************李火生*******************
     * @return mixed
     * 按周利息编辑
     **************************************
     */
    public function  week_edit($id = null){
        // 保存数据
        if ($this->request->isPost()) {
            $data = $this->request->post();
            $data['createTime'] = date("Y-m-d H:i:s");
            if($id > 0){
                $ret = Db::table("xh_stock_interest_week")->where("id=$id")->update($data);
            }else{
                $ret = Db::table("xh_stock_interest_week")->insertGetId($data);
            }
            if ($ret > 0) {
                // 记录行为
                action_log('week_edit', 'admin_role', $id, UID, $data['name']);
                return $this->success('编辑成功', url('week'));
            } else {
                return $this->error('编辑失败');
            }
        }
        // 获取数据
        if( $id > 0){
            $info= Db::table("xh_stock_interest_week")->where("id=$id")->find();
            $this->assign('info', $info);
        }


        // 使用ZBuilder快速创建表单
        $TypeArray =  array('1'=>'按周配资');
        $TypeDay =array(
            '1'=>'1周',
            '2'=>'2周',
            '3'=>'3周',
            '4'=>'4周',
            '5'=>'5周',
            '6'=>'6周',
            '7'=>'7周',
            '8'=>'8周',
            '9'=>'9周',
            '10'=>'10周',
        );
        /*天数*/
        return ZBuilder::make('form')
            ->addSelect('belong', '选择类型', '请选择类型',$TypeArray)
            ->addSelect('weeks', '选择周数', '请选择类型',$TypeDay)
            ->addFormItems([
                ['text', 'interest', '利息（单位%）'],
            ])
            ->setFormData($info)
            ->fetch();
    }

    /**
     **************李火生*******************
     * @return mixed
     * 按月利息编辑
     **************************************
     */
    public function  month_edit($id = null){
        if ($this->request->isPost()) {
            $data = $this->request->post();
            $data['createTime'] = date("Y-m-d H:i:s");
            if($id > 0){
                $ret = Db::table("xh_stock_interest_month")->where("id=$id")->update($data);
            }else{
                $ret = Db::table("xh_stock_interest_month")->insertGetId($data);
            }
            if ($ret > 0) {
                // 记录行为
                action_log('month_edit', 'admin_role', $id, UID, $data['name']);
                return $this->success('编辑成功', url('month'));
            } else {
                return $this->error('编辑失败');
            }
        }
        // 获取数据
        if( $id > 0){
            $info= Db::table("xh_stock_interest_month")->where("id=$id")->find();
            $this->assign('info', $info);
        }
        // 使用ZBuilder快速创建表单
        $TypeArray =  array('2'=>'按月配资');
        $TypeDay =array(
            '1'=>'1个月',
            '2'=>'2个月',
            '3'=>'3个月',
            '4'=>'4个月',
            '5'=>'5个月',
            '6'=>'6个月',
            '7'=>'7个月',
            '8'=>'8个月',
            '9'=>'9个月',
            '10'=>'10个月',
            '11'=>'11个月',
            '12'=>'12个月',
        );
        /*天数*/
        return ZBuilder::make('form')
            ->addSelect('belong', '选择类型', '请选择类型',$TypeArray)
            ->addSelect('months', '选择月数', '请选择类型',$TypeDay)
            ->addFormItems([
                ['text', 'interest', '利息（单位%）'],
            ])
            ->setFormData($info)
            ->fetch();
    }

    /**
     **************李火生*******************
     * @param null $id
     * 杠杆按天倍率利息编辑
     **************************************
     */
    public function lever_edit($id =null){

        if ($this->request->isPost()) {
            $data = $this->request->post();
            $data['createTime'] = date("Y-m-d H:i:s");
            if($id > 0){
                $ret = Db::table("xh_stock_interest_lever")->where("id=$id")->update($data);
            }else{
                $ret = Db::table("xh_stock_interest_lever")->insertGetId($data);
            }
            if ($ret > 0) {
                // 记录行为
                action_log('lever_edit', 'admin_role', $id, UID, $data['name']);
                return $this->success('编辑成功', url('lever'));
            } else {
                return $this->error('编辑失败');
            }
        }
        // 获取数据
        if( $id > 0){
            $info= Db::table("xh_stock_interest_lever")->where("id=$id")->find();
            $this->assign('info', $info);
        }
        // 使用ZBuilder快速创建表单
        $TypeDay =array(
            '1'=>'1倍',
            '2'=>'2倍',
            '3'=>'3倍',
            '4'=>'4倍',
            '5'=>'5倍',
            '6'=>'6倍',
            '7'=>'7倍',
            '8'=>'8倍',
            '9'=>'9倍',
            '10'=>'10倍',
        );
        /*天数*/
        return ZBuilder::make('form')
            ->addSelect('levers', '选择杠杆倍率', '请选择类型',$TypeDay)
            ->addFormItems([
                ['text', 'interest', '利息（单位%）'],
            ])
            ->setFormData($info)
            ->fetch();
    }


    /**
     **************李火生*******************
     * @param null $id
     * 杠杆按月倍率利息编辑
     **************************************
     */
    public function lever_month_edit($id =null){

        if ($this->request->isPost()) {
            $data = $this->request->post();
            $data['createTime'] = date("Y-m-d H:i:s");
            if($id > 0){
                $ret = Db::table("xh_stock_interest_lever_month")->where("id=$id")->update($data);
            }else{
                $ret = Db::table("xh_stock_interest_lever_month")->insertGetId($data);
            }
            if ($ret > 0) {
                // 记录行为
                action_log('lever_month_edit', 'admin_role', $id, UID, $data['name']);
                return $this->success('编辑成功', url('lever_month'));
            } else {
                return $this->error('编辑失败');
            }
        }
        // 获取数据
        if( $id > 0){
            $info= Db::table("xh_stock_interest_lever_month")->where("id=$id")->find();
            $this->assign('info', $info);
        }
        // 使用ZBuilder快速创建表单
        $TypeDay =array(
            '1'=>'1倍',
            '2'=>'2倍',
            '3'=>'3倍',
            '4'=>'4倍',
            '5'=>'5倍',
            '6'=>'6倍',
            '7'=>'7倍',
            '8'=>'8倍',
            '9'=>'9倍',
            '10'=>'10倍',
        );
        /**/
        return ZBuilder::make('form')
            ->addSelect('levers', '选择杠杆倍率', '请选择类型',$TypeDay)
            ->addFormItems([
                ['text', 'interest', '利息（单位%）'],
            ])
            ->setFormData($info)
            ->fetch();
    }





    /**
     **************李火生*******************
     * @param null $id
     * 按天配资删除
     **************************************
     */
    public function day_delete($id = null){
        if($id <= 0){
            return $this->error("id不正确");
        }
        $ret = Db::table("xh_stock_interest_day")->where("id = $id")->delete();
        if($ret > 0){
            return $this->success("删除成功");
        }
        return $this->error("删除失败");
    }

    /**
     **************李火生*******************
     * @param null $id
     * 按周配资删除
     **************************************
     */
    public function week_delete($id = null){
        if($id <= 0){
            return $this->error("id不正确");
        }
        $ret = Db::table("xh_stock_interest_week")->where("id = $id")->delete();
        if($ret > 0){
            return $this->success("删除成功");
        }
        return $this->error("删除失败");
    }

    /**
     **************李火生*******************
     * @param null $id
     * 按月配资删除
     **************************************
     */
    public function month_delete($id = null){
    if($id <= 0){
        return $this->error("id不正确");
    }
    $ret = Db::table("xh_stock_interest_month")->where("id = $id")->delete();
    if($ret > 0){
        return $this->success("删除成功");
    }
    return $this->error("删除失败");
}

    /**
     **************李火生*******************
     * @param null $id
     * 按天杠杆倍率删除
     **************************************
     */
    public function lever_delete($id = null){
        if($id <= 0){
            return $this->error("id不正确");
        }
        $ret = Db::table("xh_stock_interest_lever")->where("id = $id")->delete();
        if($ret > 0){
            return $this->success("删除成功");
        }
        return $this->error("删除失败");

    }

    /**
     **************李火生*******************
     * @param null $id
     * 按月杠杆倍率删除
     **************************************
     */
    public function lever_month_delete($id = null){
        if($id <= 0){
            return $this->error("id不正确");
        }
        $ret = Db::table("xh_stock_interest_lever_month")->where("id = $id")->delete();
        if($ret > 0){
            return $this->success("删除成功");
        }
        return $this->error("删除失败");

    }



}
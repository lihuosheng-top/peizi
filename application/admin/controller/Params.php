<?php
/**
 * Created by PhpStorm.
 * User: 李火生
 * Date: 2018/9/20
 * Time: 17:35
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

class  Params extends  Admin{
    /**
     **************李火生*******************
     * @return mixed
     * 有关费用的修改
     **************************************
     */
    public function  index()
    {
        $data_list = Db::table("xh_sys_params")->where('id','>','2')->limit(10)->paginate();
        // 分页数据
        $page = $data_list->render();
        // 使用ZBuilder快速创建数据表格
        return ZBuilder::make('table')
            ->hideCheckbox()
            ->setPageTitle('费用管理列表')// 设置页面标题
            ->addColumns([ // 批量添加列
                ['id', 'ID'],
                ['key', '关键字'],
                ['value','值'],
                ['remarks', '详情']
            ])
            ->addColumns([
                ['right_button', '操作', 'btn']
            ])
//            ->addTopButton('add', ['href' => url('edits')])// 批量添加顶部按钮
            ->addRightButton('edit', ['href' => url('edits', ['id' => '__id__'])])// 批量添加右侧按钮
            ->setRowList($data_list)// 设置表格数据
            ->setPages($page)// 设置分页数据
            ->fetch(); // 渲染页

    }

    /**
     **************李火生*******************
     * @param null $id
     * @return mixed|void
     * 编辑
     **************************************
     */
    public function  edits($id = null){
        // 保存数据
        if ($this->request->isPost()) {
            $data = $this->request->post();
            if($id > 0){
                $ret = Db::table("xh_sys_params")->where("id=$id")->update($data);
            }else{
                return $this->error('编辑失败，请重试');
            }
            if ($ret > 0) {
                // 记录行为
                action_log('edits', 'admin_role', $id, UID, $data['name']);
                return $this->success('编辑成功', url('index'));
            } else {
                return $this->error('编辑失败');
            }
        }
        // 获取数据
        if( $id > 0){
            $info= Db::table("xh_sys_params")->where("id=$id")->find();
            $this->assign('info', $info);
        }
        // 使用ZBuilder快速创建表单
        return ZBuilder::make('form')
            ->addFormItems([
                ['text', 'value', '具体费用'],
            ])
            ->addFormItems([
                ['text', 'remarks', '费用说明详情'],
            ])
            ->setFormData($info)
            ->fetch();
    }

}
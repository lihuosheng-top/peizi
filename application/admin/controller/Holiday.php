<?php
/**
 * Created by PhpStorm.
 * User: 李火生
 * Date: 2018/9/17
 * Time: 16:01
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

class  Holiday extends  Admin {

    /**
     **************李火生*******************
     * @return mixed
     * 节假日管理
     **************************************
     */
    public function  index(){
        $data_list = Db::table("xh_holiday")->order('day','desc')->paginate();
        // 分页数据
        $page = $data_list->render();
        // 使用ZBuilder快速创建数据表格
        return ZBuilder::make('table')
            ->hideCheckbox()
            ->setPageTitle('节假日管理列表') // 设置页面标题
            ->addColumns([ // 批量添加列
                ['id', 'ID'],
                ['day', '时间'],
                ['remarks','假日详情']
            ])
            ->addColumns([
                ['right_button', '操作', 'btn']
            ])
            ->addTopButton('add', ['href' => url('edits' )]) // 批量添加顶部按钮
            ->addRightButton('edit', ['href' => url('edits', ['id' => '__id__'])]) // 批量添加右侧按钮
            ->addRightButton('delete', ['href' => url('holiday_delete', ['id' => '__id__'])])
            ->setRowList($data_list) // 设置表格数据
            ->setPages($page) // 设置分页数据
            ->fetch(); // 渲染页面
    }

    /**
     **************李火生*******************
     * @param null $id
     * 节假日编辑
     **************************************
     */
    public function  edits($id = null){
        // 保存数据
        if ($this->request->isPost()) {
            $data = $this->request->post();
            $data['createTime'] = date("Y-m-d H:i:s");
            if($id > 0){
                $ret = Db::table("xh_holiday")->where("id=$id")->update($data);
            }else{
                $ret = Db::table("xh_holiday")->insertGetId($data);
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
            $info= Db::table("xh_holiday")->where("id=$id")->find();
            $this->assign('info', $info);
        }
        // 使用ZBuilder快速创建表单
        return ZBuilder::make('form')
            ->addFormItems([
                ['text', 'day', '具体时间'],
            ])
            ->addFormItems([
                ['text', 'remarks', '假日详情'],
            ])
            ->setFormData($info)
            ->fetch();
    }


    /**
     **************李火生*******************
     * @param null $id
     * 节假日删除
     **************************************
     */
    public function holiday_delete($id = null){
        if($id <= 0){
            return $this->error("id不正确");
        }
        $ret = Db::table("xh_holiday")->where("id = $id")->delete();
        if($ret > 0){
            return $this->success("删除成功");
        }
        return $this->error("删除失败");
    }

    /**
     **************李火生*******************
     * @return mixed|void
     * 一键生成添加输入某年某月的所有节假日
     **************************************
     */
    public function holiday_add()
    {
        if ($this->request->isPost()) {
            $data = $this->request->post();
            $datas =$data['day'];
            $data_one =substr($datas,0,4);
            $data_tow =substr($datas,4,2);
            $method = "POST";
            $headers = array();
            $url ='http://www.easybots.cn/api/holiday.php?m='.$data['day'];
            $curl = curl_init();
            curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $method);
            curl_setopt($curl, CURLOPT_URL, $url);
            curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($curl, CURLOPT_FAILONERROR, false);
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($curl, CURLOPT_HEADER, false);
            $res_str = curl_exec($curl);
//            $res =json_decode($res_str,false,1000,true);
//            $res = json_decode($res_str,false,1000 , JSON_BIGINT_AS_STRING);
            $res =json_decode($res_str,true);
            foreach ($res as $key=>$val){
                $arr[] =$val;
            }
           $res_data =array_keys($arr[0]);
            foreach ( $res_data as $keys=>$vals){
                $day_data =(string)$vals;
                $year_month_day =$data_one.'-'.$data_tow.'-'.$day_data;
                $data =['day'=>$year_month_day];
               $res = Db::name('holiday')->insert($data);
            }
            if($res){
                return $this->success('一键获取节假日成功', url('index'));
            }

        }
        return ZBuilder::make('form')
        ->addFormItems([
            ['text', 'day', '（年份月份）如：201810'],
        ])->fetch();
    }
}
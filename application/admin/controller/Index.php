<?php
// +----------------------------------------------------------------------
// | 海豚PHP框架 [ DolphinPHP ]
// +----------------------------------------------------------------------
// | 版权所有 2016~2017 河源市卓锐科技有限公司 [ http://www.zrthink.com ]
// +----------------------------------------------------------------------
// | 官方网站: http://dolphinphp.com
// +----------------------------------------------------------------------
// | 开源协议 ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------

namespace app\admin\controller;

use think\Cache;
use think\helper\Hash;
use think\Db;
use app\common\builder\ZBuilder;
use app\user\model\User as UserModel;
use think\Request;
use think\session;

/**
 * 后台默认控制器
 * @package app\admin\controller
 */
class Index extends Admin
{
    /**
     * 后台首页
     *
     */
    public function index()
    {
        $admin_pass = Db::name('admin_user')->where('id', 1)->value('password');

        if (UID == 1 && $admin_pass && Hash::check('admin', $admin_pass)) {
            $this->assign('default_pass', 1);
        }
        return $this->fetch();
    }

    /**
     * 清空系统缓存
     * @author 蔡伟明 <314013107@qq.com>
     */
    public function wipeCache()
    {
        if (!empty(config('wipe_cache_type'))) {
            foreach (config('wipe_cache_type') as $item) {
                if ($item == 'LOG_PATH') {
                    $dirs = (array) glob(constant($item) . '*');
                    foreach ($dirs as $dir) {
                        array_map('unlink', glob($dir . '/*.log'));
                    }
                    array_map('rmdir', $dirs);
                } else {
                    array_map('unlink', glob(constant($item) . '/*.*'));
                }
            }
            Cache::clear();
            $this->success('清空成功');
        } else {
            $this->error('请在系统设置中选择需要清除的缓存类型');
        }
    }

    /**
     * 个人设置
     * @author 蔡伟明 <314013107@qq.com>
     */
    public function profile()
    {
        // 保存数据
        if ($this->request->isPost()) {
            $data = $this->request->post();

            $data['nickname'] == '' && $this->error('昵称不能为空');
            $data['id'] = UID;

            // 如果没有填写密码，则不更新密码
            if ($data['password'] == '') {
                unset($data['password']);
            }

            $UserModel = new UserModel();
            if ($user = $UserModel->allowField(['nickname', 'email', 'password', 'mobile', 'avatar'])->update($data)) {
                // 记录行为
                action_log('user_edit', 'admin_user', UID, UID, get_nickname(UID));
                return $this->success('编辑成功');
            } else {
                return $this->error('编辑失败');
            }
        }

        // 获取数据
        $info = UserModel::where('id', UID)->field('password', true)->find();

        // 使用ZBuilder快速创建表单
        return ZBuilder::make('form')
            ->addFormItems([ // 批量添加表单项
                ['static', 'username', '用户名', '不可更改'],
                ['text', 'nickname', '昵称', '可以是中文'],
                ['text', 'email', '邮箱', ''],
                ['password', 'password', '密码', '必填，6-20位'],
                ['text', 'mobile', '手机号'],
                ['image', 'avatar', '头像']
            ])
            ->setFormData($info) // 设置表单数据
            ->fetch();
    }

    /**
     * 检查版本更新
     * @author 蔡伟明 <314013107@qq.com>
     * @return \think\response\Json
     */
    public function checkUpdate()
    {
        $params = config('dolphin');
        $params['domain']  = request()->domain();
        $params['website'] = config('web_site_title');
        $params['ip']      = $_SERVER['SERVER_ADDR'];
        $params['php_os']  = PHP_OS;
        $params['php_version'] = PHP_VERSION;
        $params['mysql_version'] = db()->query('select version() as version')[0]['version'];
        $params['server_software'] = $_SERVER['SERVER_SOFTWARE'];
        $params = http_build_query($params);

        $opts = [
            CURLOPT_TIMEOUT        => 20,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_URL            => config('dolphin.product_update'),
            CURLOPT_USERAGENT      => $_SERVER['HTTP_USER_AGENT'],
            CURLOPT_POST           => 1,
            CURLOPT_POSTFIELDS     => $params
        ];

        // 初始化并执行curl请求
        $ch = curl_init();
        curl_setopt_array($ch, $opts);
        $data  = curl_exec($ch);
        curl_close($ch);

        $result = json_decode($data, true);

        if ($result['code'] == 1) {
            return json([
                'update' => '<a class="badge badge-primary" href="http://www.dolphinphp.com/download" target="_blank">有新版本：'.$result["version"].'</a>',
                'auth'   => $result['auth']
            ]);
        } else {
            return json([
                'update' => '',
                'auth'   => $result['auth']
            ]);
        }
    }


    /**
     **************李火生*******************
     * @return mixed
     * 资金明细
     **************************************
     */
    public function rechargelist(){
    	// 获取筛选
        $map = $this->getMap();
        // 数据列表
        $data_list = Db::table(["xh_member_recharge"=>'a', "xh_member"=>'b'])
            ->field("a.*,  b.username, b.mobile")
            ->where($map)
            ->where("a.memberId = b.id and (a.status=1 || a.status=3) ")
            ->order("id desc")
            ->paginate(10);

        // 分页数据
        $page = $data_list->render();

        $list = array();
        foreach($data_list as $i=>$v){
            if($v['status'] == 1){
                $v['status'] = "网银充值";
            }else if($v['status'] == 3){
                $v['status'] = "手动充值";
            }
            $list[$i] = $v;
        }

        // 使用ZBuilder快速创建数据表格
        return ZBuilder::make('table')
            ->hideCheckbox()
            ->js('member')
            ->setPageTitle('入金明细') // 设置页面标题
                ->addTimeFilter('a.createTime')
            ->setSearch([ 'username' => '用户名', 'mobile' => '手机号']) // 设置搜索参数
            ->addColumns([ // 批量添加列
                ['id', 'ID'],
                ['username', '用户名'],
                ['mobile', '手机号'],
                ['amount', '金额(元)'],
                ['status', '类型'],
                ['no_order', '订单号'],
                ['createTime', '时间' ]
            ])
            ->setRowList($list) // 设置表格数据
            ->setPages($page) // 设置分页数据
            ->fetch(); // 渲染页面
    }

    /**
     **************李火生*******************
     * 如果判断有人申请充值和提现申请，后台给一个提示音
     **************************************
     */
    public function  informationHint(Request $request){
        if($request->isPost()){
         $res =  DB::name('music')->select();
         if(!empty($res)) {
             return $this->ajax_success('充值申请提示音返回成功',['czsq'=>6]);
         }

        }
    }

    /**
     **************李火生*******************
     * @param Request $request
     * 清空music数据
     **************************************
     */
    public function setInformationHint(Request $request){
        if($request->isPost()){
            if($_POST['name']=="wxcz"){
                $count =Db::name('music')->count();
                if($count>0){
                    $datas =Db::name('music')->select();
                    foreach ($datas as $key=>$val){
                        $ids[] =$val['id'];
                    }
                    $id = $ids[$count-1];
                    $res =Db::name('music')->where('id',$id)->delete();
                    if($res){
                        return $this->ajax_success('成功',['status'=>1]);
                    }
                }

            }

        }
    }





}
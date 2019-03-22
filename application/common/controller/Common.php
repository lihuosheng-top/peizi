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

namespace app\common\controller;

use think\Controller;
use think\Db;

/**
 * 项目公共控制器
 * @package app\common\controller
 */
class Common extends Controller
{
    /**
     * 获取筛选条件
     * @author 蔡伟明 <314013107@qq.com>
     * @alter 小乌 <82950492@qq.com>
     * @return array
     */
    final protected function getMap()
    {

        $search_field     = input('param.search_field/s', '');
        $keyword          = input('param.keyword/s', '');
        $filter           = input('param._filter/s', '');
        $filter_content   = input('param._filter_content/s', '');
        $filter_time      = input('param._filter_time/s', '');
        $filter_time_from = input('param._filter_time_from/s', '');
        $filter_time_to   = input('param._filter_time_to/s', '');

        $map = [];

        // 搜索框搜索
        if ($search_field != '' && $keyword !== '') {
            $map[$search_field] = ['like', "%$keyword%"];
        }

        // 时间段搜索
        if ($filter_time != '' && $filter_time_from != '' && $filter_time_to != '') {
            if ($filter_time_from == $filter_time_to) {
                $filter_time_from .= ' 00:00:00';
                $filter_time_to   .= ' 23:59:59';
            }
            $map[$filter_time] = ['between time', [$filter_time_from, $filter_time_to]];
        }

        // 下拉筛选
        if ($filter != '') {
            $filter         = array_filter(explode('|', $filter), 'strlen');
            $filter_content = array_filter(explode('|', $filter_content), 'strlen');
            foreach ($filter as $key => $item) {
                $map[$item] = ['in', $filter_content[$key]];
            }
        }
        return $map;
    }

    /**
     * 获取字段排序
     * @param string $extra_order 额外的排序字段
     * @param bool $before 额外排序字段是否前置
     * @author 蔡伟明 <314013107@qq.com>
     * @return string
     */
    final protected function getOrder($extra_order = '', $before = false)
    {
        $order = input('param._order/s', '');
        $by    = input('param._by/s', '');
        if ($order == '' || $by == '') {
            return $extra_order;
        }
        if ($extra_order == '') {
            return $order. ' '. $by;
        }
        if ($before) {
            return $extra_order. ',' .$order. ' '. $by;
        } else {
            return $order. ' '. $by . ',' . $extra_order;
        }
    }

    /**
     * 渲染插件模板
     * @param string $template 模板名称
     * @param string $suffix 模板后缀
     * @return mixed
     */
    final protected function pluginView($template = '', $suffix = '', $vars = [], $replace = [], $config = [])
    {
        $plugin_name = input('param.plugin_name');

        if ($plugin_name != '') {
            $plugin = $plugin_name;
            $action = 'index';
        } else {
            $plugin = input('param._plugin');
            $action = input('param._action');
        }
        $suffix = $suffix == '' ? 'html' : $suffix;
        $template = $template == '' ? $action : $template;
        $template_path = config('plugin_path'). "{$plugin}/view/{$template}.{$suffix}";
        return parent::fetch($template_path, $vars, $replace, $config);
    }

    /**
     **************李火生*******************
     * @param $code  股票代号
     * @return array
     * 股票数据
     **************************************
     */
    final protected function  getMarketValueBycode($code){
        $url = 'http://qt.gtimg.cn/q=';
        $market = Db::table("xh_shares")->field("market")->where('code',$code)->find();
        $sh = $market['market'];
        $code_url = $url . $sh . $code;
        $apistr = file_get_contents($code_url);
        $str = iconv("gb2312", "utf-8", $apistr);
        $long_str = strlen($str);
        $infomation = substr($str, 12, $long_str);//截取后的数据，字符串
        $info_arr = explode("~", $infomation);//对字符串进行切割成数组
        $time_url = 'http://image.sinajs.cn/newchart/min/n/'.$sh;//分时图片获取
        $day_url = 'http://image.sinajs.cn/newchart/daily/n/'.$sh;//查看日K线图
        $week_url = 'http://image.sinajs.cn/newchart/weekly/n/'.$sh;//查看周K线图
        $month_url = 'http://image.sinajs.cn/newchart/monthly/n/'.$sh;//查看月K线图
        $time_img = '.gif';
        $all_url_info = $time_url . $code . $time_img;//时K
        $day_url_info = $day_url . $code . $time_img; //日k
        $week_url_info = $week_url . $code . $time_img; //周k
        $month_url_info = $month_url . $code . $time_img; //月k
        $res =array(
            'info_arr'=>$info_arr,
            'time_url_info'=>$all_url_info,
            'day_url_info'=>$day_url_info,
            'week_url_info'=>$week_url_info,
            'month_url_info'=>$month_url_info,
        );
        return $res;
    }

    /**
     * @param $code
     * @return array
     * lihuosheng
     * 上证指数和深证指数
     * https://www.cnblogs.com/skating/p/6424342.html
     */
    final protected function  getMarketValueBycode_stock($code){
        if($code==000001){
            $url = 'http://qt.gtimg.cn/q=';
            $sh = 'sh';
            $code_url = $url . $sh . $code;
            $apistr = file_get_contents($code_url);
            $str = iconv("gb2312", "utf-8", $apistr);
            $long_str = strlen($str);
            $infomation = substr($str, 12, $long_str);//截取后的数据，字符串
            $info_arr = explode("~", $infomation);//对字符串进行切割成数组
            $time_url = 'http://image.sinajs.cn/newchart/min/n/'.$sh;//分时图片获取
            $day_url = 'http://image.sinajs.cn/newchart/daily/n/'.$sh;//查看日K线图
            $week_url = 'http://image.sinajs.cn/newchart/weekly/n/'.$sh;//查看周K线图
            $month_url = 'http://image.sinajs.cn/newchart/monthly/n/'.$sh;//查看月K线图
            $time_img = '.gif';
            $all_url_info = $time_url . $code . $time_img;//时K
            $day_url_info = $day_url . $code . $time_img; //日k
            $week_url_info = $week_url . $code . $time_img; //周k
            $month_url_info = $month_url . $code . $time_img; //月k

            $stock_all_number_url ="http://hq.sinajs.cn/list=s_sh".$code;
            $stock_num_api_str =file_get_contents($stock_all_number_url);
            $stock_num_str =iconv('gb2312',"utf-8",$stock_num_api_str);
            $stock_num_long_str = strlen($stock_num_str);
            $stock_num_infomation = substr($stock_num_str, 23, $stock_num_long_str);//截取后的数据，字符串
            $stock_num_info_arr = explode(",", $stock_num_infomation);//对字符串进行切割成数组
        }else{
            $url = 'http://qt.gtimg.cn/q=';
            $sh = 'sz';
            $code_url = $url . $sh . $code;
            $apistr = file_get_contents($code_url);
            $str = iconv("gb2312", "utf-8", $apistr);
            $long_str = strlen($str);
            $infomation = substr($str, 12, $long_str);//截取后的数据，字符串
            $info_arr = explode("~", $infomation);//对字符串进行切割成数组
            $time_url = 'http://image.sinajs.cn/newchart/min/n/'.$sh;//分时图片获取
            $day_url = 'http://image.sinajs.cn/newchart/daily/n/'.$sh;//查看日K线图
            $week_url = 'http://image.sinajs.cn/newchart/weekly/n/'.$sh;//查看周K线图
            $month_url = 'http://image.sinajs.cn/newchart/monthly/n/'.$sh;//查看月K线图
            $time_img = '.gif';
            $all_url_info = $time_url . $code . $time_img;//时K
            $day_url_info = $day_url . $code . $time_img; //日k
            $week_url_info = $week_url . $code . $time_img; //周k
            $month_url_info = $month_url . $code . $time_img; //月k

            $stock_all_number_url ="http://hq.sinajs.cn/list=s_".$sh.$code;
            $stock_num_api_str =file_get_contents($stock_all_number_url);
            $stock_num_str =iconv('gb2312',"utf-8",$stock_num_api_str);
            $stock_num_long_str = strlen($stock_num_str);
            $stock_num_infomation = substr($stock_num_str, 23, $stock_num_long_str);//截取后的数据，字符串
            $stock_num_info_arr = explode(",", $stock_num_infomation);//对字符串进行切割成数组
        }
        $res =array(
            'info_arr'=>$info_arr,
            'time_url_info'=>$all_url_info,
            'day_url_info'=>$day_url_info,
            'week_url_info'=>$week_url_info,
            'month_url_info'=>$month_url_info,
            'stock_num_info_arr'=>$stock_num_info_arr
        );
        return $res;
    }





    /**
     **************李火生*******************
     * @param $codes
     * @return array
     * 通过数组获取集体的所有数据
     **************************************
     */
    public  function  getMarketVlueByCodes($codes){
        /*以逗号切割*/
        $arrays =explode(",",$codes);
        foreach($arrays as $key=>$code){
            $url = 'http://qt.gtimg.cn/q=';
            $market = Db::table("xh_shares")->field("market")->where('code',$code)->find();
            $sh = $market['market'];
            $code_url = $url . $sh . $code;
            $apistr = file_get_contents($code_url);
            $str = iconv("gb2312", "utf-8", $apistr);
            $long_str = strlen($str);
            $infomation = substr($str, 12, $long_str);//截取后的数据，字符串
            $info_arr = explode("~", $infomation);//对字符串进行切割成数组
            $time_url = 'http://image.sinajs.cn/newchart/min/n/'.$sh;//分时图片获取
            $day_url = 'http://image.sinajs.cn/newchart/daily/n/'.$sh;//查看日K线图
            $time_img = '.gif';
//            $all_url_info = $time_url . $code . $time_img;//时K
//            $day_url_info = $day_url . $code . $time_img; //日k
            $res[] =array(
                'info_arr'=>$info_arr,
//                'time_url_info'=>$all_url_info,
//                'day_url_info'=>$day_url_info
            );

        }
        return $res;
    }

    /**
     **************李火生*******************
     * @param $code
     * @return array
     * 多个数组的code
     **************************************
     */
    final protected function  getMarketValueBycodess($code){
        $url = 'http://qt.gtimg.cn/q=';
        $market = Db::table("xh_shares")->field("market")->where('code',$code)->find();
        $sh = $market['market'];
        $code_url = $url . $sh . $code;
        $apistr = file_get_contents($code_url);
        $str = iconv("gb2312", "utf-8", $apistr);
        $long_str = strlen($str);
        $infomation = substr($str, 12, $long_str);//截取后的数据，字符串
        $info_arr = explode("~", $infomation);//对字符串进行切割成数组
        $time_url = 'http://image.sinajs.cn/newchart/min/n/'.$sh;//分时图片获取
        $day_url = 'http://image.sinajs.cn/newchart/daily/n/'.$sh;//查看日K线图
        $time_img = '.gif';
        $all_url_info = $time_url . $code . $time_img;//时K
        $day_url_info = $day_url . $code . $time_img; //日k
        $res =array(
            'info_arr'=>$info_arr,
//            'time_url_info'=>$all_url_info,
//            'day_url_info'=>$day_url_info
        );
        return $res;
    }


    /**
     **************李火生*******************
     * @param $code
     * @return array
     * 后台使用
     **************************************
     */
    public function  getMarketValueBycode_second($code){
        $url = 'http://qt.gtimg.cn/q=';
        $market = Db::table("xh_shares")->field("market")->where('code',$code)->find();
        $sh = $market['market'];
        $code_url = $url . $sh . $code;
        $apistr = file_get_contents($code_url);
        $str = iconv("gb2312", "utf-8", $apistr);
        $long_str = strlen($str);
        $infomation = substr($str, 12, $long_str);//截取后的数据，字符串
        $info_arr = explode("~", $infomation);//对字符串进行切割成数组
        $time_url = 'http://image.sinajs.cn/newchart/min/n/'.$sh;//分时图片获取
        $day_url = 'http://image.sinajs.cn/newchart/daily/n/'.$sh;//查看日K线图
        $time_img = '.gif';
        $all_url_info = $time_url . $code . $time_img;//时K
        $day_url_info = $day_url . $code . $time_img; //日k
        $res =array(
            'info_arr'=>$info_arr,
            'time_url_info'=>$all_url_info,
            'day_url_info'=>$day_url_info
        );
        return $res;
    }


    public function ajax_success($msg = '提交成功', $data = array())
    {
        $return = array('status' => '1');
        $return['info'] = $msg;
        $return['data'] = $data;
        exit(json_encode($return));
    }

    /**
     **************李火生*******************
     * 警戒线，触发止损线发送给用户的信息
     * @param $mobile @接收短信的手机号
     * @param $content  @接收短信的信息内容
     **************************************
     */
  public  function sendMobileToInformation($mobile,$content)
    {
        //接受验证码的手机号码
        $arr = json_decode($mobile, true);
        $mobiles = strlen($arr);
        if (isset($mobiles) != 11) {
            $this->error("手机号码不正确");
        }
        $url = "http://120.26.38.54:8000/interface/smssend.aspx";
        $post_data = array("account" => "peizi", "password" => "123qwe", "mobile" => $mobile, "content" => $content);
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
        $output = curl_exec($ch);
        curl_close($ch);
    }



}
<?php

namespace app\index\controller;

use app\common\controller\Frontend;
use app\common\model\HostLog;
use think\Lang;
use think\Cookie;
use think\Cache;

/**
 * Ajax异步请求接口
 * @internal
 */
class Ajax extends Frontend
{

    protected $noNeedLogin = ['lang'];
    protected $noNeedRight = ['*'];
    protected $layout = '';

    /**
     * 加载语言包
     */
    public function lang()
    {
        header('Content-Type: application/javascript');
        header("Cache-Control: public");
        header("Pragma: cache");

        $offset = 30 * 60 * 60 * 24; // 缓存一个月
        header("Expires: " . gmdate("D, d M Y H:i:s", time() + $offset) . " GMT");

        $controllername = input("controllername");
        $this->loadlang($controllername);
        //强制输出JSON Object
        $result = jsonp(Lang::get(), 200, [], ['json_encode_param' => JSON_FORCE_OBJECT | JSON_UNESCAPED_UNICODE]);
        return $result;
    }

    /**
     * 上传文件
     */
    public function upload()
    {
        return action('api/common/upload');
    }

    // 资源图表
    public function getResources()
    {
        $querytime = $this->request->param('querytime', null);
        $starttime = $this->request->param('starttime', null);
        $endtime = $this->request->param('endtime', null);
        $limit = ($querytime || $starttime || $endtime) ? 0 : ($this->request->param('init') ? 30 : 1);
        $host_id = Cookie::get('host_id_' . $this->auth->id);
        if (!$host_id) {
            $this->error(__('error'));
        }
        $where['host_id'] = $host_id;
        if ($starttime && $endtime) {
            $where['createtime'] = ['between time', [$starttime, $endtime]];
        }
        if ($querytime) {
            $where['createtime'] = ['between', [strtotime($querytime), strtotime($querytime) + 60 * 60 * 24]];
        }
        $list = \app\common\model\ResourcesLog::where($where)->order('createtime desc')->limit($limit)->field('id,host_id', true)->select();
        if ($list) {
            foreach($list as $value){
                $value->createtime = is_numeric($value->createtime) ? date('H:i:s', $value->createtime) : '';
            }
            $list  = array_column(array_reverse($list), null, 'createtime');
        }
        HostLog::setTitle(__('Resources total'));
        return json($list);
    }

    // 备案检查
    public function beian_check()
    {
        $domain = $this->request->post('domain');
        $refresh = $this->request->post('refresh', 0);
        if (!$domain) {
            $this->error(__('%s can not be empty', __('Domain')));
        }
        if ($refresh) {
            Cache::rm('domain_check_' . md5($domain));
        }

        $check = Cache::remember('domain_check_' . md5($domain), function ($domain) {
            return \app\common\library\Common::beian_check($domain);
        });

        if (!$check) {
            $this->error(__('%s domain cannot be beian', $domain));
        } else {
            $this->success(__('Success'));
        }
    }
}

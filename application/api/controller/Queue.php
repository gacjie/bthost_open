<?php

namespace app\api\controller;

use app\common\controller\Api;
use app\common\library\Btaction;
use app\common\library\Email;
use app\common\library\Ftmsg;
use app\common\library\Message;
use Exception;
use think\Config;
use think\Debug;
use fast\Http;

/**
 * 计划任务监控接口
 */
class Queue extends Api
{
    protected $noNeedLogin = ['*'];
    protected $noNeedRight = ['*'];
    protected $token       = '';
    protected $_error      = '';
    protected $is_index    = '';

    protected $successNum = [];

    protected $errorNum = [];

    protected $limit = 10;
    protected $ftmsg = 0;
    protected $email = 0;
    protected $checkTime = 20;


    public function _initialize()
    {
        parent::_initialize();
        $this->token = $this->request->request('token');
        if ($this->token) {
            if (Config::get('site.queue_key') !== $this->token) {
                $this->error('接口密钥错误');
            }
        } else {
            $this->error('token为空');
        }
        $this->model = model('Queue');
    }

    /**
     * 计划任务队列
     *
     * @ApiTitle    计划任务队列
     * @ApiSummary  计划任务队列
     * @ApiMethod   (GET)
     * @ApiParams   (name="token", type="string", required=true, description="计划任务监控密钥")
     */
    public function index()
    {
        set_time_limit(0);
        ini_set('max_execution_time', 300);
        ini_set('memory_limit', '128M');
        $this->is_index = 1;
        // 列出所有有效任务
        $queList = $this->model->where(['status' => 'normal'])->order('weigh desc')->select();
        if (!$queList) {
            $this->error('无有效任务');
        }
        $n = [];
        // 遍历任务执行
        foreach ($queList as $key => $value) {
            Debug::remark('begin');
            // 计算判断在有效执行时间内的
            try {
                $e_runtime = $value['runtime'] + $value['executetime'];
                if (time() >= $e_runtime) {
                    // 获取任务配置
                    if ($value->configgroup && json_decode($value->configgroup, 1)) {
                        $configA = array_column(json_decode($value->configgroup, 1), 'value', 'key');
                    }
                    $this->limit     = isset($configA['limit']) ? $configA['limit'] : 10;
                    $this->ftmsg      = isset($configA['ftmsg']) ? $configA['ftmsg'] : 0;
                    $this->email      = isset($configA['email']) ? $configA['email'] : 0;
                    $this->checkTime = isset($configA['checkTime']) ? $configA['checkTime'] : 20;


                    // 开始执行指定方法
                    switch ($value['function']) {
                        case 'email':
                            $s                          = $this->emailTask();
                            $s ? $n[$value['function']] = $s : '';
                            break;
                        case 'btresource':
                            $s                          = $this->btResourceTask($this->limit, $this->checkTime);
                            $s ? $n[$value['function']] = $s : '';
                            break;
                        case 'hosttask':
                            $s                          = $this->hostTask();
                            $s ? $n[$value['function']] = $s : '';
                            break;
                        case 'hostclear':
                            $s                          = $this->hostClear();
                            $s ? $n[$value['function']] = $s : '';
                            break;
                        // case 'updatecheck':
                        //     $s                          = $this->updateCheck();
                        //     $s ? $n[$value['function']] = $s : '';
                        //     break;
                        default:
                            $n[$value['function']] = 'null';
                            break;
                    }
                    // 记录任务最后执行时间
                    $this->runtimeUpdate($value);
                } else {
                    // $n[$value['function']] = 'continue';
                    // 当前方法跳过
                }
            } catch (Exception $e) {
                $n[$value['function']] = $e->getMessage();
            }
            Debug::remark('end');
        }
        if ($n) {
            // 记录执行结果及执行时间
            $this->queuelog($n);
        }

        $this->success('执行完成', $n);
    }

    // 检查更新
    public function updateCheck(){
        $total = [
            'user'=>model('User')->count(),
            'host'=>model('Host')->count(),
            'sql'=>model('Sql')->count(),
            'ftp'=>model('Ftp')->count(),
            'domain'=>model('Domain')->count(),
            'hostlog'=>model('HostLog')->count(),
            'apilog'=>model('ApiLog')->count(),
            'domainlist'=>model('Domainlist')->count(),
            'hostresetlog'=>model('HostresetLog')->count(),
        ];
        $url = Config::get('bty.api_url') . '/bthost_update_check.html';
        $bt = new Btaction();
        $ip = $bt->getIp();
        $data = [
            'obj' => Config::get('bty.APP_NAME'),
            'version' => Config::get('bty.version'),
            'is_beta' => Config::get('bty.is_beta'),
            'domain' => $ip,
            'rsa' => 1,
            'total'=>base64_encode(json_encode($total)),
        ];
        $curl = http::post($url, $data);
        $result = json_decode($curl,1);
        if($result  && isset($result ['code']) && $result ['code']==1 && isset($result ['data']) && $result ['data']){
            $title = '[btHost检测到新版本]';
            $content = '';
            $content.= '当前版本：'.$result ['data']['version'];
            $content.= "\n\n更新版本：".$result ['data']['newversion'];
            if(isset($result ['data']['upgradetext'])){
                $upgradetext = str_replace("\n","\n\n",$result ['data']['upgradetext']);
            }
            $content.= "\n\n更新内容：\n\n".$upgradetext;
            $content.= "\n\nTime：".date('Y-m-d H:i:s',time());
            // 方糖通知
            if (Config::get('site.ftqq_sckey') && $this->ftmsg) {
                $this->ft_msg($title,$content);
            }
            // 邮件通知
            if (Config::get('site.email') && $this->email) {
                $content = str_replace("\n\n","<br>",$content);
                $content.='<style>.label{display: inline;padding: .2em .6em .3em;font-size: 75%;font-weight: bold;line-height: 1;color: #fff;text-align: center;white-space: nowrap;vertical-align: baseline;border-radius: .25em;}.label-success{background-color: #18bc9c;}.label-warning{background-color: #f39c12;}.label-info{background-color: #3498db;}</style>';
                $this->email_msg($title,$content);
            }
            return $this->is_index ? [] : $this->success('发现更新！', []);
        }else{
            return $this->is_index ? '' : $this->success('当前程序没有发布更新');
        }
    }

    /**
     * 邮件队列
     *
     * @ApiTitle    邮件队列
     * @ApiSummary  邮件队列
     * @ApiMethod   (GET)
     * @ApiParams   (name="token", type="string", required=true, description="计划任务监控密钥")
     * @ApiParams   (name="limit", type="int", required=false, description="一次发送多少条", sample="10")
     */
    public function emailTask()
    {
        set_time_limit(0);
        ini_set('max_execution_time', 300);
        ini_set('memory_limit', '128M');
        $successNum = [];
        $errorNum   = [];

        // 邮件队列暂不开通
        return $this->is_index ? '' : $this->success('无有效任务');

        $list = model('Email')->where(['status' => '0'])->limit($this->limit)->select();
        if ($list) {
            $obj = \app\common\library\Email::instance();
            foreach ($list as $key => $value) {
                $result = $obj
                    ->to($value->email)
                    ->subject($value->title)
                    ->message($value->content)
                    ->send();
                if ($result) {
                    model('Email')->where('id', $value->id)->update(['status' => 1]);

                    $successNum[][$value->email] = '发送成功';
                } else {
                    model('Email')->where('id', $value->id)->update(['status' => 2]);

                    $errorNum[][$value->email] = '发送失败' . $obj->getError();
                }
            }
            return $this->is_index ? [$successNum, $errorNum] : $this->success('请求成功', [$successNum, $errorNum]);
        } else {
            return $this->is_index ? '' : $this->success('无有效任务');
        }
    }

    /**
     * 宝塔资源监控
     *
     * @param integer $limit
     * @param integer $checkTime
     * @param integer $tz_user
     * @param integer $tz_admin
     * @param integer $ftqq
     * @return void
     */
    public function btResourceTask($limit = 20, $checkTime = 0)
    {
        set_time_limit(0);
        ini_set('max_execution_time', 300);
        ini_set('memory_limit', '128M');
        $successNum = [];
        $errorNum   = [];
        //额定时间分钟数
        $time      = time() - 60 * $checkTime;

        $hostList = model('Host')
            ->alias('h')
            ->where('h.check_time', '<', $time)
            ->join('sql s', 's.vhost_id = h.id', 'LEFT')
            ->limit($limit)
            ->field('h.*,s.username as sql_name,s.id as sql_id')
            ->select();

        if ($hostList) {
            foreach ($hostList as $key => $value) {
                // 写入检查时间，防止后面报错导致重复检查

                $value->check_time = time();
                $value->save();
                // 连接宝塔
                $bt            = new Btaction();
                $bt->bt_id     = $value->bt_id;
                $bt->bt_name   = $value->bt_name;
                // 测试连接
                if (!$bt->test()) {
                    $errorNum[][$value->bt_name] = $bt->_error;
                    continue;
                }
                // 查找站点是否存在
                $Websites = $bt->getSiteInfo($value->bt_name);
                if (!$Websites) {
                    $errorNum[][$value->bt_name] = $bt->_error;
                    continue;
                }

                $v = $bt->resource($value->sql_name, 1);

                if (isset($v['sql']) && isset($v['site']) && isset($v['flow'])) {
                    $getSqlSizes = is_numeric($v['sql']) ? bytes2mb($v['sql']) : 0;
                    $getWebSizes = is_numeric($v['site']) ? bytes2mb($v['site']) : 0;
                    $total_size  = is_numeric($v['flow']) ? bytes2mb($v['flow']) : 0;
                } else {
                    $errorNum[][$value->bt_name] = $v;
                    continue;
                }

                $value->site_size = $getWebSizes;
                $value->flow_size = $total_size;
                $value->sql_size = $getSqlSizes;
                $value->save();

                // 记录站点资源日志入库
                \app\common\model\ResourcesLog::create([
                    'host_id' => $value->id,
                    'site_size' => $getWebSizes,
                    'flow_size' => $total_size,
                    'sql_size' => $getSqlSizes,
                ]);
                // Db::startTrans();
                $excess = 0;
                if (($getSqlSizes > $value->sql_max && $value->sql_max != '0') || ($getWebSizes > $value->site_max && $value->site_max != '0') || ($total_size > $value->flow_max && $value->flow_max != '0')) {
                    // 超出停止
                    $value->status = 'excess';
                    $value->save();
                    $excess = 1;
                } elseif ($value->status == 'excess') {
                    // 恢复主机
                    if ($value->endtime > time()) {
                        $s = 'normal';
                    } else {
                        $s = 'expired';
                    }
                    $value->status = $s;
                    $value->save();
                }

                $successNum[][$value->bt_name] = ['sql_size' => $getSqlSizes, 'site_size' => $getWebSizes, 'flow_size' => $total_size, 'is_excess' => $excess];
            }
            try {
                $this->message($successNum, $errorNum, 'btResourceTask');
            } catch (\Throwable $th) {
                $this->queuelog(['message' => $th->getMessage()]);
            }

            return $this->is_index ? [$successNum, $errorNum] : $this->success('请求成功', [$successNum, $errorNum]);
        } else {
            return $this->is_index ? '' : $this->success('无有效任务');
        }
    }


    /**
     * 主机过期监控
     *
     * 
     * @return void
     */
    public function hostTask()
    {
        $successNum = [];
        $errorNum   = [];
        $hostList = model('Host')->where('endtime', '<=', time())->where('status', '<>', 'expired')->select();
        if ($hostList) {
            foreach ($hostList as $key => $value) {
                $bt            = new Btaction();
                $bt->bt_id     = $value->bt_id;
                $bt->bt_name   = $value->bt_name;
                // 测试连接
                if (!$bt->test()) {
                    $errorNum[][$value->bt_name] = $bt->_error;
                    continue;
                }

                // 查找站点是否存在
                $Websites = $bt->getSiteInfo($value->bt_name);
                if (!$Websites) {
                    $errorNum[][$value->bt_name] = $bt->_error;
                    continue;
                }

                switch (Config::get('site.expire_action')) {
                    case 'recycle':
                        $value->status = 'expired';
                        $value->deletetime = time();
                        $value->save();
                        break;
                    case 'delete':
                        $del = $bt->siteDelete($value->bt_id, $value->bt_name);
                        if (!$del) {
                            $errorNum[][$value->bt_name] = isset($bt->_error) ? $bt->_error : '删除失败';
                            break;
                        }
                        $value->delete(true);
                        break;
                    default:

                        break;
                }

                $successNum[][$value->bt_name] = 'success';
            }
            try {
                $this->message($successNum, $errorNum, 'hostTask');
            } catch (\Exception $th) {
                $this->queuelog(['message' => $th->getMessage()]);
            }

            return $this->is_index ? [$successNum, $errorNum] : $this->success('请求成功', [$successNum, $errorNum]);
        } else {
            return $this->is_index ? '' : $this->success('无有效任务');
        }
    }

    /**
     * 回收站清理
     *
     * @return void
     */
    public function hostClear()
    {
        $successNum = [];
        $errorNum   = [];

        //达到指定天数后删除站点并清除所有数据
        $time      = time() - 60 * 60  * 24 * Config('site.recycle_delete');
        $hostList = model('Host')::onlyTrashed()->where('deletetime', '<=', $time)->select();
        if ($hostList) {
            foreach ($hostList as $key => $value) {
                $bt = new Btaction();
                $bt->bt_id = $value->bt_id;
                $bt->bt_name = $value->bt_name;
                $del = $bt->siteDelete($value->bt_id, $value->bt_name);
                if (!$del) {
                    $errorNum[][$value->bt_name] = isset($bt->_error) ? $bt->_error : '删除失败';
                    break;
                }

                $value->delete(true);
                $successNum[][$value->bt_name] = 'success';
            }
            try {
                $this->message($successNum, $errorNum, 'hostClear');
            } catch (\Exception $th) {
                $this->queuelog(['message' => $th->getMessage()]);
            }

            return $this->is_index ? [$successNum, $errorNum] : $this->success('请求成功', [$successNum, $errorNum]);
        } else {
            return $this->is_index ? '' : $this->success('无有效任务');
        }
    }

    // 站长通知
    private function message($successNum, $errorNum, $type)
    {
        switch ($type) {
            case 'hostClear':
                $title = '回收站清理任务完成';
                break;
            case 'hostTask':
                $title = '主机过期检查任务完成';
                break;
            case 'btResourceTask':
                $title = '主机资源检查任务完成';
                break;
            default:
                $title = '其他任务执行完成';
                break;
        }
        $content = "执行结果清单如下：<br>\n\n成功:" . arr_to_str($successNum) . "<br>\n\n失败:" . arr_to_str($errorNum) . "<br>\n\nTime:" . date("Y-m-d H:i:s", time());
        // 邮件通知
        if (Config::get('site.email') && $this->email) {
            $this->email_msg($title,$content);
        }
        // 方糖通知
        if (Config::get('site.ftqq_sckey') && $this->ftmsg) {
            $this->ft_msg($title,$content);
        }
    }

    private function email_msg($title,$content){
        $email = new Email();
        $email->to(Config::get('site.email'))
            ->subject($title)
            ->message($content);
        $message = new Message($email);
        $result = $message->send();
        if (!$result) {
            $this->queuelog(['message' => $message->getError()]);
        }
        return true;
    }

    private function ft_msg($title,$content){
        
        $ft = new Ftmsg(Config::get('site.ftqq_sckey'));
        $ft->setTitle($title);
        $ft->setMessage($content);
        $ft->sslVerify();
        $message = new Message($ft);
        $result = $message->send();
        if (!$result) {
            $this->queuelog(['message' => $message->getError()]);
        }
        return true;
    }

    /**
     * 最后运行时间记录
     *
     * @param [type] $value     任务类
     * @return void
     */
    private function runtimeUpdate($value)
    {
        $this->model->update([
            'runtime' => time(),
            'id'      => $value->id,
        ]);
    }

    /**
     * 记录执行日志
     *
     * @param [type] $n     日志数组
     * @return void
     */
    private function queuelog($n)
    {
        if (!$n) {
            return false;
        }
        model('queueLog')->data([
            'logs'      => json_encode($n, JSON_UNESCAPED_UNICODE),
            'call_time' => Debug::getRangeTime('begin', 'end'),
        ])->save();
        return true;
    }
}

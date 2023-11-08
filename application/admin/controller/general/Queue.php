<?php

namespace app\admin\controller\general;

use app\common\model\Queue as queModel;
use app\common\controller\Backend;
use app\common\library\Btaction;
use think\Config;

/**
 * 计划任务
 *
 * @icon fa fa-user
 */
class Queue extends Backend
{
    protected $model = null;
    protected $noNeedRight = '';
    protected $multiFields = 'status';

    public $queUrl = '';

    public $queName = 'btHost计划任务';

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new queModel();
        $this->view->assign('typedata', \app\common\model\Queue::getTypeList());
        $this->queUrl = request()->domain() . '/api/queue/index?token=' . Config::get('site.queue_key');
        $this->view->assign('queUrl', $this->queUrl);
    }

    // 获取宝塔面板任务执行日志
    public function getLogs()
    {
        $bt = new Btaction();

        $logsInfo = $bt->get_cron($this->queName);
        if (!$logsInfo) {
            $this->error('任务未添加，请先添加任务', null);
        }
        if (!isset($logsInfo['id'])) {
            $this->error('任务获取失败', null);
        }
        $log_id = $logsInfo['id'];
        $logs = $bt->btPanel->GetLogs($log_id);
        if (!$logs) {
            $this->error($bt->btPanel->_error, null);
        }
        $this->view->assign('logs', $logs);
        return $this->view->fetch('queuelogs');
    }

    public function detail($limit = 30)
    {
        $row = model('queueLog')->order('id desc')->paginate($limit)->each(function ($item, $key) {
            $item['logs'] = $item->logs . "\r\n";
            return $item;
        });
        if (!$row) {
            $this->error(__('No Results were found'));
        }
        $this->view->assign('count', model('queueLog')->count());
        $this->view->assign('row', $row);
        return $this->view->fetch('queuelog');
    }

    // 清空日志
    public function quelogclear()
    {
        model('QueueLog')->where('id', '>', 0)->delete(true);
        try {
            // 删除计划任务日志
            $bt = new Btaction();
            // 判断任务是否已存在
            $get_cron = $bt->get_cron($this->queName);

            if ($get_cron && isset($get_cron['id'])) {
                $bt->btPanel->DelLogs($get_cron['id']);
            }
        } catch (\Exception $e) {
        }
        $this->success('已清空');
    }

    // 快速监控
    public function deployment()
    {

        $bt = new Btaction();
        $type = $this->request->post('type', 'url');
        // 判断任务是否已存在
        $get_cron = $bt->get_cron($this->queName);

        if ($get_cron && isset($get_cron['id'])) {
            // 删除任务并重新添加
            $bt->btPanel->DelCrontab($get_cron['id']);
        }
        $data = [
            'name' => $this->queName,
            'type' => 'minute-n',
            'where1' => 1,
        ];
        if ($type == 'url') {
            $url = $this->queUrl;
            $data['urladdress'] = $url;
            $data['sType'] = 'toUrl';
        } elseif ($type == 'cron') {
            $cron = "php " . ROOT_PATH . "think cron";
            $data['sBody'] = $cron;
            $data['sType'] = 'toShell';
        }

        $set = $bt->btPanel->AddCrontab($data);
        if (!$set) {
            $this->error($bt->btPanel->_error);
        }
        $this->success('部署成功');
    }

    public function edit($ids = null)
    {
        //设置过滤方法
        $this->request->filter([]);
        return parent::edit($ids);
    }

    public function queue_url()
    {
        $cron = "*/1 * * * * php " . ROOT_PATH . "think cron";
        $this->view->assign('cron', $cron);
        return $this->view->fetch('queue_url');
    }
}

<?php


namespace app\admin\controller;


use app\common\controller\Backend;
use app\common\library\Btaction;
use think\Config;

class Domainbeian extends Backend
{
    /**
     * Domainlist模型对象
     * @var \app\common\model\DomainBeian
     */
    protected $model = null;

    protected $relationSearch = true;
    protected $searchFields = ['id', 'domain', 'vhost.bt_name'];

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\common\model\DomainBeian;
    }

    /**
     * 查看
     */
    public function index()
    {
        //设置过滤方法
        $this->request->filter(['strip_tags']);
        if ($this->request->isAjax()) {
            //如果发送的来源是Selectpage，则转发到Selectpage
            if ($this->request->request('keyField')) {
                return $this->selectpage();
            }
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();
            $total = $this->model
                ->with('vhost')
                ->where($where)
                ->order($sort, $order)
                ->count();
            $list = $this->model
                ->with('vhost')
                ->where($where)
                ->order($sort, $order)
                ->limit($offset, $limit)
                ->select();


            $result = array("total" => $total, "rows" => $list);

            return json($result);
        }
        return $this->view->fetch();
    }

    // 域名审核
    public function audit($ids)
    {
        $domainInfo = $info = $this->model::get($ids);
        if (!$info) {
            $this->error('域名不存在');
        }
        $hostInfo = model('Host')::get($info->vhost_id);
        if (!$hostInfo) {
            $this->error('主机不存在');
        }
        // 先删除
        $domainInfo->status = 'success';
        $domainInfo->save();
        // \app\common\model\DomainBeian::notbeian_domain_del($info);
        // 后绑定
        // \app\common\model\DomainBeian::notbeian_audit($domainInfo);

        // 添加到域名列表数据库中
        $data = [
            'vhost_id'    => $domainInfo->vhost_id,
            'domain'      => $domainInfo->domain,
            'dir'         => $domainInfo->dir,
            'status'       => 1,
        ];
        model('Domainlist')::create($data);

        $this->success('已通过审核');
    }

    // 一键创建未备案引导站点
    public function create_notbeian_site()
    {
        // 检测是否已初始化
        if (Config::get('beian_siteinfo.bt_id') && Config::get('beian_siteinfo.bt_name')) {
            $this->success(__('备案引导模块已完成初始化'));
        }

        // 一键创建未备案引导的站点
        $bt = new Btaction();
        $hostInfo = [
            'domains' => 'default.notbeian.com',
            'phpver' => '00',
            'ps' => '备案中转站，请勿删除'
        ];
        $hostSetInfo = $bt->setInfo([], $hostInfo);
        $createHost = $bt->btBuild($hostSetInfo);
        if (!$createHost) {
            $this->error($bt->getError());
        }
        $bt_id = $createHost['siteId'];
        $bt_name = $hostSetInfo['bt_name'];

        // 创建一个静态的站点，不开通ftp、sql
        // 修改网站index.html内容为指定的未备案拦截html
        $rootPath = $hostSetInfo['path'];
        $defaultFile = $rootPath . '/index.html';
        // 默认未备案模版
        $notbeian_file = APP_PATH . 'common' . DS . 'view' . DS . 'tpl' . DS . 'notbeian.tpl';
        $content = file_get_contents($notbeian_file);
        $savefile = $bt->btPanel->SaveFileBodys($content, $defaultFile);
        if ($savefile && isset($savefile['status']) && $savefile['status'] == true) {
        } elseif (isset($savefile['msg'])) {
            $this->error(__($savefile['msg']));
        } else {
            $this->error(__('信息保存失败'));
        }

        // 数据库里或配置文件中记录该站点信息：站点ID、站点名
        $config_file = APP_PATH . 'extra' . DS . 'beian_siteinfo.php';
        $set = setconfig($config_file, ['bt_id', 'bt_name'], [$bt_id, $bt_name]);
        if (!$set) {
            $this->error(__('信息保存失败'));
        }
        $this->success(__('初始化成功'));
        // 更多功能：
        // 1. 后台一键修改未备案站点html内容
        // 2. 列出所有绑定到该站点的域名（未备案域名列表）
        // 3. 手动审核（还原到原站点进行绑定、删除未备案站点、数据库中绑定的域名）

    }
}

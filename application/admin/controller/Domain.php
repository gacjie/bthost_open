<?php

namespace app\admin\controller;

use app\common\controller\Backend;
use dnspod\Dnspod;
use think\Config;

/**
 * 域名
 *
 * @icon fa fa-circle-o
 */
class Domain extends Backend
{
    
    /**
     * Domain模型对象
     * @var \app\admin\model\Domain
     */
    protected $model = null;

    protected $relationSearch = true;
    protected $searchFields = ['id','domain','domainpools.name'];

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\Domain;
        $this->view->assign("dnspodList", $this->model->getDnspodList());
        $this->view->assign("statusList", $this->model->getStatusList());
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
                ->with('domainpools')
                ->where($where)
                ->order($sort, $order)
                ->count();
            $list = $this->model
                ->with('domainpools')
                ->where($where)
                ->order($sort, $order)
                ->limit($offset, $limit)
                ->select();
            
                
            $result = array("total" => $total, "rows" => $list);

            return json($result);
        }
        return $this->view->fetch();
    }

    public function import()
    {
        parent::import();
    }

    /**
     * 默认生成的控制器所继承的父类中有index/add/edit/del/multi五个基础方法、destroy/restore/recyclebin三个回收站方法
     * 因此在当前控制器中可不用编写增删改查的代码,除非需要自己控制这部分逻辑
     * 需要将application/admin/library/traits/Backend.php中对应的方法复制到当前控制器,然后进行修改
     */

    
    public function add($ids = null){
        if($this->request->isPost()){
            $params = $this->request->post('row/a');
            $domain = $params['domain'];
            $dnspod = $params['dnspod'];
            $domainpools_id = $params['domainpools_id'];
            $dnspod_id = 0;
            // 判断重复
            $find = $this->model::get(['domain'=>$domain]);
            if($find){
                $this->error('域名已存在，请勿重复添加');
            }
            if($dnspod){
                // 是否存在dnspod中
                // 可以通过搜索来自动判断
                // 获取id和token
                $id = Config('dnspod.id');
                $token = Config('dnspod.token');
                $dns = new Dnspod($id,decode($token));
                
                // 检索是否已经存在该域名
                $search = $dns->domain_info('',$domain);
                if(!$search){
                    $ext = 0;
                }else{
                    $ext = 1;
                }
                if($ext){
                    $info = $dns->domain_info('',$domain);
                }else{
                    $info = $dns->domain_Create($domain);
                }
                
                if(!$info){
                    $this->error($dns->msg);
                }
                if(!isset($info['status']['code'])||$info['status']['code']!=1){
                    $this->error($info['status']['message']);
                }
                $d = $ext?$info['domain']['name']:$info['domain']['domain'];
                $doamin_id = $ext?$info['domain']['id']:$info['domain']['id'];

                $dnspod_id = $doamin_id;
            }

            $this->model::create([
                'domain'=>$domain,
                'domainpools_id'=>$domainpools_id,
                'dnspod'=>$dnspod,
                'dnspod_id'=>$dnspod_id,
                'domain'=>$domain,
            ]);
            $this->success('添加成功');
        }
        return parent::add($ids);
    }
    
    // 配置dnspod修改
    public function config(){
        \app\common\library\Common::clear_cache();
        if($this->request->isPost()){
            $params = $this->request->post('row/a');
            if(!$params['id']){
                $this->error('配置不能为空');
            }
            $file = APP_PATH . DS . 'extra' . DS . 'dnspod.php';
            $msg = '';
            // 修改配置
            $get = $set = [];
            if ($params['token'] || $params['id']) {
                try {
                    if ($params['id']) {
                        $get[] = 'id';
                        $set[] = $params['id'];
                    }
                    if ($params['token']) {
                        $get[] = 'token';
                        $set[] = encode($params['token']);
                    }
                    $set = setconfig(
                        $file,
                        $get,
                        $set
                    );
                }catch(\Exception $e){
                    $msg = '配置文件修改失败' . $e->getMessage();
                }
                if ($msg) {
                    $this->error($msg);
                }
            }
            $this->success('修改成功');
        }
        $this->view->assign("row", Config('dnspod'));
        return $this->view->fetch('config');
    }

    // 获取dnspod域名信息详情
    public function detail($ids = null){
        $row = $this->model::get($ids);
        if(!$row){
            $this->error('没有找到该域名');
        }
        $id = Config('dnspod.id');
        $token = Config('dnspod.token');
        $dnspod = new Dnspod($id,decode($token));
        if($this->request->get('info/d')){
            $info = $dnspod->domain_info('',$row->domain);
        }elseif($this->request->get('log/d')){
            $info = $dnspod->domain_log('',$row->domain);
        }
        
        if(!$info){
            $this->error($dnspod->msg,'');
        }
        
        $this->view->assign("info", $info);
        return $this->view->fetch();
    }
}
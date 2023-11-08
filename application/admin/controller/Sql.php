<?php

namespace app\admin\controller;

use app\common\controller\Backend;
use app\common\library\Btaction;

/**
 * 数据库
 *
 * @icon fa fa-circle-o
 */
class Sql extends Backend
{
    
    /**
     * Sql模型对象
     * @var \app\admin\model\Sql
     */
    protected $model = null;

    protected $relationSearch = true;

    protected $searchFields = ['id','username','vhost.bt_name'];

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\Sql;
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
            foreach ($list as $k => $v) {
                // $v->hidden(['password', 'salt']);
            }
            $result = array("total" => $total, "rows" => $list);

            return json($result);
        }
        return $this->view->fetch();
    }

    public function import()
    {
        parent::import();
    }

    // 真实删除
    // public function destroy($ids = null){
    //     $info = $this->model::onlyTrashed()->where(['id'=>$ids])->find();
    //     if(!$info){
    //         $this->error('不存在');
    //     }
    //     $bt = new Btaction();
    //     $bt->sql_name = $info->username;
    //     $del = $bt->SqlDelete();
    //     // 强制删除，忽略错误
    //     // if(!$del){
    //     //     $this->error($bt->_error);
    //     // }
    //     parent::destroy($ids);
    // }


    /**
     * 默认生成的控制器所继承的父类中有index/add/edit/del/multi五个基础方法、destroy/restore/recyclebin三个回收站方法
     * 因此在当前控制器中可不用编写增删改查的代码,除非需要自己控制这部分逻辑
     * 需要将application/admin/library/traits/Backend.php中对应的方法复制到当前控制器,然后进行修改
     */
    

}
<?php


namespace app\admin\controller;


use app\common\controller\Backend;

class Domainblock extends Backend
{
    /**
     * Domainlist模型对象
     * @var \app\common\model\DomainBlock
     */
    protected $model = null;

    protected $relationSearch = true;
    protected $searchFields = ['id','domain'];

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\common\model\DomainBlock();
        $this->view->assign("statusList", $this->model->getStatusList());
        $this->view->assign('alldata', ['0' => __('No'), '1' => __('Yes')]);
        $this->view->assign('typedata', ['block' => __('Block'), 'pass' => __('Pass')]);
    }
}

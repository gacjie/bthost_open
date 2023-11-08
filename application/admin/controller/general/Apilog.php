<?php

namespace app\admin\controller\general;

use app\admin\model\AuthGroup;
use app\common\controller\Backend;

/**
 * API日志
 *
 * @icon   fa fa-users
 * @remark 所有用户的主机操作日志
 */
class Apilog extends Backend
{

    /**
     * @var \app\common\model\ApiLog
     */
    protected $model = null;
    protected $childrenGroupIds = [];
    protected $relationSearch = true;
    // 通用搜索
    protected $searchFields = 'url,title,content,ip,useragent';

    public function _initialize()
    {
        parent::_initialize();
        $this->model = model('ApiLog');

        $this->childrenGroupIds = $this->auth->getChildrenGroupIds($this->auth->isSuperAdmin() ? true : false);

        $groupName = AuthGroup::where('id', 'in', $this->childrenGroupIds)
            ->column('id,name');

        $this->view->assign('groupdata', $groupName);
    }

    /**
     * 查看
     */
    public function index()
    {
        //设置过滤方法
        $this->request->filter(['strip_tags', 'trim']);
        if ($this->request->isAjax()) {
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();
            $list = $this->model
                ->where($where)
                ->order($sort, $order)
                ->paginate($limit);

            $result = array("total" => $list->total(), "rows" => $list->items());

            return json($result);
        }
        return $this->view->fetch();
    }

    /**
     * 详情
     */
    public function detail($ids)
    {
        $row = $this->model->get(['id' => $ids]);
        if (!$row) {
            $this->error(__('No Results were found'));
        }
        $this->view->assign("row", $row->toArray());
        return $this->view->fetch();
    }

    /**
     * 添加
     * @internal
     */
    // public function add()
    // {
    //     $this->error();
    // }

    /**
     * 编辑
     * @internal
     */
    // public function edit($ids = null)
    // {
    //     $this->error();
    // }

    /**
     * 删除
     */
    // public function del($ids = "")
    // {
    //     $this->error();
    // }

    /**
     * 批量更新
     * @internal
     */
    // public function multi($ids = "")
    // {
    //     // 管理员禁止批量操作
    //     $this->error();
    // }

    public function selectpage()
    {
        return parent::selectpage();
    }
}

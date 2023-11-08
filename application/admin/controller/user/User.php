<?php

namespace app\admin\controller\user;

use app\common\controller\Backend;
use think\Cookie;

/**
 * 会员管理
 *
 * @icon fa fa-user
 */
class User extends Backend
{

    protected $relationSearch = true;

    // 通用搜索
    protected $searchFields = 'id,username,nickname,group.name,loginip';

    /**
     * User模型对象
     * @var \app\admin\model\User
     */
    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\User;
        $this->view->assign("statusList", $this->model->getStatusList());
    }

    /**
     * 查看
     */
    public function index()
    {
        //设置过滤方法
        $this->request->filter(['strip_tags', 'trim']);
        if ($this->request->isAjax()) {
            //如果发送的来源是Selectpage，则转发到Selectpage
            if ($this->request->request('keyField')) {
                return $this->selectpage();
            }
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();
            $list = $this->model
                ->with('group')
                ->where($where)
                ->order($sort, $order)
                ->paginate($limit);
            foreach ($list as $k => $v) {
                $v->avatar = $v->avatar ? cdnurl($v->avatar, true) : letter_avatar($v->nickname);
                $v->hidden(['password', 'salt']);
            }
            $result = array("total" => $list->total(), "rows" => $list->items());

            return json($result);
        }
        return $this->view->fetch();
    }

    public function import()
    {
        parent::import();
    }

    /**
     * 一键登录用户账户
     *
     * @param [type] $ids
     * @return void
     */
    public function login($ids)
    {
        if (!$ids) {
            $this->error('ID错误');
        }
        $userInfo = model('User')::get($ids);

        if (!$userInfo) {
            $this->error('用户信息错误');
        }
        $userAuth = new \app\common\library\Auth();

        if ($userAuth->direct($userInfo->id)) {
            Cookie::set('uid', $userAuth->id);
            Cookie::set('token', $userAuth->getToken());
            $this->success(__('登录成功'), '/');
        } else {
            $this->error($userAuth->getError(), null, ['token' => $this->request->token()]);
        }
    }

    // 获取详细信息
    public function info($ids = null)
    {
        $userInfo = $this->model::get($ids);
        if (!$userInfo) {
            $this->error('没有找到该用户');
        }
        $this->view->assign("row", $userInfo->toArray());
        return $this->view->fetch();
    }

    /**
     * 删除
     */
    public function del($ids = "")
    {
        if (!$this->request->isPost()) {
            $this->error(__("Invalid parameters"));
        }
        $ids = $ids ? $ids : $this->request->post("ids");
        $list = explode(',', $ids);
        if ($list) {
            foreach ($list as $key => $value) {
                $row = $this->model->get($value);
                $this->modelValidate = true;
                if ($row) {
                    \app\common\library\Auth::instance()->delete($row->id);
                }
            }
        }
        $this->success();
    }

    /**
     * 默认生成的控制器所继承的父类中有index/add/edit/del/multi五个基础方法、destroy/restore/recyclebin三个回收站方法
     * 因此在当前控制器中可不用编写增删改查的代码,除非需要自己控制这部分逻辑
     * 需要将application/admin/library/traits/Backend.php中对应的方法复制到当前控制器,然后进行修改
     */
}

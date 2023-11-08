<?php

namespace app\admin\controller;

use app\common\controller\Backend;

/**
 * 资源套餐
 *
 * @icon fa fa-circle-o
 */
class Plans extends Backend
{
    
    /**
     * Plans模型对象
     * @var \app\admin\model\Plans
     */
    protected $model = null;

    protected $relationSearch = true;

    // 通用搜索
    protected $searchFields = ['id','name'];

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\Plans;
        $this->productModel = model('\app\common\model\Product');
        $this->view->assign("statusList", $this->model->getStatusList());
        
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
    
    public function add($ids = null)
    {
        if ($this->request->isPost()) {
            $row = $this->request->post("row/a", [], 'trim');
            if ($row) {
                if ($this->model->where('name', $row['name'])->find()) {
                    $this->error('组名已存在');
                }
                $string = json_encode($row);
                $this->model->data([
                    'name' => $row['name'],
                    'value' => $string,
                ]);
                if ($this->model->save()) {
                    $this->success('新增成功');
                } else {
                    $this->error($this->model->getError());
                }
            } else {
                $this->error(__('Parameter %s can not be empty', ''));
            }
        } else {
            $siteList = [];

            $groupList = $this->productModel::getGroupList();

            foreach ($groupList as $k => $v) {
                $siteList[$k]['name'] = $k;
                $siteList[$k]['title'] = $v;
                $siteList[$k]['list'] = [];
            }
            foreach ($this->productModel->all() as $k => $v) {
                $value = $v->toArray();
                $value['title'] = __($value['title']);
                if (in_array($value['type'], ['select', 'selects', 'checkbox', 'radio'])) {
                    $value['value'] = explode(',', $value['value']);
                }
                $value['content'] = json_decode($value['content'], true);
                $value['tip'] = htmlspecialchars($value['tip']);
                $siteList[$v['group']]['list'][] = $value;
            }
            $index = 0;
            foreach ($siteList as $k => &$v) {
                $v['active'] = !$index ? true : false;
                $index++;
            }
            $this->view->assign('siteList', $siteList);
            $this->view->assign('typeList', $this->productModel::getTypeList());
            $this->view->assign('ruleList', $this->productModel::getRegexList());
            $this->view->assign('groupList', $this->productModel::getGroupList());
            return $this->view->fetch();
        }
    }

    /**
     * 编辑
     * @param null $ids
     */
    public function edit($ids = null)
    {
        $groupInfo = $this->model->get($ids);
        if (!$groupInfo) {
            $this->error('当前组找不到');
        }
        if ($this->request->isPost()) {
            $row = $this->request->post("row/a", [], 'trim');
            if ($row) {
                $string = json_encode($row);
                if ($this->model->save([
                    'name' => $row['name'],
                    'value' => $string,
                ], ['id' => $ids])) {
                    $this->success('修改成功');
                } else {
                    $this->error($this->model->getError());
                }
            } else {
                $this->error(__('Parameter %s can not be empty', ''));
            }
        } else {
            $siteList = [];

            $groupList = $this->productModel::getGroupList();
            foreach ($groupList as $k => $v) {
                $siteList[$k]['name'] = $k;
                $siteList[$k]['title'] = $v;
                $siteList[$k]['list'] = [];
            }
            
            $valueArr = json_decode($groupInfo->value,1);
            foreach ($this->productModel->all() as $k => $v) {
                
                $value = $v->toArray();
                
                $value['title'] = __($value['title']);
                if (in_array($value['type'], ['select', 'selects', 'checkbox', 'radio'])) {
                    $value['value'] = explode(',', $value['value']);
                }
                $value['content'] = json_decode($value['content'], true);
                $value['tip'] = htmlspecialchars($value['tip']);
                
                $value['value'] = isset($valueArr[$value['name']]) ? $valueArr[$value['name']] : '';

                $value['extend_html'] = $this->productModel->ExtendHtml($value);
                $siteList[$v['group']]['list'][] = $value;
            }
            $index = 0;
            foreach ($siteList as $k => &$v) {
                $v['active'] = !$index ? true : false;
                $index++;
            }

            $this->view->assign('groupName', $groupInfo->name);
            $this->view->assign('siteList', $siteList);
            // var_dump($siteList);exit;
            $this->view->assign('typeList', $this->productModel::getTypeList());
            $this->view->assign('ruleList', $this->productModel::getRegexList());
            $this->view->assign('groupList', $this->productModel::getGroupList());
            return $this->view->fetch();
        }
    }

    public function copy($ids = null)
    {
        if (!$ids) {
            $this->error('ids为空');
        }
        $wareGroup = $this->model->where(['id' => $ids])->find();
        if (!$wareGroup) {
            $this->error('没有找到该组');
        }
        $data = [
            'name' => $wareGroup['name'] . '_copy',
            'value' => $wareGroup['value'],
            'createtime' => time(),
            'updatetime' => time(),
            'status' => 'normal',
        ];
        $wg = $this->model->data($data);
        $insert = $wg->save();
        if (!$insert) {
            $this->error('失败');
        }
        $this->success('成功');
    }

}
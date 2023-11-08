<?php

namespace app\admin\controller\general;

use app\common\controller\Backend;
use app\common\library\Btaction;
use think\Validate;

/**
 * 一键部署管理
 *
 * @icon fa fa-user
 */
class Deployment extends Backend
{
    protected $model = null;
    protected $noNeedRight = '*';

    public function _initialize()
    {
        parent::_initialize();
    }

    public function list()
    {
        // 获取服务器一键部署内容
        $bt                = new Btaction();
        $name              = $this->request->post('name');
        $keyValue          = $this->request->post('keyValue');
        $search            = $name ? $name : $keyValue;
        $new_data['list']  = $bt->getdeploymentlist($search);
        $new_data['total'] = count($new_data['list']);
        return json($new_data);
    }

    public function upload()
    {
        if ($this->request->isPost()) {
            // 上传文件
            $name = $this->request->post('row.name');
            $title = $this->request->post('row.title');
            $php = $this->request->post('row.php');
            $enable_functions = $this->request->post('row.enable_functions');
            $version = $this->request->post('row.version');
            $ps = $this->request->post('row.ps');
            // $dep_zip = $this->request->file('dep_zip');
            $validate = new Validate([
                'name' => 'require|regex:[0-9A-Za-z_-]+',
                'title' => 'require',
                'php' => 'require',
                'version' => 'require',
                'dep_zip' => 'require',
            ], [
                'name' => '名称格式不正确',
                'title.require' => '中文名不能为空',
                'php.require' => 'php版本不能为空',
                'version.require' => '版本号不能为空',
                'dep_zip' => '安装包不能为空',
            ]);
            if (!$validate->check($this->request->post('row/a'))) {
                $this->error($validate->getError());
            }

            $dep_zip = $this->request->post('row.dep_zip');
            $postFile = ROOT_PATH . 'public' . $dep_zip;
            if (class_exists('CURLFile')) {
                // php 5.5
                $data = new \CURLFile(realpath($postFile));
            } else {
                $data = '@' . realpath($postFile);
            }
            // 导入项目包
            $bt = new Btaction();
            $up = $bt->btPanel->AddPackage($name, $title, $php, $enable_functions, $version, $ps, $data);
            if ($up && isset($up['status']) && $up['status']) {
                $this->success($up['msg']);
            } elseif (isset($up['msg'])) {
                $this->error($up['msg']);
            } else {
                $this->error('上传失败，请稍候重试');
            }
        }
        return $this->fetch();
    }
}

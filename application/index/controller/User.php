<?php

namespace app\index\controller;

use app\common\controller\Frontend;
use app\common\model\HostLog;
use think\Config;
use think\Cookie;
use think\Hook;
use think\Session;
use think\Validate;

/**
 * 控制中心
 */
class User extends Frontend
{
    protected $layout = 'default';
    protected $noNeedLogin = ['login'];
    protected $noNeedRight = ['*'];

    public function _initialize()
    {
        parent::_initialize();
        $auth = $this->auth;

        if (!Config::get('fastadmin.usercenter')) {
            $this->error(__('User center already closed'));
        }

        $this->isAjax = $this->request->isAjax() ? 1 : 0;

        //监听注册登录退出的事件
        Hook::add('user_login_successed', function ($user) use ($auth) {
            $expire = input('post.keeplogin') ? 30 * 86400 : 0;
            Cookie::set('uid', $user->id, $expire);
            Cookie::set('token', $auth->getToken(), $expire);
        });
        Hook::add('user_logout_successed', function ($user) use ($auth) {
            Cookie::delete('uid');
            Cookie::delete('token');
        });
    }

    /**
     * 空的请求
     * @param $name
     * @return mixed
     */
    public function _empty($name)
    {
        $data = Hook::listen("user_request_empty", $name);
        foreach ($data as $index => $datum) {
            $this->view->assign($datum);
        }
        return $this->view->fetch($name);
    }

    public function index()
    {
        if ($this->request->get('host_id/d')) {
            Cookie::set('host_id_' . $this->auth->id, $this->request->get('host_id/d'));
            return $this->redirect('/');
        }
        // 站点列表
        $list = model('Host')::all(['user_id' => $this->auth->id]);
        if ($list) {
            foreach ($list as $value) {
                $value->domain = model('Domainlist')->where(['status' => 'normal', 'vhost_id' => $value->id])->select();
                $value->statusStr = model('Host')->status($value->status);
            }
        }
        if (!$list) {
            $this->error(__('No site currently available') . '<a href="' . url('index/user/logout') . '">' . __('Logout') . '</a>', '');
        }
        $this->view->assign('list', $list);
        // 站点选择页
        $this->view->assign('title', __('Site selection'));
        return $this->view->fetch();
    }

    /**
     * 会员登录
     */
    public function login()
    {
        $url = $this->request->request('url', '');
        if ($this->auth->id) {
            $this->success(__('You\'ve logged in, do not login again'), $url ? $url : url('/'));
        }
        if ($this->request->isPost()) {
            $account = $this->request->post('account');
            $password = $this->request->post('password');
            $keeplogin = (int)$this->request->post('keeplogin');
            $token = $this->request->post('__token__');
            $rule = [
                'account'   => 'require|length:3,50',
                'password'  => 'require|length:6,30',
                '__token__' => 'require|token',
            ];

            $msg = [
                'account.require'  => 'Account can not be empty',
                'account.length'   => 'Account must be 3 to 50 characters',
                'password.require' => 'Password can not be empty',
                'password.length'  => 'Password must be 6 to 30 characters',
            ];
            $data = [
                'account'   => $account,
                'password'  => $password,
                '__token__' => $token,
            ];
            $validate = new Validate($rule, $msg);
            $result = $validate->check($data);
            if (!$result) {
                $this->error(__($validate->getError()), null, ['token' => $this->request->token()]);
                return false;
            }
            HostLog::setTitle(__('Login'));
            if ($this->auth->login($account, $password)) {
                if ($this->isAjax) {
                    $this->success(__('Logged in successful'), $url ? $url : url('/'));
                } else {
                    $this->redirect('/');
                }
            } else {
                $this->error($this->auth->getError(), null, ['token' => $this->request->token()]);
            }
        }
        //判断来源
        $referer = $this->request->server('HTTP_REFERER');
        if (
            !$url && (strtolower(parse_url($referer, PHP_URL_HOST)) == strtolower($this->request->host()))
            && !preg_match("/(user\/login|user\/register|user\/logout)/i", $referer)
        ) {
            $url = $referer;
        }
        $this->view->assign('url', $url);
        $this->view->assign('title', __('Login'));
        return $this->view->fetch();
    }

    /**
     * 退出登录
     */
    public function logout()
    {
        //退出本站
        $this->auth->logout();
        if ($this->isAjax) {
            $this->success(__('Logout successful'), url('/'));
        } else {
            $this->redirect('index/user/login');
        }
    }
}

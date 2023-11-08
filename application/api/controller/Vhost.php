<?php

namespace app\api\controller;

use app\common\controller\Api;
use fast\Random;
use think\Validate;
use think\Config;
use think\Db;
use think\Cache;
use app\common\library\Btaction;
use app\common\library\Ftmsg;
use app\common\library\Message;
use think\Cookie;
use think\exception\ValidateException;

/**
 * 主机操作对外接口
 */
class Vhost extends Api
{

    protected $noNeedLogin = '*';
    protected $noNeedRight = '*';
    // 跳过签名验证/跨域检测
    protected $noTokenCheck = ['host_login'];
    // 跳过IP验证
    protected $noIpCheck = ['host_login'];

    public function _initialize()
    {
        parent::_initialize();
        $this->access_token = Config::get('site.access_token');
        try {
            // 判断是否需要验证权限
            if (!$this->auth->match($this->noTokenCheck)) {
                $this->token_check();
            }
        } catch (\Exception $e) {
            $this->error($e->getMessage());
        }

        // IP白名单效验
        $ip = request()->ip();
        $forbiddenip = Config::get('site.api_returnip');
        if ($forbiddenip != '' && !in_array($this->request->action(), $this->noIpCheck)) {
            $black_arr = explode("\r\n", $forbiddenip);
            if (!in_array($ip, $black_arr)) {
                $this->error('非白名单IP不允许请求');
            }
        }

        $this->hostModel = model('Host');
    }

    public function index()
    {
        $this->success('请求成功');
    }

    // 一键部署程序列表
    public function deployment_list()
    {
        $bt = new Btaction();
        $list = $bt->getdeploymentlist();
        $this->success('请求成功', $list);
    }

    // php列表
    public function php_list()
    {
        $bt = new Btaction();
        $list = $bt->getphplist();
        $this->success('请求成功', $list);
    }

    // 云服务器状态及监控
    public function server_status()
    {
        $bt = new Btaction();
        $info = $bt->btPanel->GetNetWork();
        $this->success('请求成功', $info);
    }

    // 网站分类列表
    public function sort_list()
    {
        // 调用缓存
        $sortList = Cache::remember('site_type_list', function () {
            $bt = new Btaction();
            return $sortList = $bt->getsitetype();
        });
        if (!$sortList) {
            $this->error('请求失败');
        }
        $this->success('请求成功', $sortList);
    }

    // 创建网站分类
    public function sort_create()
    {
        $name = $this->request->post('name');
        if (!$name) {
            $this->error('请求错误');
        }
        $bt = new Btaction();
        $create = $bt->btPanel->add_site_type($name);
        if (!$create) {
            $this->error($bt->btPanel->_error);
        }
        // 刷新网站分类列表
        Cache::rm('site_type_list');
        // 重新获取网站分类并缓存
        Cache::remember('site_type_list', function () {
            $bt = new Btaction();
            return $list = $bt->getsitetype();
        });
        $this->success('创建成功');
    }

    // 编辑网站分类
    public function sort_edit()
    {
        $id = $this->request->post('id/d');
        $name = $this->request->post('name');
        if (!$id || !$name) {
            $this->error('请求错误');
        }
        $bt = new Btaction();
        $edit = $bt->btPanel->edit_site_type($id, $name);
        if (!$edit) {
            $this->error($bt->btPanel->_error);
        }
        // 刷新网站分类列表
        Cache::rm('site_type_list');
        // 重新获取网站分类并缓存
        Cache::remember('site_type_list', function () {
            $bt = new Btaction();
            return $list = $bt->getsitetype();
        });
        $this->success('修改成功');
    }

    // 删除网站分类
    public function sort_delete()
    {
        $id = $this->request->post('id/d');
        if (!$id) {
            $this->error('请求错误');
        }
        $bt = new Btaction();
        $del = $bt->btPanel->delete_site_type($id);
        if (!$del) {
            $this->error($bt->btPanel->_error);
        }
        // 刷新网站分类列表
        Cache::rm('site_type_list');
        // 重新获取网站分类并缓存
        Cache::remember('site_type_list', function () {
            $bt = new Btaction();
            return $list = $bt->getsitetype();
        });
        $this->success('删除成功');
    }

    // IP池列表
    public function ippools_list()
    {
        $list = model('Ippools')::all();
        $this->success('请求成功', $list);
    }

    // IP地址列表
    public function ipaddress_list()
    {
        $ippools_id = $this->request->post('ippools_id/d');
        if (!$ippools_id) {
            $this->error('请求错误');
        }
        $list = model('Ipaddress')::all(['ippools_id' => $ippools_id]);
        $this->success('请求成功', $list);
    }

    // IP地址详情
    public function ipaddress_info()
    {
        $id = $this->request->post('id/d');
        if (!$id) {
            $this->error('请求错误');
        }
        $info = model('Ipaddress')::get($id);
        $this->success('请求成功', $info);
    }

    // 资源组列表
    public function plans_list()
    {
        $list = model('Plans')::all();
        foreach ($list as $key => $value) {
            $list[$key]['value'] = json_decode($value->value, 1);
        }
        $this->success('请求成功', $list);
    }

    // 资源组详情
    public function plans_info()
    {
        $id = $this->request->post('id/d');
        if (!$id) {
            $this->error('请求错误');
        }

        $info = model('Plans')::get($id);
        $info->value = json_decode($info->value, 1);
        $this->success('请求成功', $info);
    }

    // 域名池列表
    public function domainpools_list()
    {
        $list = model('Domainpools')::all();
        $this->success('请求成功', $list);
    }

    // 域名列表
    public function domain_list()
    {
        $domainpools_id = $this->request->post('domainpools_id/d');
        if (!$domainpools_id) {
            $this->error('请求错误');
        }
        $list = model('Domain')::all(['domainpools_id' => $domainpools_id]);
        $this->success('请求成功', $list);
    }

    // 域名详情
    public function domain_info()
    {
        $id = $this->request->post('id/d');
        if (!$id) {
            $this->error('请求错误');
        }
        $info = model('Domain')::get($id);
        $this->success('请求成功', $info);
    }

    // 用户列表
    public function user_list()
    {
        $group_id = $this->request->param('group_id/d');
        if ($group_id) {
            $list = model('User')::all(['group_id' => $group_id]);
        } else {
            $list = model('User')::all();
        }

        if ($list) {
            foreach ($list as $key => $value) {
                $value->password = decode($value->password, $value->salt);
                $value->hidden(['salt', 'loginip', 'token']);
            }
        }
        $this->success('请求成功', $list);
    }

    // 创建用户
    public function user_create()
    {
        $username = $this->request->post('username', Random::alnum(8));
        $nickname = $this->request->post('nickname', $username);
        $password = $this->request->post('password', Random::alnum(8));
        $group_id = $this->request->post('group_id/d', 1);
        if (!$username || !$nickname || !$password) {
            $this->error('用户名、昵称或密码不能为空');
        }
        $user_check = model('User')::get(['username' => $username]);
        if ($user_check) {
            $this->error('用户名已存在，请勿重复创建');
        }
        $user_inc = model('User')::create([
            'username' => $username,
            'password' => $password,
            'group_id' => $group_id,
            'nickname' => $nickname,
        ]);
        $user_inc->password = $password;
        $this->success('创建成功', $user_inc);
    }

    // 用户删除
    public function user_del()
    {
        $user_id = $this->request->post('user_id/d');
        $is_del = $this->request->post('delete/d', false);
        $user = model('User')::withTrashed()->where('id', $user_id)->find();
        if (!$user) {
            $this->error('用户不存在');
        }
        $user->delete($is_del);
        $this->success('删除成功');
    }

    // 用户信息修改
    public function user_edit()
    {
        $user_id = $this->request->post('user_id/d');
        $params = $this->request->post('info/a');
        if (!$params || !$user_id) {
            $this->error('请求错误');
        }
        $user = model('User')::get($user_id);
        if (!$user) {
            $this->error('用户不存在');
        }
        model('User')->allowField(true)->save($params, ['id' => $user_id]);
        $this->success('修改成功');
    }

    // 账号信息
    public function user_info()
    {
        $id = $this->request->post('id/d');
        if (!$id) {
            $this->error('请求错误');
        }
        $info = model('User')::get($id);
        $info->password = decode($info->password, $info->salt);
        $info->hidden(['salt', 'loginip', 'token']);
        $this->success('请求成功', $info);
    }

    // 主机转移账户
    public function host_push()
    {
        $host_id = $this->request->post('host_id/d');
        $user_id = $this->request->post('user_id/d');
        if (!$host_id || !$user_id) {
            $this->error('请求错误');
        }
        $hostInfo = $this->getHostInfo($host_id);
        $hostInfo->user_id = $user_id;
        $hostInfo->save();
        $this->success('转移成功');
    }

    // 数据库详情
    public function sql_info()
    {
        $id = $this->request->post('sql_id/d');
        if (!$id) {
            $this->error('错误的请求');
        }
        $info = model('Sql')::get($id);
        $info->console = $info->console ? $info->console : config('site.phpmyadmin');
        $this->success('请求成功', $info);
    }

    // 创建数据库
    // TODO 多数据库没出来之前临时停用
    public function sql_build()
    {
        $this->error('接口停用');
        $id = $this->request->post('id/d');
        if (!$id) {
            $this->error('错误的请求');
        }

        $username = $this->request->post('username', Random::alnum(8));
        $database = $this->request->post('database');
        $password = $this->request->post('password', Random::alnum(8));
        $console = $this->request->post('console');
        $type = $this->request->post('type', 'bt');

        if (!preg_match("/^[A-Za-z0-9]+$/", $username)) {
            $this->error('账号格式不正确');
        }

        $info = $this->getHostInfo($id);

        $sqlData = [
            'vhost_id' => $info->id,
            'username' => $username,
            'database' => $database,
            'password' => $password,
            'console'  => $console,
            'type'     => $type == 'bt' ? 'bt' : 'custom',
        ];
        $create = model('Sql')::create($sqlData);
        $sqlData['id'] = $create->id;
        $this->success('创建成功', $sqlData);
    }

    // 数据库密码修改
    public function sql_pass()
    {
        $id = $this->request->post('sql_id/d');
        if (!$id) {
            $this->error('错误的请求');
        }
        $password = $this->request->post('password', Random::alnum(8));

        $info = model('Sql')::get($id);
        $info->password = $password;
        $info->save();
        $this->success('修改成功', $info);
    }

    // FTP详情
    public function ftp_info()
    {
        $id = $this->request->post('ftp_id/d');
        if (!$id) {
            $this->error('错误的请求');
        }
        $info = model('Ftp')::get($id);
        $this->success('请求成功', $info);
    }

    // FTP密码修改
    public function ftp_pass()
    {
        $id = $this->request->post('ftp_id/d');
        $password = $this->request->post('password', Random::alnum(8));
        if (!$id) {
            $this->error('请求错误');
        }
        $info = model('Ftp')::get($id);
        if (!$info) {
            $this->error('ftp不存在');
        }
        $info->password = $password;
        $info->save();
        $this->success('修改成功', $info);
    }

    // FTP状态修改
    public function ftp_status()
    {
        $id = $this->request->post('ftp_id/d');
        $status = $this->request->post('status');
        if (!$id) {
            $this->error('请求错误');
        }
        $info = model('Ftp')::get($id);
        if (!$info) {
            $this->error('ftp不存在');
        }
        $info->status = $status == 'hidden' ? 'hidden' : 'normal';
        $info->save();
        $this->success('修改成功', $info);
    }

    // 主机列表
    public function host_list()
    {
        $sortId = $this->request->param('sort_id/d');
        $userId = $this->request->param('user_id/d');
        $where = [];
        if ($sortId) {
            $where['sort_id'] = $sortId;
        }
        if ($userId) {
            $where['user_id'] = $userId;
        }
        $list = $this->hostModel->where($where)->select();
        $this->success('请求成功', $list);
    }

    // 创建主机
    public function host_build()
    {
        $plans_id = $this->request->post('plans_id/d');
        // 格式 Y-m-d
        $endtime = $this->request->post('endtime');
        $user_id = $this->request->post('user_id', 1);
        $sort_id = $this->request->post('sort_id', 1);
        // 自定义站点名前缀
        $username = $this->request->post('username');

        if (date('Y-m-d', strtotime($endtime)) !== $endtime) {
            $this->error('时间格式错误，请严格按照Y-m-d格式传递');
        }

        $bt = new Btaction();
        if (!$bt->test()) {
            $this->error($bt->_error);
        }
        if ($plans_id) {
            // 如果传递资源组ID，使用资源组配置构建数据
            // 查询该资源组ID是否正确
            $plansInfo = model('Plans')->getPlanInfo($plans_id);
            if (!$plansInfo) {
                $this->error(model('Plans')->msg);
            }
        } else {
            // 使用用户传递参数进行构建数据
            $pack_arr = $this->request->post('pack/a');
            $plansInfo = model('Plans')->getPlanInfo('', $pack_arr);
            if (!$plansInfo) {
                $this->error(model('Plans')->msg);
            }
        }
        // 构建站点信息
        $hostSetInfo = $bt->setInfo($this->request->post(), $plansInfo);
        if (!$hostSetInfo) {
            $this->error('站点信息构建失败，请重试|' . json_encode($plansInfo));
        }

        // vsftpd创建
        if (isset($plansInfo['vsftpd']) && $plansInfo['vsftpd'] == 1) {
            // 调用vsftpd进行目录创建
            $creatVsftpdPath = $bt->btPanel->AddVsftpdUser($hostSetInfo['username'], $hostSetInfo['password'], $hostSetInfo['path'], $plansInfo['site_max'], $plansInfo['limit_rate']);
            if ($creatVsftpdPath && isset($creatVsftpdPath['status']) && $creatVsftpdPath['status'] != 'Success') {
                $this->error('主机创建失败->' . $creatVsftpdPath['msg'] . '|' . json_encode($hostSetInfo));
            } elseif ($creatVsftpdPath && !isset($creatVsftpdPath['status'])) {
                $this->error('主机创建失败->vsftpd网站根目录创建失败|' . json_encode($hostSetInfo['path']));
            } elseif (isset($creatVsftpdPath['msg'])) {
                $this->error('主机创建失败->' . $creatVsftpdPath['msg'] . '|' . json_encode($hostSetInfo));
            }
        }

        // 连接宝塔进行站点开通
        $btInfo = $bt->btBuild($hostSetInfo);
        if (!$btInfo) {
            $this->error($bt->_error);
        }

        $bt->bt_id = $btId = $btInfo['siteId'];
        $btName = $hostSetInfo['bt_name'];

        Db::startTrans();

        // vsftpd创建

        // 修改到期时间
        $timeSet = $bt->btPanel->WebSetEdate($btId, $endtime);
        if (!$timeSet['status']) {
            $this->error('开通时间设置失败|' . json_encode($endtime));
        }

        // 预装程序
        if (isset($plansInfo['preset_procedure']) && $plansInfo['preset_procedure']) {
            // 程序预装
            $defaultPhp = $hostSetInfo['version'] && $hostSetInfo['version'] != '00' ? $hostSetInfo['version'] : '56';
            $setUp = $bt->presetProcedure($plansInfo['preset_procedure'], $btName, $defaultPhp);
            if (!$setUp) {
                $this->error($bt->_error);
            }
        }
        if (isset($plansInfo['session']) && $plansInfo['session']) {
            // session隔离
            $bt->btPanel->set_php_session_path($btId, 1);
        }

        // 并发、限速设置
        // 默认并发、网速限制
        if (isset($plansInfo['perserver']) && $plansInfo['perserver'] != 0 && isset($bt->serverConfig['webserver']) && $bt->serverConfig['webserver'] == 'nginx') {
            $modify_status = $bt->setLimit($plansInfo);
            if (!$modify_status) {
                $this->error($bt->_error);
            }
        }

        $dnspod_record = $dnspod_record_id = $dnspod_domain_id = '';

        if (isset($plansInfo['dnspod']) && $plansInfo['dnspod']) {
            // 如果域名属于dnspod智能解析
            $record_type = Config::get('site.dnspod_analysis_type');
            $analysis = Config::get('site.dnspod_analysis_url');

            $sub_domain = $hostSetInfo['domain'];
            $domain_jx = model('Host')->doamin_analysis($plansInfo['domain'], $analysis, $sub_domain, $record_type);
            if (!is_array($domain_jx)) {
                $this->error('域名解析失败|' . json_encode([$plansInfo['domain'], $analysis, $sub_domain, $domain_jx], JSON_UNESCAPED_UNICODE));
            }
            $dnspod_record = $sub_domain;
            $dnspod_record_id = $domain_jx['id'];
            $dnspod_domain_id = $domain_jx['domain_id'];
        }

        // 获取信息后存入数据库
        $host_data = [
            'user_id'      => $user_id,
            'sort_id'      => $sort_id,
            'bt_id'        => $btId,
            'bt_name'      => $btName,
            'site_max'     => $plansInfo['site_max'] ?? 0,
            'sql_max'      => $plansInfo['sql_max'] ?? 0,
            'flow_max'     => $plansInfo['flow_max'] ?? 0,
            'is_audit'     => $plansInfo['domain_audit'] ?? 0,
            'is_vsftpd'    => $plansInfo['vsftpd'] ?? 0,
            'domain_max'   => $plansInfo['domain_num'] ?? 0,
            'web_back_num' => $plansInfo['web_back_num'] ?? 0,
            'sql_back_num' => $plansInfo['sql_back_num'] ?? 0,
            'ip_address'   => $plansInfo['ipArr'] ?? '',
            'endtime'      => $endtime,
            'sub_bind'     => $plansInfo['sub_bind'] ?? 0,
            'is_api'       => 1,
        ];
        $hostInfo = model('Host')::create($host_data);

        $vhost_id = $hostInfo->id;
        if (!$vhost_id) {
            $this->error('主机信息存储失败');
        }

        if ($btInfo['ftpStatus'] == true) {
            // 存储ftp
            $ftpInfo = model('Ftp')::create([
                'vhost_id' => $vhost_id,
                'username' => $btInfo['ftpUser'],
                'password' => $btInfo['ftpPass'],
            ]);
        }

        if ($btInfo['databaseStatus'] == true) {
            // 存储sql
            $sqlInfo = model('Sql')::create([
                'vhost_id' => $vhost_id,
                'database' => $btInfo['databaseUser'],
                'username' => $btInfo['databaseUser'],
                'password' => $btInfo['databasePass'],
            ]);
        }

        // 存入域名信息
        $domainInfo = model('Domainlist')::create([
            'domain'           => $btName,
            'vhost_id'         => $vhost_id,
            'domain_id'        => $plansInfo['domainlist_id'] ?? 0,
            'dnspod_record'    => $dnspod_record,
            'dnspod_record_id' => $dnspod_record_id,
            'dnspod_domain_id' => $dnspod_domain_id,
            'dir'              => '/',
        ]);

        Db::commit();

        // 方糖通知
        if (Config::get('site.ftqq_sckey')) {
            $title = '[主机开通提醒]';
            $content = "\n\n主机名：" . $btName;
            $content .= "\n\n主机空间：" . $host_data['site_max'];
            $content .= "\n\n数据库大小：" . $host_data['sql_max'];
            $content .= "\n\n流量大小：" . $host_data['flow_max'];
            $content .= "\n\n到期时间：" . $endtime;
            $content .= "\n\nTime：" . date('Y-m-d H:i:s', time());
            $this->ft_msg($title, $content);
        }

        $this->success('创建成功', ['site' => $hostInfo, 'domain' => $domainInfo, 'sql' => $sqlInfo, 'ftp' => $ftpInfo]);
    }

    // 主机详情
    public function host_info()
    {
        $id = $this->request->post('id/d');
        if (!$id) {
            $this->error('错误的请求');
        }
        $info = $this->getHostInfo($id);
        $info->default_analysis = config('site.default_analysis') == 0 ? $info->bt_name : config('site.dnspod_analysis_url');
        $info->sql = model('Sql')::all(['vhost_id' => $id, 'status' => 'normal']);
        $info->ftp = model('Ftp')::get(['vhost_id' => $id, 'status' => 'normal']);
        $info->domain = model('Domainlist')::all(['vhost_id' => $id]);

        $this->success('请求成功 ', $info);
    }

    // 主机登录
    public function host_login()
    {
        $account = $this->request->param('account');
        $password = $this->request->param('password');
        $id = $this->request->param('id/d');

        // 传参验证
        $validate = $this->validate([
            'account'  => $account,
            'password' => $password,
            'id'       => $id,
        ], [
            'account'  => 'require|length:3,50',
            'password' => 'require|length:6,30',
            'id'       => 'number',
        ], [
            'account.require'  => 'Account can not be empty',
            'account.length'   => 'Account must be 3 to 50 characters',
            'password.require' => 'Password can not be empty',
            'password.length'  => 'Password must be 6 to 30 characters',
            'id.number'        => '主机ID格式错误',
        ]);

        if ($validate !== true) {
            if ($this->request->isAjax()) {
                $this->error(__($validate), url('/'));
            }
            return redirect('/');
        }

        // 登录用户
        $userAuth = new \app\common\library\Auth();

        if ($userAuth->login($account, $password)) {
            if ($this->request->isAjax()) {
                $this->success(__('Logged in successful'), url('/'));
            } else {
                if ($id) {
                    $hostInfo = model('Host')::get($id);
                    if (!$hostInfo) {
                        $this->error('没有找到有效主机', url('/'));
                    } else {
                        // cookie切换到主机id
                        Cookie::set('host_id_' . $hostInfo->user_id, $hostInfo->id);
                    }
                }
                Cookie::set('uid', $userAuth->id);
                Cookie::set('token', $userAuth->getToken());
                // cookie切换到主机id
                Cookie::set('host_id_' . $hostInfo->user_id, $hostInfo->id);

                // 跳转首页控制台
                return redirect('/');
            }
        } else {
            $this->error($userAuth->getError(), null, ['token' => $this->request->token()]);
        }
    }

    // 主机回收站（软删除）
    public function host_recycle()
    {
        $id = $this->request->post('id/d');
        if (!$id) {
            $this->error('请求错误');
        }
        $hostFind = $this->getHostInfo($id);
        $this->hostModel::destroy($id);
        $this->success('已回收');
    }

    // 主机回收站恢复
    public function host_recovery()
    {
        $id = $this->request->post('id/d');
        if (!$id) {
            $this->error('请求错误');
        }
        $hostFind = $this->hostModel::withTrashed()->where(['id' => $id])->find();
        if (!$hostFind) {
            $this->error('主机不存在');
        }
        // 连接宝塔启用站点
        $bt = new Btaction();
        $bt->bt_id = $hostFind->bt_id;
        $bt->bt_name = $hostFind->bt_name;
        $set = $bt->webstart();
        if (!$set) {
            $this->error($bt->_error);
        }
        $hostFind->deletetime = null;
        $hostFind->save();
        $this->success('已恢复');
    }

    // 修改密码
    public function host_pass()
    {
        $id = $this->request->post('id/d');
        $type = $this->request->post('type', 'all');
        $password = $this->request->post('password', Random::alnum(12));
        if (!$id) {
            $this->error('错误的请求');
        }
        $bt = new Btaction();

        if ($type == 'ftp' || $type == 'all') {
            $ftpFind = model('Ftp')::get(['vhost_id' => $id]);
            $ftpFind->password = $password;
            $ftpFind->save();
        }
        if ($type == 'host' || $type == 'all') {
            $hostFind = $this->getHostInfo($id);
            $userInfo = model('User')::get($hostFind->user_id);
            if (!$userInfo) {
                $this->error('无此用户');
            }
            $userInfo->password = $password;
            $userInfo->save();
        }

        $this->success('请求成功', ['password' => $password]);
    }

    // 主机停用
    public function host_stop()
    {
        $id = $this->request->post('id/d');
        if (!$id) {
            $this->error('错误的请求');
        }
        $hostFind = $this->getHostInfo($id);
        $hostFind->status = 'stop';
        $hostFind->save();
        $this->success('主机已停用');
    }

    // 主机锁定
    public function host_locked()
    {
        $id = $this->request->post('id/d');
        if (!$id) {
            $this->error('错误的请求');
        }
        $hostFind = $this->getHostInfo($id);
        $hostFind->status = 'locked';
        $hostFind->save();
        $this->success('主机已锁定');
    }

    // 主机启用
    public function host_start()
    {
        $id = $this->request->post('id/d');
        if (!$id) {
            $this->error('错误的请求');
        }
        $hostFind = $this->getHostInfo($id);
        $hostFind->status = 'normal';
        $hostFind->save();
        $this->success('主机已开启');
    }

    // 主机运行状态
    public function host_status()
    {
        // 获取本地及服务器中站点运行状态
        $id = $this->request->post('id/d');
        if (!$id) {
            $this->error('错误的请求');
        }
        $hostFind = $this->getHostInfo($id);
        $bt = new Btaction();
        $bt->bt_name = $hostFind->bt_name;
        $bt->bt_id = $hostFind->bt_id;
        $hostInfo = $bt->getSiteInfo();
        if (!$hostInfo) {
            $this->error($bt->_error);
        }

        // normal:正常,stop:停止,locked:锁定,expired:过期,excess:超量,error:异常
        $this->success('请求成功', ['loca' => $hostFind->status, 'server' => $hostInfo['status']]);
    }

    // 主机同步
    public function host_sync()
    {
        // 用于同步主机状态、到期时间、宝塔ID
        $id = $this->request->post('id/d');
        if (!$id) {
            $this->error('错误的请求');
        }
        $hostFind = $this->getHostInfo($id);

        $bt = new Btaction();
        $bt->bt_name = $hostFind->bt_name;
        $hostInfo = $bt->getSiteInfo();
        if (!$hostInfo) {
            $this->error($bt->_error);
        }
        $btid = $hostInfo['id'];
        $edate = $hostInfo['edate'];
        $status = $hostInfo['status'];

        $bt->bt_id = $btid;
        if ($btid != $hostFind->bt_id) {
            // 同步宝塔ID到本地
            $hostFind->bt_id = $btid;
        }

        // 同步状态到本地
        if ($hostFind->status == 'normal' && $status != 1) {
            $bt->webstart();
            $status = '1';
        } elseif ($hostFind->status != 'normal' && $status == 1) {
            $bt->webstop();
            $status = '0';
        }

        $hostFind->save();

        // 同步本地到期时间到云端
        $localDate = date('Y-m-d', $hostFind->endtime);
        if ($edate != $localDate) {
            $set = $bt->setEndtime($btid, $localDate);
            if (!$set) {
                $this->error($bt->_error);
            }
        }

        $this->success('同步成功', ['bt_id' => $btid, 'endtime' => $localDate, 'status' => $status, 'hostStatus' => $hostFind->status]);
    }

    // 主机资源稽核，超停
    public function host_resource()
    {
        // 用于返回和同步主机资源：数据库、流量、站点
        $id = $this->request->post('id/d');
        if (!$id) {
            $this->error('错误的请求');
        }
        $hostFind = $this->getHostInfo($id);

        $sqlFind = model('Sql')::get(['vhost_id' => $id]);
        if ($sqlFind && $sqlFind->username) {
            $sql_name = $sqlFind->username;
        } else {
            $sql_name = '';
        }

        $bt = new Btaction();
        $bt->bt_name = $hostFind->bt_name;
        $bt->bt_id = $hostFind->bt_id;
        $bt->sql_name = $sql_name;
        $size = $bt->getResourceSize();

        $hostFind->site_size = $size['websize'];
        $hostFind->flow_size = $size['total_size'];
        $hostFind->sql_size = $size['sqlsize'];


        $overflow = 0;
        if ($hostFind->sql_max != 0 && $hostFind->sql_size > $hostFind->sql_max) {
            $overflow = 1;
        }
        if (
            $hostFind->site_max != 0 && $hostFind->site_size > $hostFind->site_max
        ) {
            $overflow = 1;
        }
        if (
            $hostFind->flow_max != 0 && $hostFind->flow_size > $hostFind->flow_max
        ) {
            $overflow = 1;
        }

        if ($overflow) {
            $hostFind->status = 'excess';
        } else {
            // 判断既没有过期，也处于没有超量状态，就恢复主机
            if (
                $hostFind->endtime > time() && $hostFind->status == 'excess'
            ) {
                $hostFind->status = 'normal';
            }
        }
        $hostFind->check_time = time();

        $hostFind->allowField(true)->save();

        $max = [
            'site' => $hostFind->site_max,
            'flow' => $hostFind->flow_max,
            'sql'  => $hostFind->sql_max,
        ];

        $this->success('请求成功', ['size' => $size, 'max' => $max]);
    }

    // 主机信息修改
    public function host_edit()
    {
        $id = $this->request->post('id/d');
        $sort_id = $this->request->post('sort_id/d');
        $is_audit = $this->request->post('is_audit/d');
        $endtime = $this->request->post('endtime');
        // 传递整数型，单位M
        $site_max = $this->request->post('site_max/d');
        $flow_max = $this->request->post('flow_max/d');
        $sql_max = $this->request->post('sql_max/d');
        $domain_max = $this->request->post('domain_max/d');
        $web_back_num = $this->request->post('web_back_num/d');
        $sql_back_num = $this->request->post('sql_back_num/d');
        $sub_bind = $this->request->post('sub_bind/d');
        $status = $this->request->post('status');
        if (!$id) {
            $this->error('请求错误');
        }
        // 修改内容包含：空间大小、数据库大小、流量大小、域名绑定数、网站备份数、数据库备份数
        $hostInfo = $this->getHostInfo($id);
        if ($site_max != '' && $site_max != null) {
            $hostInfo->site_max = $site_max;
        }
        if ($sql_max != '' && $sql_max != null) {
            $hostInfo->sql_max = $sql_max;
        }
        if ($flow_max != '' && $flow_max != null) {
            $hostInfo->flow_max = $flow_max;
        }
        if ($domain_max != '' && $domain_max != null) {
            $hostInfo->domain_max = $domain_max;
        }
        if ($web_back_num != '' && $web_back_num != null) {
            $hostInfo->web_back_num = $web_back_num;
        }
        if ($sql_back_num != '' && $sql_back_num != null) {
            $hostInfo->sql_back_num = $sql_back_num;
        }
        if ($sort_id) {
            $hostInfo->sort_id = $sort_id;
        }
        if ($sub_bind != '' && $sub_bind != null) {
            $hostInfo->sub_bind = $sub_bind;
        }
        if ($is_audit != '' && $is_audit != null) {
            $hostInfo->is_audit = $is_audit;
        }
        if ($status != '' && $status != null) {
            $hostInfo->status = $status;
        }
        if ($endtime) {
            if (date('Y-m-d', strtotime($endtime)) !== $endtime) {
                $this->error('时间格式错误，请严格按照Y-m-d格式传递');
            }
            $hostInfo->endtime = $endtime;
        }

        $hostInfo->save();
        $this->success('更新成功', $hostInfo);
    }

    // 主机修改套餐
    public function host_update()
    {
        $id = $this->request->post('id/d');
        $plan_id = $this->request->post('plan_id/d');

        if (!$id || !$plan_id) {
            $this->error('错误的请求');
        }
        $hostInfo = $this->getHostInfo($id);
        if (!$hostInfo) {
            $this->error('主机不存在');
        }
        $plansInfo = model('Plans')::get($plan_id);
        if (!$plansInfo) {
            $this->error('套餐不存在');
        }
        $plansInfo = json_decode($plansInfo->value);
        // var_dump($plansInfo->site_max, $plansInfo->perserver);
        // exit;
        $hostInfo->site_max = $plansInfo->site_max;
        $hostInfo->sql_max = $plansInfo->sql_max;
        $hostInfo->flow_max = $plansInfo->flow_max;
        $hostInfo->domain_max = $plansInfo->domain_num;
        $hostInfo->web_back_num = $plansInfo->web_back_num;
        $hostInfo->sql_back_num = $plansInfo->sql_back_num;
        $hostInfo->is_audit = $plansInfo->domain_audit;
        // 升级并发、限速
        $hostInfo->perserver = $plansInfo->perserver;
        $hostInfo->limit_rate = $plansInfo->limit_rate;

        $hostInfo->allowField(true)->save();
        $this->success('更新成功', $hostInfo);
    }

    // 主机域名绑定
    public function host_domain()
    {
        $id = $this->request->post('id/d');
        $domain = $this->request->post('domain');
        $dirs = $this->request->post('dirs', '/');
        $is_audit = $this->request->post('is_audit', 0);
        if (!$id || !$domain) {
            $this->error('错误的请求');
        }
        $hostInfo = model('Host')::get($id);
        if (!$hostInfo) {
            $this->error('没有找到有效主机');
        }
        $data = [
            'vhost_id' => $id,
            'domain'   => $domain,
            'dir'      => $dirs,
            'status'   => $is_audit ? 0 : 1,
        ];

        $sub_bind = isset($hostInfo->sub_bind) && $hostInfo->sub_bind ? 1 : 0;
        // 限制绑定根目录
        if ($sub_bind != 1 && $dirs != '/') {
            $this->error('绑定的目录错误');
        }

        \app\common\model\Domainlist::event('before_insert', function ($data) {
            if ($data->status == 1) {
                $hostInfo = model('Host')::get($data->vhost_id);
                if ($data->dir == '/') {
                    $isdir = 0;
                    $name = $hostInfo->bt_name;
                } else {
                    $isdir = 1;
                    $name = $data->dir;
                }

                // 连接宝塔绑定域名
                $bt = new Btaction();
                $bt->bt_id = $hostInfo->bt_id;
                $add = $bt->addDomain($data->domain, $name, $isdir);
                if (!$add) {
                    $this->error($bt->_error);
                    return false;
                }
            }
        });
        $domainInfo = model('Domainlist')::create($data);
        $this->success('添加成功', $domainInfo);
    }

    // 主机绑定IP
    public function host_bindip()
    {
        $id = $this->request->post('id/d');
        $ip_id = $this->request->post('ip_id/d');
        if (!$id || !$ip_id) {
            $this->error('错误的请求');
        }
        $hostInfo = $this->getHostInfo($id);
        $ipInfo = model('Ipaddress')::get($ip_id);
        if (!$ipInfo) {
            $this->error('IP不存在');
        }
        // 判断是否已经绑定该IP
        $ip_list = explode(',', $hostInfo->getData('ip_address'));
        if (in_array($ip_id, $ip_list)) {
            $this->error('已绑定');
        }
        $ip_list = array_filter($ip_list);
        array_push($ip_list, [$ip_id]);
        $ip_str = implode(',', $ip_list);
        $hostInfo->ip_address = $ip_str;
        $hostInfo->save();
        $this->success('绑定成功', $hostInfo->ip_address);
    }

    // 主机解绑IP
    public function host_unbindip()
    {
        $id = $this->request->post('id/d');
        $ip_id = $this->request->post('ip_id/d');
        $hostInfo = $this->getHostInfo($id);
        $ipInfo = model('Ipaddress')::get($ip_id);
        if (!$ipInfo) {
            $this->error('IP不存在');
        }
        $ip_list = explode(',', $hostInfo->getData('ip_address'));
        if (!in_array($ip_id, $ip_list)) {
            $this->error('未绑定该IP');
        }
        // 清除空值
        $ip_list = array_filter($ip_list);
        $key = array_search($ip_id, $ip_list);
        array_splice($ip_list, $key);
        $ip_str = implode(',', $ip_list);
        $hostInfo->ip_address = $ip_str;
        $hostInfo->save();
        $this->success('已解除绑定', $hostInfo->ip_address);
    }

    // 到期时间修改
    public function host_endtime()
    {
        $id = $this->request->post('id/d');
        if (!$id) {
            $this->error('请求错误');
        }
        $endtime = $this->request->post('endtime');

        if (date('Y-m-d', strtotime($endtime)) !== $endtime) {
            $this->error('时间格式错误，请严格按照Y-m-d格式传递');
        }
        $hostInfo = $this->getHostInfo($id);
        $bt = new Btaction();
        $bt->bt_id = $hostInfo->bt_id;
        $set = $bt->setEndtime($endtime);
        if (!$set) {
            $this->error($this->_error);
        }
        $hostInfo->endtime = strtotime($endtime);
        $hostInfo->save();
        $this->success('更新成功', $endtime);
    }

    // 主机限速设置
    public function host_speed()
    {
        // 仅支持Nginx环境
        $id = $this->request->post('id/d');
        // 限制当前站点最大并发数
        $perserver = $this->request->post('perserver/d');
        // 限制每个请求的流量上限（单位：KB）
        $limit_rate = $this->request->post('limit_rate/d');
        if (!$id || !$perserver || !$limit_rate) {
            $this->error('错误的请求');
        }
        $hostFind = $this->getHostInfo($id);
        $bt = new Btaction();
        $bt->bt_id = $hostFind->bt_id;
        $data = ['perserver' => $perserver, 'limit_rate' => $limit_rate];
        $set = $bt->setLimit($data);
        if (!$set) {
            $this->error($bt->_error);
        }
        $this->success('设置成功', $data);
    }

    // 主机限速停止
    public function host_speedoff()
    {
        $id = $this->request->post('id/d');
        if (!$id) {
            $this->error('错误的请求');
        }
        $hostFind = $this->getHostInfo($id);
        $bt = new Btaction();
        $bt->bt_id = $hostFind->bt_id;
        $set = $bt->closeLimit();
        if (!$set) {
            $this->error($bt->_error);
        }
        $this->success('已关闭限速');
    }

    // 主机备注修改
    public function host_notice()
    {
        $id = $this->request->post('id/d');
        $notice = $this->request->post('text');
        if (!$id || !$notice) {
            $this->error('错误的请求');
        }
        $hostFind = $this->getHostInfo($id);
        // 不修改宝塔备注
        // $bt = new Btaction();
        // $bt->bt_name = $hostFind->bt_name;
        // $bt->bt_id = $hostFind->bt_id;
        // $set = $bt->setPs($notice);
        // if(!$set){
        //     $this->error($bt->_error);
        // }
        $hostFind->notice = $notice;
        $hostFind->save();
        $this->success('修改成功');
    }

    /**
     * 获取主机信息
     *
     * @param [type] $id
     * @return obj
     */
    private function getHostInfo($id)
    {
        $hostFind = $this->hostModel::get($id);
        if (!$hostFind) {
            $this->error('主机不存在');
        }
        return $hostFind;
    }

    // 签名验证
    private function token_check()
    {
        // TODO 上线需要验证签名
        // return true;

        // TODO 接口签名需要传递具体要调用的接口方法，防止跨方法传递，导致安全问题，或者研究更好的签名方案
        // 时间戳
        $time = $this->request->param('time/d');
        $signature_time = Config::get('site.signature_time') ? Config::get('site.signature_time') : 10;

        // 随机数
        $random = $this->request->param('random');
        // 签名
        $signature = $this->request->param('signature');

        if (!$time || !$random || !$signature) {
            throw new \Exception(__('Signature fail'));
        }

        if ((time() - $time) > $signature_time) {
            throw new \Exception(__('Signature expired'));
        }

        $data = [
            'time'         => $time,
            'random'       => $random,
            'access_token' => $this->access_token,
        ];

        sort($data, SORT_STRING);
        $str = implode($data);
        $sig_key = md5($str);
        $sig_key = strtoupper($sig_key);

        if ($sig_key === $signature) {
            return true;
        } else {
            throw new \Exception(__('Signature fail'));
        }
    }

    private function ft_msg($title, $content)
    {

        $ft = new Ftmsg(Config::get('site.ftqq_sckey'));
        $ft->setTitle($title);
        $ft->setMessage($content);
        $ft->sslVerify();
        $message = new Message($ft);
        $result = $message->send();
        return true;
    }
}

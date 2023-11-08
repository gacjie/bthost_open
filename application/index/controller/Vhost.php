<?php

namespace app\index\controller;

use app\common\controller\Frontend;
use app\common\library\Btaction;
use app\common\model\Sql;
use app\common\model\Host;
use app\common\model\Ftp;
use think\Config;
use think\Cookie;
use think\Session;
use think\Validate;
use btpanel\Btpanel;
use think\Debug;
use think\Db;
use think\Cache;
use think\Hook;

/**
 * 控制中心
 */
class Vhost extends Frontend
{
    protected $layout = 'default';
    protected $noNeedLogin = ['login', 'register'];
    protected $noNeedRight = ['logout', 'clear_cache'];

    /**
     * 宝塔ID
     */
    private $bt_id;
    /**
     * 主机ID
     */
    private $vhost_id;
    /**
     * @var Btaction|null
     */
    private $btAction;
    /**
     * @var Btpanel|null
     */
    private $btPanel;

    /**
     * 全局安全规则
     * @var string
     */
    private $global_reg = "/(;|{|}|\|)/"; // TODO 全局安全规则待完善
    // 路径/目录安全规则
    private $path_reg = "/([.]){2,}/";
    // 伪静态安全规则
    private $reg_rewrite = "/(root|alias|_by_lua|http_upgrade)/";
    // 文件/文件夹过滤规则
    private $reg_file = "/(web_config|web.config|.user.ini)/";
    // 资源检查缓存时间3600=1h
    private $check_time = 3600;
    /**
     * 主机信息聚合
     *
     * @var object|bool|array
     */
    private $hostInfo;

    // 错误收集
    private $_error;


    // 服务器操作系统类型linux windows
    private $server_type = 'linux';
    // 服务器面板配置
    private $serverConfig;
    /**
     * 资源超出停用面板
     * @var int
     */
    public $is_excess_stop = 0;
    /**
     * 站点名
     * @var array|bool|float|int|mixed|object|\stdClass|null
     */
    private $siteName;
    /**
     * @var Host
     */
    private $hostModel;
    /**
     * @var Ftp
     */
    private $ftpModel;
    /**
     * @var Sql
     */
    private $sqlModel;

    public function _initialize()
    {
        Debug::remark('begin');
        parent::_initialize();
        $this->hostModel = model('Host');
        $this->ftpModel = model('Ftp');
        $this->sqlModel = model('Sql');
        $host_id = Cookie::get('host_id_' . $this->auth->id);
        if (!$host_id) return $this->redirect('/sites');
        $hostInfo = $this->hostModel::get(['user_id' => $this->auth->id, 'id' => $host_id]);
        if (!$hostInfo) $this->error(__('Site Not found<a href="%s">Switch site</a>', url('index/user/index')), '');
        $ftpInfo = $this->ftpModel::get(['vhost_id' => $hostInfo->id, 'status' => 'normal']);
        $sqlInfo = $this->sqlModel::get(['vhost_id' => $hostInfo->id, 'status' => 'normal']);
        $hostInfo->ftp = $ftpInfo ? $ftpInfo : '';
        $hostInfo->sql = $sqlInfo ? $sqlInfo : '';

        // 用户信息表
        $userInfo = $this->auth->getUserinfo();
        // 主机信息表
        $this->hostInfo = $hostInfo;
        // 验证主机是否过期
        if (time() > $this->hostInfo->endtime) {
            // 更新主机状态
            $this->hostInfo->allowField(true)->save(['status' => 'expired']);
            $this->error(__('Site is %s', __('expired')), '/sites');
        }

        $this->is_excess_stop = Config('site.excess_panel');

        // 状态甄别
        switch ($this->hostInfo->status) {
            case 'stop':
            case 'normal':
                break;
            case 'locked':
                $this->error(__('Site is %s', __('locked')), '/sites');
                break;
            case 'expired':
                $this->error(__('Site is %s', __('expired')), '/sites');
                break;
            case 'excess':
                $this->is_excess_stop ? $this->error(__('Site is %s', __('excess')), '') : null;
                break;
            case 'error':
            default:
                $this->error(__('Site is %s', __('error')), '/sites');
                break;
        }

        $this->btAction = new Btaction();
        $this->btPanel = $this->btAction->btPanel;
        $this->hostInfo->server_os = $this->server_type = $this->btAction->os;

        // 信息初始化
        $this->btAction->ftp_name = isset($hostInfo->ftp->username) ? $hostInfo->ftp->username : '';
        $this->btAction->sql_name = isset($hostInfo->sql->username) ? $hostInfo->sql->username : '';
        // 站点名
        $this->btAction->bt_name = $this->siteName = $this->hostInfo->bt_name;
        // 宝塔站点id
        $this->btAction->bt_id = $this->bt_id = $this->hostInfo->bt_id;
        // 主机ID
        $this->vhost_id = $this->hostInfo->id;

        // 获取并设置服务器配置
        $server_config = Cache::remember('vhost_config', function () {
            return $server_config = $this->btAction->clear_config();
        });
        $this->serverConfig = $this->btAction->serverConfig = $server_config;

        // 站点初始化
        $webInit = $this->btAction->webInit();
        if (!$webInit) {
            $this->error($this->btAction->bt_name . $this->btAction->_error . ' <a href="' . url('index/user/index') . '">' . __('Switch site') . '</a>', '');
        }
        // 检查资源使用量
        if (!$this->check()) $this->is_excess_stop ? $this->error($this->_error, '') : null;
        // php加载时长
        $this->assign('rangeTime', Debug::getRangeTime('begin', 'end') . 's');
        // 主机信息（数据库）
        $this->assign('hostInfo', $this->hostInfo);
        // 主机信息（宝塔面板）
        $this->assign('hostBtInfo', $this->btAction->hostBtInfo);
        // 用户信息
        $this->assign('userInfo', $userInfo);
        // 服务器配置
        $this->assign('serverConfig', $server_config);
    }

    // 首页
    public function index()
    {
        $this->view->assign('title', __('Console center'));
        return $this->view->fetch();
    }

    // 控制台
    public function main()
    {
        $phpVer = isset($this->btAction->hostBtInfo['php_version']) && $this->btAction->hostBtInfo['php_version'] ? str_replace(".", "", $this->btAction->hostBtInfo['php_version']) : $this->btAction->getSitePhpVer($this->siteName);
        $siteStatus = isset($this->btAction->hostBtInfo['status']) && $this->btAction->hostBtInfo['status'] ? $this->btAction->hostBtInfo['status'] : $this->btAction->getSiteStatus($this->siteName);
        if (isset($this->hostInfo->ftp->username) && $this->hostInfo->ftp->username) {
            $ftpInfo = $this->btAction->getFtpInfo();
            if (!$ftpInfo) {
                $ftpInfo = false;
            }
        } else {
            $ftpInfo = false;
        }

        $phpversion_list = Cache::remember('phpversion_list', function () {
            return $this->btPanel->GetPHPVersion();
        });

        // 转换资源百分比
        $site_getround = $this->hostInfo->site_max != 0 ? getround($this->hostInfo->site_max, $this->hostInfo->site_size) : 0;
        $sql_getround = $this->hostInfo->sql_max != 0 ? getround($this->hostInfo->sql_max, $this->hostInfo->sql_size) : 0;
        $flow_getround = $this->hostInfo->flow_max != 0 ? getround($this->hostInfo->flow_max, $this->hostInfo->flow_size) : 0;

        // 转换可用资源单位
        $site_max = $this->hostInfo->site_max != 0 ? format_megabyte($this->hostInfo->site_max) : __('∞');
        $sql_max = $this->hostInfo->sql_max != 0 ? format_megabyte($this->hostInfo->sql_max) : __('∞');
        $flow_max = $this->hostInfo->flow_max != 0 ? format_megabyte($this->hostInfo->flow_max) : __('∞');

        // 转换已用资源单位
        $site_size = format_megabyte($this->hostInfo->site_size);
        $sql_size = format_megabyte($this->hostInfo->sql_size);
        $flow_size = format_megabyte($this->hostInfo->flow_size);


        // 数据库管理地址
        $this->assign('phpmyadmin', Config('site.phpmyadmin'));

        $this->view->assign(compact('ftpInfo', 'phpVer', 'phpVer', 'siteStatus', 'site_getround', 'sql_getround', 'flow_getround', 'phpversion_list', 'site_max', 'sql_max', 'flow_max', 'site_size', 'sql_size', 'flow_size'));

        $this->view->assign('userLocked', $this->btAction->userini_status);
        $this->view->assign('title', __('Console center'));
        return $this->view->fetch();
    }

    // 清除缓存
    public function clear_cache()
    {
        // 清除waf类型缓存
        Cache::rm('getWaf');
        // 清除Proof类型缓存
        Cache::rm('getProof');
        // 清除php版本列表缓存
        Cache::rm('phpversion_list');
        // 清除伪静态规则缓存
        // Cache::rm('phpversion_list');
        // 清除服务器配置
        Cache::rm('vhost_config');
        // 清除加速规则
        Cache::rm('speed_rule_list');
        // 清除加速规则列表
        Cache::rm('speed_rule_name_list');
        // 清除资源使用检测缓存
        Cookie::set('vhost_check_' . $this->vhost_id, null);

        $this->success(__('Clear success'), '');
    }

    // 站点重置
    public function hostreset()
    {
        if ($this->request->isPost()) {
            try {
                // 创建站点重建记录
                $c = \app\common\model\HostresetLog::create([
                    'user_id' => $this->auth->id,
                    'host_id' => $this->hostInfo->id,
                    'bt_id'   => $this->bt_id,
                    'info'    => json_encode($this->hostInfo)
                ]);
                // 删除原有站点信息，保留数据库、ftp账号，清空域名绑定详情
                $del = $this->btAction->siteDelete($this->btAction->bt_id, $this->btAction->bt_name, 0, 0, 1);
                if (!$del) {
                    throw new \Exception(__('Fail') . '.' . $this->btAction->_error);
                }
                $arr = explode('.', $this->btAction->bt_name);
                $name = isset($arr[0]) ? $arr[0] : \fast\Random::alnum();
                // 创建一个新的站点，保持原有信息一致性，如空间大小，IP等
                $hostSetArr = [
                    'domains'   => $this->btAction->bt_name,
                    'WebGetKey' => $this->btPanel->WebGetKey($this->btAction->bt_id),
                    // 'ftp' => 1,
                    'site_max'  => $this->hostInfo->site_max,
                    'sql_max'   => $this->hostInfo->sql_max,
                    'flow_max'  => $this->hostInfo->flow_max,
                ];
                $hostSetInfo = $this->btAction->setInfo(['username' => $name], $hostSetArr);

                // 删除原有域名绑定信息
                \app\common\model\Domainlist::where(['vhost_id' => $this->hostInfo->id])->where('domain', '<>', $this->btAction->bt_name)->delete();

                $crea = $this->btAction->btBuild($hostSetInfo);
                if (!$crea) {
                    throw new \Exception(__('Fail') . '.' . $this->btAction->_error);
                }
                // 将数据库中站点信息变更为新站点信息，btid，站点名等
                $this->hostInfo->allowField(true)->save([
                    'bt_name' => $this->btAction->bt_name,
                    'bt_id'   => $crea['siteId'],
                ]);

                \app\common\model\HostresetLog::where(['id' => $c->id])->update([
                    'status'      => 1,
                    'new_host_id' => $this->hostInfo->id,
                    'new_bt_id'   => $crea['siteId'],
                ]);
                $msg = true;
            } catch (\Exception $e) {
                $msg = __('Fail') . '.' . $e->getMessage();
            }
            if ($msg === true) {
                $this->success(__('Success'));
            } else {
                $this->success($msg);
            }
        }
        return $this->view->fetch();
    }

    // 设置PHP版本
    public function phpSet()
    {
        $phpVer = input('post.ver');
        if (!$phpVer) $this->error(__('%s can not be empty', 'php'));
        $a = false;
        $phpversion_list = $this->btPanel->GetPHPVersion();
        foreach ($phpversion_list as $key => $value) {
            if (in_array($phpVer, $phpversion_list[$key])) {
                $a = true;
                break;
            } else {
                $a = false;
            }
        }
        if (!$a) $this->error(__('Not currently supported %s', $phpVer));

        $setPHP = $this->btAction->setPhpVer($phpVer);
        if (!$setPHP) $this->error($this->btAction->_error);
        $this->success(__('Change success'));
    }

    // 网站停止运行
    public function webStop()
    {
        // 先判断站点状态，防止多次重复操作
        $set = $this->btAction->webstop();
        if (!$set) $this->error($this->btAction->_error);
        $this->hostInfo->allowField(true)->save([
            'status' => 'stop'
        ]);
        $this->success(__('Success'));
    }

    // 网站开启
    public function webStart()
    {
        // 判断主机状态
        switch ($this->hostInfo->status) {
            case 'stop':
            case 'normal':
                break;
            case 'locked':
                $this->error(__('Site is %s', __('locked')), '');
                break;
            case 'expired':
                $this->error(__('Site is %s', __('expired')), '');
                break;
            case 'excess':
                $this->error(__('Site is %s', __('excess')), '');
                break;
            case 'error':
            default:
                $this->error(__('Site is %s', __('error')), '');
                break;
        }
        // 先判断站点状态，防止多次重复操作
        $set = $this->btAction->webstart();
        if (!$set) $this->error($this->btAction->_error);
        $this->hostInfo->allowField(true)->save([
            'status' => 'normal'
        ]);
        $this->success(__('Success'));
    }

    // 域名绑定
    public function domain()
    {
        //获取域名绑定列表
        $domainList = $this->btAction->getSiteDomain();
        //获取子目录绑定信息
        $dirList = $this->btAction->getSiteDirBinding();

        $sub_bind = 0;
        if (Config::get('vhost.sub_bind')) {
            $sub_bind = isset($this->hostInfo->sub_bind) && $this->hostInfo->sub_bind ? 1 : 0;
        }

        // 剩余可绑定数

        // 获取未审核的域名
        $auditList = model('Domainlist')->where('status', '<>', 1)->where('vhost_id', $this->hostInfo->id)->select();
        // 获取未备案域名
        $not_beianList = model('DomainBeian')->where('status', 'normal')->where('vhost_id', $this->hostInfo->id)->select();

        $count = count($domainList) + count($dirList['binding']) + count($auditList);
        $sys = $this->hostInfo->domain_max - $count - count($not_beianList) + 1;

        $this->view->assign('title', __('domain'));
        $this->view->assign(compact('sys', 'count', 'dirList', 'sub_bind', 'domainList', 'auditList', 'not_beianList'));
        return $this->view->fetch();
    }

    // 增加域名绑定
    public function incDomain()
    {
        $domains = $this->request->post('domain');

        $dirs = $this->request->post('dirs', '/');

        // 限制绑定子目录
        $sub_bind = 0;
        if (Config::get('vhost.sub_bind')) {
            $sub_bind = isset($this->hostInfo->sub_bind) && $this->hostInfo->sub_bind ? 1 : 0;
        }
        // 限制绑定根目录
        if ($sub_bind != 1 && $dirs != '/') $this->error(__('Bind directory error'));

        // 判断域名是否为空
        if (empty($domains)) $this->error(__('%s can not be empty', __('Domain')));

        // 非法参数过滤
        if (preg_match($this->global_reg, $dirs) || preg_match($this->global_reg, $domains)) {
            $this->error(__('Illegal parameter'));
        }

        $domain_list = trim($domains);
        $domain_arr = explode("\n", $domain_list);

        $domainCount = model('Domainlist')->where('vhost_id', $this->vhost_id)->count();

        $notbeian_count = model('DomainBeian')->where('vhost_id', $this->vhost_id)->where('status', 'normal')->count();

        // 数据库中已有数 + 准备绑定的域名数 + 未备案的域名
        $x_domain_count = count($domain_arr) + $domainCount + $notbeian_count - 1;
        // 绑定数限制
        if ($this->hostInfo->domain_max != 0 && $x_domain_count > $this->hostInfo->domain_max) {
            $this->error(__('Exceed the number of available domain name bindings %s', $this->hostInfo->domain_max));
        }
        $successArr = $errorArr = $not_beian = $new_domainlist = $is_exit = [];
        foreach ($domain_arr as $key => $value) {
            $status = $domain_pass = 0;

            // 判断当前绑定域名是否存在数据库中
            $domain_find = model('Domainlist')->where('domain', $value)->find();
            if ($domain_find) {
                $errorArr[] = __('%s domain has been bound', $value);
                continue;
            }

            // 泛解析错误格式拦截
            $isnot_fjx = preg_match('/\*[a-zA-Z0-9]/', $value);
            if ($isnot_fjx) {
                $errorArr[] = __('%s domain format is incorrect, please adjust and resubmit', $value);
                continue;
            }

            // 正则匹配法
            $isnotall = preg_match('/\*\.([a-zA-Z0-9]+[^.])$/', $value);
            if ($isnotall) {
                $errorArr[] = __('%s domain format is incorrect, please adjust and resubmit', $value);
                continue;
            }

            // 禁止绑定端口
            if (strpos($value, ":")) {
                $errorArr[] = __('%s domain format is incorrect, please adjust and resubmit', $value);
                continue;
            }

            // 拆分数组法匹配
            // $isnotall = explode('.',$value);
            // var_dump($isnotall);
            // if($isnotall){
            //     if(count($isnotall)<=2&&$isnotall[0]=='*'){
            //         var_dump(false);
            //     }else{
            //         var_dump(true);
            //     }
            // }else{
            //     var_dump(false);
            // }

            // 域名黑白名单模块
            $domainblock = model('DomainBlock')->where(['status' => 'normal', 'domain' => $value])->find();
            if ($domainblock) {
                if ($domainblock->type == 'block') {
                    $errorArr[] = __('%s domain cannot be bound', $value);
                    continue;
                } else {
                    // 跳过备案检测、域名手动审核
                    $domain_pass = 1;
                }
            }

            if ($domain_pass) {
                $status = 1;
            } elseif ($this->hostInfo->is_audit == 0) {
                $status = 1;
            } else {
                $errorArr[] = __('%s please wait for review', $value);
                $status = 0;
            }

            // 备案检测
            $search_check = model('DomainBeian')->where(['domain' => $value])->where('status', '<>', 'normal')->find();
            if (Config::get('site.ask_beian') && Config::get('beian_siteinfo.bt_id') && Config::get('beian_siteinfo.bt_name') && !$search_check && !$domain_pass) {
                $is_beian = \app\common\library\Common::beian_check($value);
                if (!$is_beian) {
                    $not_beian[] = $value;
                    $errorArr[] = __('%s domain cannot be beian', $value);
                    $status = 3;
                    $is_icp_exit = Config::get('beian_siteinfo.is_icp_exit') ?: 0; // 未备案是否允许绑定
                } else {
                    // 写入备案表
                    model('DomainBeian')::create(
                        [
                            'vhost_id'   => $this->vhost_id,
                            'bt_id'      => $this->bt_id,
                            'bt_name'    => $this->hostInfo->bt_name,
                            'dir'        => $dirs,
                            'status'     => 'auto',
                            'domain'     => $value,
                            'beian_info' => json_encode($is_beian),
                        ]
                    );
                }
            }

            if ($status != 3) {
                // 添加到数据库中
                $data = [
                    'vhost_id' => $this->vhost_id,
                    'domain'   => $value,
                    'dir'      => $dirs,
                    'status'   => $status,
                ];
                model('Domainlist')::create($data);
            }

            if ($status !== 1) {
                // 站长通知
                $tz_data = ['username' => $this->auth->username, 'domain' => $value, 'bt_name' => $this->hostInfo->bt_name];
                Hook::listen('action_domain_check_msg', $tz_data);
                continue;
            }
            $successArr[] = $value;
        }

        //获取域名绑定列表
        // $domainList = $this->btAction->getSiteDomain();
        //获取子目录绑定信息
        // $dirList = $this->btAction->getSiteDirBinding();
        // 读取默认域名，防止被恶意使用泛解析域名
        // $defaultDomain = $this->hostInfo['default_domain'];
        // 读取用户绑定的泛解析域名，防止被恶意使用该泛解析域名
        // 之后从长计议

        // 子目录处理
        if ($dirs == '/') {
            $isdir = 0;
            $name = $this->siteName;
        } else {
            $isdir = 1;
            $name = $dirs;
        }

        if ($not_beian && $is_exit) {
            // 绑定未备案站点
            $not_beian_domain_str = implode(',', $not_beian);
            $this->btAction->bt_id = Config::get('beian_siteinfo.bt_id');
            $bt_name = Config::get('beian_siteinfo.bt_name');
            $modify_status = $this->btAction->addDomain($not_beian_domain_str, $bt_name, 0);
            if ($modify_status) {
                foreach ($not_beian as $key => $value) {
                    model('DomainBeian')::create([
                        'vhost_id'  => $this->vhost_id,
                        'bt_id'     => $this->bt_id,
                        'bt_name'   => $this->hostInfo->bt_name,
                        'bt_id_n'   => $this->btAction->bt_id,
                        'bt_name_n' => $bt_name,
                        'dir'       => $dirs,
                        'status'    => 'normal',
                        'domain'    => $value,
                    ]);
                }
            } else {
                array_push($errorArr, __('Fail') . $this->btAction->getError());
            }
        }

        if ($successArr) {
            // 允许的域名绑定域名
            $domain_str = implode(',', $successArr);

            $this->btAction->bt_id = $this->bt_id;
            $modify_status_new = $this->btAction->addDomain($domain_str, $name, $isdir);
            if (!$modify_status_new) {
                array_push(
                    $errorArr,
                    __('Fail') . $this->btAction->getError()
                );
            }
        }
        $errorMsg = implode(';', $errorArr);
        if ($errorMsg) {
            $this->error($errorMsg);
        } else {
            $this->success(__('Success'));
        }
    }

    // 删除域名绑定
    public function delDomain()
    {
        $delete = $this->request->post('delete');
        $type = $this->request->post('type');
        $id = $this->request->post('id/d');
        $not_beian = $this->request->post('not_beian/d');


        Db::startTrans();
        if ($not_beian) {
            // 删除未备案域名
            $domainInfo = model('DomainBeian')::get(['vhost_id' => $this->vhost_id, 'domain' => $delete]);
            if ($domainInfo) {
                $domainInfo->delete(true);
            }

            Db::commit();
            $this->success(__('Success'));
        }
        $domainInfo = model('Domainlist')::get(['vhost_id' => $this->vhost_id, 'domain' => $delete]);
        // 先删除数据库的，如果删除失败就回滚，删除成功之后再删除宝塔面板中的，删除失败就回滚数据库
        if ($domainInfo) $domainInfo->delete(true);
        if (isset($domainInfo->status) && $domainInfo->status != 1) {
        } elseif ($type == 'domain') {
            $modify_status = $this->btAction->delDomain($this->bt_id, $this->siteName, $delete, 80);
            if (!$modify_status) {
                Db::rollback();
                $this->error(__('Delete fail') . $this->btAction->getError());
            }
        } elseif ($type == 'dir') {
            $modify_status = $this->btAction->delDomainDir($id);
            if (!$modify_status) {
                Db::rollback();
                $this->error(__('Delete fail') . $this->btAction->getError());
            }
        }
        Db::commit();
        $this->success(__('Success'));
    }

    // 密码修改
    public function pass()
    {
        $this->view->assign('title', __('pass'));
        return $this->view->fetch();
    }

    // 主机密码修改
    public function passVhost()
    {
        if (request()->post()) {
            $validate = new Validate([
                'oldpass'  => 'require|length:6,30',
                'password' => 'require|length:6,30',
            ], [
                'oldpass'  => __('The password does not meet the specification, the length should be greater than %s and less than %s characters', ['6', '30']),
                'password' => __('The password does not meet the specification, the length should be greater than %s and less than %s characters', ['6', '30']),
            ]);
            $data = [
                'oldpass'  => input('post.oldpass'),
                'password' => input('post.password'),
            ];
            if (!$validate->check($data)) $this->error($validate->getError());
            $update = $this->auth->changepwd($data['password'], $data['oldpass'], 0);
            if (!$update) $this->error($this->auth->getError());
            $this->success(__('Change success'));
        }
    }

    // 数据库密码修改
    public function passSql()
    {
        if (request()->post()) {
            // 判断是否存在这项业务
            $sqlInfo = $this->btAction->getSqlInfo();
            if (!$sqlInfo) $this->error(__('This service is not currently available'));
            $sqlFind = $this->sqlModel::get($this->hostInfo->sql->id);

            $validate = new Validate([
                'password' => 'require|length:6,12',
            ], [
                'password' => __('The password does not meet the specification, the length should be greater than %s and less than %s characters', ['6', '12']),
            ]);
            $data = [
                'password' => input('post.password'),
            ];
            if (!$validate->check($data)) $this->error($validate->getError());
            Db::startTrans();
            try {
                $sqlFind->password = $data['password'];
                $sqlFind->save();
            } catch (\Exception $e) {
                $this->error($e->getMessage());
            }
            Db::commit();
            $this->success(__('Success'));
        }
    }

    // ftp密码修改
    public function passFtp()
    {
        if (request()->post()) {
            // 判断是否存在这项业务
            $ftpInfo = $this->btAction->getFtpInfo();
            if (!$ftpInfo) $this->error(__('This service is not currently available'));

            $ftpFind = $this->ftpModel::get($this->hostInfo->ftp->id);

            $validate = new Validate([
                'password' => 'require|length:6,12',
            ], [
                'password' => __('The password does not meet the specification, the length should be greater than %s and less than %s characters', ['6', '12']),
            ]);
            $data = [
                'password' => $this->request->post('password'),
            ];

            if (!$validate->check($data)) $this->error($validate->getError());
            Db::startTrans();
            try {
                $ftpFind->password = $this->request->post('password');
                $ftpFind->save();
            } catch (\Exception $e) {
                $this->error($e->getMessage());
            }
            Db::commit();
            $this->success(__('Success'));
        }
    }

    // 带宽限制
    public function speed()
    {
        if ($this->server_type == 'linux') {
            $netInfo = $this->btPanel->GetLimitNet($this->bt_id);
            if (empty($netInfo['limit_rate']) && empty($netInfo['perip']) && empty($netInfo['perserver'])) {
                $netInfo['status'] = false;
            } else {
                $netInfo['status'] = true;
            }
            $viewTheme = 'speed';
        } else {
            $netInfo = $this->btPanel->GetLimitNet($this->bt_id);
            if (empty($netInfo['limit_rate']) && empty($netInfo['timeout']) && empty($netInfo['perserver'])) {
                $netInfo['status'] = false;
            } else {
                $netInfo['status'] = true;
            }
            $viewTheme = 'speed_win';
        }
        $this->view->assign('title', __('speed'));

        return $this->view->fetch($viewTheme, [
            'netInfo' => $netInfo,
        ]);
    }

    // 网站限速修改
    public function speedUp()
    {
        $post_str = $this->request->post();
        $validate = new Validate([
            'perserver'  => 'require|between:0,500',
            'perip'      => 'between:0,60',
            'limit_rate' => 'require|between:0,2048',
            'timeout'    => 'between:0,1000',
        ], [
            'perserver'  => __('Concurrency limited'),
            'perip'      => __('Single IP limited'),
            'limit_rate' => __('Flow limited'),
            'timeout'    => __('Time out'),
        ]);

        if (!$validate->check($post_str)) {
            $this->error($validate->getError());
        }
        $msg = '';
        try {
            $this->hostInfo->allowField(true)->save([
                'perserver'  => $post_str['perserver'],
                'limit_rate' => $post_str['limit_rate'],
            ]);
        } catch (\Exception $e) {
            $msg = $e->getMessage();
        }
        if ($msg) $this->error($msg);
        $this->success(__('Success'));
    }

    // 关闭网站限速
    public function speedOff()
    {
        $post_str = $this->request->post();

        if (isset($post_str['speed']) && $post_str['speed'] == 'off') {
            if ($modify_status = $this->btPanel->CloseLimitNet($this->bt_id)) {
                $this->hostModel->save([
                    'perserver'  => 0,
                    'limit_rate' => 0,
                ], ['id' => $this->hostInfo->id]);
                $this->success($modify_status['msg']);
            } else {
                $this->error(__('Fail') . '：' . $modify_status['msg']);
            }
        }
    }

    // 默认文件
    public function defaultfile()
    {
        $indexFile = $this->btPanel->WebGetIndex($this->bt_id);
        $files = str_replace(',', "\r\n", $indexFile);

        $this->view->assign('title', __('defaultfile'));
        return $this->view->fetch('defaultfile', [
            'indexfile' => $indexFile,
            'files'     => $files,
        ]);
    }

    // 默认文件修改
    public function fileUp()
    {
        $post_str = $this->request->post();

        if (!empty($post_str['Dindex'])) {
            // 增加非法字符效验
            if (!preg_match("/^[\w.,\n]+$/i", $post_str['Dindex'])) {
                $this->error(__('Illegal parameter'));
            }
            $post_str['Dindex'] = str_replace("\n", ",", $post_str['Dindex']);
            $modify_status = $this->btPanel->WebSetIndex($this->bt_id, $post_str['Dindex']);
            if (isset($modify_status) && $modify_status['status'] == 'true') {
                $this->success($modify_status['msg']);
            } else {
                $this->error(__('Fail') . '：' . $modify_status['msg']);
            }
        } else {
            $this->error(__('Can not be empty'));
        }
    }

    // 301重定向（普通版）
    public function Rewrite301()
    {
        if ($this->hostInfo->server_os != 'linux') {
            $this->error(__('The plug-in is not supported by the current host'), '');
        }

        $rewriteInfo = $this->btPanel->Get301Status($this->siteName);
        $rewriteInfo['domain'] = explode(',', $rewriteInfo['domain']);

        $this->view->assign('title', __('rewrite301'));
        return $this->view->fetch('rewrite301', [
            'rewriteInfo' => $rewriteInfo,
        ]);
    }

    // 301重定向（普通版）更新
    public function r301Up()
    {
        if ($this->hostInfo->server_os == 'windows') $this->error(__('The plug-in is not supported by the current host'));
        $post_str = $this->request->post();

        if (!empty($post_str['domains']) && !empty($post_str['toUrl'])) {
            $rewriteInfo = $this->btPanel->Get301Status($this->siteName);
            $rewriteInfo['domain'] = explode(',', $rewriteInfo['domain']);
            if ($post_str['domains'] !== 'all' && !deep_in_array($post_str['domains'], $rewriteInfo['domain'])) {
                $this->error(__('%s parameters', __('Domain')));
            }

            if (preg_match($this->global_reg, $post_str['domains']) || preg_match($this->global_reg, $post_str['toUrl'])) {
                $this->error(__('Illegal parameter'));
            }
            $modify_status = $this->btPanel->Set301Status($this->siteName, $post_str['toUrl'], $post_str['domains'], 1);
            if (isset($modify_status) && $modify_status['status'] == 'true') {
                $this->success($modify_status['msg']);
            } else {
                $this->error(__('Fail') . '：' . $modify_status['msg']);
            }
        } else {
            $this->error(__('Can not be empty'));
        }
    }

    // 301重定向（普通版）关闭
    public function r301Off()
    {
        if ($this->hostInfo->server_os == 'windows') $this->error(__('The plug-in is not supported by the current host'));
        $post_str = $this->request->post();

        if (isset($post_str['rewrite']) && $post_str['rewrite'] == 'off') {
            if ($modify_status = $this->btPanel->Set301Status($this->siteName, 'http://baidu.cpom$request_uri', 'all', 0)) {
                $this->success($modify_status['msg']);
            } else {
                $this->error(__('Fail') . '：' . $modify_status['msg']);
            }
        }
    }

    // 重定向（测试版）
    public function redir()
    {
        // if ($this->serverConfig['webserver'] != 'nginx') {
        //     $this->error(__('The plug-in is not supported by the current host'),'');
        // }
        // 获取网站下的域名列表
        $WebsitesList = $this->btPanel->Websitess($this->bt_id, 'domain');
        // 获取重定向内测版列表
        $RedirectList = $this->btPanel->GetRedirectList($this->siteName);
        if ($RedirectList) {
            foreach ($RedirectList as $key => $value) {
                if (isset($RedirectList[$key]['redirectdomain'][0])) {
                    $RedirectList[$key]['redirectdomain'] = $RedirectList[$key]['redirectdomain'][0];
                }
            }
        }
        $this->view->assign('title', __('redir'));
        return $this->view->fetch('redir', [
            'WebsitesList' => $WebsitesList,
            'RedirectList' => $RedirectList,
        ]);
    }

    // 重定向（测试版）更新
    public function redirUp()
    {
        if ($this->request->post()) {
            // 目标Url
            $tourl1 = input('post.tourl1');
            // 重定向域名
            $redirectdomains = input('post.redirectdomain');
            // 是否开启重定向
            $types = input('post.type') ? '1' : '0';
            // 是否保留参数
            $holdpaths = input('post.holdpath') ? '1' : '0';
            // 重定向名称（用于修改）
            $redirectname = input('post.redirectname');
            // 重定向方式 301 or 302
            $redirecttype = input('post.redirecttype') == '301' ? '301' : '302';
            // 重定向内容 域名 or 路径
            $domainortype = input('post.domainortype') == 'domain' ? 'domain' : 'path';
            // 重定向路径 （用于路径）
            $redirectpath = input('post.redirectpath');
            if (!$tourl1) {
                return ['code' => '-1', 'msg' => __('%s can not be empty', __('Redirect path'))];
            }
            if (preg_match($this->global_reg, $redirectname) || preg_match($this->global_reg, $tourl1) || preg_match($this->global_reg, $redirectpath) || preg_match($this->global_reg, $redirectdomains)) {
                $this->error(__('Illegal parameter'));
            }
            // 获取网站下的域名列表
            $WebsitesList = $this->btPanel->Websitess($this->bt_id, 'domain');
            if (!empty($redirectdomains) && !deep_in_array($redirectdomains, $WebsitesList)) {
                $this->error(__('%s parameters', __('Domain')));
            }
            //批量选择域名
            //$redirectdomain = explode(',', $redirectdomains);
            $redirectdomain = json_encode(explode(',', $redirectdomains));
            $type = $types ? 1 : '0';
            $holdpath = $holdpaths ? 1 : '0';
            if (isset($redirectname) && $redirectname) {
                $redirUp = $this->btPanel->ModifyRedirect($this->siteName, $redirectname, $redirecttype, $domainortype, $redirectdomain, $redirectpath, $tourl1, $type, $holdpath);
            } else {
                $redirUp = $this->btPanel->CreateRedirect($this->siteName, $redirecttype, $domainortype, $redirectdomain, $redirectpath, $tourl1, $type, $holdpath);
            }

            if ($redirUp) {
                return ['code' => '200', 'msg' => @$redirUp['msg']];
            } else {
                return ['code' => '-1', 'msg' => __('Fail') . @$redirUp['msg']];
            }
        }
    }

    // 重定向（测试版）删除
    public function redirDel()
    {
        if (!$this->request->post()) {
            return ['code' => '-1', 'msg' => __('Illegal request')];
        }
        $redirectname = input('post.redirectname');
        $del = $this->btPanel->DeleteRedirect($this->siteName, $redirectname);
        if ($del) {
            return ['code' => '200', 'msg' => @$del['msg']];
        } else {
            return ['code' => '-1', 'msg' => __('Fail') . '：' . @$del['msg']];
        }
    }

    // 伪静态规则
    public function rewrite()
    {
        //获取内置伪静态规则名
        $rewriteList = $this->btPanel->GetRewriteList($this->siteName);
        //获取当前网站伪静态规则
        if ($this->server_type == 'linux') {
            $rewriteInfo = $this->btPanel->GetFileBody($this->siteName, 1);
        } else {
            $rewriteInfo = $this->btPanel->GetSiteRewrite($this->siteName);
        }

        $this->view->assign('title', __('rewrite'));
        //获取子目录绑定信息
        $dirList = $this->btPanel->GetDirBinding($this->bt_id);
        return view('rewrite', [
            'dirList'     => $dirList,
            'rewriteList' => $rewriteList,
            'rewriteInfo' => $rewriteInfo,
        ]);
    }

    // 伪静态规则获取
    public function rewriteGet()
    {
        $post_str = $this->request->post();
        // 增加传值效验
        // $rewrites = ['0.当前','EduSoho','EmpireCMS','dabr','dbshop','dedecms','default','discuz','discuzx','discuzx2','discuzx3','drupal','ecshop','emlog','laravel5','maccms','mvc','niushop','phpcms','phpwind','sablog','seacms','shopex','thinkphp','typecho','typecho2','wordpress','wp2','zblog'];
        // if(!in_array($post_str['rewrites'],$rewrites)){
        //     $this->error(__('Illegal request'));
        // }
        if ($this->server_type == 'linux') {
            if (isset($post_str['rewrites']) && !empty($post_str['rewrites'])) {
                if ($post_str['rewrites'] == '0.当前') {
                    $rewrite = $this->siteName;
                    $type = 1;
                } else {
                    $rewrite = $post_str['rewrites'];
                    $type = 0;
                }
                if ($post_str['dirdomain'] == '/') {
                    $modify_status = $this->btPanel->GetFileBody($rewrite, $type);
                } else {
                    if ($post_str['rewrites'] == '0.当前') {
                        $modify_status = $this->btPanel->GetDirRewrite($post_str['dirdomain']);
                    } else {
                        $modify_status = $this->btPanel->GetFileBody($rewrite, $type);
                    }
                }
                if (isset($modify_status) && $modify_status['status'] == 'true') {
                    return ['code' => '200', 'msg' => __('Success'), 'data' => @$modify_status['data']];
                } else {
                    $this->error(__('Fail') . '：' . @$modify_status['msg']);
                }
                exit();
            } else {
                $this->error(__('Illegal request'));
            }
        } else {
            $rewrite = $post_str['rewrites'];
            if (isset($rewrite) && !empty($rewrite)) {
                if ($rewrite == '0.当前') {
                    $rewriteInfo = $this->btPanel->GetSiteRewrite($this->siteName);
                    return ['code' => '200', 'msg' => __('Success'), 'data' => @$rewriteInfo['data']];
                }

                // 获取当前运行环境
                $type = 'iis';

                if ($post_str['dirdomain'] == '/') {
                    $modify_status = $this->btPanel->GetFileBody_win($rewrite, $type);
                } else {
                    if ($rewrite == '0.当前') {
                        $modify_status = $this->btPanel->GetDirRewrite($post_str['dirdomain']);
                    } else {
                        $modify_status = $this->btPanel->GetFileBody_win($rewrite, $type);
                    }
                }
                if (isset($modify_status) && $modify_status['status'] == 'true') {
                    return ['code' => '200', 'msg' => __('Success'), 'data' => @$modify_status['data']];
                } else {
                    $this->error(__('Fail') . '：' . @$modify_status['msg']);
                }
                exit();
            } else {
                $this->error(__('Illegal request'));
            }
        }
    }

    // 伪静态规则设置
    public function rewriteSet()
    {
        $dirdomain = input('post.dirdomain');
        $rewrite = input('post.rewrite', '', 'trim');
        if (preg_match($this->reg_rewrite, $rewrite)) {
            $this->error(__('Illegal parameter'));
        }
        if (isset($rewrite)) {
            if ($dirdomain == '/') {
                if ($this->server_type == 'linux') {
                    $modify_status = $this->btPanel->SaveFileBody($this->siteName, $rewrite, 'utf-8');
                } else {
                    $modify_status = $this->btPanel->SetSiteRewrite($this->siteName, $rewrite);
                }
            } else {

                // $this->btPanel->GetDirRewrite($dirdomain, 1);
                $GetDirRewrite = $this->btPanel->GetDirRewrite($dirdomain, 1);
                if (!$GetDirRewrite || $GetDirRewrite['status'] != 'true') {
                    $this->error(__('Fail') . '：' . @$GetDirRewrite['msg']);
                } else {
                    $dir_path = $GetDirRewrite['filename'];
                }
                $modify_status = $this->btPanel->SaveFileBody($dir_path, $rewrite, 'utf-8', 1);
            }
            if (isset($modify_status) && $modify_status['status'] == 'true') {
                $this->success(@$modify_status['msg']);
            } else {
                $this->error(__('Fail') . '：' . @$modify_status['msg']);
            }
        } else {
            $this->error(__('Illegal request'));
        }
    }

    // 文件管理(FTP)
    public function file_ftp()
    {
        if (!extension_loaded('ftp')) $this->error(__('FTP extension is not enabled'), '');

        // 判断当前站点是否开通ftp

        $type = input('post.type');
        if (!$this->hostInfo->ftp) $this->error(__('This service is not currently available'), '');
        $host = Config::get('site.ftp_server') ? Config::get('site.ftp_server') : '127.0.0.1';
        $ssl = Config::get('site.ftp_type') == 'true';
        $port = Config::get('site.ftp_port') ? Config::get('site.ftp_port') : '21';
        $username = $this->hostInfo->ftp->username;
        $password = $this->hostInfo->ftp->password;
        if (!$host || !$port || !$username || !$password) $this->error(__('This service is not currently available'), '');

        // 防止错误
        try {
            $ftp = new \FtpClient\MyFtpClient();
            $ftp->connect($host, $ssl, $port, 30);
            $ftp->login($username, $password);
        } catch (\Exception $e) {
            $excMsg = $e->getMessage();
            switch ($excMsg) {
                case 'Login incorrect':
                    $this->error(__('Username or password is incorrect'), '');
                    break;
                case 'Unable to connect':
                    $this->error(__('%s connection failed', __('Ftp')), '');
                    break;
                default:
                    $this->error($excMsg);
                    break;
            }
        }
        // 定义临时下载目录
        $tempDir = ROOT_PATH . 'logs/ftp_temp/' . $this->siteName . '/';

        if (!is_dir($tempDir)) {
            // 判断临时目录是否存在，不存在则创建
            mkdir(iconv("UTF-8", "GBK", $tempDir), 0755, true);
        }

        // 进入指定目录
        $path = input('get.path');
        $ftp->chdir($path);

        $path = $ftp->pwd();

        $path_arr = explode('/', $path);
        $text_arr = [];
        for ($i = 0; $i < count($path_arr); $i++) {
            if ($path_arr[$i]) {
                if (isset($text_arr[$i - 1]['url']) && $text_arr[$i - 1]['url']) {
                    $str = $text_arr[$i - 1]['url'];
                } else {
                    $str = '';
                }
                $text_arr[$i]['path'] = $path_arr[$i];
                $text_arr[$i]['url'] = $str . '/' . $path_arr[$i];
            }
        }
        $this->view->assign('paths', $text_arr);

        // 新文件夹
        if ($type == 'newdir') {
            $newdir = input('post.newdir');

            try {
                $new = $ftp->mkdir($path . '/' . $newdir);
            } catch (\Exception $e) {
                $this->error(__('%s fail', __('Create dir')) . $e->getMessage());
            }

            if ($new) {
                $this->success(__('Success'));
            } else {
                $this->error(__('%s fail', __('Create dir')));
            }
        }
        // 上传文件
        if ($type == 'uploadfile') {
            $this->siteSizeCheck();

            $path = input('get.path') == '/' ? '' : input('get.path');

            $file = request()->file('zunfile');
            $info = $file->move($tempDir, $file->getInfo('name'));
            if ($info) {
                set_time_limit(0);
                $msg = '';
                $postFile = $tempDir . $info->getFilename();
                try {
                    $put = $ftp->put($path . '/' . $info->getFilename(), $postFile, 2);
                } catch (\Exception $e) {
                    $msg = $e->getMessage();
                }

                if (!$put) {
                    $this->error(__('Upload failed') . $msg);
                }
                $this->success(__('Success'));
            } else {
                $this->error(__('%s fail', __('file')));
            }
        }
        // 新文件
        if ($type == 'newfile') {
            $newfile = input('post.newfile') ? preg_replace('/([.]){2,}|([\/]){1,}/', '', input('post.newfile')) : '';
            $path = input('post.path') ? preg_replace($this->path_reg, '/', input('post.path')) : '/';

            // $createFile = fopen($tempDir . $newfile, 'w+');
            $createFile = fopen($tempDir . $newfile, 'w+');
            $write = fwrite($createFile, '123456');
            if (!$createFile || !$write) {
                $this->error(__('File creation failed, please check the read and write permissions of the /logs directory'));
            }
            fclose($createFile);
            try {
                $new = $ftp->up_file($path . $newfile, $tempDir . $newfile, true, FTP_ASCII);
            } catch (\Exception $e) {
                $this->error(__('%s fail', __('Create file')) . $e->getMessage());
            }

            if ($new) {
                $this->success(__('%s success', __('Create file')));
            } else {
                $this->error(__('%s fail', __('Create file')));
            }
        }
        //删除文件
        if ($type == 'deletefile') {
            $file = input('post.file');
            if (!$file) {
                $this->error(__('Please select file'));
            }
            try {
                $deleteFile = $ftp->del_file($file);
            } catch (\Exception $e) {
                $this->error(__('Delete fail') . $e->getMessage());
            }

            if ($deleteFile) {
                $this->success(__('%s success', __('Delete')) . $file);
            } else {
                $this->error(__('Delete fail') . $file);
            }
        }
        //删除目录
        if ($type == 'deletedir') {
            $file = input('post.file');
            if (!$file) {
                $this->error(__('Please select dirs'));
            }
            try {
                $deleteDir = $ftp->del_all($file, true);
            } catch (\Exception $e) {
                $this->error(__('Delete fail') . $e->getMessage());
            }

            if ($deleteDir) {
                $this->success(__('%s success', __('Delete')) . $file);
            } else {
                $this->error(__('Delete fail') . $file);
            }
        }
        //文件/目录 重命名
        if ($type == 'MvFile') {
            // $path        = input('post.path') ? preg_replace('/([.]){2,}/', '/', input('post.path')) : '';
            $oldFileName = input('post.oldName') ? preg_replace($this->path_reg, '', input('post.oldName')) : '';
            $newFileName = input('post.newName') ? preg_replace($this->path_reg, '', input('post.newName')) : '';
            if (!$oldFileName || !$newFileName) {
                $this->error(__('Can not be empty'));
            }

            $old = $path == '/' ? $path . $oldFileName : $path . '/' . $oldFileName;
            $new = $path == '/' ? $path . $newFileName : $path . '/' . $newFileName;

            // 有些服务器不支持此特性
            // if($ftp->get_size($new)=='-1'){
            //     $this->error('该命名已存在');
            // }

            try {
                $MvFile = $ftp->rename($old, $new);
            } catch (\Exception $e) {
                $this->error(__('Fail') . $e->getMessage());
            }

            if ($MvFile) {
                $this->success(__('Success'));
            } else {
                $this->error(__('Fail'));
            }
        }
        // 剪切/复制文件
        if ($type == 'cut') {
            $file = input('post.file');
            $name = input('post.name');
            $copy = input('post.copy/d');
            if (!$file || !$name) {
                $this->error(__('%s can not be empty', __('file')));
            }
            $n = $copy ? 'copyFileName' : 'cutFileName';
            Cookie::set($n, json_encode(['file' => $file, 'name' => $name]), ['prefix' => 'vhost_cutcopy_', 'expire' => 3600]);
            $this->success(__('Success'));
        }
        // 粘贴文件
        if ($type == 'paste') {
            $cut_file = Cookie::get('cutFileName', 'vhost_cutcopy_');
            $copy_file = Cookie::get('copyFileName', 'vhost_cutcopy_');
            $type = $copy_file ? 'copy' : 'cut';
            if (!$cut_file && !$copy_file) {
                $this->error(__('File not found'));
            }
            $fileArr = $type == 'copy' ? json_decode($copy_file, 1) : json_decode($cut_file, 1);

            $oldfile = $fileArr['file'];
            $newfile = $path == '/' ? $path . $fileArr['name'] : $path . '/' . $fileArr['name'];
            set_time_limit(0);

            try {
                if ($type == 'cut') {
                    // 移动文件
                    $act = $ftp->move_file($oldfile, $newfile);
                } else if ($type == 'copy') {
                    // 复制文件（容易超时，只能复制小文件）
                    try {
                        // 先下载
                        $down = @$ftp->get($tempDir . $fileArr['name'], $oldfile, 1);
                        // 再上传
                        $put = @$ftp->put($newfile, $tempDir . $fileArr['name'], 2);

                        $act = 1;
                    } catch (\Exception $e) {
                        $this->error(__('Time out', __('Operation')));
                    }

                    // $act = $ftp->copy_file($oldfile, $newfile, $tempDir . $fileArr['name']);
                }

                if (!$act) {
                    $this->error($ftp->getError());
                }
                $this->clear_cutorcopy_cookie('all');
            } catch (\Exception $e) {
                $this->error(__('Fail') . $e->getMessage());
            }
            $this->success(__('Success'));
        }
        // 批量粘贴文件
        if ($type == 'pastes') {
            $cut_files = Cookie::get('cutFileNames', 'vhost_cutcopy_');
            $copy_files = Cookie::get('copyFileNames', 'vhost_cutcopy_');

            $type = $copy_files ? 'copy' : 'cut';
            if (!$cut_files && !$copy_files) {
                $this->error(__('File not found'));
            }
            $fileArr = $type == 'copy' ? json_decode($copy_files, 1) : json_decode($cut_files, 1);
            $arr_success = [];
            $arr_error = [];
            set_time_limit(0);

            foreach ($fileArr as $key => $value) {
                $oldfile = $value['file'];
                $newfile = $path == '/' ? $path . $value['name'] : $path . '/' . $value['name'];
                if ($type == 'cut') {
                    try {
                        // 移动文件
                        $act = $ftp->move_file($oldfile, $newfile);
                    } catch (\Exception $e) {
                        $this->error(__('Fail') . $e->getMessage());
                    }
                } else if ($type == 'copy') {
                    // 复制文件（容易超时，只能复制小文件）
                    try {
                        // 先下载
                        $down = @$ftp->get($tempDir . $value['name'], $oldfile, 1);
                        // 再上传
                        $put = @$ftp->put($newfile, $tempDir . $value['name'], 2);

                        $act = 1;
                    } catch (\Exception $e) {
                        $this->error(__('Time out', __('Operation')));
                    }

                    // $act = $ftp->copy_file($oldfile, $newfile, $tempDir . $value['name']);
                }
                if ($act) {
                    $arr_success[] = $value['name'];
                } else {
                    $arr_error[] = $value['name'];
                }
            }
            $this->clear_cutorcopy_cookie('all');
            $ms = __('Success') . "(" . count($arr_success) . ")：" . implode(',', $arr_success) . "<br>" . __('Fail') . "(" . count($arr_error) . ")：" . implode(',', $arr_error);
            $this->success(__('Success') . $ms);
        }
        // 批量操作
        if ($type == 'batch') {
            $data = input('post.data');
            $batch = input('post.batch');
            if ($data == '' || $path == '') {
                $this->error(__('Request error'));
            }
            switch ($batch) {
                case 'del':
                    $data_arr = explode(',', $data);
                    $sc_error = [];
                    $sc_success = [];
                    // 因为无法分辨文件夹/文件所以统统删一遍，存在目录与文件名一致的情况的问题
                    foreach ($data_arr as $key => $value) {
                        $del = $path == '/' ? $path . $value : $path . '/' . $value;
                        try {
                            $delete = $ftp->del_all($del);
                        } catch (\Exception $e) {
                            $this->error(__('Fail') . $e->getMessage());
                        }

                        if (!$delete) {
                            $sc_error[] = $del;
                        } else {
                            $sc_success[] = $del;
                        }
                    }
                    $this->success(__('Completed') . "<br/>" . __('Success') . '：' . implode(',', $sc_success) . "<br/>" . __('Fail') . '：' . implode(',', $sc_error));

                    break;
                case 'openZip':

                    break;
                case 'CutFile':
                    $copy = $this->request->post('copy/d');
                    $path = $this->request->post('path');

                    $data_arr = explode(',', $data);

                    $n = $copy ? 'copyFileNames' : 'cutFileNames';
                    $arr = [];
                    foreach ($data_arr as $key => $value) {
                        $arr[$key]['name'] = $value;
                        $arr[$key]['file'] = $path . $value;
                    }
                    Cookie::set($n, json_encode($arr), ['prefix' => 'vhost_cutcopy_', 'expire' => 3600]);
                    $this->success(__('Success'));
                    break;
                case 'CutFiles':
                    $this->clear_cutorcopy_cookie('all');

                    // $file = input('post.file');
                    // $name = input('post.name');
                    $copy = 1;
                    $path = $this->request->post('path');

                    $data_arr = explode(',', $data);

                    $n = $copy ? 'copyFileNames' : 'cutFileNames';
                    $arr = [];
                    foreach ($data_arr as $key => $value) {
                        $arr[$key]['name'] = $value;
                        $arr[$key]['file'] = $path . $value;
                    }
                    Cookie::set($n, json_encode($arr), ['prefix' => 'vhost_cutcopy_', 'expire' => 3600]);
                    $this->success(__('Success'));
                    break;
                default:
                    $this->error(__('Request error'));
                    break;
            }
        }
        //获取文件夹大小 (有些服务器不支持此特性)
        if ($type == 'getsize') {
            $path = input('post.path');
            if (!$path) {
                $this->error(__('Please select content'));
            }
            $paths = $path;
            try {
                $size = $ftp->dirSize($paths);
            } catch (\Exception $e) {
                $this->error(__('Fail') . $e->getMessage());
            }

            if (isset($size)) {
                $this->success(formatBytes($size));
            } else {
                $this->error(__('Fail'));
            }
        }
        //获取文件内容
        if ($type == 'getfile') {
            $file = input('post.file') ? preg_replace($this->path_reg, '/', input('post.file')) : '';
            if (!$file) {
                $this->error(__('Please select file'));
            }
            try {
                $open_file = $ftp->getContent($file);
            } catch (\Exception $e) {
                $this->error(__('Fail') . $e->getMessage());
            }

            if ($open_file) {
                $this->success(__('Success'), '', $open_file);
            } else {
                $this->error(__('Fail'));
            }
        }
        //保存文件
        if ($type == 'savefile') {
            $file = input('post.file') ? preg_replace($this->path_reg, '/', input('post.file')) : '';
            if (!$file) {
                $this->error(__('Please select file'));
            }
            $content = $this->request->post('content', '', null);
            try {
                $put = $ftp->putFromString($file, $content);
            } catch (\Exception $e) {
                $this->error(__('Fail') . $e->getMessage());
            }
            if ($put) {
                $this->success(__('Success'));
            } else {
                $this->error(__('Fail'));
            }
        }
        // 文件下载FTP
        if (input('get.downfile')) {
            $file = input('get.downfile');

            $arr = explode('/', $file);

            if (!is_dir($tempDir)) {
                mkdir(iconv("UTF-8", "GBK", $tempDir), 0755, true);
            }
            // 文件名
            $downFileName = end($arr);
            try {
                // 1:二进制,2:文本模式，已知windows下使用二进制下载图片出现编码错误
                // 下载时不能开启app_trace
                $down = $ftp->get($tempDir . $downFileName, $file, 2);
            } catch (\Exception $e) {
                $this->error(__('%s fail', __('Download')) . $e->getMessage());
            }

            if ($down) {
                $file = @fopen($tempDir . $downFileName, "r");
                if (!$file) {
                    $this->error(__('File is missing'));
                } else {
                    return downloadTemplate($tempDir, $downFileName);
                }
            } else {
                $this->error(__('%s fail', __('Download')));
            }
        }
        if (input('get.api')) {
            $api = input('get.api');
            switch ($api) {
                case 'dirlist':
                    $list = $ftp->get_rawlist($path);
                    $this->success(__('Success'), '', $list);
                    break;

                default:
                    # code...
                    break;
            }
        }

        try {
            $list = $ftp->get_rawlist($path);
        } catch (\Exception $e) {
            $this->error(__('File acquisition failed') . $e->getMessage(), '');
        }

        $php_upload_max = byteconvert(ini_get('upload_max_filesize'));

        $crumbs_nav = array_filter(explode('/', $path));

        $this->view->assign('title', __('file_ftp'));
        return $this->view->fetch('file_ftp', [
            'crumbs_nav'     => $crumbs_nav,
            'viewpath'       => $path == '/' ? $path : $path . '/',
            'list'           => $list,
            'php_upload_max' => $php_upload_max,
        ]);
    }

    // 在线文件管理模块
    public function file()
    {
        //获取网站根目录
        $WebGetKey = $this->btAction->webRootPath;
        if (!$WebGetKey) $this->error(__('Failed to get root directory'), '');
        // 获取跨域信息
        $getini = $this->btAction->dirUserIni;
        if (!$getini) $this->error(__('Unexpected situation'), '');

        //请求路径
        $path = input('get.path') ? preg_replace($this->path_reg, '', input('get.path') . '/') : '/';
        // TODO 要实现的目的是屏蔽[../ ./ //]等字符
        // TODO 搜索后文件访问路径有问题，整个在线文件管理文件及路径安全还需要全部重新做
        $path_arr = explode('/', $path);
        $text_arr = [];
        for ($i = 0; $i < count($path_arr); $i++) {
            if ($path_arr[$i]) {
                if (isset($text_arr[$i - 1]['url']) && $text_arr[$i - 1]['url']) {
                    $str = $text_arr[$i - 1]['url'];
                } else {
                    $str = '';
                }
                $text_arr[$i]['path'] = $path_arr[$i];
                $text_arr[$i]['url'] = $str . '/' . $path_arr[$i];
            }
        }
        $this->view->assign('paths', $text_arr);
        //请求文件
        $file = input('post.file') ? preg_replace('/([.]){2,}|([\/]){2,}/', '', input('post.file')) : '';

        // 防止有心人post删除防跨站文件
        // if (strpos(input('post.file'), '.user.ini') !== false) {
        //     $this->error(__('Illegal request'));
        // }
        if (preg_match($this->reg_file, $file)) {
            $this->error(__('Illegal request'), '');
        }

        //$newWebGetKey = str_replace($WebGetKey,'/',$WebGetKey);

        $Webpath = $WebGetKey . $path;
        $type = $this->request->post('type');
        // 数据库导入
        if ($type == 'sqlinput') {
            // $file = input('post.file') ? preg_replace('/([.]){2,}/', '/', input('post.file')) : '';
            if (!$file) {
                $this->error(__('Please select file'));
            }
            if (!$this->path_root_check($WebGetKey . $file, $WebGetKey)) {
                $this->error(__('Illegal operation'));
            }
            if (isset($this->hostInfo->sql->username) && $this->hostInfo->sql->username != '') {
                $input = $this->btPanel->SQLInputSqlFile($WebGetKey . $file, $this->hostInfo->sql->username);
                if ($input && isset($input['status']) && $input['status'] == 'true') {
                    $this->success($input['msg']);
                } else {
                    $this->error(__('Fail'));
                }
            } else {
                $this->error(__('This service is not currently available'));
            }
        }
        if (input('get.go') == 1) {
            $new_url = url_set_value(request()->url(true), 'go', '0');
            $this->success(__('Downloading, please do not refresh the page...'), $new_url);
            exit();
        }
        // 文件下载
        if (input('get.downfile')) {
            $file = input('get.downfile') ? preg_replace($this->path_reg, '/', input('get.downfile')) : '/';
            $info = pathinfo($file);

            if (!$this->path_root_check($WebGetKey . $file, $WebGetKey)) {
                $this->error(__('Illegal operation'));
            }
            $down = $this->btPanel->download($WebGetKey . $file, $info['basename']);
            if ($down && isset($down['status']) && $down['status'] == 'false') {
                $this->success($down['msg']);
            }
            exit();
        }
        //php文件查杀
        if ($type == 'webshellcheck') {
            if (!$file) $this->error(__('Please select file'));
            if (!$this->path_root_check($WebGetKey . $file, $WebGetKey)) {
                $this->error(__('Illegal operation'));
            }
            $check = $this->btPanel->webshellCheck($WebGetKey . $file);
            if ($check && isset($check['status']) && $check['status'] == 'true') {
                $this->success($file . $check['msg']);
            } else {
                $this->error(__('Fail'));
            }
        }
        //删除文件
        if ($type == 'deletefile') {
            if (!$file) $this->error(__('Please select file'));
            if (!$this->path_root_check($WebGetKey . $file, $WebGetKey)) {
                $this->error(__('Illegal operation'));
            }
            $deleteFile = $this->btPanel->DeleteFile($WebGetKey . $file);
            if ($deleteFile && isset($deleteFile['status']) && $deleteFile['status'] == 'true') {
                $this->success(__('%s success', __('Delete')) . $file);
            } else {
                $this->error(__('Delete fail') . $file);
            }
        }
        //删除目录
        if ($type == 'deletedir') {
            if (!$file) $this->error(__('Please select dirs'));
            if (!$this->path_root_check($WebGetKey . $file, $WebGetKey)) {
                $this->error(__('Illegal operation'));
            }
            $deleteDir = $this->btPanel->DeleteDir($WebGetKey . $file);
            if ($deleteDir && isset($deleteDir['status']) && $deleteDir['status'] == 'true') {
                $this->success(__('%s success', __('Delete')) . $file);
            } else {
                $this->error(__('Delete fail') . $file);
            }
        }
        //解压
        if ($type == 'unzip') {
            $password = $this->request->post('password', '');
            $zipType = $this->request->post('zipType');
            $sfile = input('post.sfile') ? preg_replace($this->path_reg, '', input('post.sfile')) : '';
            if (!$sfile) {
                $this->error(__('Please select file'));
            }
            $dfile = input('post.dfile') ? preg_replace($this->path_reg, '/', input('post.dfile')) : '/';
            $coding = input('post.coding') == 'UTF-8' ? input('post.coding') : 'gb18030';
            if ($sfile == '' || $dfile == '') {
                $this->error(__('%s can not be empty', __('The file path or decompression path')));
            }
            $UnZip = $this->btPanel->UnZip($WebGetKey . $sfile, $WebGetKey . $dfile, $password, $zipType, $coding);
            if ($UnZip && isset($UnZip['status']) && $UnZip['status'] == 'true') {
                $this->success(__('The decompression task has been added to the message queue, and the decompression time depends on the file size'));
            } else {
                $this->error(__('Fail'));
            }
        }
        //压缩
        if ($type == 'zip') {
            $path = input('post.path') ? preg_replace($this->path_reg, '/', input('post.path')) : '/';
            $sfile = input('post.sfile') ? preg_replace($this->path_reg, '', input('post.sfile')) : '';
            $dfile = input('post.dfile') ? preg_replace($this->path_reg, '', input('post.dfile')) : '';
            $zipType = input('post.zipType');
            if (!$sfile || !$dfile || !$zipType) {
                $this->error(__('Illegal request'));
            }
            switch ($zipType) {
                case 'zip':
                    $zipType = 'zip';
                    break;
                case 'rar':
                    $zipType = 'rar';
                    break;
                case 'tar.gz':
                    $zipType = 'tar.gz';
                    break;
                default:
                    $this->error(__('Illegal request'));
                    break;
            }

            $zip = $this->btPanel->fileZip($sfile, $WebGetKey . $dfile, $zipType, $WebGetKey . $path);
            if ($zip && isset($zip['status']) && $zip['status'] == 'true') {
                $this->success($zip['msg']);
            } else {
                $this->error(__('Fail'));
            }
        }
        //文件重命名/移动
        if ($type == 'MvFile') {
            $path = input('post.path') ? preg_replace($this->path_reg, '/', input('post.path')) : '/';
            $oldFileName = input('post.oldName') ? preg_replace($this->path_reg, '/', input('post.oldName')) : '/';
            $newFileName = input('post.newName') ? preg_replace($this->path_reg, '/', input('post.newName')) : '/';
            $newFileName = trim($newFileName);
            if (!$oldFileName || !$newFileName) {
                $this->error(__('Can not be empty'));
            }
            if (!$this->path_root_check($WebGetKey . $path . $oldFileName, $WebGetKey)) {
                $this->error(__('Illegal operation'));
            }
            if (!$this->path_root_check($WebGetKey . $path . $newFileName, $WebGetKey)) {
                $this->error(__('Illegal operation'));
            }
            $MvFile = $this->btPanel->MvFile($WebGetKey . $path . $oldFileName, $WebGetKey . $path . $newFileName);
            if ($MvFile && isset($MvFile['status']) && $MvFile['status'] == 'true') {
                $this->success($MvFile['msg']);
            } else {
                $this->error(__('Fail'));
            }
        }
        //获取文件夹大小
        if ($type == 'getsize') {
            $path = input('post.path');
            if (!$path) $this->error(__('Please select file'));
            $paths = $WebGetKey . $path;
            $size = $this->btPanel->GetWebSize($paths);
            if (isset($size['size'])) {
                $this->success(formatBytes($size['size']));
            } else {
                $this->error(__('Fail'));
            }
        }
        //复制/剪切
        if ($type == 'cut') {
            $this->clear_cutorcopy_cookie('all');
            // $file = input('post.file') ? preg_replace('/([.]){2,}/', '/', input('post.file')) : '/';
            $name = input('post.name') ? preg_replace($this->path_reg, '', input('post.name')) : '';
            $copy = $this->request->post('copy');
            if ($copy) {
                if ($file && $name) {
                    Cookie::set('copyFileName', $name, ['prefix' => 'vhost_cutcopy_', 'expire' => 3600]);
                    Cookie::set('copyFileNames', $file, ['prefix' => 'vhost_cutcopy_', 'expire' => 3600]);
                    $this->success(__('Please choose a suitable location to paste'));
                } else {
                    $this->error(__('Fail'));
                }
            } else {
                if ($file && $name) {
                    Cookie::set('cutFileName', $name, ['prefix' => 'vhost_cutcopy_', 'expire' => 3600]);
                    Cookie::set('cutFileNames', $file, ['prefix' => 'vhost_cutcopy_', 'expire' => 3600]);
                    $this->success(__('Please choose a suitable location to paste'));
                } else {
                    $this->error(__('Fail'));
                }
            }
        }
        //粘贴
        if ($type == 'paste') {
            $path = input('post.path') ? preg_replace($this->path_reg, '/', input('post.path')) : '/';

            $copy = $this->request->post('copy');
            if ($copy) {
                $copyFileNames = Cookie::get('copyFileNames', 'vhost_cutcopy_') ? preg_replace($this->path_reg, '', Cookie::get('copyFileNames', 'vhost_cutcopy_')) : '';
                $copyFileName = Cookie::get('copyFileName', 'vhost_cutcopy_') ? preg_replace($this->path_reg, '', Cookie::get('copyFileName', 'vhost_cutcopy_')) : '';
                if ($copyFileNames) {
                    $sfile = $WebGetKey . $copyFileNames;
                    $dfile = $WebGetKey . $path . $copyFileName;
                    if (!$this->path_root_check($sfile, $WebGetKey)) {
                        $this->error(__('Illegal operation'));
                    }
                    if (!$this->path_root_check($dfile, $WebGetKey)) {
                        $this->error(__('Illegal operation'));
                    }

                    $mv = $this->btPanel->CopyFile($sfile, $dfile);
                } else {
                    $this->error(__('Empty'));
                }
            } else {
                $cutFileNames = Cookie::get('cutFileNames', 'vhost_cutcopy_') ? preg_replace($this->path_reg, '', Cookie::get('cutFileNames', 'vhost_cutcopy_')) : '';
                if ($cutFileNames) {
                    $sfile = $WebGetKey . $cutFileNames;
                    $dfile = $WebGetKey . $path;
                    if (!$this->path_root_check($sfile, $WebGetKey)) {
                        $this->error(__('Illegal operation'));
                    }
                    if (!$this->path_root_check($dfile, $WebGetKey)) {
                        $this->error(__('Illegal operation'));
                    }
                    $mv = $this->btPanel->MvFile($sfile, $dfile);
                } else {
                    $this->error(__('Empty'));
                }
            }
            $this->clear_cutorcopy_cookie('all');
            if ($mv && isset($mv['status']) && $mv['status'] == 'true') {
                $this->success(__('Success'));
            } elseif ($mv && isset($mv['status']) && $mv['status'] != 'true') {
                $this->error($mv['msg']);
            } else {
                $this->error(__('Fail'));
            }
        }
        // 新文件夹
        if ($type == 'newdir') {
            $newdir = input('post.newdir') ? preg_replace('/([.])+|([\/])+/', '', input('post.newdir')) : '';
            $newdir = trim($newdir);
            $path = input('post.path') ? preg_replace($this->path_reg, '/', input('post.path')) : '/';
            if (!$this->path_root_check($WebGetKey . $path . $newdir, $WebGetKey)) {
                $this->error(__('Illegal operation'));
            }
            $new = $this->btPanel->CreateDir($WebGetKey . $path . $newdir);
            if ($new && isset($new['status']) && $new['status'] == 'true') {
                $this->success(__('%s success', __('Create dir')));
            } else {
                $this->error(__('%s fail', __('Create dir')));
            }
        }
        // 新文件
        if ($type == 'newfile') {
            $newfile = input('post.newfile') ? preg_replace('/([.]){2,}|([\/])+/', '', input('post.newfile')) : '';
            $newfile = trim($newfile);
            $path = input('post.path') ? preg_replace($this->path_reg, '/', input('post.path')) : '/';
            if (!$this->path_root_check($WebGetKey . $path . $newfile, $WebGetKey)) {
                $this->error(__('Illegal operation'));
            }
            $new = $this->btPanel->CreateFile($WebGetKey . $path . $newfile);
            if ($new && isset($new['status']) && $new['status'] == 'true') {
                $this->success(__('%s success', __('Create file')));
            } else {
                $this->error(__('%s fail', __('Create file')));
            }
        }
        // 新版分片上传
        if ($files = request()->file('blob')) {
            header("Content-type: text/html; charset=utf-8");
            $this->siteSizeCheck();
            $path = input('get.path') ? preg_replace($this->path_reg, '', input('get.path') . '') : '/';
            $filePath = ROOT_PATH . 'logs' . DS . 'uploads';

            if (!is_dir($filePath)) {
                // 判断临时目录是否存在，不存在则创建
                mkdir(iconv("UTF-8", "GBK", $filePath), 0755, true);
            }

            // 获取文件扩展名
            $temp_arr = explode(".", input('post.f_name'));
            $file_ext = array_pop($temp_arr);
            $file_ext = trim($file_ext);
            $file_ext = strtolower($file_ext);

            // 获取加密文件名
            $file_name = md5(input('post.f_name')) . '.' . $file_ext;
            // 由于中文名上传失败
            // $info = $files->move($filePath, input('post.f_name'));
            $info = $files->move($filePath, $file_name);
            $data = '';
            if ($info) {
                $postFile = $filePath . DS . $info->getFilename();
                // $postFile = $filePath.DS.'1235556.png';
                // iconv("UTF-8","gb2312",$postFile);
                if (class_exists('CURLFile')) {
                    // php 5.5
                    $data = new \CURLFile(realpath($postFile));
                } else {
                    $data = '@' . realpath($postFile);
                }
                // $data->postname = $info->getFilename();
                // $fileName       = $info->getFilename();
                // 传递原始文件名到服务器中
                $fileName = input('post.f_name');
                $f_size = input('post.f_size');
                $f_start = input('post.f_start');
                $up = $this->btPanel->UploadFiles($WebGetKey . $path, $fileName, $f_size, $f_start, $data);
                if ($up && is_numeric($up)) {
                    return $up;
                } elseif (isset($up['status']) && $up['status'] == 'true') {
                    $this->success(__('Success'));
                } else {
                    $this->error(__('Fail'));
                }
            } else {
                $this->error(__('%s fail', __('file')));
            }
        }
        //老上传接口
        if ($files = request()->file('zunfile')) {
            $php_upload_max = byteconvert(ini_get('upload_max_filesize'));
            $path = input('get.path') ? preg_replace($this->path_reg, '', input('get.path') . '') : '/';
            $filePath = ROOT_PATH . 'logs' . DS . 'uploads';

            if (!is_dir($filePath)) {
                // 判断临时目录是否存在，不存在则创建
                mkdir(iconv("UTF-8", "GBK", $filePath), 0755, true);
            }

            $info = $files->validate(['size' => $php_upload_max])->move($filePath, '');

            if ($info) {
                $postFile = $filePath . DS . $info->getFilename();
                if (class_exists('CURLFile')) {
                    // php 5.5
                    $data['zunfile'] = new \CURLFile(realpath($postFile));
                } else {
                    $data['zunfile'] = '@' . realpath($postFile);
                }
                $data['zunfile']->postname = $info->getFilename();
                $up = $this->btPanel->UploadFile($WebGetKey . $path, $data);
                if ($up && isset($up['status']) && $up['status'] == 'true') {
                    $this->success(__('Success'));
                } else {
                    $this->error(__('Fail'));
                }
            } else {
                $this->error(__('%s fail', __('file')));
            }
        }
        //获取文件内容
        if ($type == 'getfile') {
            if (!$file) $this->error(__('Please select file'));
            if (!$this->path_root_check($WebGetKey . $file, $WebGetKey)) {
                $this->error(__('Illegal operation'));
            }
            $fileContent = $this->btPanel->GetFileBodys($WebGetKey . $file);
            if ($fileContent && isset($fileContent['status']) && $fileContent['status'] == 'true') {
                return ['code' => 1, 'msg' => __('Success'), 'data' => $fileContent['data'], 'encoding' => $fileContent['encoding']];
            } elseif ($fileContent && isset($fileContent['msg'])) {
                return ['code' => 0, 'msg' => $fileContent['msg']];
            } else {
                $this->error(__('Fail'));
            }
        }
        //保存文件
        if ($type == 'savefile') {
            if (!$file) $this->error(__('Please select file'));
            $content = input('post.content', '', null);
            $encoding = input('post.encoding') ? input('post.encoding') : 'utf-8';
            if (!$this->path_root_check($WebGetKey . $file, $WebGetKey)) {
                $this->error(__('Illegal operation'));
            }
            $savefile = $this->btPanel->SaveFileBodys($content, $WebGetKey . $file, $encoding);
            if ($savefile && isset($savefile['status']) && $savefile['status'] == 'true') {
                $this->success(__('Success'));
            } else {
                $this->error(__('Fail') . @$savefile['msg']);
            }
        }
        //查看图片
        if (input('post.type') == 'images') {
            // 中文文件名图片查看错误，本地服务器屏蔽关键词“你”，原因未知
            // WIndows下查看中文图片失败，原因带空格
            // 经过urlencode编码后双系统使用正常
            $file = input('post.file') ? preg_replace('/([.]){2,}|([\/]){2,}/', '', input('post.file')) : '';
            $images = $this->btPanel->images_view(
                $WebGetKey . $file,
                $file
            );
            // header('Content-type: image/jpeg');
            return json(['image' => $images]);
        }
        // 批量操作
        if ($type == 'batch') {
            $path = input('post.path') ? preg_replace($this->path_reg, '', input('post.path') . '') : '/';
            $data = $this->request->post('data');
            $batch = $this->request->post('batch');
            if ($data == '' || $path == '') $this->error(__('Request error'));
            switch ($batch) {
                case 'del':
                    // 批量删除
                    $data_arr = explode(',', $data);

                    foreach ($data_arr as $key => $value) {
                        if ($data_arr[$key] == $WebGetKey) {
                            $this->error(__('Illegal operation'));
                        }
                        if ($data_arr[$key] == $WebGetKey . '/') {
                            $this->error(__('Illegal operation'));
                        }
                    }
                    $data = json_encode($data_arr);

                    $del = $this->btPanel->SetBatchData($WebGetKey . $path, 4, $data);
                    if ($del && isset($del['status']) && $del['status'] == 'true') {
                        $this->success(__('Success'));
                    } elseif (isset($del['msg'])) {
                        $this->error(__('Fail') . $del['msg']);
                    } else {
                        $this->error(__('Fail'));
                    }
                    break;
                case 'openZip':

                    break;
                case 'CutFile':
                case 'CutFiles':
                    // 批量复制/剪切
                    $this->clear_cutorcopy_cookie('all');
                    $data_arr = explode(',', $data);

                    $data = json_encode($data_arr);
                    $cookie_name = $batch == 'CutFile' ? 'batchcutFileName' : 'batchcopyFileName';
                    $cookie_name1 = $batch == 'CutFile' ? 'CutFile' : 'CutFiles';
                    // 缓存复制/剪切文件/目录名
                    Cookie::set(
                        $cookie_name,
                        $data,
                        ['prefix' => 'vhost_cutcopy_', 'expire' => 3600]
                    );
                    // 缓存所在目录
                    Cookie::set('batch_path_name', $path, ['prefix' => 'vhost_cutcopy_', 'expire' => 3600]);
                    $type = $batch == 'CutFile' ? 2 : 1;
                    Cookie::set($cookie_name1, 1, ['prefix' => 'vhost_cutcopy_', 'expire' => 3600]);
                    $msgs = $batch == 'CutFile' ? 'Cut success .%s' : 'Copy success .%s';
                    $this->success(__($msgs, __('Please choose a suitable location to paste')));

                    // 以下接口失效
                    // $CutFiles = $this->btPanel->SetBatchData($WebGetKey . $path, $type, $data);
                    // if ($CutFiles && isset($CutFiles['status']) && $CutFiles['status'] == 'true') {
                    //
                    // }elseif(isset($CutFiles['msg'])){
                    //     $this->error(__('Fail').$CutFiles['msg']);
                    // }else {
                    //     $this->error(__('Fail'));
                    // }
                    // end

                    break;
                default:
                    $this->error(__('Request error'));
                    break;
            }
        }
        // 执行粘贴批量复制/剪切的任务
        if ($type == 'BatchPaste') {
            $ty = $this->request->post('ty/d', 1);
            $path = input('post.path') ? preg_replace($this->path_reg, '', input('post.path') . '') : '/';
            // if (!$path) {
            //     $this->error(__('Illegal operation'));
            // }
            $arr_success = $arr_error = [];
            // 遍历法移动或复制相关文件
            // 获取剪切板文件名
            if ($ty == 1) {
                // 拷贝粘贴
                $filelist = Cookie::get('batchcopyFileName', 'vhost_cutcopy_');
                $data_arr = json_decode($filelist, 1);
            } else {
                // 移动粘贴
                $filelist = Cookie::get('batchcutFileName', 'vhost_cutcopy_');
                $data_arr = json_decode($filelist, 1);
            }
            if ($data_arr) {
                foreach ($data_arr as $key => $value) {
                    // 检查文件/文件名安全性
                    $value = preg_replace($this->path_reg, '', $value . '');
                    $batch_path_name = $filelist = Cookie::get('batch_path_name', 'vhost_cutcopy_');
                    $sfile = $WebGetKey . $batch_path_name . $value;
                    $dfile = $WebGetKey . $path . $value;
                    if (!$this->path_root_check($sfile, $WebGetKey)) {
                        $this->error(__('Illegal operation'));
                    }
                    if (!$this->path_root_check($dfile, $WebGetKey)) {
                        $this->error(__('Illegal operation'));
                    }

                    if ($ty == 1) {
                        $set = $this->CopyFile($sfile, $dfile);
                    } else {
                        $set = $this->MvFile($sfile, $dfile);
                    }
                    if ($set) {
                        $arr_success[] = __('%s success', $value);
                    } else {
                        $arr_error[] = __('%s fail', $value) . $this->_error;
                    }
                }
            }
            // if($data_arr){
            //     foreach($data_arr as $key=>$value){
            //         $value = preg_replace($this->path_reg,'',$value . '');
            //         $sfile = $WebGetKey . '/'.$value;
            //         $dfile = $WebGetKey . $path.$value;
            //         if (!$this->path_root_check($sfile, $WebGetKey)) {
            //             $this->error(__('Illegal operation'));
            //         }
            //         if (!$this->path_root_check($dfile, $WebGetKey)) {
            //             $this->error(__('Illegal operation'));
            //         }
            //         // 检查文件/文件名安全性
            //
            //         if($set){
            //             $successArr[] = __('%s success',$value);
            //         }else{
            //             $errorArr[] = __('%s fail',$value);
            //         }
            //     }
            // }
            $this->clear_cutorcopy_cookie('all');
            $ms = __('Success') . "(" . count($arr_success) . ")：" . implode(',', $arr_success) . "<br>" . __('Fail') . "(" . count($arr_error) . ")：" . implode(',', $arr_error);
            $this->success($ms);

            // 循环移动到指定目录

            // $bat = $this->btPanel->BatchPaste($WebGetKey . $path, $ty);
            // if ($bat && isset($bat['status']) && $bat['status'] == 'true') {
            //     $this->success($bat['msg']);
            // } elseif (isset($bat['msg'])) {
            //     $this->error($bat['msg']);
            // } else {
            //     $this->error(__('Fail'));
            // }
        }
        // 远程下载
        if ($type == 'DownloadFile') {
            // TODO 下载后文件权限为root，可能存在安全隐患
            // 队列ID出来之后再进行队列监控转换文件组权限
            $this->error(__('Not currently supported %s', ''));
            $path = input('post.path') ? preg_replace($this->path_reg, '', input('post.path') . '') : '';
            $mUrl = input('post.mUrl') ? preg_replace($this->path_reg, '', input('post.mUrl') . '') : '';
            $dfilename = input('post.dfilename') ? preg_replace($this->path_reg, '', input('post.dfilename') . '') : '';
            if (!$mUrl) $this->error(__('%s error', __('Download url')));
            if (!$dfilename) $this->error(__('%s error', __('File name')));
            if (!$this->path_root_check($WebGetKey . $path, $WebGetKey)) {
                $this->error(__('Illegal operation'));
            }

            $this->siteSizeCheck();

            $down = $this->btPanel->DownloadFile($WebGetKey . $path, $mUrl, $dfilename);
            if ($down && isset($down['status']) && $down['status'] == 'true') {
                $this->success($down['msg']);
            } else {
                $this->error(__('Fail'));
            }
        }
        // TODO 获取队列（目前没有任务ID对应）
        if ($type == 'get_task_lists') {
        }

        $search = $this->request->get('search');
        // 目前子目录搜索有问题
        // $all = $this->request->get('all')?'True':'';
        $dirList = $this->btPanel->GetDir($Webpath, 1, $search);
        if (isset($dirList['status']) && $dirList['status'] != 'true') {
            $this->error(__('%s not found', __('Dir')), '');
        }

        //文件夹列表
        if (isset($dirList['DIR']) && $dirList['DIR'] != '') {
            foreach ($dirList['DIR'] as $key => $value) {
                if (preg_match($this->reg_file, $dirList['DIR'][$key])) {
                    unset($dirList['DIR'][$key]);
                } else {
                    $dirList['DIR'][$key] = preg_grep("/\S+/i", explode(';', $dirList['DIR'][$key]));
                }
            }
        }
        //文件列表
        if (isset($dirList['FILES']) && $dirList['FILES'] != '') {
            foreach ($dirList['FILES'] as $key => $value) {

                if (preg_match($this->reg_file, $dirList['FILES'][$key])) {
                    unset($dirList['FILES'][$key]);
                } else {
                    $dirList['FILES'][$key] = preg_grep("/\S+/i", explode(';', $dirList['FILES'][$key]));
                }
            }
        }
        // api版文件接口（未完善）
        if ($this->request->get('callback')) {
            // 处理目录与文件数据
            $path_file_arr_paths = $path_file_arr_files = $list = [];
            foreach ($dirList['DIR'] as $key => $value) {
                if (1 || $value['4'] != 'root') {
                    foreach ($value as $key2 => $value2) {
                        if (in_array($value2, ['.well-known', 'web_config', '../', '..'])) {
                            break;
                        }
                        switch ($key2) {
                            case '0':
                                $path_file_arr_paths[$key]['name'] = $value2;
                                break;
                            case '1':
                                $path_file_arr_paths[$key]['size'] = $value2;
                                break;
                            case '2':
                                $path_file_arr_paths[$key]['time'] = $value2 ? date("Y-m-d", $value2) : $value2;
                                break;
                            case '3':
                                $path_file_arr_paths[$key]['auths'] = $value2;
                                break;
                            case '4':
                                $path_file_arr_paths[$key]['group'] = $value2;
                                break;
                            default:
                                $path_file_arr_paths[$key][] = $value2;
                                break;
                        }
                    }
                }
            }
            foreach ($dirList['FILES'] as $key => $value) {
                foreach ($value as $key2 => $value2) {
                    if (in_array($value2, ['.user.ini'])) {
                        break;
                    }
                    switch ($key2) {
                        case '0':
                            $path_file_arr_files[$key]['name'] = $value2;
                            break;
                        case '1':
                            $path_file_arr_files[$key]['size'] = $value2 && is_numeric($value2) ? formatBytes($value2) : $value2;
                            break;
                        case '2':
                            $path_file_arr_files[$key]['time'] = $value2 && is_numeric($value2) ? date("Y-m-d", $value2) : $value2;
                            break;
                        case '3':
                            $path_file_arr_files[$key]['auths'] = $value2;
                            break;
                        case '4':
                            $path_file_arr_files[$key]['group'] = $value2;
                            break;
                        default:
                            $path_file_arr_files[$key][] = $value2;
                            break;
                    }
                }
            }

            $list = array_merge($path_file_arr_paths, $path_file_arr_files);

            $total = count($list);
            return jsonp(['rows' => $list, 'total' => $total], 200);
        }
        // 最大上传文件大小
        $php_upload_max = byteconvert(ini_get('upload_max_filesize'));

        $this->view->assign('title', __('Web file manager'));
        return view('file', [
            'search'         => $this->request->get('search'),
            'viewpath'       => $path,
            'dirList'        => $dirList,
            'php_upload_max' => $php_upload_max,
        ]);
    }

    /**
     * 文件移动
     * @param $sfile    原文件路径
     * @param $dfile    新文件路径
     * @return bool
     */
    private function MvFile($sfile, $dfile)
    {
        $MvFile = $this->btPanel->MvFile($sfile, $dfile);
        if ($MvFile && isset($MvFile['status']) && $MvFile['status'] == 'true') {
            return true;
        } elseif (isset($MvFile['msg'])) {
            $this->_error = $MvFile['msg'];
            return false;
        } else {
            return false;
        }
    }

    /**
     * 文件拷贝
     * @param $sfile    原文件路径
     * @param $dfile    新文件路径
     * @return bool
     */
    private function CopyFile($sfile, $dfile)
    {
        $copy = $this->btPanel->CopyFile($sfile, $dfile);
        if ($copy && isset($copy['status']) && $copy['status'] == 'true') {
            return true;
        } elseif (isset($copy['msg'])) {
            $this->_error = $copy['msg'];
            return false;
        } else {
            return false;
        }
    }

    // 网站备份
    public function back()
    {
        $WebBackupList = $SqlBackupList = [];

        $WebBackupList = $this->btPanel->WebBackupList($this->bt_id);
        if (isset($WebBackupList['data'][0])) {
            foreach ($WebBackupList['data'] as $key => $value) {
                $WebBackupList['data'][$key]['size'] = formatBytes($WebBackupList['data'][$key]['size']);
                // 下载备份文件
                if (input('get.down_back_file') == $WebBackupList['data'][$key]['name']) {
                    $filePath = $WebBackupList['data'][$key]['filename'];
                    $fileName = $WebBackupList['data'][$key]['name'];
                    $down = $this->btPanel->download($filePath, $fileName);
                    if ($down && isset($down['status']) && $down['status'] == 'false') {
                        $this->success($down['msg']);
                    }
                    exit;
                }
            }
        }

        if (isset($this->hostInfo->sql->username) || $this->hostInfo->sql->username) {
            //获取数据库ID
            $WebSqlList = $this->btPanel->WebSqlList($this->hostInfo->sql->username);
            if (!$WebSqlList || !isset($WebSqlList['data'][0])) {
                $SqlBackupList['data'] = [];
                // $this->error(__('Database not found'),'');
            } else {
                //获取数据库备份列表
                $SqlBackupList = $this->btPanel->WebBackupList($WebSqlList['data'][0]['id'], '1', '5', '1');

                if (isset($SqlBackupList['data'][0])) {
                    foreach ($SqlBackupList['data'] as $key => $value) {
                        $SqlBackupList['data'][$key]['size'] = formatBytes($SqlBackupList['data'][$key]['size']);
                        // 下载备份文件
                        if (input('get.down_back_sql') == $SqlBackupList['data'][$key]['name']) {
                            $filePath = $SqlBackupList['data'][$key]['filename'];
                            $fileName = $SqlBackupList['data'][$key]['name'];
                            $down = $this->btPanel->download($filePath, $fileName);
                            if ($down && isset($down['status']) && $down['status'] == 'false') {
                                $this->success($down['msg']);
                            }
                            exit;
                        }
                    }
                }
            }
        }

        $this->view->assign('title', __('back'));
        return view('back', [
            'has_sql'        => $this->hostInfo->sql->username ? true : false,
            'countback_site' => isset($WebBackupList['data']) ? count($WebBackupList['data']) : 0,
            'WebBackupList'  => $WebBackupList,
            'SqlBackupList'  => $SqlBackupList,
            'countback_sql'  => isset($SqlBackupList['data']) ? count($SqlBackupList['data']) : 0,
        ]);
    }

    // 网站备份创建
    public function webBackInc()
    {
        $WebBackupList = $this->btPanel->WebBackupList($this->bt_id);
        $securityArray = [];
        foreach ($WebBackupList['data'] as $key => $value) {
            $securityArray[$key] = $WebBackupList['data'][$key]['id'];
        }
        $post_str = $this->request->post();
        if (isset($post_str['to']) && $post_str['to'] == 'back') {
            $back_num = isset($this->hostInfo->web_back_num) ? $this->hostInfo->web_back_num : 5;
            if ($back_num == 0 || count($WebBackupList['data']) < $back_num) {
                if ($modify_status = $this->btPanel->WebToBackup($this->bt_id)) {
                    $this->success($modify_status['msg']);
                } else {
                    $this->error(__('Fail') . '：' . $modify_status['msg']);
                }
            } else {
                $this->error(__('Exceed the number of available backups, please delete the original backup and execute again'));
            }
        } else {
            $this->error(__('Illegal request'));
        }
    }

    // 网站备份删除
    public function webBackDel()
    {

        $WebBackupList = $this->btPanel->WebBackupList($this->bt_id);
        $securityArray = [];
        foreach ($WebBackupList['data'] as $key => $value) {
            $securityArray[$key] = $WebBackupList['data'][$key]['id'];
        }
        $post_str = $this->request->post();
        if (isset($post_str['del'])) {
            if (in_array($post_str['del'], $securityArray)) {
                if ($modify_status = $this->btPanel->WebDelBackup($post_str['del'])) {
                    $this->success($modify_status['msg']);
                } else {
                    $this->error(__('Fail') . '：' . $modify_status['msg']);
                }
            } else {
                $this->error(__('Illegal request'));
            }
        } else {
            $this->error(__('Illegal request'));
        }
    }

    // FTP开关
    public function ftpStatus()
    {
        $ftpId = $this->btAction->getFtpInfo('id');
        if (!$ftpId) $this->error(__('This service is not currently available'));
        $ftp = $this->request->post('ftp');
        if ($ftp && $ftp == 'off') {
            $status = 0;
        } elseif ($ftp && $ftp == 'on') {
            $status = 1;
        } else {
            $this->error(__('Illegal request'));
        }
        $modify_status = $this->btAction->FtpStatus($status);
        if (!$modify_status) $this->error(__('Fail') . '：' . $this->btAction->getError());
        $this->success(__('Success'));
    }

    // Mysql数据库工具箱
    public function sqlTools()
    {
        if ($this->hostInfo->server_os == 'windows') $this->error(__('The plug-in is not supported by the current host'), '');

        if (!isset($this->hostInfo->sql->username) || !$this->hostInfo->sql->username) {
            $this->error(__('This service is not currently available'), '');
        }
        $sqlInfo = $this->btPanel->WebSqlList($this->hostInfo->sql->username);
        if (!$sqlInfo || !isset($sqlInfo['data']['0'])) {
            $this->error(__('This service is not currently available'), '');
        }
        $mysql_list = $this->btPanel->GetSqlSize($this->hostInfo->sql->username);
        if ($this->request->get('callback')) {
            $list = $mysql_list['tables'];
            $count = count($list);
            return jsonp(['rows' => $list, 'total' => $count]);
        }
        $this->view->assign('title', __('sqltools'));
        return $this->view->fetch('sqltools', [
            'mysql_list' => $mysql_list,
        ]);
    }

    // Mysql工具箱操作
    public function sqlToolsAction()
    {
        $type = $this->request->post('type');
        $tables = $this->request->post('tables');
        if (!$tables) $this->error(__('Please select table name'));
        $tables = array_filter(explode(',', $tables));
        if ($type == 'retable') {
            // 修复表
            $re = $this->btPanel->ReTable($this->hostInfo->sql->username, json_encode($tables));
            if ($re && isset($re['status']) && $re['status'] == 'true') {
                $this->success($re['msg']);
            } elseif ($re && isset($re['msg'])) {
                $this->error($re['msg']);
            } else {
                $this->error(__('Fail'));
            }
        } elseif ($type == 'optable') {
            // 优化表
            $op = $this->btPanel->OpTable($this->hostInfo->sql->username, json_encode($tables));
            if ($op && isset($op['status']) && $op['status'] == 'true') {
                $this->success($op['msg']);
            } elseif ($op && isset($op['msg'])) {
                $this->error($op['msg']);
            } else {
                $this->error(__('Fail'));
            }
        } elseif ($type == 'aitable' || $type == 'InnoDB' || $type == 'MyISAM') {
            // 转换类型
            $table_type = $this->request->post('table_type') == 'MyISAM' ? 'MyISAM' : 'InnoDB';
            $al = $this->btPanel->AlTable($this->hostInfo->sql->username, json_encode($tables), $table_type);
            if ($al && isset($al['status']) && $al['status'] == 'true') {
                $this->success($al['msg']);
            } elseif ($al && isset($al['msg'])) {
                $this->error($al['msg']);
            } else {
                $this->error(__('Fail'));
            }
        } else {
            $this->error(__('Request error, please try again later'));
        }
    }

    // 数据库备份生成
    public function sqlBackInc()
    {
        if (!isset($this->hostInfo->sql->username) || !$this->hostInfo->sql->username) {
            $this->error(__('This service is not currently available'), '');
        }
        //获取数据库ID
        $WebSqlList = $this->btPanel->WebSqlList($this->hostInfo->sql->username);
        if (!$WebSqlList || !isset($WebSqlList['data'][0])) {
            $this->error(__('This service is not currently available'));
        }
        //获取数据库备份列表
        $WebBackupList = $this->btPanel->WebBackupList($WebSqlList['data'][0]['id'], '1', '5', '1');

        $securityArray = [];
        foreach ($WebBackupList['data'] as $key => $value) {
            $securityArray[$key] = $WebBackupList['data'][$key]['id'];
        }
        $post_str = $this->request->post();
        if (isset($post_str['to']) && $post_str['to'] == 'back') {
            $back_num = isset($this->hostInfo->sql_back_num) ? $this->hostInfo->sql_back_num : 5;
            if ($back_num == 0 || count($WebBackupList['data']) < $back_num) {
                if ($modify_status = $this->btPanel->SQLToBackup($WebSqlList['data'][0]['id'])) {
                    $this->success($modify_status['msg']);
                } else {
                    $this->error(__('Fail') . '：' . $modify_status['msg']);
                }
            } else {
                $this->error(__('Exceed the number of available backups, please delete the original backup and execute again'));
            }
        } else {
            $this->error(__('Illegal request'));
        }
    }

    // 数据库备份删除
    public function sqlBackDel()
    {
        if (!isset($this->hostInfo->sql->username) || !$this->hostInfo->sql->username) {
            $this->error(__('This service is not currently available'), '');
        }
        //获取数据库ID
        $WebSqlList = $this->btPanel->WebSqlList($this->hostInfo->sql->username);
        if (!$WebSqlList || !isset($WebSqlList['data'][0])) {
            $this->error(__('This service is not currently available'));
        }
        //获取数据库备份列表
        $WebBackupList = $this->btPanel->WebBackupList($WebSqlList['data'][0]['id'], '1', '5', '1');

        $securityArray = [];
        foreach ($WebBackupList['data'] as $key => $value) {
            $securityArray[$key] = $WebBackupList['data'][$key]['id'];
        }
        $post_str = $this->request->post();
        if (in_array($post_str['del'], $securityArray)) {
            if ($modify_status = $this->btPanel->SQLDelBackup($post_str['del'])) {
                $this->success($modify_status['msg']);
            } else {
                $this->error(__('Delete fail') . $modify_status['msg']);
            }
        } else {
            $this->error(__('Illegal operation'));
        }
    }

    // 数据库备份下载
    public function sqlBackDown()
    {
        if (!isset($this->hostInfo->sql->username) || !$this->hostInfo->sql->username) {
            $this->error(__('This service is not currently available'), '');
        }

        //获取数据库ID
        $WebSqlList = $this->btPanel->WebSqlList($this->hostInfo->sql->username);
        if (!$WebSqlList || !isset($WebSqlList['data'][0])) {
            $this->error(__('Database not found'));
        }
        //获取数据库备份列表
        $WebBackupList = $this->btPanel->WebBackupList($WebSqlList['data'][0]['id'], '1', '5', '1');

        if (isset($WebBackupList['data'][0])) {
            foreach ($WebBackupList['data'] as $key => $value) {
                $WebBackupList['data'][$key]['size'] = formatBytes($WebBackupList['data'][$key]['size']);
                // 下载备份文件
                if (input('get.down_back_sql') == $WebBackupList['data'][$key]['name']) {
                    $filePath = $WebBackupList['data'][$key]['filename'];
                    $fileName = $WebBackupList['data'][$key]['name'];
                    $down = $this->btPanel->download($filePath, $fileName);
                    if ($down && isset($down['status']) && $down['status'] == 'false') {
                        $this->success($down['msg']);
                    }
                    exit;
                }
            }
        }
    }

    // 数据库备份还原
    public function sqlInputSql()
    {
        //获取数据库ID
        $WebSqlList = $this->btPanel->WebSqlList($this->hostInfo->sql->username);
        if (!$WebSqlList || !isset($WebSqlList['data'][0])) {
            $this->error(__('This service is not currently available'), '');
        }
        //获取数据库备份列表
        $WebBackupList = $this->btPanel->WebBackupList($WebSqlList['data'][0]['id'], '1', '5', '1');

        if (isset($WebBackupList['data'][0])) {
            foreach ($WebBackupList['data'] as $key => $value) {
                // 下载备份文件
                if (input('post.file') == $value['name']) {
                    if ($modify_status = $this->btPanel->SQLInputSqlFile($value['filename'], $this->hostInfo->sql->username)) {
                        $this->success($modify_status['msg']);
                    } else {
                        $this->error(__('Fail') . '：' . $modify_status['msg']);
                    }
                }
            }
        } else {
            $this->error(__('File acquisition failed'));
        }
    }

    // SSl
    public function Ssl()
    {
        $GetSSL = $this->btPanel->GetSSL($this->siteName);
        $Domains = $this->btPanel->GetSiteDomains($this->bt_id);
        //获取域名绑定列表
        $domainList = $this->btPanel->WebDoaminList($this->bt_id);

        // 获取商用证书列表及价格
        $GetProductList = $this->btPanel->GetProductList();
        $this->view->assign('title', __('ssl'));
        return $this->view->fetch('ssl', [
            'GetProductList' => $GetProductList,
            'Domains'        => $Domains,
            'domainList'     => $domainList,
            'GetSSL'         => $GetSSL,
        ]);
    }

    // 强制https
    public function toHttps()
    {
        $post_str = $this->request->post();
        if ($post_str['toHttps'] == '1') {
            if ($HttpToHttps = $this->btPanel->HttpToHttps($this->siteName)) {
                $this->success($HttpToHttps['msg']);
            } else {
                $this->error(__('Fail') . '：' . $HttpToHttps['msg']);
            }
        } else {
            if ($HttpToHttps = $this->btPanel->CloseToHttps($this->siteName)) {
                $this->success($HttpToHttps['msg']);
            } else {
                $this->error(__('Fail') . '：' . $HttpToHttps['msg']);
            }
        }
    }

    // SSL配置
    public function sslSet()
    {
        $key = input('post.key');
        $csr = input('post.csr');
        if (empty($key) || empty($csr)) $this->error(__('%s can not be empty', ['key or csr']));
        if (preg_match($this->reg_rewrite, $key) || preg_match($this->reg_rewrite, $csr)) {
            $this->error(__('Illegal parameter'));
        }
        $modify_status = $this->btPanel->SetSSL(1, $this->siteName, $key, $csr);
        if (isset($modify_status) && $modify_status['status'] == 'true') {
            $this->success($modify_status['msg']);
        } else {
            $this->error(__('Fail') . '：' . $modify_status['msg']);
        }
    }

    // 关闭SSL
    public function sslOff()
    {
        $post_str = $this->request->post();
        if (isset($post_str['ssl']) && $post_str['ssl'] == 'off') {
            if ($modify_status = $this->btPanel->CloseSSLConf(1, $this->siteName)) {
                $this->success($modify_status['msg']);
            } else {
                $this->error(__('Fail') . '：' . $modify_status['msg']);
            }
        }
    }

    // ssl证书一键申请
    public function sslApply()
    {
        set_time_limit(120);
        $domain = input('post.domain');
        if (!$domain) $this->error(__('%s can not be empty', __('Domain')));
        $domainFind = model('domainlist')->where('vhost_id', $this->vhost_id)->where('domain', 'in', $domain)->find();
        if (!$domainFind) $this->error(__('%s parameters', __('Domain')));
        $WebGetKey = $this->btAction->webRootPath;
        if (!$WebGetKey) $this->error(__('Failed to get root directory'));
        $GetDVSSL = $this->btPanel->GetDVSSL($domain, $WebGetKey);
        // 提交ssl证书申请
        if ($GetDVSSL && isset($GetDVSSL['status']) && $GetDVSSL['status'] == 'true') {
            // 证书申请效验码
            $partnerOrderId = $GetDVSSL['data']['partnerOrderId'];
            // 检查中
            $Completed = $this->btPanel->Completed($this->siteName, $partnerOrderId);
            if ($Completed && isset($Completed['status']) && $Completed['status'] == 'true') {
                // 设置域名证书
                $GetSSLInfo = $this->btPanel->GetSSLInfo($this->siteName, $partnerOrderId);
                if ($GetSSLInfo && isset($GetSSLInfo['status']) && $GetSSLInfo['status'] == 'true') {
                    $this->success(__('Application is successful, please refresh'));
                } elseif ($GetSSLInfo && isset($GetSSLInfo['status']) && $GetSSLInfo['status'] == 'false') {
                    $this->error($GetSSLInfo['msg']);
                } else {
                    $this->error(__('Ssl certificate is being set up, please wait'));
                }
            } elseif ($Completed && isset($Completed['status']) && $Completed['status'] == 'false') {
                $this->error(__('Checking, please confirm that the domain name is resolved correctly and can be accessed'));
            } else {
                $this->error(__('Checking, please confirm that the domain name is resolved correctly and can be accessed'));
            }
        } elseif (
            $GetDVSSL && isset($GetDVSSL['msg'])
        ) {
            // 申请失败
            $this->error($GetDVSSL['msg']);
        } else {
            // 申请异常
            $this->error(__('Data error'));
        }
    }

    // lets证书一键申请
    public function sslApplyLets()
    {
        set_time_limit(120);
        if (!Config::get('site.email')) {
            $this->error(__('The webmaster’s mailbox is not configured'));
        }
        $domains = $this->request->post();
        if (!$domains) {
            $this->error(__('%s can not be empty', __('Domain')));
        }
        $domains_arr = $domains['domain'];
        $domain = implode(',', $domains_arr);

        $domainFind = model('domainlist')->where('vhost_id', $this->vhost_id)->where('domain', 'in', $domain)->find();
        if (!$domainFind) {
            $this->error(__('%s not found', __('Domain')));
        }
        $WebGetKey = $this->btAction->webRootPath;
        if (!$WebGetKey) {
            $this->error(__('Failed to get root directory'));
        }
        // 标记当前用户正在进行这项业务
        Session::set('is_lets', '1');
        $let = $this->btPanel->CreateLet($this->siteName, json_encode($domains_arr), Config::get('site.email'));

        if (isset($let['status']) && $let['status'] == true) {
            // 删除标记
            Session::set('is_lets', null);
            $this->success($let['msg']);
        } elseif (isset($let['status']) && $let['status'] == false) {
            // 删除标记
            Session::set('is_lets', null);
            $this->error($let['msg']['0']);
        } else {
            // 删除标记
            Session::set('is_lets', null);
            $this->error(__('error'));
        }
    }

    // 获取lets证书申请日志
    public function getFileLog()
    {
        // 判断是否正在请求这项业务
        if (Session::get('is_lets')) {
            $num = 10;
            $file = '/www/server/panel/logs/letsencrypt.log';
            $arr = $this->btPanel->getFileLog($file, $num);
            if ($arr && isset($arr['status']) && $arr['status'] == true) {
                $this->success($arr['msg']);
            } elseif (isset($arr['status']) && $arr['status'] == false) {
                $this->error($arr['msg']);
            } else {
                $this->error('Request error, please try again later');
            }
        } else {
            $this->error(__('Unexpected situation'));
        }
    }

    // Lets域名证书续签
    public function sslRenewLets()
    {
        set_time_limit(120);
        $renew = $this->btPanel->RenewLets();
        if ($renew && isset($renew['status']) && $renew['status'] == 'true') {
            $this->success(__('Loading'));
        } elseif ($renew && isset($renew['msg'])) {
            $this->error(__('Fail') . $renew['msg']);
        } else {
            $this->error(__('Request error, please try again later'));
        }
    }

    // 网站防盗链
    public function Protection()
    {

        $GetSecurity = $this->btPanel->GetSecurity($this->bt_id, $this->siteName);

        $this->view->assign('title', __('protection'));

        return view('protection', [
            'GetSecurity' => $GetSecurity,
        ]);
    }

    // 网站防盗链设置
    public function ProtectionSet()
    {
        $post_str = $this->request->post();
        if (!empty($post_str['sec_fix']) || !empty($post_str['sec_domains'])) {
            if (preg_match($this->global_reg, $post_str['sec_fix']) || preg_match($this->global_reg, $post_str['sec_domains'])) {
                $this->error(__('Illegal parameter'));
            }

            $modify_status = $this->btPanel->SetSecurity($this->bt_id, $this->siteName, $post_str['sec_fix'], $post_str['sec_domains']);


            if (isset($modify_status) && $modify_status['status'] == 'true') {
                $this->success($modify_status['msg']);
            } else {
                $this->error(__('Fail') . '：' . $modify_status['msg']);
            }
        } else {
            $this->error(__('%s can not be empty', ''));
        }
    }

    // 防盗链关闭
    public function ProtectionOff()
    {
        $GetSecurity = $this->btPanel->GetSecurity($this->bt_id, $this->siteName);
        $post_str = $this->request->post();
        if (isset($post_str['protection']) && $post_str['protection'] == 'off') {
            $modify_status = $this->btPanel->SetSecurity($this->bt_id, $this->siteName, $GetSecurity['fix'], $GetSecurity['domains'], false);
            if (isset($modify_status) && $modify_status['status'] == 'true') {
                $this->success($modify_status['msg']);
            } else {
                $this->error(__('Fail') . '：' . $modify_status['msg']);
            }
        }
    }

    // 网站日志
    public function Sitelog()
    {
        $logList = $this->btPanel->GetSiteLogs($this->siteName);
        if ($logList['msg']) {
            if (isset($logList['status']) && $logList['status'] == 'true') {
                $logArr = explode("\n", $logList['msg']);
            } else {
                $logArr = '';
            }
        } else {
            $logArr = '';
        }
        if ($this->request->get('down')) {
            // 导出日志文件excel
            $logs = $this->btAction->getLogsFileName();
            if ($logs === false) {
                $this->error($this->btAction->_error);
            }
            // 拆分成数组
            // $arr = explode("\n",$logs);
            $text = $logs;
            header("Pragma: public");
            header("Expires: 0");
            header("Cache-Control:must-revalidate, post-check=0, pre-check=0");
            header("Content-Type:application/force-download");
            header("Content-Type:application/octet-stream");
            header("Content-Type:application/download");
            header('Content-Disposition:attachment;filename="' . $this->siteName . '_' . date("Y/m/d_H:i:s") . '_' . __('Site log') . '.log"');
            header("Content-Transfer-Encoding:binary");
            echo $text;
            exit;
        }

        $this->view->assign('title', __('sitelog'));

        return view('sitelog', [
            'logList' => $logArr,
        ]);
    }

    // 密码访问
    public function Httpauth()
    {
        if ($this->hostInfo->server_os == 'windows') $this->error(__('The plug-in is not supported by the current host'), '');
        $vhost_url = $this->btAction->webRootPath;
        $setting = $this->btPanel->GetDirUserINI($this->bt_id, $vhost_url);

        $this->view->assign('title', __('httpauth'));
        return view('httpauth', [
            'pass_status' => $setting['pass'],
        ]);
    }

    // 密码访问配置
    public function httpauthSet()
    {
        if ($this->hostInfo->server_os == 'windows') $this->error(__('The plug-in is not supported by the current host'), '');
        $post_str = $this->request->post();
        if (!empty($post_str['username']) && !empty($post_str['password'])) {
            if (preg_match($this->global_reg, $post_str['username']) || preg_match($this->global_reg, $post_str['password'])) {
                $this->error(__('Illegal parameter'));
            }
            $modify_status = $this->btPanel->SetHasPwd($this->bt_id, $post_str['username'], $post_str['password']);
            if (isset($modify_status) && $modify_status['status'] == 'true') {
                $this->success($modify_status['msg']);
            } else {
                $this->error(__('Fail') . '：' . $modify_status['msg']);
            }
        } else {
            $this->error(__('Username or password is incorrect'));
        }
    }

    // 密码访问关闭
    public function httpauthOff()
    {
        if ($this->hostInfo->server_os == 'windows') $this->error(__('The plug-in is not supported by the current host'), '');
        $post_str = $this->request->post();
        if (isset($post_str['auth']) && $post_str['auth'] == 'off') {
            if ($modify_status = $this->btPanel->CloseHasPwd($this->bt_id)) {
                $this->success($modify_status['msg']);
            } else {
                $this->error(__('Fail') . '：' . $modify_status['msg']);
            }
        } else {
            $this->error(__('Illegal request'));
        }
    }

    // 目录保护
    public function dirAuth()
    {
        $list = $this->btPanel->get_dir_auth($this->bt_id);
        if ($list && isset($list['status']) && $list['status'] == false) {
            $this->error($list['msg']);
        }

        $this->view->assign('title', __('dirauth'));
        return view('dirauth', [
            'dirAuthList' => isset($list[$this->siteName]) ? $list[$this->siteName] : '',
        ]);
    }

    // 添加目录保护
    public function setDirAuth()
    {
        $siteDir = input('post.sitedir');
        $username = input('post.username', '', 'htmlspecialchars');
        $passwd = input('post.passwd', '', 'htmlspecialchars');
        if (!$siteDir || !$username || !$passwd) $this->error(__('%s can not be empty', ''));
        $siteDir = preg_replace('/([.|\/]){2,}/', '', $siteDir);

        if (!$this->path_safe_check($siteDir)) $this->error(__('Illegal parameter'));

        if (preg_match($this->global_reg, $siteDir) || preg_match($this->global_reg, $username) || preg_match($this->global_reg, $passwd)) {
            $this->error(__('Illegal parameter'));
        }
        // $randName = Random::alnum(6);

        if ($add = $this->btPanel->set_dir_auth($this->bt_id, $username, $siteDir, $username, $passwd)) {
            $this->success($add['msg']);
        } else {
            $this->error(__('Fail') . '：' . $add['msg']);
        }
    }

    // 删除目录保护
    public function delDirAuth()
    {
        $delName = input('post.delname');
        if (!$delName) $this->error(__('Request error'));
        $del = $this->btPanel->delete_dir_auth($this->bt_id, $delName);
        if (isset($del) && $del['status'] == 'true') {
            $this->success($del['msg']);
        } else {
            $this->error(__('Fail') . '：' . $del['msg']);
        }
    }

    // 获取网站运行目录
    public function runPath()
    {
        $WebGetKey = $this->btAction->webRootPath;
        if (!$WebGetKey) $this->error(__('Failed to get root directory'));
        $path = $this->btAction->dirUserIni;
        if ($path && isset($path['runPath']) && $path['runPath'] != '') {
            if (isset($path['runPath']['dirs'])) {
                // 轮询过滤网站重要配置目录，防止意外错误
                foreach ($path['runPath']['dirs'] as $key => $value) {
                    if ($value == '/web_config') {
                        unset($path['runPath']['dirs'][$key]);
                    }
                    if ($value == '/.well-known') {
                        unset($path['runPath']['dirs'][$key]);
                    }
                }
            }
            $this->view->assign('title', __('runPath'));
            return $this->view->fetch('path', [
                'runPath' => $path['runPath'],
            ]);
        } else {
            $this->error(__('Failed to get runpath directory'));
        }
    }

    // 网站运行目录配置
    public function setSiteRunPath()
    {
        switch ($this->hostInfo->status) {
            case 'normal':
                break;
            case 'stop':
                $this->error(__('Site is %s', __('stop')), '');
                break;
            case 'locked':
                $this->error(__('Site is %s', __('locked')), '');
                break;
            case 'expired':
                $this->error(__('Site is %s', __('expired')), '');
                break;
            case 'excess':
                $this->error(__('Site is %s', __('excess')), '');
                break;
            case 'error':
                $this->error(__('Site is %s', __('error')), '');
                break;
            default:
                $this->error(__('Site is %s', __('error')), '');
                break;
        }
        if ($this->request->post()) {
            $dirs = input('post.dirs') ? preg_replace('/([.]){2,}/', '', input('post.dirs')) : '';
            // 增加非法参数过滤
            if (preg_match($this->global_reg, $dirs) || preg_match($this->reg_rewrite, $dirs)) {
                $this->error(__('Illegal parameter'));
            }
            // 过滤危险目录
            $dir_arr = [
                '/.well-known',
                '/web_config',
                '//',
                '../',
                './/',
                '..//',
            ];

            if (in_array($dirs, $dir_arr)) {
                $this->error(__('Hazard dir %s', $dirs));
            }

            $runPath = $dirs ? preg_replace('/([.]){2,}/', '', $dirs) : '';
            $set = $this->btPanel->SetSiteRunPath($this->bt_id, $runPath);
            if ($set && isset($set['status']) && $set['status'] == 'true') {
                $this->success($set['msg']);
            } else {
                $this->error(__('Fail') . '：' . $set['msg']);
            }
        } else {
            $this->error(__('Illegal request'));
        }
    }

    // 一键部署
    public function deployment()
    {
        // $deploymentList = $this->btPanel->deployment();
        $deploymentList = Cache::remember('deploymentlist', function () {
            return $this->btPanel->deployment();
        });
        if (!$deploymentList || isset($deploymentList['status']) && $deploymentList['status'] == false) {
            $this->error(__('This service is not currently available'), '');
        }
        //程序列表倒叙
        $deploymentList['data'] = array_reverse($deploymentList['data']);


        $this->view->assign('title', __('deployment'));
        return view('deployment', [
            'deploymentList' => $deploymentList,
        ]);
    }

    // 一键部署到网站(兼容老版和新版)
    public function deploymentSet()
    {
        $post_str = $this->request->post();
        $is_new = input('post.is_new') ? input('post.is_new') : 0;
        // $deploymentList = $is_new ? $this->btPanel->GetList() : $this->btPanel->deployment();

        $deploymentList = Cache::remember($is_new ? 'deploymentlist_new' : 'deploymentlist', function ($is_new) {
            return $is_new ? $this->btPanel->GetList() : $this->btPanel->deployment();
        });
        if (!$deploymentList || isset($deploymentList['status']) && $deploymentList['status'] == false) {
            $is_new ? '' : $this->error(__('This service is not currently available'));
        }
        if ($dep = $post_str['dep']) {
            $is_inarray = false;
            $data = $is_new ? $deploymentList['list'] : $deploymentList['data'];
            foreach ($data as $key => $value) {
                if (in_array($dep, $data[$key])) {
                    $is_inarray = true;
                    break;
                }
            }
            if ($is_inarray) {
                $SetupPackage = $is_new ? $this->btPanel->SetupPackageNew($dep, $this->siteName, $this->btAction->getSitePhpVer($this->siteName)) : $this->btPanel->SetupPackage($dep, $this->siteName, $this->btAction->getSitePhpVer($this->siteName));
                if ($SetupPackage && isset($SetupPackage['status']) && $SetupPackage['status'] == true) {
                    $this->success(__('Completed'));
                } elseif (isset($SetupPackage['msg'])) {
                    $this->error($SetupPackage['msg']);
                } else {
                    $this->error(__('Request timed out, please wait to see if the website is deployed'));
                }
            } else {
                $this->error(__('%s parameters', __('Name')));
            }
        } else {
            $this->error(__('Can not be empty'));
        }
    }

    // 一键部署列表（新版）
    public function deployment_new()
    {
        $deploymentList = Cache::remember('deploymentlist_new', function () {
            return $this->btPanel->GetList();
        });
        // $deploymentList = $this->btPanel->GetList();

        $this->view->assign('title', __('deployment_new'));
        return view('deployment_new', [
            'deploymentList' => $deploymentList,
        ]);
    }

    private function ProofType()
    {
        $proofType = Cache::remember('getProof', function () {
            return $this->btAction->getProof();
        });
        if (!$proofType) $this->error(__('The plug-in is not supported by the current host'), '');
        $this->btPanel->proofType = $proofType;
    }

    // 防篡改
    public function Proof()
    {
        $this->ProofType();
        $GetProof = $this->btPanel->GetProof();
        if (isset($GetProof['open']) && $GetProof['open'] == 'true') {
            foreach ($GetProof['sites'] as $key => $value) {
                if ($GetProof['sites'][$key]['siteName'] == $this->siteName) {
                    $proofInfo = $GetProof['sites'][$key];
                    break;
                } else {
                    $proofInfo = '';
                }
            }
            if (!$proofInfo) {
                $this->error(__('Unexpected situation'));
            }
        } else {
            $this->error(__('The plug-in is not supported by the current host'), '');
        }
        $this->view->assign('title', __('proof'));
        return $this->view->fetch('proof', [
            'proof_status' => $this->btPanel->proofType == 'tamper_proof' ? (isset($proofInfo['lock']) && $proofInfo['lock'] == 2 ? true : false) : (isset($proofInfo['open']) ? $proofInfo['open'] : 0),
            'proofInfo'    => $proofInfo,
        ]);
    }

    // 防篡改站点设置开关
    public function proofStatus()
    {
        $this->ProofType();
        if ($this->request->isPost()) {
            $lock = $this->request->post('lock/d', 0);
            if ($this->btPanel->proofType == 'tamper_proof') {
                $SiteProof = $this->btPanel->LockProof($this->siteName, $lock);
            } else {
                $SiteProof = $this->btPanel->SiteProof($this->siteName);
            }
            if ($SiteProof && $SiteProof['status'] == 'true') {
                $this->success($SiteProof['msg']);
            } else {
                $this->error(__('Fail') . '：' . $SiteProof['msg']);
            }
        } else {
            $this->error(__('Illegal request'));
        }
    }

    // 网站防篡改删除规则
    public function delProof()
    {
        $this->ProofType();
        $post_str = $this->request->post();
        $name = $post_str['name'];
        $type = $post_str['type'];
        if (preg_match($this->global_reg, $name) || preg_match($this->global_reg, $type)) {
            $this->error(__('Illegal parameter'));
        }
        if ($type == 'protect') {
            $SiteProof = $this->btPanel->DelprotectProof($this->siteName, $name);
        } elseif ($type == 'excloud') {
            $SiteProof = $this->btPanel->DelexcloudProof($this->siteName, $name);
        } else {
            $this->error(__('Illegal request'));
        }

        if ($SiteProof && $SiteProof['status'] == 'true') {
            $this->success($SiteProof['msg']);
        } else {
            $this->error(__('Fail') . '：' . $SiteProof['msg']);
        }
    }

    // 网站防篡改添加规则
    public function incProof()
    {
        $this->ProofType();
        $post_str = $this->request->post();
        $name = $post_str['name'];
        $type = $post_str['type'];
        if ($type == 'protect') {
            $SiteProof = $this->btPanel->AddprotectProof($this->siteName, $name);
        } elseif ($type == 'excloud') {
            $SiteProof = $this->btPanel->AddexcloudProof($this->siteName, $name);
        } else {
            $this->error(__('Illegal request'));
        }

        if ($SiteProof && $SiteProof['status'] == 'true') {
            $this->success($SiteProof['msg']);
        } else {
            $this->error(__('Fail') . '：' . $SiteProof['msg']);
        }
    }

    // 监控报表
    public function total()
    {
        $Total = $this->btPanel->GetTotal();
        $day = $this->request->get('time', date('Y-m-d', time()));
        if ($Total && isset($Total['open']) && $Total['open'] == 'true') {
            $siteTotal = $this->btPanel->SiteTotal($this->siteName, $day);
            if (!$siteTotal) $this->error(__('Unexpected situation'), '');
        } else {
            $this->error(__('The plug-in is not supported by the current host'), '');
        }
        $Network = $this->btPanel->SiteNetworkTotal($this->siteName);
        if (isset($Network['days']) && $Network['days'] != '') {
            foreach ($Network['days'] as $key => $value) {
                $Network['days'][$key]['size'] = formatBytes($Network['days'][$key]['size']);
            }
            $Network['total_size'] = formatBytes($Network['total_size']);
        }
        $Spider = $this->btPanel->SiteSpiderTotal($this->siteName);

        $Client = $this->btPanel->Siteclient($this->siteName);

        if (request()->isAjax()) {
            return ['network' => $Network, 'spider' => $Spider, 'client' => $Client];
        }

        $this->view->assign('title', __('total'));
        return $this->view->fetch('total', [
            'day'       => $day,
            'Client'    => $Client,
            'Spider'    => $Spider,
            'Network'   => $Network,
            'siteTotal' => $siteTotal,
        ]);
    }

    private function getWafType()
    {
        $isWaf = Cache::remember('getWaf', function () {
            return $this->btAction->getWaf();
        });
        if (!$isWaf) $this->error(__('The plug-in is not supported by the current host'), '');
        return $isWaf;
    }

    // 防火墙
    public function Waf()
    {
        // 获取防火墙类型
        $isWaf = $this->getWafType();
        // 获取防火墙插件
        $total = [];
        $waf = $this->btPanel->Getwaf($isWaf);
        if (isset($waf['open']) && $waf['open'] == 'true') {
            $Sitewaf = $this->btPanel->Sitewaf($isWaf, $this->siteName);
            if (!$Sitewaf) {
                $this->error(__('Unexpected situation'), '');
            }
            $SitewafConfig = $this->btPanel->SitewafConfig($isWaf);
            if ($SitewafConfig) {
                foreach ($SitewafConfig as $key => $value) {
                    if ($SitewafConfig[$key]['siteName'] == $this->siteName) {
                        $total = $SitewafConfig[$key]['total'];
                        break;
                    } else {
                        $total = [];
                    }
                }
            }
        } elseif (isset($waf['msg']) && Config('app_debug')) {
            $this->error($waf['msg'], '');
        } else {
            $this->error(__('The plug-in is not supported by the current host'), '');
        }

        // 获取四层防御状态
        $ip_stop = $isWaf != 'free_waf' ? $this->btAction->getIpstopStatus($isWaf) : false;
        $GetLog = $this->btPanel->GetwafLog($isWaf, $this->siteName, date('Y-m-d', time()));

        $this->view->assign('title', __('waf'));

        return $this->view->fetch('waf', [
            'waf_type' => $isWaf,
            'ip_stop'  => $ip_stop,
            'GetLog'   => $GetLog,
            'Sitewaf'  => $Sitewaf,
            'total'    => $total,
        ]);
    }

    // 修改waf功能开关
    public function wafStatus()
    {
        // 获取防火墙类型
        $isWaf = $this->getWafType();
        $post_str = $this->request->post();
        if ($post_str && $post_str['type']) {
            $type = $post_str['type'];
            $Status = $this->btPanel->SitewafStatus($isWaf, $this->siteName, $type);
            if ($Status && $Status['status']) {
                $this->success($Status['msg']);
            } else {
                $this->error(__('Request error, please try again later'));
            }
        } else {
            $this->error(__('Illegal request'));
        }
    }

    // 修改wafcc
    public function setWafcc()
    {
        // 获取防火墙类型
        $isWaf = $this->getWafType();
        $post_str = $this->request->post();
        if ($post_str && $post_str['type']) {
            $type = $post_str['type'];

            if ($type == 'cc') {
                $cycle = input('post.cycle/d');
                $limit = input('post.limit/d');
                $endtime = input('post.endtime/d');
                $increase = input('post.cc_mode') == 4 ? 1 : 0;
                $cc_mode = input('post.cc_mode');
                $cc_increase_type = input('post.cc_increase_type');
                $increase_wu_heng = input('post.increase_wu_heng');
                $is_open_global = 0;

                $cc_four_defense = input('post.cc_four_defense') && $cc_mode > 2 ? input('post.cc_four_defense') : 0;
                // 设置四层防御
                if ($cc_four_defense) {
                    // 开启
                    $this->btPanel->SetIPStop($isWaf);
                } else {
                    $this->btPanel->SetIPStopStop($isWaf);
                }
                $Setwafcc = $this->btPanel->Setwafcc($isWaf, $this->siteName, $cycle, $limit, $endtime, $increase, $cc_mode, $cc_increase_type, $increase_wu_heng, $is_open_global);
            } elseif ($type == 'retry') {
                $retry = input('post.retry/d');
                $retry_time = input('post.retry_time/d');
                $retry_cycle = input('post.retry_cycle/d');
                $Setwafcc = $this->btPanel->SetwafRetry($isWaf, $this->siteName, $retry, $retry_time, $retry_cycle);
            } else {
                $this->error(__('Illegal request'));
            }
            if ($Setwafcc && $Setwafcc['status'] == 'true') {
                $this->success($Setwafcc['msg']);
            } else {
                $this->error($Setwafcc['msg']);
            }
            $Status = $this->btPanel->SitewafStatus($isWaf, $this->siteName, $type);
            if ($Status && $Status['status']) {
                $this->success($Status['msg']);
            } else {
                $this->error(__('Request error, please try again later'));
            }
        } else {
            $this->error(__('Illegal request'));
        }
    }

    // 反向代理
    public function proxy()
    {
        if ($post_str = $this->request->post()) {
            if (preg_match($this->global_reg, input('post.proxyname')) || preg_match($this->global_reg, input('post.proxysite')) || preg_match($this->global_reg, input('post.todomain')) || preg_match($this->global_reg, input('post.subfiltera')) || preg_match($this->global_reg, input('post.subfilterb'))) {
                $this->error(__('Illegal parameter'));
            }
            if ($this->server_type == 'windows') {
                $cache = isset($post_str['cache']) ? '1' : '0';
                $advanced = isset($post_str['advanced']) ? '1' : '0';
                $type = isset($post_str['type']) ? '1' : '0';
                $path_open = input('post.proxydir') ? '1' : '0';
                $data = [
                    'cache_open'   => $cache,
                    'path_open'    => $path_open,
                    'proxyname'    => input('post.proxyname') ? input('post.proxyname') : time(),
                    'root_path'    => input('post.proxydir') ? input('post.proxydir') : '/',
                    'proxydomains' => json_encode(input('post.proxydomains')),
                    'tourl'        => input('post.proxysite'),
                    'to_domian'    => input('post.todomain'),
                    'sitename'     => $this->siteName,
                    'sub1'         => '',
                    'sub2'         => '',
                    'open'         => 1,
                ];
                $CreateProxy = $this->btPanel->CreateProxy_win($data);

                if ($CreateProxy && isset($CreateProxy['status'])) {
                    $this->success($CreateProxy['msg']);
                } else {
                    $this->error(__('Fail') . '：' . @$CreateProxy['msg']);
                }
            } else {
                $cachetime = input('post.cachetime/d');
                $cache = isset($post_str['cache']) ? '1' : '0';
                $advanced = isset($post_str['advanced']) ? '1' : '0';
                $type = isset($post_str['type']) ? '1' : '0';
                $subfilter = '[{"sub1":"' . $post_str['subfiltera'] . '","sub2":"' . $post_str['subfilterb'] . '"},{"sub1":"","sub2":""},{"sub1":"","sub2":""}]';

                $CreateProxy = $this->btPanel->CreateProxy($cache, $post_str['proxyname'], $cachetime, $post_str['proxydir'], $post_str['proxysite'], $post_str['todomain'], $advanced, $this->siteName, $subfilter, $type);
                if ($CreateProxy && isset($CreateProxy['status'])) {
                    $this->success($CreateProxy['msg']);
                } else {
                    $this->error(__('Fail') . '：' . @$CreateProxy['msg']);
                }
            }
        } else {
            if ($this->server_type == 'windows') {
                // 如果是iis需要判断是否安装插件
                $proxyList = $this->btAction->GetProxy($this->siteName);
                if ($proxyList === false) {
                    $this->error($this->btAction->_error, '');
                }
                // 可选域名列表
                $domainList = $this->btPanel->Websitess($this->bt_id, 'domain');
            } else {
                $proxyList = $this->btPanel->GetProxyList($this->siteName);
                $domainList = '';
            }
            if ($this->server_type == 'linux') {
                $viewTheme = 'proxy';
            } else {
                $viewTheme = 'proxy_win';
            }

            $this->view->assign('title', __('proxy'));

            return $this->view->fetch($viewTheme, [
                'domainList' => $domainList,
                'proxyList'  => $proxyList,
            ]);
        }
    }

    // 反代删除
    public function proxyDel()
    {
        $proxyname = input('post.proxyname');
        if (!$proxyname) $this->error(__('Request error, please try again later'));
        $del = $this->btPanel->RemoveProxy($this->siteName, $proxyname);
        if ($del) {
            $this->success(__('%s success', __('Delete')));
        } else {
            $this->error(__('Delete fail'));
        }
    }

    // Nginx免费防火墙
    public function free_waf()
    {
        // 判断环境是否为nginx
        if (isset($this->serverConfig['webserver']) && $this->serverConfig['webserver'] == 'nginx') {
            // 判断是否安装该插件
            $pluginInfo = $this->btAction->softQuery('free_waf');
            if (!$pluginInfo) {
                $this->error(__('The plug-in is not supported by the current host'), '');
            }
            // waf站点信息
            $waf = $this->btAction->free_waf_site_info();
            // waf站点日志
            $logs = $this->btPanel->free_waf_get_logs_list($this->siteName);
            $this->view->assign(
                'waf',
                $waf
            );
            $this->view->assign(
                'logs',
                $logs
            );
            $this->view->assign('total', $waf['total']);
            $this->view->assign('title', __('Waf'));
        } else {
            $this->error(__('The plug-in is not supported by the current host'), '');
        }
    }

    // 站点加速
    public function speed_cache()
    {
        if (!$this->btAction->get_speed_open()) {
            $this->error(__('The plug-in is not supported by the current host'), '');
        }
        $info = $this->btAction->get_speed_site($this->siteName);
        if (!$info) {
            Config('app_debug') ? $this->error(__($this->btAction->_error), '') : $this->error(__('The plug-in is not supported by the current host'), '');
        }
        // 切换缓存状态
        if ($this->request->post('status')) {
            $set = $this->btAction->set_speed_site_status($this->siteName);
            if (!$set) {
                Config('app_debug') ? $this->error(__($this->btAction->_error), '') : $this->error(__('The plug-in is not supported by the current host'), '');
            }
            $this->success(__('Success'));
        }
        // 一键切换内置缓存规则
        if ($this->request->post('ruleName')) {
            $ruleName = $this->request->post('ruleName');
            if (!$ruleName) $this->error(__('Can not be empty'));
            if (!in_array($ruleName, Cache::get('speed_rule_name_list'))) {
                $this->error(__('Rule error, please try again later'));
            }
            $set = $this->btPanel->SetSpeedRule($this->siteName, $ruleName);
            if (!$set) {
                Config('app_debug') ? $this->error(__($this->btPanel->_error), '') : $this->error(__('The plug-in is not supported by the current host'), '');
            }
            $this->success(__('Success'));
        }


        $this->view->assign('status', $info['open'] ?? 0);
        $this->view->assign('domainlist', $info['domainlist'] ?? 0);
        $this->view->assign('cache_expire', $info['expire'] ?? 0);
        $this->view->assign('request_num_day', $info['total'][2] ?? 0);
        $today_hit = $info['total'][3] <= 0 ? 0 : round($info['total'][2] / $info['total'][3] * 100, 2);
        $total_hit = $info['total'][1] <= 0 ? 0 : round($info['total'][0] / $info['total'][1] * 100, 2);
        $this->view->assign('today_hit', $today_hit);
        $this->view->assign('total_hit', $total_hit);
        $this->view->assign('total_hit', $total_hit);
        $this->view->assign('rule', $info['rule'] ?? '');
        $this->view->assign('force', $info['force']);
        $this->view->assign('white', $info['white']);

        $this->view->assign('title', __('speedCache'));
        return $this->view->fetch();
    }

    // 添加缓存规则
    public function speed_cache_add()
    {
        if ($this->request->isPost()) {
            $rule_root = $this->request->post('rule_root');
            // 规则类型
            $rule_type = $this->request->post('rule_type');
            // 规则内容
            $rule_content = $this->request->post('rule_content');
            $validate = new Validate([
                'rule_type'    => 'require|in:host,ip,args,ext,type,uri',
                'rule_content' => 'require|chsDash',
            ], [
                'rule_type'    => '规则类型错误',
                'rule_content' => '规则内容不正确',
            ]);
            if (!$validate->check($this->request->post())) $this->error($validate->getError());
            $set = $this->btPanel->AddSpeedRule($this->siteName, $rule_type, $rule_content, $rule_root);
            if (!$set) $this->error(__($this->btPanel->_error), '');
            $this->success(__('Success'));
        }
    }

    // 删除缓存规则
    public function speed_cache_del()
    {
        if ($this->request->isPost()) {
            $rule_root = $this->request->post('rule_root');
            // 规则类型
            $rule_type = $this->request->post('rule_type');
            // 规则内容
            $rule_content = $this->request->post('rule_content');
            $validate = new Validate([
                'rule_type'    => 'require|in:host,ip,args,ext,type,uri,method,cookie',
                'rule_content' => 'require',
            ], [
                'rule_type'    => '规则类型错误',
                'rule_content' => '规则内容不正确',
            ]);
            if (!$validate->check($this->request->post())) $this->error($validate->getError());
            $set = $this->btPanel->DelSpeedRule($this->siteName, $rule_type, $rule_content, $rule_root);
            if (!$set) $this->error(__($this->btPanel->_error), '');
            $this->success(__('Success'));
        }
    }

    // 获取内置加速规则
    public function speed_cache_list()
    {
        Cache::remember('speed_rule_name_list', function () {
            return ['Default', 'WordPress', 'Discuz', 'ZFAKA发卡系统', 'emlog博客系统', 'ShopXO开源商城', 'JTBC网站内容管理系统', 'PHP宝塔IDC分销系统', '米拓企业建站系统', '新起点网校', '帝国CMS', '优客365网址导航开源版', '影视全搜索网站', 'HYBBS论坛', '星辰短语|密语生成系统', '杨小杰工具箱', 'tipask问答系统', 'DM企业建站系统', 'ecshop商城系统', 'DSMall多店铺B2B2C商城', 'sentcms网站管理系统', 'FastAdmin', 'WDJA网站内容管理系统', 'DSShop单店铺B2C商城', '壹度同城新零售网站', 'SchoolCMS教务系统', 'phpcms', '迅睿CMS免费开源建站程序', '奇乐中介担保交易系统', 'jizhicms建站系统', '蝉知企业门户系统', '奇博cms', '子枫后台CMS系统', 'z-BlogPHP', 'ICMS内容管理系统', '奇乐自媒体新闻管理系统', 'DSCMS内容管理系统', 'phpok企业站系统', '云课网校系统', '网钛CMS新闻网站', 'OmoCms', '易优建站系统', '苹果cms', 'drupal', 'typecho博客系统', 'bwblog博客系统', 'PbootCMS企业建站', 'DESTOON B2B网站管理系统', 'DBShop商城系统', 'cmseasy企业建站', '赞片CMS', '织梦', 'OpenCart商城', '飞飞影视导航', 'WHMCS', 'ZKEYS'];
        });
        $list = Cache::get('speed_rule_list') ? Cache::get('speed_rule_list') : $this->btPanel->GetRuleList();
        if (!$list) {
            Config('app_debug') ? $this->error(__($this->btPanel->_error), '') : $this->error(__('The plug-in is not supported by the current host'), '');
        }
        Cache::set('speed_rule_list', $list, 0);
        return $list;
    }

    // 计划任务
    public function tasks()
    {
        return false;
        $task_model_list = Model('Task')->where(['host_id' => $this->vhost_id])->column('task_id');

        $task_all = $this->btPanel->GetCrontab();
        $task_list = [];
        if ($task_all && $task_model_list) {
            foreach ($task_all as $key => $value) {
                if (in_array($value['id'], $task_model_list)) {
                    $task_list[] = $value;
                }
            }
        }
        $this->view->assign('task_type', ['toUrl' => __('toUrl'), 'webshell' => __('webshell')]);
        $this->view->assign('title', __('Task'));
        $this->view->assign('task_list', $task_list);
        return $this->view->fetch();
    }

    // 添加计划任务
    public function task_add()
    {
        return false;
        $sType = $this->request->post('sType');
        $name = $this->request->post('name');
        if ($sType !== 'webshell' && $sType !== 'toUrl') $this->error(__('任务类型错误，请重新提交'));
        $name = $sType == 'webshell' ? '木马查杀[' . $this->btAction->bt_name . ']' : $name;
        if (!$name) $this->error(__('任务名称错误，请重新提交'));
        $data = [
            'name'     => $name,
            'sType'    => $sType,
            'sName'    => $this->btAction->bt_name,
            'backupTo' => 'localhost',
        ];
        if ($sType == 'webshell') {
            if (Model('Task')->where(['task_type' => $sType, 'host_id' => $this->vhost_id])->find()) {
                $this->error(__('任务已存在,请勿重复添加'));
            }
            $data['type'] = 'day';
            $data['hour'] = rand(2, 5);
            $data['minute'] = rand(10, 40);
            $data['urladdress'] = 'mail';
        } else {
            // TODO 网址监控任务
            $data['type'] = $this->request->post('type');
            $data['hour'] = $this->request->post('hour');
            $data['minute'] = $this->request->post('minute');//30
            $data['urladdress'] = $this->request->post('urladdress');//http://111123
        }


        $taskInc = $this->btPanel->AddCrontab($data);
        if (!$taskInc) $this->error($this->btPanel->_error);
        // 执行一次
        $this->btPanel->StartTask($taskInc['id']);
        // 写入数据库
        Model('Task')::create([
            'task_name' => $data['name'],
            'host_id'   => $this->vhost_id,
            'task_id'   => $taskInc['id'],
            'task_type' => $data['sType'],
        ]);
        $this->success(__('Success'));
    }

    // 任务删除
    public function task_del()
    {
        return false;
        $task_id = $this->request->post('id/d');
        $taskFind = Model('Task')::where(['host_id' => $this->vhost_id, 'task_id' => $task_id])->find();
        if (!$taskFind) $this->error('任务不存在');

        Model('Task')::startTrans();
        $taskFind->delete();
        // 删除任务
        $delTask = $this->btPanel->DelCrontab($taskFind->task_id);
        if (!$delTask) $this->error($this->btPanel->_error);
        Model('Task')::commit();
        $this->success(__('Success'));
    }

    // 任务日志
    public function task_log()
    {
        return false;
        $task_id = $this->request->post('id/d');
        $taskFind = Model('Task')::where(['host_id' => $this->vhost_id, 'task_id' => $task_id])->find();
        if (!$taskFind) $this->error('任务不存在');
        $taskLog = $this->btPanel->GetLogs($taskFind->task_id);
        if (!$taskLog) $this->error($this->btpanel->_error);
        $this->success(__('Success'), '', $taskLog);
    }

    // 文件路径安全检查
    private function path_safe_check($path)
    {
        $names = array("./", "%", "&", "*", "^", "!", "\\", ".user.ini");
        foreach ($names as $name) {
            if (strpos($path, $name) !== false) {
                return false;
            }
        }
        // Windows下不能包含：< > / \ | :  * ?
        // Linux下特殊字符如@、#、￥、&、()、-、空格等最好不要使用
        // 记录排除规则：@#
        $reg = $this->server_type == 'windows' ? '/^[\x7f-\xff\w\s.\/:~,@#-]+$/i' : '/^[\x7f-\xff\w\s.\/~,-]+$/i';

        if (!preg_match($reg, $path)) {
            return false;
        }

        return true;
    }

    /**
     * 检查根目录合法性
     * @Author   阿良
     * @DateTime 2019-12-03
     * @param string $path 路径
     * @param string $root 网站根目录
     * @return   bool
     */
    private function path_root_check($path, $root)
    {
        if (!$this->path_safe_check($path)) return false;

        $len = strlen($root);
        if ($root[$len - 1] === '/') {
            $root = substr($root, 0, $len - 1);
        }
        // Linux下特殊字符如@、#、￥、&、()、-、空格等最好不要使用
        // 记录排除规则：@#-
        $rep = "/^" . preg_quote($root, '/') . "\/[\x7f-\xff\w\s\.\/~,-]*$/i";
        if (!preg_match($rep, $path)) {
            return false;
        }

        return true;
    }

    // 资源检查并记录
    private function check($refresh = '')
    {
        if (Cookie('vhost_check_' . $this->vhost_id) < time() && !$refresh) {
            $list = $this->btAction->getResourceSize();
            $msg = $excess = '';
            if ($this->hostInfo->flow_max != 0 && $list['total_size'] > $this->hostInfo->flow_max) {
                $msg .= __('Flow');
                $excess = 1;
            }
            if ($this->hostInfo->site_max != 0 && $list['websize'] > $this->hostInfo->site_max) {
                $msg .= __('Host');
                $excess = 1;
            }
            if ($this->hostInfo->sql_max != 0 && $list['sqlsize'] > $this->hostInfo->sql_max) {
                $msg .= __('Sql');
                $excess = 1;
            }
            $host_data = [
                'site_size'  => $list['websize'],
                'flow_size'  => $list['total_size'],
                'sql_size'   => $list['sqlsize'],
                'check_time' => time(),
            ];

            if ($excess) {
                $host_data['status'] = 'excess';
            } elseif ($this->hostInfo->status == 'excess') {
                // 恢复主机状态
                $host_data['status'] = 'normal';
            }

            $this->hostInfo->allowField(true)->save($host_data);
            // 记录站点资源日志入库
            \app\common\model\ResourcesLog::create([
                'host_id'   => $this->hostInfo->id,
                'site_size' => $this->hostInfo->site_size,
                'flow_size' => $this->hostInfo->flow_size,
                'sql_size'  => $this->hostInfo->sql_size,
            ]);
            if ($msg) {
                $this->_error = $msg . ($excess ? __('Exceeded, resource disabled') : '');
                return false;
            }
            Cookie('vhost_check_' . $this->vhost_id, time() + $this->check_time, 3600);
        }
        return true;
    }

    /**
     * 清除剪切板cookie
     * @param $name string cookie名称
     * @return bool
     */
    private function clear_cutorcopy_cookie($name)
    {
        switch ($name) {
            case 'CutFile':
            case 'CutFile':
            case 'CutFiles':
            case 'cutFileName':
            case 'cutFileNames':
            case 'copyFileName':
            case 'copyFileNames':
            case 'all':
                Cookie::clear('vhost_cutcopy_');
                break;
        }
        return true;
    }

    // 站点空间检查并记录
    private function siteSizeCheck()
    {
        $websize = bytes2mb($this->btAction->getWebSizes($this->hostInfo->bt_name));
        // 更新使用量
        $this->hostInfo->allowField(true)->save([
            'site_size' => $websize,
        ]);
        // 记录站点资源日志入库
        \app\common\model\ResourcesLog::create([
            'host_id'   => $this->hostInfo->id,
            'site_size' => $websize,
            'flow_size' => $this->hostInfo->flow_size,
            'sql_size'  => $this->hostInfo->sql_size,
        ]);
        if ($this->hostInfo->site_max != '0' && $websize > $this->hostInfo->site_max) {
            $this->error(__('Site size exceeded, resources stopped'));
        }
    }
}

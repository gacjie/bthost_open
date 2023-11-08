<?php

namespace app\common\library;

use btpanel\Btpanel;
use think\Cache;
use fast\Random;
use think\Config;
use GuzzleHttp\Client;

class Btaction
{

    public $_error = '';        //  错误信息
    public $btPanel = null;
    // 开发模式
    //private $api_url = '192.168.191.129';
    // 线上模式
    private $api_url = '127.0.0.1';
    private $port = 8888;
    private $api_token = '';
    public $bt_id = '';         //  宝塔ID
    public $bt_name = '';       //  站点名
    public $ftp_name = '';      //  ftp名
    public $sql_name = '';      //  数据库名
    public $webRootPath = '';   //  网站根目录
    public $hostBtInfo = '';   //  宝塔主机信息
    public $siteInfo = '';      //  站点信息

    public $serverConfig = null; //  服务器配置信息
    public $dirUserIni = null;  //  网站三项配置开关

    public $userini = 1;        //  是否强制打开跨站锁
    public $iis_locking = 1;    //  是否强制锁定iis配置
    public $userini_status = false;     //  跨站锁状态

    public $os = 'linux';


    public function __construct($api_token = '', $port = '', $http = '', $os = '')
    {
        $port_config = Config('site.api_port') ? Config('site.api_port') : 8888;
        $this->port = $port ? $port : $port_config;

        $http_url = Config('site.http') ? Config('site.http') : 'http://';
        $this->http = $http ? $http : $http_url;
        $this->api_url = $this->http . $this->api_url . ':' . $this->port;

        $apiToken_config = decode(Config('site.api_token'));
        $this->api_token = $api_token ? $api_token : $apiToken_config;
        $this->btPanel = new Btpanel($this->api_url, $this->api_token);
        // TODO 正式环境下切换到自动获取服务器操作系统类型
        $this->os = 'linux';
        // $this->os = $os ? $os : getOs();

    }

    // 测试入口
    public function test()
    {
        $server_config = $this->clear_config();
        if (!$server_config) {
            return false;
        }
        $server_config = Cache::remember('vhost_config', function () {
            return $server_config = $this->clear_config();
        }, 3600 * 24);
        return $this->tests();
    }

    /**
     * 获取通信时长
     * @Author   Youngxj
     * @DateTime 2019-12-14
     * @return   [type]     [description]
     */
    public function getRequestTime()
    {
        try {
            $time = $this->getRequestTimes($this->api_url, 10);
        } catch (\Exception $e) {
            return false;
        }

        if (!$time) return false;
        return $time . 'ms';
    }

    // linux版测试连接
    public function tests()
    {
        // $config = $this->getServerConfig();
        $config = Cache::get('vhost_config');
        if ($config && isset($config['status']) && $config['status']) {
            $this->serverConfig = $config;
            return true;
        } else if (isset($config['msg'])) {
            $msg = $config['msg'];
        }
        $msg = $msg ?? '服务器连接测试失败';
        $this->setError($msg . $this->getRequestTime());
        return false;
    }

    // 获取网站全部分类
    public function getsitetype()
    {
        $list = $this->btPanel->Webtypes();
        if (!$list) return false;
        return $list;
    }

    // 站点初始化
    public function webInit()
    {
        // 连接测试
        if (!$this->test()) {
            $this->setError($this->_error);
            return false;
        }
        // 查找站点是否存在
        $siteInfo = $this->getSiteInfo();
        if (!$siteInfo) {
            $this->setError($this->_error);
            return false;
        }
        if (($siteInfo['name'] != $this->bt_name) || ($siteInfo['id'] != $this->bt_id)) {
            $this->setError('该站点信息有误，请联系管理员');
            return false;
        }
        // 验证网站目录信息
        $this->webRootPath = $siteInfo['path'];
        if (empty($this->webRootPath) || !$this->webRootPath) {
            $this->setError('该站点根目录有误，请联系管理员');
            return false;
        }
        $this->hostBtInfo = $siteInfo;

        // 检查运行目录及跨站锁
        if (!$this->examineDir()) {
            $this->setError($this->_error);
            return false;
        }

        return true;
    }

    // 检查运行目录及跨站锁
    public function examineDir()
    {
        $this->dirUserIni = $this->btPanel->GetDirUserINI($this->bt_id, $this->webRootPath);
        // 检查运行目录
        if (isset($this->dirUserIni['runPath']['dirs'])) {
            $path = $this->dirUserIni['runPath']['runPath'];
            // 修正网站停止时目录变更
            if (!in_array($path, $this->dirUserIni['runPath']['dirs']) && $this->dirUserIni['runPath']['runPath'] != '/www/server/stop/') {
                // 还原运行目录为/
                $this->btPanel->SetSiteRunPath($this->bt_id, '/');
            }
        } else {
            $this->setError('网站目录错误，请联系管理员');
            return false;
        }
        // 网站停止后暂停跨站锁检查及挂锁，提升处理效率
        if ($this->dirUserIni['runPath']['runPath'] != '/www/server/stop/') {
            // 检查防跨站锁
            if (isset($this->dirUserIni['userini']) && $this->dirUserIni['userini'] != true) {
                $this->userini_status = false;
                if ($this->userini) {
                    // 当防跨站锁丢失时，强制打开跨站锁
                    $setUserIni = $this->btPanel->SetDirUserINI($this->webRootPath);
                    if (!$setUserIni || $setUserIni['status'] != true) {
                        $this->setError('当前站点安全锁打开失败');
                        return false;
                    }
                }
            } else {
                $this->userini_status = true;
            }
        }
        // windows专属iis锁
        if ($this->os == 'windows') {
            if ($this->dirUserIni['locking'] != 'true') {
                if ($this->iis_locking) {
                    // 锁定IIS配置文件(windows)
                    $this->btPanel->SetConfigLocking($this->bt_name);
                }
            }
        }
        return true;
    }

    // 获取资源大小：流量、数据库、站点
    public function getResourceSize()
    {
        set_time_limit(60);
        // 流量
        $Total = $this->btPanel->GetTotal();
        if ($Total && isset($Total['open']) && $Total['open'] == 'true') {
            $total_size = $this->getNetNumber_month($this->bt_name);
            if (!$total_size || !isset($total_size['month_total'])) {
                $total_size['month_total'] = 0;
            }
        } else {
            $total_size['month_total'] = 0;
        }
        // 实际使用流量
        $total_size = is_numeric($total_size['month_total']) ? bytes2mb($total_size['month_total']) : 0;
        // 空间大小
        $websize = bytes2mb($this->getWebSizes($this->bt_name));
        // 数据库
        $sqlsize = $this->sql_name ? bytes2mb($this->getSqlSizes($this->sql_name)) : 0;
        return compact('sqlsize', 'websize', 'total_size');
    }

    /**
     * 修改数据库密码
     *
     * @param [type] $name          数据库名
     * @param [type] $newpassword   数据库新密码
     * @return void
     */
    public function resetSqlPass($name, $newpassword)
    {
        $id = $this->getSqlInfo('id');
        if (!$id) {
            $this->setError('id获取有误');
            return false;
        }
        $reset = $this->btPanel->ResDatabasePass($id, $name, $newpassword);
        if (isset($reset['status']) && $reset['status'] == true) {
            return true;
        } elseif (isset($reset['status']) && isset($reset['msg'])) {
            $msg = $this->setError($reset['msg']);
        }
        $msg = $msg ?? 'error';
        $this->setError($msg);
        return false;
    }

    /**
     * 修改FTP密码
     *
     * @param [type] $name
     * @param [type] $newpassword
     * @return void
     */
    public function resetFtpPass($name, $newpassword)
    {
        $id = $this->getFtpInfo('id');
        if (!$id) {
            $this->setError('id获取有误');
            return false;
        }
        $reset = $this->btPanel->SetUserPassword($id, $name, $newpassword);
        if (isset($reset['status']) && $reset['status'] == true) {
            return true;
        } elseif (isset($reset['status']) && isset($reset['msg'])) {
            $msg = $this->setError($reset['msg']);
        }
        $msg = $msg ?? 'error';
        $this->setError($msg);
        return false;
    }

    /**
     * FTP删除
     *
     * @return void
     */
    public function FtpDelete()
    {
        $id = $this->getFtpInfo('id');
        if (!$id) {
            $this->setError('id获取有误');
            return false;
        }
        $del = $this->btPanel->DeleteUser($id, $this->ftp_name);
        if (isset($del['status']) && $del['status'] == true) {
            return true;
        } elseif (isset($del['msg'])) {
            $msg = $this->setError($del['msg']);
        }
        $msg = $msg ?? 'error';
        $this->setError($msg);
        return false;
    }

    /**
     * FTP状态变更
     *
     * @param integer $status 0=停用;1=启用
     * @return void
     */
    public function FtpStatus($status = 0)
    {
        $id = $this->getFtpInfo('id');
        if (!$id) {
            $this->setError('id获取有误');
            return false;
        }
        $s = $this->btPanel->SetStatus($id, $this->ftp_name, $status);
        if (isset($s['status']) && $s['status'] == true) {
            return true;
        } elseif (isset($s['msg'])) {
            $msg = $this->setError($s['msg']);
        }
        $msg = $msg ?? 'error';
        $this->setError($msg);
        return false;
    }

    /**
     * 新建网站
     *
     * @param [type] $hostSetInfo
     * @return void
     */
    public function btBuild($hostSetInfo)
    {
        //使用宝塔创建网站
        $btInfo = $this->btPanel->AddSite($hostSetInfo);

        if (isset($btInfo['status']) && $btInfo['status'] != true) {
            $this->setError('主机创建失败->' . @$btInfo['msg'] . '|' . json_encode($hostSetInfo));
            return false;
        }
        if (isset($btInfo['siteStatus']) && @$btInfo['siteStatus'] != true) {
            $this->setError('主机创建失败->' . @$btInfo['msg'] . '|' . json_encode($hostSetInfo));
            return false;
        }

        if (!isset($btInfo['siteId']) || empty($btInfo['siteId'])) {
            $this->setError('网站创建失败|' . json_encode(['btinfo' => $btInfo]));
            return false;
        }
        return $btInfo;
    }

    // 获取默认建站目录
    public function getSitePath()
    {
        $path = isset($this->serverConfig['sites_path']) && $this->serverConfig['sites_path'] ? $this->serverConfig['sites_path'] : $this->getServerConfig('sites_path');
        return $path ? $path : '/www/wwwroot';
    }

    // 获取默认备份目录
    public function getBackupPath()
    {
        $path = isset($this->serverConfig['backup_path']) && $this->serverConfig['backup_path'] ? $this->serverConfig['backup_path'] : $this->getServerConfig('backup_path');
        return $path ? $path : '/www/backup';
    }

    // 获取运行服务类型nginx、apache
    public function getWebServer()
    {
        $type = isset($this->serverConfig['webserver']) && $this->serverConfig['webserver'] ? $this->serverConfig['webserver'] : $this->getServerConfig('webserver');
        return $type;
    }

    // 构建创建站点需要的参数
    public function setInfo($params = '', $plans = '')
    {
        // 获取网站建站目录
        // 如果资源组中设定了，那么读取资源组中的，如果没有就默认读取配置接口中的
        if (isset($plans['sites_path']) && $plans['sites_path']) {
            $defaultPath = $plans['sites_path'] . '/';
        } else {
            $defaultPath = $this->getSitePath() . '/';
        }

        // 站点随机域名
        // $userRandId = ;
        // 站点域名
        if (isset($params['username']) && $params['username']) {
            // 自定义
            $set_domain = strtolower($params['username']);
        } else {
            // 随机
            $set_domain = strtolower(Random::alnum(6));
        }
        // 测试语句，正式环境注释
        // $set_domain = $userRandId;
        // 拼接默认域名 6.8.18+版官方强转小写 2019-03-09
        $defaultDomain = isset($plans['domains']) ? $plans['domains'] : strtolower($set_domain . '.' . $plans['domain']);
        // php版本
        $phpversion = isset($plans['phpver']) && is_numeric($plans['phpver']) ? $plans['phpver'] : '00';
        // mysql
        $sqlType = isset($plans['sql']) ? $plans['sql'] : 'none';

        $site_max = isset($plans['site_max']) && $plans['site_max'] ? $plans['site_max'] : '无限制';
        $sql_max = isset($plans['sql_max']) && $plans['sql_max'] ? $plans['sql_max'] : '无限制';
        $flow_max = isset($plans['flow_max']) && $plans['flow_max'] ? $plans['flow_max'] : '无限制';
        $ps = isset($plans['ps']) && $plans['ps'] ? $plans['ps'] : 'Site:' . $site_max . ' Sql:' . $sql_max . ' Flow:' . $flow_max;

        $rand_password = Random::alnum(12);

        // 构建数据
        return array(
            'webname'      => '{"domain":"' . $defaultDomain . '","domainlist":[],"count":0}',
            'path'         => isset($params['WebGetKey']) && $params['WebGetKey'] ? $params['WebGetKey'] : $defaultPath . $set_domain,
            'type_id'      => isset($params['sort_id']) ? $params['sort_id'] : '0',
            'type'         => 'PHP',
            'version'      => $phpversion ? $phpversion : '00',
            'port'         => isset($plans['port']) ? $plans['port'] : '80',
            'ps'           => $ps,
            'ftp'          => isset($plans['ftp']) && $plans['ftp'] ? 'true' : 'false',
            'ftp_username' => $set_domain,
            'ftp_password' => $rand_password,
            // 'sql'          => $plans['sql'] ? 'true' : 'false',
            'sql'          => $sqlType != 'none' ? $sqlType : 'false', // 新版传递的sql不是true/false而是具体的软件程序
            'codeing'      => 'utf8',
            'datauser'     => $set_domain,
            'datapassword' => $rand_password,
            'check_dir'    => 1, //该参数是win独有
            // 以下非宝塔使用，个人记录
            'bt_name'      => $defaultDomain,
            'domain'       => $set_domain,
            'username'     => $set_domain,
            'password'     => $rand_password,
        );
    }

    public function presetProcedure($dname, $btName, $defaultPhp)
    {
        $setUp = $this->btPanel->SetupPackageNew($dname, $btName, $defaultPhp);
        if ($setUp && isset($setUp['status']) && $setUp['status'] != 'true') {
            $setMsg = isset($setUp['msg']) ? $setUp['msg'] : '';
            // 有错误，记录，防止开通被打断
            $this->setError($setMsg);
            return false;
            // return false;
        } elseif (isset($setUp['msg']['admin_username']) && $setUp['msg']['admin_username'] != '') {
            // $setUp['msg']['admin_username']
            // $setUp['msg']['admin_password']
            // $defaultDomain.$setUp['msg']['success_url']
            // 获取安装程序后获得的默认账号密码
        }
        return true;
    }

    /**
     * 设置并发、网络限制
     *
     * @param [type] $btid  宝塔ID
     * @param [type] $data  并发限制参数
     * @param string $os 环境linux/windows
     * @return void
     */
    public function setLimit($data)
    {
        $perip = isset($data['perip']) ? $data['perip'] : 25;
        $timeout = isset($data['timeout']) ? $data['timeout'] : 120;
        if ($this->os == 'linux') {
            $modify_status = $this->btPanel->SetLimitNet($this->bt_id, $data['perserver'], $perip, $data['limit_rate']);
            if (isset($modify_status) && $modify_status['status'] != 'true') {
                // 有错误，记录，防止开通被打断
                $this->setError($modify_status['msg'] . '|' . json_encode(['info' => [$this->bt_id, $data['perserver'], $perip, $data['limit_rate']]]));
                return false;
            }
        } else {
            $modify_status = $this->btPanel->SetLimitNet_win($this->bt_id, $data['perserver'], $timeout, $data['limit_rate']);
            if (isset($modify_status) && $modify_status['status'] != 'true') {
                // 有错误，记录，防止开通被打断
                $this->setError($modify_status['msg'] . '|' . json_encode(['info' => [$this->bt_id, $data['perserver'], $timeout, $data['limit_rate']]]));
                return false;
            }
        }

        return true;
    }

    /**
     * 关闭限速
     *
     * @return void
     */
    public function closeLimit()
    {
        $set = $this->btPanel->CloseLimitNet($this->bt_id);
        if (isset($set['status']) && $set['status'] == true) {
            return true;
        } elseif (isset($set['msg'])) {
            $this->setError($set['msg']);
            return false;
        } else {
            $this->setError('请求失败');
            return false;
        }
    }

    /**
     * 检查并打开跨站锁
     *
     * @param [type] $btid      宝塔ID
     * @param [type] $rootpath  网站根目录
     * @return void
     */
    public function set_open_basedir($btid, $rootpath)
    {
        // 获取网站目录信息
        $dirUserIni = $this->btPanel->GetDirUserINI($btid, $rootpath);
        if (isset($dirUserIni['userini']) && $dirUserIni['userini'] != true) {
            // 当防跨站锁丢失时，强制打开跨站锁
            $setUserIni = $this->btPanel->SetDirUserINI($rootpath);
            if (!$setUserIni || $setUserIni['status'] != true) {
                return false;
            }
        }
    }

    /**
     * 获取服务器配置信息
     * @Author   Youngxj
     * @DateTime 2019-12-05
     * @return   [type]     [description]
     */
    public function getServerConfig($value = '')
    {
        $config = $this->btPanel->GetConfig();
        if ($config) {
            if ($value) {
                return isset($config[$value]) ? $config[$value] : false;
            } else {
                return $config;
            }
        } else {
            return false;
        }
    }

    /**
     * 获取面板信息
     *
     * @param string $value
     * @return void
     */
    public function getPanelConfig($value = '')
    {
        $config = $this->btPanel->GetSystemTotal();
        if ($config) {
            if ($value) {
                return isset($config[$value]) ? $config[$value] : false;
            } else {
                return $config;
            }
        } else {
            return false;
        }
    }

    /**
     * 获取服务器公网IP
     *
     * @return void
     */
    public function getIp()
    {
        if ($this->os != 'windows') {
            $soft = $this->btPanel->GetSoftList();
            if ($soft && isset($soft['ip']) && $soft['ip']) {
                return $soft['ip'];
            }
        }
        // 使用API获取公网IP，备用方案
        $url = config('bty.api_url') . '/bthost_get_ip.html';
        $domain = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : 'null';
        $data = [
            'obj'     => Config::get('bty.APP_NAME'),
            'version' => Config::get('bty.version'),
            'domain'  => $domain,
            'rsa'     => 1,
        ];
        $post = \fast\Http::post($url, $data);
        $post = json_decode($post, 1);
        if ($post && isset($post['data']['ip'])) {
            return $post['data']['ip'];
        } elseif (isset($post['msg'])) {
            $msg = $post['msg'];
        }
        $msg = $msg ?? '请求失败';
        $this->_error = $msg;
        return false;
    }

    /**
     * 获取主机信息
     * @Author   Youngxj
     * @DateTime 2019-12-14
     * @return   [type]               [description]
     */
    public function getSiteInfo()
    {
        $siteInfo = $this->btPanel->Websites($this->bt_name);
        if (isset($siteInfo['status']) && $siteInfo['status'] === false) {

            $this->setError($siteInfo['msg']);
            return false;
        } elseif (!$siteInfo) {
            $this->setError('服务器连接失败');
            return false;
        } elseif (isset($siteInfo['data']) && !empty($siteInfo['data'])) {
            $siteArr = '';
            if ($this->bt_name && $this->bt_id) {
                // 如果站点名和站点ID都存在那么双项认证
                foreach ($siteInfo['data'] as $value) {
                    if ($value['name'] == $this->bt_name && $value['id'] == $this->bt_id) {
                        $siteArr = $value;
                        continue;
                    }
                }
            } elseif ($this->bt_name || $this->bt_id) {
                // 如果站点名或站点ID存在一个那么单项验证
                foreach ($siteInfo['data'] as $value) {
                    if ($value['name'] == $this->bt_name || $value['id'] == $this->bt_id) {
                        $siteArr = $value;
                        continue;
                    }
                }
            } else {
                // 如果都没有就选择查到的第一条
                // 可能存在一些安全问题，一般查找都至少传递一个参数进行验证
                // 此方法留作极端环境使用
                $siteArr = $siteInfo['data'][0];
            }
            if (!$siteArr) {
                $this->setError(__('Failed to get site information'));
                return false;
            }
            return $siteArr;
        } else {
            $this->setError(__('Failed to get site information'));
            return false;
        }
    }

    /**
     * 获取站点php版本
     * @Author   Youngxj
     * @DateTime 2019-11-30
     * @param    [type]     $siteName 站点名
     * @return   [type]               [description]
     */
    public function getSitePhpVer($siteName)
    {
        $phpver = $this->btPanel->GetSitePHPVersion($siteName);
        if (!$phpver) {
            return false;
        }
        if (isset($phpver['status']) && $phpver['status'] == 'false') {
            return $phpver['msg'];
        }
        return isset($phpver['phpversion']) ? $phpver['phpversion'] : '00';
    }

    /**
     * 获取站点状态
     * @Author   Youngxj
     * @DateTime 2019-11-30
     * @return   [type]               [description]
     */
    public function getSiteStatus()
    {
        $webInfo = $this->getSiteConfig($this->bt_name);
        if ($webInfo && $webInfo['data']['0']['status'] == 1) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * 获取全部站点列表
     *
     * @param integer $maxNum
     * @return void
     */
    public function getSiteList($search = '', $p = 1, $maxNum = 999)
    {
        $list = $this->btPanel->Websites($search, $p, $maxNum);
        if (isset($list['data']) && !empty($list['data'])) {
            return $list;
        } else {
            return false;
        }
    }

    /**
     * 获取全部数据库列表
     *
     * @param integer $maxNum
     * @return void
     */
    public function getSqlList($search = '', $p = 1, $maxNum = 999)
    {
        $list = $this->btPanel->WebSqlList($search, $p, $maxNum);
        if (isset($list['data']) && !empty($list['data'])) {
            return $list;
        } else {
            return false;
        }
    }

    // 获取全部ftp列表
    public function getFtpList($search = '', $p = 1, $maxNum = 999)
    {
        $list = $this->btPanel->WebFtpList($search, $p, $maxNum);
        if (isset($list['data']) && !empty($list['data'])) {
            return $list;
        } else {
            return false;
        }
    }

    // 站点总数
    public function siteCount($search = '')
    {
        $list = $this->getSiteList($search);
        $s = isset($list['data']) ? $list['data'] : [];
        return count($s);
    }

    // ftp总数
    public function ftpCount($search = '')
    {
        $list = $this->getFtpList($search);
        $s = isset($list['data']) ? $list['data'] : [];
        return count($s);
    }

    // sql总数
    public function sqlCount($search = '')
    {
        $list = $this->getSqlList($search);
        $s = isset($list['data']) ? $list['data'] : [];
        return count($s);
    }

    /**
     * FTP删除
     *
     * @return void
     */
    public function SqlDelete()
    {
        $id = $this->getSqlInfo('id');
        if (!$id) {
            $this->setError('id获取有误');
            return false;
        }
        $del = $this->btPanel->DeleteDatabase($id, $this->sql_name);
        if (isset($del['status']) && $del['status'] == true) {
            return true;
        } elseif (isset($del['msg'])) {
            $this->setError($del['msg']);
            return false;
        } else {
            $this->setError('请求失败');
            return false;
        }
    }

    /**
     * 获取站点信息
     * @Author   Youngxj
     * @DateTime 2019-04-18
     * @param    [type]     $siteName 站点名
     * @return   [type]             [description]
     */
    public function getSiteConfig($siteName)
    {
        $webInfo = $this->btPanel->Websites($siteName);
        if (isset($webInfo['data']) && !empty($webInfo['data'])) {
            return $webInfo;
        } else {
            return false;
        }
    }

    /**
     * 获取域名绑定列表
     * @Author   Youngxj
     * @DateTime 2019-12-03
     * @return   [type]           [description]
     */
    public function getSiteDomain()
    {
        $domainList = $this->btPanel->WebDoaminList($this->bt_id);
        if ($domainList) {
            return $domainList;
        } else {
            return false;
        }
    }

    /**
     * 获取ftp信息
     * @Author   Youngxj
     * @DateTime 2019-12-05
     * @return   [type]               [description]
     */
    public function getFtpInfo($k = '')
    {
        if (!$this->ftp_name) {
            return false;
        }
        $ftp = $this->btPanel->WebFtpList($this->ftp_name);
        if ($ftp && isset($ftp['data'])) {
            // 遍历效验
            foreach ($ftp['data'] as $key => $value) {
                if ($value['name'] == $this->ftp_name) {
                    return $k ? $value[$k] : $value;
                }
            }
        }
        return false;
    }

    /**
     * 获取sql信息
     * @Author   Youngxj
     * @DateTime 2019-12-05
     * @return   [type]               [description]
     */
    public function getSqlInfo($k = '')
    {
        if (!$this->sql_name) {
            return false;
        }
        $sql = $this->btPanel->WebSqlList($this->sql_name);
        if ($sql && isset($sql['data'])) {
            // 遍历效验
            foreach ($sql['data'] as $key => $value) {
                if ($value['name'] == $this->sql_name) {
                    return $k ? $value[$k] : $value;
                }
            }
        } else {
            return false;
        }
    }

    /**
     * 获取子目录绑定信息
     * @Author   Youngxj
     * @DateTime 2019-12-03
     * @return   [type]           [description]
     */
    public function getSiteDirBinding()
    {
        $domainList = $this->btPanel->GetDirBinding($this->bt_id);
        if ($domainList) {
            return $domainList;
        } else {
            return false;
        }
    }

    /**
     * 绑定域名
     * @Author   Youngxj
     * @DateTime 2019-12-03
     * @param    [type]     $domain_str 域名数组
     * @param    [type]     $btId       宝塔ID
     * @param    [type]     $siteName   站点名（为目录是填写目录名）
     * @param integer $is_dir 是否为目录
     */
    public function addDomain($domain_str, $siteName, $is_dir = 0)
    {
        // 绑定网站
        if ($is_dir) {
            $add = $this->btPanel->AddDirBinding($this->bt_id, $domain_str, $siteName);
        } else {
            // 绑定目录
            $add = $this->btPanel->WebAddDomain($this->bt_id, $siteName, $domain_str);
        }
        if (isset($add['status']) && $add['status'] == true) {
            return $add;
        } elseif (isset($add['msg'])) {
            $this->setError($add['msg']);
            return false;
        } else {
            $this->setError('请求失败');
            return false;
        }
    }

    /**
     * 删除域名绑定
     * @Author   Youngxj
     * @DateTime 2019-12-03
     * @param    [type]     $btId    宝塔ID
     * @param    [type]     $webname 网站名
     * @param    [type]     $domain  删除的域名
     * @param    [type]     $port    端口
     * @return   [type]              [description]
     */
    public function delDomain($btId, $webname, $domain, $port = '80')
    {
        $del = $this->btPanel->WebDelDomain($btId, $webname, $domain, $port);
        if (isset($del['status']) && $del['status'] == 'true') {
            return true;
        } elseif (isset($del['msg'])) {
            $this->setError($del['msg']);
            return false;
        } else {
            $this->setError('请求失败');
            return false;
        }
    }

    /**
     * 删除绑定目录的域名
     * @Author   Youngxj
     * @DateTime 2019-12-03
     * @param    [type]     $id 列表ID
     * @return   [type]         [description]
     */
    public function delDomainDir($id)
    {
        $del = $this->btPanel->DelDirBinding($id);
        if ($del) {
            return $del;
        } else {
            return false;
        }
    }

    /**
     * 删除指定站点
     *
     * @param [type] $id        站点ID
     * @param [type] $webname   站点名称
     * @param integer $ftp      删除ftp
     * @param integer $database 删除数据库
     * @param integer $path     删除文件
     * @return void
     */
    public function siteDelete($id, $webname, $ftp = 1, $database = 1, $path = 1)
    {
        $del = $this->btPanel->WebDeleteSite($id, $webname, $ftp, $database, $path);
        if ($del && isset($del['status']) && $del['status'] == 'true') {
            return true;
        } elseif (isset($del['status'])) {
            $this->setError($del['msg']);
            return false;
        } else {
            return false;
        }
    }

    /**
     * 获取安装的php列表
     *
     * @param integer $is_all 是否显示全部php
     * @return array
     */
    public function getphplist($is_all = 0)
    {
        $list = $this->btPanel->GetSoftList('php是');
        if ($list && isset($list['list']['data']) && $list['list']['data']) {
            $arr = [];
            $arr[0]['id'] = '00';
            $arr[0]['name'] = '纯静态';
            // 将数据处理成合适的数组
            $i = 1;
            foreach ($list['list']['data'] as $key => $value) {
                // 判断是否安装
                if ($value['setup'] || $is_all) {
                    $number = preg_replace('/[^\d]*/', '', $value['name']);
                    if ($number) {
                        $arr[$i]['id'] = $number;
                        $arr[$i]['name'] = $number;
                        $i++;
                    } else {
                        continue;
                    }
                } else {
                    continue;
                }
            }
            return $arr;
        } else {
            return [];
        }
    }

    // 获取一键部署列表
    public function getdeploymentlist($search = '')
    {
        $list = $this->btPanel->GetList($search);
        if ($list && isset($list['list']) && $list['list']) {
            $arr = [];
            foreach ($list['list'] as $key => $value) {
                $arr[$key]['id'] = $value['name'];
                $arr[$key]['name'] = $value['title'] . $value['version'];
            }
            return $arr;
        } else {
            return [];
        }

        // return Cache::remember('deploymentlist',function(){
        // 缓存器
        // });
    }

    /**
     * 获取防火墙类型
     * @Author   Youngxj
     * @DateTime 2019-12-05
     * @return   [type]     [description]
     */
    public function getWaf()
    {
        $list = ['btwaf_httpd', 'btwaf', 'waf_nginx', 'waf_iis', 'waf_apache', 'free_waf'];
        $isWaf = '';
        foreach ($list as $key => $value) {
            if ($this->softQuery($value) !== false) {
                $isWaf = $value;
                break;
            }
        }
        if ($isWaf != '') {
            return $isWaf;
        } else {
            return false;
        }
    }

    /**
     * 获取防篡改类型
     * @Author   Youngxj
     * @DateTime 2019-12-05
     * @return   [type]     [description]
     */
    public function getProof()
    {
        $list = ['tamper_drive', 'tamper_proof'];
        $isProof = '';
        foreach ($list as $key => $value) {
            if ($this->softQuery($value) !== false) {
                $isProof = $value;
                break;
            }
        }
        if ($isProof != '') {
            return $isProof;
        } else {
            return false;
        }
    }

    /**
     * 查询是否安装某个插件
     *
     * @param [type] $name
     * @return void
     */
    public function softQuery($name)
    {
        $GetSoftList = $this->btPanel->GetSoftList($name);
        if ($GetSoftList) {
            foreach ($GetSoftList['list']['data'] as $key => $value) {
                if ($GetSoftList['list']['data'][$key]['name'] == $name && $GetSoftList['list']['data'][$key]['setup'] == 'true') {
                    return $GetSoftList['list']['data'][$key];
                    break;
                }
            }
        }
        return false;
    }

    /**
     * 获取反代信息
     * @Author   Youngxj
     * @DateTime 2019-12-12
     * @param    [type]     $sitename 站点名
     */
    public function GetProxy($sitename)
    {
        $fx = $this->btPanel->GetProxyList($sitename);
        if ($fx && isset($fx['status']) && $fx['status'] == false) {
            $this->setError($fx['msg']);
            return false;
        } elseif ($fx || is_array($fx)) {
            return $fx;
        } else {
            return false;
        }
    }

    /**
     * 获取统计报表中站点流量信息
     * @Author   Youngxj
     * @DateTime 2019-05-06
     * @param    [type]     $domain [description]
     * @return   [type]             [description]
     */
    public function getNetNumber($domain)
    {
        $SiteNetworkTotal = $this->btPanel->SiteNetworkTotal($domain);
        if ($SiteNetworkTotal) {
            return $SiteNetworkTotal;
        } else {
            return false;
        }
    }

    /**
     * 获取统计报表中站点流量信息（月）
     *
     * @param [type] $domain    站点名
     * @return void
     */
    public function getNetNumber_month($domain)
    {
        $size = 0;
        $SiteNetworkTotal = $this->btPanel->SiteNetworkTotal($domain);
        if (isset($SiteNetworkTotal['status']) && $SiteNetworkTotal['status'] == false) {
            return $SiteNetworkTotal;
        }
        if ($SiteNetworkTotal && isset($SiteNetworkTotal['total_size']) && $SiteNetworkTotal['total_size'] != 0) {
            // 月初时间戳
            $beginThismonth = mktime(0, 0, 0, date('m'), 1, date('Y'));
            // 月末时间戳
            $endThismonth = mktime(23, 59, 59, date('m'), date('t'), date('Y'));
            //var_dump($beginThismonth);var_dump($endThismonth);exit();
            $SiteNetworkTotal['month_total'] = 0;
            $SiteNetworkTotal = array_diff_key($SiteNetworkTotal, ['total_request' => "xy", "total_size" => "xy"]);
            // echo json_encode($SiteNetworkTotal);exit();
            foreach ($SiteNetworkTotal['days'] as $key => $value) {
                $new = strtotime($SiteNetworkTotal['days'][$key]['date']);
                if ($beginThismonth < $new && $new < $endThismonth) {
                    $size += $SiteNetworkTotal['days'][$key]['size'];
                }
            }
            //var_dump($size);
            $SiteNetworkTotal['month_total'] = $size;
            return $SiteNetworkTotal;
        } else {
            return false;
        }
    }

    /**
     * 获取站点大小
     * @param  [type] $domain 站点名
     * @return [type]         空间大小(字节)
     */
    public function getWebSizes($domain)
    {
        $search = $this->btPanel->Websites($domain);
        if (isset($search['data']['0']['path'])) {
            $pathSize = $this->btPanel->GetWebSize($search['data']['0']['path']);
            if ($pathSize) {
                return toBytes($pathSize['size']);
            } else {
                return '0';
            }
        } else {
            return '0';
        }
    }

    /**
     * 获取服务器中的数据库大小
     * @param  [type] $sqlname 数据库账号
     * @return [type]          [description]
     */
    public function getSqlSizes($sqlname)
    {
        $sqlSize = $this->btPanel->GetSqlSize($sqlname);
        if ($sqlSize && isset($sqlSize['data_size']) && $sqlSize['data_size'] != "") {
            $size = strtolower($sqlSize['data_size']);
            return $size ? toBytes($size) : '0';
        } else {
            return '0';
        }
    }

    /**
     * 站点稽核检查
     *
     * @param [type] $flow      是否检查流量（月）
     * @param [type] $sqlname   数据库名
     * @return void
     */
    public function resource($sqlname = null, $flow = true)
    {
        $data = [];
        $data['site'] = $this->getWebSizes($this->bt_name);

        if ($flow) {
            $f = $this->getNetNumber_month($this->bt_name);
            $data['flow'] = isset($f['month_total']) ? $f['month_total'] : false;
        } else {
            $data['flow'] = false;
        }

        $data['sql'] = $sqlname ? $this->getSqlSizes($sqlname) : false;

        return $data;
    }

    /**
     * 站点停止
     *
     * @param [type] $bt_id     站点ID
     * @param [type] $domain    站点名
     * @return void
     */
    public function webstop()
    {
        // 先判断站点状态，防止多次重复操作
        $siteStatus = $this->getSiteStatus($this->bt_name);
        if (!$siteStatus) {
            return true;
        }
        $stop = $this->btPanel->WebSiteStop($this->bt_id, $this->bt_name);
        if ($stop && isset($stop['status']) && $stop['status'] == true) {
            return true;
        } elseif ($stop && isset($stop['msg'])) {
            return $stop['msg'];
        } else {
            return false;
        }
    }

    /**
     * 站点开启
     *
     * @param [type] $bt_id     站点ID
     * @param [type] $domain    站点名
     * @return void
     */
    public function webstart()
    {
        $siteStatus = $this->getSiteStatus($this->bt_name);
        if ($siteStatus) {
            return true;
        }
        $start = $this->btPanel->WebSiteStart($this->bt_id, $this->bt_name);
        if ($start && isset($start['status']) && $start['status'] == true) {
            return true;
        } elseif ($start && isset($start['msg'])) {
            return $start['msg'];
        } else {
            return false;
        }
    }

    // 宝塔面板日志列表
    public function panelLogs()
    {
        $los = $this->btPanel->getPanelLogs();
        if (!$los) {
            return false;
        }
        return isset($los['data']) ? $los['data'] : false;
    }

    // 获取四层防御状态
    public function getIpstopStatus($wafType)
    {
        $get = $this->btPanel->GetIPStop($wafType);
        if (isset($get['status']) && $get['status']) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * 站点设置php版本
     *
     * @param [type] $ver       版本号
     * @return void
     */
    public function setPhpVer($ver)
    {
        $set = $this->btPanel->SetPHPVersion($this->bt_name, $ver);
        if ($set && isset($set['status']) && $set['status'] == 'true') {
            return true;
        } elseif ($set && isset($set['msg'])) {
            $this->setError($set['msg']);
            return false;
        } else {
            $this->setError('请求失败');
            return false;
        }
    }

    // Nginx免费防火墙（暂无用）
    public function free_waf_site_info()
    {
        $list = $this->btPanel->free_waf_site_config();
        if ($list) {
            foreach ($list as $key => $value) {
                if ($list[$key]['siteName'] == $this->bt_name) {
                    return $list[$key];
                }
            }
        }
        return false;
    }

    /**
     * 设置到期时间
     *
     * @param [type] $endtime   到期时间 2020-8-27
     * @return void
     */
    public function setEndtime($endtime)
    {
        $set = $this->btPanel->WebSetEdate($this->bt_id, $endtime);
        if (isset($set['status']) && $set['status'] == true) {
            return true;
        } elseif (isset($set['msg'])) {
            $this->setError($set['msg']);
            return false;
        } else {
            $this->setError('请求失败');
            return false;
        }
    }

    /**
     * 网站备注修改
     *
     * @param [type] $ps
     * @return void
     */
    public function setPs($ps)
    {
        $set = $this->btPanel->WebSetPs($this->bt_id, $ps);
        if (isset($set['status']) && $set['status'] == true) {
            return true;
        } elseif (isset($set['msg'])) {
            $this->setError($set['msg']);
            return false;
        } else {
            $this->setError('请求失败');
            return false;
        }
    }

    // 获取软件介绍
    public function getSoftInfo($name)
    {
        $info = $this->btPanel->GetSoftFind($name);
        if ($info) {
            return $info;
        }
        return false;
    }

    // 获取数据库管理地址
    public function getphpmyadminUrl()
    {
        $info = $this->getSoftInfo('phpmyadmin');
        if ($info && isset($info['ext']['url']) && $info['ext']['url']) {
            $ip = $this->getIp();
            $url = $info['ext']['url'];
            if ($ip) {
                $url = str_replace('127.0.0.1', $ip, $info['ext']['url']);
            }
            return $url;
        }
        return false;
    }

    // 获取网站日志文件
    public function getLogsFileName()
    {
        $server = $this->getWebServer();
        // if($server=='nginx'){
        //     $file_path = '/www/server/panel/vhost/nginx/'.$this->bt_name.'.conf';
        // }elseif($server=='apache'){
        //     $file_path = '';
        // }else{
        //     $file_path = '';
        // }
        // if(!$file_path){
        //     return false;
        // }

        // $file_content = $this->btPanel->GetFileBodys($file_path);
        // 正则提取？
        // 直接拼接？
        if ($server == 'nginx') {
            $file_path = '/www/wwwlogs/' . $this->bt_name . '.log';
        } elseif ($server == 'apache') {
            $file_path = '/www/wwwlogs/' . $this->bt_name . '-access_log';
        } else {
            $file_path = '';
        }
        if (!$file_path) {
            $this->setError('日志文件不存在');
        }
        $logs_list = $this->btPanel->GetFileBodys($file_path);
        if (isset($logs_list['status']) && $logs_list['status'] == 'true') {
            return $logs_list['data'];
        } elseif (isset($logs_list['msg'])) {
            $this->setError($logs_list['msg']);
        } else {
            $this->setError('请求失败');
        }
        return false;
    }

    /**
     * 是否为专业版
     * ===true为专业版永久
     * 返回数字为专业版到期时间
     *
     * @return boolean
     */
    public function isPro()
    {
        $list = $this->btPanel->GetSoftList();
        if ($list && isset($list['pro'])) {
            switch ($list['pro']) {
                case '-1':
                    return false;
                    break;
                case '1':
                    return $list['pro'];
                case '0':
                    return true;
                default:
                    return is_numeric($list['pro']) ? $list['pro'] : false;
                    break;
            }
        } else {
            return false;
        }
    }

    /**
     * 宝塔面板付费版本
     * 返回0 = 永久
     * 返回数字时间戳 = 到期时间
     *
     * @return void
     */
    public function paidVer()
    {
        $list = $this->btPanel->GetSoftList();
        if ($list) {
            if (isset($list['pro']) && $list['pro'] >= 0) {
                return ['type' => 'pro', 'time' => $list['pro']];
            } elseif (isset($list['ltd']) && $list['ltd'] >= 0) {
                return ['type' => 'ltd', 'time' => $list['ltd']];
            } else {
                return ['type' => 'free', 'time' => 0];
            }
        }
        return false;
    }

    /**
     * 是否为企业版
     * ===true为专业版永久
     * 返回数字为企业版到期时间
     *
     * @return boolean
     */
    public function isLtd()
    {
        $list = $this->btPanel->GetSoftList();
        if ($list && isset($list['ltd'])) {
            switch ($list['ltd']) {
                case '-1':
                    return false;
                    break;
                case '1':
                    return $list['ltd'];
                case '0':
                    return true;
                default:
                    return is_numeric($list['ltd']) ? $list['ltd'] : false;
                    break;
            }
        } else {
            return false;
        }
    }

    /**
     * 创建数据库
     *
     * @param [type] $username      数据库用户名
     * @param [type] $database      数据库名
     * @param [type] $password      数据库密码
     * @param string $type 数据库类型MySQL、SQLServer
     * @return void
     */
    public function buildSql($username, $database, $password, $type = 'MySQL')
    {
        $set = $this->btPanel->AddDatabase($database, $username, $password, $type);
        if ($set && isset($set['status']) && $set['status'] == true) {
            return true;
        } elseif (isset($set['msg'])) {
            $this->setError($set['msg']);
            return false;
        } else {
            $this->setError('请求错误');
            return false;
        }
    }

    /**
     * 检查任务是否存在
     *
     * @param [type] $name      任务名称
     * @return void
     */
    public function exist_cron($name)
    {
        $list = $this->btPanel->GetCrontab();
        if (!$list) {
            $this->_error = $this->btPanel->_error;
            return false;
        }
        if ($list) {
            foreach ($list as $key => $value) {
                if (isset($value['name']) && $value['name'] == $name) {
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * 获取指定名称队列任务
     *
     * @param [type] $name      任务名称
     * @param [type] $id        任务ID
     * @return void
     */
    public function get_cron($name = '', $id = '')
    {
        $list = $this->btPanel->GetCrontab();
        if (!$list) {
            $this->_error = $this->btPanel->_error;
            return false;
        }
        foreach ($list as $key => $value) {
            if ($id && $name) {
                if ((isset($value['name']) && $value['name'] == $name) && (isset($value['id']) && $value['id'] == $id)) {
                    return $value;
                }
            } else if ($name && (isset($value['name']) && $value['name'] == $name)) {
                return $value;
            } else if ($id && (isset($value['id']) && $value['id'] == $id)) {
                return $value;
            }
        }
        return false;
    }

    /**
     * 获取所有加速站点
     *
     * @return array|bool
     */
    public function get_speed_site_list()
    {
        $list = $this->btPanel->SiteSpeed();
        if (!$list) {
            $this->_error = $this->btPanel->_error;
            return false;
        }

        return $list;
    }

    /**
     * 获取站点加速站点信息
     *
     * @param [type] $bt_name   站点名
     * @return array|bool
     */
    public function get_speed_site($bt_name)
    {
        $info = $this->btPanel->GetSiteSpeed($bt_name);
        if (!$info) {
            $this->_error = $this->btPanel->_error;
            return false;
        }
        return $info;
    }

    /**
     * 获取站点加速总开关状态
     * @return bool
     * @date 2021/6/13
     */
    public function get_speed_open()
    {
        return $this->btPanel->GetSiteSpeedSettings();
    }

    /**
     * 获取站点加速站点状态
     *
     * @param [type] $bt_name   站点名
     * @return void
     */
    public function get_speed_site_status($bt_name)
    {
        $get = $this->get_speed_site($bt_name);
        if (!$get) {
            return false;
        }
        return $get['open'] ?? 0;
    }

    /**
     * 设置站点加速站点状态开关
     *
     * @param [type] $bt_name   站点名
     * @return void
     */
    public function set_speed_site_status($bt_name)
    {
        $set = $this->btPanel->SiteSpeedStatus($bt_name);
        if (!$set) {
            $this->_error = $this->btPanel->_error;
            return false;
        }
        return $set;
    }

    // 清除服务器配置缓存
    public function clear_config()
    {
        $config = $this->btPanel->getConcifInfo();
        if (isset($config['status']) && !isset($config['msg'])) {
            return $config;
        } elseif (isset($config['msg'])) {
            $this->setError($config['msg']);
        }
        return false;
    }

    // 检查文件是否存在
    public function panel_file_exist($file)
    {
        $get = $this->btPanel->getFileLog($file);
        if (isset($get['status']) && $get['status'] == true) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * 获取服务器连接时间
     *
     * @param [type] $url
     * @param string  $data
     * @param integer $timeout
     * @param integer $time
     * @return void
     */
    public function getRequestTimes($url, $timeout = 60)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_exec($ch);
        $request = curl_getinfo($ch);
        curl_close($ch);
        return isset($request['connect_time']) ? $request['connect_time'] : false;
    }

    /**
     * 设置错误信息
     *
     * @param [type] $error     错误信息
     * @return void
     */
    public function setError($error)
    {
        $this->_error = $error;
        return $this;
    }

    /**
     * 获取错误信息
     * @return string
     */
    public function getError()
    {
        return $this->_error ? __($this->_error) : '';
    }
}

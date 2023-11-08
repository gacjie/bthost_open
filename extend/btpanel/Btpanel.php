<?php
// +----------------------------------------------------------------------
// | 宝塔接口类库
// +----------------------------------------------------------------------
// | Copyright (c) 2019-2021 rights reserved.
// +----------------------------------------------------------------------
// | Author: Youngxj
// +----------------------------------------------------------------------
// | Date: 2021年6月13日 16:41:23
// +----------------------------------------------------------------------
namespace btpanel;

use think\Config;
use think\Log;

class Btpanel
{
    private $BT_PANEL = ""; // 面板地址
    private $BT_KEY = ""; // 接口密钥

    public $_error = ''; // 错误内容收集

    public $proofType = 'tamper_proof'; // 防篡改类型

    public function __construct($bt_panel = null, $bt_key = null)
    {
        if (!$bt_panel || !$bt_key) {
            $this->_error = '面板地址|密钥不能为空';
            return false;
        }
        $this->BT_PANEL = $bt_panel;
        $this->BT_KEY = $bt_key;
    }

    /**
     * API请求
     *
     * @param [type] $api   API名
     * @param array $p_data 传递数据
     * @return void
     */
    // TODO 使用该方法进行API请求封装
    public function request_get($api, $p_data = [])
    {
        $url = $this->BT_PANEL . config("bt." . $api);

        return $this->http_post($url, $p_data)->result_decode();
    }

    /**
     * 获取服务器配置（缓存）
     */
    public function GetConfig()
    {
        $url = $this->BT_PANEL . config("bt.GetConfig");
        return $this->http_post($url)->result_decode();
    }

    /**
     * 获取服务器配置（完整）
     */
    public function getConcifInfo()
    {
        $url = $this->BT_PANEL . config("bt.getConcifInfo");
        return $this->http_post($url)->result_decode();
    }

    /**
     * 获取系统基础统计
     */
    public function GetSystemTotal()
    {
        $url = $this->BT_PANEL . config("bt.GetSystemTotal");
        return $this->http_post($url)->result_decode();
    }

    /**
     * 获取指定目录大小
     * @param [type] $path 目录名
     */
    public function GetWebSize($path)
    {
        $url = $this->BT_PANEL . config("bt.GetWebSize");
        $p_data['path'] = $path;
        return $this->http_post($url, $p_data)->result_decode();
    }

    /**
     * 获取数据库信息
     * @param [type] $db_name [description]
     */
    public function GetSqlSize($db_name)
    {
        $url = $this->BT_PANEL . config("bt.GetSqlSize");
        $p_data['db_name'] = $db_name;
        return $this->http_post($url, $p_data)->result_decode();
    }

    /**
     * 获取磁盘分区信息
     */
    public function GetDiskInfo()
    {
        $url = $this->BT_PANEL . config("bt.GetDiskInfo");
        return $this->http_post($url)->result_decode();
    }

    /**
     * 获取实时状态信息
     * (CPU、内存、网络、负载)
     */
    public function GetNetWork()
    {
        $url = $this->BT_PANEL . config("bt.GetNetWork");
        return $this->http_post($url)->result_decode();
    }

    /**
     * 检查是否有安装任务
     */
    public function GetTaskCount()
    {
        $url = $this->BT_PANEL . config("bt.GetTaskCount");
        $result = $this->HttpPostCookie($url);

        $data = $result;
        return $data;
    }

    /**
     * 检查面板更新
     */
    public function UpdatePanel($check = false, $force = false)
    {
        $url = $this->BT_PANEL . config("bt.UpdatePanel");
        $p_data['check'] = $check;
        $p_data['force'] = $force;

        return $this->http_post($url, $p_data)->result_decode();
    }

    /**
     * 面板更新
     */
    public function UpdatePanels($toUpdate = true)
    {
        $url = $this->BT_PANEL . config("bt.UpdatePanel");
        $p_data['toUpdate'] = $toUpdate;

        return $this->http_post($url, $p_data)->result_decode();
    }

    // 设置面板自动更新
    public function AutoUpdatePanel()
    {
        $url = $this->BT_PANEL . config("bt.AutoUpdatePanel");
        $data = $this->http_post($url)->result_decode();
        if (isset($data['status']) && $data['status'] == true) {
            return true;
        }
        $this->_error = $data['msg'] ?? __('Fail');
        return false;
    }

    // 关闭面板自动更新
    public function AutoUpdatePanelOff($file)
    {
        // 删除路径文件
        $deleteFile = $this->DeleteFile($file);
        return true;
    }

    /**
     * 检查专业版
     * @Author   Youngxj
     * @DateTime 2019-04-25
     */
    public function IsPro()
    {
        $url = $this->BT_PANEL . config("bt.IsPro");

        return $this->http_post($url)->result_decode();
    }

    /**
     * 面板修复
     * @Author   Youngxj
     * @DateTime 2019-04-30
     * @param    [type]     $action RepPanel
     */
    public function RepPanel($action = 'RepPanel')
    {
        $url = $this->BT_PANEL . config("bt.RepPanel");

        $p_data['action'] = $action;
        return $this->http_post($url, $p_data)->result_decode();
    }

    /**
     * 面板重启
     * @Author   Youngxj
     * @DateTime 2019-04-30
     * @param string $action ReWeb
     */
    public function ReWeb($action = 'ReWeb')
    {
        $url = $this->BT_PANEL . config("bt.ReWeb");

        $p_data['action'] = $action;
        return $this->http_post($url, $p_data)->result_decode();
    }

    /**
     * 系统服务
     * @Author   Youngxj
     * @DateTime 2019-04-30
     * @param    [type]     $name 服务名
     * @param    [type]     $type 状态
     */
    public function ServiceAdmin($name, $type = 'stop')
    {
        $url = $this->BT_PANEL . config("bt.ServiceAdmin");

        $p_data['name'] = $name;
        $p_data['type'] = $type;
        return $this->http_post($url, $p_data)->result_decode();
    }

    /**
     * 系统重启
     * @Author   Youngxj
     * @DateTime 2019-04-30
     * @param    [type]     $action RestartServer
     */
    public function RestartServer($action = 'RestartServer')
    {
        $url = $this->BT_PANEL . config("bt.RestartServer");

        $p_data['action'] = $action;
        return $this->http_post($url, $p_data)->result_decode();
    }

    /**
     * 获取网站列表
     * @param string $page   当前分页
     * @param string $limit  取出的数据行数
     * @param string $type   分类标识 -1: 分部分类 0: 默认分类
     * @param string $order  排序规则 使用 id 降序：id desc 使用名称升序：name desc
     * @param string $tojs   分页 JS 回调,若不传则构造 URI 分页连接
     * @param string $search 搜索内容
     */
    public function Websites($search = '', $page = '1', $limit = '15', $type = '-1', $order = 'id desc', $tojs = '')
    {
        $url = $this->BT_PANEL . config("bt.Websites");
        $p_data['p'] = $page;
        $p_data['limit'] = $limit;
        $p_data['type'] = $type;
        $p_data['order'] = $order;
        $p_data['tojs'] = $tojs;
        $p_data['search'] = $search;
        return $this->http_post($url, $p_data)->result_decode();
    }

    /**
     * 获取网站下的域名列表
     * @Author   Youngxj
     * @DateTime 2019-04-27
     * @param    [type]     $search [description]
     * @param    [type]     $table  [description]
     * @param string $list [description]
     */
    public function Websitess($search, $table, $list = 'True')
    {
        $url = $this->BT_PANEL . config("bt.Websitess");
        $p_data['table'] = $table;
        $p_data['list'] = $list;
        $p_data['search'] = $search;
        return $this->http_post($url, $p_data)->result_decode();
    }

    /**
     * 获取重定向内测版列表
     * @Author   Youngxj
     * @DateTime 2019-04-02
     * @param    [type]     $sitename [description]
     */
    public function GetRedirectList($sitename)
    {
        $url = $this->BT_PANEL . config("bt.GetRedirectList");
        $p_data['sitename'] = $sitename;
        return $this->http_post($url, $p_data)->result_decode();
    }

    /**
     * 添加重定向
     * @Author   Youngxj
     * @DateTime 2019-04-02
     * @param    [type]     $sitename       站点名
     * @param    [type]     $redirecttype   301 or 302
     * @param    [type]     $domainorpath   重定向类型
     * @param    [type]     $redirectdomain 重定向域名
     * @param    [type]     $redirectpath   重定向路径
     * @param    [type]     $tourl          目标url
     * @param integer $type 开启重定向
     * @param    [type]     $holdpath       保留url参数
     */
    public function CreateRedirect($sitename, $redirecttype, $domainorpath, $redirectdomain, $redirectpath, $tourl, $type = 1, $holdpath)
    {
        $url = $this->BT_PANEL . config("bt.CreateRedirect");
        $p_data['sitename'] = $sitename;
        $p_data['redirecttype'] = $redirecttype;
        $p_data['domainorpath'] = $domainorpath;
        $p_data['redirectdomain'] = (string)$redirectdomain;
        $p_data['redirectpath'] = $redirectpath;
        $p_data['tourl'] = $tourl;
        $p_data['type'] = $type;
        $p_data['holdpath'] = $holdpath;
        $p_data['redirectname'] = time();
        return $this->http_post($url, $p_data)->result_decode();
    }

    /**
     * 重定向删除
     * @Author   Youngxj
     * @DateTime 2019-04-02
     * @param    [type]     $sitename     站点名
     * @param    [type]     $redirectname 重定向名称
     */
    public function DeleteRedirect($sitename, $redirectname)
    {
        $url = $this->BT_PANEL . config("bt.DeleteRedirect");
        $p_data['sitename'] = $sitename;
        $p_data['redirectname'] = $redirectname;
        return $this->http_post($url, $p_data)->result_decode();
    }

    /**
     * 修改重定向
     * @Author   Youngxj
     * @DateTime 2019-04-02
     * @param    [type]     $sitename       站点名
     * @param    [type]     $redirectname   重定向名
     * @param    [type]     $redirecttype   301 or 302
     * @param    [type]     $domainorpath   重定向类型
     * @param    [type]     $redirectdomain 重定向域名
     * @param    [type]     $redirectpath   重定向路径
     * @param    [type]     $tourl          目标url
     * @param integer $type 开启重定向
     * @param    [type]     $holdpath       保留url参数
     */
    public function ModifyRedirect($sitename, $redirectname, $redirecttype, $domainorpath, $redirectdomain, $redirectpath, $tourl, $type = 1, $holdpath)
    {
        $url = $this->BT_PANEL . config("bt.ModifyRedirect");
        $p_data['sitename'] = $sitename;
        $p_data['redirecttype'] = $redirecttype;
        $p_data['domainorpath'] = $domainorpath;
        $p_data['redirectdomain'] = (string)$redirectdomain;
        $p_data['redirectpath'] = $redirectpath;
        $p_data['tourl'] = $tourl;
        $p_data['type'] = $type;
        $p_data['holdpath'] = $holdpath;
        $p_data['redirectname'] = $redirectname;
        return $this->http_post($url, $p_data)->result_decode();
    }

    /**
     * 获取网站FTP列表
     * @param string $page   当前分页
     * @param string $limit  取出的数据行数
     * @param string $type   分类标识 -1: 分部分类 0: 默认分类
     * @param string $order  排序规则 使用 id 降序：id desc 使用名称升序：name desc
     * @param string $tojs   分页 JS 回调,若不传则构造 URI 分页连接
     * @param string $search 搜索内容
     */
    public function WebFtpList($search = '', $page = '1', $limit = '15', $order = 'id desc', $tojs = '')
    {
        $url = $this->BT_PANEL . config("bt.WebFtpList");
        $p_data['p'] = $page;
        $p_data['limit'] = $limit;
        $p_data['order'] = $order;
        $p_data['tojs'] = $tojs;
        $p_data['search'] = $search;
        return $this->http_post($url, $p_data)->result_decode();
    }

    /**
     * 获取网站数据库列表
     * @param string $page   当前分页
     * @param string $limit  取出的数据行数
     * @param string $type   分类标识 -1: 分部分类 0: 默认分类
     * @param string $order  排序规则 使用 id 降序：id desc 使用名称升序：name desc
     * @param string $tojs   分页 JS 回调,若不传则构造 URI 分页连接
     * @param string $search 搜索内容
     */
    public function WebSqlList($search = '', $page = '1', $limit = '15', $type = '-1', $order = 'id desc', $tojs = '')
    {
        $url = $this->BT_PANEL . config("bt.WebSqlList");
        $p_data['p'] = $page;
        $p_data['limit'] = $limit;
        $p_data['type'] = $type;
        $p_data['order'] = $order;
        $p_data['tojs'] = $tojs;
        $p_data['search'] = $search;

        return $this->http_post($url, $p_data)->result_decode();
    }

    /**
     * 获取所有网站分类
     */
    public function Webtypes()
    {
        $url = $this->BT_PANEL . config("bt.Webtypes");
        return $this->http_post($url)->result_decode();
    }

    /**
     * 修改网站分类
     *
     * @param [type] $site_ids  站点ID组，[179]
     * @param [type] $id        分类ID
     * @return void
     */
    public function set_site_type($site_ids, $id)
    {
        $url = $this->BT_PANEL . config("bt.set_site_type");
        $p_data['site_ids'] = $site_ids;
        $p_data['id'] = $id;
        $data = $this->http_post($url, $p_data)->result_decode();

        if ($data && isset($data['status']) && $data['status'] == true) {
            return true;
        }
        $this->_error = $data['msg'] ?? __('Fail');
        return false;
    }

    /**
     * 添加网站分类
     *
     * @param [type] $name      分类名
     * @return void
     */
    public function add_site_type($name)
    {
        $url = $this->BT_PANEL . config("bt.add_site_type");
        $p_data['name'] = $name;
        $data = $this->http_post($url, $p_data)->result_decode();
        if ($data && isset($data['status']) && $data['status']) {
            return true;
        }
        $this->_error = $data['msg'] ?? __('Fail');
        return false;
    }

    /**
     * 修改网站分类
     *
     * @param [type] $id        分类ID
     * @param [type] $name      分类名称
     * @return void
     */
    public function edit_site_type($id, $name)
    {
        $url = $this->BT_PANEL . config("bt.edit_site_type");
        $p_data['id'] = $id;
        $p_data['name'] = $name;
        $data = $this->http_post($url, $p_data)->result_decode();
        if ($data && isset($data['status']) && $data['status']) {
            return true;
        }
        $this->_error = $data['msg'] ?? __('Fail');
        return false;
    }

    /**
     * 删除网站分类
     *
     * @param [type] $id        分类ID
     * @return void
     */
    public function delete_site_type($id)
    {
        $url = $this->BT_PANEL . config("bt.delete_site_type");
        $p_data['id'] = $id;
        $data = $this->http_post($url, $p_data)->result_decode();
        if ($data && isset($data['status']) && $data['status']) {
            return true;
        }
        $this->_error = $data['msg'] ?? __('Fail');
        return false;
    }

    /**
     * 获取已安装的 PHP 版本列表
     */
    public function GetPHPVersion()
    {
        //拼接URL地址
        $url = $this->BT_PANEL . config("bt.GetPHPVersion");
        //请求面板接口
        return $this->http_post($url)->result_decode();
    }

    /**
     * 修改指定网站的PHP版本
     * @param [type] $site 网站名
     * @param [type] $php  PHP版本
     */
    public function SetPHPVersion($site, $php)
    {
        $url = $this->BT_PANEL . config("bt.SetPHPVersion");
        $p_data['siteName'] = $site;
        $p_data['version'] = $php;
        return $this->http_post($url, $p_data)->result_decode();
    }

    /**
     * 获取指定网站运行的PHP版本
     * @param [type] $site 网站名
     */
    public function GetSitePHPVersion($siteName)
    {
        $url = $this->BT_PANEL . config("bt.GetSitePHPVersion");
        $p_data['siteName'] = $siteName;
        return $this->http_post($url, $p_data)->result_decode();
    }

    /**
     * 新增网站
     * @param [type] $webname      网站域名 json格式
     * @param [type] $path         网站路径
     * @param [type] $type_id      网站分类ID
     * @param string $type 网站类型
     * @param [type] $version      PHP版本
     * @param [type] $port         网站端口
     * @param [type] $ps           网站备注
     * @param [type] $ftp          网站是否开通FTP
     * @param [type] $ftp_username FTP用户名
     * @param [type] $ftp_password FTP密码
     * @param [type] $sql          网站是否开通数据库 windows：MySQL、SQLServer
     * @param [type] $codeing      数据库编码类型 utf8|utf8mb4|gbk|big5
     * @param [type] $datauser     数据库账号
     * @param [type] $datapassword 数据库密码
     */
    public function AddSite($infoArr = [])
    {
        $url = $this->BT_PANEL . config("bt.WebAddSite");

        //准备POST数据
        //取签名
        $p_data['webname'] = $infoArr['webname'];
        $p_data['path'] = $infoArr['path'];
        $p_data['type_id'] = $infoArr['type_id'];
        $p_data['type'] = $infoArr['type'];
        $p_data['version'] = $infoArr['version'];
        $p_data['port'] = $infoArr['port'];
        $p_data['ps'] = $infoArr['ps'];
        $p_data['ftp'] = $infoArr['ftp'];
        $p_data['ftp_username'] = $infoArr['ftp_username'];
        $p_data['ftp_password'] = $infoArr['ftp_password'];
        $p_data['sql'] = $infoArr['sql'];
        $p_data['codeing'] = $infoArr['codeing'];
        $p_data['datauser'] = $infoArr['datauser'];
        $p_data['datapassword'] = $infoArr['datapassword'];
        $p_data['check_dir'] = $infoArr['check_dir'];

        return $this->http_post($url, $p_data)->result_decode();
    }

    /**
     * 删除网站
     * @param [type] $id       网站ID
     * @param [type] $webname  网站名称
     * @param [type] $ftp      是否删除关联FTP
     * @param [type] $database 是否删除关联数据库
     * @param [type] $path     是否删除关联网站根目录
     *
     */
    public function WebDeleteSite($id, $webname, $ftp = 1, $database = 1, $path = 1)
    {
        $url = $this->BT_PANEL . config("bt.WebDeleteSite");
        $p_data['id'] = $id;
        $p_data['webname'] = $webname;
        $p_data['ftp'] = $ftp;
        $p_data['database'] = $database;
        $p_data['path'] = $path;

        return $this->http_post($url, $p_data)->result_decode();
    }

    /**
     * 停用站点
     * @param [type] $id   网站ID
     * @param [type] $name 网站域名
     */
    public function WebSiteStop($id, $name)
    {
        $url = $this->BT_PANEL . config("bt.WebSiteStop");
        $p_data['id'] = $id;
        $p_data['name'] = $name;
        return $this->http_post($url, $p_data)->result_decode();
    }

    /**
     * 启用网站
     * @param [type] $id   网站ID
     * @param [type] $name 网站域名
     */
    public function WebSiteStart($id, $name)
    {
        $url = $this->BT_PANEL . config("bt.WebSiteStart");
        $p_data['id'] = $id;
        $p_data['name'] = $name;
        return $this->http_post($url, $p_data)->result_decode();
    }

    /**
     * 设置网站到期时间
     * @param [type] $id    网站ID
     * @param [type] $edate 网站到期时间 格式：2019-01-01，永久：0000-00-00
     */
    public function WebSetEdate($id, $edate)
    {
        $url = $this->BT_PANEL . config("bt.WebSetEdate");
        $p_data['id'] = $id;
        $p_data['edate'] = $edate;
        return $this->http_post($url, $p_data)->result_decode();
    }

    /**
     * 修改网站备注
     * @param [type] $id 网站ID
     * @param [type] $ps 网站备注
     */
    public function WebSetPs($id, $ps)
    {
        $url = $this->BT_PANEL . config("bt.WebSetPs");
        $p_data['id'] = $id;
        $p_data['ps'] = $ps;
        return $this->http_post($url, $p_data)->result_decode();
    }

    /**
     * 获取网站备份列表
     * @param [type] $id    网站ID
     * @param string $page  当前分页
     * @param string $limit 每页取出的数据行数
     * @param string $type  备份类型 目前固定为0
     * @param string $tojs  分页js回调若不传则构造 URI 分页连接 get_site_backup
     */
    public function WebBackupList($id, $page = '1', $limit = '5', $type = '0', $tojs = '')
    {
        $url = $this->BT_PANEL . config("bt.WebBackupList");
        $p_data['p'] = $page;
        $p_data['limit'] = $limit;
        $p_data['type'] = $type;
        $p_data['tojs'] = $tojs;
        $p_data['search'] = $id;
        return $this->http_post($url, $p_data)->result_decode();
    }

    /**
     * 创建网站备份
     * @param [type] $id 网站ID
     */
    public function WebToBackup($id)
    {
        $url = $this->BT_PANEL . config("bt.WebToBackup");
        $p_data['id'] = $id;
        return $this->http_post($url, $p_data)->result_decode();
    }

    /**
     * 删除网站备份
     * @param [type] $id 网站备份ID
     */
    public function WebDelBackup($id)
    {
        $url = $this->BT_PANEL . config("bt.WebDelBackup");
        $p_data['id'] = $id;
        return $this->http_post($url, $p_data)->result_decode();
    }

    /**
     * 删除数据库备份
     * @param [type] $id 数据库备份ID
     */
    public function SQLDelBackup($id)
    {
        $url = $this->BT_PANEL . config("bt.SQLDelBackup");
        $p_data['id'] = $id;
        return $this->http_post($url, $p_data)->result_decode();
    }

    /**
     * 备份数据库
     * @param [type] $id 数据库列表ID
     */
    public function SQLToBackup($id)
    {
        $url = $this->BT_PANEL . config("bt.SQLToBackup");
        $p_data['id'] = $id;
        return $this->http_post($url, $p_data)->result_decode();
    }

    /**
     * 数据库备份还原
     * @param [type] $file [description]
     * @param [type] $name [description]
     */
    public function SQLInputSql($file, $name)
    {
        $url = $this->BT_PANEL . config("bt.InputSql");
        $p_data['file'] = '/www/backup/database/' . $file;
        $p_data['name'] = $name;
        return $this->http_post($url, $p_data)->result_decode();
    }

    /**
     * 数据库导入
     * @param [type] $file [description]
     * @param [type] $name [description]
     */
    public function SQLInputSqlFile($file, $name)
    {
        $url = $this->BT_PANEL . config("bt.InputSql");
        $p_data['file'] = $file;
        $p_data['name'] = $name;
        return $this->http_post($url, $p_data)->result_decode();
    }

    /**
     * 创建数据库
     *
     * @param [type] $name          数据库名称
     * @param [type] $db_user       数据库账号
     * @param [type] $password      数据库密码
     * @param string $dtype      数据库类型MySQL、SQLServer
     * @param string $codeing    数据库编码utf-8、utf8mb4、gbk、big5
     * @param string $dataAccess 数据库认证：内部=127.0.0.1,开放所有=%,指定IP='ip'
     * @param string $address    数据库访问权限：内部=127.0.0.1,开放所有=%,指定IP=ip地址
     * @param string $ps         数据库备注
     * @return void
     */
    public function AddDatabase($name, $db_user, $password, $dtype = 'MySQL', $codeing = 'utf8', $dataAccess = '127.0.0.1', $address = '127.0.0.1', $ps = '')
    {
        $url = $this->BT_PANEL . config("bt.AddDatabase");
        $p_data['name'] = $name;
        $p_data['codeing'] = $codeing;
        $p_data['db_user'] = $db_user;
        $p_data['password'] = $password;
        $p_data['dtype'] = $dtype;
        $p_data['dataAccess'] = $dataAccess;
        $p_data['address'] = $address;
        $p_data['ps'] = $ps ? $ps : $name;
        return $this->http_post($url, $p_data)->result_decode();
    }

    /**
     * 删除数据库
     *
     * @param [type] $id        数据库ID
     * @param [type] $name      数据库名
     * @return void
     */
    public function DeleteDatabase($id, $name)
    {
        $url = $this->BT_PANEL . config("bt.DeleteDatabase");
        $p_data['id'] = $id;
        $p_data['name'] = $name;
        return $this->http_post($url, $p_data)->result_decode();
    }

    /**
     * 获取网站域名列表
     * @param [type]  $id   网站ID
     * @param boolean $list 固定传true
     */
    public function WebDoaminList($id, $list = true)
    {
        $url = $this->BT_PANEL . config("bt.WebDoaminList");
        $p_data['table'] = 'domain';
        $p_data['search'] = $id;
        $p_data['list'] = $list;
        return $this->http_post($url, $p_data)->result_decode();
    }

    /**
     * 添加域名
     * @param [type] $id      网站ID
     * @param [type] $webname 网站名称
     * @param [type] $domain  要添加的域名:端口 80 端品不必构造端口,多个域名用换行符隔开
     */
    public function WebAddDomain($id, $webname, $domain)
    {
        $url = $this->BT_PANEL . config("bt.WebAddDomain");
        $p_data['id'] = $id;
        $p_data['webname'] = $webname;
        $p_data['domain'] = $domain;
        return $this->http_post($url, $p_data)->result_decode();
    }

    /**
     * 删除网站域名
     * @param [type] $id      网站ID
     * @param [type] $webname 网站名
     * @param [type] $domain  网站域名
     * @param [type] $port    网站域名端口
     */
    public function WebDelDomain($id, $webname, $domain, $port = '80')
    {
        $result = $this->request_get('WebDelDomain', compact('id', 'webname', 'domain', 'port'));
        if ($result && isset($result['status']) && $result['status'] == true) {
            return $result;
        }
        $this->_error = $result['msg'] ?? __('Fail');
        return false;
    }

    /**
     * 获取可选的预定义伪静态列表
     * @param [type] $siteName 网站名
     */
    public function GetRewriteList($siteName)
    {
        $url = $this->BT_PANEL . config("bt.GetRewriteList");
        $p_data['siteName'] = $siteName;
        return $this->http_post($url, $p_data)->result_decode();
    }

    /**
     * 获取预置伪静态规则内容（文件内容）
     * @param [type] $path 规则名
     * @param [type] $type 0->获取内置伪静态规则；1->获取当前站点伪静态规则
     */
    public function GetFileBody($path, $type = 0)
    {
        $url = $this->BT_PANEL . config("bt.GetFileBody");

        $path_dir = $type ? 'vhost/rewrite' : 'rewrite/nginx';

        //获取当前站点伪静态规则
        ///www/server/panel/vhost/rewrite/user_hvVBT_1.test.com.conf
        //获取内置伪静态规则
        ///www/server/panel/rewrite/nginx/EmpireCMS.conf
        //保存伪静态规则到站点
        ///www/server/panel/vhost/rewrite/user_hvVBT_1.test.com.conf
        ///www/server/panel/rewrite/nginx/typecho.conf
        $p_data['path'] = '/www/server/panel/' . $path_dir . '/' . $path . '.conf';
        return $this->http_post($url, $p_data)->result_decode();
    }

    /**
     * 获取预置伪静态规则内容（文件内容）
     * @param [type] $path 规则名
     * @param [type] $type nginx;iis;apache
     */
    public function GetFileBody_win($path, $type = 'nginx')
    {
        $url = $this->BT_PANEL . config("bt.GetFileBody");
        $path_dir = 'rewrite/' . $type;

        //获取当前站点伪静态规则
        ///www/server/panel/vhost/rewrite/user_hvVBT_1.test.com.conf
        //获取内置伪静态规则
        ///www/server/panel/rewrite/nginx/EmpireCMS.conf
        //保存伪静态规则到站点
        ///www/server/panel/vhost/rewrite/user_hvVBT_1.test.com.conf
        ///www/server/panel/rewrite/nginx/typecho.conf
        // C:/BtSoft/panel/rewrite/iis/discuz2.conf
        $p_data['path'] = 'C:/BtSoft/panel/' . $path_dir . '/' . $path . '.conf';
        return $this->http_post($url, $p_data)->result_decode();
    }

    /**
     * 保存伪静态规则内容(保存文件内容)
     * @param [type] $path     规则名
     * @param [type] $data     规则内容
     * @param string $encoding 规则编码强转utf-8
     * @param number $type     0->系统默认路径；1->自定义全路径
     */
    public function SaveFileBody($path, $data, $encoding = 'utf-8', $type = 0)
    {
        $url = $this->BT_PANEL . config("bt.SaveFileBody");
        if ($type) {
            $path_dir = $path;
        } else {
            $path_dir = '/www/server/panel/vhost/rewrite/' . $path . '.conf';
        }

        $p_data['path'] = $path_dir;
        $p_data['data'] = $data;
        $p_data['encoding'] = $encoding;
        return $this->http_post($url, $p_data)->result_decode();
    }

    /**
     * 保存文件内容
     * @Author   Youngxj
     * @DateTime 2019-05-05
     * @param    [type]     $data     内容
     * @param    [type]     $path     文件路径
     * @param string $encoding 编码
     */
    public function SaveFileBodys($data, $path, $encoding = 'utf-8')
    {
        $url = $this->BT_PANEL . config("bt.SaveFileBody");
        $p_data['path'] = $path;
        $p_data['data'] = $data;
        $p_data['encoding'] = $encoding;
        return $this->http_post($url, $p_data)->result_decode();
    }

    /**
     * 设置密码访问网站
     * @param [type] $id       网站ID
     * @param [type] $username 用户名
     * @param [type] $password 密码
     */
    public function SetHasPwd($id, $username, $password)
    {
        $url = $this->BT_PANEL . config("bt.SetHasPwd");
        $p_data['id'] = $id;
        $p_data['username'] = $username;
        $p_data['password'] = $password;
        return $this->http_post($url, $p_data)->result_decode();
    }

    /**
     * 关闭密码访问网站
     * @param [type] $id 网站ID
     */
    public function CloseHasPwd($id)
    {
        $url = $this->BT_PANEL . config("bt.CloseHasPwd");
        $p_data['id'] = $id;
        return $this->http_post($url, $p_data)->result_decode();
    }

    /**
     * 获取网站日志
     * @param [type] $site 网站名
     */
    public function GetSiteLogs($site)
    {
        $url = $this->BT_PANEL . config("bt.GetSiteLogs");
        $p_data['siteName'] = $site;
        return $this->http_post($url, $p_data)->result_decode();
    }

    /**
     * 获取网站盗链状态及规则信息
     * @param [type] $id   网站ID
     * @param [type] $site 网站名
     */
    public function GetSecurity($id, $site)
    {
        $url = $this->BT_PANEL . config("bt.GetSecurity");
        $p_data['id'] = $id;
        $p_data['name'] = $site;
        return $this->http_post($url, $p_data)->result_decode();
    }

    /**
     * 设置网站盗链状态及规则信息
     * @param [type] $id      网站ID
     * @param [type] $site    网站名
     * @param [type] $fix     URL后缀
     * @param [type] $domains 许可域名
     * @param [type] $status  状态 true为启动 false为禁用 1为允许空HTTP_REFERER请求
     * 7.5.22版本新增
     * @param [type] $return_rule 响应资源 可设置404/403等状态码，也可以设置一个有效资源，如：/security.png
     */
    public function SetSecurity($id, $site, $fix, $domains, $status = 'true', $return_rule = '404')
    {
        $url = $this->BT_PANEL . config("bt.SetSecurity");
        $p_data['id'] = $id;
        $p_data['name'] = $site;
        $p_data['fix'] = $fix;
        $p_data['domains'] = $domains;
        $p_data['return_rule'] = $return_rule;
        $p_data['status'] = $status ? $status : 'false'; // 必须传字符型
        return $this->http_post($url, $p_data)->result_decode();
    }

    /**
     * 获取网站三项配置开关（防跨站、日志、密码访问）
     * @param [type] $id   网站ID
     * @param [type] $path 网站运行目录
     */
    public function GetDirUserINI($id, $path)
    {
        $url = $this->BT_PANEL . config("bt.GetDirUserINI");
        $p_data['id'] = $id;
        $p_data['path'] = $path;
        return $this->http_post($url, $p_data)->result_decode();
    }

    /**
     * 开启强制HTTPS
     * @param [type] $site 网站域名（纯域名）
     */
    public function HttpToHttps($site)
    {
        $url = $this->BT_PANEL . config("bt.HttpToHttps");
        $p_data['siteName'] = $site;
        return $this->http_post($url, $p_data)->result_decode();
    }

    /**
     * 关闭强制HTTPS
     * @param [type] $site 域名(纯域名)
     */
    public function CloseToHttps($site)
    {
        $url = $this->BT_PANEL . config("bt.CloseToHttps");
        $p_data['siteName'] = $site;
        return $this->http_post($url, $p_data)->result_decode();
    }

    /**
     * 设置SSL域名证书
     * @param [type] $type 类型
     * @param [type] $site 网站名
     * @param [type] $key  证书key
     * @param [type] $csr  证书PEM
     */
    public function SetSSL($type, $site, $key, $csr)
    {
        $url = $this->BT_PANEL . config("bt.SetSSL");
        $p_data['type'] = $type;
        $p_data['siteName'] = $site;
        $p_data['key'] = $key;
        $p_data['csr'] = $csr;
        return $this->http_post($url, $p_data)->result_decode();
    }

    /**
     * 关闭SSL
     * @param [type] $updateOf 修改状态码
     * @param [type] $site     域名(纯域名)
     */
    public function CloseSSLConf($updateOf, $site)
    {
        $url = $this->BT_PANEL . config("bt.CloseSSLConf");
        $p_data['updateOf'] = $updateOf;
        $p_data['siteName'] = $site;
        return $this->http_post($url, $p_data)->result_decode();
    }

    /**
     * 获取SSL状态及证书信息
     * @param [type] $site 域名（纯域名）
     */
    public function GetSSL($site)
    {
        $url = $this->BT_PANEL . config("bt.GetSSL");
        $p_data['siteName'] = $site;
        return $this->http_post($url, $p_data)->result_decode();
    }

    /**
     * 申请ssl证书 TrustAsia 域名型SSL证书(D3)
     * @Author   Youngxj
     * @DateTime 2019-04-28
     * @param    [type]     $domain 域名
     * @param    [type]     $path   站点目录
     */
    public function GetDVSSL($domain, $path)
    {
        $url = $this->BT_PANEL . config("bt.GetDVSSL");
        return $this->http_post($url, compact('domain', 'path'))->result_decode();
    }

    /**
     * 部署前效验ssl证书
     * @Author   Youngxj
     * @DateTime 2019-04-28
     * @param    [type]     $siteName       站点名
     * @param    [type]     $partnerOrderId 订单返回的partnerOrderId
     */
    public function Completed($siteName, $partnerOrderId)
    {
        $url = $this->BT_PANEL . config("bt.Completed");
        return $this->http_post($url, compact('siteName', 'partnerOrderId'))->result_decode();
    }

    /**
     * 获取申请的证书信息/状态
     * @Author   Youngxj
     * @DateTime 2019-04-28
     * @param    [type]     $siteName       [description]
     * @param    [type]     $partnerOrderId [description]
     */
    public function GetSSLInfo($siteName, $partnerOrderId)
    {
        $url = $this->BT_PANEL . config("bt.GetSSLInfo");
        return $this->http_post($url, compact('siteName', 'partnerOrderId'))->result_decode();
    }

    /**
     * 获取网站默认文件
     * @param [type] $id 网站ID
     */
    public function WebGetIndex($id)
    {
        $url = $this->BT_PANEL . config("bt.WebGetIndex");
        $p_data['id'] = $id;
        return $this->http_post($url, $p_data)->result_decode();
    }

    /**
     * 设置网站默认文件
     * @param [type] $id    网站ID
     * @param [type] $index 内容
     */
    public function WebSetIndex($id, $index)
    {
        $url = $this->BT_PANEL . config("bt.WebSetIndex");
        $p_data['id'] = $id;
        $p_data['Index'] = $index;
        return $this->http_post($url, $p_data)->result_decode();
    }

    /**
     * 获取网站流量限制信息
     * @param [type] $id [description]
     */
    public function GetLimitNet($id)
    {
        $url = $this->BT_PANEL . config("bt.GetLimitNet");
        $p_data['id'] = $id;
        return $this->http_post($url, $p_data)->result_decode();
    }

    /**
     * 设置网站流量限制信息
     * @param [type] $id         网站ID
     * @param [type] $perserver  并发限制 300
     * @param [type] $perip      单IP限制 25
     * @param [type] $limit_rate 流量限制 512
     */
    public function SetLimitNet($id, $perserver, $perip, $limit_rate)
    {
        $url = $this->BT_PANEL . config("bt.SetLimitNet");
        return $this->http_post($url, compact('id', 'perserver', 'perip', 'limit_rate'))->result_decode();
    }

    /**
     * 设置网站流量限制信息
     * @param [type] $id         网站ID
     * @param [type] $perserver  并发限制 300
     * @param [type] $perip      单IP限制 120
     * @param [type] $limit_rate 流量限制 512
     */
    public function SetLimitNet_win($id, $perserver, $timeout, $limit_rate)
    {
        $url = $this->BT_PANEL . config("bt.SetLimitNet");
        return $this->http_post($url, compact('id', 'perserver', 'timeout', 'limit_rate'))->result_decode();
    }

    /**
     * 关闭网站流量限制
     * @param [type] $id 网站ID
     */
    public function CloseLimitNet($id)
    {
        $url = $this->BT_PANEL . config("bt.CloseLimitNet");
        $p_data['id'] = $id;
        return $this->http_post($url, $p_data)->result_decode();
    }

    /**
     * 获取网站301重定向信息
     * @param [type] $site 网站名
     */
    public function Get301Status($site)
    {
        $url = $this->BT_PANEL . config("bt.Get301Status");
        $p_data['siteName'] = $site;
        return $this->http_post($url, $p_data)->result_decode();
    }

    /**
     * 设置网站301重定向信息
     * @param [type] $site      网站名
     * @param [type] $toDomain  目标Url
     * @param [type] $srcDomain 来自Url
     * @param [type] $type      类型
     */
    public function Set301Status($site, $toDomain, $srcDomain, $type)
    {
        $url = $this->BT_PANEL . config("bt.Set301Status");
        $p_data['siteName'] = $site;
        $p_data['toDomain'] = $toDomain;
        $p_data['srcDomain'] = $srcDomain;
        $p_data['type'] = $type;
        return $this->http_post($url, $p_data)->result_decode();
    }

    /**
     * 获取网站反代信息及状态
     * @param [type] $site [description]
     */
    public function GetProxyList($site)
    {
        $url = $this->BT_PANEL . config("bt.GetProxyList");
        $p_data['sitename'] = $site;
        return $this->http_post($url, $p_data)->result_decode();
    }

    /**
     * 添加网站反代信息
     * @param [type] $cache     是否缓存
     * @param [type] $proxyname 代理名称
     * @param [type] $cachetime 缓存时长 /小时
     * @param [type] $proxydir  代理目录
     * @param [type] $proxysite 反代URL
     * @param [type] $todomain  目标域名
     * @param [type] $advanced  高级功能：开启代理目录
     * @param [type] $sitename  网站名
     * @param [type] $subfilter 文本替换json格式[{"sub1":"百度","sub2":"白底"},{"sub1":"","sub2":""}]
     * @param [type] $type      开启或关闭 0关;1开
     */
    public function CreateProxy($cache, $proxyname, $cachetime, $proxydir, $proxysite, $todomain, $advanced, $sitename, $subfilter, $type)
    {
        $url = $this->BT_PANEL . config("bt.CreateProxy");
        return $this->http_post($url, compact('cache', 'proxyname', 'cachetime', 'proxydir', 'proxysite', 'todomain', 'advanced', 'sitename', 'subfilter', 'type'))->result_decode();
    }

    /**
     * 添加网站反代信息Windows版
     * @Author   Youngxj
     * @DateTime 2019-12-13
     * @param    [type]     $data [description]
     */
    public function CreateProxy_win($data)
    {
        $url = $this->BT_PANEL . config("bt.CreateProxy");
        $p_data = $data;
        return $this->http_post($url, $p_data)->result_decode();
    }

    /**
     * 添加网站反代信息
     * @param [type] $cache     是否缓存
     * @param [type] $proxyname 代理名称
     * @param [type] $cachetime 缓存时长 /小时
     * @param [type] $proxydir  代理目录
     * @param [type] $proxysite 反代URL
     * @param [type] $todomain  目标域名
     * @param [type] $advanced  高级功能：开启代理目录
     * @param [type] $sitename  网站名
     * @param [type] $subfilter 文本替换json格式[{"sub1":"百度","sub2":"白底"},{"sub1":"","sub2":""}]
     * @param [type] $type      开启或关闭 0关;1开
     */
    public function ModifyProxy($cache, $proxyname, $cachetime, $proxydir, $proxysite, $todomain, $advanced, $sitename, $subfilter, $type)
    {
        $url = $this->BT_PANEL . config("bt.ModifyProxy");
        $p_data['cache'] = $cache;
        $p_data['proxyname'] = $proxyname;
        $p_data['cachetime'] = $cachetime;
        $p_data['proxydir'] = $proxydir;
        $p_data['proxysite'] = $proxysite;
        $p_data['todomain'] = $todomain;
        $p_data['advanced'] = $advanced;
        $p_data['sitename'] = $sitename;
        $p_data['subfilter'] = $subfilter;
        $p_data['type'] = $type;
        return $this->http_post($url, $p_data)->result_decode();
    }

    /**
     * 删除反代
     * @Author   Youngxj
     * @DateTime 2019-04-27
     * @param    [type]     $sitename  网站名
     * @param    [type]     $proxyname 反代名称
     */
    public function RemoveProxy($sitename, $proxyname)
    {
        $url = $this->BT_PANEL . config("bt.RemoveProxy");
        $p_data['sitename'] = $sitename;
        $p_data['proxyname'] = $proxyname;
        return $this->http_post($url, $p_data)->result_decode();
    }

    /**
     * 获取网站域名绑定二级目录信息
     * @param [type] $id 网站ID
     */
    public function GetDirBinding($id)
    {
        $url = $this->BT_PANEL . config("bt.GetDirBinding");
        $p_data['id'] = $id;
        return $this->http_post($url, $p_data)->result_decode();
    }

    /**
     * 设置网站域名绑定二级目录
     * @param [type] $id      网站ID
     * @param [type] $domain  域名
     * @param [type] $dirName 目录
     */
    public function AddDirBinding($id, $domain, $dirName)
    {
        $url = $this->BT_PANEL . config("bt.AddDirBinding");
        $p_data['id'] = $id;
        $p_data['domain'] = $domain;
        $p_data['dirName'] = $dirName;
        return $this->http_post($url, $p_data)->result_decode();
    }

    /**
     * 删除网站域名绑定二级目录
     * @param [type] $dirid 子目录ID
     */
    public function DelDirBinding($dirid)
    {
        $url = $this->BT_PANEL . config("bt.DelDirBinding");
        $p_data['id'] = $dirid;
        return $this->http_post($url, $p_data)->result_decode();
    }

    /**
     * 获取网站子目录绑定伪静态信息
     * @param [type] $dirid 子目录绑定ID
     */
    public function GetDirRewrite($dirid, $type = 0)
    {
        $url = $this->BT_PANEL . config("bt.GetDirRewrite");
        $p_data['id'] = $dirid;
        if ($type) {
            $p_data['add'] = 1;
        }
        return $this->http_post($url, $p_data)->result_decode();
    }

    /**
     * 修改FTP账号密码
     * @param [type] $id           FTPID
     * @param [type] $ftp_username 用户名
     * @param [type] $new_password 密码
     */
    public function SetUserPassword($id, $ftp_username, $new_password)
    {
        $url = $this->BT_PANEL . config("bt.SetUserPassword");
        $p_data['id'] = $id;
        $p_data['ftp_username'] = $ftp_username;
        $p_data['new_password'] = $new_password;
        return $this->http_post($url, $p_data)->result_decode();
    }

    /**
     * 修改SQL账号密码
     * @param [type] $id           SQLID
     * @param [type] $ftp_username 用户名
     * @param [type] $new_password 密码
     */
    public function ResDatabasePass($id, $name, $password)
    {
        $url = $this->BT_PANEL . config("bt.ResDatabasePass");
        $p_data['id'] = $id;
        $p_data['name'] = $name;
        $p_data['password'] = $password;
        return $this->http_post($url, $p_data)->result_decode();
    }

    /**
     * 启用/禁用FTP
     * @param [type] $id       FTPID
     * @param [type] $username 用户名
     * @param [type] $status   状态 0->关闭;1->开启
     */
    public function SetStatus($id, $username, $status)
    {
        $url = $this->BT_PANEL . config("bt.SetStatus");
        $p_data['id'] = $id;
        $p_data['username'] = $username;
        $p_data['status'] = $status;
        return $this->http_post($url, $p_data)->result_decode();
    }

    /**
     * 删除FTP账号
     *
     * @param [type] $id            FTP——ID
     * @param [type] $username      FTP用户名
     * @return void
     */
    public function DeleteUser($id, $username)
    {
        $url = $this->BT_PANEL . config("bt.DeleteUser");
        $p_data['id'] = $id;
        $p_data['username'] = $username;
        return $this->http_post($url, $p_data)->result_decode();
    }

    /**
     * 宝塔一键部署列表
     * @param string $search 搜索关键词
     * @return [type]         [description]
     */
    public function deployment($search = '')
    {
        if ($search) {
            $url = $this->BT_PANEL . config("bt.deployment") . '&search=' . $search;
        } else {
            $url = $this->BT_PANEL . config("bt.deployment");
        }
        return $this->http_post($url)->result_decode();
    }

    /**
     * 宝塔一键部署执行
     * @param [type] $dname       部署程序名
     * @param [type] $site_name   部署到网站名
     * @param [type] $php_version PHP版本
     */
    public function SetupPackage($dname, $site_name, $php_version)
    {
        $url = $this->BT_PANEL . config("bt.SetupPackage");
        $p_data['dname'] = $dname;
        $p_data['site_name'] = $site_name;
        $p_data['php_version'] = $php_version;
        return $this->http_post($url, $p_data)->result_decode();
    }

    /**
     * 获取软件管理列表
     * @param [type]  $query 查找
     * @param integer $p     页面
     * @param integer $type  类别
     * @param string  $tojs  soft.get_list
     * @param integer $force 未知
     */
    public function GetSoftList($query = '', $p = 1, $type = 0, $tojs = 'soft.get_list', $force = 0)
    {
        $url = $this->BT_PANEL . config("bt.GetSoftList");
        $p_data['query'] = $query;
        $p_data['p'] = $p;
        $p_data['type'] = $type;
        $p_data['tojs'] = $tojs;
        $p_data['force'] = $force;
        $p_data['type'] = $type;
        return $this->http_post($url, $p_data)->result_decode();
    }

    // 获取防火墙类型
    private function GetProofType()
    {
        return $this->proofType;
    }

    /**
     * 网站防篡改信息
     * 付费插件
     */
    public function GetProof()
    {
        $url = $this->BT_PANEL . config("bt.GetProof") . '&name=' . $this->GetProofType();
        return $this->http_post($url)->result_decode();
    }

    /**
     * 网站防篡改站点设置开关
     * @param [type] $siteName 站点名
     */
    public function SiteProof($siteName)
    {
        $url = $this->BT_PANEL . config("bt.SiteProof") . '&name=' . $this->GetProofType();
        $p_data['siteName'] = $siteName;
        return $this->http_post($url, $p_data)->result_decode();
    }

    /**
     * 网站防篡改站点锁定/解锁
     *
     * @param [type] $siteName      站点名
     * @param integer $lock 状态0:关闭;1:开启
     * @return void
     */
    public function LockProof($siteName, $lock = 1)
    {
        $url = $this->BT_PANEL . config("bt.LockProof") . '&name=' . $this->GetProofType();
        $p_data['siteName'] = $siteName;
        $p_data['lock'] = $lock;
        return $this->http_post($url, $p_data)->result_decode();
    }

    /**
     * 网站防篡改功能总开、关
     * @param [type] $serviceStatus stop、start
     */
    public function ServiceProof($serviceStatus)
    {
        $url = $this->BT_PANEL . config("bt.ServiceProof") . '&name=' . $this->GetProofType();
        $p_data['serviceStatus'] = $serviceStatus;
        return $this->http_post($url, $p_data)->result_decode();
    }

    /**
     * 网站防篡改站点日志
     * @param [type] $siteName 站点名
     */
    public function LogProof($siteName)
    {
        $url = $this->BT_PANEL . config("bt.LogProof") . '&name=' . $this->GetProofType();
        $p_data['siteName'] = $siteName;
        return $this->http_post($url, $p_data)->result_decode();
    }

    /**
     * 网站防篡改规则查看
     * @param [type] $siteName 站点名
     */
    public function GetgzProof($siteName)
    {
        $url = $this->BT_PANEL . config("bt.GetgzProof") . '&name=' . $this->GetProofType();
        $p_data['siteName'] = $siteName;
        return $this->http_post($url, $p_data)->result_decode();
    }

    /**
     * 网站防篡改添加保护
     * @param [type] $siteName   站点名
     * @param [type] $protectExt 保护目录
     */
    public function AddprotectProof($siteName, $protectExt)
    {
        $url = $this->BT_PANEL . config("bt.AddprotectProof") . '&name=' . $this->GetProofType();
        $p_data['siteName'] = $siteName;
        $p_data['protectExt'] = $protectExt;
        return $this->http_post($url, $p_data)->result_decode();
    }

    /**
     * 网站防篡改添加排除
     * @param [type] $siteName    站点名
     * @param [type] $excludePath 排除目录
     */
    public function AddexcloudProof($siteName, $excludePath)
    {
        $url = $this->BT_PANEL . config("bt.AddexcloudProof") . '&name=' . $this->GetProofType();
        $p_data['siteName'] = $siteName;
        $p_data['excludePath'] = $excludePath;
        return $this->http_post($url, $p_data)->result_decode();
    }

    /**
     * 网站防篡改保护删除
     * @param [type] $siteName   站点名
     * @param [type] $protectExt 保护删除的目录名
     */
    public function DelprotectProof($siteName, $protectExt)
    {
        $url = $this->BT_PANEL . config("bt.DelprotectProof") . '&name=' . $this->GetProofType();
        $p_data['siteName'] = $siteName;
        $p_data['protectExt'] = $protectExt;
        return $this->http_post($url, $p_data)->result_decode();
    }

    /**
     * 网站防篡改排除删除
     * @param [type] $siteName    站点名
     * @param [type] $excludePath 排除删除的目录名
     */
    public function DelexcloudProof($siteName, $excludePath)
    {
        $url = $this->BT_PANEL . config("bt.DelexcloudProof") . '&name=' . $this->GetProofType();
        $p_data['siteName'] = $siteName;
        $p_data['excludePath'] = $excludePath;
        return $this->http_post($url, $p_data)->result_decode();
    }

    /**
     * 网站监控报表列表
     */
    public function GetTotal()
    {
        $url = $this->BT_PANEL . config("bt.GetTotal");
        return $this->http_post($url)->result_decode();
    }

    /**
     * 网站监控报表总开、关
     */
    public function StatusTotal()
    {
        $url = $this->BT_PANEL . config("bt.StatusTotal");
        return $this->http_post($url)->result_decode();
    }

    /**
     * 网站监控报表站点开、关
     * @param [type] $siteName 站点名
     * @param [type] $s_key    open
     * @param [type] $s_value  false
     */
    public function SetTotal($siteName, $s_key, $s_value)
    {
        $url = $this->BT_PANEL . config("bt.SetTotal");
        $p_data['siteName'] = $siteName;
        $p_data['s_key'] = $s_key;
        $p_data['s_value'] = $s_value;
        return $this->http_post($url, $p_data)->result_decode();
    }

    /**
     * 网站监控报表站点详情
     * @param [type] $siteName 站点名
     * @param [type] $today    日期 2019-03-08
     */
    public function SiteTotal($siteName, $today)
    {
        $url = $this->BT_PANEL . config("bt.SiteTotal");
        $p_data['siteName'] = $siteName;
        $p_data['today'] = $today;
        return $this->http_post($url, $p_data)->result_decode();
    }

    /**
     * 网站监控报表站点流量统计
     * @param [type] $siteName 站点名
     */
    public function SiteNetworkTotal($siteName)
    {
        $url = $this->BT_PANEL . config("bt.SiteNetworkTotal");
        $p_data['siteName'] = $siteName;
        return $this->http_post($url, $p_data)->result_decode();
    }

    /**
     * 网站监控报表站点蜘蛛统计
     * @param [type] $siteName 站点名
     */
    public function SiteSpiderTotal($siteName)
    {
        $url = $this->BT_PANEL . config("bt.SiteSpiderTotal");
        $p_data['siteName'] = $siteName;
        return $this->http_post($url, $p_data)->result_decode();
    }

    /**
     * 网站监控报表站点错误日志
     * @param [type] $siteName 站点名
     */
    public function SiteErrorLogTotal($siteName)
    {
        $url = $this->BT_PANEL . config("bt.SiteErrorLogTotal");
        $p_data['siteName'] = $siteName;
        return $this->http_post($url, $p_data)->result_decode();
    }

    /**
     * 网站监控报表站点错误统计
     * @param [type]  $siteName  站点名
     * @param [type]  $s_status  状态码
     * @param [type]  $error_log ture
     * @param integer $p 页码
     */
    public function SiteLogTotal($siteName, $s_status, $error_log, $p = 1)
    {
        $url = $this->BT_PANEL . config("bt.SiteLogTotal");
        $p_data['siteName'] = $siteName;
        $p_data['s_status'] = $s_status;
        $p_data['error_log'] = $error_log;
        $p_data['p'] = $p;
        return $this->http_post($url, $p_data)->result_decode();
    }

    /**
     * 网站监控报表站点客户端统计
     * @param [type] $siteName 站点名
     */
    public function Siteclient($siteName)
    {
        $url = $this->BT_PANEL . config("bt.Siteclient");
        $p_data['siteName'] = $siteName;
        return $this->http_post($url, $p_data)->result_decode();
    }

    /**
     * 防火墙首页数据
     * @Author   Youngxj
     * @DateTime 2019-04-18
     * @param    [type]     $wafType waf类型连接字符串
     */
    public function Getwaf($wafType)
    {
        $url = $this->BT_PANEL . config("bt.Getwaf") . '&name=' . $wafType;
        return $this->http_post($url)->result_decode();
    }

    /**
     * Nginx防火墙总开关
     */
    public function Setwaf($wafType)
    {
        $url = $this->BT_PANEL . config("bt.Setwaf") . '&name=' . $wafType;
        return $this->http_post($url)->result_decode();
    }

    /**
     * 防火墙站点信息
     * @param [type] $siteName 站点名
     */
    public function Sitewaf($wafType, $siteName)
    {
        $url = $this->BT_PANEL . config("bt.Sitewaf") . '&name=' . $wafType;
        $p_data['siteName'] = $siteName;
        return $this->http_post($url, $p_data)->result_decode();
    }

    /**
     * 防火墙站点配置、开关
     * @param [type] $siteName 站点名
     * @param string $obj open
     */
    public function SitewafStatus($wafType, $siteName, $obj = 'open')
    {
        $url = $this->BT_PANEL . config("bt.SitewafStatus") . '&name=' . $wafType;
        $p_data['siteName'] = $siteName;
        $p_data['obj'] = $obj;
        return $this->http_post($url, $p_data)->result_decode();
    }

    /**
     * 防火墙站点cc配置
     * @param [type] $siteName 站点名
     * @param [type] $cycle    周期/秒
     * @param [type] $limit    频率/次
     * @param [type] $endtime  封锁时间/秒
     * @param [type] $increase 增强模式/全局 0->关;1->开
     * @param [type] $cc_mode  模式：1->小白模式，2->一般模式，3->自动模式，4->增强模式
     * @param [type] $increase_wu_heng      浏览器验证
     * @param [type] $is_open_global        未知
     * @param [type] $cc_increase_type      增强模式下 验证方式js、code
     *
     */
    public function Setwafcc($wafType, $siteName, $cycle, $limit, $endtime, $increase = 0, $cc_mode = 1, $cc_increase_type = 'js', $increase_wu_heng = 0, $is_open_global = 0)
    {
        $url = $this->BT_PANEL . config("bt.Setwafcc") . '&name=' . $wafType;
        $p_data['siteName'] = $siteName;
        $p_data['cycle'] = $cycle;
        $p_data['limit'] = $limit;
        $p_data['endtime'] = $endtime;
        $p_data['increase'] = $increase;
        $p_data['cc_mode'] = $cc_mode;
        $p_data['is_open_global'] = $is_open_global;
        $p_data['increase_wu_heng'] = $increase_wu_heng;
        $p_data['cc_increase_type'] = $cc_increase_type;
        return $this->http_post($url, $p_data)->result_decode();
    }

    /**
     * 防火墙恶意容忍规则设置
     * @param [type] $siteName    站点名
     * @param [type] $retry       周期/秒
     * @param [type] $retry_time  频率/次
     * @param [type] $retry_cycle 封锁时间/秒
     */
    public function SetwafRetry($wafType, $siteName, $retry, $retry_time, $retry_cycle)
    {
        $url = $this->BT_PANEL . config("bt.SetwafRetry") . '&name=' . $wafType;
        $p_data['siteName'] = $siteName;
        $p_data['retry'] = $retry;
        $p_data['retry_time'] = $retry_time;
        $p_data['retry_cycle'] = $retry_cycle;
        return $this->http_post($url, $p_data)->result_decode();
    }

    /**
     * 防火墙添加国内IP段
     * @param [type] $start_ip 开始段
     * @param [type] $end_ip   结束段
     */
    public function Addwafcnip($wafType, $start_ip, $end_ip)
    {
        $url = $this->BT_PANEL . config("bt.Addwafcnip") . '&name=' . $wafType;
        $p_data['start_ip'] = $start_ip;
        $p_data['end_ip'] = $end_ip;
        return $this->http_post($url, $p_data)->result_decode();
    }

    /**
     * 防火墙获取国内ip段
     * @param [type] $ruleName cn
     */
    public function Getwafcnip($wafType, $ruleName = 'cn')
    {
        $url = $this->BT_PANEL . config("bt.Getwafcnip") . '&name=' . $wafType;
        $p_data['ruleName'] = $ruleName;
        return $this->http_post($url, $p_data)->result_decode();
    }

    /**
     * Cms防护列表
     */
    public function GetwafCms($wafType)
    {
        $url = $this->BT_PANEL . config("bt.GetwafCms") . '&name=' . $wafType;
        return $this->http_post($url)->result_decode();
    }

    /**
     * 防火墙站点日志
     * @param [type] $siteName 站点名
     * @param [type] $toDate   日期2019-03-05
     * @param string $p 翻页
     */
    public function GetwafLog($wafType, $siteName, $toDate, $p = '1')
    {
        $url = $this->BT_PANEL . config("bt.GetwafLog") . '&name=' . $wafType;
        $p_data['siteName'] = $siteName;
        $p_data['toDate'] = $toDate;
        $p_data['p'] = $p;
        return $this->http_post($url, $p_data)->result_decode();
    }

    /**
     * 防火墙总列表拦截数据
     */
    public function SitewafConfig($wafType)
    {
        $url = $this->BT_PANEL . config("bt.SitewafConfig") . '&name=' . $wafType;
        return $this->http_post($url)->result_decode();
    }

    /**
     * 防火墙停用四层防御
     */
    public function SetIPStopStop($wafType)
    {
        $url = $this->BT_PANEL . config("bt.SetIPStopStop") . '&name=' . $wafType;
        return $this->http_post($url)->result_decode();
    }

    /**
     * 防火墙开启四层防御
     */
    public function SetIPStop($wafType)
    {
        $url = $this->BT_PANEL . config("bt.SetIPStop") . '&name=' . $wafType;
        return $this->http_post($url)->result_decode();
    }

    /**
     * 防火墙获取四层防御状态
     */
    public function GetIPStop($wafType)
    {
        $url = $this->BT_PANEL . config("bt.GetIPStop") . '&name=' . $wafType;
        return $this->http_post($url)->result_decode();
    }

    /**
     * 获取网站运行目录
     * @Author   Youngxj
     * @DateTime 2019-04-24
     * @param    [type]     $btid  站点ID
     * @param string $key   path
     * @param string $table sites
     */
    public function WebGetKey($btid, $key = 'path', $table = 'sites')
    {
        $url = $this->BT_PANEL . config("bt.WebGetKey");
        $p_data['id'] = $btid;
        $p_data['key'] = $key;
        $p_data['table'] = $table;
        return $this->http_post($url, $p_data)->result_decode();
    }

    /**
     * 设置网站运行目录
     * @Author   Youngxj
     * @DateTime 2019-07-04
     * @param    [type]     $id   [description]
     * @param    [type]     $path [description]
     */
    public function SetSiteRunPath($id, $path)
    {
        $url = $this->BT_PANEL . config("bt.SetSiteRunPath");
        $p_data['id'] = $id;
        $p_data['runPath'] = $path;
        return $this->http_post($url, $p_data)->result_decode();
    }

    /**
     * 获取文件列表
     *
     * @param [type] $path          网站根目录
     * @param string $p       翻页
     * @param string $search  搜索
     * @param string $sort    排序
     * @param string $reverse 正序，倒叙
     * @param string $all     包含子目录
     * @param string $tojs    默认
     * @param string $showRow 显示文件条数
     * @return array|bool
     */
    public function GetDir($path, $p = '1', $search = '', $all = '', $sort = '', $reverse = '', $tojs = 'GetFiles', $showRow = '200')
    {
        $url = $this->BT_PANEL . config("bt.GetDir") . '&tojs=' . $tojs . '&p=' . $p . '&showRow=' . $showRow;
        if ($search) {
            $p_data['search'] = $search;
        }
        if ($sort) {
            $p_data['sort'] = $sort;
        }
        if ($reverse) {
            $p_data['reverse'] = $reverse;
        }
        if ($all) {
            $p_data['all'] = $all;
        }

        $p_data['path'] = $path;
        return $this->http_post($url, $p_data)->result_decode();
    }

    /**
     * 上传文件
     * @Author   Youngxj
     * @DateTime 2019-05-13
     * @param    [type]     $path    目录
     * @param    [type]     $data    数据
     * @param    [type]     $codeing 编码
     */
    public function UploadFile($path, $data, $codeing = 'byte')
    {

        $url = $this->BT_PANEL . config("bt.UploadFile");
        // $data            = array_merge($data, $this->GetKeyData());
        $data['path'] = $path;
        $data['codeing'] = $codeing;
        return $this->http_post($url, $data)->result_decode();
    }

    /**
     * 文件上传（分片上传）
     * @Author   Youngxj
     * @DateTime 2019-06-01
     * @param    [type]     $path    上传路径
     * @param    [type]     $name    上传文件名
     * @param    [type]     $f_size  文件大小
     * @param    [type]     $f_start 分片开始位置
     * @param    [type]     $blob    文件数据包
     * @param string $m coll_upload
     * @param string $f upload
     */
    public function UploadFiles($path, $name, $f_size, $f_start, $blob, $m = 'coll_upload', $f = 'upload')
    {

        $url = $this->BT_PANEL . config("bt.UploadFiles");
        $data['f_path'] = $path;
        $data['f_name'] = $name;
        $data['f_size'] = $f_size;
        $data['f_start'] = $f_start;
        $data['blob'] = $blob;
        $data['m'] = $m;
        $data['f'] = $f;

        return $this->http_post($url, $data)->result_decode();
    }

    /**
     * 删除文件夹
     * @Author   Youngxj
     * @DateTime 2019-04-24
     * @param    [type]     $path 文件夹路径
     */
    public function DeleteDir($path)
    {
        $url = $this->BT_PANEL . config("bt.DeleteDir");

        $p_data['path'] = $path;
        return $this->http_post($url, $p_data)->result_decode();
    }

    /**
     * 删除文件
     * @Author   Youngxj
     * @DateTime 2019-04-24
     * @param    [type]     $path [description]
     */
    public function DeleteFile($path)
    {
        $url = $this->BT_PANEL . config("bt.DeleteFile");

        $p_data['path'] = $path;
        return $this->http_post($url, $p_data)->result_decode();
    }

    /**
     * 文件重命名/移动
     * @Author   Youngxj
     * @DateTime 2019-04-25
     * @param    [type]     $sfile  文件路径
     * @param    [type]     $dfile  文件路径+重命名
     * @param string $rename 重命名需要带这个参数
     */
    public function MvFile($sfile, $dfile, $rename = 'true')
    {
        $url = $this->BT_PANEL . config("bt.MvFile");

        $p_data['sfile'] = $sfile;
        $p_data['dfile'] = $dfile;
        $p_data['rename'] = $rename;
        return $this->http_post($url, $p_data)->result_decode();
    }

    /**
     * 文件解压
     * @Author   Youngxj
     * @DateTime 2019-04-25
     * @param    [type]     $sfile    文件绝对地址
     * @param    [type]     $dfile    解压路径
     * @param    [type]     $password 密码
     * @param    [type]     $type     压缩包类型
     * @param string $coding 编码
     */
    public function UnZip($sfile, $dfile, $password = '', $type, $coding = 'UTF-8')
    {
        $url = $this->BT_PANEL . config("bt.UnZip");

        $p_data['sfile'] = $sfile;
        $p_data['dfile'] = $dfile;
        $p_data['password'] = $password;
        $p_data['type'] = $type;
        $p_data['coding'] = $coding;
        return $this->http_post($url, $p_data)->result_decode();
    }

    /**
     * 压缩
     * @Author   Youngxj
     * @DateTime 2019-04-25
     * @param    [type]     $sfile  文件名/目录名
     * @param    [type]     $dfile  压缩到路径并命名
     * @param    [type]     $z_type 压缩类型
     * @param    [type]     $path   压缩文件路径
     * @return   [type]             [description]
     */
    public function fileZip($sfile, $dfile, $z_type, $path)
    {
        $url = $this->BT_PANEL . config("bt.fileZip");

        $p_data['sfile'] = $sfile;
        $p_data['dfile'] = $dfile;
        $p_data['z_type'] = $z_type;
        $p_data['path'] = $path;
        return $this->http_post($url, $p_data)->result_decode();
    }

    /**
     * 获取文件权限组及权限
     * @Author   Youngxj
     * @DateTime 2019-04-25
     * @param    [type]     $filename 文件绝对地址
     */
    public function GetFileAccess($filename)
    {
        $url = $this->BT_PANEL . config("bt.GetFileAccess");

        $p_data['filename'] = $filename;
        return $this->http_post($url, $p_data)->result_decode();
    }

    /**
     * 修改权限及权限组
     * @Author   Youngxj
     * @DateTime 2019-04-25
     * @param    [type]     $filename 文件绝对地址
     * @param    [type]     $user     用户组
     * @param    [type]     $access   权限码
     */
    public function SetFileAccess($filename, $user, $access)
    {
        $url = $this->BT_PANEL . config("bt.SetFileAccess");

        $p_data['filename'] = $filename;
        $p_data['user'] = $user;
        $p_data['access'] = $access;
        return $this->http_post($url, $p_data)->result_decode();
    }

    /**
     * 获取文件内容
     * @Author   Youngxj
     * @DateTime 2019-04-25
     * @param    [type]     $path 文件绝对地址
     */
    public function GetFileBodys($path)
    {
        $url = $this->BT_PANEL . config("bt.GetFileBody");

        $p_data['path'] = $path;
        return $this->http_post($url, $p_data)->result_decode();
    }

    /**
     * 文件拷贝
     * @Author   Youngxj
     * @DateTime 2019-04-27
     * @param    [type]     $sfile 文件所在位置绝对地址
     * @param    [type]     $dfile 拷贝绝对地址
     */
    public function CopyFile($sfile, $dfile)
    {
        $url = $this->BT_PANEL . config("bt.CopyFile");

        $p_data['sfile'] = $sfile;
        $p_data['dfile'] = $dfile;
        return $this->http_post($url, $p_data)->result_decode();
    }

    /**
     * 批量剪切(兼删除)
     * @Author   Youngxj
     * @DateTime 2019-05-12
     * @param    [type]     $path 路径
     * @param    [type]     $type 类型：剪切->2
     * @param    [type]     $data 数据["admin","install.php"]
     */
    public function SetBatchData($path, $type, $data)
    {
        $url = $this->BT_PANEL . config("bt.SetBatchData");

        $p_data['path'] = $path;
        $p_data['type'] = $type;
        $p_data['data'] = $data;
        return $this->http_post($url, $p_data)->result_decode();
    }

    /**
     * 批量粘贴
     * @Author   Youngxj
     * @DateTime 2019-05-12
     * @param    [type]     $path 路径
     * @param    [type]     $type 类型：剪切->2;复制->1
     */
    public function BatchPaste($path, $type)
    {
        $url = $this->BT_PANEL . config("bt.BatchPaste");

        $p_data['path'] = $path;
        $p_data['type'] = $type;
        return $this->http_post($url, $p_data)->result_decode();
    }

    /**
     * 宝塔内置下载
     * @Author   Youngxj
     * @DateTime 2019-06-04
     * @param $file
     * @param $filename
     * @return void [type]               [description]
     */
    public function download($file, $filename)
    {
        error_reporting(0);
        $p_data = $this->GetKeyData();
        $url = $this->BT_PANEL . config("bt.download") . $file . '&request_token=' . $p_data['request_token'] . '&request_time=' . $p_data['request_time'];
        $downUrl = $url;
        $file = @fopen($downUrl, "r");
        if (!$file) {
            exit('文件找不到');
        } else {
            Header("Content-type: application/octet-stream");
            Header("Content-Disposition: attachment; filename=" . $filename);
            while (!feof($file)) {
                echo fread($file, 50000);
            }
            fclose($file);
        }
        // header('Content-type: application/save-as');
        // header('Content-Disposition: attachment; filename="' . $filename . '"');
        // @readfile($url);
    }

    /**
     * 获取图片文件base64
     *
     * @param [type] $file
     * @param [type] $filename
     * @return string
     */
    public function images_view($file, $filename)
    {
        error_reporting(0);
        $p_data = $this->GetKeyData();
        $url = $this->BT_PANEL . config("bt.download") . urlencode($file) . '&request_token=' . $p_data['request_token'] . '&request_time=' . $p_data['request_time'];
        $downUrl = $url;
        $base64 = "" . chunk_split(base64_encode(\fast\Http::get($downUrl)));
        // return 'data:' . $imageInfo['mime'] . ';base64,' . chunk_split(base64_encode(file_get_contents($downUrl)));
        return 'data:image/jpeg;base64,' . $base64;
    }

    /**
     * 远程下载文件
     * @Author   Youngxj
     * @DateTime 2019-06-04
     * @param    [type]     $path     存放路径
     * @param    [type]     $urls      远程文件地址
     * @param    [type]     $filename 文件名
     */
    public function DownloadFile($path, $urls, $filename)
    {
        $url = $this->BT_PANEL . config("bt.DownloadFile");

        $p_data['path'] = $path;
        $p_data['url'] = $urls;
        $p_data['filename'] = $filename;
        return $this->http_post($url, $p_data)->result_decode();
    }

    /**
     * 新建文件夹
     * @Author   Youngxj
     * @DateTime 2019-06-01
     * @param    [type]     $path 全路径
     */
    public function CreateDir($path)
    {
        $url = $this->BT_PANEL . config("bt.CreateDir");

        $p_data['path'] = $path;
        return $this->http_post($url, $p_data)->result_decode();
    }

    /**
     * 新建文件
     * @Author   Youngxj
     * @DateTime 2019-06-01
     * @param    [type]     $path 全路径
     */
    public function CreateFile($path)
    {
        $url = $this->BT_PANEL . config("bt.CreateFile");

        $p_data['path'] = $path;
        return $this->http_post($url, $p_data)->result_decode();
    }

    /**
     * 获取宝塔软件详细介绍
     * @Author   Youngxj
     * @DateTime 2019-07-14
     * @param    [type]     $sName 软件名
     */
    public function GetSoftFind($sName)
    {
        $url = $this->BT_PANEL . config("bt.GetSoftFind");

        $p_data['sName'] = $sName;
        return $this->http_post($url, $p_data)->result_decode();
    }

    /**
     * 安装宝塔软件
     * @Author   Youngxj
     * @DateTime 2019-07-14
     * @param    [type]     $sName   软件名
     * @param    [type]     $version 版本号
     * @param integer $type    类型
     * @param integer $upgrade 升级时填1
     */
    public function InstallPlugin($sName, $version = 1, $type = 0, $upgrade = '')
    {
        $url = $this->BT_PANEL . config("bt.InstallPlugin");

        $p_data['sName'] = $sName;
        $p_data['version'] = $version;
        $p_data['type'] = $type;
        if ($upgrade) {
            $p_data['upgrade'] = $upgrade;
        }
        return $this->http_post($url, $p_data)->result_decode();
    }

    /**
     * 卸载宝塔软件
     * @Author   Youngxj
     * @DateTime 2019-07-14
     * @param    [type]     $sName   软件名
     * @param    [type]     $version 版本号
     */
    public function UnInstallPlugin($sName, $version)
    {
        $url = $this->BT_PANEL . config("bt.UnInstallPlugin");

        $p_data['sName'] = $sName;
        $p_data['version'] = $version;
        return $this->http_post($url, $p_data)->result_decode();
    }

    /**
     * 清理Web日志
     * @Author   Youngxj
     * @DateTime 2019-07-14
     * @param string $action CloseLogs
     */
    public function CloseLogs($action = 'CloseLogs')
    {
        $url = $this->BT_PANEL . config("bt.CloseLogs");

        $p_data['action'] = $action;
        return $this->http_post($url, $p_data)->result_decode();
    }

    /**
     * 回收站信息
     * @Author   Youngxj
     * @DateTime 2019-07-18
     * @param string $action [description]
     */
    public function GetRecyclebin($action = 'Get_Recycle_bin')
    {
        $url = $this->BT_PANEL . config("bt.GetRecyclebin");

        $p_data['action'] = $action;
        return $this->http_post($url, $p_data)->result_decode();
    }

    /**
     * 清除回收站文件
     * @Author   Youngxj
     * @DateTime 2019-08-12
     * @param string $action [description]
     */
    public function Close_Recycle_bin($action = 'Close_Recycle_bin')
    {
        $url = $this->BT_PANEL . config("bt.Close_Recycle_bin");

        $p_data['action'] = $action;
        return $this->http_post($url, $p_data)->result_decode();
    }

    /**
     * 获取站点绑定的所有域名
     * @Author   Youngxj
     * @DateTime 2019-09-05
     * @param    [type]     $id [description]
     */
    public function GetSiteDomains($id)
    {
        $url = $this->BT_PANEL . config("bt.GetSiteDomains");

        $p_data['id'] = $id;
        return $this->http_post($url, $p_data)->result_decode();
    }

    /**
     * 申请Let’s证书
     * @Author   Youngxj
     * @DateTime 2019-09-05
     * @param    [type]     $siteName 站点名
     * @param    [type]     $domains  域名["test.yum7.cn","tests.yum7.cn"]
     * @param    [type]     $email    管理员邮箱
     * @param integer $updateOf 1
     * @param string  $force    'true'
     */
    public function CreateLet($siteName, $domains, $email, $updateOf = 1, $force = 'true')
    {
        $url = $this->BT_PANEL . config("bt.CreateLet");

        $p_data['siteName'] = $siteName;
        $p_data['domains'] = $domains;
        $p_data['email'] = $email;
        $p_data['updateOf'] = $updateOf;
        $p_data['force'] = $force;
        return $this->http_post($url, $p_data)->result_decode();
    }

    /**
     * Lets证书续签
     * @Author   Youngxj
     * @DateTime 2019-09-05
     * @param string $action [description]
     */
    public function RenewLets($action = 'renew_lets_ssl')
    {
        $url = $this->BT_PANEL . config("bt.RenewLets");

        $p_data['action'] = $action;
        return $this->http_post($url, $p_data)->result_decode();
    }

    /**
     * 获取指定文件行数内容
     * @Author   Youngxj
     * @DateTime 2019-12-07
     * @param    [type]     $filename 文件全路径
     * @param integer $num 行数
     * @return   [type]               [description]
     */
    public function getFileLog($filename, $num = 10)
    {
        $url = $this->BT_PANEL . config("bt.getFileLog");

        $p_data['filename'] = $filename;
        $p_data['num'] = $num;
        return $this->http_post($url, $p_data)->result_decode();
    }

    /**
     * 一键部署（新版）
     * @Author   Youngxj
     * @DateTime 2019-12-07
     * @param string  $search 搜索
     * @param integer $type   分类id
     */
    public function GetList($search = '', $type = 0)
    {
        $url = $this->BT_PANEL . config("bt.GetList");

        $p_data['type'] = $type;
        if ($search) {
            $p_data['search'] = $search;
        }

        return $this->http_post($url, $p_data)->result_decode();
    }

    /**
     * 宝塔一键部署执行(新版)
     * @param [type] $dname       部署程序名
     * @param [type] $site_name   部署到网站名
     * @param [type] $php_version PHP版本
     */
    public function SetupPackageNew($dname, $site_name, $php_version)
    {
        $url = $this->BT_PANEL . config("bt.SetupPackageNew");
        $p_data['dname'] = $dname;
        $p_data['site_name'] = $site_name;
        $p_data['php_version'] = $php_version;
        return $this->http_post($url, $p_data)->result_decode();
    }

    /**
     * 一键部署（新版）导入项目包
     * @Author   Youngxj
     * @DateTime 2019-12-14
     * @param    [type]     $name             英文名
     * @param    [type]     $title            中文名
     * @param    [type]     $php              php版本：70,71,72
     * @param    [type]     $enable_functions 解禁的函数
     * @param    [type]     $version          项目版本
     * @param    [type]     $ps               简介
     * @param    [type]     $dep_zip          项目包上传name=dep_zip
     */
    public function AddPackage($name, $title, $php, $enable_functions, $version, $ps, $dep_zip)
    {
        $url = $this->BT_PANEL . config("bt.AddPackage");
        $p_data['name'] = $name;
        $p_data['title'] = $title;
        $p_data['php'] = $php;
        $p_data['enable_functions'] = $enable_functions;
        $p_data['version'] = $version;
        $p_data['ps'] = $ps;
        $p_data['dep_zip'] = $dep_zip;
        return $this->http_post($url, $p_data)->result_decode();
    }

    /**
     * 获取站点伪静态规则
     * @Author   Youngxj
     * @DateTime 2019-12-12
     * @param    [type]     $siteName 站点名
     */
    public function GetSiteRewrite($siteName)
    {
        $url = $this->BT_PANEL . config("bt.GetSiteRewrite");
        $p_data['siteName'] = $siteName;
        return $this->http_post($url, $p_data)->result_decode();
    }

    /**
     * 设置站点伪静态规则
     * @Author   Youngxj
     * @DateTime 2019-12-12
     * @param    [type]     $siteName 站点名
     * @param    [type]     $data     伪静态内容
     */
    public function SetSiteRewrite($siteName, $data)
    {
        $url = $this->BT_PANEL . config("bt.SetSiteRewrite");
        $p_data['siteName'] = $siteName;
        $p_data['data'] = $data;
        return $this->http_post($url, $p_data)->result_decode();
    }

    /**
     * 设置防跨域
     * @Author   Youngxj
     * @DateTime 2019-12-12
     * @param    [type]     $path 网站目录
     */
    public function SetDirUserINI($path)
    {
        $url = $this->BT_PANEL . config("bt.SetDirUserINI");
        $p_data['path'] = $path;
        return $this->http_post($url, $p_data)->result_decode();
    }

    /**
     * IIS锁定站点配置文件
     * @Author   Youngxj
     * @DateTime 2019-12-12
     * @param    [type]     $siteName 站点名
     */
    public function SetConfigLocking($siteName)
    {
        $url = $this->BT_PANEL . config("bt.SetConfigLocking");
        $p_data['siteName'] = $siteName;
        return $this->http_post($url, $p_data)->result_decode();
    }

    /**
     * 获取CVE漏洞补丁列表
     * @Author   Youngxj
     * @DateTime 2019-12-13
     */
    public function GetPatch()
    {
        $url = $this->BT_PANEL . config("bt.GetPatch");
        return $this->http_post($url)->result_decode();
    }

    /**
     * 安装CVE漏洞补丁
     * @Author   Youngxj
     * @DateTime 2019-12-13
     * @param    [type]     $url   补丁HTTP地址
     * @param    [type]     $patch 补丁编号
     */
    public function SetPatch($url, $patch)
    {
        $url = $this->BT_PANEL . config("bt.SetPatch");
        $p_data['url'] = $url;
        $p_data['patch'] = $patch;
        return $this->http_post($url, $p_data)->result_decode();
    }

    /**
     * 安装IIS反向代理
     * @Author   Youngxj
     * @DateTime 2019-12-13
     */
    public function SetupIisProxy()
    {
        $url = $this->BT_PANEL . config("bt.SetupIisProxy");
        return $this->http_post($url)->result_decode();
    }

    /**
     * 获取IIS反向代理配置
     * @Author   Youngxj
     * @DateTime 2019-12-13
     */
    public function GetIisProxyConfig()
    {
        $url = $this->BT_PANEL . config("bt.GetIisProxyConfig");
        return $this->http_post($url)->result_decode();
    }

    /**
     * 更改IIS反向代理配置
     * @Author   Youngxj
     * @DateTime 2019-12-13
     * @param    [type]     $data [description]
     */
    public function SetIisProxyConfig($data)
    {
        $url = $this->BT_PANEL . config("bt.SetIisProxyConfig");
        // 反向代理启用状态
        $p_data['enabled'] = $data['enabled'];
        // HTTP协议版本。
        $p_data['httpVersion'] = $data['httpVersion'];
        // 使用HTTP keep-alive
        $p_data['keepAlive'] = $data['keepAlive'];
        // 超时时间(以秒为单位)
        $p_data['timeout'] = $data['timeout'];
        // 重写主机头
        $p_data['reverseRewriteHostInResponseHeaders'] = $data['reverseRewriteHostInResponseHeaders'];
        // 保留客户端IP地址
        $p_data['xForwardedForHeaderName'] = $data['xForwardedForHeaderName'];
        // 保留IP地址中的TCP端口。
        $p_data['includePortInXForwardedFor'] = $data['includePortInXForwardedFor'];
        // 可以缓存内容最小阈值(KB)
        $p_data['minResponseBuffer'] = $data['minResponseBuffer'];
        // 可以缓存内容最大阈值(KB)
        $p_data['maxResponseHeaderSize'] = $data['maxResponseHeaderSize'];
        // 代理服务器，不懂留空
        $p_data['proxy'] = $data['proxy'];
        //  代理服务器密码，不懂留空
        $p_data['proxyBypass'] = $data['proxyBypass'];
        // 开启缓存
        $p_data['cache_enabled'] = $data['cache_enabled'];
        // 缓存时间(以秒为单位，最大86400)
        $p_data['cache_validationInterval'] = $data['cache_validationInterval'];

        return $this->http_post($url, $p_data)->result_decode();
    }

    /**
     * 获取IIS网站防火墙首页数据及状态
     * @Author   Youngxj
     * @DateTime 2019-12-13
     */
    public function WafIis()
    {
        $url = $this->BT_PANEL . config("bt.WafIis");
        return $this->http_post($url)->result_decode();
    }

    /**
     * 打开/关闭IIS防火墙
     * @Author   Youngxj
     * @DateTime 2019-12-13
     */
    public function WafIisSetOpen()
    {
        $url = $this->BT_PANEL . config("bt.WafIisSetOpen");
        return $this->http_post($url)->result_decode();
    }

    /**
     * 获取Waf配置
     * @Author   Youngxj
     * @DateTime 2019-12-13
     */
    public function WafIisGetConfig()
    {
        $url = $this->BT_PANEL . config("bt.WafIisGetConfig");
        return $this->http_post($url)->result_decode();
    }

    /**
     * 站点配置列表
     * @Author   Youngxj
     * @DateTime 2019-12-13
     */
    public function WafIisSiteConfig()
    {
        $url = $this->BT_PANEL . config("bt.WafIisSiteConfig");
        return $this->http_post($url)->result_decode();
    }

    /**
     * 获取站点防火墙日志
     * @Author   Youngxj
     * @DateTime 2019-12-13
     * @param    [type]     $siteName 站点名
     */
    public function WafIisGetLog($siteName)
    {
        $url = $this->BT_PANEL . config("bt.WafIisGetLog");
        $p_data['siteName'] = $siteName;
        return $this->http_post($url, $p_data)->result_decode();
    }

    /**
     * 设置站点防火墙几项开/关
     * @Author   Youngxj
     * @DateTime 2019-12-13
     * @param    [type]     $obj      开关类型,总开关=open
     * @param    [type]     $siteName 站点名
     */
    public function WafIisSetSiteOpen($obj, $siteName)
    {
        $url = $this->BT_PANEL . config("bt.WafIisSetSiteOpen");
        $p_data['obj'] = $obj;
        $p_data['siteName'] = $siteName;
        return $this->http_post($url, $p_data)->result_decode();
    }

    /**
     * 获取站点防火墙独立配置
     * @Author   Youngxj
     * @DateTime 2019-12-13
     * @param    [type]     $siteName 站点名
     */
    public function WafIisSetSiteConfig($siteName)
    {
        $url = $this->BT_PANEL . config("bt.WafIisSetSiteConfig");
        $p_data['siteName'] = $siteName;
        return $this->http_post($url, $p_data)->result_decode();
    }

    /**
     * 编辑网站规则
     * @Author   Youngxj
     * @DateTime 2019-12-13
     * @param    [type]     $ruleName 规则类型
     * @param    [type]     $siteName 站点名
     * @return   [type]               [description]
     */
    public function get_site_disable_rule($ruleName, $siteName)
    {
        $url = $this->BT_PANEL . config("bt.get_site_disable_rule");
        $p_data['siteName'] = $siteName;
        $p_data['ruleName'] = $ruleName;
        return $this->http_post($url, $p_data)->result_decode();
    }

    /**
     * 获取一键迁移状态
     * @Author   Youngxj
     * @DateTime 2019-12-14
     * @return   [type]     [description]
     */
    public function get_speed()
    {
        $url = $this->BT_PANEL . config("bt.get_speed");
        return $this->http_post($url)->result_decode();
    }

    /**
     * 获取配置
     * @Author   Youngxj
     * @DateTime 2019-12-14
     * @return   [type]     [description]
     */
    public function get_panel_api()
    {
        $url = $this->BT_PANEL . config("bt.get_panel_api");
        return $this->http_post($url)->result_decode();
    }

    /**
     * 保存接口信息
     * @Author   Youngxj
     * @DateTime 2019-12-14
     * @param    [type]     $api_info json格式的配置信息
     * {"panel":"http://127.0.0.1:8888","token":"xxxxxxxxxxxxxxxxxxxxxxxxxxxx"}
     */
    public function set_panel_api($api_info)
    {
        $url = $this->BT_PANEL . config("bt.set_panel_api");
        $p_data['api_info'] = $api_info;
        return $this->http_post($url, $p_data)->result_decode();
    }

    /**
     * 对比配置
     * @Author   Youngxj
     * @DateTime 2019-12-14
     * @param    [type]     $panel     API地址
     * @param    [type]     $api_token 密钥
     * @return   [type]                [description]
     */
    public function chekc_surroundings($panel, $api_token)
    {
        $url = $this->BT_PANEL . config("bt.chekc_surroundings");
        $p_data['panel'] = $panel;
        $p_data['api_token'] = $api_token;
        return $this->http_post($url, $p_data)->result_decode();
    }

    /**
     * 对比配置
     * @Author   Youngxj
     * @DateTime 2019-12-14
     * @param    [type]     $panel     API地址
     * @param    [type]     $api_token 密钥
     * @return   [type]                [description]
     */
    public function get_site_info($panel, $api_token)
    {
        $url = $this->BT_PANEL . config("bt.get_site_info");
        $p_data['panel'] = $panel;
        $p_data['api_token'] = $api_token;
        return $this->http_post($url, $p_data)->result_decode();
    }

    /**
     * 配置需要同步的数据
     * @Author   Youngxj
     * @DateTime 2019-12-14
     * @param    [type]     $sync_info json格式
     */
    public function set_sync_info($sync_info)
    {
        $url = $this->BT_PANEL . config("bt.set_sync_info");
        $p_data['sync_info'] = $sync_info;
        return $this->http_post($url, $p_data)->result_decode();
    }

    /**
     * 获取迁移记录
     * @Author   Youngxj
     * @DateTime 2019-12-14
     * @return   [type]                [description]
     */
    public function get_sync_info()
    {
        $url = $this->BT_PANEL . config("bt.get_sync_info");
        return $this->http_post($url)->result_decode();
    }

    /**
     * 获取日志信息
     * @Author   Youngxj
     * @DateTime 2019-12-14
     * @return   [type]     [description]
     */
    public function return_log()
    {
        $url = $this->BT_PANEL . config("bt.return_log");
        return $this->http_post($url)->result_decode();
    }

    /**
     * 清除日志
     * @Author   Youngxj
     * @DateTime 2019-12-14
     * @param    [type]     $data json格式
     * @return   [type]           [description]
     */
    public function log_remove_file($data)
    {
        $url = $this->BT_PANEL . config("bt.log_remove_file");
        $p_data['data'] = $data;
        return $this->http_post($url, $p_data)->result_decode();
    }

    /**
     * 清除完成度
     * @Author   Youngxj
     * @DateTime 2019-12-14
     * @return   [type]     [description]
     */
    public function log_status()
    {
        $url = $this->BT_PANEL . config("bt.log_status");
        return $this->http_post($url)->result_decode();
    }

    /**
     * 获取配置备份
     * @Author   Youngxj
     * @DateTime 2019-12-14
     * @return   [type]     [description]
     */
    public function get_config_back()
    {
        $url = $this->BT_PANEL . config("bt.get_config_back");
        return $this->http_post($url)->result_decode();
    }

    /**
     * 创建配置备份
     * @Author   Youngxj
     * @DateTime 2019-12-14
     */
    public function set_config_back()
    {
        $url = $this->BT_PANEL . config("bt.set_config_back");
        return $this->http_post($url)->result_decode();
    }

    /**
     * 还原配置备份
     * @Author   Youngxj
     * @DateTime 2019-12-14
     * @param    [type]     $path 配置文件名
     * @param    [type]     $type 类型默认local
     * @return   [type]           [description]
     */
    public function import_config_back($path, $type = 'local')
    {
        $url = $this->BT_PANEL . config("bt.import_config_back");
        $p_data['path'] = $path;
        $p_data['type'] = $type;
        return $this->http_post($url, $p_data)->result_decode();
    }

    /**
     * 本地配置备份调用
     * 需要提前上传备份文件到/www/server/panel/backup/Disposable目录才能调用
     * @Author   Youngxj
     * @DateTime 2019-12-14
     * @param string $type [description]
     */
    public function Decompression($type = 'decompress')
    {
        $url = $this->BT_PANEL . config("bt.Decompression");
        $p_data['type'] = $type;
        return $this->http_post($url, $p_data)->result_decode();
    }

    /**
     * 删除备份的配置文件
     * @Author   Youngxj
     * @DateTime 2019-12-14
     * @param    [type]     $filename [description]
     * @return   [type]               [description]
     */
    public function del_config_back($filename)
    {
        $url = $this->BT_PANEL . config("bt.del_config_back");
        $p_data['filename'] = $filename;
        return $this->http_post($url, $p_data)->result_decode();
    }

    /**
     * 端口扫描
     * @Author   Youngxj
     * @DateTime 2019-12-14
     * @param    [type]     $dk 端口
     * @param string $ip IP（IPv4）
     * @return   [type]         [description]
     */
    public function port_blast($dk, $ip = '127.0.0.1')
    {
        $url = $this->BT_PANEL . config("bt.port_blast");
        $p_data['dk'] = $dk;
        $p_data['ip'] = $ip;
        return $this->http_post($url, $p_data)->result_decode();
    }

    /**
     * 获取host配置列表
     * @Author   Youngxj
     * @DateTime 2019-12-14
     * @return   [type]     [description]
     */
    public function get_host_config()
    {
        $url = $this->BT_PANEL . config("bt.get_host_config");
        return $this->http_post($url)->result_decode();
    }

    /**
     * 新增host
     * @Author   Youngxj
     * @DateTime 2019-12-14
     * @param    [type]     $domain 域名
     * @param    [type]     $ip     IP
     */
    public function add_host_config($domain, $ip)
    {
        $url = $this->BT_PANEL . config("bt.add_host_config");
        $p_data['domain'] = $domain;
        $p_data['ip'] = $ip;
        return $this->http_post($url, $p_data)->result_decode();
    }

    /**
     * 删除host配置
     * @Author   Youngxj
     * @DateTime 2019-12-14
     * @param    [type]     $domain 域名
     * @return   [type]             [description]
     */
    public function del_host_config($domain)
    {
        $url = $this->BT_PANEL . config("bt.del_host_config");
        $p_data['domain'] = $domain;
        return $this->http_post($url, $p_data)->result_decode();
    }

    /**
     * 修改host
     * @Author   Youngxj
     * @DateTime 2019-12-14
     * @param    [type]     $olddomain 老域名
     * @param    [type]     $newdomain 新域名
     * @param    [type]     $ip        IP
     * @return   [type]                [description]
     */
    public function edit_host_config($olddomain, $newdomain, $ip)
    {
        $url = $this->BT_PANEL . config("bt.edit_host_config");
        $p_data['olddomain'] = $olddomain;
        $p_data['newdomain'] = $newdomain;
        $p_data['ip'] = $ip;
        return $this->http_post($url, $p_data)->result_decode();
    }

    /**
     * 404公益
     * @Author   Youngxj
     * @DateTime 2019-12-14
     * @param    [type]     $site [description]
     * @return   [type]           [description]
     */
    public function pw404_site_list()
    {
        $url = $this->BT_PANEL . config("bt.pw404_site_list");
        return $this->http_post($url)->result_decode();
    }

    /**
     * 获取当前域名的公益情况
     * @Author   Youngxj
     * @DateTime 2019-12-14
     * @param    [type]     $site all等于全部，传递域名
     * @return   [type]           [description]
     */
    public function pw404_site_info($site = 'all')
    {
        $url = $this->BT_PANEL . config("bt.pw404_site_info");
        $p_data['site'] = $site;
        return $this->http_post($url, $p_data)->result_decode();
    }

    /**
     * 安装公益
     * @Author   Youngxj
     * @DateTime 2019-12-14
     * @param    [type]     $site    站点名
     * @param    [type]     $demourl 公益demo地址
     * @param    [type]     $demoid  公益ID（需要从指定api中获取）
     * @return   [type]              [description]
     */
    public function pw404_site_install($site, $demourl, $demoid)
    {
        $url = $this->BT_PANEL . config("bt.pw404_site_install");
        $p_data['site'] = $site;
        $p_data['demourl'] = $demourl;
        $p_data['demoid'] = $demoid;
        return $this->http_post($url, $p_data)->result_decode();
    }

    /**
     * 卸载公益
     * @Author   Youngxj
     * @DateTime 2019-12-14
     * @param    [type]     $site 站点名
     * @return   [type]           [description]
     */
    public function pw404_site_uninstall($site)
    {
        $url = $this->BT_PANEL . config("bt.pw404_site_uninstall");
        $p_data['site'] = $site;
        return $this->http_post($url, $p_data)->result_decode();
    }

    /**
     * 目录保护
     *
     * @param [type] $id 宝塔ID
     * @return void
     */
    public function get_dir_auth($id)
    {
        $url = $this->BT_PANEL . config("bt.get_dir_auth");
        $p_data['id'] = $id;
        return $this->http_post($url, $p_data)->result_decode();
    }

    /**
     * 添加目录保护
     *
     * @param [type] $id    宝塔ID
     * @param [type] $name  名称
     * @param [type] $site_dir  目录
     * @param [type] $username  账号
     * @param [type] $password  密码
     * @return void
     */
    public function set_dir_auth($id, $name, $site_dir, $username, $password)
    {
        $url = $this->BT_PANEL . config("bt.set_dir_auth");
        $p_data['id'] = $id;
        $p_data['name'] = $name;
        $p_data['site_dir'] = $site_dir;
        $p_data['username'] = $username;
        $p_data['password'] = $password;

        return $this->http_post($url, $p_data)->result_decode();
    }

    /**
     * 修改目录保护
     *
     * @param [type] $id    宝塔ID
     * @param [type] $name  名称
     * @param [type] $username  账号
     * @param [type] $password  密码
     * @return void
     */
    public function modify_dir_auth_pass($id, $name, $username, $password)
    {
        $url = $this->BT_PANEL . config("bt.modify_dir_auth_pass");
        $p_data['id'] = $id;
        $p_data['name'] = $name;
        $p_data['username'] = $username;
        $p_data['password'] = $password;

        return $this->http_post($url, $p_data)->result_decode();
    }

    /**
     * 删除目录保护
     *
     * @param [type] $id    宝塔ID
     * @param [type] $name  名称
     * @return void
     */
    public function delete_dir_auth($id, $name)
    {
        $url = $this->BT_PANEL . config("bt.delete_dir_auth");
        $p_data['id'] = $id;
        $p_data['name'] = $name;

        return $this->http_post($url, $p_data)->result_decode();
    }

    /**
     * 添加vsftpd用户
     * {"status": "Success", "msg": "\u521b\u5efa\u7528\u6237\u6210\u529f...", "userid": "f903655", "diskstatus": "virtual-disk"}
     * @param [type] $username  用户名
     * @param [type] $password  密码
     * @param [type] $homepath  路径
     * @param [type] $powerlevel    download_upload_mkdir_other
     * @param [type] $speed     限速KB/S 0不限制
     * @param [type] $disksize  容量 mb 0不限制
     * @return void
     */
    public function AddVsftpdUser($username, $password, $homepath, $disksize = 0, $speed = 0, $powerlevel = 'download_upload_mkdir_other')
    {
        $url = $this->BT_PANEL . config("bt.AddVsftpdUser");
        $p_data['username'] = $username;
        $p_data['password'] = $password;
        $p_data['homepath'] = $homepath;
        $p_data['powerlevel'] = $powerlevel;
        $p_data['speed'] = $speed;
        $p_data['disksize'] = $disksize;

        return $this->http_post($url, $p_data)->result_decode();
    }

    /**
     * 获取vsftpd全局运行状态
     * {"status": "Success", "portport": "20", "usernum": 2, "pavsport": "39000-40000", "CoreVersion": "3.0.2-25.el7", "SystemOS": "CentOS  7.2.1511(Py2.7.5)", "ServerStatus": "live", "loginmes_status": "on", "speed": 0, "controlport": "21", "loginmes_content": "Welcome To Login BT-FTP SERVER,This Server is Build in vsftpd 3.0.3 with bt-vsftpd plugin", "logsize": "0.00 b", "LocalIp": "139.9.222.32", "PluginVersion": "1.1.3-BETA"}
     *
     * @return void
     */
    public function GetTotalData()
    {
        $url = $this->BT_PANEL . config("bt.GetTotalData");
        return $this->http_post($url)->result_decode();
    }

    /**
     * 配置信息
     * {"status": "Success", "loginmes": "on", "controlport": "21", "pavsport": "39000-40000", "loginmes_content": "Welcome To Login BT-FTP SERVER,This Server is Build in vsftpd 3.0.3 with bt-vsftpd plugin", "listenmode": "ipv4", "speed": 0}
     *
     * @return void
     */
    public function GetGlobalData()
    {
        $url = $this->BT_PANEL . config("bt.GetGlobalData");
        return $this->http_post($url)->result_decode();
    }

    /**
     * 获取vsftpd用户列表
     * {"status": "Success", "list": {"621d373": {"username": "test", "diskuse": "0.0 MB", "userid": "621d373", "powerlevel": "download_upload_mkdir_other", "disksize": 0, "diskstatus": "local-disk", "homepath": "/www/wwwroot/test", "password": "kolLDhzxAAQFKoyU", "speed": 0}, "f903655": {"username": "tests", "diskuse": "0.0 MB / 100.0 MB (0.0%)", "userid": "f903655", "powerlevel": "download_upload", "disksize": 100, "diskstatus": "virtual-disk", "homepath": "/www/wwwroot/tests", "password": "lSZch4G2RlY6a69n", "speed": 100}}, "RequestId": "e040aec0a555c508e5e2a1e8a213b354"}
     *
     * @return void
     */
    public function GetVsftpdUserList()
    {
        $url = $this->BT_PANEL . config("bt.GetVsftpdUserList");
        return $this->http_post($url)->result_decode();
    }

    /**
     * 获取运行日志
     * {"status": "Success", "LogText": "\u65e5\u5fd7\u6587\u4ef6\u4e0d\u5b58\u5728"}
     *
     * @return void
     */
    public function GetLogText()
    {
        $url = $this->BT_PANEL . config("bt.GetLogText");
        return $this->http_post($url)->result_decode();
    }

    /**
     * 清空vsftpd日志
     *
     * @return void
     */
    public function DelVsftpdLog()
    {
        $url = $this->BT_PANEL . config("bt.DelVsftpdLog");
        return $this->http_post($url)->result_decode();
    }

    /**
     * Nginx免费防火墙 - webshell查杀
     *
     * @param [type] $path  路径
     * @return void
     */
    public function free_waf_san_dir($path)
    {
        $url = $this->BT_PANEL . config("bt.free_waf_san_dir");
        $p_data['path'] = $path;
        return $this->http_post($url, $p_data)->result_decode();
    }

    /**
     * Nginx免费防火墙 - 指定功能开关
     * {"status": true, "msg": "\u8bbe\u7f6e\u6210\u529f!"}
     * @param [type] $siteName  站点名
     * @param string $obj open/get/post/user-agent/cc/cdn/drop_abroad（禁国外）
     * @return void
     */
    public function free_waf_set_site_obj_open($siteName, $obj = 'open')
    {
        $url = $this->BT_PANEL . config("bt.free_waf_san_dir");
        $p_data['siteName'] = $siteName;
        $p_data['obj'] = $obj;
        return $this->http_post($url, $p_data)->result_decode();
    }

    /**
     * Nginx免费防火墙 - 查看日志
     *
     * @param [type] $siteName  站点名
     * @return void
     */
    public function free_waf_get_logs_list($siteName)
    {
        $url = $this->BT_PANEL . config("bt.free_waf_get_logs_list");
        $p_data['siteName'] = $siteName;
        return $this->http_post($url, $p_data)->result_decode();
    }

    /**
     * Nginx免费防火墙 - cc设置
     * {"status": true, "msg": "\u8bbe\u7f6e\u6210\u529f!"}
     * @param [type] $siteName  站点名
     * @param [type] $cycle     周期/秒
     * @param [type] $limit     频率/次
     * @param [type] $endtime   封锁时间/秒
     * @param [type] $is_open_global
     * @param [type] $increase
     * @return void
     */
    public function free_waf_set_site_cc_conf($siteName, $cycle, $limit, $endtime, $is_open_global = 0, $increase = 0)
    {
        $url = $this->BT_PANEL . config("bt.free_waf_set_site_cc_conf");
        $p_data['siteName'] = $siteName;
        $p_data['cycle'] = $cycle;
        $p_data['limit'] = $limit;
        $p_data['endtime'] = $endtime;
        $p_data['is_open_global'] = $is_open_global;
        $p_data['increase'] = $increase;
        return $this->http_post($url, $p_data)->result_decode();
    }

    /**
     * Nginx免费防火墙 - 数据统计
     * {"safe_day": 0, "drop_ip": [], "total": {"rules": [{"value": 0, "name": "POST\u6e17\u900f", "key": "post"}, {"value": 0, "name": "GET\u6e17\u900f", "key": "get"}, {"value": 0, "name": "CC\u653b\u51fb", "key": "cc"}, {"value": 0, "name": "\u6076\u610fUser-Agent", "key": "user_agent"}, {"value": 0, "name": "Cookie\u6e17\u900f", "key": "cookie"}, {"value": 0, "name": "\u6076\u610f\u626b\u63cf", "key": "scan"}, {"value": 0, "name": "\u6076\u610fHEAD\u8bf7\u6c42", "key": "head"}, {"value": 0, "name": "URI\u81ea\u5b9a\u4e49\u62e6\u622a", "key": "url_rule"}, {"value": 0, "name": "URI\u4fdd\u62a4", "key": "url_tell"}, {"value": 0, "name": "\u6076\u610f\u6587\u4ef6\u4e0a\u4f20", "key": "disable_upload_ext"}, {"value": 0, "name": "\u7981\u6b62\u7684\u6269\u5c55\u540d", "key": "disable_ext"}, {"value": 0, "name": "\u7981\u6b62PHP\u811a\u672c", "key": "disable_php_path"}], "total": 0}, "open": true}
     * @return void
     */
    public function free_waf_total()
    {
        $url = $this->BT_PANEL . config("bt.free_waf_total");
        return $this->http_post($url)->result_decode();
    }

    /**
     * Nginx免费防火墙 - 总开关
     *
     * @return void
     */
    public function free_waf_set_open()
    {
        $url = $this->BT_PANEL . config("bt.free_waf_set_open");
        return $this->http_post($url)->result_decode();
    }

    /**
     * 站点列表、配置、统计
     *
     * @return array
     */
    public function free_waf_site_config()
    {
        $url = $this->BT_PANEL . config("bt.free_waf_site_config");
        return $this->http_post($url)->result_decode();
    }

    /**
     * 文件查杀
     *
     * @param [type] $filename  文件全路径
     * @return array|bool
     */
    public function webshellCheck($filename)
    {
        $url = $this->BT_PANEL . config("bt.webshellCheck");
        $p_data['filename'] = $filename;
        return $this->http_post($url, $p_data)->result_decode();
    }

    /**
     * 面板操作日志
     *
     * @param integer $limit
     * @param integer $p
     * @return void
     */
    public function getPanelLogs($limit = 10, $p = 1)
    {
        $url = $this->BT_PANEL . config("bt.getData");
        $p_data['tojs'] = 'firewall.get_log_list';
        $p_data['table'] = 'logs';
        $p_data['limit'] = $limit;
        $p_data['p'] = $p;
        $p_data['search'] = '';
        $p_data['order'] = 'id desc';
        return $this->http_post($url, $p_data)->result_decode();
    }

    /**
     * 获取web日志大小
     *
     * @return void
     */
    public function GetDirSize()
    {
        $url = $this->BT_PANEL . config("bt.GetDirSize");
        $p_data['path'] = '/www/wwwlogs';
        return $this->http_post($url, $p_data)->result_decode();
    }

    /**
     * Mysql工具箱 - 修复
     *
     * @param [type] $db_name       数据库名
     * @param array $tables 表：["admin"]
     * @return void
     */
    public function ReTable($db_name, $tables = [])
    {
        $url = $this->BT_PANEL . config("bt.ReTable");
        $p_data['db_name'] = $db_name;
        $p_data['tables'] = $tables;
        return $this->http_post($url, $p_data)->result_decode();
    }

    /**
     * Mysql工具箱 - 优化
     *
     * @param [type] $db_name       数据库名
     * @param array $tables 表：["admin"]
     * @return void
     */
    public function OpTable($db_name, $tables = [])
    {
        $url = $this->BT_PANEL . config("bt.OpTable");
        $p_data['db_name'] = $db_name;
        $p_data['tables'] = $tables;
        return $this->http_post($url, $p_data)->result_decode();
    }

    /**
     * Mysql工具箱 - 类型转换
     *
     * @param [type] $db_name       数据库名
     * @param array  $tables     表：["admin"]
     * @param string $table_type InnoDB/MyISAM
     * @return void
     */
    public function AlTable($db_name, $tables = [], $table_type)
    {
        $url = $this->BT_PANEL . config("bt.AlTable");
        $p_data['db_name'] = $db_name;
        $p_data['tables'] = $tables;
        $p_data['table_type'] = $table_type;
        return $this->http_post($url, $p_data)->result_decode();
    }

    /**
     * 获取session隔离状态
     *
     * @param [type] $id        站点ID
     * @return void
     */
    public function get_php_session_path($id)
    {
        $url = $this->BT_PANEL . config("bt.get_php_session_path");
        $p_data['id'] = $id;
        $data = $this->http_post($url, $p_data)->result_decode();
        if ($data == true) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * 设置session隔离状态
     *
     * @param [type] $id        站点ID
     * @param [type] $act       状态:1=开启,0=关闭
     * @return void
     */
    public function set_php_session_path($id, $act = 1)
    {
        $url = $this->BT_PANEL . config("bt.set_php_session_path");
        $p_data['id'] = $id;
        $p_data['act'] = $act;
        $data = $this->http_post($url, $p_data)->result_decode();
        if (isset($data['status']) && $data['status'] == true) {
            return true;
        }
        $this->_error = $data['msg'] ?? __('Fail');
        return false;
    }

    /**
     * 添加计划任务
     *
     * @param array $params
     * name=名称,sType=类型,type=周期,urladdress=url地址
     * @return void
     */
    public function AddCrontab($params = [])
    {
        $url = $this->BT_PANEL . config("bt.AddCrontab");
        $p_data['name'] = isset($params['name']) ? $params['name'] : '';
        $p_data['type'] = isset($params['type']) ? $params['type'] : '';
        $p_data['where1'] = isset($params['where1']) ? $params['where1'] : '';
        $p_data['hour'] = isset($params['hour']) ? $params['hour'] : '';
        $p_data['minute'] = isset($params['minute']) ? $params['minute'] : '';
        $p_data['week'] = isset($params['week']) ? $params['week'] : '';
        $p_data['sType'] = isset($params['sType']) ? $params['sType'] : '';
        $p_data['sBody'] = isset($params['sBody']) ? $params['sBody'] : 'undefined';
        $p_data['sName'] = isset($params['sName']) ? $params['sName'] : '';
        $p_data['backupTo'] = isset($params['backupTo']) ? $params['backupTo'] : 'localhost';
        $p_data['save'] = isset($params['save']) ? $params['save'] : '';
        $p_data['urladdress'] = isset($params['urladdress']) ? $params['urladdress'] : '';
        $data = $this->http_post($url, $p_data)->result_decode();
        if ($data && isset($data['status']) && $data['status'] == true) {
            return $data;
        }
        $this->_error = $data['msg'] ?? __('Fail');
        return false;
    }

    /**
     * 计划任务列表
     *
     * @return array
     */
    public function GetCrontab()
    {
        $url = $this->BT_PANEL . config("bt.GetCrontab");
        $data = $this->http_post($url)->result_decode();
        if ($data && !isset($data['msg'])) {
            return $data;
        }
        $this->_error = $data['msg'] ?? __('Fail');
        return false;
    }
    
    /**
     * 执行任务
     * @param $id   任务ID
     * @return bool
     * @date 2021/6/13
     */
    public function StartTask($id)
    {
        $result = $this->request_get('StartTask', ['id' => $id]);
        if ($result && isset($result['status']) && $result['status'] == true) {
            return true;
        }
        $this->_error = $result['msg'] ?? __('Fail');
        return false;
    }

    /**
     * 获取计划任务执行日志
     *
     * @param [type] $id        任务ID
     * @return void
     */
    public function GetLogs($id)
    {
        $url = $this->BT_PANEL . config("bt.GetLogs");
        $p_data['id'] = $id;
        $data = $this->http_post($url, $p_data)->result_decode();
        if ($data && isset($data['status']) && $data['status'] == true) {
            return isset($data['msg']) ? $data['msg'] : '';
        }
        $this->_error = $data['msg'] ?? __('Fail');
        return false;
    }

    /**
     * 删除计划任务执行日志
     *
     * @param [type] $id        任务ID
     * @return void
     */
    public function DelLogs($id)
    {
        $url = $this->BT_PANEL . config("bt.DelLogs");
        $p_data['id'] = $id;
        $data = $this->http_post($url, $p_data)->result_decode();
        if ($data && isset($data['status']) && $data['status'] == true) {
            return isset($data['msg']) ? $data['msg'] : '';
        }
        $this->_error = $data['msg'] ?? __('Fail');
        return false;
    }

    /**
     * 删除计划任务
     *
     * @param [type] $id        任务ID
     * @return void
     */
    public function DelCrontab($id)
    {
        $url = $this->BT_PANEL . config("bt.DelCrontab");
        $p_data['id'] = $id;
        $data = $this->http_post($url, $p_data)->result_decode();
        if ($data && isset($data['status']) && $data['status'] == true) {
            return true;
        }
        $this->_error = $data['msg'] ?? __('Fail');
        return false;
    }

    /**
     * 商用证书申请列表
     *
     * @return array|bool
     * {"data": [{"pid": 8001, "code": "comodo-positivessl", "num": 1, "title": "PositiveSSL  SSL证书", "price": 78.66, "discount": 0.23, "state": 1, "ps": "", "src_price": 342}, {"pid": 8002, "code": "comodo-positive-multi-domain", "num": 3, "title": "PositiveSSL 多域名SSL证书", "price": 378.88, "discount": 0.37, "state": 1, "ps": "", "src_price": 1024}, {"pid": 8008, "code": "comodo-positivessl-wildcard", "num": 1, "title": "PositiveSSL 通配符SSL证书", "price": 696.8, "discount": 0.4, "state": 1, "ps": "", "src_price": 1742}, {"pid": 8009, "code": "comodo-positive-multi-domain-wildcard", "num": 2, "title": "PositiveSSL 多域名通配符SSL证书", "price": 1257.8, "discount": 0.38, "state": 1, "ps": "", "src_price": 3310}], "administrator": {"job": "总务", "city": "", "email": "", "state": "", "mobile": "13800138000", "address": "", "country": "CN", "lastName": "", "firstName": "", "organation": "", "postCode": "523000"}}
     */
    public function GetProductList()
    {
        $url = $this->BT_PANEL . config("bt.GetProductList");
        return $this->http_post($url)->result_decode();
    }

    /**
     * 商用证书下单
     *
     * @param [type] $array 申请信息
     * @return void
     * {"status": true, "msg": {"wxcode": "weixin://wxpay/bizpayurl?pr=aOI0e3uzz", "alicode": "https://qr.alipay.com/bax03666ir8yise92pri20e6", "oid": 800001784}}
     */
    public function ApplyOrderPay($array)
    {
        $url = $this->BT_PANEL . config("bt.ApplyOrderPay");
        $p_data['pdata'] = json_encode($array);
        $result = $this->http_post($url, $p_data)->result_decode();
        if ($result && isset($result['status']) && $result['status'] == true) {
            return $result;
        }
        $this->_error = $result['msg'] ?? __('Fail');
        return false;
    }

    /**
     * 文件查重
     * @param $dfile string   目录
     * @return false|mixed|array
     *               [{"filename": "about-rtl.css", "size": 27151, "mtime": "1605648844"}, {"filename": "php", "size": 0, "mtime": "1610427550"}]
     */
    public function CheckExistsFiles($dfile)
    {
        $url = $this->BT_PANEL . config("bt.CheckExistsFiles");
        $p_data['dfile'] = $dfile;
        $result = $this->http_post($url, $p_data)->result_decode();
        if ($result) {
            return $result;
        }
        $this->_error = $result['msg'] ?? __('Fail');
        return false;
    }

    /**
     * 堡塔网站加速列表
     *
     * @return array|bool
     */
    public function SiteSpeed()
    {
        $result = $this->request_get('SiteSpeed');
        if ($result && isset($result['data'])) {
            return $result['data'];
        }
        $this->_error = $result['msg'] ?? __('Fail');
        return false;
    }

    /**
     * 站点加速开关
     *
     * @param [type] $siteName  站点名
     * @return array|bool
     */
    public function SiteSpeedStatus($siteName)
    {
        $result = $this->request_get('SiteSpeedStatus', ['siteName' => $siteName]);
        if ($result && isset($result['status']) && $result['status'] == true) {
            return true;
        }
        $this->_error = $result['msg'] ?? __('Fail');
        return false;
    }

    /**
     * 站点加速总开关
     * @return bool
     * @date 2021/6/13
     */
    public function GetSiteSpeedSettings()
    {
        $result = $this->request_get('GetSiteSpeedSettings');
        if ($result && isset($result['open']) && $result['open'] == true) {
            return true;
        }
        $this->_error = $result['msg'] ?? __('Fail');
        return false;
    }

    /**
     * 获取站点加速站点信息
     *
     * @param [type] $siteName  站点名
     * @return array|bool
     */
    public function GetSiteSpeed($siteName)
    {
        $result = $this->request_get('GetSiteSpeed', ['siteName' => $siteName]);
        if ($result && isset($result['open'])) {
            return $result;
        }
        $this->_error = $result['msg'] ?? __('Fail');
        return false;
    }

    /**
     * 配置缓存目录(疑似需要先执行移除之前的规则才能正常使用)
     *
     * @param [type] $siteName  站点名
     * @param [type] $rules     缓存目录  {"white":{"not_uri":["/"]}}
     * @return void
     */
    public function CreateSpeedRule($siteName, $path)
    {
        $rules = json_encode(['not_uri' => $path]);
        $result = $this->request_get('CreateSpeedRule', ['siteName' => $siteName, 'rules' => $rules]);
        if ($result && isset($result['status']) && $result['status'] == true) {
            return true;
        }
        $this->_error = $result['msg'] ?? __('Fail');
        return false;
    }

    /**
     * 设置加速规则
     *
     * @param [type] $siteName      站点名
     * @param [type] $ruleName      规则名
     * @return void
     */
    public function SetSpeedRule($siteName, $ruleName)
    {
        $result = $this->request_get('SetSpeedRule', ['siteName' => $siteName, 'ruleName' => $ruleName]);
        if ($result && isset($result['status']) && $result['status'] == true) {
            return true;
        }
        $this->_error = $result['msg'] ?? __('Fail');
        return false;
    }

    /**
     * 添加缓存规则
     *
     * @param [type] $siteName      站点名
     * @param [type] $ruleKey       规则方式host:域名,ip:IP,args:请求参数,ext:后缀名,type:响应类型,uri:URL地址
     * @param [type] $ruleValue     规则内容
     * @param string $ruleRoot 不缓存:ruleRoot,缓存:force
     * @return void
     */
    public function AddSpeedRule($siteName, $ruleKey, $ruleValue, $ruleRoot = 'force')
    {
        $result = $this->request_get('AddSpeedRule', ['siteName' => $siteName, 'ruleKey' => $ruleKey, 'ruleValue' => $ruleValue, 'ruleRoot' => $ruleRoot]);
        if ($result && isset($result['status']) && $result['status'] == true) {
            return true;
        }
        $this->_error = $result['msg'] ?? __('Fail');
        return false;
    }

    // 删除缓存规则
    public function DelSpeedRule($siteName, $ruleKey, $ruleValue, $ruleRoot = 'force')
    {
        $result = $this->request_get('DelSpeedRule', ['siteName' => $siteName, 'ruleKey' => $ruleKey, 'ruleValue' => $ruleValue, 'ruleRoot' => $ruleRoot]);
        if ($result && isset($result['status']) && $result['status'] == true) {
            return true;
        }
        $this->_error = $result['msg'] ?? __('Fail');
        return false;
    }

    /**
     * 获取内置加速规则列表
     *
     * @return array|bool
     */
    public function GetRuleList()
    {
        $result = $this->request_get('GetRuleList');
        if ($result && isset($result['rule_list'])) {
            return $result['rule_list'];
        }
        $this->_error = $result['msg'] ?? __('Fail');
        return false;
    }

    /**
     * 构造带有签名的关联数组
     * @return array
     * Author: Youngxj
     * Date: 2019/9/21 15:19
     */
    public function GetKeyData()
    {
        $now_time = time();
        return array(
            'request_token' => md5($now_time . '' . md5($this->BT_KEY)),
            'request_time'  => $now_time,
        );
    }

    /**
     * 发起POST请求
     * @param String       $url  目标网填，带http://
     * @param Array|String $data 欲提交的数据
     * @return string
     */
    private function HttpPostCookie($url, $data = [], $timeout = 120)
    {
        // 拼接api验证信息
        $data = array_merge($data, $this->GetKeyData());

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        $output = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        if ($httpCode != 200) {
            Config::set('log.type', 'File');
            Log::write('API请求失败:' . $url . '请求参数:' . json_encode($data), 'error');
        }
        return $output;
    }

    /**
     * http_post
     * @param       $url
     * @param array $data
     * @param int   $timeout
     * @return $this|Btpanel
     * @date 2021/6/13
     */
    private function http_post($url, $data = [], $timeout = 120)
    {
        $this->result = $this->HttpPostCookie($url, $data, $timeout);
        return $this;
    }

    /**
     * json解码
     * @return mixed|array
     * @date 2021/6/13
     */
    private function result_decode()
    {
        return json_decode($this->result, true);
    }

}

<?php
// +----------------------------------------------------------------------
// | 宝塔接口配置文件 By Bty5
// +----------------------------------------------------------------------
// | Copyright (c) 2019-2020 rights reserved.
// +----------------------------------------------------------------------
// | Author: Youngxj
// +----------------------------------------------------------------------
// | Date: 2019/9/21 15:22
// +----------------------------------------------------------------------

return [
    # 系统状态相关接口
    'GetConfig'                  => '/config?action=get_config', //获取服务器配置
    'getConcifInfo'              => '/system?action=GetConcifInfo',//获取服务器配置（完整）
    'IsPro'                      => '/config?action=is_pro', //是否为专业版
    'GetSystemTotal'             => '/system?action=GetSystemTotal', //获取系统基础统计
    'GetDiskInfo'                => '/system?action=GetDiskInfo', //获取磁盘分区信息
    'GetNetWork'                 => '/system?action=GetNetWork', //获取实时状态信息(CPU、内存、网络、负载)
    'RepPanel'                   => '/system?action=RepPanel', //面板修复
    'ReWeb'                      => '/system?action=ReWeb', //重启面板
    'ServiceAdmin'               => '/system?action=ServiceAdmin', //系统服务
    'RestartServer'              => '/system?action=RestartServer', //重启服务器
    'GetTaskCount'               => '/ajax?action=GetTaskCount', //检查是否有安装任务
    'UpdatePanel'                => '/ajax?action=UpdatePanel', //检查面板更新
    'AutoUpdatePanel'            => '/config?action=AutoUpdatePanel', //设置面板自动更新
    'CloseLogs'                  => '/files?action=CloseLogs', //清理Web日志
    'GetRecyclebin'              => '/files?action=Get_Recycle_bin', //回收站
    'Close_Recycle_bin'          => '/files?action=Close_Recycle_bin', //清除回收站
    'GetWebSize'                 => '/files?action=get_path_size', //计算站点及文件夹大小
    'get_task_lists'             => '/task?action=get_task_lists', //执行计划任务
    'remove_task'                => '/task?action=remove_task', //移除任务
    'GetSqlSize'                 => '/database?action=GetInfo', //计算数据库大小
    'getData'                    => '/data?action=getData', //查询数据库数据
    'GetDirSize'                 => '/files?action=GetDirSize', // 面板日志大小
    'AddCrontab'                 => '/crontab?action=AddCrontab', // 添加计划任务
    'DelCrontab'                 => '/crontab?action=DelCrontab', // 删除计划任务
    'GetCrontab'                 => '/crontab?action=GetCrontab', // 计划任务列表
    'GetLogs'                    => '/crontab?action=GetLogs',// 获取计划任务执行日志
    'DelLogs'                    => '/crontab?action=DelLogs',// 删除计划任务执行日志
    'StartTask'                  => '/crontab?action=StartTask',// 执行计划任务
    'CheckInstalled'             => '/ajax?action=CheckInstalled', //检查是否完成程序初始化['nginx','apache','php','pure-ftpd','mysql']

    # 网站管理相关接口
    'Websites'                   => '/data?action=getData&table=sites', //获取网站列表
    'Websitess'                  => '/data?action=getData', //获取网站下的域名列表
    'Webtypes'                   => '/site?action=get_site_types', //获取网站分类
    'add_site_type'              => '/site?action=add_site_type', //添加网站分类
    'set_site_type'              => '/site?action=set_site_type', //设置网站分类
    'edit_site_type'             => '/site?action=modify_site_type_name', //修改网站分类
    'delete_site_type'           => '/site?action=remove_site_type', //删除网站分类
    'GetPHPVersion'              => '/site?action=GetPHPVersion', //获取已安装的 PHP 版本列表
    'GetSitePHPVersion'          => '/site?action=GetSitePHPVersion', //获取指定网站运行的PHP版本
    'SetPHPVersion'              => '/site?action=SetPHPVersion', //修改指定网站的PHP版本
    'SetHasPwd'                  => '/site?action=SetHasPwd', //开启并设置网站密码访问
    'CloseHasPwd'                => '/site?action=CloseHasPwd', //关闭网站密码访问
    'GetDirUserINI'              => '/site?action=GetDirUserINI', //获取网站几项开关（防跨站、日志、密码访问、运行目录）
    'SetSiteRunPath'             => '/site?action=SetSiteRunPath', // 设置网站运行目录
    'WebAddSite'                 => '/site?action=AddSite', //创建网站
    'WebDeleteSite'              => '/site?action=DeleteSite', //删除网站
    'WebSiteStop'                => '/site?action=SiteStop', //停用网站
    'WebSiteStart'               => '/site?action=SiteStart', //启用网站
    'WebSetEdate'                => '/site?action=SetEdate', //设置网站有效期
    'WebSetPs'                   => '/data?action=setPs&table=sites', //修改网站备注
    'WebGetKey'                  => '/data?action=getKey', //获取网站目录
    'WebBackupList'              => '/data?action=getData&table=backup', //获取网站备份列表
    'WebToBackup'                => '/site?action=ToBackup', //创建网站备份
    'WebDelBackup'               => '/site?action=DelBackup', //删除网站备份
    'WebDoaminList'              => '/data?action=getData&table=domain', //获取网站域名列表
    'GetDirBinding'              => '/site?action=GetDirBinding', //获取网站域名绑定二级目录信息
    'AddDirBinding'              => '/site?action=AddDirBinding', //添加网站子目录域名
    'DelDirBinding'              => '/site?action=DelDirBinding', //删除网站绑定子目录
    'GetDirRewrite'              => '/site?action=GetDirRewrite', //获取网站子目录伪静态规则
    'WebAddDomain'               => '/site?action=AddDomain', //添加网站域名
    'WebDelDomain'               => '/site?action=DelDomain', //删除网站域名
    'GetSiteLogs'                => '/site?action=GetSiteLogs', //获取网站日志
    'GetSecurity'                => '/site?action=GetSecurity', //获取网站盗链状态及规则信息
    'SetSecurity'                => '/site?action=SetSecurity', //设置网站盗链状态及规则信息
    'GetSSL'                     => '/site?action=GetSSL', //获取SSL状态及证书详情
    'HttpToHttps'                => '/site?action=HttpToHttps', //强制HTTPS
    'CloseToHttps'               => '/site?action=CloseToHttps', //关闭强制HTTPS
    'SetSSL'                     => '/site?action=SetSSL', //设置SSL证书
    'CloseSSLConf'               => '/site?action=CloseSSLConf', //关闭SSL
    'GetDVSSL'                   => '/ssl?action=GetDVSSL', //申请ssl证书 TrustAsia 域名型SSL证书(D3)
    'Completed'                  => '/ssl?action=Completed', //部署前效验ssl证书
    'GetSSLInfo'                 => '/ssl?action=GetSSLInfo', //获取申请的ssl证书信息
    'GetProductList'             => '/ssl?action=get_product_list', //商用证书列表
    'ApplyOrderPay'              => '/ssl?action=apply_order_pay', //商用证书订单提交
    'GetSiteDomains'             => '/site?action=GetSiteDomains', // 获取站点绑定的所有域名
    'CreateLet'                  => '/site?action=CreateLet', // 批量申请Lets证书
    'RenewLets'                  => '/ssl?action=renew_lets_ssl', // Lets证书续签
    'getFileLog'                 => '/ajax?action=get_lines', //获取指定文件内容
    'WebGetIndex'                => '/site?action=GetIndex', //获取网站默认文件
    'WebSetIndex'                => '/site?action=SetIndex', //设置网站默认文件
    'GetLimitNet'                => '/site?action=GetLimitNet', //获取网站流量限制信息
    'SetLimitNet'                => '/site?action=SetLimitNet', //设置网站流量限制信息
    'CloseLimitNet'              => '/site?action=CloseLimitNet', //关闭网站流量限制
    'Get301Status'               => '/site?action=Get301Status', //获取网站301重定向信息
    'Set301Status'               => '/site?action=Set301Status', //设置网站301重定向信息
    'GetRedirectList'            => '/site?action=GetRedirectList', //重定向（内测版）列表
    'DeleteRedirect'             => '/site?action=DeleteRedirect', //删除重定向
    'CreateRedirect'             => '/site?action=CreateRedirect', //添加重定向
    'ModifyRedirect'             => '/site?action=ModifyRedirect', //修改重定向
    'GetRewriteList'             => '/site?action=GetRewriteList', //获取可选的预定义伪静态列表
    'GetFileBody'                => '/files?action=GetFileBody', //获取指定预定义伪静态规则内容(获取文件内容)
    'SaveFileBody'               => '/files?action=SaveFileBody', //保存伪静态规则内容(保存文件内容)
    'GetProxyList'               => '/site?action=GetProxyList', //获取网站反代信息及状态
    'CreateProxy'                => '/site?action=CreateProxy', //添加网站反代信息
    'ModifyProxy'                => '/site?action=ModifyProxy', //修改网站反代信息
    'RemoveProxy'                => '/site?action=RemoveProxy', //删除网站反代
    'get_dir_auth'               => '/site?action=get_dir_auth', // 目录保护
    'set_dir_auth'               => '/site?action=set_dir_auth', // 添加目录保护
    'modify_dir_auth_pass'       => '/site?action=modify_dir_auth_pass', // 修改目录保护
    'delete_dir_auth'            => '/site?action=delete_dir_auth', // 删除目录保护
    'get_php_session_path'       => '/config?action=get_php_session_path', // 获取session隔离状态
    'set_php_session_path'       => '/config?action=set_php_session_path', // 设置session隔离状态

    # Ftp管理
    'WebFtpList'                 => '/data?action=getData&table=ftps', //获取FTP信息列表
    'SetUserPassword'            => '/ftp?action=SetUserPassword', //修改FTP账号密码
    'SetStatus'                  => '/ftp?action=SetStatus', //启用/禁用FTP
    'DeleteUser'                 => '/ftp?action=DeleteUser', //删除FTP

    # Sql管理
    'WebSqlList'                 => '/data?action=getData&table=databases', //获取SQL信息列表
    'ResDatabasePass'            => '/database?action=ResDatabasePassword', //修改SQL账号密码
    'SQLToBackup'                => '/database?action=ToBackup', //创建sql备份
    'SQLDelBackup'               => '/database?action=DelBackup', //删除sql备份
    'InputSql'                   => '/database?action=InputSql', //备份恢复 数据库导入
    'AddDatabase'                => '/database?action=AddDatabase', //创建数据库
    'DeleteDatabase'             => '/database?action=DeleteDatabase', //删除数据库

    # Mysql工具箱
    'ReTable'                    => '/database?action=ReTable', //修复
    'OpTable'                    => '/database?action=OpTable', //优化
    'AlTable'                    => '/database?action=AlTable', //转换数据库类型

    # 文件操作
    'UploadFile'                 => '/files?action=UploadFile', //上传文件
    'UploadFiles'                => '/files?action=upload', //上传文件（分片上传）
    'download'                   => '/download?filename=', //下载备份文件
    'GetDir'                     => '/files?action=GetDir', //文件浏览器
    'DeleteDir'                  => '/files?action=DeleteDir', //删除文件夹
    'DeleteFile'                 => '/files?action=DeleteFile', //删除文件
    'CheckExistsFiles'           => '/files?action=CheckExistsFiles', //效验文件
    'MvFile'                     => '/files?action=MvFile', //文件移动/重命名
    'UnZip'                      => '/files?action=UnZip', //解压
    'fileZip'                    => '/files?action=Zip', //压缩
    'CopyFile'                   => '/files?action=CopyFile', //复制文件
    'SetBatchData'               => '/files?action=SetBatchData', //批量剪切文件(兼删除)
    'BatchPaste'                 => '/files?action=BatchPaste', //批量粘贴剪切文件
    'GetFileAccess'              => '/files?action=GetFileAccess', //获取文件权限组及权限
    'SetFileAccess'              => '/files?action=SetFileAccess', //修改文件权限组及权限
    'CreateDir'                  => '/files?action=CreateDir', //新建文件夹
    'CreateFile'                 => '/files?action=CreateFile', //新建文件
    'DownloadFile'               => '/files?action=DownloadFile', //远程下载文件
    'webshellCheck'              => '/files?action=file_webshell_check', //文件查杀

    # 插件管理
    'deployment'                 => '/plugin?action=a&name=deployment&s=GetList&type=0', //宝塔一键部署列表
    'SetupPackage'               => '/plugin?action=a&name=deployment&s=SetupPackage', //部署任务
    'GetSoftList'                => '/plugin?action=get_soft_list', //获取宝塔软件列表
    'GetSoftFind'                => '/plugin?action=get_soft_find', //获取软件详细介绍
    'InstallPlugin'              => '/plugin?action=install_plugin', // 安装宝塔软件
    'UnInstallPlugin'            => '/plugin?action=uninstall_plugin', // 卸载宝塔软件

    # 付费插件
    //网站防篡改
    'GetProof'                   => '/plugin?action=a&s=get_index', //信息
    'SiteProof'                  => '/plugin?action=a&s=set_site_status', //站点设置
    'ServiceProof'               => '/plugin?action=a&s=service_admin', //功能总开、关
    'LogProof'                   => '/plugin?action=a&s=get_safe_logs', //站点日志
    'GetgzProof'                 => '/plugin?action=a&s=get_site_find', //规则查看
    'AddprotectProof'            => '/plugin?action=a&s=add_protect_ext', //添加保护
    'AddexcloudProof'            => '/plugin?action=a&s=add_excloud', //添加排除
    'DelprotectProof'            => '/plugin?action=a&s=remove_protect_ext', //保护删除
    'DelexcloudProof'            => '/plugin?action=a&s=remove_excloud', //排除删除
    'LockProof'                  => '/plugin?action=a&s=SetLockFile', //站点锁定（专业版防篡改专属

    //网站监控报表
    'GetTotal'                   => '/plugin?action=a&name=total&s=get_sites', //列表
    'StatusTotal'                => '/plugin?action=a&name=total&s=set_status', //总开、关
    'SetTotal'                   => '/plugin?action=a&name=total&s=set_site_value', //站点开、关
    'SiteTotal'                  => '/plugin?action=a&name=total&s=get_total_bysite', //站点详情
    'SiteNetworkTotal'           => '/plugin?action=a&name=total&s=get_site_network_all', //站点流量统计
    'SiteSpiderTotal'            => '/plugin?action=a&name=total&s=get_site_total_byspider', //站点蜘蛛统计
    'SiteErrorLogTotal'          => '/plugin?action=a&name=total&s=get_site_error_logs', //站点错误日志
    'SiteLogTotal'               => '/plugin?action=a&name=total&s=get_site_log_byday', //站点错误统计
    'Siteclient'                 => '/plugin?action=a&name=total&s=get_site_total_byclient', //站点客户端统计

    // Nginx/Apache/IIS防火墙
    'Getwaf'                     => '/plugin?action=a&s=get_total_all', //首页数据
    'Setwaf'                     => '/plugin?action=a&s=set_open', //总开关
    'Sitewaf'                    => '/plugin?action=a&s=get_site_config_byname', //站点信息
    'SitewafStatus'              => '/plugin?action=a&s=set_site_obj_open', //站点配置、开关
    'Setwafcc'                   => '/plugin?action=a&s=set_site_cc_conf', //站点cc配置信息
    'SetwafRetry'                => '/plugin?action=a&s=set_site_retry', //恶意容忍规则设置
    'Addwafcnip'                 => '/plugin?action=a&s=add_cnip', //添加国内IP段
    'Getwafcnip'                 => '/plugin?action=a&s=get_rule', //获取国内ip段
    'GetwafCms'                  => '/plugin?action=a&s=get_site_cms', //Cms防护列表
    'GetwafLog'                  => '/plugin?action=a&s=get_safe_logs', //获取日志
    'SitewafConfig'              => '/plugin?action=a&s=get_site_config', //总列表拦截数据
    'SetIPStopStop'              => '/plugin?action=a&s=set_stop_ip_stop', //关闭四层防御
    'SetIPStop'                  => '/plugin?action=a&s=set_stop_ip', //开启四层防御
    'GetIPStop'                  => '/plugin?action=a&s=get_stop_ip', //获取四层防御状态

    // Vsftpd
    'GetTotalData'               => '/plugin?action=a&s=PageLoad&name=bt_vsftpd', // 获取全局运行状态
    'GetGlobalData'              => '/plugin?action=a&s=GetGlobalData&name=bt_vsftpd', // 配置信息
    'GetVsftpdUserList'          => '/plugin?action=a&s=Users_List&name=bt_vsftpd', // 获取用户列表
    'GetConfigText'              => '/plugin?action=a&s=GetConfigText&name=bt_vsftpd', // 配置文件内容
    'GetLogText'                 => '/plugin?action=a&s=GetLogText&name=bt_vsftpd', // 获取运行日志
    'AddVsftpdUser'              => '/plugin?action=a&s=Users_AddUser&name=bt_vsftpd', // 添加用户
    'DelVsftpdLog'               => '/plugin?action=a&s=Service_LogClean&name=bt_vsftpd', // 清空日志

    // 端口扫描器
    'port_blast'                 => '/plugin?action=a&s=portblast&name=portblast', // 端口扫描

    #免费扩展
    // 一键部署（新版）
    'GetList'                    => '/deployment?action=GetList', // 一键部署（新版）
    'SetupPackageNew'            => '/deployment?action=SetupPackage', // 部署任务（新版）
    'AddPackage'                 => '/deployment?action=AddPackage', // 手动导入项目包

    // 一键迁移API版
    'get_speed'                  => '/plugin?action=a&name=psync_api&s=get_speed', // 获取一键迁移状态
    'get_panel_api'              => '/plugin?action=a&name=psync_api&s=get_panel_api', // 获取保存的对接配置信息
    'set_panel_api'              => '/plugin?action=a&name=psync_api&s=set_panel_api', // 设置对接配置
    'chekc_surroundings'         => '/plugin?action=a&name=psync_api&s=chekc_surroundings', // 检查环境
    'get_site_info'              => '/plugin?action=a&name=psync_api&s=get_site_info', // 获取站点/FTP/数据库信息
    'set_sync_info'              => '/plugin?action=a&name=psync_api&s=set_sync_info', // 设置需要对接的内容
    'get_sync_info'              => '/plugin?action=a&name=psync_api&s=get_sync_info', // 获取迁移记录

    // 日志清理工具
    'return_log'                 => '/plugin?action=a&name=clear&s=RetuenLog', // 获取日志信息
    'log_remove_file'            => '/plugin?action=a&name=clear&s=remove_file', // 清除日志
    'log_status'                 => '/plugin?action=a&name=clear&s=GetToStatus', // 清除日志

    // 宝塔配置备份
    'get_config_back'            => '/plugin?action=a&name=backup&s=GetBuckup', // 获取宝塔配置备份
    'set_config_back'            => '/plugin?action=a&name=backup&s=Backup_all', // 创建宝塔配置备份
    'import_config_back'         => '/plugin?action=a&name=backup&s=LocalImport', // 备份还原
    'Decompression'              => '/plugin?action=a&name=backup&s=Decompression', // 本地文件还原
    'del_config_back'            => '/plugin?action=a&name=backup&s=DelFile', // 删除备份文件

    // host修改工具
    'get_host_config'            => '/ehost/GetHostConfig.json', // host列表
    'add_host_config'            => '/ehost/AddDomainConfig.json', // 添加host
    'del_host_config'            => '/ehost/DelDomainConfig.json', // 删除host
    'edit_host_config'           => '/ehost/EditDomainConfig.json', // 修改host

    // 404公益
    'pw404_site_list'            => '/plugin?action=a&s=site_list&name=publicwelfare404', // 404公益专属域名列表
    'pw404_site_info'            => '/plugin?action=a&s=site_info&name=publicwelfare404', // 获取公益状态
    'pw404_site_install'         => '/plugin?action=a&s=site_install&name=publicwelfare404', // 安装公益
    'pw404_site_uninstall'       => '/plugin?action=a&s=site_uninstall&name=publicwelfare404', // 卸载公益

    // Nginx免费防火墙
    'free_waf_total'             => '/plugin?action=a&name=free_waf&s=get_total_all', // 数据统计
    'free_waf_config'            => '/plugin?action=a&name=free_waf&s=get_config', // 配置信息
    'free_waf_site_config'       => '/plugin?action=a&name=free_waf&s=get_site_config', // 站点列表、配置、统计
    'free_waf_safe_logs'         => '/plugin?action=a&name=free_waf&s=get_safe_logs', // 封锁历史
    'free_waf_san_dir'           => '/plugin?action=a&name=free_waf&s=san_dir', // webshell查杀
    'free_waf_set_site_obj_open' => '/plugin?action=a&name=free_waf&s=set_site_obj_open', // 指定功能开关
    'free_waf_get_logs_list'     => '/plugin?action=a&name=free_waf&s=get_logs_list', // 查看日志
    'free_waf_set_site_cc_conf'  => '/plugin?action=a&name=free_waf&s=set_site_cc_conf', // cc配置
    'free_waf_set_open'          => '/plugin?action=a&name=free_waf&s=set_open', // 总开关

    // 宝塔网站加速
    'SiteSpeed'                  => '/plugin?action=a&s=get_site_list&name=site_speed', // 宝塔网站加速列表
    'SiteSpeedStatus'            => '/plugin?action=a&s=set_site_status&name=site_speed',// 站点加速开关
    'GetSiteSpeed'               => '/plugin?action=a&s=get_site_find&name=site_speed',// 站点加速站点详情
    'CreateSpeedRule'            => '/plugin?action=a&s=create_rule&name=site_speed',// 设置缓存目录
    'SetSpeedRule'               => '/plugin?action=a&s=set_site_rule&name=site_speed',// 设置规则
    'GetRuleList'                => '/plugin?action=a&s=get_rule_list&name=site_speed',// 加速规则列表
    'AddSpeedRule'               => '/plugin?action=a&s=add_site_rule&name=site_speed',// 添加缓存规则
    'DelSpeedRule'               => '/plugin?action=a&s=remove_site_rule&name=site_speed',// 删除缓存规则
    'GetSiteSpeedSettings'       => '/plugin?action=a&s=get_settings&name=site_speed',// 获取堡塔网站加速配置


    # windows
    'GetSiteRewrite'             => '/site?action=GetSiteRewrite', // 获取站点伪静态规则
    'SetSiteRewrite'             => '/site?action=SetSiteRewrite', // 设置站点伪静态规则
    'SetDirUserINI'              => '/site?action=SetDirUserINI', // 设置防跨域
    'SetConfigLocking'           => '/site?action=set_config_locking', //iis锁定站点配置文件

    'GetPatch' => '/plugin?action=a&name=btpatch&s=get_list', // CVE漏洞补丁列表
    'SetPatch' => '/plugin?action=a&name=btpatch&s=setup_patch', // 安装CVE漏洞补丁

    'SetupIisProxy'     => '/ajax?action=setup_iis_proxy', // 安装IIS反向代理
    'GetIisProxyConfig' => '/ajax?action=get_iis_proxy_config', // 获取反向代理配置
    'SetIisProxyConfig' => '/ajax?action=set_iis_proxy_config', // 修改反向代理配置

    'WafIis'                => '/plugin?action=a&name=waf_iis&s=get_total_all', //获取IIS网站防火墙首页数据及状态
    'WafIisSetOpen'         => '/plugin?action=a&name=waf_iis&s=set_open', // 打开/关闭IIS防火墙
    'WafIisGetConfig'       => '/plugin?action=a&name=waf_iis&s=get_config', // 获取配置
    'WafIisSiteConfig'      => '/plugin?action=a&name=waf_iis&s=get_site_config', // 站点配置列表
    'WafIisGetLog'          => '/plugin?action=a&name=waf_iis&s=get_logs_list', // 获取站点防火墙日志
    'WafIisSetSiteOpen'     => '/plugin?action=a&name=waf_iis&s=set_site_obj_open', // 设置站点防火墙几项开关
    'WafIisSetSiteConfig'   => '/plugin?action=a&name=waf_iis&s=get_site_config_byname', // 获取站点独立配置
    'get_site_disable_rule' => '/plugin?action=a&name=waf_iis&s=get_site_disable_rule', // 编辑网站规则

];

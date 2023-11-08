<?php

// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006~2016 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------

return [
    //别名配置,别名只能是映射到控制器且访问时必须加上请求的方法
    '__alias__'        => [
    ],
    //变量规则
    '__pattern__'      => [
    ],
    '[api]'            => [
        // 'api/index'=>'api/vhost/index',
    ],
    '[user]'           => [
        // 'index'=>'index/user/index',
        // 'login'=>'index/user/login',
        // 'logout'=>'index/user/logout',
    ],
    'index'            => 'index/vhost/index',
    'sites'            => 'index/user/index',
    'login'            => 'index/user/login',
    'logout'           => 'index/user/logout',
    'main'             => 'index/vhost/main',
    'domain'           => 'index/vhost/domain',
    'hostreset'        => 'index/vhost/hostreset',
    'speed_cache'      => 'index/vhost/speed_cache',
    'speed_cache_list' => 'index/vhost/speed_cache_list',
    'pass'             => 'index/vhost/pass',
    'speed'            => 'index/vhost/speed',
    'Rewrite301'       => 'index/vhost/Rewrite301',
    'redir'            => 'index/vhost/redir',
    'defaultfile'      => 'index/vhost/defaultfile',
    'rewrite'          => 'index/vhost/rewrite',
    'ssl'              => 'index/vhost/ssl',
    'runPath'          => 'index/vhost/runPath',
    'file'             => 'index/vhost/file',
    'file_ftp'         => 'index/vhost/file_ftp',
    'back'             => 'index/vhost/back',
    'protection'       => 'index/vhost/protection',
    'waf'              => 'index/vhost/waf',
    'dirauth'          => 'index/vhost/dirauth',
    'proof'            => 'index/vhost/proof',
    'sitelog'          => 'index/vhost/sitelog',
    'sqltools'         => 'index/vhost/sqltools',
    'httpauth'         => 'index/vhost/httpauth',
    'deployment'       => 'index/vhost/deployment',
    'deployment_new'   => 'index/vhost/deployment_new',
    'total'            => 'index/vhost/total',
    'proxy'            => 'index/vhost/proxy',
    'tasks'            => 'index/vhost/tasks',
    '/'                => 'index/vhost/index',
    '[vhost]'          => [
        'index'          => 'index/vhost/index',
        'main'           => 'index/vhost/main',
        'domain'         => 'index/vhost/domain',
        'pass'           => 'index/vhost/pass',
        'speed'          => 'index/vhost/speed',
        'Rewrite301'     => 'index/vhost/Rewrite301',
        'redir'          => 'index/vhost/redir',
        'defaultfile'    => 'index/vhost/defaultfile',
        'rewrite'        => 'index/vhost/rewrite',
        'ssl'            => 'index/vhost/ssl',
        'runPath'        => 'index/vhost/runPath',
        'file'           => 'index/vhost/file',
        'file_ftp'       => 'index/vhost/file_ftp',
        'back'           => 'index/vhost/back',
        'protection'     => 'index/vhost/protection',
        'waf'            => 'index/vhost/waf',
        'dirauth'        => 'index/vhost/dirauth',
        'proof'          => 'index/vhost/proof',
        'sitelog'        => 'index/vhost/sitelog',
        'sqltools'       => 'index/vhost/sqltools',
        'httpauth'       => 'index/vhost/httpauth',
        'deployment'     => 'index/vhost/deployment',
        'deployment_new' => 'index/vhost/deployment_new',
        'total'          => 'index/vhost/total',
        'proxy'          => 'index/vhost/proxy',
        'tasks'          => 'index/vhost/tasks',
    ],
    //        域名绑定到模块
    //        '__domain__'  => [
    //            'admin' => 'admin',
    //            'api'   => 'api',
    //        ],
];
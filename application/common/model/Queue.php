<?php

namespace app\common\model;

use think\Model;

/**
 * 计划任务
 */
class Queue extends Model
{

    // 开启自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';
    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';

    public static function getTypeList()
    {
        return [
            'email'          => __('邮件'),
            'btresource'     => __('宝塔资源'),
            'server'         => __('服务器到期'),
            'host'           => __('主机到期'),
            'customize'      => __('其他产品到期'),
        ];
    }
}
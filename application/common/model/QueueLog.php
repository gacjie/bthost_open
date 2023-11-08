<?php

namespace app\common\model;

use think\Model;

/**
 * 计划任务日志
 */
class QueueLog extends Model
{

    // 开启自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';
    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = '';
}
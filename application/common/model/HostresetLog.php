<?php

namespace app\common\model;

use think\Model;

class HostresetLog extends Model
{
    // 表名
    protected $name = 'hostreset_log';

    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';

    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';
}

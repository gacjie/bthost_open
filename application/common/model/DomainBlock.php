<?php

namespace app\common\model;

use think\Model;
use traits\model\SoftDelete;

class DomainBlock extends Model
{
    use SoftDelete;

    // 表名
    protected $name = 'domain_block';

    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';

    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';
    protected $deleteTime = 'deletetime';

    public function getStatusList()
    {
        return ['normal' => __('Normal'), 'hidden' => __('Hidden')];
    }
}

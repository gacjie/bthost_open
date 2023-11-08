<?php

namespace app\common\model;

use think\Model;
use traits\model\SoftDelete;

class Domainlist extends Model
{

    use SoftDelete;

    

    // 表名
    protected $name = 'domainlist';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';

    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';
    protected $deleteTime = 'deletetime';

    // 追加属性
    protected $append = [
        'audit_text',
        'status_text'
    ];
    

    
    public function getAuditList()
    {
        return ['0' => __('Audit 0'), '1' => __('Audit 1'), '2' => __('Audit 2')];
    }

    public function getStatusList()
    {
        return ['normal' => __('Normal'), 'hidden' => __('Hidden')];
    }


    public function getAuditTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['audit']) ? $data['audit'] : '');
        $list = $this->getAuditList();
        return isset($list[$value]) ? $list[$value] : '';
    }


    public function getStatusTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['status']) ? $data['status'] : '');
        $list = $this->getStatusList();
        return isset($list[$value]) ? $list[$value] : '';
    }




}
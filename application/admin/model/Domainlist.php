<?php

namespace app\admin\model;

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
        'status_text',
        'vhost',
    ];
    

    
    public function getAuditList()
    {
        return ['0' => __('Audit 0'), '1' => __('Audit 1'), '2' => __('Audit 2')];
    }

    public function getStatusList()
    {
        return ['0' => __('Status 0'), '1' => __('Status 1'), '2' => __('Status 2')];
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

    public function vhost()
    {
        return $this->belongsTo('Host', 'vhost_id', 'id', [], 'LEFT')->setEagerlyType(0);
    }

    public function doma(){
        return $this->belongsTo('Domain', 'domain_id', 'id', [], 'LEFT')->setEagerlyType(0);
    }

    public function getVhostAttr($value,$data){
        return $data['vhost_id']?$this->vhost():$data['vhost_id'];
    }


}

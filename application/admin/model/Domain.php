<?php

namespace app\admin\model;

use think\Model;
use traits\model\SoftDelete;

class Domain extends Model
{

    use SoftDelete;

    

    // 表名
    protected $name = 'domain';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';

    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';
    protected $deleteTime = 'deletetime';

    // 追加属性
    protected $append = [
        'dnspod_text',
        'status_text',
        'domain_info',
    ];
    

    
    public function getDnspodList()
    {
        return ['0' => __('Dnspod 0'), '1' => __('Dnspod 1')];
    }

    public function getStatusList()
    {
        return ['normal' => __('Normal'), 'hidden' => __('Hidden'), 'locked' => __('Locked')];
    }


    public function getDnspodTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['dnspod']) ? $data['dnspod'] : '');
        $list = $this->getDnspodList();
        return isset($list[$value]) ? $list[$value] : '';
    }


    public function getStatusTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['status']) ? $data['status'] : '');
        $list = $this->getStatusList();
        return isset($list[$value]) ? $list[$value] : '';
    }

    public function getDomainInfoAttr($value,$data){
        return $data['domain']?$data['domain']:'';
    }

    public function domainpools(){
        return $this->belongsTo('Domainpools', 'domainpools_id', 'id', [], 'LEFT')->setEagerlyType(0);
    }

}
<?php

namespace app\common\model;

use think\Model;
use traits\model\SoftDelete;

class Ippools extends Model
{

    use SoftDelete;

    

    // 表名
    protected $name = 'ippools';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';

    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';
    protected $deleteTime = 'deletetime';

    // 追加属性
    protected $append = [
        'status_text',
        'ip_count',
        'ip_sys',
    ];
    

    
    public function getStatusList()
    {
        return ['normal' => __('Normal'), 'hidden' => __('Hidden')];
    }


    public function getStatusTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['status']) ? $data['status'] : '');
        $list = $this->getStatusList();
        return isset($list[$value]) ? $list[$value] : '';
    }

    // IP池IP地址总数
    public function getIpCountAttr($value, $data){
        return model('Ipaddress')->where(['ippools_id'=>$data['id']])->count();
        // return 1000;
    }

    // 剩余没有绑定的IP数
    public function getIpSysAttr($value, $data){
        return model('Ipaddress')->where(['ippools_id'=>$data['id']])->count();
        // return 1000;
    }


}
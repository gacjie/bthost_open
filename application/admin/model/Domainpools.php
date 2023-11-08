<?php

namespace app\admin\model;

use think\Model;
use traits\model\SoftDelete;

class Domainpools extends Model
{

    use SoftDelete;

    

    // 表名
    protected $name = 'domainpools';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';

    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';
    protected $deleteTime = 'deletetime';

    // 追加属性
    protected $append = [
        'status_text',
        'count',
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

    public function getCountAttr($value,$data){
        return $data['id']?model('Domain')->where('domainpools_id',$data['id'])->count():0;
    }

}
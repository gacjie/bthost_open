<?php

namespace app\admin\model;

use think\Model;
use traits\model\SoftDelete;

class User extends Model
{

    use SoftDelete;

    

    // 表名
    protected $name = 'user';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';

    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';
    protected $deleteTime = 'deletetime';

    // 追加属性
    protected $append = [
        'prevtime_text',
        'logintime_text',
        'status_text'
    ];
    
    
    protected static function init()
    {
        self::beforeUpdate(function ($row) {
            $changed = $row->getChangedData();
            // 如果有修改密码
            if (isset($changed['password'])) {
                if ($changed['password']) {
                    $salt = \fast\Random::alnum();
                    // $row->password = \app\common\library\Auth::instance()->getEncryptPassword($changed['password'], $salt);
                    $row->password = encode($changed['password'], $salt);
                    $row->salt = $salt;
                } else {
                    unset($row->password);
                }
            }
        });

        self::beforeInsert(function ($row) {
            $changed = $row->getChangedData();
            // 新建账号时加密密码
            if (isset($changed['password'])) {
                if ($changed['password']) {
                    $salt = \fast\Random::alnum();
                    // $row->password = \app\common\library\Auth::instance()->getEncryptPassword($changed['password'], $salt);
                    $row->password = encode($changed['password'], $salt);
                    $row->salt = $salt;
                } else {
                    unset($row->password);
                }
            }
        });
    }

    
    public function getStatusList()
    {
        return ['normal' => __('Status normal'), 'hidden' => __('Status hidden'), 'locked' => __('Status locked')];
    }


    public function getPrevtimeTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['prevtime']) ? $data['prevtime'] : '');
        return is_numeric($value) ? date("Y-m-d H:i:s", $value) : $value;
    }


    public function getLogintimeTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['logintime']) ? $data['logintime'] : '');
        return is_numeric($value) ? date("Y-m-d H:i:s", $value) : $value;
    }


    public function getStatusTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['status']) ? $data['status'] : '');
        $list = $this->getStatusList();
        return isset($list[$value]) ? $list[$value] : '';
    }

    protected function setPrevtimeAttr($value)
    {
        return $value === '' ? null : ($value && !is_numeric($value) ? strtotime($value) : $value);
    }

    protected function setLogintimeAttr($value)
    {
        return $value === '' ? null : ($value && !is_numeric($value) ? strtotime($value) : $value);
    }

    public function group()
    {
        return $this->belongsTo('UserGroup', 'group_id', 'id', [], 'LEFT')->setEagerlyType(0);
    }


}
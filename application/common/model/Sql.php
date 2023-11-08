<?php

namespace app\common\model;

use app\common\library\Btaction;
use think\Model;
use traits\model\SoftDelete;

class Sql extends Model
{

    use SoftDelete;

    

    // 表名
    protected $name = 'sql';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';

    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';
    protected $deleteTime = 'deletetime';

    // 追加属性
    protected $append = [
        'status_text'
    ];

    protected static function init()
    {
        self::beforeUpdate(function ($row) {
            $changed = $row->getChangedData();
            // 如果有修改密码
            if (isset($changed['password']) && isset($row->origin['password']) && ($changed['password'] != $row->origin['password'])) {
                if ($changed['password']) {
                    if ($row->origin['type'] == 'bt') {
                        if (\app\common\model\Sql::sql_pass($row) == false) {
                            return false;
                        }
                    }
                    $row->password = encode($changed['password']);
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
                    $row->password = encode($changed['password']);
                } else {
                    unset($row->password);
                }
            }
            \app\common\model\Sql::sql_create($row);
        });

        // 数据库删除前事件
        self::beforeDelete(function ($row) {
            \app\common\model\Sql::sql_del($row);
        });
    }

    /**
     * 数据库删除
     *
     * @param [type] $row
     * @return void
     */
    public static function sql_del($row)
    {
        $bt = new Btaction();
        if ($row->deletetime) {
            // 真删除
            if ($row->username) {
                $bt = new Btaction();
                $bt->sql_name = $row->username;
                $del = $bt->SqlDelete();
                // if (!$del) {
                // 删除失败
                //     return false;
                // }
            }
        }
    }

    /**
     * 数据库密码修改
     */
    public static function sql_pass($row)
    {
        $changed = $row->getChangedData();
        $bt = new Btaction();
        $bt->sql_name = $row->username;
        $set = $bt->resetSqlPass($row->username, $changed['password']);
        if (!$set) {
            throw new \Exception($bt->_error, 1);
        }
        return true;
    }

    /**
     * 数据库创建
     *
     * @param [type] $row
     * @return void
     */
    public static function sql_create($row)
    {
        $changed = $row->getChangedData();
        // 单独创建数据库
        if (isset($changed['type']) && $changed['type'] == 'bt') {
            $bt = new Btaction();
            $database = $changed['database'] ? $changed['database'] : $changed['username'];
            $bt->buildSql($changed['username'], $database, $changed['password']);
        }
    }
    

    
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

    public function getPasswordAttr($value,$data){
        return $data['password']?decode($data['password']):$data['password'];
    }


}
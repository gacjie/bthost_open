<?php

namespace app\common\model;

use app\common\library\Btaction;
use think\Model;
use traits\model\SoftDelete;

class Ftp extends Model
{

    use SoftDelete;

    

    // 表名
    protected $name = 'ftp';
    
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
            \app\common\model\Ftp::ftp_update_status($row);
            // 如果有修改密码
            if (isset($changed['password'])) {
                if ($changed['password']) {
                    if (\app\common\model\Ftp::ftp_update_pass($row) == false) {
                        return false;
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
        });

        // TODO 删除前事件
        self::beforeDelete(function ($row) {
            \app\common\model\Ftp::ftp_del($row);
        });
    }

    /**
     * FTP 更新
     *
     * @param [type] $row
     * @return void
     */
    public static function ftp_update_status($row)
    {
        $changed = $row->getChangedData();
        // 如果有状态发生改变
        if (isset($changed['status']) && ($changed['status'] != $row->origin['status'])) {
            $bt = new Btaction();
            $bt->ftp_name = $row->username;
            if ($changed['status'] == 'normal') {
                $bt->FtpStatus(1);
            }
            if ($changed['status'] != 'normal') {
                $bt->FtpStatus(0);
            }
        }
    }

    /**
     * FTP密码修改
     *
     * @param [type] $row
     * @return void
     */
    public static function ftp_update_pass($row)
    {
        $changed = $row->getChangedData();
        $bt = new Btaction();
        $bt->ftp_name = $row->username;
        $set = $bt->resetFtpPass($row->username, $changed['password']);
        if (!$set) {
            throw new \Exception($bt->_error, 1);
        }
        return true;
    }

    /**
     * FTP 删除
     *
     * @param [type] $row
     * @return void
     */
    public static function ftp_del($row)
    {
        if ($row->deletetime) {
            // 真删除
            if ($row->username) {
                $bt = new Btaction();
                $bt->ftp_name = $row->username;
                $del = $bt->FtpDelete();
                // if (!$del) {
                // 删除失败
                //     return false;
                // }
            }
        } else {
            // 软删除，停用ftp
            $bt = new Btaction();
            $bt->ftp_name = $row->username;
            $bt->FtpStatus(0);
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
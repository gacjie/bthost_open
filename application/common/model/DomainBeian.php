<?php

namespace app\common\model;

use app\common\library\Btaction;
use think\Model;

class DomainBeian extends Model
{

    // 表名
    protected $name = 'domain_beian';

    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';

    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';

    protected static function init()
    {
        self::beforeUpdate(function ($row) {
            $changed = $row->getChangedData();
            if (isset($changed['status']) && $changed['status'] == 'success') {
                // 先删除
                \app\common\model\DomainBeian::notbeian_domain_del($row);
                // 后绑定
                \app\common\model\DomainBeian::notbeian_audit($row);
            }
        });
        // 删除前事件
        self::beforeDelete(function ($row) {
            if ($row->status == 'normal') {
                \app\common\model\DomainBeian::notbeian_domain_del($row);
            }
        });
    }

    /**
     * 审核
     *
     * @param [type] $row
     * @return void
     */
    public static function notbeian_audit($row)
    {
        $is_dir = $row->dir == '/' ? 0 : 1;
        $name = $row->dir == '/' ? $row->bt_name : $row->dir;
        // 备案审核
        $bt = new Btaction();
        $bt->bt_id = $row->bt_id; // 原有站点ID
        $set = $bt->addDomain($row->domain, $name, $is_dir);
        if (!$set) {
            throw new \Exception($bt->getError());
            return false;
        }
        return true;
    }

    /**
     * 未备案域名删除
     *
     * @param [type] $row
     * @return bool
     */
    public static function notbeian_domain_del($row)
    {
        $domain =  $row->domain;
        $bt = new Btaction();
        $del = $bt->delDomain($row->bt_id_n, $row->bt_name_n, $domain);
        // 静默删除防止意外情况导致卡死
        // throw new \Exception($bt->getError());
        return true;
    }

    public function getBeianInfoAttr($value, $data)
    {
        return $value ? json_decode($value, 1) : $value;
    }

    public function vhost()
    {
        return $this->belongsTo('Host', 'vhost_id', 'id', [], 'LEFT')->setEagerlyType(0);
    }
}

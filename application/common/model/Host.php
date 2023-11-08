<?php

namespace app\common\model;

use think\Model;
use traits\model\SoftDelete;
use app\common\library\Btaction;
use think\Config;
use dnspod\Dnspod;

class Host extends Model
{
    use SoftDelete;

    // 表名
    protected $name = 'host';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';

    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';
    protected $deleteTime = 'deletetime';

    // 追加属性
    protected $append = [
        // 'check_time_text',
        // 'is_vsftpd_text',
        // 'endtime_text',
        // 'status_text'
    ];

    protected static function init()
    {
        self::beforeUpdate(function ($row) {
            $changed = $row->getChangedData();
            // 如果有状态发生改变
            if (isset($changed['status']) && isset($row->origin['status']) && ($changed['status'] != $row->origin['status'])) {
                \app\common\model\Host::host_update_status($row);
            }
            // 如果分类ID发生改变
            if (isset($changed['sort_id']) && isset($row->origin['sort_id']) && ($changed['sort_id'] != $row->origin['sort_id'])) {
                \app\common\model\Host::host_update_sort($row);
            }
            // 如果到期时间发生改变
            if (isset($changed['endtime']) && isset($row->origin['endtime']) && ($changed['endtime'] != $row->origin['endtime'])) {
                \app\common\model\Host::host_update_endtime($row);
            }
            // 如果修改并发、限速
            if (isset($changed['perserver']) && isset($row->origin['perserver']) && ($changed['perserver'] != $row->origin['perserver'])) {
                \app\common\model\Host::host_perserver($row);
            }
        });

        // TODO 主机创建前事件
        self::beforeInsert(function ($row) {
            // $changed = $row->getChangedData();
            // 新建主机调用方法
        });

        // 主机删除前事件
        self::beforeDelete(function ($row) {
            \app\common\model\Host::host_del($row);
        });
    }

    /**
     * 主机到期时间修改
     *
     * @param [type] $row
     * @return void
     */
    public static function host_update_endtime($row)
    {
        $changed = $row->getChangedData();
        $bt = new Btaction();
        $bt->bt_id = $row->bt_id;
        $bt->bt_name = $row->bt_name;
        // 构建数据
        $expTime = date('Y-m-d', $changed['endtime']);
        $bt->setEndtime($expTime);
    }

    /**
     * 主机分类修改
     *
     * @param [type] $row
     * @return void
     */
    public static function host_update_sort($row)
    {
        $changed = $row->getChangedData();
        $bt = new Btaction();
        // 构建数据
        $data = json_encode([$row->bt_id]);
        $bt->btPanel->set_site_type($data, $changed['sort_id']);
    }

    /**
     * 主机状态修改
     *
     * @param [type] $row
     * @return void
     */
    public static function host_update_status($row)
    {
        $changed = $row->getChangedData();
        $bt = new Btaction();
        $bt->bt_id = $row->bt_id;
        $bt->bt_name = $row->bt_name;
        if ($changed['status'] == 'normal') {
            $bt->webstart();
        }
        if ($changed['status'] != 'normal') {
            $bt->webstop();
        }
    }

    /**
     * 主机删除
     *
     * @param [type] $row
     * @return void
     */
    public static function host_del($row)
    {
        $bt = new Btaction();
        if ($row->deletetime) {
            // 真删除
            if ($row->bt_id && $row->bt_name) {
                $del = $bt->siteDelete($row->bt_id, $row->bt_name);
                // if (!$del) {
                // 删除失败
                //     return false;
                // }
            }
            if ($row->is_vsftpd) {
                // TODO 如果有开通vsftpd，也删除
                // 暂时没有api，后续更新
            }
            // 删除数据库
            model('Sql')->where('vhost_id', $row->id)->delete(true);
            // 删除FTP
            model('Ftp')->where('vhost_id', $row->id)->delete(true);
        } else {
            // 软删除，停用主机
            $bt->bt_id = $row->bt_id;
            $bt->bt_name = $row->bt_name;
            $bt->webstop();
        }
    }

    /**
     * 主机并发、限速
     *
     * @param [type] $row
     * @return void
     */
    public static function host_perserver($row)
    {
        $changed = $row->getChangedData();
        $bt = new Btaction();
        $bt->bt_id = $row->bt_id;
        $bt->bt_name = $row->bt_name;
        $data = [
            'perserver' => isset($changed['perserver']) ? $changed['perserver'] : $row->origin['perserver'],
            'limit_rate' => isset($changed['limit_rate']) ? $changed['limit_rate'] : $row->origin['limit_rate'],
        ];
        if (request()->post('perip/d')) {
            $data['perip'] = request()->post('perip/d');
        }
        if (request()->post('timeout/d')) {
            $data['timeout'] = request()->post('timeout/d');
        }
        $set = $bt->setLimit($data);
        if (!$set) {
            throw new \Exception($bt->getError());
        }
    }
    

    
    public function getIsVsftpdList()
    {
        return ['0' => __('Is_vsftpd 0'), '1' => __('Is_vsftpd 1')];
    }

    public function getStatusList()
    {
        return ['normal' => __('Status normal'), 'stop' => __('Status stop'), 'locked' => __('Status locked'), 'expired' => __('Status expired'), 'excess' => __('Status excess'), 'error' => __('Status error')];
    }


    public function getCheckTimeTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['check_time']) ? $data['check_time'] : '');
        return is_numeric($value) ? date("Y-m-d H:i:s", $value) : $value;
    }


    public function getIsVsftpdTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['is_vsftpd']) ? $data['is_vsftpd'] : '');
        $list = $this->getIsVsftpdList();
        return isset($list[$value]) ? $list[$value] : '';
    }


    public function getEndtimeTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['endtime']) ? $data['endtime'] : '');
        return is_numeric($value) ? date("Y-m-d H:i:s", $value) : $value;
    }


    public function getStatusTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['status']) ? $data['status'] : '');
        $list = $this->getStatusList();
        return isset($list[$value]) ? $list[$value] : '';
    }

    protected function setCheckTimeAttr($value)
    {
        return $value === '' ? null : ($value && !is_numeric($value) ? strtotime($value) : $value);
    }

    protected function setEndtimeAttr($value)
    {
        return $value === '' ? null : ($value && !is_numeric($value) ? strtotime($value) : $value);
    }
    
    protected static function passencode($password){
        return $password?getPasswordHash($password):$password;
    }

    // 获取宝塔主机分类
    public function sort($value){
        if($value){
            $bt = new Btaction();
            $sortList = $bt->getsitetype();
            if($sortList){
                $newArr = array_column($sortList,'name');
                return isset($newArr[$value])?$newArr[$value]:$value;
            }else{
                return $value;
            }
            
        }else{
            return $value;
        }
    }

    public function user()
    {
        return $this->belongsTo('User', 'user_id', 'id', [], 'LEFT');
    }

    /**
     * 域名解析
     *
     * @param [type] $domain        根域名
     * @param [type] $value         记录值 如ip cname.xxx.com
     * @param [type] $sub_domain    解析值 如www @ xxxx
     * @param [type] $record_type   解析方式 'SRV', 'MX', 'CNAME', 'AAAA', 'A', 'TXT', 'NS'
     * @return void
     */
    public function doamin_analysis($domain, $value, $sub_domain, $record_type)
    {
        $domain_find = model('Domain')->where(['domain' => $domain, 'status' => 'normal'])->find();
        if (!$domain_find) {
            return '域名不存在';
        }
        $id = Config::get('dnspod.id');
        $token = Config::get('dnspod.token');
        if (!$id || !$token) {
            return '配置不完整';
        }
        $dnspod = new Dnspod($id, decode($token));
        $jx = $dnspod->record_Create($domain_find['dnspod_id'], '', $value, $sub_domain, $record_type);
        if ($jx && isset($jx['record']) && $jx['record']) {
            $data = array_merge($jx['record'], ['domain_id' => $domain_find['dnspod_id'], 'domain' => $domain_find['domain']]);
            return $data;
        } else {
            return $dnspod->msg;
        }
    }

    // 转换IP地址
    public function getIpAddressAttr($value,$data){
        return $data['ip_address']?collection(model('Ipaddress')::all($data['ip_address']))->toArray():[];
    }

    // 获取sql信息
    public function getSqlInfo($value, $data)
    {
        return Sql::get(['vhost_id' => $data['id']]);
    }

    // 获取ftp信息
    public function getFtpInfo($value, $data)
    {
        return Ftp::get(['vhost_id' => $data['id']]);
    }

    // 转化状态码为中文（已使用语言配置文件实现）
    public static function status($status)
    {
        switch ($status) {
            case 'normal':
                $status_attr = __('normal');
                break;
            case 'stop':
                $status_attr = __('stop');
                break;
            case 'locked':
                $status_attr = __('locked');
                break;
            case 'expired':
                $status_attr = __('expired');
                break;
            case 'excess':
                $status_attr = __('excess');
                break;
            case 'error':
                $status_attr = __('error');
                break;
            default:
                $status_attr = __('error');
                break;
        }
        return $status_attr;
    }

    // 转换主机状态为数字状态
    public static function getNumber($status)
    {
        switch ($status) {
            case 'normal':
                $vhostStatus = 1;
                break;
            case 'stop':
                $vhostStatus = 0;
                break;
            case 'locked':
                $vhostStatus = 2;
                break;
            case 'expired':
                $vhostStatus = 3;
                break;
            case 'excess':
                $vhostStatus = 4;
                break;
            case 'error':
                $vhostStatus = 5;
                break;
            default:
                $vhostStatus = 0;
                break;
        }
        return $vhostStatus;
    }
}
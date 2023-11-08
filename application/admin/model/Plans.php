<?php

namespace app\admin\model;

use think\Model;
use traits\model\SoftDelete;

class Plans extends Model
{

    use SoftDelete;

    

    // 表名
    protected $name = 'plans';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';

    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';
    protected $deleteTime = 'deletetime';

    public $msg = '';

    // 追加属性
    protected $append = [
        'status_text',
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

    /**
     * 资源组转化
     *
     * @param [type] $id        资源组ID
     * @param array $params     自定义资源组内容
     * @return void
     */
    public function getPlanInfo($id = '', $params = [])
    {
        if ($params) {
            $plansArr = $params;
        } else {
            $plansInfo = self::get(['status' => 'normal', 'id' => $id]);
            if (!$plansInfo) {
                return false;
            }
            $plansArr = json_decode($plansInfo->value, 1);
        }
        
        // 域名池，随机抽选一个域名
        if($plansArr['domainpools_id']){
            $domainArr = model('Domain')->where(['domainpools_id'=>$plansArr['domainpools_id'],'status'=>'normal'])->column('id,domain,dnspod');
        }else{
            $domainArr = false;
        }
        if(!$domainArr){
            $this->msg = '域名池中无可用域名';
            return false;
        }
        $domain_key = array_rand($domainArr);
        $domain = $domainArr[$domain_key]['domain'];

        // IP池，随机抽选一个IP
        $ip = '';
        $ip_num = isset($plansArr['ip_num'])?$plansArr['ip_num']:0;
        if($plansArr['ippools_id']&&$ip_num){
            $ipRandList = model('Ipaddress')->getRandId($plansArr['ippools_id'],$ip_num);
            // exit;
            $ipArr = implode(',',$ipRandList);
            if($ipRandList&&isset($ipRandList[0])){
                $ip = model('Ipaddress')->where('id',$ipRandList[0])->value('ip');
            }
        }else{
            $ipArr = false;
        }

        // XXX 获取IP列表用于域名解析
        // model('Ipaddress')::all($ipArr);
        
        $plansArr['domain'] = $domain;
        $plansArr['ipArr'] = $ipArr;
        $plansArr['ip'] = $ip;
        $plansArr['domainlist_id'] = $domain_key;
        $plansArr['dnspod'] = $domainArr[$domain_key]['dnspod'];
        return $plansArr;
    }
}
<?php

namespace dnspod;

use think\Session;

/**
 * dnspod 
 * 
 * 使用教程
 * 
 * 测试数据：域名id：66174382,79163643    解析记录id：609833463,609833471     id：166546    密钥：2225b19248e22b132471f676a30a23e0
 * $dnspod = new Dnspod('166546','2225b19248e22b132471f676a30a23e0');
 * 域名列表
 * $dnspod->domain_List();
 * 记录列表
 * $dnspod->record_List('79163643');
 * 删除记录
 * $dnspod->record_Remove('609833463','66174382');
 * $dnspod = new Dnspod('166546','2225b19248e22b132471f676a30a23e0');
 * 批量添加
 * $dnspod->batch_record_Create('66174382,79163643',[['name'=>'w', 'type'=>'MX', 'value'=>'mx.com', 'mx'=>1]]);
 * 批量修改
 * $dnspod->batch_record_Modify('609833463,609833471','status','disable');
 * 获取错误信息
 * $dnspod->msg;
 */
class Dnspod
{
    public $api_url = 'https://dnsapi.cn/';

    public $login_token = '';

    public function __construct($id='',$token='')
    {
        if(!$id||!$token){
            return $this->message('密钥不能为空');
        }
        $this->login_token = $id.','.$token;
    }

    /**
     * 状态对照表
     *
     * @var array
     */
    public $status_list = [
        'enable' => '启用',
        'pause' => '暂停',
        'spam' => '封禁',
        'lock' => '锁定',
    ];

    /**
     * 解析方式
     *
     * @var array
     */
    public $allowed_type = ['SRV', 'MX', 'CNAME', 'AAAA', 'A', 'TXT', 'NS'];

    public $msg = '';

    /**
     * 获取域名列表
     *
     * @param string $type	否	域名分组类型, 默认为’all’. 包含以下类型：
     * all：所有域名
     * mine：我的域名
     * share：共享给我的域名
     * ismark：星标域名
     * pause：暂停域名
     * vip：VIP域名
     * recent：最近操作过的域名
     * share_out：我共享出去的域名
     * @param int $offset	否	记录开始的偏移, 第一条记录为 0, 依次类推
     * @param int $length	否	要获取的域名数量, 比如获取20个, 则为20
     * @param int $group_id	否	分组ID, 获取指定分组的域名。可以通过 获取域名分组 获取 group_id
     * @param string $keyword	否	搜索的关键字, 如果指定则只返回符合该关键字的域名
     * @return void
     */
    public function domain_List($keyword='',$group_id='',$type='all',$length='99'){
        $data = [
            'offset' => 0,
            'length' => $length,
            'group_id' =>$group_id,
            'keyword' => $keyword,
            'type' => $type
        ];
        return $this->api_call('Domain.List',$data);
    }

    /**
     * 域名信息
     *
     * @param string $domain_id     	分别对应域名ID和域名, 提交其中一个即可
     * @param string $domain
     * @return void
     */
    public function domain_info($domain_id = '', $domain = ''){
        $data = [
            'domain_id' =>$domain_id,
            'domain' =>$domain,
        ];
        return $this->api_call('Domain.Info',$data);
    }

    public function domain_log($domain_id = '', $domain = ''){
        $data = [
            'domain_id' =>$domain_id,
            'domain' =>$domain,
        ];
        return $this->api_call('Domain.Log',$data);
    }

    /**
     * 添加新域名
     *
     * @param string $domain	是	域名, 没有 www, 如 dnspod.com
     * @param int $group_id	否	域名分组ID, 可选参数
     * @param string $is_mark	否	是否星标域名，”yes”、”no” 分别代表是和否
     * @return void
     * 
     */
    public function domain_Create($domain='',$group_id='',$is_mark='no'){
        $data = [
            'domain'=>$domain,
            'group_id'=>$group_id,
            'is_mark'=>$is_mark,
        ];
        return $this->api_call('Domain.Create',$data);
    }

    /**
     * 域名删除
     *
     * @param string $domain_id domain_id 或 domain, 分别对应域名ID和域名, 提交其中一个即可
     * @param string $domain    
     * @return void
     */
    public function domain_Remove($domain_id = '', $domain = ''){
        $data = [
            'domain_id' =>$domain_id,
            'domain' =>$domain,
        ];
        return $this->api_call('Domain.Remove',$data);
    }

    /**
     * 记录列表
     *
     * @param string $domain_id     domain_id 或 domain, 分别对应域名ID和域名, 提交其中一个即可
     * @param string $domain
     * @param string $keyword       搜索的关键字，如果指定则只返回符合该关键字的记录，可选 【指定 keyword 后系统忽略查询参数 sub_domain，record_type，record_line，record_line_id】
     * @return void
     */
    public function record_List($domain_id='',$domain='',$keyword = ''){
        $data =[
            'domain_id' =>$domain_id,
            'domain' =>$domain,
            'keyword'=>$keyword,
        ];
        return $this->api_call('Record.List',$data);
    }

    /**
     * 添加记录
     *
     * @param [type] $domain_id domain_id 或 domain, 分别对应域名ID和域名, 提交其中一个即可
     * @param [type] $domain    
     * @param [type] $value     记录值, 如 IP:200.200.200.200, CNAME: cname.dnspod.com., MX: mail.dnspod.com., 必选
     * @param [type] $record_type   记录类型，通过API记录类型获得，大写英文，比如：A, 必选
     * @param [type] $sub_domain    主机记录, 如 www，可选，如果不传，默认为 @
     * @param [type] $status        [“enable”, “disable”]，记录初始状态，默认为”enable”，如果传入”disable”，解析不会生效，也不会验证负载均衡的限制，可选
     * @param string $ttl           {1-604800} TTL，范围1-604800，不同等级域名最小值不同, 可选
     * @return void
     */
    public function record_Create($domain_id='',$domain='',$value,$sub_domain='@',$record_type="A",$record_line="默认",$status='enable',$ttl='600'){
        if(!in_array($record_type,$this->allowed_type)){
            return $this->message('解析方式不正常');
        }
        $data = [
            'domain_id' =>$domain_id,
            'domain' =>$domain,
            'sub_domain'=>$sub_domain,
            'value'=>$value,
            'record_type'=>$record_type,
            'status'=>$status,
            'ttl'=>$ttl,
            'record_line'=>$record_line,
        ];
        return $this->api_call('Record.Create',$data);
    }

    /**
     * 修改记录
     *
     * @param [type] $record_id     记录ID，必选
     * @param [type] $domain_id     domain_id 或 domain, 分别对应域名ID和域名, 提交其中一个即可
     * @param [type] $domain
     * @param [type] $value         记录值, 如 IP:200.200.200.200, CNAME: cname.dnspod.com., MX: mail.dnspod.com.，必选
     * @param [type] $record_type   记录类型，通过API记录类型获得，大写英文，比如：A，必选
     * @param string $sub_domain    主机记录, 如 www，可选，如果不传，默认为 @
     * @param [type] $mx            {1-20} MX优先级, 当记录类型是 MX 时有效，范围1-20, MX记录必选
     * @param [type] $status        [“enable”, “disable”]，记录初始状态，默认为”enable”，如果传入”disable”，解析不会生效，也不会验证负载均衡的限制，可选
     * @param string $ttl           {1-604800} TTL，范围1-604800，不同等级域名最小值不同, 可选
     * @return void
     */
    public function record_Modify($record_id,$domain_id='',$domain='',$value,$record_type,$sub_domain='@',$mx,$status='enable',$ttl='600'){
        if(!in_array($record_type,$this->allowed_type)){
            return $this->message('解析方式不正常');
        }
        $data = [
            'domain_id' =>$domain_id,
            'domain' =>$domain,
            'record_id' =>$record_id,
            'sub_domain'=>$sub_domain,
            'value'=>$value,
            'record_type'=>$record_type,
            'mx'=>$mx,
            'status'=>$status,
            'ttl'=>$ttl,
        ];
        return $this->api_call('Record.Create',$data);
    }

    /**
     * 删除记录
     *
     * @param [type] $record_id     记录ID，必选
     * @param [type] $domain_id     domain_id 或 domain, 分别对应域名ID和域名, 提交其中一个即可
     * @param [type] $domain
     * @return void
     */
    public function record_Remove($record_id,$domain_id='',$domain=''){
        $data = [
            'domain_id' =>$domain_id,
            'domain' =>$domain,
            'record_id' =>$record_id,
        ];
        return $this->api_call('Record.Remove',$data);
    }

    /**
     * 设置记录备注
     *
     * @param [type] $record_id     record_id 记录ID，必选
     * @param [type] $remark        remark 域名备注，删除备注请提交空内容，必选
     * @param string $domain_id     domain_id 或 domain, 分别对应域名ID和域名, 提交其中一个即可
     * @param string $domain        
     * @return void
     */
    public function record_Remark($record_id,$remark,$domain_id='',$domain=''){
        $data = [
            'domain_id' =>$domain_id,
            'domain' =>$domain,
            'record_id' =>$record_id,
            'remark'=>$remark,
        ];
        return $this->api_call('Record.Remark',$data);
    }

    /**
     * 设置记录状态
     *
     * @param [type] $record_id     record_id 记录ID，必选
     * @param [type] $status        {enable|disable} 新的状态，必选
     * @param string $domain_id     domain_id 或 domain, 分别对应域名ID和域名, 提交其中一个即可
     * @param string $domain
     * @return void
     */
    public function record_Status($record_id,$status,$domain_id='',$domain=''){
        $data = [
            'domain_id' =>$domain_id,
            'domain' =>$domain,
            'record_id' =>$record_id,
            'status'=>$status,
        ];
        return $this->api_call('Record.Status',$data);
    }

    /**
     * 批量添加域名
     *
     * @param array $domains        传入数组 demo：['doamin.com','domain1.com']
     * @param string $record_value  为每个域名添加 @ 和 www 的 A 记录值，记录值为IP，可选，如果不传此参数或者传空，将只添加域名，不添加记录
     * @return void
     */
    public function batch_domain_Create($domains=[],$record_value = ''){
        $data = [
            'domains'=>implode(',',$domains),
            'record_value'=>$record_value,
        ];
        return $this->api_call('Batch.Domain.Create',$data);
    }

    /**
     * 批量添加记录
     *
     * @param [type] $domain_id         域名ID，多个 domain_id 用英文逗号进行分割
     * @param [type] $record            demo：['name'=>'w', 'type'=>'MX', 'value'=>'mx.com', 'mx'=>1]
     * @return void
     */
    public function batch_record_Create($domain_id,$record){
        if(!is_array($record) OR 0==count($record)){
            return false;
        }
        $records = array();
        foreach($record as $v){
            $arr = $this->newRecords($v['name'], $v['type'], $v['value']);
            if($v['type'] == 'MX'){
                $arr['mx'] = isset($v['mx'])?$v['mx']:1;
            }
            $records[] = $arr;
        }
        $data = [
            'domain_id'=>$domain_id,
            'records'=>json_encode($records),
        ];
        return $this->api_call('Batch.Record.Create',$data);
    }

    /**
     * 批量修改记录
     *
     * @param [type] $record_id     记录的ID，多个 record_id 用英文的逗号分割
     * @param [type] $change        要修改的字段，可选值为 [“sub_domain”、”record_type”、”area”、”value”、”mx”、”ttl”、”status”] 中的某一个
     * @param [type] $change_to     修改为，具体依赖 change 字段，必填参数
     * @param string $value         要修改到的记录值，可选，仅当 change 字段为 “record_type” 时为必填参数
     * @return void
     */
    public function batch_record_Modify($record_id,$change,$change_to,$value=''){
        $data = [
            'record_id'=>$record_id,
            'change'=>$change,
            'change_to'=>$change_to,
            'value'=>$value,
        ];
        if(!$value){
            unset($data['value']);
        }
        return $this->api_call('Batch.Record.Modify',$data);
    }

    /**
     * 获取API版本号
     *
     * @return void
     */
    public function info_Version(){
        return $this->api_call('Info.Version');
    }

    /**
     * 构造新记录表
     *
     * @param [type] $name      主机记录, 如 www，可选，如果不传，默认为 @
     * @param [type] $type      记录类型，通过API记录类型获得，大写英文，比如：A, 必选
     * @param [type] $value     记录值, 如 IP:200.200.200.200, CNAME: cname.dnspod.com., MX: mail.dnspod.com., 必选
     * @return void
     */
    public function newRecords($name, $type, $value){
        if(!in_array($type,$this->allowed_type)){
            return $this->message('解析方式不正常');
        }
        return array(
            'sub_domain' => $name,
            'record_type' => $type,
            'record_line' => '默认',
            //'record_line_id' => '0',
            'ttl' => 600,
            //'weight' => '0',
            'value' => $value,
            'status' => 'enable'
        );
    }

    /**
     * 最后一次报错
     *
     * @param [type] $msg
     * @return void
     */
    public function message($msg){
        $this->msg  = $msg;
        return false;
    }

    /**
     * 请求api
     *
     * @param [type] $api
     * @param array $data
     * @return void
     */
    private function api_call($api,$data=[]){
        if ($api == '' || !is_array($data)) {
            return $this->message('内部错误：参数错误');
        }

        $api = $this->api_url . $api;
        $data = array_merge($data, ['login_token' => $this->login_token, 'format' => 'json', 'lang' => 'cn', 'error_on_empty' => 'yes']);

        $result = $this->post_data($api, $data, Session::get('cookies'));
        // var_dump($result);exit;
        if (!$result) {
            return $this->message('内部错误：调用失败');
        }

        $result = explode("\r\n\r\n", $result);
        if (preg_match_all('/^Set-Cookie:\s*([^;]*)/mi', $result[0], $cookies)) {
            foreach ($cookies[1] as $key => $value) {
                if (substr($value, 0, 1) == 't') {
                    Session::set('cookies',$value);
                }
            }
        }

        $results = @json_decode($result[1], 1);
        // var_dump($results);exit;
        if (!is_array($results)) {
            return $this->message('内部错误：返回异常');
        }

        if ($results['status']['code'] != 1) {
            return $this->message($results['status']['message']);
        }

        return $results;
    }

    /**
     * curl模拟提交
     * @param  [type]  $url          访问的URL
     * @param  string  $post         post数据(不填则为GET)
     * @param  string  $cookie       提交的$cookies
     * @param  integer $returnCookie 是否返回$cookies
     * @param  string  $ua           自定义UA
     * @return [type]                [description]
     */
    function post_data($url, $post = '', $cookie = '', $returnCookie = 1, $ua = 'DNSPod API PHP Web Client/2.0.0 (+https://btai.cc/)')
    {
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_USERAGENT, $ua);
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($curl, CURLOPT_TIMEOUT, 60);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        $httpheader[] = "text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,image/apng,*/*;q=0.8";
        $httpheader[] = "Accept-Encoding:gzip, deflate";
        $httpheader[] = "Accept-Language:zh-CN,zh;q=0.9";
        $httpheader[] = "Connection:close";
        curl_setopt($curl, CURLOPT_HTTPHEADER, $httpheader);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        if ($post) {
            curl_setopt($curl, CURLOPT_POST, 1);
            curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($post));
        }
        if ($cookie) {
            curl_setopt($curl, CURLOPT_COOKIE, $cookie);
        }
        curl_setopt($curl, CURLOPT_HEADER, $returnCookie);
        curl_setopt($curl, CURLOPT_TIMEOUT, 10);
        curl_setopt($curl, CURLOPT_ENCODING, "gzip");
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        $data = curl_exec($curl);
        if (curl_errno($curl)) {
            return curl_error($curl);
        }
        curl_close($curl);
        return $data;
    }
}
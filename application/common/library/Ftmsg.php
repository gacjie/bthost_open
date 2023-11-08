<?php

namespace app\common\library;

class Ftmsg
{

    private static $api = 'https://sc.ftqq.com';
    private $api_url;
    public $_error;
    public $title;
    public $message;
    public $ssl;
    public function __construct($key)
    {
        $this->api_url = self::$api . '/' . $key . '.send';
    }

    // 发送
    public function send()
    {
        return $this->sc_send($this->title, $this->message);
    }

    private function sc_send($text, $desp = '')
    {
        $postdata = http_build_query(
            array(
                'text' => $text,
                'desp' => $desp
            )
        );

        $opts = array(
            'http' =>
            array(
                'method'  => 'POST',
                'header'  => 'Content-type: application/x-www-form-urlencoded',
                'content' => $postdata
            )
        );
        if($this->ssl){
            $opts = array_merge($opts,$this->ssl);
        }
        $result = false;
        $context  = stream_context_create($opts);
        try {
            $result = file_get_contents($this->api_url, false, $context);
            $data = json_decode($result, 1);
        } catch (\Exception $th) {
            $this->setError($th->getMessage());
        }
        
        if ($result && $data) {
            if (isset($data['errno']) && $data['errno'] == 0) {
                return true;
            } elseif (isset($data['errmsg'])) {
                $this->setError($data['errmsg']);
            } else {
                $this->setError('null');
            }
        } else {
            $this->setError(__('Request fail') . json_encode($result));
        }
        return false;
    }

    // 防止ssl请求报错
    public function sslVerify(){
        $this->ssl = [
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false,
            ]
        ];
        return $this;
    }

    /**
     * 设置发送标题
     *
     * @param [type] $title     消息标题，最长为256，必填。
     * @return void
     */
    public function setTitle($title)
    {
        $this->title =  $title;
        return $this;
    }

    /**
     * 设置发送消息
     *
     * @param [type] $message       消息内容，最长64Kb，可空，支持MarkDown。
     * @return void
     */
    public function setMessage($message)
    {
        $this->message =  $message;
        return $this;
    }

    /**
     * 设置错误
     * @param string $error 信息信息
     */
    protected function setError($error)
    {
        $this->_error = $error;
    }

    /**
     * 获取最后产生的错误
     * @return string
     */
    public function getError()
    {
        return $this->_error;
    }
}

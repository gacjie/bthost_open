<?php
namespace app\index\behavior;

use app\common\library\Email;
use app\common\library\Ftmsg;
use app\common\library\Message;
use think\Config;

class DomainCheckTz{

    public function run(&$params)
    {
        $is_ftmsg = Config::get('site.domain_audit_ftmsg');
        $is_email = Config::get('site.domain_audit_email');
        try{
            $title = '[域名审核提醒]'.$params['domain'];
            $content = '用户：'.$params['username']."\n\n主机：".$params['bt_name']."\n\n域名：".$params['domain']."\n\nTime：".date("Y-m-d H:i:s",time());
            // 方糖通知
            if (Config::get('site.ftqq_sckey') && $is_ftmsg) {
                $ft = new Ftmsg(Config::get('site.ftqq_sckey'));
                
                $ft->setTitle($title);
                $ft->setMessage($content);
                $ft->sslVerify();
                $message = new Message($ft);
                $result = $message->send();
                if (!$result) {
                    model('queueLog')->data([
                        'logs'      => json_encode($message->getError(), JSON_UNESCAPED_UNICODE),
                        'call_time' => '',
                    ])->save();
                }
            }

            // 邮件通知
            if (Config::get('site.email') && $is_email) {
                $content = str_replace("\n\n","<br>",$content);
                $email = new Email();
                $email->to(Config::get('site.email'))
                    ->subject($title)
                    ->message($content);
                $message = new Message($email);
                $result = $message->send();
                if (!$result) {
                    model('queueLog')->data([
                        'logs'      => json_encode($message->getError(), JSON_UNESCAPED_UNICODE),
                        'call_time' => '',
                    ])->save();
                }
            }
        }catch(\Throwable $th){
            model('queueLog')->data(['message' => $th->getMessage()])->save();
        }
        
    }
}
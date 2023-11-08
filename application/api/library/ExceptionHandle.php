<?php

namespace app\api\library;

use Exception;
use think\exception\Handle;

/**
 * 自定义API模块的错误显示
 */
class ExceptionHandle extends Handle
{

    public function render(Exception $e)
    {
        // API错误日志记录
        $msg = 'Error: ' . $e->getMessage().PHP_EOL; // 获取错误信息
        $msg .= $e->getTraceAsString().PHP_EOL;      // 获取字符串类型的异常追踪信息
        $msg .= '异常行号：' . $e->getLine().PHP_EOL; // 异常发生所在行
        $msg .= '所在文件：' . $e->getFile().PHP_EOL; // 异常发生所在文件绝对路径
        $log = @fopen(ROOT_PATH .  DS . 'logs'.DS.'api_debug.log', 'a+');
        if ($log) {
            $message = date('[Y-m-d H:i:s] ') . $msg . "\r\n";
            @fputs($log, $message);
            @fclose($log);
        }
        // 在生产环境下返回code信息
        if (!\think\Config::get('app_debug')) {
            $statuscode = $code = 500;
            $msg = 'An error occurred';
            // 验证异常
            if ($e instanceof \think\exception\ValidateException) {
                $code = 0;
                $statuscode = 200;
                $msg = $e->getError();
            }
            // Http异常
            if ($e instanceof \think\exception\HttpException) {
                $statuscode = $code = $e->getStatusCode();
            }
            return json(['code' => $code, 'msg' => $msg, 'time' => time(), 'data' => null], $statuscode);
        }
        

        //其它此交由系统处理
        return parent::render($e);
    }

}

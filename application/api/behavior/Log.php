<?php

namespace app\api\behavior;

class Log
{
    public function run(&$params)
    {
        if (request()->controller()=='Vhost') {
            \app\common\model\ApiLog::record(request()->action());
        }
    }
}

<?php

namespace app\index\behavior;

class HostLog
{
    public function run(&$params)
    {
        if (request()->isPost()) {
            \app\common\model\HostLog::record();
        }
    }
}

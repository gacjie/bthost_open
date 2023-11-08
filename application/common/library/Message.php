<?php

namespace app\common\library;

class Message
{

    private $message;

    public function __construct($message)
    {
        $this->message = $message;
    }

    public function send()
    {
        return $this->message->send();
    }

    public function getError(){
        return $this->message->getError();
    }
}

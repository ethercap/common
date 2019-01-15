<?php

namespace ethercap\common\base;

use ethercap\common\helpers\SysMsg;

class SysException extends \Exception
{
    public function __construct($message = '', $code = 0, \Throwable $previous = null)
    {
        $message = SysMsg::getErrMsg($message);
        parent::__construct($message, $code, $previous);
    }
}

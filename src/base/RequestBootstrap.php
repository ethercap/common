<?php

namespace ethercap\common\base;

use yii\base\BootstrapInterface;
use ethercap\common\helpers\StringHelper;

class RequestBootstrap implements BootstrapInterface
{
    /**
     * @inheritdoc
     */
    public function bootstrap($app)
    {
        $ip = StringHelper::getRealIp();
        $_SERVER['REMOTE_ADDR'] = $ip;
    }
}

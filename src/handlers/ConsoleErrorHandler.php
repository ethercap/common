<?php

namespace ethercap\common\handlers;

use Yii;
use ethercap\common\controllers\BaseServiceController;

class ConsoleErrorHandler extends \yii\console\ErrorHandler
{
    protected function renderException($exception)
    {
        if (Yii::$app->controller instanceof BaseServiceController) {
            Yii::$app->controller->processException($exception);
        }
        return parent::renderException($exception);
    }
}

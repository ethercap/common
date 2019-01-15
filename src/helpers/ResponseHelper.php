<?php

namespace ethercap\common\helpers;

use yii\base\Component;

abstract class ResponseHelper extends Component
{
    abstract public function handle($curl, $responseData);

    abstract public function preset($curl);
}

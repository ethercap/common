<?php

namespace ethercap\common\validators;

class CaptchaValidator extends \yii\captcha\CaptchaValidator
{
    /**
     * @inheritdoc
     */
    public function clientValidateAttribute($object, $attribute, $view)
    {
        return '';
    }
}

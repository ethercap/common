<?php

namespace lspbupt\common\validators;

use yii\validators\RegularExpressionValidator;

class IdCardNoValidator extends RegularExpressionValidator
{
    public function init()
    {
        $this->pattern = '/^[1-9]\d{7}((0\d)|(1[0-2]))(([0|1|2]\d)|3[0-1])\d{3}$|^[1-9]\d{5}[1-9]\d{3}((0\d)|(1[0-2]))(([0|1|2]\d)|3[0-1])\d{3}([0-9]|X)$/';
        parent::init();
    }

    protected function validateValue($value)
    {
        $ret = parent::validateValue($value);
        if ($ret) {
            return $ret;
        }
        $errRet = [$this->message, []];
        $length = strlen($value);
        if ($length != 18 && strlen($value) != 15) {
            return $errRet;
        }

        //18位身份证有校验位
        if ($length == 18) {
            if (!self::checkNewNum($value)) {
                return $errRet;
            }
            return null;
        }

        return null;
    }

    private static function checkNewNum($value)
    {
        $arr = [7, 9, 10, 5, 8, 4, 2, 1, 6, 3, 7, 9, 10, 5, 8, 4, 2];
        $sum = 0;
        foreach ($arr as $key => $val) {
            $sum += $arr[$key] * $value[$key];
        }
        $num = $sum % 11;
        $dict = ['1', '0', 'X', '9', '8', '7', '6', '5', '4', '3', '2'];
        if ($dict[$num] === $value[17]) {
            return true;
        }
        return false;
    }
}

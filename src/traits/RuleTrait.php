<?php

namespace ethercap\common\traits;

use ethercap\common\validators\DictValidator;
use yii\helpers\ArrayHelper;

trait RuleTrait
{
    public function getAttributeDescArr($attribute)
    {
        $validator = $this->getAttributeValidator($attribute);
        if ($validator) {
            return $validator->getList();
        }
        return [];
    }

    public function getAttributeDesc($attribute, $default = null)
    {
        $value = $this->$attribute;
        $validator = $this->getAttributeValidator($attribute);
        if ($validator) {
            if (is_array($value)) {
                $ret = [];
                foreach ($value as $val) {
                    $ret[] = ArrayHelper::getValue($validator->list, $val, $default);
                }
                return $ret;
            } else {
                return ArrayHelper::getValue($validator->list, $value, $default);
            }
        }
        return $default;
    }

    //获取某个类型的validator，只返回第一个
    public function getAttributeValidator($attribute, $class = DictValidator::class)
    {
        $validators = $this->getValidators();
        foreach ($validators as $validator) {
            if ($validator instanceof DictValidator && in_array($attribute, $validator->attributes)) {
                return $validator;
            }
        }
        return null;
    }
}

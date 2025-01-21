<?php

namespace ethercap\common\behaviors;

use ethercap\common\helpers\ArrayHelper;
use ethercap\common\helpers\RuleHelper;

/**
 *  主要是与DictValidator配合使用
 *  提供getter方法,可以通过 $model->{attribute}_desc获取值的描述
 *  提供setter方法,可以通过 $model->{attribute}_desc = $value
 *
 *  用法:
 *  在model的behaviors中加入如下行:
 *  [
 *      'class' => DictBehavior,
 *      'attributes' => ['status', 'type'],
 *  ],
 *
 *   //同时,请确保你在rules中加入了DictValidator, 如下为示例:
 *   [
 *       ['status', DictValidator::class, 'list'=> self::$statusArr],
 *       // ...
 *   ]
 *
 *  // 然后你就可以很方便的访问值了.
 *  echo $model->status_desc;
 */
class DictBehavior extends PropertyBehavior
{
    public $ending = '_desc';
    public $validatorClass = \ethercap\common\validators\DictValidator::class;

    protected function getValueByName($name)
    {
        is_null($this->defaultValue) && $this->defaultValue = $this->owner->$name;
        $validator = RuleHelper::getAttributeValidator($this->owner, $name, $this->validatorClass);
        if ($validator) {
            return $this->getValueByValidator($validator, $name);
        }
        return $this->defaultValue;
    }

    protected function getValueByValidator($validator, $name)
    {
        $value = $this->owner->$name;
        is_null($this->defaultValue) && $this->defaultValue = $value;
        if ($validator) {
            if (is_array($value)) {
                $ret = [];
                foreach ($value as $val) {
                    $ret[] = ArrayHelper::getValue($validator->list, $val, $this->defaultValue);
                }
                return $ret;
            } else {
                return ArrayHelper::getValue($validator->list, $value, $this->defaultValue);
            }
        } else {
            throw new \Exception("{$name} 不存在  {$this->validatorClass}");
        }
        return $value;
    }

    public function setValueByName($name, $value)
    {
        $validator = RuleHelper::getAttributeValidator($this->owner, $name, $this->validatorClass);
        if ($validator) {
            $list = $validator->list;
            foreach ($validator->list as $key => $val) {
                if ($val === $value) {
                    $this->owner->$name = $key;
                    return;
                }
            }
        } else {
            throw new \Exception("{$name} 不存在  {$this->validatorClass}");
        }
    }
}

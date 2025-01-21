<?php

namespace ethercap\common\helpers;

use ethercap\common\validators\DictValidator;
use yii\base\Component;
use yii\base\Model;

class RuleHelper extends Component
{
    /**
     * 返回一个model的attr的生效的验证器的property的值。
     *
     * @param \yii\base\Model $model
     * @param string          $map     格式形如"attr.rule.prop" 返回的属性链
     *                                 rule: built-in validators name | closure | classname without namespace
     * @param mixed           $default 未找到的默认值
     *
     * @return mixed|null 返回的属性值
     */
    public static function value($model, $map, $default = null)
    {
        if (!$model instanceof \yii\base\Model) {
            throw new \yii\base\InvalidParamException('model 应该是一个model。');
        }
        $mapArr = explode('.', $map);
        if (count($mapArr) != 3) {
            throw new \yii\base\InvalidConfigException('模型验证器的属性链应该是attr.rule.prop的格式。');
        }
        [$attr, $rule, $prop] = $mapArr;
        foreach ($model->getActiveValidators($attr) as $validator) {
            $params = null;
            if (self::isValidator($validator, $rule, $params)) {
                if ($validator instanceof \Closure) {
                    return $params[$prop];
                }
                return $validator->$prop;
            }
        }
        return $default;
    }

    private static function isValidator($validator, $name, &$params)
    {
        if (isset(\yii\validators\Validator::$builtInValidators[$name])) {
            $builtInValidator = \yii\validators\Validator::$builtInValidators[$name];
            if (!is_array($builtInValidator)) {
                $builtInValidator = ['class' => $builtInValidator];
            }
            foreach ($builtInValidator as $k => $v) {
                if ($k === 'class') {
                    if (!$validator instanceof $v) {
                        return false;
                    }
                } elseif ($validator->$k !== $v) {
                    return false;
                }
            }
            return true;
        } elseif ($validator instanceof \Closure && $name === 'closure') {
            //todo
            $params = [];
            return true;
        } elseif (stristr($validator::className(), $name)) {
            return true;
        }
        return false;
    }

    //获取某个类型的validator，只返回第一个
    public static function getAttributeValidator(Model $model, string $attribute, $class = DictValidator::class)
    {
        $validators = $model->getValidators();
        foreach ($validators as $validator) {
            if ($validator instanceof $class && in_array($attribute, $validator->attributes)) {
                return $validator;
            }
        }
        return null;
    }
}

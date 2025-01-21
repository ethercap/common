<?php

namespace ethercap\common\validators;

use ethercap\common\assets\ValidationAsset;
use ethercap\common\helpers\ArrayHelper;
use Yii;
use yii\helpers\Json;
use yii\validators\Validator;

/*  子model的核验器
 *
 **/
class SubModelValidator extends Validator
{
    public $value = null;
    public $multiple = false;
    //仅在multiple时有效
    public $min = 1;
    //仅在multiple时有效
    public $max = null;

    public $typeKey = 'type';
    // [key => modelClass]
    public $modelClassList = \yii\base\DynamicModel::class;

    public $tooMuch = null;
    public $tooSmall = null;

    public function init()
    {
        parent::init();
        if ($this->message == null) {
            $this->message = Yii::t('yii', '{attribute}的值不正确');
        }
        if ($this->multiple) {
            if ($this->tooMuch == null) {
                $this->tooMuch = Yii::t('yii', '{attribute}最多只允许存在{max}个');
            }
            if ($this->tooSmall == null) {
                $this->tooSmall = Yii::t('yii', '{attribute}最少需要{min}个');
            }
        }
        if (empty($this->modelClassList)) {
            throw new \Exception('modelClassList不能为空');
        }
        if (!is_array($this->modelClassList)) {
            $this->modelClassList = [$this->modelClassList];
        }
    }

    public function validateAttribute($model, $attribute)
    {
        $value = $model->$attribute;
        if ($this->value) {
            //判断是不是callback
            if ($this->value instanceof Closure || (is_array($this->value) && is_callable($this->value))) {
                $value = call_user_func($this->value, $model, $attribute);
            } elseif (is_string($this->value)) {
                $key = $this->value;
                $value = $model->$key;
            }
        }
        $path = '';
        if (!$this->multiple) {
            $value = [$value];
            $this->min = 1;
            $this->max = 1;
        }

        if ($this->min && count($value) < $this->min) {
            $this->addError($model, $attribute, $this->tooSmall, ['min' => $this->min]);
            return;
        }
        if ($this->max && count($value) > $this->max) {
            $this->addError($model, $attribute, $this->tooMuch, ['max' => $this->max]);
            return;
        }

        foreach ($value as $index => $subModel) {
            $path = $this->multiple ? '' : "$.{$index}";
            // 获取type的value
            if ($this->typeKey) {
                $type = $subModel->{$this->typeKey};
                if ($this->modelClassList && isset($this->modelClassList[$type])) {
                    $class = $this->modelClassList[$type];
                    if (!($subModel instanceof $class)) {
                        $this->addError($model, $attribute, Yii::t('yii', '{attribute}的值不正确,'.$path.': class不正确'));
                        return;
                    }
                    if (!$subModel->validate()) {
                        $this->addError($model, $attribute, Yii::t('yii', '{attribute}的值不正确,'.$path.': '.current($subModel->getFirstErrors())));
                        return;
                    }
                } else {
                    $this->addError($model, $attribute, Yii::t('yii', '{attribute}的值不正确, '.$path.': class不正确'));
                    return;
                }
            } else {
                $isValid = false;
                foreach ($this->modelClassList as $key => $class) {
                    if ($subModel instanceof $class) {
                        $isValid = true;
                        break;
                    }
                }
                if (!$isValid) {
                    $this->addError($model, $attribute, Yii::t('yii', '{attribute}的值不正确'));
                    return;
                }
                if (!$subModel->validate()) {
                    $this->addError($model, $attribute, Yii::t('yii', '{attribute}的值不正确,'.$path.': '.current($subModel->getFirstErrors())));
                    return;
                }
            }
        }
    }

    public function getModelClassMapOption()
    {
        $data = [];
        foreach ($this->modelClassList as $key => $class) {
            $model = new $class();
            $data[$key] = ArrayHelper::renderToArray($model);
        }
        return $data;
    }

    //获取客户端的配置
    public function getClientOptions($model, $attribute)
    {
        $label = $model->getAttributeLabel($attribute);
        $options = [
            'multiple' => $this->multiple,
            'typeKey' => $this->typeKey,
            'modelClassList' => $this->getModelClassMapOption(),
            'message' => $this->formatMessage($this->message, ['attribute' => $label]),
        ];
        if ($this->multiple) {
            if ($this->min !== null) {
                $options['min'] = $this->min;
                $options['tooSmall'] = $this->formatMessage($this->tooSmall, ['attribute' => $label, 'min' => $this->min]);
            }
            if ($this->max !== null) {
                $options['max'] = $this->max;
                $options['tooMuch'] = $this->formatMessage($this->tooMuch, ['attribute' => $label, 'max' => $this->max]);
            }
        }
        if ($this->skipOnEmpty) {
            $options['skipOnEmpty'] = 1;
        }
        return $options;
    }

    public function clientValidateAttribute($model, $attribute, $view)
    {
        ValidationAsset::register($view);
        $options = $this->getClientOptions($model, $attribute);
        return 'yii.validation.subModel(value, messages, ' . Json::htmlEncode($options) . ');';
    }
}

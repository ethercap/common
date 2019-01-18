<?php

namespace ethercap\common\validators;

use yii\helpers\Json;
use yii\helpers\ArrayHelper;
use yii\validators\Validator;
use ethercap\common\assets\ValidationAsset;
use Yii;

/* 数组选择的validator
 *
 **/
class DictValidator extends Validator
{
    public $list = [];
    public $multiple = false;
    //仅在多选时有效
    public $min = 1;
    //仅在多选时有效
    public $max = null;
    public $excludes = [];

    //有些时候需要向前插入选项
    public $append = [];
    //有些时候需要向后插入选项
    public $prefix = [];
    private $_allList = null;

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
                $this->tooMuch = Yii::t('yii', '{attribute}最多只允许选择{max}个');
            }
            if ($this->tooSmall == null) {
                $this->tooSmall = Yii::t('yii', '{attribute}最少需要选择{min}个');
            }
            if ($this->max == null) {
                $this->max = count($this->list);
            }
        }
    }

    public function getAllList()
    {
        if ($this->_allList) {
            return $this->_allList;
        }
        $this->_allList = ArrayHelper::merge($this->prefix, $this->list);
        $this->_allList = ArrayHelper::merge($this->_allList, $this->append);
        return $this->_allList;
    }

    public function validateAttribute($model, $attribute)
    {
        $value = $model->$attribute;
        if (!$this->multiple) {
            if (is_array($value)) {
                $this->addError($model, $attribute, $this->message);
                return false;
            }
            $value = [$value];
            $this->min = 1;
            $this->max = 1;
        }
        if (!is_array($value)) {
            $this->addError($model, $attribute, $this->message);
            return false;
        }
        if (count($value) < $this->min) {
            $this->addError($model, $attribute, $this->tooSmall, ['min' => $this->min]);
            return false;
        }
        if (count($value) > $this->max) {
            $this->addError($model, $attribute, $this->tooMuch, ['max' => $this->max]);
            return false;
        }

        $keys = array_keys($this->allList);
        if (!empty($this->allList)) {
            foreach ($value as $val) {
                if (!in_array($val, $keys)) {
                    $this->addError($model, $attribute, $this->message);
                    return;
                }
            }
        }

        if (!empty(array_intersect($this->excludes, $value))) {
            $this->addError($model, $attribute, $this->message);
            return;
        }
        return;
    }

    //获取客户端的配置
    public function getClientOptions($model, $attribute)
    {
        $label = $model->getAttributeLabel($attribute);
        $options = [
            'list' => $this->allList,
            'multiple' => $this->multiple,
            'excludes' => $this->excludes,
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
        return 'yii.validation.dict(value, messages, ' . Json::htmlEncode($options) . ');';
    }
}

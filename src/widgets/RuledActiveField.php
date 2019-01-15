<?php

namespace lspbupt\common\widgets;

use yii\helpers\Html;
use yii\helpers\ArrayHelper;
use kartik\form\ActiveField;
use kartik\widgets\Select2;
use kartik\widgets\DatePicker;
use lspbupt\common\helpers\RuleHelper;
use kartik\widgets\DateTimePicker;
use lspbupt\common\helpers\BucketHelper;
use lspbupt\common\annotations\S2Annotation;
use lspbupt\common\annotations\FileAnnotation;
use lspbupt\common\annotations\RuledAnnotation;

class RuledActiveField extends ActiveField
{
    public function init()
    {
        parent::init();
    }

    public function textInput($options = [])
    {
        $this->applyAnnotations($options);
        return parent::textInput($options);
    }

    public function textArea($options = [])
    {
        $this->applyAnnotations($options);
        $this->staticValue = Html::tag('div', $this->model->{$this->attribute}, [
            'style' => 'white-space: pre-line;word-break: break-all;',
        ]);
        return parent::textArea($options);
    }

    public function checkboxButtonGroup($items = 'array.keys', $options = [])
    {
        $this->applyAnnotations($options);
        return parent::checkboxButtonGroup(self::r($items), $options);
    }

    public function checkboxList($items = 'array.keys', $options = [])
    {
        $this->applyAnnotations($options);
        return parent::checkboxList(self::r($items), $options);
    }

    public function radioButtonGroup($items = 'array.keyIn', $options = [])
    {
        $this->applyAnnotations($options);
        return parent::radioButtonGroup(self::r($items), $options);
    }

    public function dropdownList($items = 'array.keyIn', $options = [])
    {
        $this->applyAnnotations($options);
        $placehoder = ArrayHelper::remove($options, 'placeholder', null);
        $items = self::r($items);
        if ($placehoder !== null) {
            $items = ArrayHelper::merge([null => $placehoder], $items);
        }
        return parent::dropdownList($items, $options);
    }

    public function select2($options = [])
    {
        $annotation = S2Annotation::parse($this->model::className(), $this->attribute);
        $this->staticValue = $annotation->staticValue === null ? null :
            ArrayHelper::getValue($this->model, $annotation->staticValue);
        if ($annotation->ajax) {
            $selectInputOption = SelectInput::inputConfig($annotation->url, $annotation->placeholder, $annotation->name);
        } else {
            $selectInputOption = SelectInput::getConfig([], false);
        }
        ArrayHelper::setValue($selectInputOption, 'options.multiple', $annotation->multiple);
        $options = ArrayHelper::merge($options, $selectInputOption);
        if ($annotation->initValueText) {
            if ($annotation->multiple) {
                // TODO: multiple的初始显示。
            } else {
                $options['initValueText'] = ArrayHelper::getValue($this->model, $annotation->initValueText, '');
            }
        }
        if (!isset($options['data'])) {
            $options['data'] = 'array.keyIn';
        }
        if (!is_array($options['data'])) {
            $options['data'] = [null => $annotation->placeholder] + self::r($options['data'], []);
        }
        return $this->widget(Select2::className(), $options);
    }

    public function datePicker($options = [])
    {
        return $this->widget(DatePicker::className(), [
            'options' => $options,
            'pluginOptions' => [
                'autoclose' => true,
                'format' => 'yyyy-mm-dd',
            ],
        ]);
    }

    public function datetimePicker($options = [])
    {
        return $this->widget(DateTimePicker::className(), [
            'options' => $options,
            'pluginOptions' => [
                'autoclose' => true,
                'format' => 'yyyy-mm-dd hh:ii:ss',
            ],
        ]);
    }

    public function staticHiddenInput($options = [])
    {
        $staticThis = clone $this;
        $staticThis->staticValue = ArrayHelper::remove($options, 'staticValue', $this->model->{$this->attribute});

        $hiddenThis = clone $this;
        $hiddenThis->template = '{input}';
        return $staticThis->staticInput($options).$hiddenThis->hiddenInput($options);
    }

    public function uploadWidget()
    {
        $annotation = FileAnnotation::parse($this->model::className(), $this->attribute);
        $config = [
            'maxFileCount' => $annotation->maxFileCount,
            'attachAppend' => $annotation->attachAppend,
            'inputId' => $annotation->inputId,
            'previewStringPrefix' => $annotation->previewStringPrefix,
        ];
        if ($annotation->uploadUrl !== null) {
            $config['uploadUrl'] = $annotation->uploadUrl;
        } else {
            $config['uploadUrl'] = BucketHelper::uploadUrl(
                $annotation->oss,
                $annotation->bucket,
                $annotation->object
            );
        }
        $files = json_decode($this->model->{$this->attribute}, true);
        if ($files && is_array($files)) {
            foreach ($files as $file) {
                $fileurl = ArrayHelper::getValue($file, 'url');
                if (!$fileurl) {
                    continue;
                }
                $fileurl = BucketHelper::downloadUrl($annotation->oss, $fileurl);
                $this->staticValue .= Html::img($fileurl, ['style' => 'width:80%']);
            }
        }
        return $this->widget(UploadWidget::class, $config);
    }

    public function onNull(\Closure $callback)
    {
        if ($this->model->{$this->attribute} === null) {
            ($callback->bindTo($this))();
        }
        return $this;
    }

    public static $_pluginHintKeys;

    private function r($items, $default = null)
    {
        if (is_string($items)) {
            substr_count($items, '.') === 1 && $items = "{$this->attribute}.$items";
            $items = RuleHelper::value($this->model, $items, $default);
        }
        return $items;
    }

    private function translateRule($string)
    {
        $trmap = [
            //tricky here
            //当没有required会返回false；有required但是when被设置了也不认为是必填。
            '{required}' => self::r("{$this->attribute}.required.when", false) === null ? '必填 ' : '',
        ];
        preg_match("/{\w+\.\w+}/", $string, $result);
        foreach ($result as $match) {
            $ruleProperty = trim($match, '{ }');
            $trmap[$match] = self::r("{$this->attribute}.$ruleProperty", '');
        }
        return strtr($string, $trmap);
    }

    private function applyAnnotations(&$options = [])
    {
        if ($propertyAnnotation = RuledAnnotation::parse($this->model::className(), $this->attribute)) {
            $propertyAnnotation->hint !== null
                && $this->hint(self::translateRule($propertyAnnotation->hint));
            $propertyAnnotation->placeholder !== null
                && $options['placeholder'] = self::translateRule($propertyAnnotation->placeholder);
            $propertyAnnotation->staticValue !== null
                && $this->staticValue = ArrayHelper::getValue($this->model, $propertyAnnotation->staticValue);
        }
    }
}
$props = new \ReflectionProperty(ActiveField::className(), '_pluginHintKeys');
$props->setAccessible(true);
RuledActiveField::$_pluginHintKeys = $props->getValue();

<?php

namespace ethercap\common\widgets;

use yii\helpers\ArrayHelper;
use kartik\editable\Editable;

class EditableWidget
{
    public static function autoSelectConfig($name, $url, $data, $value = null, $displayValue = null, $header = null, $pjaxContainerId = 'ajaxCrudDatatable')
    {
        if ($value === null && is_array($data) && !empty($data)) {
            $value = key($data);
        }
        $displayValue === null && $displayValue = $data;
        $value = ArrayHelper::getValue($displayValue, $value, $value);

        $header === null && $header = $name;
        return [
            'name' => $name,
            'value' => $value,
            'asPopover' => true,
            'header' => $header,
            'inputType' => Editable::INPUT_DROPDOWN_LIST,
            'data' => $data,
            'options' => ['class' => 'form-control', 'prompt' => '选择'.$header.'...'],
            'ajaxSettings' => ['url' => $url],
            'showButtons' => false,
            'enablePopStateFix' => false,
            'displayValueConfig' => $displayValue,
            'buttonsTemplate' => '<div hidden>{submit}</div>',
            'pluginEvents' => [
                'editableChange' => "function(event, val) { var popover = $(event.currentTarget).find('button').data('target');$(popover).find('.kv-editable-submit').trigger('click') }",
            ],
            'pjaxContainerId' => $pjaxContainerId,
        ];
    }

    public static function config($name, $type = Editable::INPUT_TEXTAREA, $url = '', $placeholder = '', $value = '', $displayValue = '')
    {
        $config = [
            'name' => $name,
            'asPopover' => false,
            'displayValue' => $displayValue,
            'inputType' => $type,
            'value' => $value,
            'submitOnEnter' => false,
            'showButtonLabels' => true,
            'submitButton' => [
                'label' => '提交',
            ],
            'resetButton' => [
                'label' => '重置',
            ],
            'ajaxSettings' => ['url' => $url],
            'options' => [
                'class' => 'form-control',
                'rows' => 5,
                'style' => 'width:400px',
                'placeholder' => $placeholder,
            ],
        ];
        return $config;
    }
}

<?php

namespace lspbupt\common\widgets;

use yii\web\JsExpression;
use yii\helpers\ArrayHelper;

class SelectInput
{
    public static function getConfig($config, $isAjax = true)
    {
        $ajax = [
            'url' => '',
            'dataType' => 'json',
            'data' => new JsExpression('function(params) { return {q:params.term}; }'),
            'processResults' => new JsExpression('function (data, params){
                if(data.code == 0) {
                    return {
                        results: data.data,
                    };
                } else {
                    console.log(data.message);
                }
            }'),
        ];
        $default = [
            'language' => 'zh-cn',
            'pluginOptions' => [
                'allowClear' => true,
            ],
        ];
        if ($isAjax) {
            $default['pluginOptions']['ajax'] = $ajax;
        }
        return ArrayHelper::merge($default, $config);
    }

    public static function inputConfig($url, $placeholder = '', $name = '')
    {
        $configArr = [
            'language' => 'zh-cn',
            'options' => ['placeholder' => '请使用中文名称进行搜索'],
            'pluginOptions' => [
                'allowClear' => false,
                'minimumInputLength' => 1,
                'ajax' => [
                    'url' => $url,
                    'dataType' => 'json',
                    'data' => self::getDataJS(),
                ],
                'templateResult' => self::getTemplateJS(),
                'templateSelection' => self::getTemplateJS(),
            ],
        ];
        !empty($placeholder) && $configArr['options']['placeholder'] = $placeholder;
        !empty($name) && $configArr['name'] = $name;
        return $configArr;
    }

    public static function multiInputConfig($url, $placeholder = '', $name = '')
    {
        $configArr = self::inputConfig($url, $placeholder, $name);
        $configArr['options']['multiple'] = true;
        return $configArr;
    }

    public static function getDataJS()
    {
        return new jsexpression('function(params) { return {q:params.term}; }');
    }

    public static function getTemplateJS()
    {
        return new jsexpression('function(data) { return data.text; }');
    }
}

<?php

namespace ethercap\common\behaviors;

use ethercap\common\assets\DiffAsset;
use ethercap\common\forms\DiffFieldsForm;
use ethercap\common\helpers\ArrayHelper;
use yii\base\Behavior;

/**
 * Formatter 主要是针对 默认 \yii\i18n\Formatter 的一些扩展格式
 * usage:
 * pre-0: 在config里加上formatter配置
 *      'formatter' => [
 *           'as customFormatter' => 'ethercap\common\behaviors\Formatter',
 *       ],
 * pre-1: 注册一下DiffAsset // ethercap\common\assets\DiffAsset::register($this);
 * $diff = ['小菜', '大鸟'] // $diff = [['degree' => 'bachelor'], ['degree' => 'master']]
 * 1. 可以直接使用 $diff = \Yii::$app->formatter->asTwoFieldDiff($diff);
 * 2: DetailView / GridView 里可以写成 'field:twoFieldDiff' 或者 显式的写上 'format' => 'twoFieldDiff'
 * demo
 */
class Formatter extends Behavior
{
    /**
     * 展示两个不同字段的变化的html
     * NOTE: 使用时候必须引入 DiffAsset // ethercap\common\assets\DiffAsset::register($this);
     * @see DiffAsset
     * @param array $params [$old, $new]
     * @return string
     */
    public function asTwoFieldDiff($params)
    {
        $ret = '';
        if (!is_array($params) || empty($params) || !ArrayHelper::isIndexed($params)) {
            return $ret;
        }
        @list($old, $new) = $params;
        $diffForm = new DiffFieldsForm(['old' => $old, 'new' => $new, ]);
        if ($diffForm->validate()) {
            $ret = $diffForm->getDiffHtml();
        }
        return $ret;
    }
}

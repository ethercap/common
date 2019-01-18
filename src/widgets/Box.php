<?php

namespace ethercap\common\widgets;

use yii\helpers\Html;

class Box extends \yii\bootstrap\Widget
{
    public $pluginOptions = [];
    public $defaultPluginOptions = [];
    public $title;
    public $content;
    public $footer;
    public $type = 'default';
    public $collapsable = true;
    public $collapse = false;

    const TYPE_INFO = 'info';
    const TYPE_DEFAULT = 'default';
    const TYPE_DANGER = 'danger';
    const TYPE_PRIMARY = 'primary';
    const TYPE_SUCCESS = 'success';

    public function init()
    {
        parent::init();
        $style = $this->collapse ? 'display:none' : '';
        echo Html::beginTag('div', ['class' => 'box box-'.$this->type.' box-solid', 'id' => $this->getId()]);
        if ($this->title) {
            echo Html::tag('div', $this->title, ['class' => 'box-header']);
        }
        echo Html::beginTag('div', ['class' => 'box-body', 'style' => $style]);
    }

    public function run()
    {
        echo $this->content;
        echo Html::endTag('div');
        if ($this->footer) {
            echo Html::tag('div', $this->footer, ['class' => 'box-footer']);
        }
        echo Html::endTag('div');
    }

    public static function begin($config = array())
    {
        parent::begin($config);
    }

    public static function end()
    {
        parent::end();
    }
}

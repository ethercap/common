<?php

namespace lspbupt\common\widgets;

use yii\base\Widget;
use yii\helpers\Html;

class IBox extends Widget
{
    public $title;
    public $collapsable = true;
    public $closable = true;
    public $configable = [];
    public $boxClass = 'ibox float-e-margins';
    public $titleClass = 'ibox-title';
    public $toolClass = 'ibox-tools';
    public $contentClass = 'ibox-content';
    public $contentStyle = '';

    protected $content;

    public function init()
    {
        parent::init();
        echo Html::beginTag('div', ['class' => $this->boxClass]);
        if ($this->title) {
            echo Html::beginTag('div', ['class' => $this->titleClass]);
            echo Html::tag('h5', $this->title);
            if ($this->collapsable || $this->closable || $this->config) {
                echo Html::beginTag('div', ['class' => $this->toolClass]);
                if ($this->collapsable) {
                    echo Html::tag('a', '<i class="fa fa-chevron-up"></i>', ['class' => 'collapse-link']);
                }
                if ($this->closable) {
                    echo Html::tag('a', '<i class="fa fa-times"></i>', ['class' => 'close-link']);
                }
                echo Html::endTag('div');
            }
            echo Html::endTag('div');
        }
        echo Html::beginTag('div', ['class' => $this->contentClass, 'style' => $this->contentStyle]);
    }

    public function run()
    {
        echo $this->content;
        echo Html::endTag('div');
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

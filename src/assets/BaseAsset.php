<?php

namespace ethercap\common\assets;

use yii\web\AssetBundle;

class BaseAsset extends AssetBundle
{
    public $sourcePath = __DIR__ . '/src';
    public $js = [
        'js/base.js',
    ];
    public $css = [
        'css/base.css',
    ];
    public $depends = [
        'yii\web\YiiAsset',
        'yii\bootstrap\BootstrapAsset',
    ];
}

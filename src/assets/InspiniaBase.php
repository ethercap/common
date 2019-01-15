<?php

namespace ethercap\common\assets;

use yii\web\AssetBundle;

class InspiniaBase extends AssetBundle
{
    public $sourcePath = __DIR__ . '/src';
    public $js = [
        'js/inspinia.js',
        'js/metismenu.js',
        'js/pace.min.js',
        'js/slimscroll.min.js',
        'js/jquery.cookie.js',
        'js/site.js',
    ];
    public $css = [
        'css/morris.css',
        'css/inspinia.css',
        'css/animate.css',
        'css/menu.css',
    ];
    public $depends = [
        'rmrevin\yii\fontawesome\AssetBundle',
        'yii\web\YiiAsset',
        'yii\bootstrap\BootstrapAsset',
        'yii\bootstrap\BootstrapPluginAsset',
    ];
}

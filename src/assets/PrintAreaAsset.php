<?php

namespace ethercap\common\assets;

use yii\web\AssetBundle;

/**  打印网页
 **  $("...").printArea();
 **/
class PrintAreaAsset extends AssetBundle
{
    public $sourcePath = __DIR__ . '/src';
    public $js = [
        'js/jquery.printarea.js',
    ];
    public $css = [
    ];
    public $depends = [
        'yii\web\YiiAsset',
    ];
}

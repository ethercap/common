<?php

namespace ethercap\common\assets;

use yii\web\AssetBundle;

class ValidationAsset extends AssetBundle
{
    public $sourcePath = __DIR__ . '/src';

    public $js = [
        'js/validation.js',
    ];

    public $depends = [
        'yii\validators\ValidationAsset',
    ];
}

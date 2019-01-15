<?php

namespace ethercap\common\assets;

use yii\web\AssetBundle;

/**
 * Class DiffAsset
 * @package ethercap\common\assets
 * 用法参考: https://c.ethercap.com/pages/viewpage.action?pageId=23077591
 */
class DiffAsset extends AssetBundle
{
    public $sourcePath = __DIR__ . '/src';
    public $css = [
        'css/diff.css',
    ];

    public $js = [
    ];

    public $depends = [
        'yii\web\YiiAsset',
    ];

    public function init()
    {
        parent::init();
    }
}

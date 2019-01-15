<?php

namespace ethercap\common\filters;

use yii\base\ActionFilter;
use Yii;

/**
 * MemoryLimitFilter 用来设置memorylimit
 *
 * 主要是为了防止其他的脚本调用的时候也触发MemoryLimit, 比如 ./yii help的时候会遍历所有的脚本, 这个时候的memorylimit就取决于最后一次的调用了
 *
 * ```php
 * public function behaviors()
 * {
 *       return [
 *           'memorylimit' => [
 *               'class' => MemoryLimitFilter::class,
 *               'actions' => ['projectinbase'],
 *               'memoryLimit' => '1024M',
 *           ],
 *       ];
 * }
 * ```
 */
class MemoryLimitFilter extends ActionFilter
{
    public $actions = [];
    public $memoryLimit = '1024M';

    public function beforeAction($action)
    {
        if (in_array($action->id, $this->actions)) {
            ini_set('memory_limit', $this->memoryLimit);
        }
        return parent::beforeAction($action);
    }
}

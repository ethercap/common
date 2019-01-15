<?php

namespace ethercap\common\filters;

use yii\base\ActionFilter;
use Yii;

/**
 * NoCsrfFilter 用来去掉csrf校验
 *
 * 为了防止跨域攻击，页面上会对请求进行csrf校验，如果一些场景不需要，可以去掉csrf校验功能
 *
 * ```php
 * public function behaviors()
 * {
 *       return [
 *           'nocsrf' => [
 *               'class' => NoCsrfFilter::class,
 *               'actions' => ['index'],
 *           ],
 *       ];
 * }
 * ```
 */
class NoCsrfFilter extends ActionFilter
{
    public $actions = [];
    // 兼容之前的版本，完全可以不用填
    public $controller = null;

    public function beforeAction($action)
    {
        if (in_array($action->id, $this->actions)) {
            Yii::$app->controller->enableCsrfValidation = false;
        }
        return parent::beforeAction($action);
    }
}

<?php

namespace ethercap\common\filters;

use Yii;
use yii\base\ActionFilter;
use yii\base\InvalidConfigException;
use yii\web\HttpException;
use yii\console\Exception;

/**
 * SynchronizedFilter 用来防止服务器同时收到两个一样的请求
 *
 * ```php
 * public function behaviors()
 * {
 *       return [
 *           'synchronized' => [
 *               'class' => SynchronizedFilter::class,
 *               'actions' => ['update'],
 *           ],
 *       ];
 * }
 * ```
 */
class SynchronizedFilter extends ActionFilter
{
    public $actions = [];
    public $mutex = 'mutex';
    public $timeout = 0;
    public $mutexKey;
    public $needThrowException = true;
    public $maxInstance = 1;
    public $instanceIndex = 0;

    protected $_cacheKey;

    private $_lockIndex = null;

    public function init()
    {
        if (!$mutex = Yii::$app->get($this->mutex, false)) {
            throw new InvalidConfigException('Component mutex must be set.');
        }
        parent::init();
    }

    public function beforeAction($action)
    {
        if (in_array($action->id, $this->actions)) {
            if (!$key = $this->getKey()) {
                return true;
            }
            for ($this->instanceIndex = 0; $this->instanceIndex < $this->maxInstance; $this->instanceIndex++) {
                $ret = Yii::$app->{$this->mutex}->acquire($key.$this->instanceIndex, $this->timeout);
                if ($ret) {
                    $this->_lockIndex = $this->instanceIndex;
                    return true;
                }
            }

            if (Yii::$app->request instanceof \yii\web\Request) {
                throw new HttpException(400, '请求速度过快，请呆会再试');
            } else {
                if ($this->needThrowException) {
                    throw new Exception('达到最大进程数上限，启动失败');
                } else {
                    return false;
                }
            }
            return false;
        }
        return parent::beforeAction($action);
    }

    public function afterAction($action, $result)
    {
        if (in_array($action->id, $this->actions)) {
            if ($key = $this->getKey() && $this->_lockIndex !== null) {
                Yii::$app->{$this->mutex}->release($key.$this->_lockIndex);
            }
        }
        return parent::afterAction($action, $result);
    }

    protected function getKey()
    {
        if (!$this->_cacheKey) {
            $request = Yii::$app->getRequest();
            if ($this->mutexKey && is_callable($this->mutexKey)) {
                return call_user_func($this->mutexKey, $request);
            }
            if ($request instanceof \yii\web\Request) {
                $str = $request->getMethod() . "\n" . $request->getAbsoluteUrl() . "\n" . $request->getRawBody();
            } else {
                $controller = Yii::$app->controller;
                $str = Yii::$app->id."\n".$controller->id."\n".$controller->action->id . json_encode($request->getParams());
            }
            $this->_cacheKey = md5($str);
        }
        return $this->_cacheKey;
    }
}

<?php

namespace ethercap\common\controllers;

use yii\helpers\Console;
use yii\helpers\ArrayHelper;
use yii\console\Exception;
use yii\base\ExitException;
use yii\base\ModelEvent;
use Yii;

/**
 * 允许你进行队列的操作
 *
 * 查看允许的命令列表
 *     yii queue
 * 启动队列
 *     yii queue/start
 * 队列状态
 *     yii queue/status
 *
 * @author lishipeng <lishipeng@ethercap.com>
 */
class QueueController extends ServiceController
{
    const PRIORITY_HIGH = 0;
    const PRIORITY_NORMAL = 8;
    const PRIORITY_LOW = 16;

    // @event ModelEvent 开启队列之前的事件
    const EVENT_BEFORE_DATAFAILED = 'beforeDataFailed';
    const EVENT_AFTER_DATAFAILED = 'afterDataFailed';

    // 队列名
    public $queue;
    public $mns = 'mns';
    // 获取消息时，等待多长时间
    public $wait = 3;
    // 消息处理失败后，等待多少时间才塞回队列
    public $delaySeconds = 5;
    //重试次数
    public $maxRetry = 5;

    public function init()
    {
        if (!isset($this->queue)) {
            throw new Exception('需指定队列名称 $this->queue');
        }
        $mns = Yii::$app->{$this->mns};
        $this->queue = $mns->{$this->queue};
        parent::init();
    }

    // 从队列中获取数据
    public function getData()
    {
        try {
            $ret = $this->queue->receive($this->wait);
        } catch (ExitException $e) {
            throw $e;
        } catch (\Exception $e) {
            if ($e->getCode() != '404') {
                $this->stdout($e->getMessage()."\n", Console::FG_RED);
            }
            return null;
        }
        $data = json_decode($ret, true);
        $retry = ArrayHelper::getValue($data, 'retry', 0);
        if ($retry >= $this->maxRetry) {
            $this->stdout('超出最大重试次数:'.$ret."\n", Console::FG_RED);
            return null;
        }
        return $data;
    }

    public function processData()
    {
    }

    //失败时，将消息塞回队列
    public function failure()
    {
        if (!$this->beforeDataFailed()) {
            return;
        }
        if (!empty($this->oneData)) {
            if (!isset($this->oneData['retry'])) {
                $this->oneData['retry'] = 0;
            }
            $this->oneData['retry']++;
            $data = json_encode($this->oneData);
            $this->stdout($data."\n", Console::FG_YELLOW);
            //将数据塞回队列, 并置为较低优先级
            $this->queue->send($data, $this->delaySeconds, self::PRIORITY_LOW);
            $this->isProcessed = true;
        }
        $this->afterDataFailed();
    }

    //关闭时的处理
    public function beforeStop()
    {
        if (!$this->isProcessed) {
            $this->failure();
        }
        return parent::beforeStop();
    }

    //留给上层处理
    public function beforeDataFailed()
    {
        $event = new ModelEvent();
        $this->trigger(self::EVENT_BEFORE_DATAFAILED, $event);
        return $event->isValid;
    }

    public function afterDataFailed()
    {
        $event = new ModelEvent();
        $this->trigger(self::EVENT_AFTER_DATAFAILED, $event);
    }

    public function actionStatus()
    {
        $this->printPrefix = false;
        $this->printInfo = true;
        $arr = $this->queue->getStatus();
        foreach ($arr as $item) {
            $this->stdout($item['name'].'：', Console::FG_GREEN);
            $this->stdout($item['value']."\n", Console::FG_YELLOW);
        }
        $this->stdout("\n", Console::FG_YELLOW);
        return parent::actionStatus();
    }
}

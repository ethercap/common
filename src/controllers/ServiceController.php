<?php

namespace ethercap\common\controllers;

use yii\base\ModelEvent;

/**
 * 允许你进行队列的操作
 *
 * 查看允许的命令列表
 *     yii service
 * 启动队列
 *     yii service/start
 * 队列状态
 *     yii service/status
 *
 * @author lishipeng <lishipeng@ethercap.com>
 */
abstract class ServiceController extends BaseServiceController
{
    // 多长时间重启,单位为秒
    public $timeout = 3600;
    // 每次处理间隔时间, 单位为微秒, 用来控制速度
    public $dataInterval = 20;
    public $nullInterval = 1000000;

    // 全局缓存当前的数据
    protected $oneData;
    // 当前数据的处理状况
    protected $isProcessed;

    // @event ModelEvent 开始处理任务之前的事件
    const EVENT_BEFORE_PROCESS = 'beforeProcess';
    // @event ModelEvent 开始处理任务之后的事件
    const EVENT_AFTER_PROCESS = 'afterProcess';

    public function process()
    {
        $starttime = time();
        while (true) {
            $this->oneData = $this->getData();
            $this->isProcessed = false;
            if (!empty($this->oneData)) {
                if (!$this->beforeProcess()) {
                    continue;
                }
                $this->processData();
                $this->afterProcess();
                $this->isProcessed = true;
            } else {
                usleep($this->nullInterval);
            }
            $now = time();
            //超时，退出循环
            if ($now - $starttime >= $this->timeout) {
                break;
            }
        }
    }

    abstract public function getData();

    abstract public function processData();

    protected function beforeProcess()
    {
        $event = new ModelEvent();
        $this->trigger(self::EVENT_BEFORE_PROCESS, $event);
        return $event->isValid;
    }

    protected function afterProcess()
    {
        $event = new ModelEvent();
        $this->trigger(self::EVENT_AFTER_PROCESS, $event);
        usleep($this->dataInterval);
    }
}

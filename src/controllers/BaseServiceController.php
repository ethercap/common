<?php

namespace ethercap\common\controllers;

use yii\console\Controller;
use yii\helpers\Console;
use yii\console\ExitCode;
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
abstract class BaseServiceController extends Controller
{
    // boolean，是否打印信息到屏幕
    public $printInfo = true;
    public $printPrefix = true;

    // @event ModelEvent 开启队列之前的事件
    const EVENT_BEFORE_START = 'beforeStart';
    // @event ModelEvent 开启队列之后的事件
    const EVENT_AFTER_START = 'afterStart';
    // @event ModelEvent 关闭队列之前的事件
    const EVENT_BEFORE_STOP = 'beforeStop';

    protected $hostname;

    /**
     * 启动队列处理
     *
     * @return int CLI exit code
     *
     * @throws \yii\console\Exception on failure.
     */
    public function actionStart()
    {
        if (!$this->beforeStart()) {
            $this->stdout('beforeStart失败', Console::FG_RED, Console::UNDERLINE);
            return ExitCode::UNSPECIFIED_ERROR;
        }
        // 安装上信号量处理
        pcntl_async_signals(true);
        pcntl_signal(SIGTERM, [&$this, 'recycle']);
        pcntl_signal(SIGINT, [&$this, 'recycle']);
        pcntl_signal(SIGQUIT, [&$this, 'recycle']);
        $this->hostname = gethostname();
        $this->afterStart();
        $result = $this->process();
        $this->beforeStop();
        return ExitCode::OK;
    }

    /**
     * 获取队列的状态, ps aux|grep 队列
     *
     * @return int CLI exit code
     * @throw \yii\console\Exception on failure
     **/
    public function actionStatus()
    {
        $this->printPrefix = false;
        $name = \Yii::$app->controller->id.'/start';
        $string = shell_exec("ps aux|grep yii |grep -v \"ps aux\"|grep ${name}");
        if (!empty($string)) {
            $this->stdout("当前任务的进程列表为：\n", Console::FG_GREEN);
            $this->stdout("===============================================================================\n", Console::FG_GREEN);
            $this->stdout("USER       PID %CPU %MEM    VSZ   RSS TTY      STAT START   TIME COMMAND\n", Console::FG_YELLOW);
            $this->stdout("===============================================================================\n", Console::FG_GREEN);
            $this->stdout($string, Console::FG_YELLOW);
            $this->stdout("===============================================================================\n", Console::FG_GREEN);
            $this->stdout("注意：建议使用kill来关闭任务即可, 不建议使用kill -9 来关闭任务!\n", Console::FG_RED);
        } else {
            $this->stdout('当前任务没有执行', Console::FG_RED);
        }
    }

    // 在开启之前的处理
    protected function beforeStart()
    {
        $event = new ModelEvent();
        $this->trigger(self::EVENT_BEFORE_START, $event);
        return $event->isValid;
    }

    protected function afterStart()
    {
        $this->stdout("启动成功,开始准备处理数据...\n", Console::FG_GREEN);
        $event = new ModelEvent();
        $this->trigger(self::EVENT_AFTER_START, $event);
    }

    /**
     * 数据处理函数
     *
     * @return mixed boolean|Array
     **/
    abstract protected function process();

    // 在关闭掉之前进行的操作,可以实现优雅地关闭进程
    protected function beforeStop()
    {
        $this->stdout("开始关闭...\n", Console::FG_GREEN);
        $event = new ModelEvent();
        $this->trigger(self::EVENT_BEFORE_STOP, $event);
    }

    //处理回收
    public function recycle()
    {
        $this->stdout("开始处理回收\n", Console::FG_GREEN);
        $this->beforeStop();
        die;
    }

    public function processException($e)
    {
        $this->stdout($e->getMessage() . "\n", Console::FG_RED);
        $this->recycle();
    }

    // 覆盖底层的stdout方法
    public function stdout($string)
    {
        if ($this->printInfo) {
            if ($this->printPrefix) {
                $time = date('Y-m-d H:i:s');
                $string = "[$time] [$this->hostname] ".$string;
            }
            if ($this->isColorEnabled()) {
                $args = func_get_args();
                array_shift($args);
                $string = Console::ansiFormat($string, $args);
            }
            return Console::stdout($string);
        }
    }
}

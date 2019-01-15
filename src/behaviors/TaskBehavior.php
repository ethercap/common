<?php

namespace ethercap\common\behaviors;

use Yii;
use yii\base\InvalidConfigException;

class TaskBehavior extends \yii\base\Behavior
{
    public $taskTitle;
    public $taskWorkflow;
    public $taskDesc = '';
    public $taskExecutorProp = '';
    public $taskExecutorId = 0;
    public $taskType = '';
    public $taskBidProp = '';
    public $taskBeginActions = [];
    public $task = 'task';

    public function init()
    {
        parent::init();
        $this->task = \yii\di\Instance::ensure($this->task, '\ethercap\common\helpers\TaskHelper');
        $this->check();
    }

    private function check()
    {
        $data = get_object_vars($this);
        $model = \yii\base\DynamicModel::validateData($data, [
            [['taskTitle', 'taskWorkflow', 'taskType', 'taskBeginActions', 'task'], 'required'],
            [['taskTitle', 'taskWorkflow', 'taskDesc', 'taskExecutorProp', 'taskType', 'taskBidProp', ], 'string'],
            [['taskExecutorId'], 'integer'],
            [['taskBeginActions'], function ($attribute, $params) {
                if (!is_array($this->$attribute)) {
                    $this->addError($attribute, 'taskBeginActions 必须为数组');
                } else {
                    foreach ($this->taskBeginActions as $action) {
                        if (!is_string($action)) {
                            $this->addError($attribute, '工单起始动作名须为字符串类型');
                            return;
                        }
                    }
                }
            }],
        ]);
        if ($model->hasErrors()) {
            $arr = [];
            foreach ($model->errors as $prop => $errors) {
                $arr[] = $prop.': '.implode(', ', $errors);
            }
            $errmsg = implode("\n", $arr);
            throw new InvalidConfigException($errmsg);
        }
    }

    public function events()
    {
        return [StateBehavior::EVENT_STATE_AFTER.'*' => '_taskProcess'];
    }

    private function getBid()
    {
        $bidProp = $this->taskBidProp;
        return ($bidProp && isset($this->owner->$bidProp)) ? $this->owner->$bidProp : $this->owner->primaryKey;
    }

    private function getType()
    {
        $type = $this->taskType;
        return $type ?: (Yii::$app->id.'-'.$this->owner::className());
    }

    private function getExecutorId()
    {
        if ($this->taskExecutorId) {
            return $this->taskExecutorId;
        }
        if ($this->taskExecutorProp) {
            $name = $this->taskExecutorProp;
            if (isset($this->owner->$name)) {
                return $this->owner->$name;
            }
        }
        return 0;
    }

    public function _taskProcess($event)
    {
        $action = $event->sender->getStateAction();
        $taskId = 0;
        if (in_array($action->name, $this->taskBeginActions)) {
            $taskId = $this->getTaskId();
            if ($taskId === false) {
                $params = [
                    'title' => $this->taskTitle,
                    'desc' => $this->taskDesc,
                    'workflowId' => $this->getWorkflowId(),
                    'bid' => $this->getBid(),
                    'executorId' => $this->getExecutorId(),
                    'type' => $this->getType(),
                ];
                $taskId = $this->task->create($params);
                if ($taskId === false) {
                    return;
                }
            }
        } else {
            $taskId = $this->getTaskId();
            if ($taskId === false) {
                return;
            }
            $executorId = $this->getExecutorId();
            if ($executorId) {
                $this->setTaskExecutor($executorId);
            }
        }
        $this->task->process($taskId, $action->name);
    }

    private function getWorkflowId()
    {
        return $this->task->getWorkflowIdByName($this->taskWorkflow);
    }

    public function getTaskId()
    {
        $bid = $this->getBid();
        $type = $this->getType();
        return $this->task->search($type, $bid);
    }

    public function setTaskExecutor($executorId)
    {
        $taskId = $this->getTaskId();
        if ($taskId === false) {
            return;
        }
        $this->task->choose($taskId, $executorId);
    }

    public function getTaskInfo()
    {
        $taskId = $this->getTaskId();
        if ($taskId === false) {
            return;
        }
        return $this->task->info($taskId);
    }
}

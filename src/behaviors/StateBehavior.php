<?php

namespace ethercap\common\behaviors;

use yii\db\ActiveRecord;
use yii\base\Model;
use yii\base\InvalidConfigException;
use yii\helpers\ArrayHelper;

/**
 * Usage:
 * add these codes below in AR-model's behaviors():
 * [
 *     'class' => StateBehavior::className(),
 *     'attr' => 'status',
 *     'setScenario' => true/false, (optional)
 *     'states' => [
 *         [
 *             'desc' => 'state 1',
 *             'value' => 1,
 *         ],
 *         [
 *             'desc' => 'state 2',
 *             'value' => 2,
 *         ],
 *     ],
 *     'moveActions' => [
 *         [
 *             'name' => 'move', //this name should be identical to scenario.
 *             'from' => 1,
 *             'to' => 2,
 *         ],
 *     ],
 * ]
 *
 * Then you can try to capture events named 'eventBeforeMove' and 'eventAfterMove' to process something u need.
 */
class State
{
    public $name;
    public $value;

    public function check()
    {
    }
}

class Action
{
    public $name;
    public $from;
    public $to;

    public function check()
    {
        if (!$this->name) {
            throw new InvalidConfigException('动作name不能为空');
        }
    }
}

class StateBehavior extends \yii\base\Behavior
{
    public $attr;
    public $states = [];
    public $moveActions = [];
    public $setScenario = true;
    private $_oldState;
    private $_newState;
    private $_action;

    const EVENT_STATE_VALIDATE = 'event.state.validate.';
    const EVENT_STATE_BEFORE = 'event.state.before.';
    const EVENT_STATE_AFTER = 'event.state.after.';

    public function init()
    {
        parent::init();

        $arr = [];
        foreach ($this->states as $state) {
            if (!isset($state['class'])) {
                $state['class'] = 'ethercap\common\behaviors\State';
            }
            $obj = \Yii::createObject($state);
            $obj->check();
            $arr[] = $obj;
        }
        $this->states = $arr;

        $arr = [];
        foreach ($this->moveActions as $action) {
            if (!isset($action['class'])) {
                $action['class'] = 'ethercap\common\behaviors\Action';
            }
            $obj = \Yii::createObject($action);
            $obj->check();
            $arr[] = $obj;
        }
        $this->moveActions = $arr;

        $this->check();
    }

    private function check()
    {
        foreach ($this->states as $state) {
            if (!$state instanceof State) {
                throw new InvalidConfigException('states必须为State类及其子类');
            }
        }
        foreach ($this->moveActions as $action) {
            if (!$action instanceof Action) {
                throw new InvalidConfigException('moveActions必须为Action类及其子类');
            }
        }
    }

    public function events()
    {
        $events = [];
        $events[ActiveRecord::EVENT_BEFORE_VALIDATE] = 'validateHandler';
        $events[ActiveRecord::EVENT_BEFORE_INSERT] = 'beforeHandler';
        $events[ActiveRecord::EVENT_BEFORE_UPDATE] = 'beforeHandler';
        $events[ActiveRecord::EVENT_AFTER_INSERT] = 'afterHandler';
        $events[ActiveRecord::EVENT_AFTER_UPDATE] = 'afterHandler';
        return array_merge($events, parent::events());
    }

    public function validateHandler($event)
    {
        $key = $this->attr;
        if (!$this->owner->isAttributeChanged($key)) {
            return;
        }
        $this->_oldState = ArrayHelper::getValue($this->owner->oldAttributes, $key, null);
        $this->_newState = $this->owner->$key;
        $action = $this->findAction($this->_oldState, $this->_newState);
        if ($action === false) {
            return;
        }
        if ($this->setScenario) {
            $this->owner->setScenario($action->name);
        }
        $this->owner->trigger(self::getValidateEventName($action->name));
    }

    public function beforeHandler($event)
    {
        $key = $this->attr;
        if (!$this->owner->isAttributeChanged($key)) {
            return;
        }
        $this->_oldState = ArrayHelper::getValue($this->owner->oldAttributes, $key, null);
        $this->_newState = $this->owner->$key;
        $action = $this->findAction($this->_oldState, $this->_newState);
        if ($action === false) {
            return;
        }
        $this->_action = $action;
        $this->owner->trigger(self::getBeforeEventName($action->name));
    }

    public function afterHandler($event)
    {
        if ($this->_oldState === $this->_newState) {
            $key = $this->attr;
            if ($this->_oldState === $this->owner->$key) {
                return;
            }
            $this->_newState = $this->owner->$key;
            $action = $this->findAction($this->_oldState, $this->_newState);
            if ($action === false) {
                return;
            }
            $this->_action = $action;
        }
        if (is_string($this->_oldState)
            && (is_int($this->_newState) || is_real($this->_newState))
            && $this->_oldState === strval($this->_newState)) {
            return;
        }
        if (is_string($this->_newState)
            && (is_int($this->_oldState) || is_real($this->_oldState))
            && $this->_newState === strval($this->_oldState)) {
            return;
        }
        $action = $this->findAction($this->_oldState, $this->_newState);
        if ($action === false) {
            return;
        }
        $this->owner->trigger(self::getAfterEventName($action->name));
        $this->_oldState = $this->_newState;
    }

    private function findAction($from, $to)
    {
        foreach ($this->moveActions as $action) {
            if ($action->from === $from) {
                if ($action->to === $to) {
                    return $action;
                } else {
                    if (is_string($to) && (is_int($action->to) || is_real($action->to)) && $to === strval($action->to)) {
                        return $action;
                    }
                }
            } else {
                if ($action->to === $to) {
                    if (is_string($from) && (is_int($action->from) || is_real($action->from)) && $from === strval($action->from)) {
                        return $action;
                    }
                } else {
                    if (is_string($to)
                        && (is_int($action->to) || is_real($action->to))
                        && $to === strval($action->to)
                        && is_string($from)
                        && (is_int($action->from) || is_real($action->from))
                        && $from === strval($action->from)) {
                        return $action;
                    }
                }
            }
        }
        return false;
    }

    public static function getValidateEventName($action)
    {
        return self::EVENT_STATE_VALIDATE.$action;
    }

    public static function getAfterEventName($action)
    {
        return self::EVENT_STATE_AFTER.$action;
    }

    public static function getBeforeEventName($action)
    {
        return self::EVENT_STATE_BEFORE.$action;
    }

    public function getStateAction()
    {
        return $this->_action;
    }

    public function getStateName()
    {
        $key = $this->attr;
        $val = $this->owner->$key;
        foreach ($this->states as $state) {
            if ($state->value === $val) {
                return $state->name;
            }
        }
        return '未知状态';
    }

    public function getAllStates()
    {
        $ret = [];
        foreach ($this->states as $state) {
            $ret[$state->name] = $state->value;
        }
        return $ret;
    }
}

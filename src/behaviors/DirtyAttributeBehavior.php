<?php

namespace ethercap\common\behaviors;

use yii\helpers\ArrayHelper;
use yii\db\ActiveRecord;

/**
 * 当某个属性发生修改了以后, 可以在数据库处理前或处理后, 执行指定方法
 *  public function behaviors()
 *  {
 *      return [
 *          [
 *              'class' => DirtyAttributeBehavior::class,
 *              'attributes' => [
 *                  // 当直接指定字段的时候, 该字段如果发生修改, 将会在增、删、改之后执行对应的callback
 *                  'field1' => function ($model, $oldValue) {
 *                      // model为当前模型, $oldValue表示修改前的值
 *                      // 处理逻辑
 *                  },
 *                  // 当指定事件时, 如果字段发生修改, 将会在该事件起效的时候执行对应的callback
 *                  ActiveRecord::EVENT_BEFORE_INSERT => [
 *                      'field2' => function ($model, $oldValue) {
 *                              // 处理逻辑
 *                      },
 *                  ],
 *                  ActiveRecord::EVENT_AFTER_INSERT => [
 *                      'field3' => function ($model, $oldValue) {
 *                              // 处理逻辑
 *                      },
 *                  ],
 *                  // 支持调用类内部的方法,
 *                  'field4' => 'func',
 *              ]
 *            ],
 *      ];
 *  }
 *
 * Public function func ($model, $oldValue) {
 *      // 处理逻辑
 * }
 *
 *
 * Class DirtyAttributeBehavior
 *
 * @package common\behaviors
 *
 * @property ActiveRecord $owner
 */
class DirtyAttributeBehavior extends \yii\base\Behavior
{
    public $attributes = [];

    // 执行数组, 钩子 => [字段 => callBack, ....]
    protected $executeAttributes = [
        ActiveRecord::EVENT_BEFORE_INSERT => [],
        ActiveRecord::EVENT_BEFORE_UPDATE => [],
        ActiveRecord::EVENT_BEFORE_DELETE => [],
        ActiveRecord::EVENT_AFTER_INSERT => [],
        ActiveRecord::EVENT_AFTER_UPDATE => [],
        ActiveRecord::EVENT_AFTER_DELETE => [],
    ];

    // 属性发生修改的记录
    protected $dirtyAttributes = [];

    // before类型的钩子, 钩子 => 执行函数名
    protected static $beforeHookFuncArr = [
        ActiveRecord::EVENT_BEFORE_INSERT => 'beforeInsertExecute',
        ActiveRecord::EVENT_BEFORE_UPDATE => 'beforeUpdateExecute',
        ActiveRecord::EVENT_BEFORE_DELETE => 'beforeDeleteExecute',
    ];

    // after类型的钩子, 钩子 => 执行函数名
    protected static $afterHookFuncArr = [
        ActiveRecord::EVENT_AFTER_INSERT => 'afterInsertExecute',
        ActiveRecord::EVENT_AFTER_UPDATE => 'afterUpdateExecute',
        ActiveRecord::EVENT_AFTER_DELETE => 'afterDeleteExecute',
    ];

    const FLAG = 0;
    const OLD_VALUE = 1;

    public function init()
    {
        $flag = parent::init();
        $this->formatAttributes();
        return $flag;
    }

    /**
     * @inheritdoc
     */
    public function events()
    {
        return array_merge(self::$afterHookFuncArr, self::$beforeHookFuncArr);
    }

    public function __call($name, $params)
    {
        if (in_array($name, self::$beforeHookFuncArr)) {
            $this->setFlag();
            $this->execute(array_search($name, self::$beforeHookFuncArr));
        } elseif (in_array($name, self::$afterHookFuncArr)) {
            $this->execute(array_search($name, self::$afterHookFuncArr));
        } else {
            parent::__call($name, $params);
        }
    }

    protected function execute($hook)
    {
        foreach ($this->executeAttributes[$hook] as $attribute => $func) {
            if (isset($this->dirtyAttributes[$attribute]) && $this->dirtyAttributes[$attribute][self::FLAG] === true) {
                if (is_string($func)) {
                    call_user_func([$this->owner, $func], $this->owner, $this->dirtyAttributes[$attribute][self::OLD_VALUE]);
                } else {
                    call_user_func($func, $this->owner, $this->dirtyAttributes[$attribute][self::OLD_VALUE]);
                }
            }
        }
    }

    /**
     * 检查哪些字段发生了修改
     */
    protected function setFlag()
    {
        $dirtyArr = $this->owner->getDirtyAttributes();
        $oldArr = $this->owner->getOldAttributes();
        foreach ($dirtyArr as $field => $value) {
            foreach ($this->executeAttributes as $hook => $attributes) {
                if (isset($attributes[$field])) {
                    $this->dirtyAttributes[$field] = [self::FLAG => true, self::OLD_VALUE => ArrayHelper::getValue($oldArr, $field, null)];
                }
            }
        }
    }

    protected function formatAttributes()
    {
        foreach ($this->attributes as $hook => $attributeArr) {
            if (is_array($attributeArr)) {
                $this->executeAttributes[$hook] = $attributeArr;
            } else {
                $this->registerAfter($hook, $attributeArr);
            }
        }
    }

    /**
     * 将某个字段注册到所有after类型的钩子后
     *
     * @param $field
     */
    protected function registerAfter($field, $callBack)
    {
        foreach (self::$afterHookFuncArr as $hook => $func) {
            $this->executeAttributes[$hook][$field] = $callBack;
        }
    }
}

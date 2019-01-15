<?php

namespace ethercap\common\behaviors;

use Closure;
use Yii;
use yii\base\Behavior;
use yii\base\Event;
use yii\db\ActiveRecord;
use yii\base\Application;
use yii\db\Connection;

/**
 * RelationAttributeBehavior 用来更新关联表的相关字段
 * NOTE: 如果在长脚本里 或者 后续有新的修改的话, 需要手动调用一下executeCommits函数来保证数据不会覆盖后续的新提交数据
 * 比如 upTags更新时, 更新关联主表Up的 udpateTime, 这样就可以知道UP相关的信息发生了更改, 下游表就可以同步信息更新
 *
 * For example,
 *
 * ```php
 * use common\behaviors\RelationAttributeBehavior;
 *
 * public function behaviors()
 * {
 *     return [
 *         [
 *             'class' => RelationAttributeBehavior::class,
 *             'relations' => ['up',],
 *             'attributes' => [
 *                 'attribute1', 'attribute2',
 *             ],
 *             'value' => function ($event) {
 *                 return 'some value';
 *             },
 *         ],
 *     ];
 * }
 * ```
 */
class RelationAttributeBehavior extends Behavior
{
    /**
     * @var array list of relations
     */
    public $relations = [];
    /**
     * @var array list of attributes that are to be automatically filled with the value specified via [[value]].
     *            The array keys are the ActiveRecord events upon which the attributes are to be updated,
     *            and the array values are the corresponding attribute(s) to be updated. You can use a string to represent
     *            a single attribute, or an array to represent a list of attributes. For example,
     *
     * ```php
     * [
     *     'attribute1', 'attribute2',
     * ]
     * ```
     */
    public $attributes = [];
    /**
     * @var mixed the value that will be assigned to the current attributes. This can be an anonymous function,
     *            callable in array format (e.g. `[$this, 'methodName']`), an [[\yii\db\Expression|Expression]] object representing a DB expression
     *            (e.g. `new Expression('NOW()')`), scalar, string or an arbitrary value. If the former, the return value of the
     *            function will be assigned to the attributes.
     *            The signature of the function should be as follows,
     *
     * ```php
     * function ($event)
     * {
     *     // return value will be assigned to the attribute
     * }
     * ```
     */
    public $value = '';
    /**
     * @var bool whether to skip this behavior when the `$owner` has not been
     *           modified
     */
    public $skipUpdateOnClean = true;
    /**
     * @var array 保存所有用到的db 为最后执行开启事务的时候使用
     */
    private static $_needCommitDBs = [];
    /**
     * @var array 保存应该提交的commit
     */
    private static $_needCommits = [];
    /**
     * @var int 确保只绑定一次afterRequest
     */
    private static $_bindCount = 0;

    public function init()
    {
        parent::init();
        if (self::$_bindCount++ == 0) {
            Yii::$app->on(Application::EVENT_AFTER_REQUEST, [self::class, 'executeCommits']);
        }
    }

    /**
     * NOTE 如果在长脚本里, 可以手动调用此函数来保证数据不会覆盖后续的新修改
     */
    public static function executeCommits()
    {
        $transactions = [];
        if (self::$_needCommitDBs) {
            foreach (self::$_needCommitDBs as $db) {
                /* @var $db Connection */
                $transactions[] = $db->beginTransaction();
            }
        }
        if (self::$_needCommits) {
            foreach (self::$_needCommits as $relation) {
                if ($relation instanceof ActiveRecord) {
                    Yii::info('Execute Commit in RelationBehavior: ' . $relation->formName() . ' : ' . json_encode($relation->primaryKey), 'relationAttributeBehavior.exec');
                    $relation->save(false);
                }
            }
            if ($transactions) {
                foreach ($transactions as $transaction) {
                    $transaction->commit();
                }
            }
        }
        self::$_needCommits = [];
        self::$_needCommitDBs = [];
    }

    private function _parseRelations()
    {
        $relations = $this->relations;
        is_string($this->relations) && $relations = [$this->relations];
        return $relations;
    }

    /**
     * 默认将 insert/update/delete 等有修改操作的事件全部绑定上
     */
    public function events()
    {
        $defaultEvents = [
            ActiveRecord::EVENT_BEFORE_INSERT,
            ActiveRecord::EVENT_BEFORE_UPDATE,
            ActiveRecord::EVENT_BEFORE_DELETE,
        ];
        return array_fill_keys(
            $defaultEvents,
            'evaluateAttributes'
        );
    }

    /**
     * Evaluates the attribute value and assigns it to the current attributes.
     *
     * @param Event $event
     */
    public function evaluateAttributes($event)
    {
        $relations = $this->_parseRelations();
        if ($this->skipUpdateOnClean
            && ($event->name == ActiveRecord::EVENT_BEFORE_UPDATE)
            && empty($this->owner->dirtyAttributes)
        ) {
            return;
        }

        if (!empty($this->attributes)) {
            $attributes = (array) $this->attributes;
            $value = $this->getValue($event);
            foreach ($attributes as $attribute) {
                if (is_string($attribute)) {
                    if ($relations) {
                        foreach ($relations as $relation) {
                            $owner = $this->owner;
                            /* @var ActiveRecord $owner */
                            if ($owner->hasProperty($relation) && ($relationQuery = $owner->getRelation($relation)) && !empty($owner->$relation)) {
                                // hasMany
                                if ($relationQuery->multiple) {
                                    if (is_array($owner->$relation) && $owner->$relation) {
                                        foreach ($owner->$relation as $childRelation) {
                                            $this->commitAttribute($childRelation, $attribute, $value);
                                        }
                                    }
                                } else {
                                    $this->commitAttribute($owner->$relation, $attribute, $value);
                                }
                            }
                        }
                    }
                }
            }
        }
    }

    /**
     * 将单个relation对应的record的提交记录下来
     *
     * @param $relation
     * @param $attribute
     * @param $value
     *
     * @return bool
     */
    private function commitAttribute($relation, $attribute, $value)
    {
        if (($relation instanceof ActiveRecord) && ($relation->hasProperty($attribute))) {
            $relation->$attribute = $value;
            return $this->commitToQueue($relation);
        }
        return true;
    }

    /**
     * Returns the value for the current attributes.
     * 默认返回当前时间
     * This method is called by [[evaluateAttributes()]]. Its return value will be assigned
     * to the attributes corresponding to the triggering event.
     *
     * @param Event $event the event that triggers the current attribute updating.
     *
     * @return mixed the attribute value
     */
    protected function getValue($event)
    {
        if ($this->value == '') {
            return date('Y-m-d H:i:s');
        } elseif ($this->value instanceof Closure || is_array($this->value) && is_callable($this->value)) {
            return call_user_func($this->value, $event);
        }
        return $this->value;
    }

    /**
     * @param $relation ActiveRecord
     * @param $attribute
     * @param $value
     *
     * @return bool
     */
    private function commitToQueue($relation)
    {
        $db = $relation->getDb();
        $uniqId = $relation->formName() . '_' . md5(json_encode($relation->primaryKey));
        self::$_needCommitDBs[$db->dsn] = $db;
        self::$_needCommits[$uniqId] = $relation;
        return true;
    }
}

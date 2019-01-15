<?php

namespace ethercap\common\behaviors;

/**
 * ```php
 * use yii\db\Expression;
 *
 * public function behaviors()
 * {
 *     return [
 *           [
 *               'class' => DateTimeBehavior::class,
 *               'attributes' => [
 *                   ActiveRecord::EVENT_BEFORE_INSERT => ['creationTime','updateTime'],
 *                   ActiveRecord::EVENT_BEFORE_UPDATE => ['updateTime'],
 *               ],
 *           ],
 *     ];
 * }
 * ```
 * 也可以直接touch某个变量
 * $model->touch('creationTime');
 */
class DateTimeBehavior extends \yii\behaviors\AttributeBehavior
{
    public $value;

    protected function getValue($event)
    {
        if ($this->value === null) {
            return date('Y-m-d H:i:s');
        }
        return parent::getValue($event);
    }

    public function touch($attribute)
    {
        $owner = $this->owner;
        $owner->$attribute = $this->getValue(null);
    }
}

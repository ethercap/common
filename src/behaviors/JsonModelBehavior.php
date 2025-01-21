<?php

namespace ethercap\common\behaviors;

use Closure;

/**
 *   提供jsonModel的便利使用
 *   [
 *       'class' => JsonModelBehavior::class,
 *       'attributes' => ['attr'],
 *       'modelClass' => xxModel::class, // 也可以传入匿名函数 function($val) {return xxModelClass; }
 *   ]
 *
 *   //访问attr的model
 *   $model->attr_jmodel
 *   $model->attr_jmodel = new xxxModel();
 *   $model->attr_jmodel->attribute = "hello world";
 *   // save时会将model的数据自动存入数据库中
 *   $model->save()
 */
class JsonModelBehavior extends SerializeBehavior
{
    public $ending = '_jmodel';
    public $defaultValue = '{}';
    public $modelClass = null;

    //通过数组获取Model
    public function getModelByValue($arr)
    {
        $modelClass = $this->modelClass;
        if ($modelClass instanceof Closure || (is_array($modelClass) && is_callable($modelClass))) {
            $modelClass = call_user_func($modelClass, $arr);
        }
        empty($modelClass) && $modelClass = \yii\base\DynamicModel::class;
        $model = new $modelClass();
        if (!($model instanceof \yii\base\Model)) {
            throw new \Exception("modelClass 必须是\yii\base\Model类");
        }
        if ($model instanceof \yii\base\DynamicModel) {
            foreach ($arr as $key => $val) {
                $model->defineAttribute($key, $val);
            }
        } else {
            $model->load($arr, '');
        }
        return $model;
    }

    protected function serializeValue($value)
    {
        return parent::serializeValue($value->toArray());
    }

    protected function unSerializeValue($value)
    {
        $arr = parent::unSerializeValue($value);
        return $this->getModelByValue($arr);
    }
}

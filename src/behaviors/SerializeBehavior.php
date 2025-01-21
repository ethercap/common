<?php

namespace ethercap\common\behaviors;

use Closure;
use yii\db\ActiveRecord;

/**
 *  支持将model上的某个值以其它格式保存和使用
 *  // 如下为一个示例,假设 $price存的为分, 我们可以通过如下配置, 然后$model->price_money 获取对应的元
 *  [
 *     'class' => SerializeBehavior::class,
 *     'ending' => '_money';
 *     'attributes' => ['price'],
 *     'unSerializeFunc' => function($val) {
 *          return number_format($val / 100, 2);
 *     },
 *     'serializeFunc' => function($val) {
 *          return intval($val * 100);
 *     }
 *  ]
 */
class SerializeBehavior extends PropertyBehavior
{
    public $ending = '_unser';
    public $defaultValue = null;

    // 如何将数据从原来的格式转为你希望为的格式
    public $unSerializeFunc = [self::class, 'jsonDecode'];
    // 如何将数据从你希望为的格式转为原来的格式
    public $serializeFunc = [self::class, 'jsonEncode'];

    private $_buffer = [];

    public function init()
    {
        parent::init();

        if (!($this->unSerializeFunc instanceof Closure || (is_array($this->unSerializeFunc) && is_callable($this->unSerializeFunc)))) {
            throw new \Exception('unSerializeFunc 必须是一个可以被调用的函数');
        }
        if (!($this->serializeFunc instanceof Closure || (is_array($this->serializeFunc) && is_callable($this->serializeFunc)))) {
            throw new \Exception('serializeFunc 必须是一个可以被调用的函数');
        }
    }

    public static function returnOrigin($val)
    {
        return $val;
    }

    public function events()
    {
        return [
            ActiveRecord::EVENT_BEFORE_INSERT => 'syncToOrigin',
            ActiveRecord::EVENT_BEFORE_UPDATE => 'syncToOrigin',
        ];
    }

    public static function jsonDecode($value)
    {
        $arr = @json_decode($value, true);
        empty($arr) && $arr = [];
        return $arr;
    }

    public static function jsonEncode($value)
    {
        return json_encode($value);
    }

    protected function unSerializeValue($value)
    {
        return call_user_func($this->unSerializeFunc, $value);
    }

    protected function serializeValue($value)
    {
        return call_user_func($this->serializeFunc, $value);
    }

    public function getValueByName($name)
    {
        if (isset($this->_buffer[$name])) {
            return $this->_buffer[$name];
        }

        $value = $this->owner->$name;
        $result = $this->unSerializeValue($value);
        $this->_buffer[$name] = $result;
        return $result;
    }

    public function setValueByName($name, $value)
    {
        $this->_buffer[$name] = $value;
    }

    // 在更改数据之前,将数据置回
    public function syncToOrigin()
    {
        foreach ($this->_buffer as $key => $value) {
            $this->owner->$key = $this->serializeValue($value);
        }
        $this->_buffer = [];
    }
}

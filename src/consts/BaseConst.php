<?php

namespace ethercap\common\consts;

use yii\helpers\Inflector;
use yii\helpers\ArrayHelper;
use yii\base\NotSupportedException;

class BaseConst extends \yii\base\Component
{
    const SECONDS_PER_DAY = 24 * 60 * 60;

    public static $default = [
        null => ['val' => -1, 'desc' => '未知'],
    ];

    public static function desc($const, $key = null)
    {
        list($key, $defaultDesc, $defaultVal) = self::_getKeyDefault($key);
        return ArrayHelper::getValue(static::$$key, $const, $defaultDesc);
    }

    public static function val($value, $key = null)
    {
        list($key, $defaultDesc, $defaultVal) = self::_getKeyDefault($key);
        return ArrayHelper::getValue(array_flip(static::$$key), $value, $defaultVal);
    }

    public static function __callStatic($name, $args)
    {
        $nameIds = Inflector::camel2id($name);
        $nameArr = explode('-', $nameIds);
        $method = array_pop($nameArr);
        if (count($args) !== count($nameArr)) {
            throw new \Exception('参数个数不匹配。', 1);
        }
        if (method_exists(static::class, $method)) {
            $result = '';
            foreach ($nameArr as $k => $v) {
                $result .= static::$method($args[$k], $v);
            }
            return $result;
        }
        throw new NotSupportedException("\"$name\" or \"$method\" is not implemented.\n");
    }

    private static function _getKeyDefault($key)
    {
        $val = ArrayHelper::getValue(static::$default, "$key.val", self::$default[null]['val']);
        $desc = ArrayHelper::getValue(static::$default, "$key.desc", self::$default[null]['desc']);
        $classArr = explode('\\', static::class);
        $keyName = is_null($key) ? end($classArr) : $key;
        $key = strtolower(Inflector::pluralize($keyName));
        return [$key, $desc, $val];
    }

    public static $booleans = [
        true => '是',
        false => '否',
    ];

    public static $weekdays = [
        '星期日', '星期一', '星期二', '星期三', '星期四', '星期五', '星期六',
    ];
}

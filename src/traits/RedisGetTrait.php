<?php

namespace lspbupt\common\traits;

use Yii;
use yii\base\UnknownMethodException;

/**
 * 调用方法的时候把get换成redis
 * 用于Compontent子类
 *
 * 需要 redis 组件
 *
 * 不会缓存null值
 * 不能用于relation
 */
trait RedisGetTrait
{
    public function extraPrimaryKey()
    {
        return [];
    }

    public static function redisFlush($name)
    {
        Yii::$app->redis->DEL(self::_getKeyPrefix($name));
    }

    public static function redisTTL($name)
    {
        Yii::$app->redis->TTL(self::_getKeyPrefix($name));
    }

    private static $refrethMode;

    public static function setRefrethMode($refreth = true)
    {
        if ($refreth) {
            self::$refrethMode = true;
        } else {
            self::$refrethMode = false;
        }
    }

    public function __call($name, $arguments)
    {
        $targetName = str_replace('redis', 'get', $name);

        if (strpos($name, 'redis') === 0 && $this->hasMethod($targetName)) {
            $prefix = self::_getKeyPrefix($name);
            $key = self::_getKey($name, array_merge($arguments, $this->extraPrimaryKey()));
            $cacheRes = Yii::$app->redis->HGET($prefix, $key);

            if (!isset($cacheRes) || self::$refrethMode) {
                $ret = call_user_func_array([$this, $targetName], $arguments);
                isset($ret) && self::_cache($ret, $prefix, $key);
            } else {
                $ret = self::_parseCacheRes($cacheRes);
            }

            return $ret;
        }
        foreach ($this->getBehaviors() as $object) {
            if ($object->hasMethod($name)) {
                return call_user_func_array([$object, $name], $arguments);
            }
        }
        throw new UnknownMethodException('Calling unknown method: ' . get_class($this) . "::$name()");
    }

    public static function __callStatic($name, $arguments)
    {
        if (strpos($name, 'redis') === 0 && method_exists(static::class, str_replace('redis', 'get', $name))) {
            $prefix = self::_getKeyPrefix($name);
            $key = self::_getKey($name, $arguments);
            $cacheRes = Yii::$app->redis->HGET($prefix, $key);
            if (!isset($cacheRes) || self::$refrethMode) {
                $ret = call_user_func_array([get_called_class(), str_replace('redis', 'get', $name)], $arguments);
                isset($ret) && self::_cache($ret, $prefix, $key);
            } else {
                $ret = self::_parseCacheRes($cacheRes);
            }

            return $ret;
        }
    }

    private static function _cache($ret, $prefix, $key)
    {
        $tmp = (is_object($ret) || is_array($ret)) ? json_encode($ret, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) : $ret;
        Yii::$app->redis->HSET($prefix, $key, $tmp);
        if (Yii::$app->redis->TTL($prefix) === '-1') {
            Yii::$app->redis->EXPIRE($prefix, self::_getCacheLife());
        }
    }

    private static function _parseCacheRes($cacheRes)
    {
        $tmp = @json_decode($cacheRes, true);
        if (json_last_error() == JSON_ERROR_NONE) {
            $ret = $tmp;
        } else {
            $ret = $cacheRes;
        }

        return $ret;
    }

    private static function _getKeyPrefix($name)
    {
        $key1 = str_replace('\\', ':', __TRAIT__);
        $ket2 = str_replace('\\', ':', __CLASS__);

        return $key1 . ':' . $ket2 . ':' . $name;
    }

    private static function _getKey($name, $arguments)
    {
        return $name . ':' . md5(json_encode([$arguments]));
    }

    private static function _getCacheLife()
    {
        return 1200;
    }
}

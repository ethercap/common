<?php

namespace ethercap\common\helpers;

class ArrayHelper extends \yii\helpers\ArrayHelper
{
    /**
     * toArray的扩展, 支持array转换成其他array, 用法和toArray一样 只是源变成了array
     * $arr = [
     *      ['k1' => '冯诺依曼.杨', 'k2' => 'R.I.P'],
     *      ['k1' => '尼古拉斯.张董', 'k2' => 'R.I.P', 'k3'=>99],
     * ];
     * $map = ['dumb1' => 'k1', 'dumb2' => function($temp) {return $temp['k2'] . ' in 1,000 years.';}, 'dumb3' => 'k3'];
     * output:
     * array(2) {
     *   [0]=>
     *       array(2) {
     *       ["dumb1"]=>
     *           string(16) "冯诺依曼.杨"
     *       ["dumb2"]=>
     *           string(21) "R.I.P in 1,000 years."
     *       ["dumb3"]=>
     *           NULL
     *   }
     *   [1]=>
     *       array(2) {
     *       ["dumb1"]=>
     *           string(19) "尼古拉斯.张董"
     *       ["dumb2"]=>
     *           string(21) "R.I.P in 1,000 years."
     *       ["dumb3"]=>
     *           int(99)
     *       }
     *   }
     *
     * @param $arr
     * @param $map
     *
     * @return $arr
     */
    public static function arrayToArray($arr, $map)
    {
        $result = [];
        if (is_array($arr) && !empty($arr)) {
            if (ArrayHelper::isIndexed($arr)) {
                foreach ($arr as $key => $subArr) {
                    $result[$key] = self::arrayToArray($subArr, $map);
                }
            } elseif (is_array($map) && !empty($map)) {
                $tempArr = [];
                foreach ($map as $key => $name) {
                    $newKey = is_int($key) ? $name : $key;
                    $tempArr[$newKey] = ArrayHelper::getValue($arr, $name);
                }
                return $tempArr;
            }
        }
        return $result;
    }

    /**
     * 目前还只支持 单个model, 利用getValue支持获取relation的属性
     * [
     *      Uniqproject::class => [
     *          'id',
     *          'xiniudata.id',
     *          'test' => function(){return 119;}
     *      ]
     * ]
     *
     * @param $model
     * @param $map
     */
    public static function singleModelToArray($model, $map)
    {
        $retArr = [];
        foreach ($map as $mainKey => $arrs) {
            foreach ($arrs as $key => $value) {
                $tempArr = [];
                if (is_callable($value)) {
                    $tempArr = ArrayHelper::toArray($model, [$mainKey => [$key => $value]]);
                } else {
                    $tempValue = ArrayHelper::getValue($model, $value);
                    if (is_string($key)) {
                        $tempArr[$key] = $tempValue;
                    } else {
                        $tempArr[$value] = $tempValue;
                    }
                }
                $retArr = array_merge($retArr, $tempArr);
            }
        }
        return $retArr;
    }

    /**
     * 转换key名
     * $arr=['k1'=>110, 'k2'=>119, 'k3'=>112];
     * $map = ['k1'=>'key1', 'k2'=>'key2',];
     */
    public static function renameKeys($arr, $map)
    {
        return self::arrayToArray($arr, array_flip($map));
    }

    /**
     * 仿照getValue 特殊处理一些空值 ''/NULL 等, 当字段对应的值为空的时候直接使用default值
     * 字符型的判空 数值型的认为0也是可接受的非空值
     *
     * @param array|object          $arr
     * @param array|\Closure|string $key
     * @param null                  $default
     */
    public static function getRightValue($arr, $key, $default = null)
    {
        $value = ArrayHelper::getValue($arr, $key, $default);
        if (is_numeric($value)) {
            is_null($value) && $value = $default;
        } else {
            empty($value) && $value = $default;
        }
        return $value;
    }

    /**
     * 根据唯一键值 来 合并两个数组, 会以leftArray为准
     *
     * @param array  $leftArray
     * @param array  $rightArray
     * @param string $indexKeyName
     */
    public static function mergeByIndexKey($leftArray, $rightArray, $indexKeyName = 'id')
    {
        $newArray = [];
        if ($leftArray) {
            $newRightArray = ArrayHelper::index($rightArray, $indexKeyName);
            foreach ($leftArray as $arr) {
                $index = ArrayHelper::getRightValue($arr, $indexKeyName, '');
                $correspondRightArr = ArrayHelper::getRightValue($newRightArray, $index, []);
                $arr = array_merge($arr, $correspondRightArr);
                $newArray[] = $arr;
            }
        }
        return $newArray;
    }

    /**
     * 以leftArray为基准, rightArray里面有的 并且leftArray里没有或者为empty的就merge进去 作为一种补充策略
     *
     * @param array $leftArray
     * @param array $rightArray
     */
    public static function mergeIfEmpty($leftArray, $rightArray)
    {
        if ($leftArray && $rightArray) {
            foreach ($rightArray as $key => $value) {
                if (!isset($leftArray[$key]) || empty($leftArray[$key])) {
                    $leftArray[$key] = $value;
                }
            }
        }
        return $leftArray;
    }

    /**
     * 主要避免array_merge中的讨厌的NULL导致报错的问题 trimNullArray
     *     如果有一个为NULL, 则返回非NULL的那个; 如果两个都为NULL, 那就返回[]
     *
     * @param array $leftArr
     * @param array $rightArr
     *
     * @return array
     */
    public static function safeMerge($leftArr, $rightArr)
    {
        $retArr = [];
        if (!is_null($leftArr) && !is_null($rightArr)) {
            $retArr = array_merge($leftArr, $rightArr);
        } elseif (is_null($leftArr)) {
            $retArr = $rightArr;
        } elseif (is_null($rightArr)) {
            $retArr = $leftArr;
        }
        return $retArr;
    }

    /**
     * model转换成数组的时候不用加上类名 直接从model里面取
     *
     * @param object | array $objects
     * @param array          $properties
     * @param bool           $recursive
     *
     * @return array
     */
    public static function modelToArray($objects, $properties = [], $recursive = true)
    {
        if (empty($objects)) {
            return self::toArray($objects, $properties, $recursive);
        }
        $topObject = $objects;
        if (is_array($objects) && ArrayHelper::isIndexed($objects)) {
            $topObject = $objects[0];
        }
        return self::toArray($objects, [get_class($topObject) => $properties], $recursive);
    }

    // 采用render的方式来渲染
    public static function renderToArray($model, $attrs = null, $modelResponse = true)
    {
        if (is_null($attrs)) {
            $attrs = array_keys($model->attributes);
            if (method_exists($model, 'getAttrProperties')) {
                $attrs = array_merge($attrs, array_keys($model->getAttrProperties()));
            }
        }
        $serialize = new \ethercap\apiBase\components\Serializer([
            'useModelResponse' => $modelResponse,
            'columns' => $attrs,
            'addConfig' => true,
        ]);
        return $serialize->serializeModel($model);
    }
}

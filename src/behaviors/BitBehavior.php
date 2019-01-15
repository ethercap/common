<?php

namespace ethercap\common\behaviors;

use yii\helpers\ArrayHelper;
use yii\base\Behavior;
use yii\db\ActiveRecord;

/**
 * Class BitBehavior
 *
 * @property ActiveRecord $owner
 *                               ```php
 *                               use ethercap\common\behaviors\BitBehavior;
 *
 * public function behaviors()
 * {
 *     return [
 *           [
 *               'class' => BitBehavior::class,
 *               'bitMaps' => [
 *                   'attr1' => [
 *                        0 => 'item0',
 *                        1 => 'item1',
 *                   ],
 *                   'attr2' => [
 *                        0 => 'itemA',
 *                        1 => 'itemB'
 *                   ],
 *               ],
 *           ],
 *     ];
 * }
 * ```
 */
class BitBehavior extends Behavior
{
    public $bitMaps = [];

    /**
     * @var array | null
     *
     * ```php
     * $_bitmap = [
     *  'item0' => ['attr1', 0], // ['column', 'shift']
     *  'item1' => ['attr1', 1],
     * ]
     * ```
     */
    private $_bitmap = null;

    private $_properties = [];

    public function init()
    {
        parent::init();
        foreach ($this->bitMaps as $map) {
            foreach ($map as $item) {
                $this->_properties[] = $item;
            }
        }
    }

    public function isBit($item, $alias = '')
    {
        list($bitKey, $shift) = self::_getOffset($item);
        if ($shift < 0) {
            return '';
        }
        return ($alias ? $alias.'.' : '') . "`{$bitKey}` & ".(1 << $shift);
    }

    public function isnotBit($item, $alias = '')
    {
        list($bitKey, $shift) = self::_getOffset($item);
        if ($shift < 0) {
            return '';
        }
        return ($alias ? $alias.'.' : '') . "`{$bitKey}` & ".(1 << $shift).' = 0';
    }

    public function canGetProperty($name, $checkVars = true)
    {
        if (strncmp($name, 'is', 2) !== 0) {
            return false;
        }

        $name = lcfirst(substr($name, 2));
        return is_array($this->_properties) && in_array($name, $this->_properties);
    }

    public function canSetProperty($name, $checkVars = true)
    {
        if (strncmp($name, 'is', 2) !== 0) {
            return false;
        }

        $name = lcfirst(substr($name, 2));
        return is_array($this->_properties) && in_array($name, $this->_properties);
    }

    public function __get($name)
    {
        $name = lcfirst(substr($name, 2));
        return $this->_getBit($name);
    }

    public function __set($name, $value)
    {
        $name = lcfirst(substr($name, 2));
        return $this->_setBit($name, $value);
    }

    private function _getBit($item, $defaultVal = true)
    {
        list($bitKey, $shift) = $this->_getAndCacheOffset($item);
        if ($shift < 0) {
            return $defaultVal;
        }

        return (bool) ($this->owner->$bitKey & (1 << $shift));
    }

    private function _setBit($item, $value)
    {
        list($bitKey, $shift) = $this->_getAndCacheOffset($item);
        if ($shift < 0) {
            return false;
        }

        if ($value) {
            $this->owner->$bitKey |= (1 << $shift);
        } else {
            $this->owner->$bitKey &= ~(1 << $shift);
        }
    }

    private function _getAndCacheOffset($item)
    {
        if ($this->_bitmap === null) {
            $this->_bitmap = $this->_getBitmap();
        }

        return $this->_getOffset($item, $this->_bitmap);
    }

    private function _getOffset($item, $map = null)
    {
        if ($map === null) {
            $map = $this->_getBitmap();
        }
        if (isset($map[$item])) {
            return $map[$item];
        }

        return [null, -1];
    }

    private function _getBitmap()
    {
        $map = [];
        $config = array_keys($this->bitMaps);
        foreach ($config as $column) {
            $map = array_merge($map, self::_getConf($column));
        }

        return $map;
    }

    private function _getConf($column)
    {
        if (!$column) {
            return [];
        }
        $colConf = ArrayHelper::getValue($this->bitMaps, $column, []);
        $ret = [];
        foreach ($colConf as $offset => $key) {
            $ret[$key] = [$column, $offset];
        }
        return $ret;
    }
}

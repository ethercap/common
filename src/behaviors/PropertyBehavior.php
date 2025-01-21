<?php

namespace ethercap\common\behaviors;

use yii\helpers\StringHelper;

/**
 *  提供getter/setter方法,可以通过 $model->attribute{ending}获取/改变值
 */
abstract class PropertyBehavior extends \yii\base\Behavior
{
    public $ending = '_desc';
    public $defaultValue = null;
    public $attributes = [];

    public $canSet = true;
    public $canGet = true;

    public function init()
    {
        parent::init();
    }

    public function __get($name)
    {
        if ($name = $this->getAttributeByStr($name)) {
            return $this->getValueByName($name);
        }
        return parent::__get($name);
    }

    public function __set($name, $value)
    {
        if ($name = $this->getAttributeByStr($name)) {
            return $this->setValueByName($name, $value);
        }
        return parent::__set($name, $value);
    }

    abstract protected function getValueByName($name);

    abstract protected function setValueByName($name, $value);

    protected function getAttributeByStr($name, $ending = null)
    {
        empty($ending) && $ending = $this->ending;
        if (StringHelper::endsWith($name, $ending)) {
            $name = substr($name, 0, -1 * strlen($ending));
            if (in_array($name, $this->attributes)) {
                return $name;
            }
        }
        return null;
    }

    public function canGetProperty($name, $checkVars = true)
    {
        $name = $this->getAttributeByStr($name);
        if ($name) {
            return $this->canGet;
        }
        return parent::canGetProperty($name, $checkVars);
    }

    public function canSetProperty($name, $checkVars = true)
    {
        if ($name = $this->getAttributeByStr($name)) {
            return $this->canSet;
        }
        return parent::canSetProperty($name, $checkVars);
    }
}

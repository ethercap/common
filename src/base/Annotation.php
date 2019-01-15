<?php

namespace ethercap\common\base;

use Doctrine\Common\Annotations\AnnotationReader;

class Annotation extends \yii\base\Component
{
    public static $reader;

    public static function getReader(): AnnotationReader
    {
        if (static::$reader === null) {
            $reader = new AnnotationReader();
            static::$reader = $reader;
        }
        return static::$reader;
    }

    public static function parse($class, $attr)
    {
        if (!property_exists($class, $attr)) {
            return null;
        }
        $property = new \ReflectionProperty($class, $attr);
        return self::getReader()->getPropertyAnnotation($property, self::className());
    }
}

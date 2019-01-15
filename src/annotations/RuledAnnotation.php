<?php

namespace ethercap\common\annotations;

use ethercap\common\base\Annotation;

/**
 * @Annotation
 */
class RuledAnnotation extends Annotation
{
    public $placeholder;
    public $hint;
    public $staticValue;
}

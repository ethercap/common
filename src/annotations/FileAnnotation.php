<?php

namespace ethercap\common\annotations;

use ethercap\common\base\Annotation;

/**
 * @Annotation
 */
class FileAnnotation extends Annotation
{
    public $maxFileCount = 1;
    public $attachAppend = true;
    public $inputId = 'fileinput';
    public $previewStringPrefix = '';
    public $uploadUrl;
    public $oss = 'in_oss';
    public $bucket;
    public $object;
}

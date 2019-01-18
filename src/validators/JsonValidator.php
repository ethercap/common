<?php

namespace ethercap\common\validators;

use Yii;
use yii\validators\Validator;

/**
 * JsonValidator validates that the attribute value is a valid Json
 */
class JsonValidator extends Validator
{
    /*  @var $noString string not a valid string */
    public $noString;
    /*  @var $jsonErrorDepth string json too deep */
    public $jsonErrorDepth;
    /*  @var $jsonErrorCtrlChar */
    public $jsonErrorCtrlChar;
    /*  @var $jsonErrorSyntax */
    public $jsonErrorSyntax;
    /*  @var $enableJsonError bool */
    public $enableJsonError = true;

    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();
        if ($this->message === null) {
            $this->message = Yii::t('yii', '{attribute} is not a valid Json.');
        }
        if ($this->noString === null) {
            $this->noString = Yii::t('yii', '{attribute} is not a string.');
        }
        if ($this->jsonErrorDepth === null) {
            $this->jsonErrorDepth = Yii::t('yii', '{attribute} is not a valid Json. Maximum stack depth exceeded.');
        }
        if ($this->jsonErrorCtrlChar === null) {
            $this->jsonErrorCtrlChar = Yii::t('yii', '{attribute} is not a valid Json. Unexpected control character found.');
        }
        if ($this->jsonErrorSyntax === null) {
            $this->jsonErrorSyntax = Yii::t('yii', '{attribute} is not a valid Json. Syntax error, malformed JSON.');
        }
    }

    /**
     * @inheritdoc
     */
    public function validateAttribute($model, $attribute)
    {
        $value = $model->$attribute;
        $result = $this->validateValue($value);
        if (!empty($result)) {
            $this->addError($model, $attribute, $result[0], $result[1]);
        }
    }

    /**
     * @inheritdoc
     */
    protected function validateValue($value)
    {
        if (!is_string($value)) {
            return [$this->noString, []];
        }
        // in PHP, json_decode return NULL if anything wrong
        $ret = @json_decode($value, true);
        $jsonLastError = json_last_error();
        if ($this->enableJsonError) {
            switch ($jsonLastError) {
                case JSON_ERROR_DEPTH:
                    return [$this->jsonErrorDepth, []];
                case JSON_ERROR_CTRL_CHAR:
                    return [$this->jsonErrorCtrlChar, []];
                case JSON_ERROR_SYNTAX:
                    return [$this->jsonErrorSyntax, []];
            }
        }
        if ($jsonLastError !== JSON_ERROR_NONE) {
            return [$this->message, []];
        }
        return null;
    }
}

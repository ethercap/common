<?php

namespace lspbupt\common\validators;

class ArrayValidator extends DictValidator
{
    public $elements;
    public $keys;
    public $keyIn;

    public function init()
    {
        $this->enableClientValidation = false;
        $this->multiple = true;
        if ($this->keyIn !== null && is_array($this->keyIn)) {
            $this->multiple = false;
            $this->list = $this->keyIn;
        }
        if ($this->elements !== null && is_array($this->elements)) {
            $this->multiple = true;
            $this->list = $this->elements;
        }
        if ($this->keys !== null && is_array($this->keys)) {
            $this->multiple = true;
            $this->list = $this->keys;
        }
        parent::init();
    }
}

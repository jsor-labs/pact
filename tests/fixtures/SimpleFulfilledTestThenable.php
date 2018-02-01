<?php

namespace Pact;

class SimpleFulfilledTestThenable
{
    private $value;

    public function __construct($value)
    {
        $this->value = $value;
    }

    public function then($onFulfilled = null, $onRejected = null)
    {
        if ($onFulfilled) {
            \call_user_func($onFulfilled, $this->value);
        }

        return new self($this->value);
    }
}

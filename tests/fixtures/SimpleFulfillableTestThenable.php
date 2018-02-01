<?php

namespace Pact;

class SimpleFulfillableTestThenable
{
    private $callbacks = array();

    public function then($onFulfilled = null, $onRejected = null)
    {
        if ($onFulfilled) {
            $this->callbacks[] = $onFulfilled;
        }

        return new self();
    }

    public function fulfill($value)
    {
        foreach ($this->callbacks as $callback) {
            \call_user_func($callback, $value);
        }
    }
}

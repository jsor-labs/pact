<?php

namespace Pact;

class SimpleRejectableTestThenable
{
    private $callbacks = [];

    public function then($onFulfilled = null, $onRejected = null)
    {
        if ($onRejected) {
            $this->callbacks[] = $onRejected;
        }

        return new self();
    }

    public function reject($reason)
    {
        foreach ($this->callbacks as $callback) {
            \call_user_func($callback, $reason);
        }
    }
}

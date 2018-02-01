<?php

namespace Pact;

class SimpleRejectedTestThenable
{
    private $reason;

    public function __construct($reason)
    {
        $this->reason = $reason;
    }

    public function then($onFulfilled = null, $onRejected = null)
    {
        if ($onRejected) {
            \call_user_func($onRejected, $this->reason);
        }

        return new self($this->reason);
    }
}

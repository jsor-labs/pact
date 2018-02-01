<?php

namespace Pact;

class SimpleCancellableTestThenable
{
    public $cancelCalled = false;
    public $onCancel;

    public function __construct(callable $onCancel = null)
    {
        $this->onCancel = $onCancel;
    }

    public function then($onFulfilled = null, $onRejected = null)
    {
        return new self();
    }

    public function cancel()
    {
        $this->cancelCalled = true;

        if (\is_callable($this->onCancel)) {
            \call_user_func($this->onCancel);
        }
    }
}

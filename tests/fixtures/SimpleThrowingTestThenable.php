<?php

namespace Pact;

class SimpleThrowingTestThenable
{
    private $exception;

    public function __construct($exception)
    {
        $this->exception = $exception;
    }

    public function then($onFulfilled = null, $onRejected = null)
    {
        throw $this->exception;
    }
}

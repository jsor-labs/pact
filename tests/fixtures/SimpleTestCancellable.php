<?php

namespace Pact;

class SimpleTestCancellable
{
    public $cancelCalled = false;

    public function cancel()
    {
        $this->cancelCalled = true;
    }
}

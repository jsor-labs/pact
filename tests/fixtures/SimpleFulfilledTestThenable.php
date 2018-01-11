<?php

namespace Pact;

class SimpleFulfilledTestThenable
{
    public function then($onFulfilled = null, $onRejected = null)
    {
        if ($onFulfilled) {
            \call_user_func($onFulfilled, 'foo');
        }

        return new self();
    }
}

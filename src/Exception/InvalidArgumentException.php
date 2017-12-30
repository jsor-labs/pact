<?php

namespace Pact\Exception;

class InvalidArgumentException extends \InvalidArgumentException
{
    public static function invalidResolver($resolver)
    {
        return new InvalidArgumentException(
            \sprintf(
                'The resolver argument must be of type callable, %s given.',
                \is_object($resolver) ? \get_class($resolver) : \gettype($resolver)
            )
        );
    }

    public static function invalidCanceller($canceller)
    {
        return new InvalidArgumentException(
            \sprintf(
                'The canceller argument must be null or of type callable, %s given.',
                \is_object($canceller) ? \get_class($canceller) : \gettype($canceller)
            )
        );
    }

    public static function invalidThenFulfillmentCallback($onFulfilled)
    {
        return new InvalidArgumentException(
            \sprintf(
                'The $onFulfilled argument passed to then() must be null or callable, %s given.',
                \is_object($onFulfilled) ? \get_class($onFulfilled) : \gettype($onFulfilled)
            )
        );
    }

    public static function invalidThenRejectionCallback($onRejected)
    {
        return new InvalidArgumentException(
            \sprintf(
                'The $onRejected argument passed to then() must be null or callable, %s given.',
                \is_object($onRejected) ? \get_class($onRejected) : \gettype($onRejected)
            )
        );
    }

    public static function nonThrowableRejection($reason)
    {
        return new InvalidArgumentException(
            \sprintf(
                'The rejection reason must be of type \Throwable or \Exception, %s given.',
                \is_object($reason) ? \get_class($reason) : \gettype($reason)
            )
        );
    }
}

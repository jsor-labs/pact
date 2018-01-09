<?php

namespace Pact\Exception;

class InvalidArgumentException extends \InvalidArgumentException
{
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

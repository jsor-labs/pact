<?php

namespace Pact\Exception;

class LogicException extends \LogicException
{
    public static function circularResolution()
    {
        return new LogicException(
            'Cannot resolve a promise with itself.'
        );
    }

    public static function valueFromNonFulfilledPromise()
    {
        return new LogicException(
            'Cannot get the fulfillment value of a non-fulfilled promise.'
        );
    }

    public static function reasonFromNonRejectedPromise()
    {
        return new LogicException(
            'Cannot get the rejection reason of a non-rejected promise.'
        );
    }
}

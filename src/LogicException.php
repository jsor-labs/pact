<?php

namespace Pact;

final class LogicException extends \LogicException implements PactThrowable
{
    public static function circularResolution()
    {
        return new self(
            'Cannot resolve a promise with itself.'
        );
    }

    public static function valueFromNonFulfilledPromise()
    {
        return new self(
            'Cannot get the fulfillment value of a non-fulfilled promise.'
        );
    }

    public static function reasonFromNonRejectedPromise()
    {
        return new self(
            'Cannot get the rejection reason of a non-rejected promise.'
        );
    }
}

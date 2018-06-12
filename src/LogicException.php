<?php

namespace Pact;

final class LogicException extends \LogicException implements PactThrowable
{
    public static function circularResolution(): self
    {
        return new self(
            'Cannot resolve a promise with itself.'
        );
    }

    public static function valueFromNonFulfilledPromise(): self
    {
        return new self(
            'Cannot get the fulfillment value of a non-fulfilled promise.'
        );
    }

    public static function reasonFromNonRejectedPromise(): self
    {
        return new self(
            'Cannot get the rejection reason of a non-rejected promise.'
        );
    }

    public static function disableRejectionTrackerWithUnhandledRejections()
    {
        return new self(
            'Cannot disable rejection tracker while it has unhandled rejections.'
        );
    }
}

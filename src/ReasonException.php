<?php

namespace Pact;

final class ReasonException extends \RuntimeException implements PactThrowable
{
    private $reason;
    private $hasReason = false;

    public static function createWithoutReason()
    {
        return new self('Promise rejected without a reason.');
    }

    public static function createForReason($reason)
    {
        if (null === $reason) {
            $value = '<NULL>';
        } elseif (\is_bool($reason)) {
            $value = $reason ? '<TRUE>' : '<FALSE>';
        } elseif (\is_float($reason) || \is_int($reason)) {
            $value = (string) $reason;
        } elseif (\is_array($reason)) {
            $value = '<ARRAY>';
        } elseif (\is_resource($reason)) {
            $value = \get_resource_type($reason);
        } elseif (\is_object($reason) && !\method_exists($reason, '__toString')) {
            $value = \get_class($reason);
        } else {
            $value = "\"$reason\"";
        }

        $exception = new self("Promise rejected with reason $value.");

        $exception->reason = $reason;
        $exception->hasReason = true;

        return $exception;
    }

    public function hasReason()
    {
        return $this->hasReason;
    }

    public function getReason()
    {
        return $this->reason;
    }
}

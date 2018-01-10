<?php

namespace Pact;

final class ReasonException extends \RuntimeException
{
    private $reason;
    private $hasReason = false;

    public static function createWithoutReason()
    {
        return new self('Promise rejected without a reason.');
    }

    public static function createForReason($reason)
    {
        $message = 'Promise rejected with reason %s.';

        if (\is_bool($reason)) {
            $value = $reason ? '<TRUE>' : '<FALSE>';
        } elseif (\is_array($reason)) {
            $value = '<ARRAY>';
        } elseif (\is_object($reason) && !\method_exists($reason, '__toString')) {
            $value = \get_class($reason);
        } elseif (\is_resource($reason)) {
            $value = \get_resource_type($reason);
        } elseif (null === $reason) {
            $value = '<NULL>';
        } else {
            $value = (string) $reason;
        }

        $exception = new self(
            sprintf($message, $value)
        );

        $exception->reason = $reason;
        $exception->hasReason = true;

        return $exception;
    }

    /**
     * @internal
     */
    public function __construct($message)
    {
        parent::__construct($message);
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

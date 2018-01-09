<?php

namespace Pact;

class ReasonException extends \RuntimeException
{
    private $reason;

    public function __construct($reason)
    {
        $this->reason = $reason;

        $message = 'Promise rejected with ';

        if (\is_bool($reason)) {
            $message .= $reason ? '<TRUE>' : '<FALSE>';
        } elseif (\is_array($reason)) {
            $message .= '<ARRAY>';
        } elseif (\is_object($reason) && !method_exists($reason, '__toString')) {
            $message .= \get_class($reason);
        } elseif (\is_resource($reason)) {
            $message .= \get_resource_type($reason);
        } elseif (null === $reason) {
            $message .= '<NULL>';
        } else {
            $message .= (string) $reason;
        }

        parent::__construct($message);
    }

    public function getReason()
    {
        return $this->reason;
    }
}

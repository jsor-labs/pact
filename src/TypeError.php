<?php

namespace Pact;

final class TypeError extends \TypeError
{
    public static function createForNonClassTypeHintArgument
    (
        $template,
        $method,
        $arg
    ) {
        return self::create(
            $template,
            $method,
            \strtolower(\gettype($arg))
        );
    }

    public static function createForClassTypeHintArgument
    (
        $template,
        $method,
        $arg
    ) {
        return self::create(
            $template,
            $method,
            \is_object($arg) ? 'instance of ' . \get_class($arg) : \strtolower(\gettype($arg))
        );
    }

    public static function create
    (
        $template,
        $method,
        $argType
    ) {
        $file = '(n/a)';
        $line = '(n/a)';

        if (PHP_VERSION_ID >= 54000) {
            // Second parameter available since 5.4.0
            $trace = \debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 10);
        } else {
            // DEBUG_BACKTRACE_IGNORE_ARGS available since 5.3.6
            $trace = \defined('DEBUG_BACKTRACE_IGNORE_ARGS')
                ? \debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS)
                : \debug_backtrace(false);
        }

        foreach ($trace as $step) {
            $traceMethod = $step['function'];

            if (!empty($step['class'])) {
                $traceMethod = $step['class'] . '::' . $traceMethod;
            }

            if ($traceMethod !== $method) {
                continue;
            }

            if (isset($step['file'])) {
                $file = $step['file'];
            }

            if (isset($step['line'])) {
                $line = $step['line'];
            }

            break;
        }

        return new TypeError(
            \sprintf(
                $template,
                $method,
                $argType,
                $file,
                $line
            )
        );
    }
}

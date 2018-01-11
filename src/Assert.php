<?php

namespace Pact;

final class Assert
{
    private static $enabled = true;

    public static function enable()
    {
        self::$enabled = true;
    }

    public static function disable()
    {
        self::$enabled = false;
    }

    /**
     * @internal
     */
    public static function callback($callback)
    {
        if (!self::$enabled || !\assert_options(\ASSERT_ACTIVE)) {
            return true;
        }

        $parameters = \func_get_args();
        \array_shift($parameters);

        return true === \call_user_func_array($callback, $parameters);
    }

    /**
     * @internal
     */
    public static function descriptionForTypeHintedArgument(
        $message,
        $method,
        $arg
    ) {
        return self::createDescription(
            $message,
            $method,
            \strtolower(\gettype($arg))
        );
    }

    /**
     * @internal
     */
    public static function descriptionForClassTypeHintedArgument(
        $message,
        $method,
        $arg
    ) {
        return self::createDescription(
            $message,
            $method,
            \is_object($arg) ? 'instance of ' . \get_class($arg) : \strtolower(\gettype($arg))
        );
    }

    private static function createDescription(
        $message,
        $method,
        $argType
    ) {
        $description = "$message, $argType given";

        $file = null;
        $line = null;

        if (\PHP_VERSION_ID >= 50400) {
            // Second parameter available since 5.4.0
            $trace = \debug_backtrace(\DEBUG_BACKTRACE_IGNORE_ARGS, 10);
        } else {
            // DEBUG_BACKTRACE_IGNORE_ARGS available since 5.3.6
            $trace = \defined('DEBUG_BACKTRACE_IGNORE_ARGS')
                ? \debug_backtrace(\DEBUG_BACKTRACE_IGNORE_ARGS)
                : \debug_backtrace(false);
        }

        foreach ($trace as $step) {
            $traceMethod = $step['function'];

            if (!empty($step['class'])) {
                $traceMethod = "{$step['class']}::$traceMethod";
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

        if (null !== $file || null !== $line) {
            $fileDesc = '';
            $lineDesc = '';

            if (null !== $file) {
                $fileDesc .= " in $file";
            }

            if (null !== $line) {
                $lineDesc = " on line $line";
            }

            $description = "$description, called$fileDesc$lineDesc";
        }

        return $description;
    }
}

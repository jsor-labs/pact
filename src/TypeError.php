<?php

namespace {
    // Polyfill for Error classes in PHP 5.
    // class_exists() ensures other polyfills (eg. symfony/polyfill-php70) are
    // preferred and loaded before.
    if (!\class_exists('\Error')) {
        class Error extends Exception
        {
        }
    }

    if (!\class_exists('\TypeError')) {
        class TypeError extends Error
        {
        }
    }
}

namespace Pact {
    final class TypeError extends \TypeError implements PactThrowable
    {
        public static function messageForIncorrectArgumentType(
            $message,
            $method,
            $arg
        ) {
            return self::createMessage(
                $message,
                $method,
                \strtolower(\gettype($arg))
            );
        }

        public static function messageForIncorrectArgumentClassType(
            $message,
            $method,
            $arg
        ) {
            return self::createMessage(
                $message,
                $method,
                \is_object($arg) ? 'instance of ' . \get_class($arg) : \strtolower(\gettype($arg))
            );
        }

        private static function createMessage(
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
}

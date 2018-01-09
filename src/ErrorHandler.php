<?php

namespace Pact;

final class ErrorHandler
{
    /**
     * @internal
     */
    public static function warning($warning)
    {
        try {
            \trigger_error($warning, \E_USER_WARNING);
        } catch (\Exception $e) {
            \set_error_handler(function() { return false; });
            \trigger_error($warning, \E_USER_WARNING);
            \restore_error_handler();
        } catch (\Throwable $e) {
            \set_error_handler(function() { return false; });
            \trigger_error($warning, \E_USER_WARNING);
            \restore_error_handler();
        }
    }

    /**
     * @internal
     */
    public static function error($error)
    {
        try {
            \trigger_error($error, \E_USER_ERROR);
        } catch (\Exception $e) {
            \set_error_handler(function() { return false; });
            \trigger_error($error, \E_USER_ERROR);
        } catch (\Throwable $e) {
            \set_error_handler(function() { return false; });
            \trigger_error($error, \E_USER_ERROR);
        }
    }
}

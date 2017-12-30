<?php

namespace Pact;

final class ErrorCollector
{
    private $errors = array();

    public function start()
    {
        $errors = array();

        set_error_handler(function ($errno, $errstr, $errfile, $errline, $errcontext) use (&$errors) {
            $errors[] = compact('errno', 'errstr', 'errfile', 'errline', 'errcontext');
        });

        $this->errors = &$errors;
    }

    public function stop()
    {
        $errors = $this->errors;
        $this->errors = array();

        restore_error_handler();

        return $errors;
    }
}

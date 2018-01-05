--SKIPIF--
<?php if (PHP_VERSION_ID < 70000) die("Skipped: PHP ^7.0 required."); ?>
--TEST--
ErrorHandler::error() triggers error when native handler throws error
--FILE--
<?php

require __DIR__ . "/../../vendor/autoload.php";

set_error_handler(function ($e) { throw new Error(); });
Pact\ErrorHandler::error(new Error('Test error'));

?>
--EXPECTF--
Fatal error: %s in %s:%d
Stack trace:
#0 {main} in %s on line %d

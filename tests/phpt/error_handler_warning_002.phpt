--TEST--
ErrorHandler::warning() triggers warning when native handler throws exception
--FILE--
<?php

require __DIR__ . "/../../vendor/autoload.php";

set_error_handler(function ($e) { throw new Exception(); });
Pact\ErrorHandler::warning(new Exception('Test warning'));

?>
--EXPECTF--
Warning: %s in %s:%d
Stack trace:
#0 {main} in %s on line %d

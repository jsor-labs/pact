--TEST--
ErrorHandler::error() triggers error when native handler throws exception
--FILE--
<?php

require __DIR__ . "/../../vendor/autoload.php";

set_error_handler(function ($e) { throw new Exception(); });
Pact\ErrorHandler::error(new Exception('Test error'));

?>
--EXPECTF--
Fatal error: %s in %s:%d
Stack trace:
#0 {main} in %s on line %d

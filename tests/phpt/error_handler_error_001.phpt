--TEST--
ErrorHandler::error() triggers error
--FILE--
<?php

require __DIR__ . "/../../vendor/autoload.php";

Pact\ErrorHandler::error(new Exception('Test error'));

?>
--EXPECTF--
Fatal error: %s in %s:%d
Stack trace:
#0 {main} in %s on line %d

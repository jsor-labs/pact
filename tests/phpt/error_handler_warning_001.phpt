--TEST--
ErrorHandler::warning() triggers warning
--FILE--
<?php

require __DIR__ . "/../../vendor/autoload.php";

Pact\ErrorHandler::warning(new Exception('Test warning'));

?>
--EXPECTF--
Warning: %s in %s:%d
Stack trace:
#0 {main} in %s on line %d

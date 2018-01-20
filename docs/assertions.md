Assertions
==========

This library does not use 
[type declarations](http://php.net/functions.arguments#functions.arguments.type-declaration) 
but [assert()](http://php.net/assert) to check the types of arguments passed to
the following functions.

* `Promise::reject(\Throwable $reason)` for PHP 7, 
  `Promise::reject(\Exception $reason)` for PHP 5
* `Promise::then(callable $onFulfilled = null, callable $onRejected = null)`
* `Promise::always(callable $onSettled)`

The behaviour of assertions can be controlled with
[`assert_opions()`](http://php.net/assert_options) in both PHP 5 and 7 or with 
[php.ini directives](http://php.net/assert#function.assert.expectations) in
PHP 7.

In PHP 7, assertions can be made zero-cost when running with
`zend.assertions = -1` ([Docs](http://php.net/ini.core#ini.zend.assertions)).

Also, setting `assert.exception = 1` in PHP 7 will throw a `Pact\TypeError`
extending the native `\TypeError` when the assertion fails instead of triggering
a warning ([Docs](http://php.net/info.configuration#ini.assert.exception)).

The thrown `Pact\TypeError` tries to replicate the native `\TypeError` in
PHP 7 if an argument does not match a native function type declaration as close
as possible.

When running on 5.4.8 or higher, this library uses the second parameter 
`description` of `assert()` to trigger a more meaningful message similar to
the message of `\TypeError`'s in PHP 7 if an argument does not match a native
function type declaration.

This library is fully functional with disabled assertions. The behaviour is
as follows:

* `Promise::reject(\Throwable|\Exception $reason)`

  If `$reason` is not an instance of `\Throwable` in PHP 7 or an instance of
  `\Exception` in PHP 5, it will be wrapped in `Pact\ReasonException`.

  This ensures that `$reason` passed as argument to the `$onRejected` callback
  of `then()` is always a `\Throwable` (or `\Exception` in PHP 5) and it is safe
  to `throw $reason` inside the callback.
* `Promise::then(callable $onFulfilled = null, callable $onRejected = null)`

  If both `$onFulfilled` or `$onRejected` are not a `callable` or `null`, they
  will be ignored.
* `Promise::always(callable $onSettled)`

  If `$onSettled` is not a `callable`, it will be ignored.

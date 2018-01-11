<?php

namespace Pact;

/**
 * @requires PHP 7
 */
class AssertActiveExceptionTest extends TestCase
{
    private $assertActive;
    private $assertException;

    public function setUp()
    {
        if (\PHP_VERSION_ID >= 70000 && !\ini_get('zend.assertions')) {
            $this->markTestSkipped('Assertions disabled with zend.assertions=0, run with `php -dzend.assertions=1 vendor/bin/phpunit`.');
        }

        $this->assertActive = \assert_options(ASSERT_ACTIVE, 1);
        $this->assertException = \assert_options(ASSERT_EXCEPTION, 1);

        parent::setUp();
    }

    public function tearDown()
    {
        \assert_options(ASSERT_ACTIVE, $this->assertActive);
        \assert_options(ASSERT_EXCEPTION, $this->assertException);

        parent::tearDown();
    }

    /**
     * @test
     * @dataProvider invalidCallbackDataProvider
     */
    public function it_throws_from_constructor_for_invalid_resolver($invalidCallable, $type)
    {
        $this->setExpectedExceptionRegExp(
            'Pact\TypeError',
            '/^' . \preg_quote('Argument 1 passed to Pact\Promise::__construct() must be callable or null, ' . $type . ' given, called in ' . __FILE__ . ' on line ' . (__LINE__ + 3), '/') . '/'
        );

        new Promise($invalidCallable);
    }

    /**
     * @test
     * @dataProvider invalidCallbackDataProvider
     */
    public function it_throws_from_constructor_for_invalid_canceller($invalidCallable, $type)
    {
        $this->setExpectedExceptionRegExp(
            'Pact\TypeError',
            '/^' . \preg_quote('Argument 2 passed to Pact\Promise::__construct() must be callable or null, ' . $type . ' given, called in ' . __FILE__ . ' on line ' . (__LINE__ + 3), '/') . '/'
        );

        new Promise(null, $invalidCallable);
    }

    /**
     * @test
     * @dataProvider invalidReasonProvider
     **/
    public function it_throws_from_reject_for_invalid_rejection_reason($invalidReason, $type)
    {
        if (PHP_VERSION_ID < 70000) {
            $regexp = '/^' . \preg_quote('Argument 1 passed to Pact\Promise::reject() must be an instance of Exception, ' . $type . ' given, called in ' . __FILE__ . ' on line ' . (__LINE__ + 10), '/') . '/';
        } else {
            $regexp = '/^' . \preg_quote('Argument 1 passed to Pact\Promise::reject() must implement interface Throwable, ' . $type . ' given, called in ' . __FILE__ . ' on line ' . (__LINE__ + 8), '/') . '/';
        }

        $this->setExpectedExceptionRegExp(
            'Pact\TypeError',
            $regexp
        );

        Promise::reject($invalidReason);
    }

    /**
     * @test
     * @dataProvider invalidCallbackDataProvider
     **/
    public function it_throws_from_then_for_invalid_fulfillment_callback($invalidCallable, $type)
    {
        $this->setExpectedExceptionRegExp(
            'Pact\TypeError',
            '/^' . \preg_quote('Argument 1 passed to Pact\Promise::then() must be callable or null, ' . $type . ' given, called in ' . __FILE__ . ' on line ' . (__LINE__ + 4), '/') . '/'
        );

        $promise = Promise::resolve();
        $promise->then($invalidCallable);
    }

    /**
     * @test
     * @dataProvider invalidCallbackDataProvider
     **/
    public function it_throws_from_then_for_invalid_rejection_callback($invalidCallable, $type)
    {
        $this->setExpectedExceptionRegExp(
            'Pact\TypeError',
            '/^' . \preg_quote('Argument 2 passed to Pact\Promise::then() must be callable or null, ' . $type . ' given, called in ' . __FILE__ . ' on line ' . (__LINE__ + 4), '/') . '/'
        );

        $promise = Promise::reject(new \Exception());
        $promise->then(null, $invalidCallable);
    }

    /**
     * @test
     * @dataProvider invalidCallbackDataProvider
     **/
    public function it_throws_from_always_for_invalid_callback($invalidCallable, $type)
    {
        $this->setExpectedExceptionRegExp(
            'Pact\TypeError',
            '/^' . \preg_quote('Argument 1 passed to Pact\Promise::always() must be callable, ' . $type . ' given, called in ' . __FILE__ . ' on line ' . (__LINE__ + 4), '/') . '/'
        );

        $promise = Promise::resolve();
        $promise->always($invalidCallable);
    }
}

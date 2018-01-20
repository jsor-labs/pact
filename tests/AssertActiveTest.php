<?php

namespace Pact;

class AssertActiveTest extends TestCase
{
    private $assertActive;
    private $assertWarning;
    private $assertException;

    public function setUp()
    {
        if (\PHP_VERSION_ID >= 70000 && \ini_get('zend.assertions') < 1) {
            $this->markTestSkipped('Assertions disabled with zend.assertions=' . \ini_get('zend.assertions'));
        }

        $this->assertActive = \assert_options(ASSERT_ACTIVE, 1);
        $this->assertWarning = \assert_options(ASSERT_WARNING, 1);

        if (\PHP_VERSION_ID >= 70000) {
            $this->assertException = \assert_options(ASSERT_EXCEPTION, 0);
        }

        parent::setUp();
    }

    public function tearDown()
    {
        \assert_options(ASSERT_ACTIVE, $this->assertActive);
        \assert_options(ASSERT_WARNING, $this->assertWarning);

        if (\PHP_VERSION_ID >= 70000) {
            \assert_options(ASSERT_EXCEPTION, $this->assertException);
        }

        parent::tearDown();
    }

    /**
     * @test
     * @dataProvider invalidReasonProvider
     **/
    public function it_triggers_warning_from_reject_for_invalid_rejection_reason($invalidReason, $type)
    {
        if (\PHP_VERSION_ID >= 50408) {
            if (\PHP_VERSION_ID >= 70000) {
                $description = 'Pact\TypeError: Argument 1 passed to Pact\Promise::reject() must implement interface Throwable, ' . $type . ' given, called in ' . __FILE__ . ' on line ' . (__LINE__ + 11);
            } else {
                $description = 'Argument 1 passed to Pact\Promise::reject() must be an instance of Exception, ' . $type . ' given, called in ' . __FILE__ . ' on line ' . (__LINE__ + 9);
            }
        } else {
            $description = 'Assertion';
        }

        $errorCollector = new ErrorCollector();
        $errorCollector->start();

        Promise::reject($invalidReason);

        $errors = $errorCollector->stop();

        $this->assertEquals(E_WARNING, $errors[0]['errno']);
        $this->assertRegExp(
            '/^' . \preg_quote('assert(): ' . $description, '/') . '/',
            $errors[0]['errstr']
        );
    }

    /**
     * @test
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function it_does_not_call_internal_assert_class_from_reject_for_valid_rejection_reason()
    {
        Promise::reject(new \Exception());

        $this->assertFalse(\class_exists('Pact\TypeError', false), 'Pact\TypeError must not be loaded');
    }

    /**
     * @test
     * @dataProvider invalidCallbackDataProvider
     **/
    public function it_triggers_warning_from_then_for_invalid_fulfillment_callback($invalidCallable, $type)
    {
        if (\PHP_VERSION_ID >= 50408) {
            $description = 'Argument 1 passed to Pact\Promise::then() must be callable or null, ' . $type . ' given, called in ' . __FILE__ . ' on line ' . (__LINE__ + 13);

            if (\PHP_VERSION_ID >= 70000) {
                $description = 'Pact\TypeError: ' . $description;
            }
        } else {
            $description = 'Assertion';
        }

        $errorCollector = new ErrorCollector();
        $errorCollector->start();

        $promise = Promise::resolve();
        $promise->then($invalidCallable);

        $errors = $errorCollector->stop();

        $this->assertEquals(E_WARNING, $errors[0]['errno']);
        $this->assertRegExp(
            '/^' . \preg_quote('assert(): ' . $description, '/') . '/',
            $errors[0]['errstr']
        );
    }

    /**
     * @test
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function it_does_not_call_internal_assert_class_from_then_for_valid_fulfillment_callback()
    {
        $promise = Promise::resolve();
        $promise->then(function () {});

        $this->assertFalse(\class_exists('Pact\TypeError', false), 'Pact\TypeError must not be loaded');
    }

    /**
     * @test
     * @dataProvider invalidCallbackDataProvider
     **/
    public function it_triggers_warning_from_then_for_invalid_rejection_callback($invalidCallable, $type)
    {
        if (\PHP_VERSION_ID >= 50408) {
            $description = 'Argument 2 passed to Pact\Promise::then() must be callable or null, ' . $type . ' given, called in ' . __FILE__ . ' on line ' . (__LINE__ + 13);

            if (\PHP_VERSION_ID >= 70000) {
                $description = 'Pact\TypeError: ' . $description;
            }
        } else {
            $description = 'Assertion';
        }

        $errorCollector = new ErrorCollector();
        $errorCollector->start();

        $promise = Promise::reject(new \Exception());
        $promise->then(null, $invalidCallable);

        $errors = $errorCollector->stop();

        $this->assertEquals(E_WARNING, $errors[0]['errno']);
        $this->assertRegExp(
            '/^' . \preg_quote('assert(): ' . $description, '/') . '/',
            $errors[0]['errstr']
        );
    }

    /**
     * @test
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function it_does_not_call_internal_assert_class_from_then_for_valid_rejection_callback()
    {
        $promise = Promise::reject(new \Exception());
        $promise->then(null, function () {});

        $this->assertFalse(\class_exists('Pact\TypeError', false), 'Pact\TypeError must not be loaded');
    }

    /**
     * @test
     * @dataProvider invalidCallbackDataProvider
     **/
    public function it_triggers_warning_from_always_for_invalid_callback($invalidCallable, $type)
    {
        if (\PHP_VERSION_ID >= 50408) {
            $description = 'Argument 1 passed to Pact\Promise::always() must be callable, ' . $type . ' given, called in ' . __FILE__ . ' on line ' . (__LINE__ + 13);

            if (\PHP_VERSION_ID >= 70000) {
                $description = 'Pact\TypeError: ' . $description;
            }
        } else {
            $description = 'Assertion';
        }

        $errorCollector = new ErrorCollector();
        $errorCollector->start();

        $promise = Promise::resolve();
        $promise->always($invalidCallable);

        $errors = $errorCollector->stop();

        $this->assertEquals(E_WARNING, $errors[0]['errno']);
        $this->assertRegExp(
            '/^' . \preg_quote('assert(): ' . $description, '/') . '/',
            $errors[0]['errstr']
        );
    }

    /**
     * @test
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function it_does_not_call_internal_assert_class_from_always_for_valid_callback()
    {
        $promise = Promise::resolve();
        $promise->always(function () {});

        $this->assertFalse(\class_exists('Pact\TypeError', false), 'Pact\TypeError must not be loaded');
    }
}

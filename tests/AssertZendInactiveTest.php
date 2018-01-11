<?php

namespace Pact;

/**
 * @runTestsInSeparateProcesses
 * @requires PHP 7
 */
class AssertZendInactiveTest extends TestCase
{
    public function setUp()
    {
        if (\PHP_VERSION_ID >= 70000 && \ini_get('zend.assertions')) {
            $this->markTestSkipped('Assertions enabled with zend.assertions=1, run with `php -dzend.assertions=0 vendor/bin/phpunit`.');
        }

        parent::setUp();
    }

    /**
     * @test
     * @dataProvider invalidCallbackDataProvider
     */
    public function it_throws_from_constructor_for_invalid_resolver($invalidCallable, $type)
    {
        new Promise($invalidCallable);

        $this->assertFalse(class_exists('Pact\Assert', false), 'Pact\Assert must not be loaded');
    }

    /**
     * @test
     * @dataProvider invalidCallbackDataProvider
     */
    public function it_throws_from_constructor_for_invalid_canceller($invalidCallable, $type)
    {
        new Promise(null, $invalidCallable);

        $this->assertFalse(class_exists('Pact\Assert', false), 'Pact\Assert must not be loaded');
    }

    /**
     * @test
     * @dataProvider invalidReasonProvider
     **/
    public function it_throws_from_reject_for_invalid_rejection_reason($invalidReason, $type)
    {
        Promise::reject($invalidReason);

        $this->assertFalse(class_exists('Pact\Assert', false), 'Pact\Assert must not be loaded');
    }

    /**
     * @test
     * @dataProvider invalidCallbackDataProvider
     **/
    public function it_throws_from_then_for_invalid_fulfillment_callback($invalidCallable, $type)
    {
        $promise = Promise::resolve();
        $promise->then($invalidCallable);

        $this->assertFalse(class_exists('Pact\Assert', false), 'Pact\Assert must not be loaded');
    }

    /**
     * @test
     * @dataProvider invalidCallbackDataProvider
     **/
    public function it_throws_from_then_for_invalid_rejection_callback($invalidCallable, $type)
    {
        $promise = Promise::reject(new \Exception());
        $promise->then(null, $invalidCallable);

        $this->assertFalse(class_exists('Pact\Assert', false), 'Pact\Assert must not be loaded');
    }

    /**
     * @test
     * @dataProvider invalidCallbackDataProvider
     **/
    public function it_throws_from_always_for_invalid_callback($invalidCallable, $type)
    {
        $promise = Promise::resolve();
        $promise->always($invalidCallable);

        $this->assertFalse(class_exists('Pact\Assert', false), 'Pact\Assert must not be loaded');
    }
}

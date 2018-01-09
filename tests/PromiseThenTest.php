<?php

namespace Pact;

class PromiseThenTest extends TestCase
{
    /** @test */
    public function it_returns_a_promise_from_then_for_resolved_promise()
    {
        $promise = Promise::resolve();

        $this->assertInstanceOf('Pact\Promise', $promise->then());
    }


    /** @test */
    public function it_returns_a_promise_from_then_for_pending_promise()
    {
        $promise = new Promise(function () {});

        $this->assertInstanceOf('Pact\Promise', $promise->then());
    }

    /** @test */
    public function it_allows_null_for_callback_parameters_for_resolved_promise()
    {
        $promise = Promise::resolve();

        $this->assertInstanceOf('Pact\Promise', $promise->then(null, null));
    }

    /** @test */
    public function it_allows_null_for_callback_parameters_for_pending_promise()
    {
        $promise = new Promise(function () {
        });

        $this->assertInstanceOf('Pact\Promise', $promise->then(null, null));
    }

    /**
     * @test
     * @dataProvider invalidCallbackDataProvider
     **/
    public function it_throws_for_invalid_fulfillment_callback($invalidCallable, $type)
    {
        $class = new \ReflectionClass('Pact\Promise');
        $method = $class->getMethod('then');

        $this->setExpectedExceptionRegExp(
            '\TypeError',
            '/' . preg_quote('Argument 1 passed to Pact\Promise::then() must be callable or null, ' . $type . ' given, called in ' . $method->getFileName() . ' on line ' . $method->getStartLine(), '/') . '/'
        );

        $promise = Promise::resolve();
        $promise
            ->then(
                $invalidCallable
            );
    }

    /**
     * @test
     * @dataProvider invalidCallbackDataProvider
     **/
    public function it_throws_for_invalid_rejection_callback($invalidCallable, $type)
    {
        $class = new \ReflectionClass('Pact\Promise');
        $method = $class->getMethod('then');

        $this->setExpectedExceptionRegExp(
            '\TypeError',
            '/' . preg_quote('Argument 2 passed to Pact\Promise::then() must be callable or null, ' . $type . ' given, called in ' . $method->getFileName() . ' on line ' . $method->getStartLine(), '/') . '/'
        );

        $promise = Promise::reject(new \Exception());
        $promise
            ->then(
                null,
                $invalidCallable
            );
    }
}

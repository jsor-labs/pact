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
    public function it_ignores_non_callable_fulfillment_callback_and_triggers_php_warning($invalidCallable, $type)
    {
        $errorCollector = new ErrorCollector();
        $errorCollector->start();

        $mock = $this->createCallableMock();
        $mock
            ->expects($this->once())
            ->method('__invoke')
            ->with($this->identicalTo(1));

        $promise = Promise::resolve(1);
        $promise
            ->then(
                $invalidCallable
            )
            ->then(
                $mock,
                $this->expectCallableNever()
            );

        $errors = $errorCollector->stop();

        $this->assertEquals(E_USER_WARNING, $errors[0]['errno']);
        $this->assertContains('The $onFulfilled argument passed to then() must be null or callable, ' . $type . ' given.', $errors[0]['errstr']);
    }

    /**
     * @test
     * @dataProvider invalidCallbackDataProvider
     **/
    public function it_ignores_non_callable_rejection_callback_and_triggers_php_warning($invalidCallable, $type)
    {
        $errorCollector = new ErrorCollector();
        $errorCollector->start();

        $exception = new \Exception();

        $mock = $this->createCallableMock();
        $mock
            ->expects($this->once())
            ->method('__invoke')
            ->with($this->identicalTo($exception));

        $promise = Promise::reject($exception);
        $promise
            ->then(
                null,
                $invalidCallable
            )
            ->then(
                $this->expectCallableNever(),
                $mock
            );

        $errors = $errorCollector->stop();

        $this->assertEquals(E_USER_WARNING, $errors[0]['errno']);
        $this->assertContains('The $onRejected argument passed to then() must be null or callable, ' . $type . ' given.', $errors[0]['errstr']);
    }
}

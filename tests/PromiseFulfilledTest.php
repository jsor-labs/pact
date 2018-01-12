<?php

namespace Pact;

use Pact;

class PromiseFulfilledTest extends TestCase
{
    /** @test */
    public function it_makes_promise_immutable_after_fulfillment()
    {
        $promise = new Promise(function ($resolve) {
            $resolve(1);
            $resolve(2);
        });

        $mock = $this->createCallableMock();
        $mock
            ->expects($this->once())
            ->method('__invoke')
            ->with($this->identicalTo(1));

        $promise
            ->then(
                $mock,
                $this->expectCallableNever()
            );
    }

    /** @test */
    public function it_invokes_newly_added_callback_after_fulfillment()
    {
        $promise = new Promise(function ($resolve) {
            $resolve(1);
        });

        $mock = $this->createCallableMock();
        $mock
            ->expects($this->once())
            ->method('__invoke')
            ->with($this->identicalTo(1));

        $promise
            ->then($mock, $this->expectCallableNever());
    }

    /** @test */
    public function it_forwards_fulfillment_value_when_callback_is_null()
    {
        $promise = new Promise(function ($resolve) {
            $resolve(1);
        });

        $mock = $this->createCallableMock();
        $mock
            ->expects($this->once())
            ->method('__invoke')
            ->with($this->identicalTo(1));

        $promise
            ->then(
                null,
                $this->expectCallableNever()
            )
            ->then(
                $mock,
                $this->expectCallableNever()
            );
    }

    /** @test */
    public function it_forwards_fulfillment_value_from_callback()
    {
        $promise = new Promise(function ($resolve) {
            $resolve(1);
        });

        $mock = $this->createCallableMock();
        $mock
            ->expects($this->once())
            ->method('__invoke')
            ->with($this->identicalTo(2));

        $promise
            ->then(
                function ($val) {
                    return $val + 1;
                },
                $this->expectCallableNever()
            )
            ->then(
                $mock,
                $this->expectCallableNever()
            );
    }

    /** @test */
    public function it_forwards_fulfillment_value_from_callback_returned_as_promise()
    {
        $promise = new Promise(function ($resolve) {
            $resolve(1);
        });

        $mock = $this->createCallableMock();
        $mock
            ->expects($this->once())
            ->method('__invoke')
            ->with($this->identicalTo(2));

        $promise
            ->then(
                function ($val) {
                    return Pact\Promise::resolve($val + 1);
                },
                $this->expectCallableNever()
            )
            ->then(
                $mock,
                $this->expectCallableNever()
            );
    }

    /** @test */
    public function it_switches_to_rejection_when_fulfillment_callback_returns_a_rejected_promise()
    {
        $exception = new \Exception();

        $promise = new Promise(function ($resolve) {
            $resolve(1);
        });

        $mock = $this->createCallableMock();
        $mock
            ->expects($this->once())
            ->method('__invoke')
            ->with($this->identicalTo($exception));

        $promise
            ->then(
                function () use ($exception) {
                    return Pact\Promise::reject($exception);
                },
                $this->expectCallableNever()
            )
            ->then(
                $this->expectCallableNever(),
                $mock
            );
    }

    /** @test */
    public function it_switches_to_rejection_when_fulfillment_callback_throws_an_exception()
    {
        $promise = new Promise(function ($resolve) {
            $resolve(1);
        });

        $exception = new \Exception();

        $mock = $this->createCallableMock();
        $mock
            ->expects($this->once())
            ->method('__invoke')
            ->with($this->identicalTo($exception));

        $promise
            ->then(
                function () use ($exception) {
                    throw $exception;
                },
                $this->expectCallableNever()
            )
            ->then(
                $this->expectCallableNever(),
                $mock
            );
    }

    /**
     * @test
     * @requires PHP 7
     */
    public function it_switches_to_rejection_when_fulfillment_callback_throws_an_error()
    {
        $promise = new Promise(function ($resolve) {
            $resolve(1);
        });

        $exception = new \Error();

        $mock = $this->createCallableMock();
        $mock
            ->expects($this->once())
            ->method('__invoke')
            ->with($this->identicalTo($exception));

        $promise
            ->then(
                function () use ($exception) {
                    throw $exception;
                },
                $this->expectCallableNever()
            )
            ->then(
                $this->expectCallableNever(),
                $mock
            );
    }
}

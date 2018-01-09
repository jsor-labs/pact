<?php

namespace Pact;

use Pact;

class PromiseRejectedTest extends TestCase
{
    /** @test */
    public function it_makes_promise_immutable_after_rejection()
    {
        $exception = new \Exception();

        $promise = new Promise(function ($resolve, $reject) use ($exception) {
            $reject($exception);
            $reject(new \Exception());
        });

        $mock = $this->createCallableMock();
        $mock
            ->expects($this->once())
            ->method('__invoke')
            ->with($this->identicalTo($exception));

        $promise
            ->then(
                $this->expectCallableNever(),
                $mock
            );
    }

    /** @test */
    public function it_invokes_newly_added_callback_after_rejection()
    {
        $exception = new \Exception();

        $promise = new Promise(function ($resolve, $reject) use ($exception) {
            $reject($exception);
        });

        $mock = $this->createCallableMock();
        $mock
            ->expects($this->once())
            ->method('__invoke')
            ->with($this->identicalTo($exception));

        $promise
            ->then($this->expectCallableNever(), $mock);
    }

    /** @test */
    public function it_switches_from_rejection_to_fulfillment_when_rejection_handler_does_not_return()
    {
        $promise = new Promise(function ($resolve, $reject) {
            $reject(new \Exception());
        });

        $mock = $this->createCallableMock();
        $mock
            ->expects($this->once())
            ->method('__invoke')
            ->with(null);

        $promise
            ->then(
                $this->expectCallableNever(),
                function () {
                    // Presence of rejection handler is enough to switch back
                    // to resolve mode, even though it returns undefined.
                    // The ONLY way to propagate a rejection is to re-throw or
                    // return a rejected promise;
                }
            )
            ->then(
                $mock,
                $this->expectCallableNever()
            );
    }

    /** @test */
    public function it_switches_from_rejection_to_fulfillment_when_rejection_handler_returns_a_value()
    {
        $exception = new \Exception();

        $promise = new Promise(function ($resolve, $reject) use ($exception) {
            $reject($exception);
        });

        $mock = $this->createCallableMock();
        $mock
            ->expects($this->once())
            ->method('__invoke')
            ->with($this->identicalTo('foo'));

        $promise
            ->then(
                $this->expectCallableNever(),
                function () {
                    return 'foo';
                }
            )
            ->then(
                $mock,
                $this->expectCallableNever()
            );
    }

    /** @test */
    public function it_switches_from_rejection_to_fulfillment_when_rejection_handler_returns_a_fulfilled_promise()
    {
        $promise = new Promise(function ($resolve, $reject) {
            $reject(new \Exception());
        });

        $mock = $this->createCallableMock();
        $mock
            ->expects($this->once())
            ->method('__invoke')
            ->with($this->identicalTo(1));

        $promise
            ->then(
                $this->expectCallableNever(),
                function () {
                    return Pact\Promise::resolve(1);
                }
            )
            ->then(
                $mock,
                $this->expectCallableNever()
            );
    }

    /** @test */
    public function it_propagates_rejection_when_rejection_handler_throws_an_exception()
    {
        $exception = new \Exception();

        $promise = new Promise(function ($resolve, $reject) use ($exception) {
            $reject($exception);
        });

        $exception = new \Exception();

        $mock = $this->createCallableMock();
        $mock
            ->expects($this->once())
            ->method('__invoke')
            ->will($this->throwException($exception));

        $mock2 = $this->createCallableMock();
        $mock2
            ->expects($this->once())
            ->method('__invoke')
            ->with($this->identicalTo($exception));

        $promise
            ->then(
                $this->expectCallableNever(),
                $mock
            )
            ->then(
                $this->expectCallableNever(),
                $mock2
            );
    }

    /**
     * @test
     */
    public function it_propagates_rejection_when_rejection_handler_throws_an_error()
    {
        $exception = new \Error();

        $promise = new Promise(function ($resolve, $reject) use ($exception) {
            $reject($exception);
        });

        $exception = new \Exception();

        $mock = $this->createCallableMock();
        $mock
            ->expects($this->once())
            ->method('__invoke')
            ->will($this->throwException($exception));

        $mock2 = $this->createCallableMock();
        $mock2
            ->expects($this->once())
            ->method('__invoke')
            ->with($this->identicalTo($exception));

        $promise
            ->then(
                $this->expectCallableNever(),
                $mock
            )
            ->then(
                $this->expectCallableNever(),
                $mock2
            );
    }

    /** @test */
    public function it_propagates_rejection_when_rejection_handler_returns_rejected_promise()
    {
        $exception = new \Exception();

        $promise = new Promise(function ($resolve, $reject) use ($exception) {
            $reject($exception);
        });

        $mock = $this->createCallableMock();
        $mock
            ->expects($this->once())
            ->method('__invoke')
            ->with($this->identicalTo($exception));

        $promise
            ->then(
                $this->expectCallableNever(),
                function ($exception) {
                    return Pact\Promise::reject($exception);
                }
            )
            ->then(
                $this->expectCallableNever(),
                $mock
            );
    }
}

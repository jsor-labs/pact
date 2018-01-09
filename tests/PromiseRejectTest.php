<?php

namespace Pact;

use Pact;

class PromiseRejectTest extends TestCase
{
    /** @test */
    public function it_rejects_when_resolver_throws_an_exception()
    {
        $exception = new \Exception('foo');

        $promise = new Promise(function () use ($exception) {
            throw $exception;
        });

        $mock = $this->createCallableMock();
        $mock
            ->expects($this->once())
            ->method('__invoke')
            ->with($this->identicalTo($exception));

        $promise
            ->then($this->expectCallableNever(), $mock);
    }

    /**
     * @test
     */
    public function it_rejects_when_resolver_throws_an_error()
    {
        $exception = new \Error('foo');

        $promise = new Promise(function () use ($exception) {
            throw $exception;
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
    public function it_rejects_with_an_immediate_exception()
    {
        $promise = new Promise(function ($res, $rej) use (&$reject) {
            $reject = $rej;
        });

        $exception = new \Exception();

        $mock = $this->createCallableMock();
        $mock
            ->expects($this->once())
            ->method('__invoke')
            ->with($this->identicalTo($exception));

        $promise
            ->then($this->expectCallableNever(), $mock);

        $reject($exception);
    }

    /**
     * @test
     */
    public function it_rejects_with_an_immediate_error()
    {
        $promise = new Promise(function ($res, $rej) use (&$reject) {
            $reject = $rej;
        });

        $exception = new \Exception();

        $mock = $this->createCallableMock();
        $mock
            ->expects($this->once())
            ->method('__invoke')
            ->with($this->identicalTo($exception));

        $promise
            ->then($this->expectCallableNever(), $mock);

        $reject($exception);
    }

    /** @test */
    public function it_forwards_rejection_reason_when_callback_is_null()
    {
        $promise = new Promise(function ($res, $rej) use (&$reject) {
            $reject = $rej;
        });

        $exception = new \Exception();

        $mock = $this->createCallableMock();
        $mock
            ->expects($this->once())
            ->method('__invoke')
            ->with($this->identicalTo($exception));

        $promise
            ->then(
                $this->expectCallableNever()
            )
            ->then(
                $this->expectCallableNever(),
                $mock
            );

        $reject($exception);
    }

    /** @test */
    public function it_makes_promise_immutable_after_rejection()
    {
        $promise = new Promise(function ($res, $rej) use (&$reject) {
            $reject = $rej;
        });

        $exception = new \Exception('1');

        $mock = $this->createCallableMock();
        $mock
            ->expects($this->once())
            ->method('__invoke')
            ->with($this->identicalTo($exception));

        $promise
            ->then(null, function ($value) use (&$reject) {
                $reject(new \Exception('3'));

                return Pact\Promise::reject($value);
            })
            ->then(
                $this->expectCallableNever(),
                $mock
            );

        $reject($exception);
        $reject(new \Exception('2'));
    }

    /**
     * @test
     * @dataProvider invalidReasonProvider
     **/
    public function it_wraps_non_throwable_reasons_in_rejection_exception($invalidReason, $type, $repr)
    {
        $that = $this;

        $promise = new Promise(function ($res, $rej) use ($invalidReason) {
            $rej($invalidReason);
        });

        $failure = null;

        $promise
            ->then(null, function ($e) use ($that, $invalidReason, $type, $repr) {
                $that->assertInstanceOf('Pact\ReasonException', $e);

                $that->assertEquals('Promise rejected with ' . $repr, $e->getMessage());

                $that->assertSame($invalidReason, $e->getReason());
            })
            ->then(null, function ($e) use (&$failure) {
                $failure = $e;
            });

        if ($failure) {
            PromiseRejectTest::fail($failure);
        }
    }

    /**
     * @test
     **/
    public function it_wraps_missing_reason_in_rejection_exception()
    {
        $that = $this;

        $promise = new Promise(function ($res, $rej) {
            $rej();
        });

        $failure = null;

        $promise
            ->then(null, function ($e) use ($that) {
                $that->assertInstanceOf('Pact\ReasonException', $e);

                $that->assertEquals('Promise rejected with <NULL>', $e->getMessage());

                $that->assertNull($e->getReason());
            })
            ->then(null, function ($e) use (&$failure) {
                $failure = $e;
            });

        if ($failure) {
            PromiseRejectTest::fail($failure);
        }
    }
}

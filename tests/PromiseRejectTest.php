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
     * @requires PHP 7
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
    public function it_resolves_without_creating_garbage_cycles_if_resolver_resolves_with_an_exception()
    {
        \gc_collect_cycles();

        $promise = new Promise(function ($resolve) {
            $resolve(new \Exception('foo'));
        });

        unset($promise);

        $this->assertSame(0, \gc_collect_cycles());
    }

    /** @test */
    public function it_rejects_without_creating_garbage_cycles_if_resolver_throws_an_exception_without_resolver()
    {
        \gc_collect_cycles();

        $promise = new Promise(function () {
            throw new \Exception('foo');
        });

        unset($promise);

        $this->assertSame(0, \gc_collect_cycles());
    }

    /** @test */
    public function it_rejects_without_creating_garbage_cycles_if_resolver_rejects_with_an_exception()
    {
        \gc_collect_cycles();

        $promise = new Promise(function ($resolve, $reject) {
            $reject(new \Exception('foo'));
        });

        unset($promise);

        $this->assertSame(0, \gc_collect_cycles());
    }

    /** @test */
    public function it_rejects_without_creating_garbage_cycles_if_canceller_rejects_with_an_exception()
    {
        \gc_collect_cycles();

        $promise = new Promise(function ($resolve, $reject) { }, function ($resolve, $reject) {
            $reject(new \Exception('foo'));
        });

        $promise->cancel();

        unset($promise);

        $this->assertSame(0, \gc_collect_cycles());
    }

    /** @test */
    public function it_rejects_without_creating_garbage_cycles_if_parent_canceller_rejects_with_exception()
    {
        \gc_collect_cycles();

        $promise = new Promise(function ($resolve, $reject) { }, function ($resolve, $reject) {
            $reject(new \Exception('foo'));
        });

        $promise->then()->then()->then()->cancel();

        unset($promise);

        $this->assertSame(0, \gc_collect_cycles());
    }

    /** @test */
    public function it_rejects_without_creating_garbage_cycles_if_resolver_throws_an_exception()
    {
        \gc_collect_cycles();

        $promise = new Promise(function ($resolve, $reject) {
            throw new \Exception('foo');
        });

        unset($promise);

        $this->assertSame(0, \gc_collect_cycles());
    }

    /**
     * Test that checks number of garbage cycles after throwing from a canceller
     * that explicitly uses a reference to the promise. This is rather synthetic,
     * actual use cases often have implicit (hidden) references which ought not
     * to be stored in the stack trace.
     *
     * Reassigned arguments only show up in the stack trace in PHP 7, so we can't
     * avoid this on legacy PHP. As an alternative, consider explicitly unsetting
     * any references before throwing.
     *
     * @test
     * @requires PHP 7
     */
    public function it_rejects_without_creating_garbage_cycles_if_canceller_with_reference_throws_an_exception()
    {
        \gc_collect_cycles();

        $promise = new Promise(function () {}, function () use (&$promise) {
            throw new \Exception('foo');
        });

        $promise->cancel();

        unset($promise);

        $this->assertSame(0, \gc_collect_cycles());
    }

    /**
     * @test
     * @requires PHP 7
     * @see self::it_rejects_without_creating_garbage_cycles_if_canceller_with_reference_throws_an_exception
     */
    public function it_rejects_without_creating_garbage_cycles_if_resolver_with_reference_throws_an_exception()
    {
        \gc_collect_cycles();

        $promise = new Promise(function () use (&$promise) {
            throw new \Exception('foo');
        });

        unset($promise);

        $this->assertSame(0, \gc_collect_cycles());
    }

    /**
     * @test
     * @requires PHP 7
     * @see self::it_rejects_without_creating_garbage_cycles_if_canceller_with_reference_throws_an_exception
     */
    public function it_rejects_without_creating_garbage_cycles_if_canceller_holds_reference_and_resolver_throws_an_exception()
    {
        \gc_collect_cycles();

        $promise = new Promise(function () {
            throw new \Exception('foo');
        }, function () use (&$promise) { });

        unset($promise);

        $this->assertSame(0, \gc_collect_cycles());
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

                $that->assertEquals('Promise rejected with reason ' . $repr . '.', $e->getMessage());

                $that->assertTrue($e->hasReason());
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

                $that->assertEquals('Promise rejected without a reason.', $e->getMessage());

                $that->assertFalse($e->hasReason());
                $that->assertNull($e->getReason());
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
     */
    public function it_propagates_rejection_to_children()
    {
        $exception = new \Exception();

        $promise = new Promise(function ($res, $rej) use (&$reject) {
            $reject = $rej;
        });

        $mock = $this->createCallableMock();
        $mock
            ->expects($this->once())
            ->method('__invoke')
            ->with($this->identicalTo($exception));

        $promise
            ->then($this->expectCallableNever(), function ($reason) {
                throw $reason;
            })
            ->then($this->expectCallableNever(), $mock);

        $reject($exception);
    }

    /** @test */
    public function it_propagates_rejection_from_follower()
    {
        $exception = new \Exception();

        $root = new Promise(function ($res, $rej) use (&$reject) {
            $reject = $rej;
        });

        $promise1 = new Promise(function ($res) use ($root) {
            $res($root);
        });

        $promise2 = new Promise(function ($res) use ($promise1) {
            $res($promise1);
        });

        $mock = $this->createCallableMock();
        $mock
            ->expects($this->once())
            ->method('__invoke')
            ->with($this->identicalTo($exception));

        $promise1
            ->then($this->expectCallableNever(), $mock);

        $mock = $this->createCallableMock();
        $mock
            ->expects($this->once())
            ->method('__invoke')
            ->with($this->identicalTo($exception));

        $promise2
            ->then($this->expectCallableNever(), $mock);

        $reject($exception);
    }
}

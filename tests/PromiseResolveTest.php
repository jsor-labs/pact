<?php

namespace Pact;

use Pact;

class PromiseResolveTest extends TestCase
{
    /** @test */
    public function it_resolves_a_promise()
    {
        $promise = new Promise(function ($res) use (&$resolve) {
            $resolve = $res;
        });

        $mock = $this->createCallableMock();
        $mock
            ->expects($this->once())
            ->method('__invoke')
            ->with($this->identicalTo(1));

        $promise
            ->then($mock);

        $resolve(1);
    }

    /** @test */
    public function it_resolves_with_a_promised_value()
    {
        $promise = new Promise(function ($res) use (&$resolve) {
            $resolve = $res;
        });

        $mock = $this->createCallableMock();
        $mock
            ->expects($this->once())
            ->method('__invoke')
            ->with($this->identicalTo(1));

        $promise
            ->then($mock);

        $resolve(Pact\Promise::resolve(1));
    }

    /** @test */
    public function it_resolves_with_a_thenable()
    {
        $promise = new Promise(function ($res) use (&$resolve) {
            $resolve = $res;
        });

        $mock = $this->createCallableMock();
        $mock
            ->expects($this->once())
            ->method('__invoke')
            ->with($this->identicalTo(1));

        $promise
            ->then($mock);

        $resolve(new SimpleFulfilledTestThenable(1));
    }

    /** @test */
    public function it_rejects_when_resolved_with_a_rejected_promise()
    {
        $promise = new Promise(function ($res) use (&$resolve) {
            $resolve = $res;
        });

        $exception = new \Exception();

        $mock = $this->createCallableMock();
        $mock
            ->expects($this->once())
            ->method('__invoke')
            ->with($this->identicalTo($exception));

        $promise
            ->then($this->expectCallableNever(), $mock);

        $resolve(Pact\Promise::reject($exception));
    }

    /** @test */
    public function it_forwards_value_when_callback_is_null()
    {
        $promise = new Promise(function ($res) use (&$resolve) {
            $resolve = $res;
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

        $resolve(1);
    }

    /** @test */
    public function it_makes_promise_immutable_after_fulfillment()
    {
        $promise = new Promise(function ($res) use (&$resolve) {
            $resolve = $res;
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

        $resolve(1);
        $resolve(2);

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
    public function it_makes_promise_immutable_after_rejection()
    {
        $exception = new \Exception(1);

        $promise = new Promise(function ($res, $rej) use (&$reject) {
            $reject = $rej;
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

        $reject($exception);
        $reject(new \Exception(2));

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
    public function it_makes_promise_immutable_after_resolved_with_a_fulfilled_promise()
    {
        $promise = new Promise(function ($res) use (&$resolve) {
            $resolve = $res;
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

        $resolve(Promise::resolve(1));
        $resolve(Promise::resolve(2));

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
    public function it_makes_promise_immutable_after_resolved_with_a_rejected_promise()
    {
        $exception = new \Exception(1);

        $promise = new Promise(function ($res) use (&$resolve) {
            $resolve = $res;
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

        $resolve(Promise::reject($exception));
        $resolve(Promise::reject(new \Exception(2)));

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
    public function it_makes_promise_immutable_after_resolved_with_a_pending_promise_which_fulfills()
    {
        $promise = new Promise(function ($res) use (&$resolve) {
            $resolve = $res;
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

        $followee1 = new Promise(function ($res) use (&$resolveFollowee1) {
            $resolveFollowee1 = $res;
        });
        $followee2 = new Promise(function ($res) use (&$resolveFollowee2) {
            $resolveFollowee2 = $res;
        });

        $resolve($followee1);
        $resolve($followee2);

        // Explicitly resolve followee2 before followee1
        $resolveFollowee2(2);
        $resolveFollowee1(1);

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
    public function it_makes_promise_immutable_after_resolved_with_a_pending_promise_which_rejects()
    {
        $exception = new \Exception(1);

        $promise = new Promise(function ($res) use (&$resolve) {
            $resolve = $res;
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

        $followee1 = new Promise(function ($res, $rej) use (&$rejectFollowee1) {
            $rejectFollowee1 = $rej;
        });
        $followee2 = new Promise(function ($res, $rej) use (&$rejectFollowee2) {
            $rejectFollowee2 = $rej;
        });

        $resolve($followee1);
        $resolve($followee2);

        // Explicitly reject followee2 before followee1
        $rejectFollowee2(new \Exception(2));
        $rejectFollowee1($exception);

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
    public function it_makes_promise_immutable_after_resolved_with_a_fulfilled_thenable()
    {
        $promise = new Promise(function ($res) use (&$resolve) {
            $resolve = $res;
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

        $resolve(new SimpleFulfilledTestThenable(1));
        $resolve(new SimpleFulfilledTestThenable(2));

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
    public function it_makes_promise_immutable_after_resolved_with_a_rejected_thenable()
    {
        $exception = new \Exception(1);

        $promise = new Promise(function ($res) use (&$resolve) {
            $resolve = $res;
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

        $resolve(new SimpleRejectedTestThenable($exception));
        $resolve(new SimpleRejectedTestThenable(new \Exception(2)));

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

    /**
     * @test
     */
    public function it_makes_promise_immutable_after_resolved_with_a_pending_thenable_which_fulfills()
    {
        $promise = new Promise(function ($res) use (&$resolve) {
            $resolve = $res;
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

        $followee1 = new SimpleFulfillableTestThenable();
        $followee2 = new SimpleFulfillableTestThenable();

        $resolve($followee1);
        $resolve($followee2);

        // Explicitly resolve followee2 before followee1
        $followee2->fulfill(2);
        $followee1->fulfill(1);

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
    public function it_makes_promise_immutable_after_resolved_with_a_pending_thenable_which_rejects()
    {
        $exception = new \Exception(1);

        $promise = new Promise(function ($res) use (&$resolve) {
            $resolve = $res;
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

        $followee1 = new SimpleRejectableTestThenable();
        $followee2 = new SimpleRejectableTestThenable();

        $resolve($followee1);
        $resolve($followee2);

        // Explicitly reject followee2 before followee1
        $followee2->reject(new \Exception(2));
        $followee1->reject($exception);

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
    public function it_makes_promise_immutable_after_resolved_with_a_throwing_thenable()
    {
        $exception = new \Exception(1);

        $promise = new Promise(function ($res) use (&$resolve) {
            $resolve = $res;
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

        $followee1 = new SimpleThrowingTestThenable($exception);
        $followee2 = new SimpleThrowingTestThenable(new \Exception(2));

        $resolve($followee1);
        $resolve($followee2);

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

    /**
     * @test
     */
    public function it_makes_promise_immutable_after_resolved_with_a_pending_thenable_from_canceller_which_fulfills()
    {
        $promise = new Promise(
            function ($res) use (&$resolve) {
                $resolve = $res;
            },
            function ($res) use (&$cancellerResolve) {
                $cancellerResolve = $res;
            }
        );

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

        $promise->cancel();

        $followee1 = new SimpleFulfillableTestThenable();
        $followee2 = new SimpleFulfillableTestThenable();

        $cancellerResolve($followee1);
        $resolve($followee2);

        // Explicitly resolve followee2 before followee1
        $followee2->fulfill(2);
        $followee1->fulfill(1);

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
    public function it_makes_promise_immutable_after_resolved_with_a_pending_thenable_from_cancellerwhich_rejects()
    {
        $exception = new \Exception(1);

        $promise = new Promise(
            function ($res) use (&$resolve) {
                $resolve = $res;
            },
            function ($res) use (&$cancellerResolve) {
                $cancellerResolve = $res;
            }
        );

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

        $promise->cancel();

        $followee1 = new SimpleRejectableTestThenable();
        $followee2 = new SimpleRejectableTestThenable();

        $cancellerResolve($followee1);
        $resolve($followee2);

        // Explicitly reject followee2 before followee1
        $followee2->reject(new \Exception(2));
        $followee1->reject($exception);

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

    /**
     * @test
     */
    public function it_rejects_promise_when_resolved_with_itself()
    {
        $promise = new Promise(function ($res) use (&$resolve) {
            $resolve = $res;
        });

        $mock = $this->createCallableMock();
        $mock
            ->expects($this->once())
            ->method('__invoke')
            ->with(new Pact\LogicException('Cannot resolve a promise with itself.'));

        $promise
            ->then(
                $this->expectCallableNever(),
                $mock
            );

        $resolve($promise);
    }

    /**
     * @test
     */
    public function it_rejects_when_resolved_with_a_promise_which_follows_itself()
    {
        $promise1 = new Promise(function ($res) use (&$resolve1) {
            $resolve1 = $res;
        });

        $promise2 = new Promise(function ($res) use (&$resolve2) {
            $resolve2 = $res;
        });

        $promise3 = new Promise(function ($res) use (&$resolve3) {
            $resolve3 = $res;
        });

        $promise4 = new Promise(function ($res) use (&$resolve4) {
            $resolve4 = $res;
        });

        $promise5 = new Promise(function ($res) use (&$resolve5) {
            $resolve5 = $res;
        });

        $mock = $this->createCallableMock();
        $mock
            ->expects($this->once())
            ->method('__invoke')
            ->with(new Pact\LogicException('Cannot resolve a promise with itself.'));

        $promise2->then(
            $this->expectCallableNever(),
            $mock
        );

        $resolve1($promise5);

        $resolve5($promise4);
        $resolve4($promise3);
        $resolve3($promise2);
        $resolve2($promise1);
    }

    /**
     * @test
     */
    public function it_propagates_fulfillment_to_children()
    {
        $promise = new Promise(function ($res) use (&$resolve) {
            $resolve = $res;
        });

        $mock = $this->createCallableMock();
        $mock
            ->expects($this->once())
            ->method('__invoke')
            ->with($this->identicalTo(1));

        $promise
            ->then(function ($value) {
                return $value;
            }, $this->expectCallableNever())
            ->then($mock, $this->expectCallableNever());

        $resolve(1);
    }

    /** @test */
    public function it_propagates_fulfillment_from_follower()
    {
        $root = new Promise(function ($res) use (&$resolve) {
            $resolve = $res;
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
            ->with($this->identicalTo(1));

        $promise1
            ->then($mock, $this->expectCallableNever());

        $mock = $this->createCallableMock();
        $mock
            ->expects($this->once())
            ->method('__invoke')
            ->with($this->identicalTo(1));

        $promise2
            ->then($mock, $this->expectCallableNever());

        $resolve(1);
    }
}

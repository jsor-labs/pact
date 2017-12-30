<?php

namespace Pact;

use Pact;

class PromiseCancelTest extends TestCase
{
    /** @test */
    public function it_returns_null_from_cancel_for_resolved_promise()
    {
        $promise = new Promise(function ($resolve) {
            $resolve(1);
        });

        $this->assertNull($promise->cancel());
    }

    /** @test */
    public function it_returns_null_from_cancel_for_pending_promise()
    {
        $promise = new Promise(function () {});

        $this->assertNull($promise->cancel());
    }

    /** @test */
    public function it_does_not_invoke_canceller_for_fulfilled_promise()
    {
        $promise = new Promise(
            function ($resolve) {
                $resolve(1);
            },
            $this->expectCallableNever()
        );

        $promise->cancel();
    }

    /** @test */
    public function it_does_not_invoke_canceller_for_rejected_promise()
    {
        $promise = new Promise(
            function ($resolve, $reject) {
                $reject(new \Exception());
            },
            $this->expectCallableNever()
        );

        $promise->cancel();
    }

    /** @test */
    public function it_invokes_canceller_with_resolver_arguments()
    {
        $mock = $this->createCallableMock();
        $mock
            ->expects($this->once())
            ->method('__invoke')
            ->with($this->isType('callable'), $this->isType('callable'));

        $promise = new Promise(function () {}, $mock);

        $promise->cancel();
    }

    /** @test */
    public function it_fulfills_promise_from_cancellers()
    {
        $promise = new Promise(
            function () {},
            function ($resolve) {
                $resolve(1);
            }
        );

        $mock = $this->createCallableMock();
        $mock
            ->expects($this->once())
            ->method('__invoke')
            ->with($this->identicalTo(1));

        $promise
            ->then($mock, $this->expectCallableNever());

        $promise->cancel();
    }

    /** @test */
    public function it_rejects_promise_from_cancellers()
    {
        $exception = new \Exception();

        $promise = new Promise(
            function () {},
            function ($resolve, $reject) use ($exception) {
                $reject($exception);
            }
        );

        $mock = $this->createCallableMock();
        $mock
            ->expects($this->once())
            ->method('__invoke')
            ->with($this->identicalTo($exception));

        $promise
            ->then($this->expectCallableNever(), $mock);

        $promise->cancel();
    }

    /** @test */
    public function it_rejects_promise_when_canceller_throws()
    {
        $exception = new \Exception();

        $promise = new Promise(
            function () {},
            function () use ($exception) {
                throw $exception;
            }
        );

        $mock = $this->createCallableMock();
        $mock
            ->expects($this->once())
            ->method('__invoke')
            ->with($this->identicalTo($exception));

        $promise
            ->then($this->expectCallableNever(), $mock);

        $promise->cancel();
    }

    /** @test */
    public function it_invokes_canceller_only_once_when_canceller_fulfills()
    {
        $mock = $this->createCallableMock();
        $mock
            ->expects($this->once())
            ->method('__invoke')
            ->will($this->returnCallback(function ($resolve) {
                $resolve();
            }));

        $promise = new Promise(function () {}, $mock);

        $promise->cancel();
        $promise->cancel();
    }

    /** @test */
    public function it_invokes_canceller_from_deep_nested_promise_chain()
    {
        $promise = new Promise(
            function () {},
            $this->expectCallableOnce()
        );

        $promise = $promise
            ->then(function () {
                return new Pact\Promise(function () {});
            })
            ->then(function () {
                return new Pact\Promise(function () {});
            })
            ->then(function () {
                return new Pact\Promise(function () {});
            });

        $promise->cancel();
    }

    /** @test */
    public function it_invokes_canceller_only_once()
    {
        $promise = new Promise(
            function () {},
            $this->expectCallableOnce()
        );

        $promise->cancel();
        $promise->cancel();
    }

    /** @test */
    public function it_does_not_cancel_when_not_all_children_cancel()
    {
        $promise = new Promise(
            function () {},
            $this->expectCallableNever()
        );

        $child1 = $promise
            ->then()
            ->then();

        $promise
            ->then();

        $child1->cancel();
    }

    /** @test */
    public function it_cancels_when_all_children_cancel()
    {
        $promise = new Promise(
            function () {},
            $this->expectCallableOnce()
        );

        $child1 = $promise
            ->then()
            ->then();

        $child2 = $promise
            ->then();

        $child1->cancel();
        $child2->cancel();
    }

    /** @test */
    public function it_does_not_cancel_when_one_children_cancels_multiple_times()
    {
        $promise = new Promise(
            function () {},
            $this->expectCallableNever()
        );

        $child1 = $promise
            ->then()
            ->then();

        $child2 = $promise
            ->then();

        $child1->cancel();
        $child1->cancel();
    }

    /** @test */
    public function it_always_invokes_canceller_when_root_cancels()
    {
        $promise = new Promise(
            function () {},
            $this->expectCallableOnce()
        );

        $promise
            ->then()
            ->then();

        $promise
            ->then();

        $promise->cancel();
    }

    /** @test */
    public function it_invokes_canceller_when_follower_cancels()
    {
        $root = new Promise(
            function () {},
            $this->expectCallableOnce()
        );

        $follower = new Promise(
            function ($resolve) use ($root) {
                $resolve($root);
            },
            $this->expectCallableOnce()
        );

        $follower->cancel();
    }

    /** @test */
    public function it_invokes_cancellation_chain_upwards()
    {
        $sequence = '';

        $promise1 = new Promise(
            function () {},
            function () use (&$sequence) {
                $sequence .= '4';
            }
        );

        $promise2 = new Promise(
            function ($res) use ($promise1) {
                $res($promise1);
            },
            function () use (&$sequence) {
                $sequence .= '3';
            }
        );

        $promise3 = new Promise(
            function ($res) use ($promise2) {
                $res($promise2);
            },
            function () use (&$sequence) {
                $sequence .= '2';
            }
        );

        $promise4 = new Promise(
            function ($res) use ($promise3) {
                $res($promise3);
            },
            function () use (&$sequence) {
                $sequence .= '1';
            }
        );

        $promise4->cancel();

        $this->assertEquals('1234', $sequence);
    }

    /** @test */
    public function it_breaks_upward_cancellation_chaon_when_one_followee_has_another_follower()
    {
        $sequence = '';

        $promise1 = new Promise(
            function () {},
            function () use (&$sequence) {
                $sequence .= '3';
            }
        );

        // Break chain by creating an additional child promise
        $promise1->then();

        $promise2 = new Promise(
            function ($res) use ($promise1) {
                $res($promise1);
            },
            function () use (&$sequence) {
                $sequence .= '2';
            }
        );

        $promise3 = new Promise(
            function ($res) use ($promise2) {
                $res($promise2);
            },
            function () use (&$sequence) {
                $sequence .= '1';
            }
        );

        $promise3->cancel();

        $this->assertEquals('12', $sequence);
    }

    /** @test */
    public function it_does_not_invoke_canceller_when_only_one_follower_cancels()
    {
        $root = new Promise(
            function () {},
            $this->expectCallableNever()
        );

        $follower1 = new Promise(
            function ($resolve) use ($root) {
                $resolve($root);
            },
            $this->expectCallableOnce()
        );

        new Promise(
            function ($resolve) use ($root) {
                $resolve($root);
            },
            $this->expectCallableNever()
        );

        $follower1->cancel();
    }

    /** @test */
    public function it_invokes_canceller_only_when_all_children_and_follower_cancel()
    {
        $root = new Promise(
            function () {},
            $this->expectCallableOnce()
        );

        $child = $root->then();

        $follower = new Promise(
            function ($resolve) use ($root) {
                $resolve($root);
            },
            $this->expectCallableOnce()
        );

        $follower->cancel();
        $child->cancel();
    }

    /** @test */
    public function it_does_not_invoke_canceller_when_follower_cancels_but_not_children()
    {
        $root = new Promise(
            function () {},
            $this->expectCallableNever()
        );

        $root->then();

        $follower = new Promise(
            function ($resolve) use ($root) {
                $resolve($root);
            },
            $this->expectCallableOnce()
        );

        $follower->cancel();
    }
}

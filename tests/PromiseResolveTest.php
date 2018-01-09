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
    public function it_makes_promise_immutable_after_resolution()
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
            ->then(function ($value) use (&$resolve) {
                $resolve(3);

                return $value;
            })
            ->then(
                $mock,
                $this->expectCallableNever()
            );

        $resolve(1);
        $resolve(2);
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
}

<?php

namespace Pact;

use Pact;

class PromiseInspectionTest extends TestCase
{
    /** @test */
    public function it_inspects_a_pending_promise()
    {
        $promise = new Promise(function () {});

        $this->assertFalse($promise->isFulfilled());
        $this->assertFalse($promise->isRejected());
        $this->assertTrue($promise->isPending());
        $this->assertFalse($promise->isCancelled());
    }

    /** @test */
    public function it_inspects_a_fulfilled_promise()
    {
        $promise = Promise::resolve(1);

        $this->assertTrue($promise->isFulfilled());
        $this->assertFalse($promise->isRejected());
        $this->assertFalse($promise->isPending());
        $this->assertFalse($promise->isCancelled());
        $this->assertSame(1, $promise->value());
    }

    /** @test */
    public function it_inspects_a_rejected_promise()
    {
        $exception = new \Exception();

        $promise = Promise::reject($exception);

        $this->assertFalse($promise->isFulfilled());
        $this->assertTrue($promise->isRejected());
        $this->assertFalse($promise->isPending());
        $this->assertFalse($promise->isCancelled());
        $this->assertSame($exception, $promise->reason());
    }

    /** @test */
    public function it_inspects_a_promise_resolved_with_a_fulfilled_promise()
    {
        $promise = new Promise(function ($resolve) {
            $resolve(Promise::resolve(1));
        });

        $this->assertTrue($promise->isFulfilled());
        $this->assertFalse($promise->isRejected());
        $this->assertFalse($promise->isPending());
        $this->assertFalse($promise->isCancelled());
        $this->assertSame(1, $promise->value());
    }

    /** @test */
    public function it_inspects_a_promise_resolved_with_a_rejected_promise()
    {
        $exception = new \Exception();

        $promise = new Promise(function ($resolve) use ($exception) {
            $resolve(Promise::reject($exception));
        });

        $this->assertFalse($promise->isFulfilled());
        $this->assertTrue($promise->isRejected());
        $this->assertFalse($promise->isPending());
        $this->assertFalse($promise->isCancelled());
        $this->assertSame($exception, $promise->reason());
    }

    /** @test */
    public function it_inspects_a_promise_resolved_with_a_pending_promise()
    {
        $promise = new Promise(function ($resolve) {
            $resolve(new Promise(function () {}));
        });

        $this->assertFalse($promise->isFulfilled());
        $this->assertFalse($promise->isRejected());
        $this->assertTrue($promise->isPending());
        $this->assertFalse($promise->isCancelled());
    }

    /** 
     * @test
     * @expectedException Pact\Exception\LogicException
     * @expectedExceptionMessage Cannot get the fulfillment value of a non-fulfilled promise.
     */
    public function it_throws_when_getting_value_from_a_pending_promise()
    {
        $promise = new Promise(function () {});

        $promise->value();
    }

    /**
     * @test
     * @expectedException Pact\Exception\LogicException
     * @expectedExceptionMessage Cannot get the fulfillment value of a non-fulfilled promise.
     */
    public function it_throws_when_getting_value_from_a_rejected_promise()
    {
        $promise = Promise::reject(new \Exception());

        $promise->value();
    }

    /**
     * @test
     * @expectedException Pact\Exception\LogicException
     * @expectedExceptionMessage Cannot get the rejection reason of a non-rejected promise.
     */
    public function it_throws_when_getting_reason_from_a_pending_promise()
    {
        $promise = new Promise(function () {});

        $promise->reason();
    }

    /**
     * @test
     * @expectedException Pact\Exception\LogicException
     * @expectedExceptionMessage Cannot get the rejection reason of a non-rejected promise.
     */
    public function it_throws_when_getting_reason_from_a_fulfilled_promise()
    {
        $promise = Promise::resolve(1);

        $promise->reason();
    }

    /** @test */
    public function it_inspects_a_cancelled_promise_resolved_from_canceller()
    {
        $promise = new Promise(
            function () {},
            function ($resolve) {
                $resolve(1);
            }
        );

        $promise->cancel();

        $this->assertTrue($promise->isFulfilled());
        $this->assertFalse($promise->isRejected());
        $this->assertFalse($promise->isPending());
        $this->assertTrue($promise->isCancelled());
        $this->assertSame(1, $promise->value());
    }

    /** @test */
    public function it_inspects_a_cancelled_promise_rejected_from_canceller()
    {
        $exception = new \Exception();

        $promise = new Promise(
            function () {},
            function ($resolve, $reject) use ($exception) {
                $reject($exception);
            }
        );

        $promise->cancel();

        $this->assertFalse($promise->isFulfilled());
        $this->assertTrue($promise->isRejected());
        $this->assertFalse($promise->isPending());
        $this->assertTrue($promise->isCancelled());
        $this->assertSame($exception, $promise->reason());
    }

    /** @test */
    public function it_inspects_a_cancelled_promise_when_canceller_does_noting()
    {
        $promise = new Promise(function () {});

        $promise->cancel();

        $this->assertFalse($promise->isFulfilled());
        $this->assertFalse($promise->isRejected());
        $this->assertTrue($promise->isPending());
        $this->assertTrue($promise->isCancelled());
    }
}
